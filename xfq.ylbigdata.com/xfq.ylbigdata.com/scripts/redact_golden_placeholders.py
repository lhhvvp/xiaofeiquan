#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
from pathlib import Path


PLACEHOLDER_RE = re.compile(r"^\\$\\{[A-Za-z_][A-Za-z0-9_]*\\}$")


def is_placeholder(value: object) -> bool:
    if not isinstance(value, str):
        return False
    return bool(PLACEHOLDER_RE.match(value.strip()))


def redact_headers(headers: dict[str, object]) -> bool:
    changed = False

    # api（小程序）
    if "Userid" in headers:
        if "Token" in headers and not is_placeholder(headers.get("Token")):
            headers["Token"] = "${API_TOKEN}"
            changed = True
        if "Userid" in headers and not is_placeholder(headers.get("Userid")):
            headers["Userid"] = "${API_USERID}"
            changed = True
        return changed

    # window（窗口/售票员）
    if "Uuid" in headers:
        if "Token" in headers and not is_placeholder(headers.get("Token")):
            headers["Token"] = "${WINDOW_TOKEN}"
            changed = True
        if "Uuid" in headers and not is_placeholder(headers.get("Uuid")):
            headers["Uuid"] = "${WINDOW_UUID}"
            changed = True
        return changed

    # selfservice（自助机/商户）
    if "No" in headers:
        if "Token" in headers and not is_placeholder(headers.get("Token")):
            headers["Token"] = "${SELFSERVICE_TOKEN}"
            changed = True
        if "No" in headers and not is_placeholder(headers.get("No")):
            headers["No"] = "${SELFSERVICE_NO}"
            changed = True
        return changed

    return changed


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Redact recorded golden case headers back into ${ENV} placeholders."
    )
    parser.add_argument(
        "--cases-dir",
        type=Path,
        default=Path(__file__).resolve().parents[1] / "docs" / "rewrite" / "golden" / "cases",
        help="Cases root (default: docs/rewrite/golden/cases).",
    )
    args = parser.parse_args()

    cases_dir: Path = args.cases_dir
    if not cases_dir.exists():
        raise SystemExit(f"missing dir: {cases_dir}")

    touched = 0
    for p in sorted(cases_dir.rglob("*.json")):
        raw = json.loads(p.read_text(encoding="utf-8"))
        req = raw.get("request") if isinstance(raw.get("request"), dict) else None
        if not isinstance(req, dict):
            continue
        headers = req.get("headers") if isinstance(req.get("headers"), dict) else None
        if not isinstance(headers, dict):
            continue

        if redact_headers(headers):
            p.write_text(json.dumps(raw, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
            touched += 1

    print(f"OK: redacted {touched} case files under {cases_dir}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

