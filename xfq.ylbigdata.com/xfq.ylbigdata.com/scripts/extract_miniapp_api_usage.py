#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import re
from dataclasses import dataclass
from pathlib import Path


PATH_RE = re.compile(r"""path\s*:\s*(?P<q>['"])(?P<path>.*?)(?P=q)""")
METHOD_RE = re.compile(r"""method\s*:\s*(?P<q>['"])(?P<method>[A-Za-z]+)(?P=q)""")
REQUEST_START_RE = re.compile(r"""\brequest\s*\(\s*\{""")


@dataclass(frozen=True)
class UsageRow:
    client: str
    module: str
    method: str
    path: str
    full_path: str
    file: str
    line: int


def iter_service_files(root: Path) -> list[Path]:
    # mp-native miniprogram
    base = root / "xfq-miniapp" / "mp-native" / "miniprogram"
    if not base.exists():
        return []
    candidates = []
    candidates.extend(sorted((base / "services" / "api").glob("*.js")))
    candidates.extend([base / "services" / "system.js"])
    return [p for p in candidates if p.exists()]


def parse_usage(file_path: Path, client: str) -> list[UsageRow]:
    text = file_path.read_text(encoding="utf-8", errors="ignore")
    module = file_path.stem

    def extract_object_literal(open_brace_idx: int) -> tuple[str, int]:
        if open_brace_idx < 0 or open_brace_idx >= len(text) or text[open_brace_idx] != "{":
            raise ValueError("open_brace_idx must point to '{'")

        depth = 0
        i = open_brace_idx
        in_str: str | None = None
        escaped = False
        in_line_comment = False
        in_block_comment = False

        while i < len(text):
            ch = text[i]
            nxt = text[i + 1] if i + 1 < len(text) else ""

            if in_line_comment:
                if ch == "\n":
                    in_line_comment = False
                i += 1
                continue

            if in_block_comment:
                if ch == "*" and nxt == "/":
                    in_block_comment = False
                    i += 2
                    continue
                i += 1
                continue

            if in_str is not None:
                if escaped:
                    escaped = False
                    i += 1
                    continue
                if ch == "\\":
                    escaped = True
                    i += 1
                    continue
                if ch == in_str:
                    in_str = None
                i += 1
                continue

            if ch == "/" and nxt == "/":
                in_line_comment = True
                i += 2
                continue
            if ch == "/" and nxt == "*":
                in_block_comment = True
                i += 2
                continue
            if ch in ("'", '"', "`"):
                in_str = ch
                i += 1
                continue

            if ch == "{":
                depth += 1
            elif ch == "}":
                depth -= 1
                if depth == 0:
                    return text[open_brace_idx : i + 1], i + 1
            i += 1

        raise ValueError("unterminated object literal")

    out: list[UsageRow] = []
    for m in REQUEST_START_RE.finditer(text):
        open_brace_idx = m.end() - 1  # points to '{'
        try:
            obj, _end = extract_object_literal(open_brace_idx)
        except ValueError:
            continue

        mp = PATH_RE.search(obj)
        if not mp:
            continue
        path = mp.group("path")
        if not path.startswith("/"):
            continue

        method = "POST"
        mm = METHOD_RE.search(obj)
        if mm:
            method = mm.group("method").upper()

        full_path = "/api" + path
        abs_idx = open_brace_idx + mp.start()
        line = text.count("\n", 0, abs_idx) + 1
        out.append(
            UsageRow(
                client=client,
                module=module,
                method=method,
                path=path,
                full_path=full_path,
                file=str(file_path),
                line=line,
            )
        )
    return out


def write_tsv(rows: list[UsageRow], output: Path) -> None:
    output.parent.mkdir(parents=True, exist_ok=True)
    with output.open("w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f, delimiter="\t")
        writer.writerow(["client", "module", "method", "path", "full_path", "file", "line"])
        for r in rows:
            writer.writerow([r.client, r.module, r.method, r.path, r.full_path, r.file, r.line])


def main() -> int:
    parser = argparse.ArgumentParser(description="Extract miniapp (mp-native) API usage paths.")
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[3],
        help="Repo root that contains xfq-miniapp/ (default: three levels up from scripts/).",
    )
    parser.add_argument(
        "--out",
        type=Path,
        default=None,
        help="Output TSV path (default: <backend>/docs/rewrite/miniapp-api-usage.tsv)",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root.resolve()
    backend_root = repo_root / "xfq.ylbigdata.com" / "xfq.ylbigdata.com"
    out_path: Path = args.out.resolve() if args.out else (backend_root / "docs" / "rewrite" / "miniapp-api-usage.tsv")

    files = iter_service_files(repo_root)
    rows: list[UsageRow] = []
    for f in files:
        rows.extend(parse_usage(f, client="xfq-miniapp/mp-native"))

    # dedupe by method+full_path
    seen: set[tuple[str, str]] = set()
    uniq: list[UsageRow] = []
    for r in rows:
        key = (r.method, r.full_path)
        if key in seen:
            continue
        seen.add(key)
        uniq.append(r)
    uniq.sort(key=lambda r: (r.full_path, r.method, r.module))

    write_tsv(uniq, out_path)
    print(f"Wrote {len(uniq)} rows to {out_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
