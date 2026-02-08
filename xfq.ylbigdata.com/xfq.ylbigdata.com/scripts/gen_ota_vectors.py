#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path
from typing import Any


def _add_import_roots() -> None:
    backend_root = Path(__file__).resolve().parents[1]
    # For `import rewrite_py.app.*` (rewrite_py is an implicit namespace package).
    sys.path.insert(0, str(backend_root))


def _print_json(obj: Any) -> None:
    sys.stdout.write(json.dumps(obj, ensure_ascii=False, indent=2) + "\n")


def main() -> int:
    _add_import_roots()
    from rewrite_py.app.ota_crypto import (  # noqa: PLC0415
        meituan_build_authorization,
        xc_encrypt,
        xc_sign_target,
    )

    parser = argparse.ArgumentParser(
        description="Generate deterministic OTA request vectors for golden cases (Meituan BA-sign / XC AES+md5-sign)."
    )
    sub = parser.add_subparsers(dest="cmd", required=True)

    p_mt = sub.add_parser("meituan", help="Meituan BA-sign Authorization header")
    p_mt.add_argument("--method", default="POST")
    p_mt.add_argument("--uri", default="/meituan/index/system.html")
    p_mt.add_argument("--date", default="Thu, 01 Jan 1970 00:00:00 GMT")
    p_mt.add_argument("--client-id", default="703")
    p_mt.add_argument("--client-secret", default="pw4user2test@RA")

    p_xc = sub.add_parser("xc", help="XC AES encrypt + md5 sign request body")
    p_xc.add_argument("--account-id", default="5931ac6d70f46ed2")
    p_xc.add_argument("--sign-key", default="be8c6b51e5817111a4d7d8757093d4ec")
    p_xc.add_argument("--aes-key", default="bc3cd7f181409eda")
    p_xc.add_argument("--aes-iv", default="ce0e70accadb339f")
    p_xc.add_argument("--service-name", default="system")
    p_xc.add_argument("--request-time", default="19700101000000")
    p_xc.add_argument("--version", default="1.0")
    p_xc.add_argument("--body-json", default="{}")

    args = parser.parse_args()

    if args.cmd == "meituan":
        auth = meituan_build_authorization(
            method=str(args.method).upper(),
            uri=str(args.uri),
            date=str(args.date),
            client_id=str(args.client_id),
            client_secret=str(args.client_secret),
        )
        _print_json({"Authorization": auth})
        return 0

    if args.cmd == "xc":
        try:
            body_obj = json.loads(str(args.body_json))
        except Exception as e:
            raise SystemExit(f"--body-json must be valid JSON: {e}")

        plaintext = json.dumps(body_obj, ensure_ascii=False, separators=(",", ":"), sort_keys=True)
        body_str = xc_encrypt(plaintext, aes_key=str(args.aes_key), aes_iv=str(args.aes_iv))
        sign = xc_sign_target(
            account_id=str(args.account_id),
            service_name=str(args.service_name),
            request_time=str(args.request_time),
            body_str=body_str,
            version=str(args.version),
            sign_key=str(args.sign_key),
        )
        payload = {
            "header": {
                "accountId": str(args.account_id),
                "serviceName": str(args.service_name),
                "requestTime": str(args.request_time),
                "version": str(args.version),
                "sign": sign,
            },
            "body": body_str,
        }
        _print_json(payload)
        return 0

    raise SystemExit(f"unknown cmd: {args.cmd}")


if __name__ == "__main__":
    raise SystemExit(main())

