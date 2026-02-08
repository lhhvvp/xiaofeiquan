#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import json
import re
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


def slugify(value: str) -> str:
    s = (value or "").strip()
    if s.startswith("/"):
        s = s[1:]
    s = s.replace("/", "-").replace(".", "-")
    s = re.sub(r"[^A-Za-z0-9_-]+", "-", s)
    s = re.sub(r"-{2,}", "-", s).strip("-")
    return s or "root"


@dataclass(frozen=True)
class ExistingCase:
    method: str | None
    path: str | None


def load_existing_cases(cases_root: Path) -> set[tuple[str, str]]:
    existing: set[tuple[str, str]] = set()
    for p in sorted(cases_root.rglob("*.json")):
        try:
            raw = json.loads(p.read_text(encoding="utf-8"))
        except json.JSONDecodeError:
            continue
        req = raw.get("request") or {}
        method = normalize_method(req.get("method")) or ""
        path = normalize_path(req.get("path")) or ""
        if method and path:
            existing.add((method, path))
    return existing


def headers_for_auth(auth: str, auth_required: str) -> dict[str, str]:
    if (auth_required or "").strip().lower() != "yes":
        return {}

    a = (auth or "").strip()
    if a.startswith("api-token"):
        return {"Token": "${API_TOKEN}", "Userid": "${API_USERID}"}
    if a.startswith("window-token"):
        return {"Token": "${WINDOW_TOKEN}", "Uuid": "${WINDOW_UUID}"}
    if a.startswith("selfservice-token"):
        return {"Token": "${SELFSERVICE_TOKEN}", "No": "${SELFSERVICE_NO}"}
    return {}


def is_captcha_endpoint(controller: str, action: str, path: str) -> bool:
    if (action or "").strip().lower() == "captcha":
        return True
    if "/captcha" in (path or "").lower():
        return True
    if (controller or "").strip().lower().endswith("/captcha"):
        return True
    return False


def build_case(
    *,
    case_id: str,
    method: str,
    path: str,
    app: str,
    controller: str,
    action: str,
    auth: str,
    auth_required: str,
    notes: str,
    compare_mode: str,
    request_body: dict | None,
) -> dict:
    req: dict = {
        "method": method,
        "path": path,
        "query": {},
        "headers": headers_for_auth(auth, auth_required),
    }
    if request_body:
        req["body"] = request_body
    else:
        if method == "GET":
            req["query"] = {}
        else:
            req["body"] = {"type": "form", "form": {}}

    return {
        "id": case_id,
        "meta": {
            "tier": "p0",
            "source": "p0-interfaces",
            "app": app,
            "controller": controller,
            "action": action,
            "notes": notes,
        },
        "request": req,
        "response": {
            "status": 200,
            "headers": {"Content-Type": "application/json"},
            "body": {"type": "json", "json": {}},
        },
        "compare": {
            "mode": compare_mode,
            "ignore_json_keys": ["time"],
            "ignore_json_pointers": [],
        },
    }


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Generate golden stub cases for missing P0 interfaces."
    )
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="Backend repo root containing docs/rewrite/ (default: scripts/..).",
    )
    parser.add_argument(
        "--out-dir",
        type=Path,
        default=None,
        help="Output directory (default: docs/rewrite/golden/cases/p0/stubs).",
    )
    parser.add_argument(
        "--include-html",
        action="store_true",
        help="Also generate stubs for path_html variant when present.",
    )
    parser.add_argument(
        "--overwrite",
        action="store_true",
        help="Overwrite existing stub files (does not touch non-stub cases).",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root
    interfaces_path = repo_root / "docs" / "rewrite" / "p0-interfaces.tsv"
    if not interfaces_path.exists():
        raise SystemExit(f"missing file: {interfaces_path}")

    cases_root = repo_root / "docs" / "rewrite" / "golden" / "cases" / "p0"
    if not cases_root.exists():
        raise SystemExit(f"missing dir: {cases_root}")

    out_dir = args.out_dir or (cases_root / "stubs")
    out_dir.mkdir(parents=True, exist_ok=True)

    rows = read_tsv(interfaces_path)
    existing = load_existing_cases(cases_root)

    created = 0
    skipped = 0

    def write_case_file(app: str, payload: dict) -> None:
        nonlocal created, skipped
        case_id = payload["id"]
        method = payload["request"]["method"].lower()
        path = payload["request"]["path"]
        slug = slugify(path)
        out_path = out_dir / app / f"{case_id}.json"
        if out_path.exists() and not args.overwrite:
            skipped += 1
            return
        out_path.parent.mkdir(parents=True, exist_ok=True)
        out_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        created += 1

    def maybe_emit(
        *,
        method: str,
        path: str,
        app: str,
        controller: str,
        action: str,
        auth: str,
        auth_required: str,
        variant: str,
    ) -> None:
        key = (method, path)
        if key in existing and not args.overwrite:
            return

        notes = "stub：先录制 legacy baseline，再用它验收 Python 实现。"
        compare_mode = "json"
        request_body: dict | None = None

        if is_captcha_endpoint(controller, action, path):
            compare_mode = "raw"
            notes = "stub：captcha 通常为图片/二进制，建议用 raw 对比（录制 baseline 后会存 raw_base64/sha256）。"
            if method != "GET":
                # captcha 绝大多数为 GET
                method = "GET"

        if app == "xc":
            # 不依赖密钥：构造可稳定触发 body 解密错误的报文（期望 resultCode=0001）
            compare_mode = "json"
            request_body = {
                "type": "json",
                "json": {
                    "header": {
                        "accountId": "INVALID",
                        "serviceName": action or "",
                        "requestTime": "19700101000000",
                        "version": "1.0",
                        "sign": "invalid",
                    },
                    "body": "invalid",
                },
            }
            notes = "stub：xc 验签/AES 失败用例（body 解密错误），用于锁定错误码/返回结构。"

        if app == "meituan":
            # 不依赖密钥：构造必然 BA 验证失败的 Header（期望 code=300）
            compare_mode = "json"
            request_body = {"type": "json", "json": {}}
            notes = "stub：meituan BA 失败用例（固定返回 code=300），用于锁定错误响应。"

        payload = build_case(
            case_id=f"p0-stub-{app}-{method.lower()}-{slugify(path)}-001",
            method=method,
            path=path,
            app=app,
            controller=controller,
            action=action,
            auth=auth,
            auth_required=auth_required,
            notes=f"{notes} variant={variant}",
            compare_mode=compare_mode,
            request_body=request_body,
        )

        if app == "meituan":
            payload["request"]["headers"] = {
                "Content-Type": "application/json; charset=utf-8",
                "Date": "Thu, 01 Jan 1970 00:00:00 GMT",
                "PartnerId": "0",
                "Authorization": "MWS invalid:invalid",
            }
        write_case_file(app, payload)

    for r in rows:
        app = (r.get("app") or "").strip() or "unknown"
        controller = (r.get("controller") or "").strip()
        action = (r.get("action") or "").strip()
        auth = (r.get("auth") or "").strip()
        auth_required = (r.get("auth_required") or "").strip()

        m = normalize_method(r.get("method")) or "POST"
        p = normalize_path(r.get("path"))
        p_html = normalize_path(r.get("path_html"))

        if p:
            maybe_emit(
                method=m,
                path=p,
                app=app,
                controller=controller,
                action=action,
                auth=auth,
                auth_required=auth_required,
                variant="path",
            )
        if args.include_html and p_html:
            maybe_emit(
                method=m,
                path=p_html,
                app=app,
                controller=controller,
                action=action,
                auth=auth,
                auth_required=auth_required,
                variant="path_html",
            )

    print(f"interfaces: {len(rows)} ({interfaces_path})")
    print(f"cases root: {cases_root}")
    print(f"output dir: {out_dir}")
    print(f"created: {created}")
    print(f"skipped: {skipped}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

