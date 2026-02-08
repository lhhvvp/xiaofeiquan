#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import re
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable


@dataclass(frozen=True)
class InterfaceRow:
    method: str
    path: str
    path_html: str
    app: str
    controller: str
    action: str
    auth: str
    auth_required: str
    desc: str
    file: str
    line: int


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f, delimiter="\t"))


def to_interface_rows(rows: list[dict[str, str]]) -> list[InterfaceRow]:
    out: list[InterfaceRow] = []
    for r in rows:
        out.append(
            InterfaceRow(
                method=(r.get("method") or "UNKNOWN").upper(),
                path=r.get("path") or "",
                path_html=r.get("path_html") or "",
                app=r.get("app") or "",
                controller=r.get("controller") or "",
                action=r.get("action") or "",
                auth=r.get("auth") or "",
                auth_required=r.get("auth_required") or "",
                desc=r.get("desc") or "",
                file=r.get("file") or "",
                line=int(r.get("line") or 0),
            )
        )
    return out


def normalize_controller(controller: str) -> str:
    return controller.replace("\\", "/").strip("/").lower()


def normalize_action(action: str) -> str:
    return action.strip().lower()


def contract_path_for(tier: str, row: InterfaceRow) -> str:
    return f"docs/rewrite/contracts/{tier}/{row.app.lower()}/{normalize_controller(row.controller)}/{normalize_action(row.action)}.md"


def tag_for(row: InterfaceRow) -> str:
    app = row.app.lower()
    path = row.path.lower()
    controller = row.controller.lower()
    action = row.action.lower()

    if app == "xc":
        return "OTA_XC"
    if app == "meituan":
        return "OTA_MT"

    if controller == "notify" or "/notify/" in path:
        return "PAY_NOTIFY"
    if controller == "pay" or "/pay/" in path:
        return "PAY_API"
    if "refund" in path or "refund" in action:
        return "REFUND"
    if "upload" in path or controller in {"upload", "webupload"}:
        return "UPLOAD"
    if any(k in path for k in ["miniwxlogin", "miniwxregister", "getuserphonenumber"]):
        return "AUTH_LOGIN"
    if any(k in action for k in ["smsverification", "auth_identity", "auth_info"]):
        return "SMS_ID"
    if controller == "system":
        return "SYSTEM_TASK"
    return "BUSINESS"


def group_for(tag: str, miniapp_used: bool) -> str:
    if tag in {"PAY_NOTIFY", "OTA_XC", "OTA_MT"}:
        return "A0-external-callbacks"
    if tag in {"PAY_API", "REFUND"}:
        return "A1-money"
    if tag in {"UPLOAD", "SMS_ID", "AUTH_LOGIN"}:
        return "A2-identity-upload"
    if miniapp_used:
        return "B1-miniapp"
    if tag == "SYSTEM_TASK":
        return "B2-jobs"
    return "C-remaining"


def write_backlog_tsv(rows: list[dict[str, str]], out_path: Path) -> None:
    out_path.parent.mkdir(parents=True, exist_ok=True)
    with out_path.open("w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f, delimiter="\t")
        writer.writerow(
            [
                "group",
                "tag",
                "miniapp_used",
                "method",
                "path",
                "path_html",
                "app",
                "controller",
                "action",
                "auth",
                "auth_required",
                "contract",
                "desc",
                "file",
                "line",
            ]
        )
        for r in rows:
            writer.writerow(
                [
                    r["group"],
                    r["tag"],
                    "yes" if r["miniapp_used"] else "no",
                    r["method"],
                    r["path"],
                    r["path_html"],
                    r["app"],
                    r["controller"],
                    r["action"],
                    r["auth"],
                    r["auth_required"],
                    r["contract"],
                    r["desc"],
                    r["file"],
                    r["line"],
                ]
            )


def write_summary_md(rows: list[dict[str, str]], out_path: Path) -> None:
    out_path.parent.mkdir(parents=True, exist_ok=True)
    by_group: dict[str, int] = {}
    by_tag: dict[str, int] = {}
    mini_used = 0
    for r in rows:
        by_group[r["group"]] = by_group.get(r["group"], 0) + 1
        by_tag[r["tag"]] = by_tag.get(r["tag"], 0) + 1
        mini_used += 1 if r["miniapp_used"] else 0

    lines: list[str] = []
    lines.append("# P0 重写 Backlog（自动生成）")
    lines.append("")
    lines.append(f"- 接口总数：{len(rows)}")
    lines.append(f"- 小程序（mp-native）实际调用覆盖：{mini_used}")
    lines.append("")
    lines.append("## 分组统计")
    lines.append("")
    for k in sorted(by_group):
        lines.append(f"- `{k}`：{by_group[k]}")
    lines.append("")
    lines.append("## 标签统计")
    lines.append("")
    for k in sorted(by_tag):
        lines.append(f"- `{k}`：{by_tag[k]}")
    lines.append("")
    lines.append("## 建议实现顺序（按分组）")
    lines.append("")
    lines.append("- A0 外部回调/对接（支付通知、OTA）：先做，便于尽早联调第三方")
    lines.append("- A1 资金链路（支付/退款）：先做，逻辑复杂且风险高")
    lines.append("- A2 身份/上传（短信、实名认证、文件上传）：先做，牵涉外部依赖与安全")
    lines.append("- B1 小程序调用接口：按业务闭环补齐并用契约测试锁定")
    lines.append("")
    lines.append("## Top 30（按当前排序）")
    lines.append("")
    for r in rows[:30]:
        lines.append(f"- `{r['method']}` `{r['path']}` ({r['tag']}, miniapp={ 'yes' if r['miniapp_used'] else 'no' }) -> `{r['contract']}`")

    out_path.write_text("\n".join(lines) + "\n", encoding="utf-8")


def main() -> int:
    parser = argparse.ArgumentParser(description="Generate rewrite backlog for P0 interfaces.")
    parser.add_argument(
        "--project-root",
        type=Path,
        default=Path(__file__).resolve().parent.parent,
        help="Backend project root (default: scripts/..).",
    )
    parser.add_argument(
        "--p0",
        type=Path,
        default=None,
        help="P0 interfaces TSV (default: docs/rewrite/p0-interfaces.tsv).",
    )
    parser.add_argument(
        "--miniapp",
        type=Path,
        default=None,
        help="Miniapp usage TSV (default: docs/rewrite/miniapp-api-usage.tsv).",
    )
    parser.add_argument(
        "--out",
        type=Path,
        default=None,
        help="Output backlog TSV (default: docs/rewrite/p0-backlog.tsv).",
    )
    parser.add_argument(
        "--summary",
        type=Path,
        default=None,
        help="Output backlog summary markdown (default: docs/rewrite/p0-backlog.md).",
    )
    args = parser.parse_args()

    root: Path = args.project_root.resolve()
    p0_path = args.p0.resolve() if args.p0 else (root / "docs" / "rewrite" / "p0-interfaces.tsv")
    mini_path = args.miniapp.resolve() if args.miniapp else (root / "docs" / "rewrite" / "miniapp-api-usage.tsv")
    out_path = args.out.resolve() if args.out else (root / "docs" / "rewrite" / "p0-backlog.tsv")
    summary_path = args.summary.resolve() if args.summary else (root / "docs" / "rewrite" / "p0-backlog.md")

    p0_rows = to_interface_rows(read_tsv(p0_path))
    mini_rows = read_tsv(mini_path) if mini_path.exists() else []
    mini_used_paths = set((r.get("method", "").upper(), r.get("full_path", "")) for r in mini_rows)
    mini_used_any_method = set(r.get("full_path", "") for r in mini_rows)

    backlog: list[dict[str, str]] = []
    for r in p0_rows:
        miniapp_used = (r.method.upper(), r.path) in mini_used_paths or r.path in mini_used_any_method
        tag = tag_for(r)
        group = group_for(tag, miniapp_used)
        backlog.append(
            {
                "group": group,
                "tag": tag,
                "miniapp_used": miniapp_used,
                "method": r.method.upper(),
                "path": r.path,
                "path_html": r.path_html,
                "app": r.app,
                "controller": r.controller,
                "action": r.action,
                "auth": r.auth,
                "auth_required": r.auth_required,
                "contract": contract_path_for("p0", r),
                "desc": r.desc,
                "file": r.file,
                "line": str(r.line),
            }
        )

    order = {
        "A0-external-callbacks": 0,
        "A1-money": 1,
        "A2-identity-upload": 2,
        "B1-miniapp": 3,
        "B2-jobs": 4,
        "C-remaining": 9,
    }
    backlog.sort(key=lambda r: (order.get(r["group"], 99), r["tag"], r["path"]))

    write_backlog_tsv(backlog, out_path)
    write_summary_md(backlog, summary_path)
    print(f"Wrote backlog TSV: {out_path}")
    print(f"Wrote backlog summary: {summary_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

