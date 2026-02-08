#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
from pathlib import Path


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f, delimiter="\t"))


def write_tsv(path: Path, rows: list[dict[str, str]], fieldnames: list[str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as f:
        w = csv.DictWriter(f, delimiter="\t", fieldnames=fieldnames)
        w.writeheader()
        for r in rows:
            w.writerow({k: (r.get(k) or "") for k in fieldnames})


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Generate SYSTEM_TASK inventory from docs/rewrite/p0-backlog.tsv."
    )
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="Backend repo root containing docs/rewrite/ (default: scripts/..).",
    )
    parser.add_argument(
        "--out",
        type=Path,
        default=None,
        help="Output TSV path (default: docs/rewrite/system-tasks.tsv).",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root
    backlog_path = repo_root / "docs" / "rewrite" / "p0-backlog.tsv"
    if not backlog_path.exists():
        raise SystemExit(f"missing file: {backlog_path}")

    rows = read_tsv(backlog_path)
    system_rows = [r for r in rows if (r.get("tag") or "").strip() == "SYSTEM_TASK"]

    out = args.out or (repo_root / "docs" / "rewrite" / "system-tasks.tsv")
    fieldnames = [
        "group",
        "tag",
        "method",
        "path",
        "path_html",
        "app",
        "controller",
        "action",
        "auth",
        "auth_required",
        "desc",
        "contract",
        "file",
        "line",
    ]
    write_tsv(out, system_rows, fieldnames)

    print(f"backlog: {len(rows)} ({backlog_path})")
    print(f"SYSTEM_TASK: {len(system_rows)}")
    print(f"output: {out}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

