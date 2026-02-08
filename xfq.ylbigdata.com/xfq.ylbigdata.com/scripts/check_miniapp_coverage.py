#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
from collections import defaultdict
from pathlib import Path


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f, delimiter="\t"))


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Check that miniapp (mp-native) API usage is covered by P0 interfaces list."
    )
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="Repository root containing docs/rewrite/*.tsv (default: scripts/..)",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root
    usage_path = repo_root / "docs" / "rewrite" / "miniapp-api-usage.tsv"
    p0_path = repo_root / "docs" / "rewrite" / "p0-interfaces.tsv"

    if not usage_path.exists():
        raise SystemExit(f"missing file: {usage_path}")
    if not p0_path.exists():
        raise SystemExit(f"missing file: {p0_path}")

    usage_rows = read_tsv(usage_path)
    p0_rows = read_tsv(p0_path)

    usage_locations: dict[str, list[str]] = defaultdict(list)
    usage_paths: set[str] = set()
    for r in usage_rows:
        full_path = (r.get("full_path") or "").strip()
        if not full_path:
            continue
        usage_paths.add(full_path)
        loc = f"{r.get('file','')}:{r.get('line','')}"
        if loc != ":":
            usage_locations[full_path].append(loc)

    p0_paths = {(r.get("path") or "").strip() for r in p0_rows if (r.get("path") or "").strip()}
    p0_paths_html = {
        (r.get("path_html") or "").strip()
        for r in p0_rows
        if (r.get("path_html") or "").strip()
    }

    missing = sorted(p for p in usage_paths if p not in p0_paths and p not in p0_paths_html)

    print(f"miniapp usage paths: {len(usage_paths)}")
    print(f"p0 interfaces paths: {len(p0_paths)}")
    if not missing:
        print("OK: all miniapp paths are covered by P0 interfaces list.")
        return 0

    print(f"MISSING: {len(missing)}")
    for p in missing:
        locs = usage_locations.get(p) or []
        loc_hint = f"  used at: {', '.join(locs[:3])}" if locs else ""
        print(f"- {p}{loc_hint}")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())

