#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import json
from collections import defaultdict
from dataclasses import dataclass
from pathlib import Path


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f, delimiter="\t"))


def normalize_method(value: str | None) -> str | None:
    if not value:
        return None
    v = value.strip().upper()
    return v or None


def normalize_path(value: str | None) -> str | None:
    if not value:
        return None
    v = value.strip()
    if not v:
        return None
    if not v.startswith("/"):
        v = "/" + v
    return v


@dataclass(frozen=True)
class CaseRef:
    file: Path
    case_id: str
    method: str | None
    path: str | None


def load_cases(cases_dir: Path) -> list[CaseRef]:
    cases: list[CaseRef] = []
    for p in sorted(cases_dir.rglob("*.json")):
        raw = json.loads(p.read_text(encoding="utf-8"))
        case_id = str(raw.get("id") or p.stem)
        req = raw.get("request") or {}
        method = normalize_method(req.get("method"))
        path = normalize_path(req.get("path"))
        cases.append(CaseRef(file=p, case_id=case_id, method=method, path=path))
    return cases


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Check that miniapp API usage list is covered by golden cases."
    )
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="Backend repo root containing docs/rewrite/ (default: scripts/..).",
    )
    parser.add_argument(
        "--usage-tsv",
        type=Path,
        default=None,
        help="Miniapp usage tsv (default: docs/rewrite/miniapp-api-usage.tsv).",
    )
    parser.add_argument(
        "--cases-dir",
        type=Path,
        default=None,
        help="Golden cases dir (default: docs/rewrite/golden/cases/p0).",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root
    usage_path = args.usage_tsv or (repo_root / "docs" / "rewrite" / "miniapp-api-usage.tsv")
    cases_dir = args.cases_dir or (repo_root / "docs" / "rewrite" / "golden" / "cases" / "p0")

    if not usage_path.exists():
        raise SystemExit(f"missing file: {usage_path}")
    if not cases_dir.exists():
        raise SystemExit(f"missing dir: {cases_dir}")

    usage_rows = read_tsv(usage_path)
    usage_keys: set[tuple[str, str]] = set()
    usage_locations: dict[tuple[str, str], list[str]] = defaultdict(list)
    for r in usage_rows:
        m = normalize_method(r.get("method"))
        p = normalize_path(r.get("full_path"))
        if not m or not p:
            continue
        key = (m, p)
        usage_keys.add(key)
        loc = f"{r.get('file','')}:{r.get('line','')}"
        if loc != ":":
            usage_locations[key].append(loc)

    cases = load_cases(cases_dir)
    case_keys = {(c.method, c.path) for c in cases if c.method and c.path}

    missing = sorted(k for k in usage_keys if k not in case_keys)

    print(f"miniapp usage endpoints: {len(usage_keys)}  ({usage_path})")
    print(f"golden case files:      {len(cases)}  ({cases_dir})")

    if not missing:
        print("OK: all miniapp endpoints are covered by golden cases.")
        return 0

    print(f"MISSING: {len(missing)}")
    for m, p in missing:
        locs = usage_locations.get((m, p)) or []
        loc_hint = f"  used at: {', '.join(locs[:3])}" if locs else ""
        print(f"- {m} {p}{loc_hint}")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())

