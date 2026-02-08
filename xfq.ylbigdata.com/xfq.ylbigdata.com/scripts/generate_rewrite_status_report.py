#!/usr/bin/env python3
from __future__ import annotations

import argparse
import ast
import csv
import json
from collections import defaultdict
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path
from typing import Iterable


GROUP_ORDER = {
    "A0-external-callbacks": 0,
    "A1-money": 1,
    "A2-identity-upload": 2,
    "B1-miniapp": 3,
    "B2-jobs": 4,
    "C-remaining": 9,
}


@dataclass(frozen=True)
class CaseCoverage:
    method_path: set[tuple[str, str]]
    any_path: set[str]


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f, delimiter="\t"))


def _extract_methods(keyword_value: ast.AST | None) -> set[str]:
    if keyword_value is None:
        return {"GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD"}

    if isinstance(keyword_value, ast.List | ast.Tuple | ast.Set):
        methods: set[str] = set()
        for item in keyword_value.elts:
            if isinstance(item, ast.Constant) and isinstance(item.value, str):
                methods.add(item.value.upper())
        if methods:
            return methods
    return {"GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD"}


def parse_fastapi_routes(main_py: Path) -> dict[str, set[str]]:
    tree = ast.parse(main_py.read_text(encoding="utf-8"), filename=str(main_py))
    routes: dict[str, set[str]] = {}
    single_method_map = {
        "get": "GET",
        "post": "POST",
        "put": "PUT",
        "patch": "PATCH",
        "delete": "DELETE",
        "head": "HEAD",
        "options": "OPTIONS",
    }

    for node in ast.walk(tree):
        if not isinstance(node, ast.FunctionDef | ast.AsyncFunctionDef):
            continue
        for deco in node.decorator_list:
            if not isinstance(deco, ast.Call):
                continue
            if not isinstance(deco.func, ast.Attribute):
                continue
            if not isinstance(deco.func.value, ast.Name):
                continue
            if deco.func.value.id != "fastapi_app":
                continue

            route_call = deco.func.attr
            if route_call not in set(single_method_map.keys()) | {"api_route"}:
                continue
            if not deco.args:
                continue
            if not isinstance(deco.args[0], ast.Constant) or not isinstance(deco.args[0].value, str):
                continue

            route_path = deco.args[0].value
            if not route_path.startswith("/"):
                continue
            if route_path.startswith("/{"):
                continue

            if route_call == "api_route":
                kw_methods = None
                for kw in deco.keywords:
                    if kw.arg == "methods":
                        kw_methods = kw.value
                        break
                methods = _extract_methods(kw_methods)
            else:
                methods = {single_method_map[route_call]}

            routes.setdefault(route_path, set()).update(methods)
    return routes


def load_case_coverage(cases_dir: Path) -> CaseCoverage:
    method_path: set[tuple[str, str]] = set()
    any_path: set[str] = set()
    if not cases_dir.exists():
        return CaseCoverage(method_path=method_path, any_path=any_path)

    for file in sorted(cases_dir.rglob("*.json")):
        try:
            payload = json.loads(file.read_text(encoding="utf-8"))
        except Exception:
            continue
        req = payload.get("request") if isinstance(payload, dict) else None
        if not isinstance(req, dict):
            continue
        method = str(req.get("method") or "").upper()
        path = str(req.get("path") or "")
        if not path.startswith("/"):
            continue
        if method:
            method_path.add((method, path))
        any_path.add(path)
    return CaseCoverage(method_path=method_path, any_path=any_path)


def _phase_for_group(group: str) -> str:
    if group == "A0-external-callbacks":
        return "M1"
    if group == "A1-money":
        return "M1"
    if group == "A2-identity-upload":
        return "M1"
    if group == "B1-miniapp":
        return "M2"
    if group == "B2-jobs":
        return "M3"
    return "M3"


def build_board(
    backlog: list[dict[str, str]],
    routes: dict[str, set[str]],
    p0_cov: CaseCoverage,
    p0_success_cov: CaseCoverage,
) -> list[dict[str, str]]:
    out: list[dict[str, str]] = []
    for row in backlog:
        method = str(row.get("method") or "UNKNOWN").upper()
        path = str(row.get("path") or "")
        path_html = str(row.get("path_html") or "")
        methods = set()
        if path in routes:
            methods.update(routes[path])
        if path_html in routes:
            methods.update(routes[path_html])

        implemented = bool(methods)
        if not implemented:
            python_status = "stub-only"
            method_match = "no"
        else:
            if method == "UNKNOWN":
                method_match = "unknown"
                python_status = "implemented"
            elif method in methods:
                method_match = "yes"
                python_status = "implemented"
            else:
                method_match = "no"
                python_status = "implemented-method-mismatch"

        covered_p0 = (
            (method, path) in p0_cov.method_path
            or (method, path_html) in p0_cov.method_path
            or path in p0_cov.any_path
            or path_html in p0_cov.any_path
        )
        covered_p0_success = (
            (method, path) in p0_success_cov.method_path
            or (method, path_html) in p0_success_cov.method_path
            or path in p0_success_cov.any_path
            or path_html in p0_success_cov.any_path
        )

        out.append(
            {
                "phase": _phase_for_group(str(row.get("group") or "")),
                "group": str(row.get("group") or ""),
                "tag": str(row.get("tag") or ""),
                "miniapp_used": str(row.get("miniapp_used") or "no"),
                "method": method,
                "path": path,
                "path_html": path_html,
                "python_status": python_status,
                "route_methods": ",".join(sorted(methods)),
                "method_compatible": method_match,
                "golden_p0": "yes" if covered_p0 else "no",
                "golden_p0_success": "yes" if covered_p0_success else "no",
                "auth_required": str(row.get("auth_required") or ""),
                "auth": str(row.get("auth") or ""),
                "contract": str(row.get("contract") or ""),
                "app": str(row.get("app") or ""),
                "controller": str(row.get("controller") or ""),
                "action": str(row.get("action") or ""),
                "desc": str(row.get("desc") or ""),
                "file": str(row.get("file") or ""),
                "line": str(row.get("line") or ""),
            }
        )
    out.sort(
        key=lambda r: (
            GROUP_ORDER.get(r["group"], 99),
            0 if r["python_status"] == "stub-only" else 1,
            r["path"],
        )
    )
    return out


def write_board(rows: list[dict[str, str]], out_path: Path) -> None:
    out_path.parent.mkdir(parents=True, exist_ok=True)
    headers = [
        "phase",
        "group",
        "tag",
        "miniapp_used",
        "method",
        "path",
        "path_html",
        "python_status",
        "route_methods",
        "method_compatible",
        "golden_p0",
        "golden_p0_success",
        "auth_required",
        "auth",
        "contract",
        "app",
        "controller",
        "action",
        "desc",
        "file",
        "line",
    ]
    with out_path.open("w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, delimiter="\t", fieldnames=headers)
        writer.writeheader()
        writer.writerows(rows)


def _stats_by_group(rows: Iterable[dict[str, str]]) -> dict[str, dict[str, int]]:
    stats: dict[str, dict[str, int]] = defaultdict(lambda: {"total": 0, "implemented": 0, "stub": 0})
    for r in rows:
        group = r["group"]
        stats[group]["total"] += 1
        if r["python_status"] == "stub-only":
            stats[group]["stub"] += 1
        else:
            stats[group]["implemented"] += 1
    return dict(stats)


def write_summary(rows: list[dict[str, str]], out_path: Path) -> None:
    out_path.parent.mkdir(parents=True, exist_ok=True)

    total = len(rows)
    implemented = sum(1 for r in rows if r["python_status"] != "stub-only")
    stub_only = sum(1 for r in rows if r["python_status"] == "stub-only")
    mismatch = sum(1 for r in rows if r["python_status"] == "implemented-method-mismatch")
    miniapp_total = sum(1 for r in rows if r["miniapp_used"] == "yes")
    miniapp_impl = sum(1 for r in rows if r["miniapp_used"] == "yes" and r["python_status"] != "stub-only")
    success_covered = sum(1 for r in rows if r["golden_p0_success"] == "yes")
    by_group = _stats_by_group(rows)

    stub_rows = [r for r in rows if r["python_status"] == "stub-only"]
    top_next = stub_rows[:40]

    lines: list[str] = []
    lines.append("# 重写状态报告（自动生成）")
    lines.append("")
    lines.append(f"- 生成时间：`{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}`")
    lines.append(f"- P0 总接口：`{total}`")
    lines.append(f"- Python 真实实现：`{implemented}`")
    lines.append(f"- 仍为 stub-only：`{stub_only}`")
    lines.append(f"- 已实现但 Method 需确认：`{mismatch}`")
    lines.append(f"- 小程序接口实现进度：`{miniapp_impl}/{miniapp_total}`")
    lines.append(f"- 含成功路径用例接口：`{success_covered}`")
    lines.append("")
    lines.append("## 分组进度")
    lines.append("")
    for group in sorted(by_group.keys(), key=lambda x: GROUP_ORDER.get(x, 99)):
        s = by_group[group]
        lines.append(
            f"- `{group}`: implemented `{s['implemented']}` / total `{s['total']}`, "
            f"stub `{s['stub']}`"
        )
    lines.append("")
    lines.append("## 下阶段优先接口（stub-only Top 40）")
    lines.append("")
    for r in top_next:
        lines.append(
            f"- `{r['group']}` `{r['method']}` `{r['path']}` "
            f"(miniapp={r['miniapp_used']}, tag={r['tag']})"
        )

    out_path.write_text("\n".join(lines) + "\n", encoding="utf-8")


def main() -> int:
    parser = argparse.ArgumentParser(description="Generate rewrite migration board + summary report.")
    parser.add_argument(
        "--project-root",
        type=Path,
        default=Path(__file__).resolve().parent.parent,
        help="Backend project root (default: scripts/..).",
    )
    parser.add_argument(
        "--backlog",
        type=Path,
        default=None,
        help="P0 backlog TSV (default: docs/rewrite/p0-backlog.tsv).",
    )
    parser.add_argument(
        "--main-py",
        type=Path,
        default=None,
        help="FastAPI main.py (default: rewrite_py/app/main.py).",
    )
    parser.add_argument(
        "--cases-p0",
        type=Path,
        default=None,
        help="P0 case dir (default: docs/rewrite/golden/cases/p0).",
    )
    parser.add_argument(
        "--cases-p0-success",
        type=Path,
        default=None,
        help="P0 success case dir (default: docs/rewrite/golden/cases/p0_success).",
    )
    parser.add_argument(
        "--out-board",
        type=Path,
        default=None,
        help="Output board TSV (default: docs/rewrite/p0-migration-board.tsv).",
    )
    parser.add_argument(
        "--out-summary",
        type=Path,
        default=None,
        help="Output summary Markdown (default: docs/rewrite/reports/rewrite-status.md).",
    )
    args = parser.parse_args()

    root = args.project_root.resolve()
    backlog_path = args.backlog.resolve() if args.backlog else root / "docs" / "rewrite" / "p0-backlog.tsv"
    main_py = args.main_py.resolve() if args.main_py else root / "rewrite_py" / "app" / "main.py"
    cases_p0 = args.cases_p0.resolve() if args.cases_p0 else root / "docs" / "rewrite" / "golden" / "cases" / "p0"
    cases_p0_success = (
        args.cases_p0_success.resolve()
        if args.cases_p0_success
        else root / "docs" / "rewrite" / "golden" / "cases" / "p0_success"
    )
    out_board = (
        args.out_board.resolve()
        if args.out_board
        else root / "docs" / "rewrite" / "p0-migration-board.tsv"
    )
    out_summary = (
        args.out_summary.resolve()
        if args.out_summary
        else root / "docs" / "rewrite" / "reports" / "rewrite-status.md"
    )

    backlog = read_tsv(backlog_path)
    routes = parse_fastapi_routes(main_py)
    p0_cov = load_case_coverage(cases_p0)
    p0_success_cov = load_case_coverage(cases_p0_success)
    board = build_board(backlog, routes, p0_cov, p0_success_cov)

    write_board(board, out_board)
    write_summary(board, out_summary)

    print(f"Wrote board: {out_board}")
    print(f"Wrote summary: {out_summary}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
