#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
from dataclasses import dataclass
from pathlib import Path


REQUEST_START_RE = re.compile(r"""\brequest\s*\(\s*\{""")
PATH_RE = re.compile(r"""path\s*:\s*(?P<q>['"])(?P<path>.*?)(?P=q)""")
METHOD_RE = re.compile(r"""method\s*:\s*(?P<q>['"])(?P<method>[A-Za-z]+)(?P=q)""")
DATA_RE = re.compile(r"""\bdata\s*:\s*""")


@dataclass(frozen=True)
class MiniappCall:
    module: str
    method: str
    path: str
    full_path: str
    file: str
    line: int
    data_keys: tuple[str, ...]


def _extract_object_literal(text: str, open_brace_idx: int) -> tuple[str, int]:
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


def _split_top_level_csv(inner: str) -> list[str]:
    parts: list[str] = []
    buf: list[str] = []
    depth = 0
    in_str: str | None = None
    escaped = False

    for ch in inner:
        if in_str is not None:
            buf.append(ch)
            if escaped:
                escaped = False
                continue
            if ch == "\\":
                escaped = True
                continue
            if ch == in_str:
                in_str = None
            continue

        if ch in ("'", '"', "`"):
            in_str = ch
            buf.append(ch)
            continue
        if ch in ("{", "[", "("):
            depth += 1
            buf.append(ch)
            continue
        if ch in ("}", "]", ")"):
            depth = max(0, depth - 1)
            buf.append(ch)
            continue
        if ch == "," and depth == 0:
            part = "".join(buf).strip()
            if part:
                parts.append(part)
            buf = []
            continue
        buf.append(ch)

    tail = "".join(buf).strip()
    if tail:
        parts.append(tail)
    return parts


def _parse_object_keys(obj: str) -> tuple[str, ...]:
    s = obj.strip()
    if not (s.startswith("{") and s.endswith("}")):
        return tuple()
    inner = s[1:-1].strip()
    if not inner:
        return tuple()

    keys: list[str] = []
    for part in _split_top_level_csv(inner):
        raw = part.strip()
        if not raw:
            continue
        if raw.startswith("..."):
            continue
        if raw.startswith("["):
            # computed keys not supported
            continue

        # key: value  OR  key = value  OR shorthand key
        key = raw
        for sep in (":", "=", " "):
            if sep in key:
                key = key.split(sep, 1)[0].strip()
                break
        if not key:
            continue
        if (key.startswith("'") and key.endswith("'")) or (key.startswith('"') and key.endswith('"')):
            key = key[1:-1]
        if not re.match(r"^[A-Za-z_][A-Za-z0-9_]*$", key):
            continue
        keys.append(key)

    # keep order, dedupe
    seen: set[str] = set()
    out: list[str] = []
    for k in keys:
        if k in seen:
            continue
        seen.add(k)
        out.append(k)
    return tuple(out)


def _guess_value(key: str) -> object:
    k = key.lower()
    if k in {"page", "p", "page_no", "pageno"}:
        return 1
    if k in {"page_size", "pagesize", "limit", "size"}:
        return 12
    if k in {"latitude", "lat"}:
        return 0
    if k in {"longitude", "lng"}:
        return 0
    if k in {"mobile", "phone"}:
        return "13800000000"
    if k in {"type"}:
        return "login"
    if k == "status":
        return ""
    if k == "keyword":
        return ""
    if k == "ids":
        return "1"
    if k.endswith("_id") or k == "id" or k.endswith("id"):
        return "1"
    return ""


def iter_service_files(repo_root: Path) -> list[Path]:
    base = repo_root / "xfq-miniapp" / "mp-native" / "miniprogram"
    if not base.exists():
        return []
    candidates = []
    candidates.extend(sorted((base / "services" / "api").glob("*.js")))
    candidates.extend([base / "services" / "system.js"])
    return [p for p in candidates if p.exists()]


def parse_miniapp_calls(file_path: Path) -> list[MiniappCall]:
    text = file_path.read_text(encoding="utf-8", errors="ignore")
    module = file_path.stem

    out: list[MiniappCall] = []
    for m in REQUEST_START_RE.finditer(text):
        open_brace_idx = m.end() - 1
        try:
            obj, _end = _extract_object_literal(text, open_brace_idx)
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

        # try extract data keys: data: { ... }
        data_keys: tuple[str, ...] = tuple()
        md = DATA_RE.search(obj)
        if md:
            rest = obj[md.end() :]
            rest = rest.lstrip()
            if rest.startswith("{"):
                try:
                    data_obj, _ = _extract_object_literal(rest, 0)
                    data_keys = _parse_object_keys(data_obj)
                except ValueError:
                    data_keys = tuple()

        full_path = "/api" + path
        abs_idx = open_brace_idx + mp.start()
        line = text.count("\n", 0, abs_idx) + 1

        out.append(
            MiniappCall(
                module=module,
                method=method,
                path=path,
                full_path=full_path,
                file=str(file_path),
                line=line,
                data_keys=data_keys,
            )
        )

    # dedupe by method+full_path
    seen: set[tuple[str, str]] = set()
    uniq: list[MiniappCall] = []
    for r in out:
        key = (r.method, r.full_path)
        if key in seen:
            continue
        seen.add(key)
        uniq.append(r)
    uniq.sort(key=lambda r: (r.full_path, r.method, r.module))
    return uniq


def _slugify_path(path: str) -> str:
    p = path.strip()
    if p.startswith("/"):
        p = p[1:]
    p = p.replace("/", "-")
    p = p.replace(".", "-")
    p = re.sub(r"[^A-Za-z0-9_-]+", "-", p)
    p = re.sub(r"-{2,}", "-", p).strip("-")
    return p or "root"


def write_case(path: Path, payload: dict) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def main() -> int:
    parser = argparse.ArgumentParser(description="Generate golden case stubs from miniapp (mp-native) services.")
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[3],
        help="Repo root that contains xfq-miniapp/ (default: three levels up from scripts/).",
    )
    parser.add_argument(
        "--out-dir",
        type=Path,
        default=None,
        help="Output dir (default: <backend>/docs/rewrite/golden/cases/p0/miniapp).",
    )
    parser.add_argument(
        "--overwrite",
        action="store_true",
        help="Overwrite existing case files.",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root.resolve()
    backend_root = repo_root / "xfq.ylbigdata.com" / "xfq.ylbigdata.com"
    out_dir: Path = (
        args.out_dir.resolve()
        if args.out_dir
        else (backend_root / "docs" / "rewrite" / "golden" / "cases" / "p0" / "miniapp")
    )

    files = iter_service_files(repo_root)
    calls: list[MiniappCall] = []
    for f in files:
        calls.extend(parse_miniapp_calls(f))

    created = 0
    skipped = 0

    for c in calls:
        slug = _slugify_path(c.full_path)
        fname = f"p0-miniapp-{c.method.lower()}-{slug}-001.json"
        out_path = out_dir / fname
        if out_path.exists() and not args.overwrite:
            skipped += 1
            continue

        fields = {k: _guess_value(k) for k in c.data_keys}
        req: dict = {
            "method": c.method,
            "path": c.full_path,
            "query": fields if c.method.upper() == "GET" else {},
            "headers": {"Token": "${API_TOKEN}", "Userid": "${API_USERID}"},
        }
        if c.method.upper() != "GET":
            req["body"] = {"type": "form", "form": fields}

        payload = {
            "id": f"p0-miniapp-{c.method.lower()}-{slug}-001",
            "meta": {
                "tier": "p0",
                "source": "miniapp",
                "module": c.module,
                "source_file": c.file,
                "source_line": c.line,
                "notes": "运行前设置 API_TOKEN/API_USERID；必要时补齐 form/query 参数后再录制 baseline",
            },
            "request": req,
            "response": {
                "status": 200,
                "headers": {"Content-Type": "application/json"},
                "body": {"type": "json", "json": {}},
            },
            "compare": {"mode": "json", "ignore_json_keys": ["time"], "ignore_json_pointers": []},
        }
        write_case(out_path, payload)
        created += 1

    print(f"output dir: {out_dir}")
    print(f"miniapp calls: {len(calls)}")
    print(f"created: {created}")
    print(f"skipped: {skipped}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

