#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import json
from dataclasses import dataclass
from pathlib import Path


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f, delimiter="\t"))


def normalize_method(value: str | None) -> str | None:
    if not value:
        return None
    v = value.strip().upper()
    if not v or v == "UNKNOWN":
        return None
    return v


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
        try:
            raw = json.loads(p.read_text(encoding="utf-8"))
        except json.JSONDecodeError as e:
            raise SystemExit(f"invalid json: {p} ({e})") from e

        case_id = str(raw.get("id") or p.stem)
        req = raw.get("request") or {}
        method = normalize_method(req.get("method"))
        path = normalize_path(req.get("path"))
        cases.append(CaseRef(file=p, case_id=case_id, method=method, path=path))
    return cases


def method_matches(interface_method: str | None, case_method: str | None) -> bool:
    if interface_method is None:
        return True
    if case_method is None:
        return True
    return interface_method == case_method


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Check that golden cases cover tier interfaces list (p0/p1/p2)."
    )
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="Repository root containing docs/rewrite/*.tsv (default: scripts/..)",
    )
    parser.add_argument(
        "--tier",
        choices=["p0", "p1", "p2"],
        default="p0",
        help="Which interfaces list to check (default: p0).",
    )
    parser.add_argument(
        "--cases-dir",
        type=Path,
        default=None,
        help="Cases directory (default: docs/rewrite/golden/cases/<tier>).",
    )
    parser.add_argument(
        "--require-non-html",
        action="store_true",
        help="Require coverage for non-.html path variant.",
    )
    parser.add_argument(
        "--require-html",
        action="store_true",
        help="Require coverage for .html path variant (when present).",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root
    tier: str = args.tier

    interfaces_path = repo_root / "docs" / "rewrite" / f"{tier}-interfaces.tsv"
    if not interfaces_path.exists():
        raise SystemExit(f"missing file: {interfaces_path}")

    cases_dir = args.cases_dir or (repo_root / "docs" / "rewrite" / "golden" / "cases" / tier)
    if not cases_dir.exists():
        raise SystemExit(f"missing dir: {cases_dir}")

    interface_rows = read_tsv(interfaces_path)
    cases = load_cases(cases_dir)

    # index cases by path
    cases_by_path: dict[str, list[CaseRef]] = {}
    for c in cases:
        if not c.path:
            continue
        cases_by_path.setdefault(c.path, []).append(c)

    def covered(path: str | None, method: str | None) -> bool:
        if not path:
            return False
        for c in cases_by_path.get(path, []):
            if method_matches(method, c.method):
                return True
        return False

    missing_any: list[dict[str, str]] = []
    missing_non_html: list[dict[str, str]] = []
    missing_html: list[dict[str, str]] = []

    for r in interface_rows:
        interface_method = normalize_method(r.get("method"))
        p = normalize_path(r.get("path"))
        p_html = normalize_path(r.get("path_html"))

        cov_non_html = covered(p, interface_method)
        cov_html = covered(p_html, interface_method) if p_html else False

        if args.require_non_html and not cov_non_html:
            missing_non_html.append(r)
        if args.require_html and p_html and not cov_html:
            missing_html.append(r)

        if not cov_non_html and not cov_html:
            missing_any.append(r)

    print(f"tier interfaces: {len(interface_rows)}  ({interfaces_path})")
    print(f"golden cases:   {len(cases)}  ({cases_dir})")

    covered_any = len(interface_rows) - len(missing_any)
    print(f"covered (any variant): {covered_any}/{len(interface_rows)}")

    if args.require_non_html:
        covered_non_html = len(interface_rows) - len(missing_non_html)
        print(f"covered (non-html):    {covered_non_html}/{len(interface_rows)}")
    if args.require_html:
        # only count rows that have html variant
        has_html = sum(1 for r in interface_rows if (r.get("path_html") or "").strip())
        covered_html = has_html - len(missing_html)
        print(f"covered (html):        {covered_html}/{has_html}")

    failed = False
    if missing_any:
        failed = True
        print(f"\nMISSING (no case for both variants): {len(missing_any)}")
        for r in missing_any[:200]:
            m = (r.get("method") or "UNKNOWN").strip()
            p = (r.get("path") or "").strip()
            app = (r.get("app") or "").strip()
            controller = (r.get("controller") or "").strip()
            action = (r.get("action") or "").strip()
            file = (r.get("file") or "").strip()
            line = (r.get("line") or "").strip()
            loc = f"{file}:{line}" if file or line else ""
            print(f"- {m} {p}  ({app}:{controller}.{action})  {loc}")

    if args.require_non_html and missing_non_html:
        failed = True
        print(f"\nMISSING non-html variant: {len(missing_non_html)}")
        for r in missing_non_html[:200]:
            m = (r.get("method") or "UNKNOWN").strip()
            p = (r.get("path") or "").strip()
            print(f"- {m} {p}")

    if args.require_html and missing_html:
        failed = True
        print(f"\nMISSING html variant: {len(missing_html)}")
        for r in missing_html[:200]:
            m = (r.get("method") or "UNKNOWN").strip()
            p = (r.get("path_html") or "").strip()
            print(f"- {m} {p}")

    if failed:
        print("\nTIP: add cases under docs/rewrite/golden/cases/<tier>/")
        return 1
    print("\nOK: golden cases coverage looks good.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

