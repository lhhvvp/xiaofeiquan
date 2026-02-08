#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import re
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class Hit:
    kind: str
    subject: str
    id_value: str
    file: str
    line: int
    code: str


MODEL_FIND = re.compile(r"\b([A-Z][A-Za-z0-9_\\\\]*)::find\(\s*([0-9]+)\s*\)")
DB_NAME_FIND = re.compile(
    r"\bDb::name\(\s*['\"]([a-zA-Z0-9_]+)['\"]\s*\).*?->find\(\s*([0-9]+)\s*\)"
)
DB_HELPER_FIND = re.compile(
    r"\bdb\(\s*['\"]([a-zA-Z0-9_]+)['\"]\s*\).*?->find\(\s*([0-9]+)\s*\)"
)
DB_NAME_WHERE_ID = re.compile(
    r"\bDb::name\(\s*['\"]([a-zA-Z0-9_]+)['\"]\s*\).*?->where\(\s*['\"]id['\"]\s*,\s*(?:['\"]=)?\s*([0-9]+)\s*\)"
)
DB_HELPER_WHERE_ID = re.compile(
    r"\bdb\(\s*['\"]([a-zA-Z0-9_]+)['\"]\s*\).*?->where\(\s*['\"]id['\"]\s*,\s*(?:['\"]=)?\s*([0-9]+)\s*\)"
)


def scan_php_files(root: Path) -> list[Hit]:
    hits: list[Hit] = []
    for p in sorted(root.rglob("*.php")):
        rel = str(p.relative_to(root.parent))
        try:
            text = p.read_text(encoding="utf-8", errors="replace")
        except Exception:
            continue
        for i, line in enumerate(text.splitlines(), start=1):
            for m in MODEL_FIND.finditer(line):
                hits.append(
                    Hit(
                        kind="model_find",
                        subject=m.group(1),
                        id_value=m.group(2),
                        file=rel,
                        line=i,
                        code=line.strip(),
                    )
                )
            for m in DB_NAME_FIND.finditer(line):
                hits.append(
                    Hit(
                        kind="db_name_find",
                        subject=m.group(1),
                        id_value=m.group(2),
                        file=rel,
                        line=i,
                        code=line.strip(),
                    )
                )
            for m in DB_HELPER_FIND.finditer(line):
                hits.append(
                    Hit(
                        kind="db_helper_find",
                        subject=m.group(1),
                        id_value=m.group(2),
                        file=rel,
                        line=i,
                        code=line.strip(),
                    )
                )
            for m in DB_NAME_WHERE_ID.finditer(line):
                hits.append(
                    Hit(
                        kind="db_name_where_id",
                        subject=m.group(1),
                        id_value=m.group(2),
                        file=rel,
                        line=i,
                        code=line.strip(),
                    )
                )
            for m in DB_HELPER_WHERE_ID.finditer(line):
                hits.append(
                    Hit(
                        kind="db_helper_where_id",
                        subject=m.group(1),
                        id_value=m.group(2),
                        file=rel,
                        line=i,
                        code=line.strip(),
                    )
                )
    return hits


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Scan PHP code for hardcoded ID lookups that likely require seed data."
    )
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="Repository root (default: scripts/..).",
    )
    parser.add_argument(
        "--out",
        type=Path,
        default=None,
        help="Output tsv path (default: docs/rewrite/seed-dependencies.tsv).",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root
    app_root = repo_root / "app"
    if not app_root.exists():
        raise SystemExit(f"missing dir: {app_root}")

    out_path = args.out or (repo_root / "docs" / "rewrite" / "seed-dependencies.tsv")
    out_path.parent.mkdir(parents=True, exist_ok=True)

    hits = scan_php_files(app_root)

    with out_path.open("w", encoding="utf-8", newline="") as f:
        w = csv.writer(f, delimiter="\t")
        w.writerow(["kind", "subject", "id", "file", "line", "code"])
        for h in hits:
            w.writerow([h.kind, h.subject, h.id_value, h.file, str(h.line), h.code])

    print(f"scanned: {app_root}")
    print(f"hits:    {len(hits)}")
    print(f"output:  {out_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

