#!/usr/bin/env python3
from __future__ import annotations

import argparse
import base64
import csv
import json
from collections import Counter
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class Issue:
    case_id: str
    tier: str
    method: str
    path: str
    status: int
    content_type: str
    issue_type: str
    details: str
    file: str


def iter_case_files(cases_root: Path) -> list[Path]:
    if not cases_root.exists():
        return []
    return sorted(cases_root.rglob("*.json"))


def _decode_raw_base64(text: str) -> str:
    if not text:
        return ""
    try:
        raw = base64.b64decode(text, validate=False)
    except Exception:
        return ""
    try:
        return raw.decode("utf-8", errors="ignore")
    except Exception:
        return ""


def detect_issues(case_file: Path) -> list[Issue]:
    try:
        payload = json.loads(case_file.read_text(encoding="utf-8"))
    except Exception as e:
        return [
            Issue(
                case_id=case_file.stem,
                tier="unknown",
                method="UNKNOWN",
                path="",
                status=0,
                content_type="",
                issue_type="invalid_json",
                details=str(e),
                file=str(case_file),
            )
        ]

    req = payload.get("request") if isinstance(payload, dict) else {}
    rsp = payload.get("response") if isinstance(payload, dict) else {}
    meta = payload.get("meta") if isinstance(payload, dict) else {}
    headers = rsp.get("headers") if isinstance(rsp, dict) else {}
    body = rsp.get("body") if isinstance(rsp, dict) else {}

    case_id = str(payload.get("id") or case_file.stem)
    tier = str(meta.get("tier") or "unknown")
    method = str(req.get("method") or "UNKNOWN").upper()
    path = str(req.get("path") or "")
    status = int(rsp.get("status") or 0)
    content_type = str(headers.get("Content-Type") or headers.get("content-type") or "")
    body_type = str(body.get("type") or "")

    issues: list[Issue] = []

    if status >= 500:
        issues.append(
            Issue(
                case_id=case_id,
                tier=tier,
                method=method,
                path=path,
                status=status,
                content_type=content_type,
                issue_type="status_5xx",
                details="response status >= 500",
                file=str(case_file),
            )
        )

    if body_type == "raw_base64":
        base64_text = str(body.get("base64") or "")
        if base64_text == "":
            issues.append(
                Issue(
                    case_id=case_id,
                    tier=tier,
                    method=method,
                    path=path,
                    status=status,
                    content_type=content_type,
                    issue_type="empty_raw_body",
                    details="raw_base64 body is empty",
                    file=str(case_file),
                )
            )
        decoded = _decode_raw_base64(base64_text)
        if "<title>500</title>" in decoded or "500.png" in decoded:
            issues.append(
                Issue(
                    case_id=case_id,
                    tier=tier,
                    method=method,
                    path=path,
                    status=status,
                    content_type=content_type,
                    issue_type="legacy_500_html_page",
                    details="detected default 500 html page",
                    file=str(case_file),
                )
            )

    if "text/html" in content_type.lower() and body_type == "json":
        issues.append(
            Issue(
                case_id=case_id,
                tier=tier,
                method=method,
                path=path,
                status=status,
                content_type=content_type,
                issue_type="content_type_mismatch",
                details="json body with text/html content-type",
                file=str(case_file),
            )
        )

    if status == 200 and body_type == "raw_base64" and not str(body.get("base64") or ""):
        issues.append(
            Issue(
                case_id=case_id,
                tier=tier,
                method=method,
                path=path,
                status=status,
                content_type=content_type,
                issue_type="empty_200_body",
                details="HTTP 200 with empty raw body",
                file=str(case_file),
            )
        )

    return issues


def write_issues_tsv(issues: list[Issue], out_path: Path) -> None:
    out_path.parent.mkdir(parents=True, exist_ok=True)
    with out_path.open("w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f, delimiter="\t")
        writer.writerow(
            [
                "case_id",
                "tier",
                "method",
                "path",
                "status",
                "content_type",
                "issue_type",
                "details",
                "file",
            ]
        )
        for i in issues:
            writer.writerow(
                [
                    i.case_id,
                    i.tier,
                    i.method,
                    i.path,
                    i.status,
                    i.content_type,
                    i.issue_type,
                    i.details,
                    i.file,
                ]
            )


def write_report_md(total_cases: int, issues: list[Issue], out_path: Path) -> None:
    out_path.parent.mkdir(parents=True, exist_ok=True)
    by_type = Counter(i.issue_type for i in issues)
    by_path = Counter((i.method, i.path) for i in issues)

    lines: list[str] = []
    lines.append("# Golden 质量审计报告（自动生成）")
    lines.append("")
    lines.append(f"- 用例总数：`{total_cases}`")
    lines.append(f"- 检出问题条目：`{len(issues)}`")
    lines.append(f"- 受影响接口数：`{len(by_path)}`")
    lines.append("")
    lines.append("## 问题类型统计")
    lines.append("")
    for issue_type, count in by_type.most_common():
        lines.append(f"- `{issue_type}`: `{count}`")
    lines.append("")
    lines.append("## 受影响接口 Top 40")
    lines.append("")
    for (method, path), count in by_path.most_common(40):
        lines.append(f"- `{method}` `{path}`: `{count}`")
    lines.append("")
    lines.append("## 处理建议")
    lines.append("")
    lines.append("- 对 `status_5xx` / `legacy_500_html_page` 用例优先重录 baseline。")
    lines.append("- 重录前执行 `make db-reset-minimal` 或 `make db-reset-success`，保证可重复。")
    lines.append("- 重录后立即执行 `make py-check-p0-dev`，避免把错误基线带入新实现。")

    out_path.write_text("\n".join(lines) + "\n", encoding="utf-8")


def main() -> int:
    parser = argparse.ArgumentParser(description="Audit golden case quality and output issue report.")
    parser.add_argument(
        "--project-root",
        type=Path,
        default=Path(__file__).resolve().parent.parent,
        help="Backend project root (default: scripts/..).",
    )
    parser.add_argument(
        "--cases-dir",
        action="append",
        default=[],
        help="Cases directory; repeatable. Default: docs/rewrite/golden/cases/p0 and p0_success.",
    )
    parser.add_argument(
        "--out-tsv",
        type=Path,
        default=None,
        help="Output issues TSV (default: docs/rewrite/reports/golden-quality-issues.tsv).",
    )
    parser.add_argument(
        "--out-md",
        type=Path,
        default=None,
        help="Output Markdown report (default: docs/rewrite/reports/golden-quality.md).",
    )
    args = parser.parse_args()

    root = args.project_root.resolve()
    case_dirs = [Path(p).resolve() for p in args.cases_dir] if args.cases_dir else [
        root / "docs" / "rewrite" / "golden" / "cases" / "p0",
        root / "docs" / "rewrite" / "golden" / "cases" / "p0_success",
    ]
    out_tsv = (
        args.out_tsv.resolve()
        if args.out_tsv
        else root / "docs" / "rewrite" / "reports" / "golden-quality-issues.tsv"
    )
    out_md = (
        args.out_md.resolve()
        if args.out_md
        else root / "docs" / "rewrite" / "reports" / "golden-quality.md"
    )

    files: list[Path] = []
    for d in case_dirs:
        files.extend(iter_case_files(d))

    issues: list[Issue] = []
    for file in files:
        issues.extend(detect_issues(file))

    issues.sort(key=lambda x: (x.issue_type, x.method, x.path, x.case_id))

    write_issues_tsv(issues, out_tsv)
    write_report_md(len(files), issues, out_md)

    print(f"Scanned cases: {len(files)}")
    print(f"Issues found: {len(issues)}")
    print(f"Wrote issues: {out_tsv}")
    print(f"Wrote report: {out_md}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
