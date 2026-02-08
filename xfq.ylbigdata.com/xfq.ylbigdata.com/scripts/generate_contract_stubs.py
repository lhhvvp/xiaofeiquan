#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import re
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable, Optional


@dataclass(frozen=True)
class InventoryRow:
    app: str
    controller: str
    action: str
    route_guess: str
    route_guess_html: str
    doc_method: str
    doc_path: str
    doc_desc: str
    auth: str
    auth_required: str
    file: str
    line: int


def read_inventory(tsv_path: Path) -> list[InventoryRow]:
    rows: list[InventoryRow] = []
    with tsv_path.open("r", encoding="utf-8", newline="") as f:
        reader = csv.DictReader(f, delimiter="\t")
        for r in reader:
            rows.append(
                InventoryRow(
                    app=r["app"],
                    controller=r["controller"],
                    action=r["action"],
                    route_guess=r["route_guess"],
                    route_guess_html=r["route_guess_html"],
                    doc_method=r.get("doc_method", "") or "",
                    doc_path=r.get("doc_path", "") or "",
                    doc_desc=r.get("doc_desc", "") or "",
                    auth=r.get("auth", "") or "",
                    auth_required=r.get("auth_required", "") or "",
                    file=r.get("file", "") or "",
                    line=int(r.get("line", "0") or 0),
                )
            )
    return rows


def normalize_controller(controller: str) -> str:
    # keep nested paths, but make it URL-ish and stable on disk
    return controller.replace("\\", "/").strip("/").lower()


def normalize_action(action: str) -> str:
    return action.strip().lower()


def _dedupe_keep_first(rows: Iterable[InventoryRow]) -> list[InventoryRow]:
    seen: set[tuple[str, str, str]] = set()
    out: list[InventoryRow] = []
    for r in rows:
        key = (r.app, r.controller, r.action)
        if key in seen:
            continue
        seen.add(key)
        out.append(r)
    return out


def guess_full_path(row: InventoryRow) -> tuple[str, str]:
    """
    Returns: (canonical_path, canonical_path_html)
    Prefer doc_path only when it looks consistent with controller/action; otherwise fallback to default route.
    """
    app = row.app.strip("/")

    if row.doc_path and row.doc_path.strip().startswith("/"):
        doc_path = row.doc_path.strip()
        doc_action = doc_path.rstrip("/").split("/")[-1]
        controller_norm = normalize_controller(row.controller).split("/")[-1]
        action_norm = row.action

        # If doc_path is like /user/index, require it to match current controller/action (best-effort).
        # This avoids misleading/placeholder docs such as /ticket/refund on method single_refund.
        ok = True
        parts = [p for p in doc_path.strip("/").split("/") if p]
        if len(parts) >= 2:
            doc_controller = parts[-2].lower()
            ok = (doc_controller == controller_norm.lower()) and (
                doc_action == action_norm or doc_action.lower() == action_norm.lower()
            )
        else:
            ok = False

        if ok:
            if doc_path.lower().startswith(f"/{app.lower()}/"):
                base = doc_path
            else:
                base = f"/{app}{doc_path}"
            return base, f"{base}.html"

    # fallback to route_guess-like convention: lower-case controller, keep action as-is
    controller = normalize_controller(row.controller)
    base = f"/{app}/{controller}/{row.action}"
    return base, f"{base}.html"


def app_default_headers(app: str) -> list[str]:
    app = app.lower()
    if app == "api":
        return ["Token", "Userid", "Pip(可选，仅大屏)"]
    if app == "window":
        return ["Token", "Uuid"]
    if app == "selfservice":
        return ["Token", "No"]
    if app in {"admin", "seller", "travel", "travelv2"}:
        return ["Cookie: PHPSESSID=..."]
    if app == "xc":
        return ["Content-Type: application/json"]
    if app == "meituan":
        return ["Authorization", "Date", "PartnerId", "Content-Type: application/json"]
    return []


def contract_stub(row: InventoryRow) -> str:
    path, path_html = guess_full_path(row)
    method = (row.doc_method or "").upper().strip() or "UNKNOWN"
    desc = row.doc_desc.strip() if row.doc_desc else ""
    if (row.auth_required or "").strip().lower() == "no":
        headers_md = "- （无需鉴权 Header；按第三方/业务需要补充）"
    else:
        headers = app_default_headers(row.app)
        headers_md = "\n".join([f"- `{h}`" for h in headers]) if headers else "- （待补充）"

    doc_path_full = ""
    if row.doc_path:
        doc_path_full, _ = guess_full_path(
            InventoryRow(
                app=row.app,
                controller=row.controller,
                action=row.action,
                route_guess=row.route_guess,
                route_guess_html=row.route_guess_html,
                doc_method=row.doc_method,
                doc_path=row.doc_path,
                doc_desc=row.doc_desc,
                auth=row.auth,
                auth_required=row.auth_required,
                file=row.file,
                line=row.line,
            )
        )

    return "\n".join(
        [
            f"# [{row.app}] {method} {path}",
            "",
            "## 1. 基本信息",
            "",
            f"- 路径（兼容要求）：`{path}`（建议同时兼容：`{path_html}`）",
            f"- 源码定位：`{row.file}:{row.line}`",
            f"- 控制器/方法：`{row.app}/{row.controller}.{row.action}()`",
            f"- 描述：{desc or '（待补充）'}",
            f"- 鉴权（基线）：`{row.auth or 'unknown'}`；是否要求鉴权：`{row.auth_required or 'unknown'}`",
            f"- 文档注释（如有）：`{row.doc_method or ''} {row.doc_path or ''}`".rstrip(),
            "",
            "## 2. 鉴权与 Header",
            "",
            headers_md,
            "",
            "## 3. 请求",
            "",
            "- Content-Type：",
            "- Query 参数：",
            "- Body：",
            "- 文件上传（如有）：",
            "",
            "## 4. 响应",
            "",
            "- HTTP 状态码：",
            "- 返回结构：",
            "- 错误码：",
            "",
            "## 5. 副作用与幂等",
            "",
            "- 写库：",
            "- 外部调用：",
            "- 幂等策略：",
            "",
            "## 6. 测试用例（契约验收）",
            "",
            "- 正常用例：",
            "- 异常用例：",
            "- 幂等/重放：",
            "",
        ]
    )


def write_contracts(rows: list[InventoryRow], out_dir: Path, *, overwrite: bool) -> None:
    for r in rows:
        controller_dir = out_dir / r.app.lower() / normalize_controller(r.controller)
        controller_dir.mkdir(parents=True, exist_ok=True)
        path = controller_dir / f"{normalize_action(r.action)}.md"
        if path.exists() and not overwrite:
            continue
        path.write_text(contract_stub(r), encoding="utf-8")


def write_list(rows: list[InventoryRow], out_path: Path) -> None:
    out_path.parent.mkdir(parents=True, exist_ok=True)
    with out_path.open("w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f, delimiter="\t")
        writer.writerow(["method", "path", "path_html", "app", "controller", "action", "auth", "auth_required", "desc", "file", "line"])
        for r in rows:
            path, path_html = guess_full_path(r)
            writer.writerow(
                [
                    (r.doc_method or "").upper() or "UNKNOWN",
                    path,
                    path_html,
                    r.app,
                    r.controller,
                    r.action,
                    r.auth,
                    r.auth_required,
                    r.doc_desc,
                    r.file,
                    r.line,
                ]
            )


def filter_tier(rows: list[InventoryRow], tier: str) -> list[InventoryRow]:
    tier = tier.lower().strip()
    if tier == "p0":
        apps = {"api", "window", "selfservice", "xc", "meituan"}
        return [r for r in rows if r.app.lower() in apps]
    if tier == "p1":
        apps = {"admin", "seller", "travel", "travelv2"}
        return [r for r in rows if r.app.lower() in apps]
    if tier == "p2":
        apps = {"index", "mobile", "handheld"}
        return [r for r in rows if r.app.lower() in apps]
    if tier == "all":
        return list(rows)
    raise ValueError(f"unknown tier: {tier}")


def main() -> int:
    parser = argparse.ArgumentParser(description="Generate contract stub markdowns from api-inventory.tsv.")
    parser.add_argument(
        "--project-root",
        type=Path,
        default=Path(__file__).resolve().parent.parent,
        help="ThinkPHP project root (default: scripts/..)",
    )
    parser.add_argument(
        "--tier",
        choices=["p0", "p1", "p2", "all"],
        default="p0",
        help="Which tier of interfaces to generate stubs for.",
    )
    parser.add_argument(
        "--inventory",
        type=Path,
        default=None,
        help="Inventory TSV path (default: docs/rewrite/api-inventory.tsv under project root)",
    )
    parser.add_argument(
        "--out-dir",
        type=Path,
        default=None,
        help="Output directory for contracts (default: docs/rewrite/contracts/<tier>)",
    )
    parser.add_argument(
        "--overwrite",
        action="store_true",
        help="Overwrite existing contract markdown files (default: keep existing).",
    )
    args = parser.parse_args()

    project_root: Path = args.project_root.resolve()
    inventory_path: Path = args.inventory.resolve() if args.inventory else (project_root / "docs" / "rewrite" / "api-inventory.tsv")
    out_dir: Path = args.out_dir.resolve() if args.out_dir else (project_root / "docs" / "rewrite" / "contracts" / args.tier)

    rows = read_inventory(inventory_path)
    tier_rows = _dedupe_keep_first(filter_tier(rows, args.tier))
    tier_rows.sort(key=lambda r: (r.app.lower(), normalize_controller(r.controller), normalize_action(r.action)))

    write_contracts(tier_rows, out_dir, overwrite=args.overwrite)
    write_list(tier_rows, project_root / "docs" / "rewrite" / f"{args.tier}-interfaces.tsv")
    print(f"Generated {len(tier_rows)} contract stubs into {out_dir}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
