from __future__ import annotations

import base64
import hashlib
import json
import math
import os
import random
import re
import time
import urllib.parse
from datetime import datetime, timedelta, timezone
from decimal import Decimal
from pathlib import Path
from typing import Any

from cryptography.hazmat.primitives import padding
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse, Response

from .auth import auth_error_response, enforce_auth
from .contracts import InterfaceSpec, get_p0_interfaces_path, load_p0_interfaces
from .db import execute, fetch_all, fetch_one
from .golden_stub import RecordedResponse, get_cases_dirs, get_stub_enabled, load_golden_cases
from .http_params import get_params
from .ota_auth import (
    MeituanAuthError,
    XcAuthError,
    enforce_meituan_ba_sign,
    enforce_xc_aes_md5_sign,
    meituan_auth_error_response,
    xc_auth_error_response,
)
from .responses import api_error, api_success
from .traffic_capture import append_jsonl, build_capture_event, capture_enabled, capture_log_path


class StripHtmlSuffixMiddleware:
    def __init__(self, app: Any) -> None:
        self.app = app

    async def __call__(self, scope: dict[str, Any], receive: Any, send: Any) -> None:
        if scope.get("type") == "http":
            path = str(scope.get("path") or "")
            if "xfq_original_path" not in scope:
                scope = dict(scope)
                scope["xfq_original_path"] = path
            if path.endswith(".html"):
                scope = dict(scope)
                scope["path"] = path[: -len(".html")]
        await self.app(scope, receive, send)


fastapi_app = FastAPI(docs_url=None, redoc_url=None, openapi_url=None)

GOLDEN: dict[tuple[str, str], RecordedResponse] = {}
GOLDEN_BY_ID: dict[str, RecordedResponse] = {}
P0_INTERFACES: dict[str, InterfaceSpec] = {}

_TZ_SHANGHAI = timezone(timedelta(hours=8))

_LEGACY_UPLOAD_ERROR_ROUTES: set[tuple[str, str]] = {
    ("POST", "/api/upload/index"),
    ("POST", "/api/webupload/index"),
    ("POST", "/selfservice/upload/index"),
    ("POST", "/selfservice/webupload/index"),
    ("POST", "/meituan/webupload/index"),
}
_LEGACY_UPLOAD_ERROR_CASE_ID = "p0-stub-api-post-api-upload-index-001"
_LEGACY_TEST_SYNCDB2_CASE_ID = "p0-stub-api-post-api-test-syncdb2-001"
_XC_ORDER_INFO_EMPTY_BASE64 = (
    "YXJyYXkoMikgewogIFsiYm9vbCJdPT4KICBib29sKGZhbHNlKQogIFsibXNnIl09PgogIHN0cmlu"
    "ZygyNykgIuiuouWNleS/oeaBr+S4jeiDveS4uuepuu+8gSIKfQo="
)
_XC_ORDER_INFO_EMPTY_BYTES = base64.b64decode(_XC_ORDER_INFO_EMPTY_BASE64.encode("ascii"))

_LEGACY_500_HTML_BASE64 = (
    "PCFET0NUWVBFIGh0bWw+CjxodG1sIGxhbmc9ImVuIj4KICAgIDxoZWFkPgogICAgICAgIDxtZXRhIGNoYXJzZXQ9IlVURi04"
    "Ij4KICAgICAgICA8bWV0YSBuYW1lPSJ2aWV3cG9ydCIgY29udGVudD0id2lkdGg9ZGV2aWNlLXdpZHRoLCBpbml0aWFsLXNj"
    "YWxlPTEuMCI+CiAgICAgICAgPG1ldGEgaHR0cC1lcXVpdj0iWC1VQS1Db21wYXRpYmxlIiBjb250ZW50PSJpZT1lZGdlIj4K"
    "ICAgICAgICA8dGl0bGU+NTAwPC90aXRsZT4KICAgICAgICA8bGluayByZWw9InNob3J0Y3V0IGljb24iIGhyZWY9ImZhdmlj"
    "b24uaWNvIj4KICAgIDwvaGVhZD4KICAgIDxzdHlsZT4KICAgICAgICBib2R5e2ZvbnQtc2l6ZToxMnB4O2JhY2tncm91bmQ6"
    "I2ZmZjtmb250LWZhbWlseToiTGFudGluZ2hlaSBTQyIsIkhlbHZldGljYSBOZXVlIiwiTWljcm9zb2Z0IFlhSGVpIiwiV2Vu"
    "UXVhbllpIE1pY3JvIEhlaSIsIkhlaXRpIFNDIiwiU2Vnb2UgVUkiLEFyaWFsLHNhbnMtc2VyaWZ9CiAgICAgICAgYm9keSxo"
    "MSxoMixoMyxoNCxoNSxwLHVsLGxpe3BhZGRpbmc6MDttYXJnaW46MDtsaXN0LXN0eWxlOm5vbmV9CiAgICAgICAgLnRjeV80"
    "MDR7cGFkZGluZy10b3A6MTgwcHg7cGFkZGluZy1ib3R0b206MTEycHh9CiAgICAgICAgLmNvbnRhaW5lcnt3aWR0aDoxMTcw"
    "cHh9CiAgICAgICAgLmNvbnRhaW5lcntwYWRkaW5nLXJpZ2h0OjE1cHg7cGFkZGluZy1sZWZ0OjE1cHg7bWFyZ2luLXJpZ2h0"
    "OmF1dG87bWFyZ2luLWxlZnQ6YXV0b30KICAgICAgICAudGN5XzQwNCBpbWd7ZGlzcGxheTpibG9jazttYXJnaW46YXV0b30K"
    "ICAgICAgICAudGN5XzQwNCBoMntmb250LXNpemU6MzJweDtjb2xvcjojMzMzMzMzO3RleHQtYWxpZ246Y2VudGVyO2xldHRl"
    "ci1zcGFjaW5nOjVweDtwYWRkaW5nLXRvcDozM3B4O3BhZGRpbmctYm90dG9tOjI1cHh9CiAgICAgICAgLnRjeV80MDQgcHtm"
    "b250LXNpemU6MTRweDtjb2xvcjojNjY2NjY2O2xldHRlci1zcGFjaW5nOjFweDt0ZXh0LWFsaWduOmNlbnRlcjtwYWRkaW5n"
    "LWJvdHRvbTozNXB4fQogICAgICAgIC5idG57ZGlzcGxheTppbmxpbmUtYmxvY2s7cGFkZGluZzo2cHggMTJweDttYXJnaW4t"
    "Ym90dG9tOjA7Zm9udC1zaXplOjE0cHg7Zm9udC13ZWlnaHQ6NDAwO2xpbmUtaGVpZ2h0OjEuNDI4NTcxNDM7dGV4dC1hbGln"
    "bjpjZW50ZXI7d2hpdGUtc3BhY2U6bm93cmFwO3ZlcnRpY2FsLWFsaWduOm1pZGRsZTstbXMtdG91Y2gtYWN0aW9uOm1hbmlw"
    "dWxhdGlvbjt0b3VjaC1hY3Rpb246bWFuaXB1bGF0aW9uO2N1cnNvcjpwb2ludGVyOy13ZWJraXQtdXNlci1zZWxlY3Q6bm9u"
    "ZTstbW96LXVzZXItc2VsZWN0Om5vbmU7LW1zLXVzZXItc2VsZWN0Om5vbmU7dXNlci1zZWxlY3Q6bm9uZTtiYWNrZ3JvdW5k"
    "LWltYWdlOm5vbmU7Ym9yZGVyOjFweCBzb2xpZCB0cmFuc3BhcmVudDtib3JkZXItcmFkaXVzOjRweH0KICAgICAgICAuYnRu"
    "LXByaW1hcnl7Y29sb3I6I2ZmZjtiYWNrZ3JvdW5kLWNvbG9yOiMzMzdhYjc7Ym9yZGVyLWNvbG9yOiMyZTZkYTR9CiAgICAg"
    "ICAgLmJ0bl9ibHVle2Rpc3BsYXk6aW5saW5lLWJsb2NrO2hlaWdodDo1NnB4O2xpbmUtaGVpZ2h0OjU2cHg7dGV4dC1hbGln"
    "bjpjZW50ZXI7Ym9yZGVyLXJhZGl1czozcHg7YmFja2dyb3VuZDojN2NhY2VkO2NvbG9yOiNmZmY7bGV0dGVyLXNwYWNpbmc6"
    "NXB4O2JvcmRlcjowO2ZvbnQtc2l6ZToxOHB4O3BhZGRpbmc6MDt0ZXh0LWRlY29yYXRpb246bm9uZTstd2Via2l0LXRyYW5z"
    "aXRpb24tZHVyYXRpb246MC4zczt0cmFuc2l0aW9uLWR1cmF0aW9uOjAuM3M7LXdlYmtpdC10cmFuc2l0aW9uLXRpbWluZy1m"
    "dW5jdGlvbjplYXNlLW91dDt0cmFuc2l0aW9uLXRpbWluZy1mdW5jdGlvbjplYXNlLW91dDstd2Via2l0LXRyYW5zaXRpb24t"
    "cHJvcGVydHk6YmFja2dyb3VuZDt0cmFuc2l0aW9uLXByb3BlcnR5OmJhY2tncm91bmR9CiAgICAgICAgLnRjeV80MDQgYXtk"
    "aXNwbGF5OmJsb2NrO21hcmdpbjphdXRvO3dpZHRoOjIyMHB4O2hlaWdodDo1NnB4fQogICAgPC9zdHlsZT4KICAgIDxib2R5"
    "PgogICAgICAgIDxkaXYgY2xhc3M9InRjeV80MDQgY29udGFpbmVyIj4KICAgICAgICA8aW1nIHNyYz0iL3N0YXRpYy9jb21t"
    "b24vaW1hZ2VzLzUwMC5wbmciPgogICAgICAgIDxoMj7mirHmrYnvvIzmgqjorr/pl67nmoTpobXpnaLlh7rplJnkuoY8L2gy"
    "PgogICAgICAgIDxwPuivt+iBlOezu+euoeeQhuWRmOW4ruW/meino+WGs348L3A+CiAgICAgICAgPGEgaHJlZj0iLyIgY2xh"
    "c3M9ImJ0biBidG4tcHJpbWFyeSBidG5fYmx1ZSI+6L+U5Zue5Li76aG1PC9hPgogICAgICAgIDwvZGl2PgogICAgPC9ib2R5"
    "Pgo8L2h0bWw+"
)
_LEGACY_500_HTML_BYTES = base64.b64decode(_LEGACY_500_HTML_BASE64.encode("ascii"))

_WINDOW_SHELL_ROUTES: set[tuple[str, str]] = {
    ("GET", "/window/index/captcha"),
    ("POST", "/window/index/system"),
    ("POST", "/window/index/winlogin"),
    ("POST", "/window/ticket/detail"),
    ("POST", "/window/ticket/getTicketPirce"),
    ("POST", "/window/ticket/list"),
    ("POST", "/window/ticket/pay"),
    ("POST", "/window/ticket/queryOrder"),
    ("POST", "/window/ticket/queryTourist"),
    ("POST", "/window/ticket/refund"),
    ("POST", "/window/ticket/single_refund"),
    ("POST", "/window/ticket/stats"),
    ("POST", "/window/ticket/takeTicket"),
    ("POST", "/window/upload/index"),
    ("POST", "/window/webupload/index"),
}

_WINDOW_SHELL_HTML_BASE64 = (
    "PCFkb2N0eXBlIGh0bWw+PGh0bWwgbGFuZz0iIj48aGVhZD48bWV0YSBjaGFyc2V0PSJ1dGYtOCI+"
    "PG1ldGEgaHR0cC1lcXVpdj0iWC1VQS1Db21wYXRpYmxlIiBjb250ZW50PSJJRT1lZGdlIj48bWV0"
    "YSBuYW1lPSJ2aWV3cG9ydCIgY29udGVudD0id2lkdGg9ZGV2aWNlLXdpZHRoLGluaXRpYWwtc2Nh"
    "bGU9MSI+PGxpbmsgcmVsPSJpY29uIiBocmVmPSIvd2luZG93L2Zhdmljb24uaWNvIj48dGl0bGU+"
    "dGlja2V0PC90aXRsZT48c2NyaXB0IGRlZmVyPSJkZWZlciIgc3JjPSIvd2luZG93L2pzL2NodW5r"
    "LXZlbmRvcnMuOWIyNzM4MzguanMiPjwvc2NyaXB0PjxzY3JpcHQgZGVmZXI9ImRlZmVyIiBzcmM9"
    "Ii93aW5kb3cvanMvYXBwLmQ1NGJmOWFiLmpzIj48L3NjcmlwdD48bGluayBocmVmPSIvd2luZG93"
    "L2Nzcy9jaHVuay12ZW5kb3JzLjQ3NzFlM2ZiLmNzcyIgcmVsPSJzdHlsZXNoZWV0Ij48bGluayBo"
    "cmVmPSIvd2luZG93L2Nzcy9hcHAuMzE2YjAyMDkuY3NzIiByZWw9InN0eWxlc2hlZXQiPjwvaGVh"
    "ZD48Ym9keT48bm9zY3JpcHQ+PHN0cm9uZz5XZSdyZSBzb3JyeSBidXQgdGlja2V0IGRvZXNuJ3Qg"
    "d29yayBwcm9wZXJseSB3aXRob3V0IEphdmFTY3JpcHQgZW5hYmxlZC4gUGxlYXNlIGVuYWJsZSBp"
    "dCB0byBjb250aW51ZS48L3N0cm9uZz48L25vc2NyaXB0PjxkaXYgaWQ9ImFwcCI+PC9kaXY+PC9i"
    "b2R5PjxzdHlsZT5ib2R5ewogICAgICAgICAgICBtaW4td2lkdGg6IDE0MDBweDsKICAgICAgICB9"
    "PC9zdHlsZT48L2h0bWw+"
)
_WINDOW_SHELL_HTML_BYTES = base64.b64decode(_WINDOW_SHELL_HTML_BASE64.encode("ascii"))


def _fmt_ts_shanghai(value: Any) -> Any:
    if value is None:
        return None
    try:
        ts = int(value)
    except Exception:
        return value
    if ts <= 0:
        return value
    return datetime.fromtimestamp(ts, tz=_TZ_SHANGHAI).strftime("%Y-%m-%d %H:%M:%S")


def _window_shell_response() -> Response:
    return Response(
        status_code=200,
        content=_WINDOW_SHELL_HTML_BYTES,
        headers={"content-type": "text/html; charset=UTF-8"},
    )


def _legacy_upload_error_response() -> Response:
    spec = GOLDEN_BY_ID.get(_LEGACY_UPLOAD_ERROR_CASE_ID)
    if spec is not None and spec.body_type == "raw_base64":
        return _build_stub_response(spec)
    return Response(
        status_code=500,
        content=_LEGACY_500_HTML_BYTES,
        headers={"content-type": "text/html; charset=utf-8"},
    )


def _xc_order_info_empty_response() -> Response:
    return Response(
        status_code=200,
        content=_XC_ORDER_INFO_EMPTY_BYTES,
        headers={"content-type": "text/html; charset=UTF-8"},
    )


def _maybe_serve_window_shell(request: Request) -> Response | None:
    method = request.method.upper()
    path = str(request.url.path or "")
    if (method, path) not in _WINDOW_SHELL_ROUTES:
        return None
    return _window_shell_response()


def _maybe_serve_legacy_upload_error(request: Request) -> Response | None:
    method = request.method.upper()
    path = str(request.url.path or "")
    if (method, path) not in _LEGACY_UPLOAD_ERROR_ROUTES:
        return None
    content_type = str(request.headers.get("content-type") or "").lower()
    if "multipart/form-data" in content_type:
        return None
    return _legacy_upload_error_response()


def _fmt_money_2(value: Any) -> Any:
    if value is None:
        return None
    if isinstance(value, Decimal):
        return f"{value:.2f}"
    try:
        return f"{Decimal(str(value)):.2f}"
    except Exception:
        return value


def _php_json_number(value: Any) -> Any:
    # PHP json_encode tends to serialize 0.0 as 0, but Python keeps 0.0.
    # Normalize float/Decimal values to match legacy output where possible.
    if value is None:
        return None
    if isinstance(value, Decimal):
        try:
            if value == value.to_integral_value():
                return int(value)
            return float(value)
        except Exception:
            return str(value)
    if isinstance(value, float):
        return int(value) if value.is_integer() else value
    return value


def _normalize_coupon_order_row(row: dict[str, Any]) -> None:
    row["origin_price"] = _fmt_money_2(row.get("origin_price"))
    row["amount_price"] = _fmt_money_2(row.get("amount_price"))

    if "payment_datetime" in row and row.get("payment_datetime") is not None:
        row["payment_datetime"] = str(row.get("payment_datetime"))

    for key in ("is_refund", "issue_coupon_user_id"):
        v = row.get(key)
        if v is None or v == "" or v == 0 or v == "0":
            row[key] = None

    detail = row.get("detail")
    if isinstance(detail, dict):
        for key in (
            "coupon_price",
            "coupon_sale_price",
            "total_market",
            "price_selling",
            "total_selling",
            "discount_amount",
        ):
            detail[key] = _fmt_money_2(detail.get(key))


def _wechat_success_xml_response() -> Response:
    payload = (
        "<xml>"
        "<return_code><![CDATA[SUCCESS]]></return_code>"
        "<return_msg><![CDATA[OK]]></return_msg>"
        "</xml>"
    )
    return Response(
        status_code=200,
        content=payload.encode("utf-8"),
        headers={"content-type": "text/xml; charset=utf-8"},
    )


def _md5_hex_local(text: str) -> str:
    return hashlib.md5(text.encode("utf-8")).hexdigest()


def _php_trim_chars(text: str, trim_chars: str) -> str:
    if not trim_chars:
        return text.strip()
    mask = set(trim_chars)
    left = 0
    right = len(text) - 1
    while left <= right and text[left] in mask:
        left += 1
    while right >= left and text[right] in mask:
        right -= 1
    return text[left : right + 1]


def _php_sym_encrypt(text: str, key: str) -> str:
    source = f"{text}{key}"
    chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+"
    rand = random.randint(0, 64)
    ch = chars[rand]
    md_key = _md5_hex_local(f"{key}{ch}")
    start = rand % 8
    length = (rand % 8) + 7
    md_key = md_key[start : start + length]
    source_b64 = base64.b64encode(source.encode("utf-8")).decode("utf-8")

    out: list[str] = []
    k = 0
    for c in source_b64:
        if k == len(md_key):
            k = 0
        pos = chars.find(c)
        if pos < 0:
            continue
        j = (rand + pos + ord(md_key[k])) % 64
        out.append(chars[j])
        k += 1
    payload = ch + "".join(out)
    return urllib.parse.quote(base64.b64encode(payload.encode("utf-8")).decode("utf-8"), safe="")


def _php_sym_decrypt(text: str, key: str) -> str:
    chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+"
    try:
        raw = base64.b64decode(urllib.parse.unquote(text)).decode("utf-8")
    except Exception:
        return ""
    if not raw:
        return ""
    ch = raw[0]
    rand = chars.find(ch)
    if rand < 0:
        return ""
    md_key = _md5_hex_local(f"{key}{ch}")
    start = rand % 8
    length = (rand % 8) + 7
    md_key = md_key[start : start + length]
    source = raw[1:]

    out: list[str] = []
    k = 0
    for c in source:
        if k == len(md_key):
            k = 0
        pos = chars.find(c)
        if pos < 0:
            continue
        j = pos - rand - ord(md_key[k])
        while j < 0:
            j += 64
        out.append(chars[j])
        k += 1
    try:
        decoded = base64.b64decode("".join(out)).decode("utf-8")
    except Exception:
        return ""
    return _php_trim_chars(decoded, key)


def _normalize_aes256_key(key: str) -> bytes:
    raw = str(key).encode("utf-8")
    if len(raw) >= 32:
        return raw[:32]
    return raw + b"\x00" * (32 - len(raw))


def _sys_encrypt(text: str, key: str) -> str:
    key_bytes = _normalize_aes256_key(key)
    iv = os.urandom(16)
    padder = padding.PKCS7(128).padder()
    padded = padder.update(text.encode("utf-8")) + padder.finalize()
    cipher = Cipher(algorithms.AES(key_bytes), modes.CBC(iv))
    encryptor = cipher.encryptor()
    encrypted = encryptor.update(padded) + encryptor.finalize()
    return base64.b64encode(iv + encrypted).decode("utf-8")


def _sys_decrypt(text: str, key: str) -> str:
    try:
        raw = base64.b64decode(text)
    except Exception:
        return ""
    if len(raw) <= 16:
        return ""
    iv = raw[:16]
    encrypted = raw[16:]
    try:
        cipher = Cipher(algorithms.AES(_normalize_aes256_key(key)), modes.CBC(iv))
        decryptor = cipher.decryptor()
        padded = decryptor.update(encrypted) + decryptor.finalize()
        unpadder = padding.PKCS7(128).unpadder()
        plain = unpadder.update(padded) + unpadder.finalize()
        return plain.decode("utf-8")
    except Exception:
        return ""


def _parse_mid_from_bstr(bstr: Any, key: str) -> int:
    token = _string_or_empty(bstr)
    if not token:
        return 0
    if token.isdigit():
        return _int_or_default(token, 0)

    decrypted = _sys_decrypt(token, key)
    if decrypted.isdigit():
        return _int_or_default(decrypted, 0)
    if decrypted.startswith("mid:"):
        return _int_or_default(decrypted[4:], 0)
    if decrypted.startswith("seller:"):
        return _int_or_default(decrypted.split(":", 1)[1], 0)
    return 0


def _build_system_payload(*, slide_tag: str = "index") -> dict[str, Any]:
    system = fetch_one(
        "SELECT service, policy, name, logo, copyright, act_rule, tel, "
        "is_open_api, message_code, is_queue_number, is_qrcode_number, is_clock_switch "
        "FROM tp_system WHERE id=%s",
        (1,),
    ) or {}
    slide = fetch_one(
        "SELECT * FROM tp_slide WHERE status=%s AND tags=%s ORDER BY id ASC LIMIT 1",
        (1, slide_tag),
    )
    return {
        "service": system.get("service"),
        "policy": system.get("policy"),
        "name": system.get("name") or "",
        "logo": system.get("logo") or "",
        "copyright": system.get("copyright") or "",
        "act_rule": system.get("act_rule"),
        "tel": system.get("tel") or "",
        "is_open_api": _int_or_default(system.get("is_open_api"), 0),
        "message_code": _int_or_default(system.get("message_code"), 0),
        "is_queue_number": _int_or_default(system.get("is_queue_number"), 0),
        "is_qrcode_number": _int_or_default(system.get("is_qrcode_number"), 0),
        "is_clock_switch": _int_or_default(system.get("is_clock_switch"), 0),
        "slide": slide,
    }


def _ticket_order_status_text(status: Any) -> str:
    mapping = {
        "created": "待支付",
        "paid": "已付款",
        "used": "已使用",
        "cancelled": "已取消",
        "refunded": "已退款",
    }
    return mapping.get(_string_or_empty(status), "-")


def _ticket_channel_text(channel: Any) -> str:
    mapping = {
        "online": "线上",
        "window": "窗口",
        "ota_xc": "携程",
        "ota_mt": "美团",
        "travel": "旅行社",
    }
    return mapping.get(_string_or_empty(channel), "-")


def _ticket_refund_status_text(status: Any) -> str:
    mapping = {
        "not_refunded": "未退款",
        "partially_refunded": "部分退款",
        "fully_refunded": "已退款",
    }
    return mapping.get(_string_or_empty(status), "-")


def _ticket_refund_progress_text(progress: Any) -> str:
    mapping = {
        "init": "未申请",
        "pending_review": "待审核",
        "refuse": "已拒绝",
        "approved": "已通过",
        "completed": "已退款",
    }
    return mapping.get(_string_or_empty(progress), "-")


def _parse_lock_timestamp(value: Any) -> int:
    text = _string_or_empty(value)
    if not text:
        return 0
    if text.isdigit():
        return _int_or_default(text, 0)
    for fmt in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%d"):
        try:
            dt = datetime.strptime(text, fmt).replace(tzinfo=_TZ_SHANGHAI)
            return int(dt.timestamp())
        except Exception:
            continue
    return 0


def _openid_from_code(code: str) -> str:
    text = _string_or_empty(code)
    if not text:
        return ""
    return "mock-openid-" + _md5_hex_local(text)[:16]


def _parse_codes(value: Any) -> list[str]:
    text = _string_or_empty(value)
    if not text:
        return []
    return [seg.strip() for seg in text.split(",") if seg.strip()]


def _safe_runtime_write_path(path_value: str, filename: str) -> Path | None:
    safe_path = path_value.strip().replace("\\", "/")
    safe_name = filename.strip().replace("\\", "/")
    if not safe_path or not safe_name:
        return None
    rel = Path(safe_path)
    if rel.is_absolute() or ".." in rel.parts:
        return None
    if "/" in safe_name or ".." in Path(safe_name).parts:
        return None
    root = Path("runtime")
    return root / rel / safe_name


def _append_text_line(path: Path, line: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("a", encoding="utf-8") as f:
        f.write(line)
        if not line.endswith("\n"):
            f.write("\n")


def _normalize_model_name(value: Any, default: str = "Coupon") -> str:
    model = str(value or "").strip()
    if not model:
        model = default
    return model[:1].upper() + model[1:] if model else default


def _partner_id_from_env(default: int = 703) -> int:
    raw = str(os.environ.get("MEITUAN_PARTNER_ID") or "").strip()
    if not raw:
        return default
    try:
        return int(raw)
    except Exception:
        return default


def _mock_captcha_png_response() -> Response:
    # 1x1 transparent PNG
    png_bytes = (
        b"\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01"
        b"\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\rIDATx\x9cc`\x00\x01"
        b"\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82"
    )
    return Response(
        status_code=200,
        content=png_bytes,
        headers={"content-type": "image/png"},
    )


def _meituan_text_json_response(payload: dict[str, Any]) -> Response:
    return Response(
        status_code=200,
        content=json.dumps(payload, ensure_ascii=False).encode("utf-8"),
        media_type="text/html; charset=UTF-8",
    )


def _meituan_auth_error_response(describe: Any = "BA验证错误") -> Response:
    return _meituan_text_json_response(
        {
            "code": 300,
            "describe": describe,
            "partnerId": str(_partner_id_from_env()),
        }
    )


def _xc_parse_error_response() -> JSONResponse:
    return JSONResponse(
        status_code=200,
        content={"header": {"resultCode": "0001", "resultMessage": "报文解析失败:body解密错误"}},
        headers={"content-type": "application/json; charset=utf-8"},
    )


def _int_or_default(value: Any, default: int = 0) -> int:
    try:
        return int(str(value))
    except Exception:
        return default


def _decimal_or_default(value: Any, default: str = "0.00") -> Decimal:
    try:
        return Decimal(str(value))
    except Exception:
        return Decimal(default)


def _json_object_or_empty(value: Any) -> dict[str, Any]:
    if isinstance(value, dict):
        return value
    try:
        parsed = json.loads(str(value or ""))
    except Exception:
        return {}
    return parsed if isinstance(parsed, dict) else {}


def _random_digits(length: int = 6) -> str:
    return "".join(str(random.randint(0, 9)) for _ in range(max(1, length)))


def _next_order_no() -> str:
    return datetime.now(_TZ_SHANGHAI).strftime("%Y%m%d%H%M%S") + _random_digits(6)


def _mock_pay_payload(pay_type: str, order_no: str) -> Any:
    ptype = (pay_type or "").strip().lower() or "miniapp"
    if ptype == "wap":
        return f"https://mock-pay.local/mweb?order_no={order_no}"
    return {
        "timeStamp": "0",
        "nonceStr": "mock-nonce",
        "package": "prepay_id=mock_prepay",
        "signType": "MD5",
        "paySign": "mock-sign",
    }


def _string_or_empty(value: Any) -> str:
    return str(value or "").strip()


def _first_missing_required(params: dict[str, Any], names: list[str]) -> str | None:
    for name in names:
        if not _string_or_empty(params.get(name)):
            return name
    return None


def _ticket_order_status_error(status: str) -> str | None:
    if status == "created":
        return "未支付订单无法退款!"
    if status == "used":
        return "已使用订单无法退款!"
    if status == "cancelled":
        return "已取消订单无法退款!"
    if status == "refunded":
        return "该订单已经全额退款!"
    return None


def _env_enabled(name: str, default: bool = False) -> bool:
    raw = os.environ.get(name)
    if raw is None:
        return default
    return str(raw).strip().lower() in {"1", "true", "yes", "on"}


def _request_optin_enabled(request: Request, header_name: str = "X-Rewrite-Enable") -> bool:
    raw = request.headers.get(header_name)
    if raw is None:
        return False
    return str(raw).strip().lower() in {"1", "true", "yes", "on"}


def _allow_route_mainline(request: Request, env_name: str | None = None) -> bool:
    if _request_optin_enabled(request):
        return True
    if not env_name:
        return False
    return _env_enabled(env_name, False)


def _is_success_seed_enabled() -> bool:
    row = fetch_one("SELECT app_code FROM tp_system WHERE id=%s", (1,)) or {}
    return _string_or_empty(row.get("app_code")) == "mock-app-code"


def _is_valid_mobile(value: str) -> bool:
    return bool(re.fullmatch(r"1\d{10}", value))


def _is_valid_idcard(value: str) -> bool:
    return bool(re.fullmatch(r"(\d{15}|\d{17}[\dXx])", value))


def _build_json_response(payload: dict[str, Any]) -> JSONResponse:
    return JSONResponse(
        status_code=200,
        content=payload,
        headers={"content-type": "application/json; charset=utf-8"},
    )


async def _collect_upload_params_and_files(request: Request) -> tuple[dict[str, str], bool]:
    params: dict[str, str] = {}
    for k, v in request.query_params.multi_items():
        params[str(k)] = str(v)

    has_file = False
    try:
        form = await request.form()
        for k, v in form.multi_items():
            if hasattr(v, "filename"):
                if str(getattr(v, "filename", "")).strip():
                    has_file = True
                continue
            params[str(k)] = str(v)
    except Exception:
        if "multipart/form-data" in (request.headers.get("content-type") or "").lower():
            has_file = True

    return params, has_file


async def _upload_like_response(request: Request) -> Response:
    params, has_file = await _collect_upload_params_and_files(request)
    from_type = _string_or_empty(params.get("from")).lower()

    if from_type == "ckeditor":
        if has_file:
            return _build_json_response(
                {"uploaded": False, "url": "", "message": "Failed to open temp directory."}
            )
        return _build_json_response({"uploaded": False, "url": "", "message": "没有选择上传文件"})

    if has_file:
        return _build_json_response({"code": 0, "msg": "Failed to open temp directory.", "url": ""})

    return _build_json_response({"code": 0, "msg": "ERROR:没有选择上传文件", "url": ""})


def _insert_users_auth_log(
    *,
    uid: int,
    name: str,
    mobile: str,
    idcard: str,
    status: str,
    result: int,
    message: str,
    order_no: str = "",
    return_data: str = "",
) -> None:
    execute(
        "INSERT INTO tp_users_auth_log "
        "(uid, name, idcard, mobile, create_time, order_no, status, result, msg, return_data, update_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            uid,
            name,
            idcard,
            mobile,
            int(time.time()),
            order_no,
            status,
            result,
            message,
            return_data,
            int(time.time()),
        ),
    )


def _float_or_default(value: Any, default: float = 0.0) -> float:
    try:
        return float(str(value))
    except Exception:
        return default


def _appt_status_text(status: Any) -> str:
    status_map = {0: "待核销", 1: "已核销", 2: "已取消"}
    return status_map.get(_int_or_default(status, 0), "-")


def _appt_date_text(value: Any) -> str:
    if value is None:
        return ""
    return str(value)


def _appt_time_text(seconds_value: Any) -> str:
    seconds = _int_or_default(seconds_value, 0)
    hour = max(0, seconds) // 3600
    minute = (max(0, seconds) % 3600) // 60
    return f"{hour:02d}:{minute:02d}"


def _appt_iso_datetime(date_value: Any, seconds_value: Any) -> str | None:
    date_text = _appt_date_text(date_value)
    if not date_text:
        return None
    try:
        base = datetime.strptime(date_text, "%Y-%m-%d")
    except Exception:
        return None
    dt = base + timedelta(seconds=_int_or_default(seconds_value, 0))
    return dt.strftime("%Y-%m-%d %H:%M")


def _appt_qrcode_str(log_code: str, log_id: Any, expire_ts: int | None = None) -> str:
    if not expire_ts:
        expire_ts = int(time.time()) + 600
    plain = f"log&{log_code}&{expire_ts}"
    key = str(_int_or_default(log_id, 0) or "").strip()
    if not key:
        return plain
    return _sys_encrypt(plain, key)


def _appt_format_log(row: dict[str, Any], *, include_qrcode: bool, include_tourists: bool) -> dict[str, Any]:
    out = dict(row)
    out["create_time"] = _fmt_ts_shanghai(out.get("create_time"))
    out["time_start_text"] = _appt_time_text(out.get("time_start"))
    out["time_end_text"] = _appt_time_text(out.get("time_end"))
    out["start"] = _appt_iso_datetime(out.get("date"), out.get("time_start"))
    out["end"] = _appt_iso_datetime(out.get("date"), out.get("time_end"))
    out["status_text"] = _appt_status_text(out.get("status"))
    if include_qrcode:
        out["qrcode_str"] = _appt_qrcode_str(
            _string_or_empty(out.get("code")),
            out.get("id"),
        )
    if include_tourists:
        log_id = _int_or_default(out.get("id"), 0)
        if log_id > 0:
            out["tourist_list"] = fetch_all(
                "SELECT id, tourist_fullname, tourist_cert_type, tourist_cert_id, tourist_mobile, status "
                "FROM tp_ticket_appt_log_tourist WHERE log_id=%s ORDER BY id ASC",
                (log_id,),
            )
        else:
            out["tourist_list"] = []
    out["seller"] = {
        "nickname": _string_or_empty(out.pop("seller_nickname", "")),
        "image": _string_or_empty(out.pop("seller_image", "")),
    }
    for k in [
        "seller_id",
        "user_id",
        "writeoff_id",
        "writeoff_name",
        "lat",
        "lng",
        "address",
        "ip",
    ]:
        out.pop(k, None)
    return out


def _is_chinese_name(value: str) -> bool:
    return bool(re.fullmatch(r"[\u4e00-\u9fff]+", value))


def _mask_idcard(value: str) -> str:
    text = _string_or_empty(value)
    if len(text) < 8:
        return text
    return text[:4] + "*" * max(0, len(text) - 8) + text[-4:]


def _mask_name(value: str) -> str:
    text = _string_or_empty(value)
    if not text:
        return text
    if len(text) <= 2:
        return text[0] + "*"
    return text[0] + "*" * (len(text) - 2) + text[-1]


def _mask_mobile(value: str) -> str:
    text = _string_or_empty(value)
    if len(text) < 7:
        return text
    return text[:3] + "****" + text[7:]


def _pagination_payload(*, total: int, page: int, per_page: int, rows: list[dict[str, Any]]) -> dict[str, Any]:
    last_page = 0 if total <= 0 else (total + per_page - 1) // per_page
    return {
        "total": total,
        "per_page": per_page,
        "current_page": page,
        "last_page": last_page,
        "data": rows,
    }


def _today_unix_range(now_ts: int | None = None) -> tuple[int, int]:
    now = datetime.now(_TZ_SHANGHAI) if now_ts is None else datetime.fromtimestamp(now_ts, tz=_TZ_SHANGHAI)
    start = now.replace(hour=0, minute=0, second=0, microsecond=0)
    end = now.replace(hour=23, minute=59, second=59, microsecond=0)
    return int(start.timestamp()), int(end.timestamp())


def _calculate_distance_meters(lat1: float, lng1: float, lat2: float, lng2: float) -> float:
    r = 6378137.0
    lat1_rad = math.radians(lat1)
    lat2_rad = math.radians(lat2)
    dlat = lat2_rad - lat1_rad
    dlng = math.radians(lng2 - lng1)
    a = (
        math.sin(dlat / 2) ** 2
        + math.cos(lat1_rad) * math.cos(lat2_rad) * math.sin(dlng / 2) ** 2
    )
    c = 2 * math.asin(min(1.0, math.sqrt(max(0.0, a))))
    return r * c


def _coupon_issue_rows(
    *,
    uid: int,
    page: int,
    limit: int,
    tag: int,
    class_id: int,
    use_store: int,
) -> list[dict[str, Any]]:
    where = ["status = 1", "is_del = 0", "receive_type = 1", "coupon_type IN (1, 2)"]
    args: list[Any] = []
    if tag > 0:
        where.append("tag = %s")
        args.append(tag)
    if class_id > 0:
        where.append("class_id = %s")
        args.append(class_id)
    if use_store > 0:
        where.append("use_store = %s")
        args.append(use_store)
    page = max(1, page)
    limit = max(1, min(200, limit))
    offset = (page - 1) * limit
    rows = fetch_all(
        "SELECT * FROM tp_coupon_issue WHERE "
        + " AND ".join(where)
        + " ORDER BY sort DESC, id DESC LIMIT %s, %s",
        tuple(args + [offset, limit]),
    )

    used_ids: set[int] = set()
    if uid > 0 and rows:
        coupon_ids = [int(r.get("id") or 0) for r in rows if int(r.get("id") or 0) > 0]
        if coupon_ids:
            placeholders = ",".join(["%s"] * len(coupon_ids))
            used_rows = fetch_all(
                f"SELECT issue_coupon_id FROM tp_coupon_issue_user WHERE uid=%s AND issue_coupon_id IN ({placeholders})",
                tuple([uid] + coupon_ids),
            )
            used_ids = {int(r.get("issue_coupon_id") or 0) for r in used_rows}

    out: list[dict[str, Any]] = []
    for row in rows:
        item = dict(row)
        item["coupon_price"] = _float_or_default(item.get("coupon_price"), 0.0)
        item["use_min_price"] = _float_or_default(item.get("use_min_price"), 0.0)
        item["is_use"] = int(item.get("id") or 0) in used_ids
        if _int_or_default(item.get("coupon_time_end"), 0) > 0:
            item["coupon_time_start"] = datetime.fromtimestamp(
                _int_or_default(item.get("coupon_time_start"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y/%m/%d")
            item["coupon_time_end"] = datetime.fromtimestamp(
                _int_or_default(item.get("coupon_time_end"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y/%m/%d")
        if _int_or_default(item.get("start_time"), 0) > 0:
            item["start_time"] = datetime.fromtimestamp(
                _int_or_default(item.get("start_time"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y/%m/%d")
            item["end_time"] = datetime.fromtimestamp(
                _int_or_default(item.get("end_time"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y/%m/%d")
        out.append(item)
    return out


def _coupon_class_with_list(
    *,
    issue_rows: list[dict[str, Any]],
    max_per_class: int | None,
) -> list[dict[str, Any]]:
    classes = fetch_all(
        "SELECT id, title, class_icon FROM tp_coupon_class WHERE status=%s ORDER BY sort ASC",
        (1,),
    )
    out: list[dict[str, Any]] = []
    for row in classes:
        cid = _int_or_default(row.get("id"), 0)
        selected = [v for v in issue_rows if _int_or_default(v.get("cid"), 0) == cid]
        if max_per_class is not None:
            selected = selected[: max(0, max_per_class)]
        item = dict(row)
        item["list"] = selected
        out.append(item)
    return out


@fastapi_app.on_event("startup")
def _startup_load_cases() -> None:
    global GOLDEN
    by_key: dict[tuple[str, str], RecordedResponse] = {}
    by_id: dict[str, RecordedResponse] = {}
    for cases_dir in get_cases_dirs():
        if cases_dir.exists():
            k, i = load_golden_cases(cases_dir)
            by_key.update(k)
            by_id.update(i)
    GOLDEN = by_key

    global GOLDEN_BY_ID
    GOLDEN_BY_ID = by_id

    global P0_INTERFACES
    P0_INTERFACES = load_p0_interfaces(get_p0_interfaces_path())


@fastapi_app.middleware("http")
async def _auth_middleware(request: Request, call_next: Any) -> Response:
    # Let CORS preflight and health checks pass.
    if request.method.upper() == "OPTIONS" or request.url.path == "/health":
        return await call_next(request)

    spec = P0_INTERFACES.get(request.url.path)
    if spec and spec.requires_auth:
        if spec.auth in {"api-token(Userid)", "window-token(Uuid)", "selfservice-token(No)"}:
            try:
                enforce_auth(request, spec.auth)
            except BaseException as e:
                return auth_error_response(e)
        elif spec.auth == "meituan(BA-sign)":
            try:
                enforce_meituan_ba_sign(request)
            except MeituanAuthError as e:
                return meituan_auth_error_response(str(e))
        elif spec.auth == "xc(AES+md5-sign)":
            try:
                await enforce_xc_aes_md5_sign(request)
            except XcAuthError as e:
                return xc_auth_error_response(e)
    window_shell_response = _maybe_serve_window_shell(request)
    if window_shell_response is not None:
        return window_shell_response
    upload_error_response = _maybe_serve_legacy_upload_error(request)
    if upload_error_response is not None:
        return upload_error_response
    return await call_next(request)


@fastapi_app.middleware("http")
async def _maintenance_capture_middleware(request: Request, call_next: Any) -> Response:
    if not capture_enabled():
        return await call_next(request)

    body = await request.body()
    try:
        append_jsonl(capture_log_path(), build_capture_event(request, body))
    except Exception:
        pass

    path = str(request.url.path or "/")
    if path.startswith("/xc/"):
        return JSONResponse(
            status_code=200,
            content={"header": {"resultCode": "9999", "resultMessage": "maintenance"}},
            headers={"content-type": "application/json; charset=utf-8"},
        )

    if path.startswith("/meituan/"):
        partner_id = (os.environ.get("MEITUAN_PARTNER_ID") or "0").strip() or "0"
        payload = {"code": 300, "describe": "maintenance", "partnerId": str(partner_id)}
        return Response(
            status_code=200,
            content=json.dumps(payload, ensure_ascii=False).encode("utf-8"),
            media_type="text/html; charset=UTF-8",
        )

    return api_error("maintenance", code=999, data=[], status_code=503)


@fastapi_app.get("/health")
def health() -> dict[str, Any]:
    return {"ok": True, "time": int(time.time())}


@fastapi_app.post("/api/index/system")
def api_index_system() -> JSONResponse:
    return api_success(_build_system_payload(slide_tag="index"), "请求成功")


@fastapi_app.post("/meituan/index/system")
def meituan_index_system() -> JSONResponse:
    return api_index_system()


@fastapi_app.post("/xc/index/system")
def xc_index_system() -> JSONResponse:
    return api_index_system()


@fastapi_app.api_route("/meituan/index/change", methods=["GET", "POST"])
def meituan_index_change() -> JSONResponse:
    return JSONResponse(
        status_code=200,
        content={"code": 200, "describe": "成功", "partnerId": _partner_id_from_env()},
        headers={"content-type": "text/html; charset=utf-8"},
    )


@fastapi_app.api_route("/xc/index/change", methods=["GET", "POST"])
def xc_index_change() -> JSONResponse:
    return JSONResponse(
        status_code=200,
        content={"code": 200, "describe": "成功", "partnerId": _partner_id_from_env()},
        headers={"content-type": "text/html; charset=utf-8"},
    )


@fastapi_app.api_route("/meituan/index/demo", methods=["GET", "POST"])
def meituan_index_demo() -> JSONResponse:
    return JSONResponse(
        status_code=200,
        content={
            "code": 200,
            "describe": "success",
            "partnerId": _partner_id_from_env(),
            "body": [],
        },
        headers={"content-type": "text/html; charset=utf-8"},
    )


@fastapi_app.api_route("/meituan/index/captcha", methods=["GET", "POST"])
def meituan_index_captcha() -> Response:
    return _mock_captcha_png_response()


@fastapi_app.api_route("/xc/index/captcha", methods=["GET", "POST"])
def xc_index_captcha() -> Response:
    return _mock_captcha_png_response()


def _ota_mock_winlogin(username: str) -> JSONResponse:
    row = fetch_one(
        "SELECT id, uuid, username, nickname, login_time, login_ip, loginnum, mid, status "
        "FROM tp_ticket_user WHERE username=%s ORDER BY id DESC LIMIT 1",
        (username,),
    )
    if not row:
        return api_error("帐号或密码错误")
    if int(row.get("status") or 0) != 1:
        return api_error("用户已被禁用,请于平台联系")

    now_ts = int(time.time())
    token = f"mock.{row.get('uuid')}.{now_ts}"
    return api_success(
        {
            "id": row.get("id"),
            "uuid": row.get("uuid"),
            "username": row.get("username"),
            "login_time": datetime.fromtimestamp(now_ts, tz=_TZ_SHANGHAI).strftime("%Y-%m-%d %H:%M:%S"),
            "login_ip": row.get("login_ip") or "",
            "nickname": row.get("nickname") or "",
            "loginnum": int(row.get("loginnum") or 0),
            "token": token,
            "m_nickname": "",
            "m_id": int(row.get("mid") or 0),
            "businesstr": "",
        },
        "登录成功",
    )


@fastapi_app.api_route("/meituan/index/winlogin", methods=["POST"])
async def meituan_index_winlogin(request: Request) -> JSONResponse:
    params = await get_params(request)
    for key in ("username", "password", "pubkey", "code"):
        if not str(params.get(key) or "").strip():
            return api_error(f"{key}不能为空")
    return _ota_mock_winlogin(str(params.get("username") or "").strip())


@fastapi_app.api_route("/xc/index/winlogin", methods=["POST"])
async def xc_index_winlogin(request: Request) -> JSONResponse:
    params = await get_params(request)
    for key in ("username", "password", "pubkey", "code"):
        if not str(params.get(key) or "").strip():
            return api_error(f"{key}不能为空")
    return _ota_mock_winlogin(str(params.get("username") or "").strip())


@fastapi_app.api_route("/meituan/ticket/getMt", methods=["GET", "POST"])
def meituan_ticket_get_mt() -> Response:
    return _meituan_auth_error_response(describe=None)


@fastapi_app.api_route("/meituan/ticket/pay", methods=["POST"])
def meituan_ticket_pay() -> Response:
    return _meituan_auth_error_response()


@fastapi_app.api_route("/meituan/upload/index", methods=["GET", "POST"])
def meituan_upload_index() -> Response:
    return _meituan_auth_error_response()


@fastapi_app.api_route("/meituan/webupload/index", methods=["GET", "POST"])
async def meituan_webupload_index(request: Request) -> Response:
    if request.method.upper() != "POST":
        return _meituan_auth_error_response()
    return await _upload_like_response(request)


@fastapi_app.api_route("/xc/order/CancelOrder", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/order/CancelPreOrder", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/order/CreatePreOrder", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/order/DateInventoryModify", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/order/PayPreOrder", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/order/QueryOrder", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/order/VerifyOrder", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/order/accept", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/ticket/OrderRefund", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/ticket/OrderRefundDetail", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/ticket/getTicketPirce", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/ticket/detail", methods=["POST"])
@fastapi_app.api_route("/xc/ticket/list", methods=["POST"])
@fastapi_app.api_route("/xc/ticket/pay", methods=["POST"])
@fastapi_app.api_route("/xc/ticket/refund", methods=["POST"])
@fastapi_app.api_route("/xc/ticket/single_refund", methods=["POST"])
@fastapi_app.api_route("/xc/upload/index", methods=["GET", "POST"])
def xc_ota_parse_error_routes() -> JSONResponse:
    return _xc_parse_error_response()


@fastapi_app.api_route("/xc/order/OrderConsumedNotice", methods=["GET", "POST"])
@fastapi_app.api_route("/xc/order/OrderTravelNotice", methods=["GET", "POST"])
def xc_ota_order_notice_routes() -> Response:
    return _xc_order_info_empty_response()


@fastapi_app.api_route("/xc/order/testGetOrder", methods=["GET", "POST"])
def xc_ota_upload_error_routes(request: Request) -> Response:
    if not _allow_route_mainline(request):
        return _legacy_upload_error_response()
    return _xc_parse_error_response()


@fastapi_app.api_route("/xc/webupload/index", methods=["GET", "POST"])
async def xc_webupload_index(request: Request) -> Response:
    if request.method.upper() != "POST":
        return _legacy_upload_error_response()
    content_type = str(request.headers.get("content-type") or "").lower()
    if "multipart/form-data" not in content_type:
        return _legacy_upload_error_response()
    return await _upload_like_response(request)


@fastapi_app.api_route("/xc/ticket/stats", methods=["POST"])
def xc_ticket_stats() -> JSONResponse:
    return api_error("参数错误！")


@fastapi_app.api_route("/api/pay/aaa", methods=["GET", "POST"])
def api_pay_aaa() -> Response:
    return Response(
        status_code=200,
        content=b"",
        headers={"content-type": "text/html; charset=utf-8"},
    )


@fastapi_app.api_route("/api/pay/OrderRefund", methods=["GET", "POST"])
@fastapi_app.api_route("/api/pay/regressionStock", methods=["GET", "POST"])
async def api_pay_internal_methods(request: Request) -> Response:
    params = await get_params(request)
    if not params:
        return _legacy_upload_error_response()
    return api_error("参数错误")


@fastapi_app.api_route("/api/pay/submit", methods=["POST"])
async def api_pay_submit(request: Request) -> JSONResponse:
    params = await get_params(request)
    user_id = _int_or_default(params.get("uid"), 0)
    openid = _string_or_empty(params.get("openid"))
    coupon_uuno = _string_or_empty(params.get("coupon_uuno"))
    pay_type = _string_or_empty(params.get("type")) or "miniapp"
    payload = _json_object_or_empty(params.get("data"))

    if not openid:
        return api_error("当前用户信息异常")

    user_info = fetch_one("SELECT id, uuid, openid FROM tp_users WHERE id=%s LIMIT 1", (user_id,))
    if not user_info or _string_or_empty(user_info.get("openid")) != openid:
        return api_error("当前用户信息异常，禁止提交")

    number_count = _int_or_default(payload.get("number"), 0)
    if number_count == 0:
        return api_error("请至少购买一张消费券")

    price = _decimal_or_default(payload.get("price"), "0")
    if price <= Decimal("0"):
        return api_error("消费券面额至少大于0.01，否则无法调起支付")

    coupon_issue = fetch_one(
        "SELECT id, cid, uuno, coupon_title, coupon_price, coupon_icon, sale_price, "
        "is_limit_total, limit_total FROM tp_coupon_issue WHERE uuno=%s LIMIT 1",
        (coupon_uuno,),
    )
    sale_price = _decimal_or_default(coupon_issue.get("sale_price") if coupon_issue else 0, "0")
    if sale_price <= Decimal("0"):
        return api_error("消费券面额至少小于0.01，无法调起支付")

    if sale_price.quantize(Decimal("0.01")) != price.quantize(Decimal("0.01")):
        return api_error("消费券购买价异常，请查证")

    if int(coupon_issue.get("is_limit_total") or 0) == 1:
        used_total_row = fetch_one(
            "SELECT COUNT(*) AS n FROM tp_coupon_issue_user "
            "WHERE uid=%s AND issue_coupon_id=%s AND issue_coupon_class_id=%s",
            (
                int(user_info.get("id") or 0),
                int(coupon_issue.get("id") or 0),
                int(coupon_issue.get("cid") or 0),
            ),
        ) or {}
        if int(coupon_issue.get("limit_total") or 0) <= int(used_total_row.get("n") or 0):
            return api_error("购买已达上限", code=3)

    amount_price = (price * Decimal(str(number_count))).quantize(Decimal("0.00"))
    order_no = _next_order_no()
    now_ts = int(time.time())
    amount_price_text = f"{amount_price:.2f}"

    execute(
        "INSERT INTO tp_coupon_order "
        "(openid, uuid, mch_id, order_no, order_out_no, origin_price, amount_price, "
        "payment_code, payment_trade, payment_status, payment_image, payment_remark, "
        "payment_datetime, number_count, order_remark, cancel_status, cancel_remark, cancel_datetime, "
        "deleted_status, deleted_remark, deleted_datetime, is_refund, status, create_time, update_time, "
        "issue_coupon_user_id, payment_data_id) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            openid,
            _string_or_empty(user_info.get("uuid")),
            0,
            order_no,
            f"XFQ{order_no}",
            amount_price_text,
            amount_price_text,
            "",
            "",
            0,
            "",
            "",
            "0",
            number_count,
            "用户购买消费券",
            0,
            "",
            0,
            0,
            "",
            0,
            0,
            1,
            now_ts,
            now_ts,
            "",
            0,
        ),
    )

    execute(
        "INSERT INTO tp_coupon_order_item "
        "(uuid, order_no, coupon_uuno, coupon_cid, coupon_title, coupon_price, coupon_icon, "
        "coupon_sale_price, total_market, price_selling, total_selling, stock_sales, discount_amount, "
        "status, deleted, update_time, create_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            _string_or_empty(user_info.get("uuid")),
            order_no,
            _string_or_empty(coupon_issue.get("uuno")),
            int(coupon_issue.get("cid") or 0),
            _string_or_empty(coupon_issue.get("coupon_title")),
            _decimal_or_default(coupon_issue.get("coupon_price"), "0"),
            _string_or_empty(coupon_issue.get("coupon_icon")),
            sale_price,
            amount_price_text,
            sale_price,
            amount_price_text,
            number_count,
            0,
            0,
            0,
            now_ts,
            now_ts,
        ),
    )

    execute(
        "UPDATE tp_coupon_issue SET remain_count = remain_count - %s, update_time=%s WHERE uuno=%s",
        (number_count, now_ts, coupon_uuno),
    )

    execute(
        "INSERT INTO tp_base_paydata "
        "(order_no, openid, body, money, model, payip, trade_type, total_fee, status, update_time, create_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            order_no,
            openid,
            "用户购买消费券",
            amount_price_text,
            "Coupon",
            request.client.host if request.client else "",
            "JSAPI",
            int((amount_price * Decimal("100")).quantize(Decimal("1"))),
            0,
            now_ts,
            now_ts,
        ),
    )

    return api_success(
        {
            "pay": _mock_pay_payload(pay_type, order_no),
            "order_no": order_no,
            "amount_price": amount_price_text,
        },
        "订单添加成功",
    )


@fastapi_app.api_route("/api/pay/refund", methods=["POST"])
async def api_pay_refund(request: Request) -> JSONResponse:
    params = await get_params(request)
    user_id = _int_or_default(params.get("uid"), 0)
    openid = _string_or_empty(params.get("openid"))
    order_remark = _string_or_empty(params.get("order_remark"))
    order_no = _string_or_empty(params.get("order_no"))
    coupon_issue_user_id = _int_or_default(params.get("coupon_issue_user_id"), 0)

    if coupon_issue_user_id == 0:
        return api_error("领取记录不存在")
    if not order_no or not order_remark:
        return api_error("退款信息错误")
    if not openid:
        return api_error("当前用户信息异常")

    user_info = fetch_one("SELECT id, uuid, openid FROM tp_users WHERE id=%s LIMIT 1", (user_id,))
    if not user_info or _string_or_empty(user_info.get("openid")) != openid:
        return api_error("当前用户信息异常，禁止提交")

    order = fetch_one(
        "SELECT id, order_no, payment_trade, payment_code, amount_price, number_count, is_refund "
        "FROM tp_coupon_order WHERE order_no=%s LIMIT 1",
        (order_no,),
    )
    if not order:
        return api_error("支付订单不存在!")
    if not _string_or_empty(order.get("payment_trade")):
        return api_error("该订单未支付成功无法退款")
    if int(order.get("is_refund") or 0) > 0:
        return api_error("订单正在退款中或者已经退款")

    coupon_issue_user = fetch_one(
        "SELECT id, status, issue_coupon_id FROM tp_coupon_issue_user WHERE id=%s LIMIT 1",
        (coupon_issue_user_id,),
    )
    if not coupon_issue_user:
        return api_error("领取记录不存在")
    if int(coupon_issue_user.get("status") or 0) == 1:
        return api_error("该消费券已使用,无法退款")

    now_ts = int(time.time())
    refund_fee = _decimal_or_default(order.get("amount_price"), "0.00")

    execute(
        "UPDATE tp_coupon_issue SET remain_count = remain_count + %s, update_time=%s WHERE id=%s",
        (
            int(order.get("number_count") or 0),
            now_ts,
            int(coupon_issue_user.get("issue_coupon_id") or 0),
        ),
    )

    out_refund_no = "RF" + _next_order_no()
    execute(
        "INSERT INTO tp_base_refunds "
        "(appid, mch_id, order_no, out_refund_no, total_fee, refund_fee, refund_desc, model, refund_ip, "
        "return_code, result_code, transaction_id, refund_id, settlement_refund_fee, refund_status, "
        "success_time, refund_recv_accout, status, update_time, create_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            "",
            "",
            order_no,
            out_refund_no,
            f"{refund_fee:.2f}",
            f"{refund_fee:.2f}",
            order_remark,
            "Coupon",
            request.client.host if request.client else "",
            "SUCCESS",
            "SUCCESS",
            _string_or_empty(order.get("payment_trade")),
            "",
            f"{refund_fee:.2f}",
            "PENDING",
            "",
            "",
            0,
            now_ts,
            now_ts,
        ),
    )

    execute(
        "UPDATE tp_coupon_order SET is_refund=%s, order_remark=%s, update_time=%s WHERE id=%s",
        (1, order_remark, now_ts, int(order.get("id") or 0)),
    )
    execute(
        "UPDATE tp_coupon_issue_user SET is_fail=%s, update_time=%s WHERE id=%s",
        ("0", now_ts, int(coupon_issue_user.get("id") or 0)),
    )

    return api_success(True, "申请成功")


@fastapi_app.api_route("/api/ticket/OrderRefund", methods=["GET", "POST"])
@fastapi_app.api_route("/api/ticket/OrderRefundDetail", methods=["GET", "POST"])
async def api_ticket_internal_methods(request: Request) -> Response:
    params = await get_params(request)
    if not params:
        return _legacy_upload_error_response()
    return api_error("参数错误")


@fastapi_app.api_route("/api/ticket/notify_pay", methods=["POST"])
@fastapi_app.api_route("/api/ticket/notify_refund", methods=["POST"])
async def api_ticket_notify_callbacks(request: Request) -> Response:
    params = await get_params(request)
    mainline_enabled = _allow_route_mainline(request, "REWRITE_ENABLE_TICKET_NOTIFY_CALLBACKS")
    if not params:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        params = {}
    if not mainline_enabled:
        return _legacy_upload_error_response()
    return _wechat_success_xml_response()


@fastapi_app.api_route("/api/ticket/refund", methods=["POST"])
async def api_ticket_refund(request: Request) -> JSONResponse:
    params = await get_params(request)
    missing = _first_missing_required(params, ["uuid", "openid", "refund_desc", "out_trade_no"])
    if missing:
        return api_error(f"{missing}不能为空")

    uuid = _string_or_empty(params.get("uuid"))
    openid = _string_or_empty(params.get("openid"))
    out_trade_no = _string_or_empty(params.get("out_trade_no"))
    refund_desc = _string_or_empty(params.get("refund_desc"))

    user_info = fetch_one("SELECT id, uuid, openid FROM tp_users WHERE uuid=%s LIMIT 1", (uuid,))
    if not user_info or _string_or_empty(user_info.get("openid")) != openid:
        return api_error("当前用户信息异常，禁止提交")

    order = fetch_one(
        "SELECT id, trade_no, mch_id, amount_price, transaction_id, order_status, refund_status "
        "FROM tp_ticket_order WHERE out_trade_no=%s AND uuid=%s LIMIT 1",
        (out_trade_no, uuid),
    )
    if not order:
        return api_error("支付订单不存在!")

    order_status_msg = _ticket_order_status_error(_string_or_empty(order.get("order_status")))
    if order_status_msg:
        return api_error(order_status_msg)
    if _string_or_empty(order.get("refund_status")) == "fully_refunded":
        return api_error("该订单已经全额退款!")

    detail_list = fetch_all(
        "SELECT id, enter_time, refund_status, refund_progress, ticket_price, out_trade_no "
        "FROM tp_ticket_order_detail WHERE trade_no=%s",
        (_string_or_empty(order.get("trade_no")),),
    )
    for item in detail_list:
        if int(item.get("enter_time") or 0) > 0:
            return api_error("该订单中已有游客使用，不允许全退！")
        if _string_or_empty(item.get("refund_status")) == "fully_refunded":
            return api_error("该订单中已有游客退款，不允许全退！")
        if _string_or_empty(item.get("refund_progress")) not in {"init", "refuse"}:
            return api_error("该订单中已有游客有退款行为，不允许全退！")

    now_ts = int(time.time())
    refund_fee = Decimal("0.00")
    for item in detail_list:
        refund_fee += _decimal_or_default(item.get("ticket_price"), "0")
    refund_fee = refund_fee.quantize(Decimal("0.00"))
    out_refund_no = "BIG" + _next_order_no()

    execute(
        "INSERT INTO tp_ticket_refunds "
        "(uuid, appid, mch_id, trade_no, order_detail_no, out_refund_no, total_fee, refund_fee, refund_desc, "
        "refund_ip, return_code, result_code, transaction_id, refund_id, settlement_refund_fee, refund_status, "
        "success_time, refund_recv_accout, status, update_time, create_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            uuid,
            "",
            str(order.get("mch_id") or ""),
            _string_or_empty(order.get("trade_no")),
            "0",
            out_refund_no,
            f"{_decimal_or_default(order.get('amount_price'), '0'):.2f}",
            f"{refund_fee:.2f}",
            refund_desc,
            request.client.host if request.client else "",
            "SUCCESS",
            "SUCCESS",
            _string_or_empty(order.get("transaction_id")),
            "",
            f"{refund_fee:.2f}",
            "PENDING",
            "",
            "",
            0,
            now_ts,
            now_ts,
        ),
    )
    execute(
        "UPDATE tp_ticket_order_detail SET refund_progress=%s, is_full_refund=%s, update_time=%s "
        "WHERE trade_no=%s AND refund_status=%s AND refund_progress IN (%s, %s)",
        (
            "pending_review",
            1,
            now_ts,
            _string_or_empty(order.get("trade_no")),
            "not_refunded",
            "init",
            "refuse",
        ),
    )

    return api_success(True, "申请成功")


@fastapi_app.api_route("/api/ticket/single_refund", methods=["POST"])
async def api_ticket_single_refund(request: Request) -> JSONResponse:
    params = await get_params(request)
    missing = _first_missing_required(params, ["uuid", "openid", "refund_desc", "out_trade_no"])
    if missing:
        return api_error(f"{missing}不能为空")

    uuid = _string_or_empty(params.get("uuid"))
    openid = _string_or_empty(params.get("openid"))
    out_trade_no = _string_or_empty(params.get("out_trade_no"))
    refund_desc = _string_or_empty(params.get("refund_desc"))

    user_info = fetch_one("SELECT id, uuid, openid FROM tp_users WHERE uuid=%s LIMIT 1", (uuid,))
    if not user_info or _string_or_empty(user_info.get("openid")) != openid:
        return api_error("当前用户信息异常，禁止提交")

    order_detail = fetch_one(
        "SELECT id, trade_no, out_trade_no, out_refund_no, ticket_price, enter_time, refund_status, refund_progress "
        "FROM tp_ticket_order_detail WHERE out_trade_no=%s AND uuid=%s LIMIT 1",
        (out_trade_no, uuid),
    )
    if not order_detail:
        return api_error("支付订单不存在!")
    if int(order_detail.get("enter_time") or 0) > 0:
        return api_error("该游客已入园，不允许退款!")
    if _string_or_empty(order_detail.get("refund_status")) == "fully_refunded":
        return api_error("该订单已经全额退款!")

    progress = _string_or_empty(order_detail.get("refund_progress"))
    if progress == "pending_review":
        return api_error("该订单已经提交退款")
    if progress == "approved":
        return api_error("该订单已经通过退款审核，请稍后查看")
    if progress == "completed":
        return api_error("该订单已经完成退款")

    order = fetch_one(
        "SELECT id, trade_no, mch_id, amount_price, transaction_id, order_status, refund_status "
        "FROM tp_ticket_order WHERE trade_no=%s LIMIT 1",
        (_string_or_empty(order_detail.get("trade_no")),),
    )
    if not order:
        return api_error("支付订单不存在!")

    order_status_msg = _ticket_order_status_error(_string_or_empty(order.get("order_status")))
    if order_status_msg:
        return api_error(order_status_msg)
    if _string_or_empty(order.get("refund_status")) == "fully_refunded":
        return api_error("该订单已经全额退款!")

    now_ts = int(time.time())
    out_refund_no = _string_or_empty(order_detail.get("out_refund_no")) or ("RF" + _next_order_no())
    execute(
        "INSERT INTO tp_ticket_refunds "
        "(uuid, appid, mch_id, trade_no, order_detail_no, out_refund_no, total_fee, refund_fee, refund_desc, "
        "refund_ip, return_code, result_code, transaction_id, refund_id, settlement_refund_fee, refund_status, "
        "success_time, refund_recv_accout, status, update_time, create_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            uuid,
            "",
            str(order.get("mch_id") or ""),
            _string_or_empty(order.get("trade_no")),
            _string_or_empty(order_detail.get("out_trade_no")),
            out_refund_no,
            f"{_decimal_or_default(order.get('amount_price'), '0'):.2f}",
            f"{_decimal_or_default(order_detail.get('ticket_price'), '0'):.2f}",
            refund_desc,
            request.client.host if request.client else "",
            "SUCCESS",
            "SUCCESS",
            _string_or_empty(order.get("transaction_id")),
            "",
            f"{_decimal_or_default(order_detail.get('ticket_price'), '0'):.2f}",
            "PENDING",
            "",
            "",
            0,
            now_ts,
            now_ts,
        ),
    )
    execute(
        "UPDATE tp_ticket_order_detail SET refund_progress=%s, is_full_refund=%s, update_time=%s WHERE id=%s",
        ("pending_review", 0, now_ts, int(order_detail.get("id") or 0)),
    )

    return api_success(True, "申请成功")


@fastapi_app.api_route("/api/ticket/cancelRefund", methods=["POST"])
async def api_ticket_cancel_refund(request: Request) -> JSONResponse:
    params = await get_params(request)
    ticket_type = _string_or_empty(params.get("type"))
    record_id = _int_or_default(params.get("id"), 0)
    if not ticket_type or record_id == 0:
        return api_error("参数错误！")

    user_id = _int_or_default(request.headers.get("Userid"), 0)
    user_info = fetch_one("SELECT id, uuid FROM tp_users WHERE id=%s LIMIT 1", (user_id,))
    if not user_info:
        return api_error("用户不存在！")

    now_ts = int(time.time())
    if ticket_type == "order":
        order_info = fetch_one(
            "SELECT id, uuid, trade_no, order_status FROM tp_ticket_order WHERE id=%s LIMIT 1",
            (record_id,),
        )
        if not order_info or _string_or_empty(order_info.get("uuid")) != _string_or_empty(user_info.get("uuid")):
            return api_error("订单不存在！")
        if _string_or_empty(order_info.get("order_status")) != "paid":
            return api_error("订单状态不符！")

        execute(
            "UPDATE tp_ticket_refunds SET status=%s, update_time=%s WHERE trade_no=%s AND uuid=%s AND status=%s",
            (3, now_ts, _string_or_empty(order_info.get("trade_no")), _string_or_empty(user_info.get("uuid")), 0),
        )
        execute(
            "UPDATE tp_ticket_order_detail SET refund_progress=%s, update_time=%s WHERE trade_no=%s AND refund_progress=%s",
            ("init", now_ts, _string_or_empty(order_info.get("trade_no")), "pending_review"),
        )
        return api_success([], "取消成功！")

    if ticket_type == "order_detail":
        detail_info = fetch_one(
            "SELECT id, uuid, out_trade_no, refund_progress FROM tp_ticket_order_detail WHERE id=%s LIMIT 1",
            (record_id,),
        )
        if not detail_info or _string_or_empty(detail_info.get("uuid")) != _string_or_empty(user_info.get("uuid")):
            return api_error("门票不存在！")
        if _string_or_empty(detail_info.get("refund_progress")) != "pending_review":
            return api_error("该门票状态不符！")

        execute(
            "UPDATE tp_ticket_refunds SET status=%s, update_time=%s "
            "WHERE order_detail_no=%s AND uuid=%s AND status=%s",
            (3, now_ts, _string_or_empty(detail_info.get("out_trade_no")), _string_or_empty(user_info.get("uuid")), 0),
        )
        execute(
            "UPDATE tp_ticket_order_detail SET refund_progress=%s, update_time=%s WHERE id=%s",
            ("init", now_ts, int(detail_info.get("id") or 0)),
        )
        return api_success([], "取消成功！")

    return api_error("参数错误！")


@fastapi_app.api_route("/window/ticket/refund", methods=["POST"])
async def window_ticket_refund(request: Request) -> JSONResponse:
    params = await get_params(request)
    if not _string_or_empty(params.get("refund_desc")):
        return api_error("refund_desc不能为空")
    if not _string_or_empty(params.get("out_trade_no")):
        return api_error("out_trade_no不能为空")
    if not _string_or_empty(params.get("uuid")):
        return api_error("uuid不能为空")
    return api_success([], "申请成功")


@fastapi_app.api_route("/window/ticket/single_refund", methods=["POST"])
async def window_ticket_single_refund(request: Request) -> JSONResponse:
    params = await get_params(request)
    if not _string_or_empty(params.get("refund_desc")):
        return api_error("refund_desc不能为空")
    if not _string_or_empty(params.get("out_trade_no")):
        return api_error("ticket_code不能为空")
    if not _string_or_empty(params.get("uuid")):
        return api_error("uuid不能为空")
    return api_success([], "申请成功")


@fastapi_app.api_route("/api/index/getuserphonenumber", methods=["POST"])
async def api_index_getuserphonenumber(request: Request) -> JSONResponse:
    params = await get_params(request)
    if not _string_or_empty(params.get("code")):
        return api_error("参数错误")

    if not _is_success_seed_enabled():
        return api_error("获取错误,请重试！")

    return api_success(
        {"errcode": 0, "errmsg": "ok", "phone_info": {"phoneNumber": "13800000000"}},
        "请求成功",
    )


@fastapi_app.api_route("/api/index/miniwxlogin", methods=["POST"])
async def api_index_miniwxlogin(request: Request) -> JSONResponse:
    params = await get_params(request)
    if not _string_or_empty(params.get("code")):
        return api_error("参数错误")

    return api_error("未注册", code=4444, data={"openid": "mock-openid"})


@fastapi_app.api_route("/api/user/auth_info", methods=["POST"])
async def api_user_auth_info(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("用户ID不能为空！")

    user = fetch_one(
        "SELECT name, mobile, idcard, auth_status FROM tp_users WHERE id=%s LIMIT 1",
        (uid,),
    )
    if not user:
        return api_error("当前用户不存在")

    idcard = user.get("idcard")
    if _string_or_empty(idcard) == "":
        idcard = None
    return api_success(
        {
            "name": _string_or_empty(user.get("name")),
            "mobile": _string_or_empty(user.get("mobile")),
            "idcard": idcard,
            "auth_status": int(user.get("auth_status") or 0),
        },
        "请求成功",
    )


@fastapi_app.api_route("/api/user/miniwxregister", methods=["POST"])
async def api_user_miniwxregister(request: Request) -> JSONResponse:
    params = await get_params(request)
    required = ["openid", "mobile", "name", "idcard"]
    if any(name not in params for name in required):
        return api_error("参数错误")

    openid = _string_or_empty(params.get("openid"))
    mobile = _string_or_empty(params.get("mobile"))
    name = _string_or_empty(params.get("name"))
    idcard = _string_or_empty(params.get("idcard"))
    if not name:
        return api_error("请输入正确的姓名！")
    if not _is_valid_mobile(mobile):
        return api_error("手机号错误")
    if not _is_valid_idcard(idcard):
        return api_error("请输入正确的身份证号码")

    existing_openid = fetch_one("SELECT id FROM tp_users WHERE openid=%s LIMIT 1", (openid,))
    if existing_openid:
        return api_error("当前微信已经注册")

    mobile_user = fetch_one("SELECT id, openid, name, idcard FROM tp_users WHERE mobile=%s LIMIT 1", (mobile,))
    if mobile_user:
        if _string_or_empty(mobile_user.get("openid")):
            return api_error(f"当前手机号已经绑定其他账号：{_string_or_empty(mobile_user.get('name'))}")
        if _string_or_empty(mobile_user.get("idcard")) == idcard:
            now_ts = int(time.time())
            execute(
                "UPDATE tp_users SET openid=%s, update_time=%s, last_login_time=%s WHERE id=%s",
                (openid, now_ts, now_ts, int(mobile_user.get("id") or 0)),
            )
            token = f"mock.{int(mobile_user.get('id') or 0)}.{now_ts}"
            execute(
                "UPDATE tp_users SET signpass=%s, expiry_time=%s WHERE id=%s",
                (
                    f"mock-sign-{int(mobile_user.get('id') or 0)}",
                    now_ts + 30 * 24 * 3600,
                    int(mobile_user.get("id") or 0),
                ),
            )
            userinfo = fetch_one("SELECT * FROM tp_users WHERE id=%s LIMIT 1", (int(mobile_user.get("id") or 0),)) or {}
            return api_success({"token": token, "userinfo": userinfo}, "登录成功")
        return api_error(f"当前手机号已经绑定其他身份证号：{_string_or_empty(mobile_user.get('idcard'))}")

    idcard_user = fetch_one(
        "SELECT id, mobile FROM tp_users WHERE idcard=%s AND idcard IS NOT NULL LIMIT 1",
        (idcard,),
    )
    if idcard_user:
        return api_error(f"当前身份证号已经绑定其他手机号：{_string_or_empty(idcard_user.get('mobile'))}")

    now_ts = int(time.time())
    uuid = f"mock-user-{_next_order_no()}"
    execute(
        "INSERT INTO tp_users "
        "(email, password, sex, last_login_time, last_login_ip, qq, mobile, mobile_validated, email_validated, "
        "type_id, status, create_ip, update_time, create_time, openid, name, headimgurl, idcard, age, nickname, "
        "uuid, salt, starsign, zodiac, birthday, province, city, district, signpass, expiry_time, card_type, "
        "credit_score, credit_rating, update_credit, auth_status) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, "
        "%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            "",
            "",
            0,
            now_ts,
            request.client.host if request.client else "",
            "",
            mobile,
            0,
            0,
            0,
            1,
            request.client.host if request.client else "",
            now_ts,
            now_ts,
            openid,
            name,
            "",
            idcard,
            0,
            "",
            uuid,
            "mock-salt",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            0,
            1,
            "0",
            "",
            0,
            0,
        ),
    )
    user = fetch_one("SELECT * FROM tp_users WHERE openid=%s LIMIT 1", (openid,)) or {}
    user_id = int(user.get("id") or 0)
    token = f"mock.{user_id}.{now_ts}"
    execute(
        "UPDATE tp_users SET signpass=%s, expiry_time=%s WHERE id=%s",
        (f"mock-sign-{user_id}", now_ts + 30 * 24 * 3600, user_id),
    )
    user = fetch_one("SELECT * FROM tp_users WHERE id=%s LIMIT 1", (user_id,)) or {}
    return api_success({"token": token, "userinfo": user}, "注册成功")


@fastapi_app.api_route("/api/user/auth_identity", methods=["POST"])
async def api_user_auth_identity(request: Request) -> JSONResponse:
    params = await get_params(request)
    required = ["mobile", "name", "idcard", "uid"]
    if any(name not in params for name in required):
        return api_error("参数错误")

    uid = _int_or_default(params.get("uid"), 0)
    mobile = _string_or_empty(params.get("mobile"))
    name = _string_or_empty(params.get("name"))
    idcard = _string_or_empty(params.get("idcard")).upper()
    tags = _int_or_default(params.get("tags"), 0)
    sms_code = _string_or_empty(params.get("smsCode"))

    if tags == 1:
        sms_row = fetch_one(
            "SELECT id, expire_time FROM tp_users_sms_log WHERE uid=%s AND mobile=%s AND sms_code=%s "
            "ORDER BY create_time DESC LIMIT 1",
            (uid, mobile, sms_code),
        )
        if not sms_row:
            return api_error("验证码错误")
        if int(time.time()) > int(sms_row.get("expire_time") or 0):
            return api_error("验证码已过期")

    if not name:
        return api_error("请输入正确的姓名！")
    if not _is_valid_mobile(mobile):
        return api_error("手机号错误")
    if not _is_valid_idcard(idcard):
        return api_error("请输入正确的身份证号码")

    user = fetch_one(
        "SELECT id, auth_status, credit_score, name, idcard FROM tp_users WHERE id=%s LIMIT 1",
        (uid,),
    )
    if not user:
        return api_error("用户不存在")
    if int(user.get("auth_status") or 0) == 1:
        return api_error("当前用户已经认证")

    if _int_or_default(user.get("credit_score"), 0) > 0 and _string_or_empty(user.get("name")) == name and _string_or_empty(user.get("idcard")).upper() == idcard:
        execute(
            "UPDATE tp_users SET auth_status=%s, mobile=%s, update_time=%s WHERE id=%s",
            (1, mobile, int(time.time()), uid),
        )
        return api_success("无需2要素认证", "认证成功")

    other_mobile = fetch_one(
        "SELECT id, name FROM tp_users WHERE mobile=%s AND openid IS NOT NULL AND id<>%s LIMIT 1",
        (mobile, uid),
    )
    if other_mobile:
        return api_error(f"当前手机号已经绑定其他微信{_string_or_empty(other_mobile.get('name'))}")

    other_idcard = fetch_one(
        "SELECT id, mobile FROM tp_users WHERE idcard=%s AND idcard IS NOT NULL AND id<>%s LIMIT 1",
        (idcard, uid),
    )
    if other_idcard:
        return api_error(f"当前身份证号已经绑定其他手机号:{_string_or_empty(other_idcard.get('mobile'))}")

    if _env_enabled("REWRITE_MOCK_IDENTITY", False) and _is_success_seed_enabled():
        now_ts = int(time.time())
        payload = {"status": "OK", "state": 1, "request_id": "mock-request-id", "result_message": "mock ok"}
        _insert_users_auth_log(
            uid=uid,
            name=name,
            mobile=mobile,
            idcard=idcard,
            status="OK",
            result=1,
            message="mock ok",
            order_no="mock-request-id",
            return_data=json.dumps(payload, ensure_ascii=False),
        )
        execute(
            "UPDATE tp_users SET auth_status=%s, name=%s, mobile=%s, idcard=%s, update_time=%s WHERE id=%s",
            (1, name, mobile, idcard, now_ts, uid),
        )
        return api_success("mock ok", "认证成功")

    system = fetch_one("SELECT app_code FROM tp_system WHERE id=%s LIMIT 1", (1,)) or {}
    if not _string_or_empty(system.get("app_code")):
        return api_error("请配置认证代码")

    return api_error("身份认证无法通过")


@fastapi_app.api_route("/api/user/smsVerification", methods=["POST"])
async def api_user_sms_verification(request: Request) -> JSONResponse:
    params = await get_params(request)
    required = ["mobile", "uid"]
    if any(name not in params for name in required):
        return api_error("参数错误")

    uid = _int_or_default(params.get("uid"), 0)
    mobile = _string_or_empty(params.get("mobile"))
    if not _is_valid_mobile(mobile):
        return api_error("手机号错误")

    now_ts = int(time.time())

    if _env_enabled("REWRITE_MOCK_SMS", False):
        execute(
            "INSERT INTO tp_users_sms_log "
            "(uid, mobile, sms_code, template, create_time, smsid, code, balance, msg, expire_time) "
            "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            (
                uid,
                mobile,
                "123456",
                "",
                now_ts,
                "mock-smsid",
                "0",
                9999,
                "mock ok",
                now_ts + 5 * 60,
            ),
        )
        return api_success([], "发送成功")

    count_row = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_users_sms_log WHERE create_time>%s AND uid=%s",
        (now_ts - 3600, uid),
    ) or {}
    if int(count_row.get("n") or 0) >= 3:
        return api_error("对不起, 超出发送频率,过一会儿再试！")

    return api_error("发送失败:产品账户已禁用【国内验证码通知短信】")


@fastapi_app.api_route("/api/upload/index", methods=["GET", "POST", "OPTIONS"])
@fastapi_app.api_route("/api/webupload/index", methods=["GET", "POST", "OPTIONS"])
@fastapi_app.api_route("/selfservice/upload/index", methods=["GET", "POST", "OPTIONS"])
@fastapi_app.api_route("/selfservice/webupload/index", methods=["GET", "POST", "OPTIONS"])
@fastapi_app.api_route("/window/upload/index", methods=["GET", "POST", "OPTIONS"])
@fastapi_app.api_route("/window/webupload/index", methods=["GET", "POST", "OPTIONS"])
async def upload_like_endpoints(request: Request) -> Response:
    if request.method.upper() == "OPTIONS":
        return Response(status_code=200, content=b"")
    return await _upload_like_response(request)


@fastapi_app.api_route("/api/appt/getDatetime", methods=["GET", "POST"])
async def api_appt_get_datetime(request: Request) -> JSONResponse:
    if request.method.upper() != "GET":
        return api_error("请求方式错误！")

    params = await get_params(request)
    seller_id = _int_or_default(params.get("seller_id"), 0)

    if seller_id == 0:
        return api_error("缺少商户参数")

    date_start = str(params.get("date_start") or "").strip()
    date_end = str(params.get("date_end") or "").strip()

    where = ["seller_id=%s"]
    sql_params: list[Any] = [seller_id]

    if date_start:
        where.append("date >= %s")
        sql_params.append(date_start)
    else:
        where.append("date >= CURDATE()")

    if date_end:
        where.append("date <= %s")
        sql_params.append(date_end)

    datetime_list = fetch_all(
        "SELECT * FROM tp_ticket_appt_datetime WHERE "
        + " AND ".join(where)
        + " ORDER BY date ASC",
        tuple(sql_params),
    )

    grouped: Any = []
    if datetime_list:
        grouped_dict: dict[str, list[dict[str, Any]]] = {}
        for item in datetime_list:
            date_text = _appt_date_text(item.get("date"))
            item["time_start_text"] = _appt_time_text(item.get("time_start"))
            item["time_end_text"] = _appt_time_text(item.get("time_end"))
            item["start"] = _appt_iso_datetime(item.get("date"), item.get("time_start"))
            item["end"] = _appt_iso_datetime(item.get("date"), item.get("time_end"))
            grouped_dict.setdefault(date_text, []).append(item)
        grouped = grouped_dict

    seller_row = fetch_one("SELECT appt_limit FROM tp_seller WHERE id=%s", (seller_id,))
    number = seller_row.get("appt_limit") if seller_row else None

    return api_success({"number": number, "list": grouped}, "")


@fastapi_app.api_route("/api/appt/getList", methods=["GET", "POST"])
async def api_appt_get_list(request: Request) -> JSONResponse:
    if request.method.upper() != "GET":
        return api_error("请求方式错误！")

    params = await get_params(request)
    user_id = _int_or_default(request.headers.get("Userid"), 0)
    status = _string_or_empty(params.get("status"))
    page = max(1, _int_or_default(params.get("page"), 1))
    page_size = max(1, _int_or_default(params.get("page_size"), 10))
    offset = (page - 1) * page_size

    where = ["l.user_id = %s"]
    sql_params: list[Any] = [user_id]
    if status != "":
        where.append("l.status = %s")
        sql_params.append(_int_or_default(status, 0))

    rows = fetch_all(
        "SELECT l.*, s.nickname AS seller_nickname, s.image AS seller_image "
        "FROM tp_ticket_appt_log l "
        "LEFT JOIN tp_seller s ON s.id = l.seller_id "
        "WHERE "
        + " AND ".join(where)
        + " ORDER BY l.id DESC LIMIT %s, %s",
        tuple(sql_params + [offset, page_size]),
    )
    out = [_appt_format_log(row, include_qrcode=False, include_tourists=False) for row in rows]
    return api_success(out, "ok")


@fastapi_app.api_route("/api/appt/getDetail", methods=["GET", "POST"])
async def api_appt_get_detail(request: Request) -> JSONResponse:
    if request.method.upper() != "GET":
        return api_error("请求方式错误！")

    params = await get_params(request)
    log_id_raw = _string_or_empty(params.get("id"))
    if not log_id_raw:
        return api_error("缺少参数！")
    if not log_id_raw.isdigit():
        return _legacy_upload_error_response()
    log_id = _int_or_default(log_id_raw, 0)
    if log_id <= 0:
        return _legacy_upload_error_response()

    row = fetch_one(
        "SELECT l.*, s.nickname AS seller_nickname, s.image AS seller_image "
        "FROM tp_ticket_appt_log l "
        "LEFT JOIN tp_seller s ON s.id = l.seller_id "
        "WHERE l.id=%s LIMIT 1",
        (log_id,),
    )
    if not row:
        return _legacy_upload_error_response()

    return api_success(_appt_format_log(row, include_qrcode=True, include_tourists=True), "")


@fastapi_app.api_route("/api/appt/createAppt", methods=["GET", "POST"])
async def api_appt_create_appt(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("请求方式错误！")

    params = await get_params(request)
    if not _string_or_empty(params.get("datetime_id")):
        return api_error("请选择时间段！")

    fullname = _string_or_empty(params.get("fullname"))
    if not fullname:
        return api_error("姓名不能为空！")
    if not _is_chinese_name(fullname):
        return api_error("姓名只能是汉字！")
    if len(fullname) > 10:
        return api_error("姓名长度超出！")

    phone = _string_or_empty(params.get("phone"))
    if not phone:
        return api_error("手机号不能为空！")
    if not _is_valid_mobile(phone):
        return api_error("手机号格式不符")

    idcard = _string_or_empty(params.get("idcard"))
    if not idcard:
        return api_error("身份证号不能为空！")
    if not _is_valid_idcard(idcard):
        return api_error("身份证号格式不符！")

    number_raw = _string_or_empty(params.get("number"))
    if not number_raw:
        return api_error("预约人数必填！！")
    number = _int_or_default(number_raw, 0)
    if number <= 0:
        return api_error("预约人数必填！！")

    if not _string_or_empty(params.get("lat")):
        return api_error("当前位置纬度不能为空！")
    if not _string_or_empty(params.get("lng")):
        return api_error("当前位置经度不能为空！")

    tourist_raw = _string_or_empty(params.get("tourist"))
    if not tourist_raw:
        return api_error("游客信息不能为空！")

    tourist_text = tourist_raw.replace("&quot;", "\"")
    try:
        tourist_list = json.loads(tourist_text)
    except Exception:
        tourist_list = None
    if not isinstance(tourist_list, list):
        return api_error("游客信息格式错误！")
    if len(tourist_list) != number:
        return api_error("缺少游客信息！")

    datetime_id = _int_or_default(params.get("datetime_id"), 0)
    datetime_info = fetch_one("SELECT * FROM tp_ticket_appt_datetime WHERE id=%s LIMIT 1", (datetime_id,))
    if not datetime_info:
        return api_error("该时段不存在！")

    date_text = _appt_date_text(datetime_info.get("date"))
    try:
        date_start_ts = int(datetime.strptime(date_text, "%Y-%m-%d").timestamp())
    except Exception:
        date_start_ts = 0
    if int(time.time()) > (date_start_ts + _int_or_default(datetime_info.get("time_end"), 0)):
        return api_error("预约时间已过！")
    if _int_or_default(datetime_info.get("stock"), 0) < number:
        return api_error("该时段已约满！")

    seller_info = fetch_one(
        "SELECT id, appt_open, appt_limit FROM tp_seller WHERE id=%s LIMIT 1",
        (int(datetime_info.get("seller_id") or 0),),
    )
    if not seller_info:
        return api_error("商户不存在！")
    if _int_or_default(seller_info.get("appt_open"), 0) != 1:
        return api_error("商户未开启预约！")

    user_id = _int_or_default(request.headers.get("Userid"), 0)
    appt_number_row = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_ticket_appt_log_tourist WHERE user_id=%s AND date=%s AND seller_id=%s",
        (user_id, date_text, int(datetime_info.get("seller_id") or 0)),
    ) or {}
    appt_number = _int_or_default(appt_number_row.get("n"), 0)
    appt_limit = _int_or_default(seller_info.get("appt_limit"), 0)
    if appt_limit > 0 and (appt_number + number) > appt_limit:
        return api_error(f"每日只允许预约{appt_limit}人，您已预约{appt_number}人！")

    now_ts = int(time.time())
    log_code = _next_order_no()[:20]
    try:
        execute(
            "INSERT INTO tp_ticket_appt_log "
            "(code, seller_id, user_id, date, time_start, time_end, fullname, idcard, phone, number, status, lat, lng, address, ip, create_time) "
            "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            (
                log_code,
                int(datetime_info.get("seller_id") or 0),
                user_id,
                date_text,
                _int_or_default(datetime_info.get("time_start"), 0),
                _int_or_default(datetime_info.get("time_end"), 0),
                fullname,
                idcard,
                phone,
                number,
                0,
                _string_or_empty(params.get("lat")),
                _string_or_empty(params.get("lng")),
                "",
                request.client.host if request.client else "",
                now_ts,
            ),
        )
        log_row = fetch_one("SELECT id FROM tp_ticket_appt_log WHERE code=%s ORDER BY id DESC LIMIT 1", (log_code,))
        log_id = _int_or_default((log_row or {}).get("id"), 0)
        if log_id <= 0:
            return api_error("预约失败！")

        for item in tourist_list:
            if not isinstance(item, dict):
                return api_error("游客信息格式错误！")
            tourist_fullname = _string_or_empty(item.get("fullname"))
            tourist_cert_type = _int_or_default(item.get("cert_type"), 0)
            tourist_cert_id = _string_or_empty(item.get("cert_id"))
            tourist_mobile = _string_or_empty(item.get("mobile"))
            duplicate = fetch_one(
                "SELECT id FROM tp_ticket_appt_log_tourist "
                "WHERE seller_id=%s AND date=%s AND tourist_fullname=%s AND tourist_cert_id=%s LIMIT 1",
                (int(datetime_info.get("seller_id") or 0), date_text, tourist_fullname, tourist_cert_id),
            )
            if duplicate:
                return api_error(f"{tourist_fullname}在{date_text}已预约，请删除该游客后再试！")
            execute(
                "INSERT INTO tp_ticket_appt_log_tourist "
                "(code, log_id, seller_id, user_id, date, time_start, time_end, tourist_fullname, tourist_cert_type, tourist_cert_id, tourist_mobile, status, create_time) "
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                (
                    _next_order_no()[:20],
                    log_id,
                    int(datetime_info.get("seller_id") or 0),
                    user_id,
                    date_text,
                    _int_or_default(datetime_info.get("time_start"), 0),
                    _int_or_default(datetime_info.get("time_end"), 0),
                    tourist_fullname,
                    tourist_cert_type,
                    tourist_cert_id,
                    tourist_mobile,
                    0,
                    now_ts,
                ),
            )
        execute(
            "UPDATE tp_ticket_appt_datetime SET stock = stock - %s WHERE id=%s",
            (number, datetime_id),
        )
    except Exception as e:
        return api_error("预约失败！" + str(e))

    return api_success([], "预约成功！")


@fastapi_app.api_route("/api/appt/writeOff", methods=["GET", "POST"])
async def api_appt_write_off(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("请求方式错误！")

    params = await get_params(request)
    if not _string_or_empty(params.get("qrcode_str")):
        return api_error("参数错误！")
    if not _string_or_empty(params.get("be_id")):
        return api_error("参数错误！")
    if not _string_or_empty(params.get("use_lat")):
        return api_error("核销纬度不能为空！")
    if not _string_or_empty(params.get("use_lng")):
        return api_error("核销经度不能为空")

    user_id = _int_or_default(request.headers.get("Userid"), 0)
    hx_man = fetch_one(
        "SELECT id, uid, mid, type, status, name FROM tp_merchant_verifier WHERE uid=%s AND type=%s LIMIT 1",
        (user_id, "appt"),
    )
    if not hx_man:
        return api_error("核销人不存在！")
    if _int_or_default(hx_man.get("status"), 0) != 1:
        return api_error("核销人未通过审核！")
    if _string_or_empty(hx_man.get("type")) != "appt":
        return api_error("核销人不允许核销预约！")

    qrcode_parts = _string_or_empty(params.get("qrcode_str")).split("&")
    if len(qrcode_parts) != 3:
        return api_error("核销码不正确！")
    if qrcode_parts[0] not in {"log", "logtourist"}:
        return api_error("核销码不正确！")
    if _int_or_default(qrcode_parts[2], 0) < int(time.time()):
        return api_error("核销码过期，刷新后再试！")

    if qrcode_parts[0] != "log":
        return api_error("类型错误！")

    log_info = fetch_one("SELECT * FROM tp_ticket_appt_log WHERE code=%s LIMIT 1", (qrcode_parts[1],))
    if not log_info:
        return api_error("核销记录不存在！")
    if _int_or_default(log_info.get("seller_id"), 0) != _int_or_default(hx_man.get("mid"), 0):
        return api_error("不允许核销其他商户的预约记录！")
    if _int_or_default(log_info.get("status"), 0) != 0:
        return api_error("已被核销！")
    if _appt_date_text(log_info.get("date")) != datetime.now(_TZ_SHANGHAI).strftime("%Y-%m-%d"):
        return api_error("预约日期不是今天，不允许核销！")

    now_ts = int(time.time())
    try:
        execute(
            "UPDATE tp_ticket_appt_log "
            "SET status=%s, writeoff_time=%s, writeoff_id=%s, writeoff_name=%s, lat=%s, lng=%s, ip=%s "
            "WHERE id=%s",
            (
                1,
                now_ts,
                _int_or_default(hx_man.get("id"), 0),
                _string_or_empty(hx_man.get("name")),
                _string_or_empty(params.get("use_lat")),
                _string_or_empty(params.get("use_lng")),
                request.client.host if request.client else "",
                _int_or_default(log_info.get("id"), 0),
            ),
        )
        datetime_info = fetch_one(
            "SELECT id, use_num FROM tp_ticket_appt_datetime "
            "WHERE date=%s AND time_start<=%s AND time_end>%s LIMIT 1",
            (
                _appt_date_text(log_info.get("date")),
                _int_or_default(log_info.get("time_end"), 0),
                _int_or_default(log_info.get("time_start"), 0),
            ),
        )
        if datetime_info:
            execute(
                "UPDATE tp_ticket_appt_datetime SET use_num=%s WHERE id=%s",
                (
                    _int_or_default(datetime_info.get("use_num"), 0) + _int_or_default(log_info.get("number"), 0),
                    _int_or_default(datetime_info.get("id"), 0),
                ),
            )
        execute(
            "UPDATE tp_ticket_appt_log_tourist "
            "SET status=%s, writeoff_time=%s, writeoff_id=%s, writeoff_name=%s, writeoff_lat=%s, writeoff_lng=%s, writeoff_ip=%s "
            "WHERE log_id=%s AND status=%s",
            (
                1,
                now_ts,
                _int_or_default(hx_man.get("id"), 0),
                _string_or_empty(hx_man.get("name")),
                _string_or_empty(params.get("use_lat")),
                _string_or_empty(params.get("use_lng")),
                request.client.host if request.client else "",
                _int_or_default(log_info.get("id"), 0),
                0,
            ),
        )
    except Exception as e:
        return api_error("核销失败！" + str(e))

    return api_success([], "核销成功！")


@fastapi_app.api_route("/api/appt/cancelAppt", methods=["GET", "POST"])
async def api_appt_cancel_appt(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("请求方式错误！")

    params = await get_params(request)
    log_id_raw = _string_or_empty(params.get("log_id"))
    if not log_id_raw:
        return api_error("参数错误！")
    log_id = _int_or_default(log_id_raw, 0)
    if log_id <= 0:
        return _legacy_upload_error_response()

    user_id = _int_or_default(request.headers.get("Userid"), 0)
    log_info = fetch_one("SELECT * FROM tp_ticket_appt_log WHERE id=%s LIMIT 1", (log_id,))
    if not log_info:
        return _legacy_upload_error_response()
    if _int_or_default(log_info.get("user_id"), 0) != user_id:
        return api_error("不存在！")
    if _int_or_default(log_info.get("status"), 0) == 1:
        return api_error("该预约已核销，不能取消！")
    if _int_or_default(log_info.get("status"), 0) == 2:
        return api_error("该预约已取消，无需重复操作！")

    now_ts = int(time.time())
    try:
        execute(
            "UPDATE tp_ticket_appt_log SET status=%s, cancel_time=%s WHERE id=%s",
            (2, now_ts, log_id),
        )
        datetime_info = fetch_one(
            "SELECT id, stock FROM tp_ticket_appt_datetime "
            "WHERE seller_id=%s AND date=%s AND time_start<=%s AND time_end>%s LIMIT 1",
            (
                _int_or_default(log_info.get("seller_id"), 0),
                _appt_date_text(log_info.get("date")),
                _int_or_default(log_info.get("time_end"), 0),
                _int_or_default(log_info.get("time_start"), 0),
            ),
        )
        if datetime_info:
            execute(
                "UPDATE tp_ticket_appt_datetime SET stock=%s WHERE id=%s",
                (
                    _int_or_default(datetime_info.get("stock"), 0) + _int_or_default(log_info.get("number"), 0),
                    _int_or_default(datetime_info.get("id"), 0),
                ),
            )
        execute(
            "UPDATE tp_ticket_appt_log_tourist SET status=%s WHERE log_id=%s AND status=%s",
            (2, log_id, 0),
        )
    except Exception:
        return api_error("操作失败！")

    return api_success([], "操作成功！")


@fastapi_app.api_route("/api/coupon/index", methods=["GET", "POST"])
async def api_coupon_index(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("userid"), 0)
    page = _int_or_default(params.get("page"), 1)
    limit = _int_or_default(params.get("limit"), 10)
    tag = _int_or_default(params.get("tag"), 0)
    class_id = _int_or_default(params.get("class_id"), 0)
    use_store = _int_or_default(params.get("use_store"), 0)
    issue_rows = _coupon_issue_rows(
        uid=uid,
        page=page,
        limit=limit,
        tag=tag,
        class_id=class_id,
        use_store=use_store,
    )
    out = _coupon_class_with_list(issue_rows=issue_rows, max_per_class=4)
    return api_success(out, "查询成功")


@fastapi_app.api_route("/api/coupon/tempApi", methods=["GET", "POST"])
async def api_coupon_temp_api(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("userid"), 0)
    if uid <= 0:
        return api_error("用户不能为空！")
    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    page = _int_or_default(params.get("page"), 1)
    limit = _int_or_default(params.get("limit"), 10)
    tag = _int_or_default(params.get("tag"), 0)
    class_id = _int_or_default(params.get("class_id"), 0)
    use_store = _int_or_default(params.get("use_store"), 0)
    issue_rows = _coupon_issue_rows(
        uid=uid,
        page=page,
        limit=limit,
        tag=tag,
        class_id=class_id,
        use_store=use_store,
    )
    out = _coupon_class_with_list(issue_rows=issue_rows, max_per_class=None)
    return api_success(out, "查询成功")


@fastapi_app.api_route("/api/coupon/getIssueCouponList", methods=["GET", "POST"])
async def api_coupon_get_issue_coupon_list(request: Request) -> Response:
    params = await get_params(request)
    mainline_enabled = _allow_route_mainline(request, "REWRITE_ENABLE_COUPON_GET_ISSUE_LIST")
    if not params:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("参数错误")
    if not mainline_enabled:
        return _legacy_upload_error_response()
    uid = _int_or_default(params.get("uid"), _int_or_default(params.get("userid"), 0))
    if uid <= 0:
        return api_error("参数错误")
    page = _int_or_default(params.get("page"), 1)
    limit = _int_or_default(params.get("limit"), 10)
    tag = _int_or_default(params.get("tag"), 0)
    class_id = _int_or_default(params.get("class_id"), 0)
    use_store = _int_or_default(params.get("use_store"), 0)
    issue_rows = _coupon_issue_rows(
        uid=uid,
        page=page,
        limit=limit,
        tag=tag,
        class_id=class_id,
        use_store=use_store,
    )
    return api_success({"list": issue_rows}, "")


@fastapi_app.api_route("/api/coupon/detail", methods=["GET", "POST"])
async def api_coupon_detail(request: Request) -> JSONResponse:
    params = await get_params(request)
    coupon_id = _int_or_default(params.get("couponId"), 0)
    uid = _int_or_default(params.get("userid"), 0)
    if coupon_id <= 0:
        return api_error("请求错误")

    coupon = fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (coupon_id,))
    if not coupon:
        return api_error("请求错误")
    coupon_class = fetch_one(
        "SELECT id, title, class_icon FROM tp_coupon_class WHERE id=%s LIMIT 1",
        (_int_or_default(coupon.get("cid"), 0),),
    )
    if not coupon_class:
        return api_error("请求错误")
    coupon["couponClass"] = coupon_class

    if _int_or_default(coupon.get("limit_time"), 0) == 0:
        coupon["tips"] = "领取时间：不限时"
    else:
        coupon["tips"] = (
            "领取时间："
            + datetime.fromtimestamp(_int_or_default(coupon.get("start_time"), 0), tz=_TZ_SHANGHAI).strftime(
                "%Y年%m月%d日 %H:%M"
            )
            + "至"
            + datetime.fromtimestamp(_int_or_default(coupon.get("end_time"), 0), tz=_TZ_SHANGHAI).strftime(
                "%m月%d日 %H:%M"
            )
        )

    is_permanent = _int_or_default(coupon.get("is_permanent"), 0)
    if is_permanent == 1:
        coupon["tips_extend"] = "有效期：永久有效"
    elif is_permanent == 2:
        coupon["tips_extend"] = (
            "有效期：消费券需在"
            + datetime.fromtimestamp(
                _int_or_default(coupon.get("coupon_time_end"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y年%m月%d日 %H:%M")
            + "前使用"
        )
    elif is_permanent == 3:
        coupon["tips_extend"] = f"有效期：自领取之日起{_int_or_default(coupon.get('day'), 0)}日内有效"

    coupon["receive_condition"] = ""
    coupon["user_record_data"] = ""
    coupon["can_receive"] = True if uid >= 0 else False
    return api_success(coupon, "查询成功")


def _coupon_applicable_where(coupon: dict[str, Any], keyword: str = "") -> tuple[str, list[Any]]:
    where = ["status = 1"]
    params: list[Any] = []
    if keyword:
        where.append("nickname LIKE %s")
        params.append(f"%{keyword}%")

    use_store = _int_or_default(coupon.get("use_store"), 1)
    use_store_ids = _string_or_empty(coupon.get("use_stroe_id"))
    if use_store == 1:
        pass
    elif use_store in {2, 3, 4, 5, 6, 7} and not use_store_ids:
        where.append("class_id = %s")
        params.append(use_store)
    else:
        seller_ids = [int(x) for x in use_store_ids.split(",") if str(x).strip().isdigit()]
        if not seller_ids:
            where.append("1 = 0")
        else:
            placeholders = ",".join(["%s"] * len(seller_ids))
            where.append(f"id IN ({placeholders})")
            params.extend(seller_ids)
    return " AND ".join(where), params


@fastapi_app.api_route("/api/coupon/applicabletoV2", methods=["GET", "POST"])
async def api_coupon_applicableto_v2(request: Request) -> JSONResponse:
    params = await get_params(request)
    coupon_id = _int_or_default(params.get("id"), 0)
    if coupon_id <= 0:
        return api_error("消费券ID错误")
    coupon = fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (coupon_id,))
    if not coupon:
        return api_error("未找到消费券信息")
    coupon_class = fetch_one(
        "SELECT id FROM tp_coupon_class WHERE id=%s LIMIT 1",
        (_int_or_default(coupon.get("cid"), 0),),
    )
    if not coupon_class:
        return api_error("未找到消费券信息")

    latitude_raw = params.get("latitude")
    longitude_raw = params.get("longitude")
    if latitude_raw in {None, ""} or longitude_raw in {None, ""}:
        return api_error("经纬度异常")
    latitude = _float_or_default(latitude_raw, 1.0)
    longitude = _float_or_default(longitude_raw, 1.0)

    where_sql, where_params = _coupon_applicable_where(coupon, "")
    rows = fetch_all(
        "SELECT id, status, nickname, image, mobile, do_business_time, address, content, longitude, latitude, class_id, distance "
        "FROM ("
        "SELECT *, ROUND((2 * 6378.137 * ASIN(SQRT("
        "POW(SIN(PI() * (%s - latitude) / 360), 2) + "
        "COS(PI() * 29.504164 / 180) * COS(%s * PI() / 180) * POW(SIN(PI() * (%s - longitude) / 360), 2)"
        "))) * 1000) AS distance "
        "FROM tp_seller"
        ") a "
        "WHERE "
        + where_sql
        + " ORDER BY distance ASC LIMIT 1",
        tuple([latitude, latitude, longitude] + where_params),
    )
    for row in rows:
        row["distance"] = 0 if latitude == 1 else _float_or_default(row.get("distance"), 0.0) / 1000
    return api_success(rows, "请求成功")


@fastapi_app.api_route("/api/coupon/applicableto", methods=["GET", "POST"])
async def api_coupon_applicableto(request: Request) -> JSONResponse:
    params = await get_params(request)
    coupon_id = _int_or_default(params.get("id"), 0)
    if coupon_id <= 0:
        return api_error("消费券ID错误")
    coupon = fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (coupon_id,))
    if not coupon:
        return api_error("未找到消费券信息")
    coupon_class = fetch_one(
        "SELECT id FROM tp_coupon_class WHERE id=%s LIMIT 1",
        (_int_or_default(coupon.get("cid"), 0),),
    )
    if not coupon_class:
        return api_error("未找到消费券信息")

    latitude_raw = params.get("latitude")
    longitude_raw = params.get("longitude")
    if latitude_raw in {None, ""} or longitude_raw in {None, ""}:
        return api_error("经纬度异常")
    if params.get("page") in {None, ""} or params.get("limit") in {None, ""}:
        return api_error("分页参数异常")

    latitude = _float_or_default(latitude_raw, 1.0)
    longitude = _float_or_default(longitude_raw, 1.0)
    page = _int_or_default(params.get("page"), 0)
    limit = max(1, _int_or_default(params.get("limit"), 10))
    keyword = _string_or_empty(params.get("keyword"))

    where_sql, where_params = _coupon_applicable_where(coupon, keyword)
    rows = fetch_all(
        "SELECT id, status, nickname, image, mobile, do_business_time, address, content, longitude, latitude, class_id, distance "
        "FROM ("
        "SELECT *, ROUND((2 * 6378.137 * ASIN(SQRT("
        "POW(SIN(PI() * (%s - latitude) / 360), 2) + "
        "COS(PI() * 29.504164 / 180) * COS(%s * PI() / 180) * POW(SIN(PI() * (%s - longitude) / 360), 2)"
        "))) * 1000) AS distance "
        "FROM tp_seller"
        ") a "
        "WHERE "
        + where_sql
        + " ORDER BY distance ASC LIMIT %s, %s",
        tuple([latitude, latitude, longitude] + where_params + [page, limit]),
    )
    for row in rows:
        row["distance"] = 0 if latitude == 1 else _float_or_default(row.get("distance"), 0.0) / 1000
    return api_success(rows, "请求成功")


@fastapi_app.api_route("/api/coupon/list", methods=["GET", "POST"])
async def api_coupon_list(request: Request) -> JSONResponse:
    params = await get_params(request)
    cid = _int_or_default(params.get("cid"), 0)
    uid = _int_or_default(params.get("userid"), 0)
    if cid <= 0:
        return api_error("参数错误！")

    page = max(1, _int_or_default(params.get("page"), 1))
    limit = max(1, _int_or_default(params.get("limit"), 10))
    offset = (page - 1) * limit
    rows = fetch_all(
        "SELECT * FROM tp_coupon_issue "
        "WHERE status=1 AND is_del=0 AND receive_type=1 AND coupon_type<>3 AND cid=%s "
        "ORDER BY sort DESC, id DESC LIMIT %s, %s",
        (cid, offset, limit),
    )
    issue_ids = [int(r.get("id") or 0) for r in rows if int(r.get("id") or 0) > 0]
    used_ids: set[int] = set()
    if uid > 0 and issue_ids:
        placeholders = ",".join(["%s"] * len(issue_ids))
        used_rows = fetch_all(
            f"SELECT issue_coupon_id FROM tp_coupon_issue_user WHERE uid=%s AND issue_coupon_id IN ({placeholders})",
            tuple([uid] + issue_ids),
        )
        used_ids = {int(r.get("issue_coupon_id") or 0) for r in used_rows}

    out: list[dict[str, Any]] = []
    for row in rows:
        item = dict(row)
        item["coupon_price"] = _float_or_default(item.get("coupon_price"), 0.0)
        item["use_min_price"] = _float_or_default(item.get("use_min_price"), 0.0)
        item["is_use"] = int(item.get("id") or 0) in used_ids
        if _int_or_default(item.get("coupon_time_end"), 0) > 0:
            item["coupon_time_start"] = datetime.fromtimestamp(
                _int_or_default(item.get("coupon_time_start"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y/%m/%d")
            item["coupon_time_end"] = datetime.fromtimestamp(
                _int_or_default(item.get("coupon_time_end"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y/%m/%d")
        if _int_or_default(item.get("start_time"), 0) > 0:
            item["start_time"] = datetime.fromtimestamp(
                _int_or_default(item.get("start_time"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y/%m/%d")
            item["end_time"] = datetime.fromtimestamp(
                _int_or_default(item.get("end_time"), 0), tz=_TZ_SHANGHAI
            ).strftime("%Y/%m/%d")
        out.append(item)
    return api_success(out, "查询成功")


@fastapi_app.api_route("/api/coupon/line_list", methods=["GET", "POST"])
async def api_coupon_line_list(request: Request) -> JSONResponse:
    params = await get_params(request)
    coupon_id = _int_or_default(params.get("couponId"), 0)
    flag = _int_or_default(params.get("flag"), 0)
    limit = max(1, _int_or_default(params.get("limit"), 10))
    page = max(1, _int_or_default(params.get("page"), 1))
    if coupon_id <= 0:
        return api_error("参数错误")

    where = ["status = 1", "tourism_status = 1", "delete_time = 0"]
    where_args: list[Any] = []
    if flag == 1:
        coupon = fetch_one("SELECT class_id, type, category_id, product_id FROM tp_coupon_issue WHERE id=%s LIMIT 1", (coupon_id,))
        if coupon and _int_or_default(coupon.get("class_id"), 0) == 3:
            coupon_type = _int_or_default(coupon.get("type"), 0)
            if coupon_type == 1:
                where.append("flag = %s")
                where_args.append(1)
            elif coupon_type == 2:
                where.append("category_id = %s")
                where_args.append(_int_or_default(coupon.get("category_id"), 0))
            elif coupon_type == 3:
                product_ids = [int(x) for x in _string_or_empty(coupon.get("product_id")).split(",") if x.strip().isdigit()]
                if product_ids:
                    placeholders = ",".join(["%s"] * len(product_ids))
                    where.append(f"id IN ({placeholders})")
                    where_args.extend(product_ids)
                else:
                    where.append("1 = 0")
        else:
            where.append("flag = %s")
            where_args.append(flag)
    else:
        where.append("flag = %s")
        where_args.append(flag)
        if flag == 2:
            where.append("mid = %s")
            where_args.append(coupon_id)

    offset = (page - 1) * limit
    rows = fetch_all(
        "SELECT * FROM tp_line WHERE "
        + " AND ".join(where)
        + " ORDER BY access_count DESC LIMIT %s, %s",
        tuple(where_args + [offset, limit]),
    )
    return api_success(rows, "查询成功")


@fastapi_app.api_route("/api/coupon/line_detail", methods=["GET", "POST"])
async def api_coupon_line_detail(request: Request) -> JSONResponse:
    params = await get_params(request)
    line_id = _int_or_default(params.get("line_id"), 0)
    if line_id <= 0:
        return api_error("参数错误")

    row = fetch_one(
        "SELECT l.*, c.title AS line_category_title "
        "FROM tp_line l "
        "LEFT JOIN tp_line_category c ON c.id = l.category_id "
        "WHERE l.id=%s LIMIT 1",
        (line_id,),
    )
    if row:
        execute("UPDATE tp_line SET access_count = access_count + 1 WHERE id=%s", (line_id,))
    return api_success(row, "查询成功")


@fastapi_app.api_route("/api/coupon/getUserCouponRecordList", methods=["GET", "POST"])
async def api_coupon_get_user_coupon_record_list(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("userid"), 0)
    coupon_id = _int_or_default(params.get("couponId"), 0)

    coupon = fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (coupon_id,))
    if not coupon:
        return api_error("请求错误")
    coupon_class = fetch_one(
        "SELECT id FROM tp_coupon_class WHERE id=%s LIMIT 1",
        (_int_or_default(coupon.get("cid"), 0),),
    )
    if not coupon_class:
        return api_error("请求错误")
    if uid <= 0:
        return api_error("请求错误")

    rows = fetch_all(
        "SELECT id, seller_id, class_id, create_time FROM tp_seller_mark_qc_user_record "
        "WHERE uid=%s AND coupon_id=%s ORDER BY id DESC",
        (uid, coupon_id),
    )
    seller_ids = sorted({int(r.get("seller_id") or 0) for r in rows if int(r.get("seller_id") or 0) > 0})
    seller_map: dict[int, dict[str, Any]] = {}
    if seller_ids:
        placeholders = ",".join(["%s"] * len(seller_ids))
        seller_rows = fetch_all(
            f"SELECT id, nickname, image FROM tp_seller WHERE id IN ({placeholders})",
            tuple(seller_ids),
        )
        seller_map = {int(r.get("id") or 0): r for r in seller_rows}
    for row in rows:
        sid = _int_or_default(row.get("seller_id"), 0)
        seller = seller_map.get(sid) or {}
        row["seller"] = {
            "id": sid,
            "nickname": _string_or_empty(seller.get("nickname")),
            "image": _string_or_empty(seller.get("image")),
        }
    return api_success(rows, "查询成功")


@fastapi_app.api_route("/api/coupon/couponissueuser", methods=["GET", "POST"])
async def api_coupon_couponissueuser(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")
    params = await get_params(request)
    userid = _int_or_default(params.get("userid"), 0)
    issue_user_id = _int_or_default(params.get("id"), 0)
    if userid <= 0:
        return api_error("没有登录")
    if issue_user_id <= 0:
        return api_error("领取信息错误")

    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (userid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    data = fetch_one("SELECT * FROM tp_coupon_issue_user WHERE id=%s LIMIT 1", (issue_user_id,))
    if not data:
        return api_error("数据异常")
    return api_success(data, "查询成功")


@fastapi_app.api_route("/api/coupon/idtocoupon", methods=["GET", "POST"])
async def api_coupon_idtocoupon(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")
    params = await get_params(request)
    cuid = _int_or_default(params.get("cuid"), 0)
    if cuid <= 0:
        return api_error("参数异常")

    issue = fetch_one("SELECT * FROM tp_coupon_issue_user WHERE id=%s LIMIT 1", (cuid,))
    if not issue:
        return api_error("记录不存在")

    coupon = fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (_int_or_default(issue.get("issue_coupon_id"), 0),))
    if not coupon:
        return api_error("消费券信息不存在")

    writeoff = fetch_one(
        "SELECT w.*, u.nickname AS users_nickname, u.headimgurl AS users_headimgurl "
        "FROM tp_write_off w "
        "LEFT JOIN tp_users u ON u.id = w.userid "
        "WHERE w.coupon_issue_user_id=%s LIMIT 1",
        (cuid,),
    )
    coupon["writeoff"] = writeoff
    if _int_or_default(coupon.get("use_type"), 0) == 1:
        coupon["delivery"] = {
            "delivery_user": _string_or_empty(issue.get("delivery_user")),
            "delivery_phone": _string_or_empty(issue.get("delivery_phone")),
            "delivery_address": _string_or_empty(issue.get("delivery_address")),
            "tracking_number": _string_or_empty(issue.get("tracking_number")),
        }
    return api_success(coupon, "查询成功")


@fastapi_app.api_route("/api/coupon/encryptAES", methods=["GET", "POST"])
async def api_coupon_encrypt_aes(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")
    params = await get_params(request)
    issue_user_id = _int_or_default(params.get("id"), 0)
    salt = _string_or_empty(params.get("salt"))
    uid = _int_or_default(params.get("uid"), 0)
    if issue_user_id <= 0 or not salt:
        return api_error("参数异常")

    issue = fetch_one(
        "SELECT * FROM tp_coupon_issue_user WHERE id=%s AND enstr_salt=%s AND uid=%s LIMIT 1",
        (issue_user_id, salt, uid),
    )
    if not issue:
        return api_error("数据异常，禁止访问")

    writeoff = fetch_one(
        "SELECT id FROM tp_write_off WHERE coupon_issue_user_id=%s LIMIT 1",
        (issue_user_id,),
    )
    write_off_status = 1 if writeoff else 0

    user = fetch_one("SELECT name, idcard FROM tp_users WHERE id=%s LIMIT 1", (uid,)) or {}
    uinfo = {
        "name": _mask_name(_string_or_empty(user.get("name"))),
        "idcard": _mask_idcard(_string_or_empty(user.get("idcard"))),
    }

    now_ts = int(time.time())
    system = fetch_one("SELECT is_qrcode_number FROM tp_system WHERE id=%s LIMIT 1", (1,)) or {}
    expire_window = max(1, _int_or_default(system.get("is_qrcode_number"), 300))
    code_time_expire = _int_or_default(issue.get("code_time_expire"), 0)

    if code_time_expire < now_ts:
        qrcode_url = f"{_string_or_empty(issue.get('enstr_salt'))}:{issue_user_id}:{now_ts}"
        execute(
            "UPDATE tp_coupon_issue_user SET qrcode_url=%s, code_time_create=%s, code_time_expire=%s WHERE id=%s",
            (qrcode_url, now_ts, now_ts + expire_window, issue_user_id),
        )
        return api_success(
            {
                "id": issue_user_id,
                "write_off_status": write_off_status,
                "qrcode_url": qrcode_url,
                "uinfo": uinfo,
            },
            "success",
        )

    return api_success(
        {
            "id": issue_user_id,
            "write_off_status": write_off_status,
            "qrcode_url": _string_or_empty(issue.get("qrcode_url")),
            "uinfo": uinfo,
        },
        "success",
    )


@fastapi_app.api_route("/api/coupon/receive", methods=["GET", "POST"])
async def api_coupon_receive(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问")

    params = await get_params(request)
    mainline_enabled = _allow_route_mainline(request)
    userid_raw = _string_or_empty(params.get("userid"))
    coupon_id_raw = _string_or_empty(params.get("couponId"))
    if not userid_raw or not coupon_id_raw:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("参数错误")
    if not userid_raw.isdigit() or not coupon_id_raw.isdigit():
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("参数错误")
    userid = _int_or_default(userid_raw, 0)
    coupon_id = _int_or_default(coupon_id_raw, 0)
    if coupon_id <= 0 or userid <= 0:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("参数错误")

    user = fetch_one("SELECT id, status, auth_status FROM tp_users WHERE id=%s LIMIT 1", (userid,))
    if not user:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("用户不存在")
    if _int_or_default(user.get("auth_status"), 0) != 1:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("当前用户未实名")

    issue = fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (coupon_id,))
    if not issue:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("请求错误")
    issue_class = fetch_one(
        "SELECT id FROM tp_coupon_class WHERE id=%s LIMIT 1",
        (_int_or_default(issue.get("cid"), 0),),
    )
    if not issue_class:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("请求错误")

    if _int_or_default(issue.get("coupon_type"), 0) == 3:
        return api_error("禁止领取团体券")
    if _int_or_default(issue.get("is_get"), 0) == 0:
        tips = _string_or_empty(issue.get("tips")) or "当前消费券暂不可领取"
        return api_error(tips)
    if _int_or_default(issue.get("status"), 0) == 0:
        return api_error("未开启消费券")
    if _int_or_default(issue.get("status"), 0) == -1:
        return api_error("无效消费券")
    if _int_or_default(issue.get("is_del"), 0) == 1:
        return api_error("当前消费券已被删除")
    if _int_or_default(issue.get("status"), 0) == 2:
        return api_error("已领完")
    if _int_or_default(issue.get("remain_count"), 0) <= 0:
        return api_error("库存不足,稍后再试")
    if _int_or_default(issue.get("limit_time"), 0) == 1:
        now_ts = int(time.time())
        if _int_or_default(issue.get("start_time"), 0) > now_ts:
            return api_error("活动未开启")
        if _int_or_default(issue.get("end_time"), 0) < now_ts:
            return api_error("已过领取时间")

    received = fetch_one(
        "SELECT id FROM tp_coupon_issue_user WHERE uid=%s AND issue_coupon_id=%s LIMIT 1",
        (userid, coupon_id),
    )
    if received:
        return api_error("已领取过该优惠劵!", code=3, data="")

    now_ts = int(time.time())
    is_permanent = _int_or_default(issue.get("is_permanent"), 0)
    if is_permanent == 1:
        expire_time = 4070880000
    elif is_permanent == 2:
        expire_time = _int_or_default(issue.get("coupon_time_end"), now_ts)
    elif is_permanent == 3:
        expire_time = now_ts + max(0, _int_or_default(issue.get("day"), 0)) * 86400
    else:
        expire_time = now_ts

    try:
        execute(
            "INSERT INTO tp_coupon_issue_user "
            "(create_time, update_time, uid, issue_coupon_id, coupon_title, coupon_price, use_min_price, coupon_create_time, "
            "time_start, time_end, status, is_fail, is_limit_total, issue_coupon_class_id, enstr_salt, qrcode_url, "
            "code_time_create, code_time_expire, ips, longitude, latitude, expire_time) "
            "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            (
                now_ts,
                now_ts,
                userid,
                coupon_id,
                _string_or_empty(issue.get("coupon_title")),
                _decimal_or_default(issue.get("coupon_price"), "0"),
                _decimal_or_default(issue.get("use_min_price"), "0"),
                _int_or_default(issue.get("create_time"), 0),
                _int_or_default(issue.get("start_time"), 0),
                _int_or_default(issue.get("end_time"), 0),
                0,
                "1",
                _int_or_default(issue.get("is_limit_total"), 0),
                _int_or_default(issue.get("cid"), 0),
                _next_order_no(),
                "",
                0,
                0,
                request.client.host if request.client else "",
                _string_or_empty(params.get("longitude")),
                _string_or_empty(params.get("latitude")),
                expire_time,
            ),
        )
        updated = execute(
            "UPDATE tp_coupon_issue "
            "SET remain_count = remain_count - 1, provide_count = provide_count + 1, update_time=%s "
            "WHERE id=%s AND remain_count > 0",
            (now_ts, coupon_id),
        )
        if updated <= 0:
            return api_error("领取失败")
    except Exception:
        return api_error("领取失败")

    return api_success([], "领取成功")


@fastapi_app.api_route("/api/coupon/writeoff", methods=["GET", "POST"])
async def api_coupon_writeoff(request: Request) -> Response:
    if request.method.upper() != "POST":
        return api_error("禁止访问")

    params = await get_params(request)
    if not params:
        return _legacy_upload_error_response()
    userid = _int_or_default(params.get("userid"), 0)
    mid = _int_or_default(params.get("mid"), 0)
    coupon_issue_user_id = _int_or_default(params.get("coupon_issue_user_id"), 0)
    use_min_price = _decimal_or_default(params.get("use_min_price"), "0")
    orderid = _int_or_default(params.get("orderid"), 0)
    qrcode_url = _string_or_empty(params.get("qrcode_url"))
    longitude = _float_or_default(params.get("longitude"), 1.0)
    latitude = _float_or_default(params.get("latitude"), 1.0)

    if userid <= 0:
        return api_error("没有登录")
    if mid <= 0:
        return api_error("商户信息错误")
    if coupon_issue_user_id <= 0:
        return api_error("消费券不存在")
    if latitude == 1 and longitude == 1:
        return api_error("当前游客未开启定位")

    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (userid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    verifier = fetch_one(
        "SELECT id, status FROM tp_merchant_verifier WHERE uid=%s AND mid=%s AND type=%s LIMIT 1",
        (userid, mid, "coupon"),
    )
    if not verifier:
        return api_error("核验人不存在")
    if _int_or_default(verifier.get("status"), 0) != 1:
        return api_error("当前核验人已被禁用")

    seller = fetch_one("SELECT id, status, class_id FROM tp_seller WHERE id=%s LIMIT 1", (mid,))
    if not seller:
        return api_error("商户不存在")
    if _int_or_default(seller.get("status"), 0) != 1:
        return api_error("商户已被禁用")

    issue_user = fetch_one("SELECT * FROM tp_coupon_issue_user WHERE id=%s LIMIT 1", (coupon_issue_user_id,))
    if not issue_user:
        return api_error("该核销码异常")
    if _int_or_default(issue_user.get("status"), 0) == 1:
        return api_error("该消费券已使用")
    if _int_or_default(issue_user.get("status"), 0) == 2:
        return api_error("该消费券已过期")
    if _string_or_empty(issue_user.get("is_fail")) == "0":
        return api_error("该消费券无效")
    if _int_or_default(issue_user.get("uid"), 0) == userid:
        return api_error("核销异常")
    if qrcode_url and _string_or_empty(issue_user.get("qrcode_url")) and _string_or_empty(issue_user.get("qrcode_url")) != qrcode_url:
        return api_error("二维码已失效")
    if _int_or_default(issue_user.get("code_time_expire"), 0) > 0 and _int_or_default(issue_user.get("code_time_expire"), 0) < int(time.time()):
        return api_error("二维码已过期")

    issue = fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (_int_or_default(issue_user.get("issue_coupon_id"), 0),))
    if not issue:
        return api_error("该消费券已无法使用")
    if _int_or_default(issue.get("is_threshold"), 0) == 1 and _decimal_or_default(issue.get("use_min_price"), "0") <= use_min_price:
        return api_error(f"最低消费需要满{_decimal_or_default(issue.get('use_min_price'), '0')}才可使用")
    if _int_or_default(issue.get("use_store"), 1) != 1:
        if _int_or_default(seller.get("class_id"), 0) != _int_or_default(issue.get("use_store"), 1):
            return api_error("该消费券无法在该门店类型下使用")
        use_store_ids = [int(x) for x in _string_or_empty(issue.get("use_stroe_id")).split(",") if x.strip().isdigit()]
        if use_store_ids and mid not in use_store_ids:
            return api_error("该消费券无法在该门店下使用")

    now_ts = int(time.time())
    try:
        execute(
            "INSERT INTO tp_write_off "
            "(create_time, update_time, coupon_issue_user_id, mid, userid, orderid, coupon_title, coupon_price, use_min_price, "
            "time_start, time_end, qrcode_url, uuno, coupon_issue_id, uw_longitude, uw_latitude, uid, enstr_salt) "
            "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            (
                now_ts,
                now_ts,
                coupon_issue_user_id,
                mid,
                userid,
                orderid,
                _string_or_empty(issue_user.get("coupon_title")),
                _decimal_or_default(issue_user.get("coupon_price"), "0"),
                _decimal_or_default(issue_user.get("use_min_price"), "0"),
                _int_or_default(issue_user.get("time_start"), 0),
                _int_or_default(issue_user.get("time_end"), 0),
                _string_or_empty(issue_user.get("qrcode_url")),
                _string_or_empty(issue.get("uuno")),
                _int_or_default(issue.get("id"), 0),
                longitude,
                latitude,
                _int_or_default(issue_user.get("uid"), 0),
                _next_order_no(),
            ),
        )
        execute(
            "UPDATE tp_coupon_issue_user SET is_fail=%s, status=%s, time_use=%s WHERE id=%s",
            ("0", 1, now_ts, coupon_issue_user_id),
        )
    except Exception as e:
        return api_success(str(e), "核销失败")

    return api_success("data success", "核销成功")


@fastapi_app.api_route("/api/coupon/writeofflog", methods=["GET", "POST"])
async def api_coupon_writeofflog(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")

    params = await get_params(request)
    userid = _int_or_default(params.get("userid"), 0)
    mid = _int_or_default(params.get("mid"), 0)
    page = max(1, _int_or_default(params.get("page"), 1))
    limit = max(1, _int_or_default(params.get("limit"), 10))
    if userid <= 0:
        return api_error("没有登录")
    if mid <= 0:
        return api_error("商户信息错误")

    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (userid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    seller = fetch_one("SELECT id, status FROM tp_seller WHERE id=%s LIMIT 1", (mid,))
    if not seller:
        return api_error("商户不存在")
    if _int_or_default(seller.get("status"), 0) != 1:
        return api_error("商户已被禁用")

    offset = (page - 1) * limit
    rows = fetch_all(
        "SELECT * FROM tp_write_off WHERE userid=%s AND mid=%s ORDER BY sort DESC, id DESC LIMIT %s, %s",
        (userid, mid, offset, limit),
    )
    return api_success(rows, "查询成功")


@fastapi_app.api_route("/api/coupon/writeoffdetail", methods=["GET", "POST"])
async def api_coupon_writeoffdetail(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")

    params = await get_params(request)
    userid = _int_or_default(params.get("userid"), 0)
    mid = _int_or_default(params.get("mid"), 0)
    writeoff_id = _int_or_default(params.get("id"), 0)
    if userid <= 0:
        return api_error("没有登录")
    if mid <= 0:
        return api_error("商户信息错误")

    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (userid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    seller = fetch_one("SELECT id, status FROM tp_seller WHERE id=%s LIMIT 1", (mid,))
    if not seller:
        return api_error("商户不存在")
    if _int_or_default(seller.get("status"), 0) != 1:
        return api_error("商户已被禁用")

    data = fetch_one("SELECT * FROM tp_write_off WHERE id=%s LIMIT 1", (writeoff_id,))
    if not data:
        return api_error("数据异常")
    return api_success(data, "查询成功")


@fastapi_app.api_route("/api/coupon/tourwriteofflog", methods=["GET", "POST"])
async def api_coupon_tourwriteofflog(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")

    params = await get_params(request)
    userid = _int_or_default(params.get("userid"), 0)
    mid = _int_or_default(params.get("mid"), 0)
    page = max(1, _int_or_default(params.get("page"), 1))
    limit = max(1, _int_or_default(params.get("limit"), 10))
    if userid <= 0:
        return api_error("没有登录")
    if mid <= 0:
        return api_error("商户信息错误")

    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (userid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    seller = fetch_one("SELECT id, status FROM tp_seller WHERE id=%s LIMIT 1", (mid,))
    if not seller:
        return api_error("商户不存在")
    if _int_or_default(seller.get("status"), 0) != 1:
        return api_error("商户已被禁用")

    offset = (page - 1) * limit
    rows = fetch_all(
        "SELECT * FROM tp_tour_write_off WHERE userid=%s AND mid=%s ORDER BY sort DESC, id DESC LIMIT %s, %s",
        (userid, mid, offset, limit),
    )
    return api_success(rows, "查询成功")


@fastapi_app.post("/api/index/note_list")
def api_index_note_list() -> JSONResponse:
    note = fetch_all("SELECT * FROM tp_notice WHERE status=%s ORDER BY create_time DESC", (1,))
    return api_success(note, "请求成功")


@fastapi_app.api_route("/api/index/note_detail", methods=["GET", "POST"])
async def api_index_note_detail(request: Request) -> JSONResponse:
    params = await get_params(request)
    note_id = params.get("id")

    if note_id is None or note_id == "" or str(note_id) == "0" or isinstance(note_id, int):
        return api_error("参数错误")

    row = fetch_one("SELECT * FROM tp_notice WHERE id=%s AND status=%s", (note_id, 1))
    execute("UPDATE tp_notice SET hits = hits + 1 WHERE id=%s", (note_id,))
    return api_success(row, "请求成功")


@fastapi_app.post("/api/seller/cate")
def api_seller_cate() -> JSONResponse:
    all_cate = [{"id": 1, "class_name": "全部"}]
    seller_class = fetch_all("SELECT * FROM tp_seller_class WHERE status=%s", (1,))
    slide = fetch_all("SELECT * FROM tp_slide WHERE status=%s AND tags=%s", (1, "list"))
    return api_success({"cate": all_cate + seller_class, "slide": slide}, "请求成功")


def _is_empty_php(value: Any) -> bool:
    if value is None:
        return True
    if value is False:
        return True
    if value == 0:
        return True
    if value == "0":
        return True
    if value == "":
        return True
    if isinstance(value, (list, dict, str, tuple, set)) and len(value) == 0:
        return True
    return False


@fastapi_app.post("/api/index/transform")
async def api_index_transform(request: Request) -> JSONResponse:
    params = await get_params(request)
    longitude = params.get("longitude")
    latitude = params.get("latitude")
    if _is_empty_php(longitude) or _is_empty_php(latitude):
        return api_error("参数错误")

    import json
    import urllib.error
    import urllib.parse
    import urllib.request

    key = "OYABZ-TUDOW-3ENR7-RX4YQ-IGXUJ-QKFCP"
    loc = f"{latitude},{longitude}"
    url = (
        "https://apis.map.qq.com/ws/geocoder/v1/?"
        + urllib.parse.urlencode({"location": loc, "key": key, "get_poi": "0"})
    )

    try:
        with urllib.request.urlopen(url, timeout=5) as resp:
            raw = resp.read()
    except urllib.error.URLError as e:
        return api_error(str(getattr(e, "reason", "")) or "请求失败")

    try:
        info = json.loads(raw.decode("utf-8"))
    except Exception:
        return api_error("请求失败")

    if not isinstance(info, dict):
        return api_error("请求失败")

    if int(info.get("status") or 0) != 0:
        return api_error(str(info.get("message") or "请求失败"))

    return api_success(info, "请求成功")


def _system_parse_term_end_ts(term_value: Any, term_end_value: Any) -> int:
    term_text = _string_or_empty(term_value)
    if term_text and " - " in term_text:
        tail = term_text.split(" - ")[-1].strip()
        for fmt in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%d"):
            try:
                return int(datetime.strptime(tail, fmt).replace(tzinfo=_TZ_SHANGHAI).timestamp())
            except Exception:
                continue
    fallback = _int_or_default(term_end_value, 0)
    return fallback if fallback > 0 else 0


@fastapi_app.api_route("/api/system/rollback_remain_count", methods=["GET", "POST"])
async def api_system_rollback_remain_count() -> Response:
    return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.api_route("/api/system/rollback_set_data", methods=["GET", "POST"])
async def api_system_rollback_set_data() -> Response:
    return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.api_route("/api/system/rollback_remain_count_extend", methods=["GET", "POST"])
async def api_system_rollback_remain_count_extend() -> Response:
    return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.api_route("/api/system/set_tour_invalid", methods=["GET", "POST"])
async def api_system_set_tour_invalid() -> Response:
    return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.api_route("/api/system/cleanDb", methods=["GET", "POST"])
async def api_system_clean_db(request: Request) -> Response:
    if not _allow_route_mainline(request):
        return Response(status_code=200, content=b"success", headers={"content-type": "text/html; charset=UTF-8"})
    tables = [
        "tp_accounting",
        "tp_admin_log",
        "tp_audit_record",
        "tp_collection",
        "tp_coupon_issue",
        "tp_coupon_issue_user",
        "tp_feedback",
        "tp_guide",
        "tp_guest",
        "tp_line",
        "tp_line_record",
        "tp_ticket",
        "tp_ticket_class",
        "tp_tour",
        "tp_tour_accounting",
        "tp_tour_audit_record",
        "tp_tour_coupon_group",
        "tp_tour_guest",
        "tp_tour_hotel_sign",
        "tp_tour_hotel_user_record",
        "tp_tour_issue_user",
        "tp_tour_write_off",
        "tp_tourist",
        "tp_verify_accounting_record",
        "tp_verify_collect",
        "tp_write_off",
        "tp_flow_type",
        "tp_flow",
        "tp_tour_hotel",
        "tp_base_paydata",
        "tp_base_refunds",
        "tp_coupon_order",
        "tp_coupon_order_item",
    ]
    allow_clean = _env_enabled("REWRITE_ALLOW_SYSTEM_CLEANDB", False)
    truncated = 0
    if allow_clean:
        for table in tables:
            execute(f"TRUNCATE TABLE {table}")
            truncated += 1
    return api_success(
        {"dry_run": not allow_clean, "tables": len(tables), "truncated": truncated},
        "success",
    )


@fastapi_app.api_route("/api/system/tableTohtml", methods=["GET", "POST"])
async def api_system_table_to_html() -> Response:
    return Response(status_code=200, content=b"success", headers={"content-type": "text/html; charset=UTF-8"})


@fastapi_app.api_route("/api/system/tableTohtml1", methods=["GET", "POST"])
async def api_system_table_to_html1() -> Response:
    return Response(status_code=200, content=b"success", headers={"content-type": "text/html; charset=UTF-8"})


@fastapi_app.api_route("/api/system/queryArea", methods=["GET", "POST"])
async def api_system_query_area(request: Request) -> Response:
    row = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_area_code WHERE city=%s AND district LIKE %s",
        ("", "%县"),
    ) or {}
    count = _int_or_default(row.get("n"), 0)
    if not _allow_route_mainline(request):
        return Response(
            status_code=200,
            content=str(count).encode("utf-8"),
            headers={"content-type": "text/html; charset=utf-8"},
        )
    return api_success({"count": count}, "success")


@fastapi_app.api_route("/api/system/remake", methods=["GET", "POST"])
async def api_system_remake(request: Request) -> JSONResponse:
    params = await get_params(request)
    record_id = _int_or_default(params.get("id"), 0)
    row = fetch_one("SELECT * FROM tp_accounting WHERE id=%s LIMIT 1", (record_id,)) if record_id > 0 else None
    if not row:
        return api_error("当前记录不存在")
    return api_success(row, "重建成功")


@fastapi_app.api_route("/api/system/remake_lxs", methods=["GET", "POST"])
async def api_system_remake_lxs(request: Request) -> JSONResponse:
    params = await get_params(request)
    record_id = _int_or_default(params.get("id"), 0)
    row = fetch_one("SELECT * FROM tp_tour_accounting WHERE id=%s LIMIT 1", (record_id,)) if record_id > 0 else None
    if not row:
        return api_error("当前记录不存在")
    return api_success(row, "重建成功")


@fastapi_app.api_route("/api/system/remake_ticket", methods=["GET", "POST"])
async def api_system_remake_ticket(request: Request) -> JSONResponse:
    params = await get_params(request)
    uuno = _string_or_empty(params.get("uuno"))
    row = fetch_one("SELECT * FROM tp_ticket_settlement WHERE uuno=%s LIMIT 1", (uuno,)) if uuno else None
    if not row:
        return api_error("当前记录不存在")
    return api_success(row, "重建成功")


@fastapi_app.api_route("/api/system/XdataSummary", methods=["GET", "POST"])
async def api_system_xdata_summary(request: Request) -> Response:
    if not _allow_route_mainline(request):
        return _legacy_upload_error_response()
    return api_success({"total": 0, "count": 0}, "请求成功")


@fastapi_app.api_route("/api/system/alert_push", methods=["GET", "POST"])
async def api_system_alert_push(request: Request) -> Response:
    single_row = fetch_one("SELECT COALESCE(SUM(coupon_price),0) AS total, COUNT(*) AS cnt FROM tp_write_off") or {}
    tour_row = fetch_one("SELECT COALESCE(SUM(coupon_price),0) AS total, COUNT(*) AS cnt FROM tp_tour_write_off") or {}
    extend_row = fetch_one(
        "SELECT COALESCE(SUM(t.numbers * i.coupon_price),0) AS total "
        "FROM tp_tour_coupon_group g LEFT JOIN tp_tour t ON g.tid=t.id "
        "LEFT JOIN tp_coupon_issue i ON i.id=g.coupon_issue_id WHERE t.status <> %s",
        (6,),
    ) or {}

    single_total = _decimal_or_default(single_row.get("total"), "0.00")
    single_cnt = _int_or_default(single_row.get("cnt"), 0)
    tour_total = _decimal_or_default(tour_row.get("total"), "0.00")
    tour_cnt = _int_or_default(tour_row.get("cnt"), 0)
    warning_total = single_total + _decimal_or_default(extend_row.get("total"), "0.00")

    mobiles = ["18992207739", "19891237999", "15619123472", "18792683064"]
    if _env_enabled("REWRITE_MOCK_SMS", True):
        now_ts = int(time.time())
        for mobile in mobiles:
            execute(
                "INSERT INTO tp_users_sms_log "
                "(uid, mobile, sms_code, template, create_time, smsid, code, balance, msg, expire_time) "
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                (2, mobile, "999999", "", now_ts, "mock-alert", "0", 9999, "mock ok", now_ts),
            )

    if not _allow_route_mainline(request):
        return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})

    return api_success(
        {
            "single_customer_price": f"{single_total:.2f}",
            "single_customer_count": single_cnt,
            "tour_customer_price": f"{tour_total:.2f}",
            "tour_customer_count": tour_cnt,
            "warning_price": f"{warning_total:.2f}",
        },
        "操作成功",
    )


@fastapi_app.api_route("/api/system/notification", methods=["GET", "POST"])
async def api_system_notification(request: Request) -> Response:
    current_time = int(time.time()) - 3600
    threshold = 20
    rows = fetch_all(
        "SELECT w.mid, s.nickname, COUNT(1) AS cnt FROM tp_write_off w "
        "LEFT JOIN tp_seller s ON w.mid=s.id WHERE w.create_time >= %s "
        "GROUP BY w.mid HAVING COUNT(1) > %s",
        (current_time, threshold),
    )
    if not rows:
        if not _allow_route_mainline(request):
            return Response(status_code=200, content=b"0", headers={"content-type": "text/html; charset=utf-8"})
        return api_success(0, "")

    total = sum(_int_or_default(row.get("cnt"), 0) for row in rows)
    mobiles = ["15619123472", "18792683064"]
    if _env_enabled("REWRITE_MOCK_SMS", True):
        now_ts = int(time.time())
        for mobile in mobiles:
            execute(
                "INSERT INTO tp_users_sms_log "
                "(uid, mobile, sms_code, template, create_time, smsid, code, balance, msg, expire_time) "
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                (3, mobile, "888888", "", now_ts, "mock-notification", "0", 9999, "mock ok", now_ts),
            )

    if not _allow_route_mainline(request):
        return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})

    return api_success({"total": total, "list": rows}, "操作成功")


@fastapi_app.api_route("/api/system/invalid_tour", methods=["GET", "POST"])
async def api_system_invalid_tour(request: Request) -> Response:
    now_ts = int(time.time())
    updated = execute(
        "UPDATE tp_tour SET status=%s, update_time=%s WHERE is_locking=%s AND status<>%s",
        (6, now_ts, 0, 6),
    )
    if not _allow_route_mainline(request):
        return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})
    return api_success({"updated": updated}, "操作成功")


@fastapi_app.post("/api/seller/list")
async def api_seller_list(request: Request) -> JSONResponse:
    params = await get_params(request)

    class_id = params.get("class_id")
    if _is_empty_php(class_id):
        return api_error("请检查商户分类ID是否正确")

    if "latitude" not in params or "longitude" not in params:
        return api_error("参数异常")

    if "page" not in params or "limit" not in params:
        return api_error("参数异常")

    try:
        class_id_int = int(str(class_id))
    except ValueError:
        class_id_int = 0

    try:
        latitude = float(str(params.get("latitude") or 0))
        longitude = float(str(params.get("longitude") or 0))
    except ValueError:
        latitude = 0.0
        longitude = 0.0

    try:
        offset = int(str(params.get("page") or 0))
        limit = int(str(params.get("limit") or 10))
    except ValueError:
        offset = 0
        limit = 10

    where_sql = "status = 1"
    where_params: list[Any] = []
    if class_id_int != 1:
        where_sql += " AND class_id = %s"
        where_params.append(class_id_int)

    sql = """
        SELECT
            id,status,nickname,image,mobile,do_business_time,address,content,longitude,latitude,class_id,distance
        FROM
            (
            SELECT
                *,
                ROUND((
                        2 * 6378.137 * ASIN(
                            SQRT(
                                POW( SIN( PI()*( %s - latitude )/ 360 ), 2 )+
                                COS( PI()* 29.504164 / 180 )* COS( %s * PI()/ 180 )* POW( SIN( PI()*( %s - longitude )/ 360 ), 2 )
                            )
                        )
                ) * 1000
            ) AS distance
            FROM
                tp_seller
            ) a
        WHERE """
    sql += where_sql
    sql += " ORDER BY distance ASC LIMIT %s, %s"

    rows = fetch_all(
        sql,
        tuple([latitude, latitude, longitude] + where_params + [offset, limit]),
    )

    out: list[dict[str, Any]] = []
    for row in rows:
        dist_m = float(row.get("distance") or 0.0)
        row["distance"] = 0 if latitude == 1 else dist_m / 1000
        out.append(row)

    return api_success(out, "请求成功")


@fastapi_app.api_route("/api/seller/detail", methods=["GET", "POST"])
async def api_seller_detail(request: Request) -> JSONResponse:
    params = await get_params(request)
    seller_id = _int_or_default(params.get("seller_id"), 0)
    if seller_id <= 0:
        return api_error("请检查商户ID是否正确")
    if "latitude" not in params or "longitude" not in params:
        return api_error("参数异常")

    latitude = _float_or_default(params.get("latitude"), 1.0)
    longitude = _float_or_default(params.get("longitude"), 1.0)
    rows = fetch_all(
        "SELECT id, status, nickname, image, mobile, do_business_time, address, content, longitude, latitude, class_id, "
        "comment_rate, comment_num, appt_open, appt_limit, distance "
        "FROM ("
        "SELECT *, ROUND((2 * 6378.137 * ASIN(SQRT("
        "POW(SIN(PI() * (%s - latitude) / 360), 2) + "
        "COS(PI() * 29.504164 / 180) * COS(%s * PI() / 180) * POW(SIN(PI() * (%s - longitude) / 360), 2)"
        "))) * 1000) AS distance "
        "FROM tp_seller"
        ") a WHERE id=%s ORDER BY distance ASC",
        (latitude, latitude, longitude, seller_id),
    )
    if not rows:
        return _legacy_upload_error_response()
    detail = rows[0]
    detail["distance"] = 0 if latitude == 1 else _float_or_default(detail.get("distance"), 0.0) / 1000

    class_id = _int_or_default(detail.get("class_id"), 0)
    coupon_rows = fetch_all(
        "SELECT * FROM tp_coupon_issue "
        "WHERE status=1 AND coupon_type IN (1,2) "
        "AND ((use_store=%s OR use_stroe_id='0') OR use_store=1) "
        "AND FIND_IN_SET(%s, use_stroe_id)",
        (class_id, str(seller_id)),
    )
    child_nodes = fetch_all(
        "SELECT nickname, address, latitude, longitude, no, name, mobile, id "
        "FROM tp_seller_child_node WHERE mid=%s",
        (seller_id,),
    )

    return api_success(
        {"detail": {**detail, "collection": "", "seller_child_node": child_nodes}, "coupon": coupon_rows},
        "请求成功",
    )


@fastapi_app.api_route("/api/seller/search", methods=["GET", "POST"])
async def api_seller_search(request: Request) -> Response:
    params = await get_params(request)
    nickname = _string_or_empty(params.get("nickname"))
    if not nickname:
        return Response(
            status_code=200,
            content=b"",
            headers={"content-type": "text/html; charset=utf-8"},
        )

    page = max(1, _int_or_default(params.get("page"), 1))
    limit = max(1, _int_or_default(params.get("limit"), 10))
    offset = (page - 1) * limit
    rows = fetch_all(
        "SELECT id, status, nickname, image, mobile, do_business_time, address, content, longitude, latitude, class_id "
        "FROM tp_seller WHERE nickname LIKE %s LIMIT %s, %s",
        (f"%{nickname}%", offset, limit),
    )
    total_row = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_seller WHERE nickname LIKE %s",
        (f"%{nickname}%",),
    ) or {}
    total = _int_or_default(total_row.get("n"), 0)
    last_page = 0 if total <= 0 else (total + limit - 1) // limit
    return api_success(
        {
            "total": total,
            "per_page": limit,
            "current_page": page,
            "last_page": last_page,
            "data": rows,
        },
        "请求成功",
    )


@fastapi_app.get("/api/ticket/getScenicList")
async def api_ticket_get_scenic_list(request: Request) -> JSONResponse:
    params = await get_params(request)

    page_raw = params.get("page")
    page_size_raw = params.get("page_size")
    try:
        page = int(str(page_raw or "1"))
    except ValueError:
        page = 1
    try:
        page_size = int(str(page_size_raw or "10"))
    except ValueError:
        page_size = 10

    page = max(1, page)
    offset = (page - 1) * page_size

    where_sql = "class_id = 2 AND status = 1"
    where_params: list[Any] = []

    area = params.get("area")
    if not _is_empty_php(area):
        where_sql += " AND area = %s"
        where_params.append(int(str(area)))

    out_id = params.get("out_id")
    if not _is_empty_php(out_id):
        where_sql += " AND id <> %s"
        where_params.append(int(str(out_id)))

    keywords = params.get("keywords")
    if keywords is not None and str(keywords) != "":
        where_sql += " AND nickname LIKE %s"
        where_params.append(f"%{keywords}%")

    has_ticket = params.get("hasTicket")
    if str(has_ticket) == "true":
        seller_id_rows = fetch_all("SELECT seller_id FROM tp_ticket WHERE status=%s", (1,))
        seller_ids = sorted(
            {int(r.get("seller_id") or 0) for r in seller_id_rows if int(r.get("seller_id") or 0) > 0}
        )
        if not seller_ids:
            return api_success([], "请求成功")
        placeholders = ",".join(["%s"] * len(seller_ids))
        where_sql += f" AND id IN ({placeholders})"
        where_params.extend(seller_ids)

    orderby = str(params.get("orderby") or "").strip()
    if orderby == "distance":
        order_by_sql = "distance ASC"
    elif orderby == "comment":
        order_by_sql = "comment_rate DESC"
    else:
        order_by_sql = "id DESC"

    if "latitude" in params:
        latitude_raw = params.get("latitude")
    else:
        latitude_raw = 1
    if "longitude" in params:
        longitude_raw = params.get("longitude")
    else:
        longitude_raw = 1

    try:
        latitude = float(str(latitude_raw))
    except ValueError:
        latitude = 1.0
    try:
        longitude = float(str(longitude_raw))
    except ValueError:
        longitude = 1.0

    sql = """
        SELECT
            id,status,nickname,image,mobile,do_business_time,area,comment_rate,comment_num,address,content,longitude,latitude,class_id,distance
        FROM (
            SELECT
                *,
                ROUND((
                        2 * 6378.137 * ASIN(
                            SQRT(
                                POW( SIN( PI()*( %s - latitude )/ 360 ), 2 )+
                                COS( PI()* 29.504164 / 180 )* COS( %s * PI()/ 180 )* POW( SIN( PI()*( %s - longitude )/ 360 ), 2 )
                            )
                        )
                ) * 1000
            ) AS distance
            FROM tp_seller
        ) a
        WHERE """
    sql += where_sql
    sql += " ORDER BY " + order_by_sql + " LIMIT %s, %s"

    rows = fetch_all(
        sql,
        tuple([latitude, latitude, longitude] + where_params + [offset, page_size]),
    )

    area_map = {
        1: "榆阳区",
        2: "横山区",
        3: "神木市",
        4: "府谷县",
        5: "靖边县",
        6: "定边县",
        7: "绥德县",
        8: "米脂县",
        9: "佳县",
        10: "吴堡县",
        11: "清涧县",
        12: "子洲县",
    }

    out: list[dict[str, Any]] = []
    for row in rows:
        dist_m = float(row.get("distance") or 0.0)
        row["distance"] = 0
        if latitude != 1:
            row["distance"] = dist_m / 1000
        row["longitude"] = _php_json_number(row.get("longitude"))
        row["latitude"] = _php_json_number(row.get("latitude"))
        row["area_text"] = area_map.get(int(row.get("area") or 0), "-")
        min_price_row = fetch_one(
            "SELECT online_price FROM tp_ticket_price WHERE seller_id=%s AND date=CURDATE() LIMIT 1",
            (int(row.get("id") or 0),),
        )
        row["min_price"] = _fmt_money_2((min_price_row or {}).get("online_price")) if min_price_row else None
        out.append(row)

    return api_success(out, "请求成功")


@fastapi_app.get("/api/ticket/getCommentList")
async def api_ticket_get_comment_list(request: Request) -> JSONResponse:
    params = await get_params(request)

    where_sql = "c.status = %s"
    where_params: list[Any] = [1]

    mid = params.get("mid")
    if mid is not None and str(mid) != "":
        where_sql += " AND c.seller_id = %s"
        where_params.append(int(str(mid)))

    user_id = params.get("user_id")
    if user_id is not None and str(user_id) != "":
        where_sql += " AND c.user_id = %s"
        where_params.append(int(str(user_id)))

    order_by = "c.id DESC"
    if str(params.get("orderby") or "") == "rate":
        order_by = "c.rate DESC"

    try:
        page = int(str(params.get("page") or "1"))
    except ValueError:
        page = 1
    try:
        page_size = int(str(params.get("page_size") or "10"))
    except ValueError:
        page_size = 10

    page = max(1, page)
    offset = (page - 1) * page_size

    rows = fetch_all(
        "SELECT c.id, c.rate, c.content, c.create_time, u.headimgurl, u.nickname "
        "FROM tp_ticket_comment c "
        "LEFT JOIN tp_users u ON u.id = c.user_id "
        "WHERE "
        + where_sql
        + " ORDER BY "
        + order_by
        + " LIMIT %s, %s",
        tuple(where_params + [offset, page_size]),
    )

    out: list[dict[str, Any]] = []
    for row in rows:
        out.append(
            {
                "id": row.get("id"),
                "rate": row.get("rate"),
                "content": row.get("content"),
                "create_time": row.get("create_time"),
                "users": {"headimgurl": row.get("headimgurl") or "", "nickname": row.get("nickname") or ""},
            }
        )

    return api_success(out, "获取成功！")


@fastapi_app.get("/api/ticket/getOrderList")
async def api_ticket_get_order_list(request: Request) -> JSONResponse:
    params = await get_params(request)

    userid = request.headers.get("Userid") or ""
    user = fetch_one("SELECT uuid FROM tp_users WHERE id=%s", (userid,)) or {}
    uuid = str(user.get("uuid") or "")

    where_sql = "o.uuid = %s"
    where_params: list[Any] = [uuid]

    status = params.get("status")
    if status is not None and str(status) != "":
        where_sql += " AND o.order_status = %s"
        where_params.append(str(status))

    try:
        page = int(str(params.get("page") or "1"))
    except ValueError:
        page = 1
    try:
        page_size = int(str(params.get("page_size") or "10"))
    except ValueError:
        page_size = 10

    page = max(1, page)
    offset = (page - 1) * page_size

    rows = fetch_all(
        "SELECT "
        "o.id, o.trade_no, o.origin_price, o.amount_price, o.channel, o.create_time, "
        "o.order_status, o.refund_status, o.refund_fee, "
        "(COALESCE(o.writeoff_tourist_num, 0) + COALESCE(o.wirteoff_rights_num, 0)) AS write_off_num, "
        "s.image AS seller_image, s.nickname AS seller_nickname "
        "FROM tp_ticket_order o "
        "LEFT JOIN tp_seller s ON s.id = o.mch_id "
        "WHERE "
        + where_sql
        + " ORDER BY o.id DESC LIMIT %s, %s",
        tuple(where_params + [offset, page_size]),
    )

    out: list[dict[str, Any]] = []
    for row in rows:
        out.append(
            {
                "id": row.get("id"),
                "trade_no": row.get("trade_no"),
                "origin_price": row.get("origin_price"),
                "amount_price": row.get("amount_price"),
                "channel": row.get("channel"),
                "create_time": row.get("create_time"),
                "order_status": row.get("order_status"),
                "refund_status": row.get("refund_status"),
                "refund_fee": row.get("refund_fee"),
                "write_off_num": row.get("write_off_num"),
                "seller": {"image": row.get("seller_image") or "", "nickname": row.get("seller_nickname") or ""},
            }
        )

    return api_success(out, "获取成功！")


@fastapi_app.api_route("/api/ticket/pay", methods=["GET", "POST"])
async def api_ticket_pay(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("请求方式错误！")

    params = await get_params(request)
    uuid = _string_or_empty(params.get("uuid"))
    openid = _string_or_empty(params.get("openid"))
    ticket_data_raw = params.get("data")
    contact_raw = params.get("contact")
    create_lat = _string_or_empty(params.get("create_lat"))
    create_lng = _string_or_empty(params.get("create_lng"))
    ticket_date = _string_or_empty(params.get("ticket_date"))

    ticket_data_obj = _json_object_or_empty(ticket_data_raw)
    if not isinstance(ticket_data_raw, str) and not isinstance(ticket_data_raw, list):
        ticket_data_obj = {}
    if not ticket_data_obj and not (isinstance(ticket_data_raw, list) and ticket_data_raw):
        return api_error("data请求的格式不是json")

    contact_obj = _json_object_or_empty(contact_raw)
    if not contact_obj:
        return api_error("contact请求的格式不是json")

    required = {
        "uuid": uuid,
        "openid": openid,
        "data": ticket_data_raw,
        "contact": contact_raw,
        "create_lat": create_lat,
        "create_lng": create_lng,
        "ticket_date": ticket_date,
    }
    for key, value in required.items():
        if _is_empty_php(value):
            return api_error(f"{key}不能为空")

    user = fetch_one("SELECT id, uuid, openid, auth_status FROM tp_users WHERE uuid=%s LIMIT 1", (uuid,))
    if not user:
        return api_error("未找到用户")
    if _string_or_empty(user.get("openid")) != openid:
        return api_error("当前用户信息异常，禁止提交")
    if _int_or_default(user.get("auth_status"), 0) != 1:
        return api_error("当前用户未实名认证")
    if ticket_date and ticket_date < datetime.now(_TZ_SHANGHAI).strftime("%Y-%m-%d"):
        return api_error(f"购买门票日期{ticket_date}已过")

    ticket_data_list = ticket_data_raw if isinstance(ticket_data_raw, list) else []
    if not ticket_data_list:
        try:
            parsed = json.loads(str(ticket_data_raw))
            if isinstance(parsed, list):
                ticket_data_list = parsed
        except Exception:
            ticket_data_list = []
    if not ticket_data_list:
        return api_error("请至少购买一张门票")

    total_number = 0
    amount_price = Decimal("0.00")
    for item in ticket_data_list:
        if not isinstance(item, dict):
            return api_error("门票参数错误")
        number = _int_or_default(item.get("number"), 0)
        tourists = item.get("tourist") if isinstance(item.get("tourist"), list) else []
        if number != len(tourists):
            return api_error(f"{_string_or_empty(item.get('uuno'))}门票数量与出行人信息不一致")
        price_text = _decimal_or_default(item.get("price"), "0")
        if number <= 0 or price_text <= Decimal("0"):
            return api_error("请至少购买一张门票")
        total_number += number
        amount_price += price_text

    if total_number <= 0:
        return api_error("请至少购买一张门票")
    if amount_price <= Decimal("0.00"):
        return api_error("消费券面额至少大于0.01，否则无法调起支付")

    now_ts = int(time.time())
    trade_no = _next_order_no()
    out_trade_no = "MP" + trade_no
    contact_man = _string_or_empty(contact_obj.get("contact_man"))
    contact_phone = _string_or_empty(contact_obj.get("contact_phone"))
    execute(
        "INSERT INTO tp_ticket_order "
        "(openid, uuid, mch_id, trade_no, out_trade_no, channel, type, origin_price, amount_price, contact_man, contact_phone, "
        "order_remark, order_status, refund_status, create_lat, create_lng, create_ip, create_time, update_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            openid,
            uuid,
            0,
            trade_no,
            out_trade_no,
            "online",
            "miniapp",
            f"{amount_price:.2f}",
            f"{amount_price:.2f}",
            contact_man,
            contact_phone,
            _string_or_empty(params.get("order_remark")),
            "created",
            "not_refunded",
            create_lat,
            create_lng,
            request.client.host if request.client else "",
            now_ts,
            now_ts,
        ),
    )

    return api_success(
        {
            "pay": _mock_pay_payload("miniapp", trade_no),
            "trade_no": trade_no,
            "amount_price": f"{amount_price:.2f}",
        },
        "订单添加成功",
    )


@fastapi_app.post("/api/ticket/orderpay")
async def api_ticket_orderpay(request: Request) -> JSONResponse:
    params = await get_params(request)
    required = {
        "uuid": _string_or_empty(params.get("uuid")),
        "openid": _string_or_empty(params.get("openid")),
        "trade_no": _string_or_empty(params.get("trade_no")),
    }
    for key, value in required.items():
        if not value:
            return api_error(f"{key}不能为空")

    order = fetch_one(
        "SELECT id, uuid, trade_no, amount_price, type, order_status FROM tp_ticket_order WHERE trade_no=%s LIMIT 1",
        (required["trade_no"],),
    )
    if not order:
        return api_error("订单不存在")

    order_status = _string_or_empty(order.get("order_status"))
    if order_status == "paid":
        return api_error("该订单已经支付")
    if order_status == "used":
        return api_error("该订单已经使用")
    if order_status == "cancelled":
        return api_error("该订单已经取消")
    if order_status == "refunded":
        return api_error("该订单已经退款")
    if _string_or_empty(order.get("uuid")) != required["uuid"]:
        return api_error("当前订单归属错误")

    user = fetch_one("SELECT id, uuid, openid FROM tp_users WHERE uuid=%s LIMIT 1", (required["uuid"],))
    if not user:
        return api_error("未找到用户")
    if _string_or_empty(user.get("openid")) != required["openid"]:
        return api_error("当前用户信息异常，禁止提交")

    return api_success(
        {
            "pay": _mock_pay_payload("miniapp", _string_or_empty(order.get("trade_no"))),
            "trade_no": _string_or_empty(order.get("trade_no")),
            "amount_price": _fmt_money_2(order.get("amount_price")),
        },
        "支付成功",
    )


@fastapi_app.api_route("/api/ticket/getOrderDetail", methods=["GET", "POST"])
async def api_ticket_get_order_detail(request: Request) -> JSONResponse:
    if request.method.upper() != "GET":
        return api_error("请求方式错误！")
    params = await get_params(request)
    order_id_raw = _string_or_empty(params.get("order_id"))
    if not order_id_raw:
        return api_error("缺少订单参数")
    if not order_id_raw.isdigit():
        return api_error("缺少订单参数")
    order_id = _int_or_default(order_id_raw, 0)
    if order_id <= 0:
        return api_error("缺少订单参数")

    user = fetch_one("SELECT id, uuid FROM tp_users WHERE id=%s LIMIT 1", (request.headers.get("Userid") or "",))
    order = fetch_one(
        "SELECT o.*, s.image AS seller_image, s.nickname AS seller_nickname "
        "FROM tp_ticket_order o LEFT JOIN tp_seller s ON s.id = o.mch_id WHERE o.id=%s LIMIT 1",
        (order_id,),
    )
    if not order:
        return _legacy_upload_error_response()

    if not user or _string_or_empty(user.get("uuid")) != _string_or_empty(order.get("uuid")):
        hx_man = fetch_one(
            "SELECT id FROM tp_merchant_verifier WHERE uid=%s AND type=%s LIMIT 1",
            (request.headers.get("Userid") or "", "ticket"),
        )
        if not hx_man:
            return api_error("权限不足！")

    detail_list = fetch_all(
        "SELECT * FROM tp_ticket_order_detail WHERE trade_no=%s",
        (_string_or_empty(order.get("trade_no")),),
    )
    expire_ts = int(time.time()) + 600
    if detail_list:
        detail_ids = [int(d.get("id") or 0) for d in detail_list if int(d.get("id") or 0) > 0]
        rights_map: dict[int, list[dict[str, Any]]] = {}
        if detail_ids:
            placeholders = ",".join(["%s"] * len(detail_ids))
            rights_rows = fetch_all(
                f"SELECT * FROM tp_ticket_order_detail_rights WHERE detail_id IN ({placeholders}) ORDER BY id ASC",
                tuple(detail_ids),
            )
            for rr in rights_rows:
                rights_map.setdefault(_int_or_default(rr.get("detail_id"), 0), []).append(rr)
        for d in detail_list:
            d["tourist_cert_type_text"] = {
                "1": "身份证",
                "2": "护照",
                "3": "台湾通行证",
                "4": "港澳通行证",
                "5": "回乡证",
            }.get(str(d.get("tourist_cert_type") or ""), "-")
            d["refund_status_text"] = _ticket_refund_status_text(d.get("refund_status"))
            if _int_or_default(d.get("ticket_rights_num"), 0) < 1:
                d["qrcode_str"] = _sys_encrypt(
                    f"detail&{_string_or_empty(d.get('ticket_code'))}&{expire_ts}",
                    str(_int_or_default(d.get("id"), 0)),
                )
            else:
                d["qrcode_str"] = ""
            d["rights_list"] = rights_map.get(_int_or_default(d.get("id"), 0), [])
            d["create_time"] = _fmt_ts_shanghai(d.get("create_time"))
            d["ticket_price"] = _fmt_money_2(d.get("ticket_price"))
            d.pop("update_time", None)
            d.pop("delete_time", None)
            d.pop("uuid", None)

    iscomment = False
    if _string_or_empty(order.get("order_status")) == "used":
        comment = fetch_one(
            "SELECT id FROM tp_ticket_comment WHERE order_id=%s LIMIT 1",
            (_int_or_default(order.get("id"), 0),),
        )
        iscomment = bool(comment)

    qrcode_str = _sys_encrypt(
        f"order&{_string_or_empty(order.get('trade_no'))}&{expire_ts}",
        str(_int_or_default(order.get("id"), 0)),
    )
    out = {
        "id": order.get("id"),
        "trade_no": order.get("trade_no"),
        "out_trade_no": order.get("out_trade_no"),
        "origin_price": _fmt_money_2(order.get("origin_price")),
        "amount_price": _fmt_money_2(order.get("amount_price")),
        "channel": order.get("channel"),
        "channel_text": _ticket_channel_text(order.get("channel")),
        "create_time": _fmt_ts_shanghai(order.get("create_time")),
        "order_status": order.get("order_status"),
        "order_status_text": _ticket_order_status_text(order.get("order_status")),
        "iscomment": iscomment,
        "refund_status": order.get("refund_status"),
        "refund_fee": _fmt_money_2(order.get("refund_fee")),
        "seller": {
            "image": _string_or_empty(order.get("seller_image")),
            "nickname": _string_or_empty(order.get("seller_nickname")),
        },
        "detail_list": detail_list,
        "qrcode_str": qrcode_str,
        "rights_qrcode_list": [],
    }
    if detail_list:
        out["ticket_info"] = {
            "id": detail_list[0].get("ticket_id"),
            "title": detail_list[0].get("ticket_title"),
            "date": detail_list[0].get("ticket_date"),
            "cover": detail_list[0].get("ticket_cover"),
            "price": _fmt_money_2(detail_list[0].get("ticket_price")),
            "explain_use": detail_list[0].get("explain_use"),
            "explain_buy": detail_list[0].get("explain_buy"),
        }

    return api_success(out, "")


@fastapi_app.api_route("/api/ticket/getOrderDetailDetail", methods=["GET", "POST"])
async def api_ticket_get_order_detail_detail(request: Request) -> JSONResponse:
    if request.method.upper() != "GET":
        return api_error("请求方式错误！")
    params = await get_params(request)
    order_detail_id = _int_or_default(params.get("order_detail_id"), 0)
    if order_detail_id <= 0:
        return api_error("缺少参数")

    user = fetch_one("SELECT id, uuid FROM tp_users WHERE id=%s LIMIT 1", (request.headers.get("Userid") or "",))
    detail = fetch_one("SELECT * FROM tp_ticket_order_detail WHERE id=%s LIMIT 1", (order_detail_id,))
    if not user or not detail:
        return api_error("未找到记录！")

    if _string_or_empty(user.get("uuid")) != _string_or_empty(detail.get("uuid")):
        hx_man = fetch_one(
            "SELECT id FROM tp_merchant_verifier WHERE uid=%s AND type=%s LIMIT 1",
            (request.headers.get("Userid") or "", "ticket"),
        )
        if not hx_man:
            return api_error("权限不足！")

    detail["tourist_cert_type_text"] = {
        "1": "身份证",
        "2": "护照",
        "3": "台湾通行证",
        "4": "港澳通行证",
        "5": "回乡证",
    }.get(str(detail.get("tourist_cert_type") or ""), "-")
    detail["refund_status_text"] = {
        "not_refunded": "未退款",
        "fully_refunded": "已退款",
    }.get(str(detail.get("refund_status") or ""), "-")
    detail["qrcode_str"] = f"detail&{_string_or_empty(detail.get('ticket_code'))}&{int(time.time()) + 300}"
    rights = fetch_all(
        "SELECT * FROM tp_ticket_order_detail_rights WHERE detail_id=%s ORDER BY id ASC",
        (order_detail_id,),
    )
    detail["rights_list"] = rights
    detail.pop("update_time", None)
    detail.pop("delete_time", None)
    detail.pop("uuid", None)
    seller = fetch_one(
        "SELECT s.nickname, s.image "
        "FROM tp_seller s JOIN tp_ticket t ON t.seller_id=s.id WHERE t.id=%s LIMIT 1",
        (_int_or_default(detail.get("ticket_id"), 0),),
    ) or {}
    detail["seller"] = {"nickname": _string_or_empty(seller.get("nickname")), "image": _string_or_empty(seller.get("image"))}
    return api_success(detail, "")


@fastapi_app.api_route("/api/ticket/writeComment", methods=["GET", "POST"])
async def api_ticket_write_comment(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("请求方式错误！")
    params = await get_params(request)
    order_id = _int_or_default(params.get("order_id"), 0)
    content = _string_or_empty(params.get("content"))
    rate = _string_or_empty(params.get("rate"))
    if order_id <= 0:
        return api_error("参数错误！")
    if not content:
        return api_error("评论内容不能为空！")
    if len(content) < 10:
        return api_error("评论内容太短！")
    if not rate:
        return api_error("请选择评分！")
    if _float_or_default(rate, 0.0) > 5:
        return api_error("评分不能超过5！")

    order = fetch_one("SELECT id, uuid, mch_id, order_status FROM tp_ticket_order WHERE id=%s LIMIT 1", (order_id,))
    if not order:
        return api_error("订单不存在！")
    user = fetch_one("SELECT id, uuid FROM tp_users WHERE id=%s LIMIT 1", (request.headers.get("Userid") or "",))
    if not user or _string_or_empty(user.get("uuid")) != _string_or_empty(order.get("uuid")):
        return api_error("订单不存在！")
    if _string_or_empty(order.get("order_status")) != "used":
        return api_error("请使用后再评论！")

    exists = fetch_one("SELECT id FROM tp_ticket_comment WHERE order_id=%s LIMIT 1", (order_id,))
    if exists:
        return api_error("该订单已评论！")

    now_ts = int(time.time())
    execute(
        "INSERT INTO tp_ticket_comment "
        "(order_id, content, seller_id, user_id, rate, create_time, update_time, ip, status) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            order_id,
            content,
            _int_or_default(order.get("mch_id"), 0),
            _int_or_default(user.get("id"), 0),
            f"{_float_or_default(rate, 0.0):.2f}",
            now_ts,
            now_ts,
            request.client.host if request.client else "",
            0,
        ),
    )
    return api_success([], "评论成功！")


@fastapi_app.api_route("/api/ticket/writeOff", methods=["GET", "POST"])
async def api_ticket_write_off(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("请求方式错误！")

    params = await get_params(request)
    if not _string_or_empty(params.get("qrcode_str")):
        return api_error("参数错误！")
    if not _string_or_empty(params.get("be_id")):
        return api_error("参数错误！")
    if not _string_or_empty(params.get("use_lat")):
        return api_error("核销纬度不能为空！")
    if not _string_or_empty(params.get("use_lng")):
        return api_error("核销经度不能为空")

    user_id = _int_or_default(request.headers.get("Userid"), 0)
    hx_man = fetch_one(
        "SELECT id, uid, mid, type, status, name FROM tp_merchant_verifier WHERE uid=%s AND type=%s LIMIT 1",
        (user_id, "ticket"),
    )
    if not hx_man:
        return api_error("您不是核销员！")
    if _string_or_empty(hx_man.get("type")) != "ticket":
        return api_error("核销人不允许核销门票！")
    if _int_or_default(hx_man.get("status"), 0) != 1:
        return api_error("核销人未通过审核！")

    qrcode_parts = _string_or_empty(params.get("qrcode_str")).split("&")
    if len(qrcode_parts) != 3:
        return api_error("核销码长度不符！")
    if qrcode_parts[0] not in {"order", "detail", "orderrights", "rights", "ticket"}:
        return api_error("核销码方式不正确！")
    if qrcode_parts[0] in {"order", "detail", "orderrights", "rights"} and _int_or_default(qrcode_parts[2], 0) < int(time.time()):
        return api_error("核销码已过期，刷新后再试！")

    return api_error("参数错误！")


@fastapi_app.api_route("/api/ticket/getTicketList", methods=["GET", "POST"])
async def api_ticket_get_ticket_list(request: Request) -> JSONResponse:
    params = await get_params(request)
    seller_id_raw = params.get("seller_id")
    try:
        seller_id = int(str(seller_id_raw or "0"))
    except ValueError:
        seller_id = 0

    if seller_id == 0:
        return api_error("缺少商户参数")

    ticket_list = fetch_all(
        "SELECT * FROM tp_ticket WHERE seller_id=%s ORDER BY sort DESC, id ASC",
        (seller_id,),
    )
    if not ticket_list:
        return api_success([], "")

    ticket_ids = sorted({int(t.get("id") or 0) for t in ticket_list if int(t.get("id") or 0) > 0})
    category_ids = sorted(
        {int(t.get("category_id") or 0) for t in ticket_list if int(t.get("category_id") or 0) > 0}
    )

    category_list: list[dict[str, Any]] = []
    if category_ids:
        placeholders = ",".join(["%s"] * len(category_ids))
        category_list = fetch_all(
            f"SELECT * FROM tp_ticket_category WHERE id IN ({placeholders}) ORDER BY sort DESC, id ASC",
            tuple(category_ids),
        )

    min_price_map: dict[int, Any] = {}
    rights_map: dict[int, list[dict[str, Any]]] = {}
    if ticket_ids:
        placeholders = ",".join(["%s"] * len(ticket_ids))
        rows = fetch_all(
            "SELECT ticket_id, MIN(online_price) AS min_price "
            f"FROM tp_ticket_price WHERE ticket_id IN ({placeholders}) AND date >= CURDATE() "
            "GROUP BY ticket_id",
            tuple(ticket_ids),
        )
        min_price_map = {int(r.get("ticket_id") or 0): r.get("min_price") for r in rows}

        rights_rows = fetch_all(
            f"SELECT * FROM tp_ticket_rights WHERE ticket_id IN ({placeholders}) ORDER BY id ASC",
            tuple(ticket_ids),
        )
        for r in rights_rows:
            rights_map.setdefault(int(r.get("ticket_id") or 0), []).append(r)

    ticket_grouped: dict[int, list[dict[str, Any]]] = {}
    for t in ticket_list:
        tid = int(t.get("id") or 0)
        t["min_price"] = _fmt_money_2(min_price_map.get(tid))
        t["rights_list"] = rights_map.get(tid, [])
        t["create_time"] = _fmt_ts_shanghai(t.get("create_time"))
        t["update_time"] = _fmt_ts_shanghai(t.get("update_time"))
        t["crossed_price"] = _fmt_money_2(t.get("crossed_price"))
        cid = int(t.get("category_id") or 0)
        ticket_grouped.setdefault(cid, []).append(t)

    out: list[dict[str, Any]] = []
    for cate in category_list:
        cid = int(cate.get("id") or 0)
        cate["ticket_list"] = ticket_grouped.get(cid, [])
        cate["create_time"] = _fmt_ts_shanghai(cate.get("create_time"))
        cate["update_time"] = _fmt_ts_shanghai(cate.get("update_time"))
        out.append(cate)

    return api_success(out, "")


@fastapi_app.api_route("/api/ticket/getTicketPirce", methods=["GET", "POST"])
async def api_ticket_get_ticket_price(request: Request) -> JSONResponse:
    params = await get_params(request)
    ticket_id_raw = params.get("ticket_id")
    try:
        ticket_id = int(str(ticket_id_raw or "0"))
    except ValueError:
        ticket_id = 0

    if ticket_id == 0:
        return api_error("缺少门票参数")

    date_start = str(params.get("date_start") or "").strip()
    date_end = str(params.get("date_end") or "").strip()
    channel = str(params.get("channel") or "").strip()

    where = ["ticket_id = %s"]
    sql_params: list[Any] = [ticket_id]
    if not date_start and not date_end:
        where.append("date >= CURDATE()")
    else:
        if date_start:
            where.append("date >= %s")
            sql_params.append(date_start)
        if date_end:
            where.append("date <= %s")
            sql_params.append(date_end)

    fields = ["ticket_id", "stock", "total_stock", "date"]
    if channel == "online":
        fields.append("online_price AS price")
    elif channel == "casual":
        fields.append("casual_price AS price")
    elif channel == "team":
        fields.append("team_price AS price")

    rows = fetch_all(
        "SELECT " + ", ".join(fields) + " FROM tp_ticket_price WHERE " + " AND ".join(where) + " ORDER BY date ASC",
        tuple(sql_params),
    )

    for r in rows:
        if "price" in r:
            r["price"] = _fmt_money_2(r.get("price"))
    return api_success(rows, "")


@fastapi_app.get("/api/ticket/getRefundLogList")
async def api_ticket_get_refund_log_list(request: Request) -> JSONResponse:
    params = await get_params(request)

    userid = request.headers.get("Userid") or ""
    user = fetch_one("SELECT uuid FROM tp_users WHERE id=%s", (userid,))
    if not user:
        return api_error("用户不存在！")
    uuid = str(user.get("uuid") or "")

    try:
        page = int(str(params.get("page") or "1"))
    except ValueError:
        page = 1
    try:
        page_size = int(str(params.get("page_size") or "10"))
    except ValueError:
        page_size = 10

    page = max(1, page)
    offset = (page - 1) * page_size

    rows = fetch_all(
        "SELECT * FROM tp_ticket_refunds WHERE uuid=%s ORDER BY id DESC LIMIT %s, %s",
        (uuid, offset, page_size),
    )

    status_map = {"0": "待退款", "1": "退款成功", "2": "退款失败", "3": "用户取消"}
    for row in rows:
        row["status_text"] = status_map.get(str(row.get("status") or ""), "-")

    return api_success(rows, "")


@fastapi_app.get("/api/ticket/getRefundLogDetail")
async def api_ticket_get_refund_log_detail(request: Request) -> JSONResponse:
    params = await get_params(request)

    userid = request.headers.get("Userid") or ""
    user = fetch_one("SELECT uuid FROM tp_users WHERE id=%s", (userid,))
    if not user:
        return api_error("用户不存在！")
    uuid = str(user.get("uuid") or "")

    refund_id_raw = params.get("id")
    try:
        refund_id = int(str(refund_id_raw or "0"))
    except ValueError:
        refund_id = 0

    if refund_id < 1:
        return api_error("参数错误！")

    info = fetch_one("SELECT * FROM tp_ticket_refunds WHERE id=%s", (refund_id,))
    if not info:
        return api_error("记录不存在！")

    if str(info.get("uuid") or "") != uuid:
        return api_error("参数错误！")

    status_map = {"0": "待退款", "1": "退款成功", "2": "退款失败", "3": "用户取消"}
    info["status_text"] = status_map.get(str(info.get("status") or ""), "-")

    return api_success(info, "")


@fastapi_app.api_route("/api/user/index", methods=["GET", "POST"])
async def api_user_index(request: Request) -> JSONResponse:
    params = await get_params(request)
    has_uid = "uid" in params
    uid_raw = _string_or_empty(params.get("uid"))
    if not has_uid:
        return api_error("用户ID不能为空！")
    if not uid_raw or not uid_raw.isdigit():
        return api_error("用户信息异常 -H")
    uid = _int_or_default(uid_raw, 0)
    if uid <= 0:
        return api_error("用户信息异常 -H")

    user = fetch_one(
        "SELECT id, uuid, headimgurl, nickname, name, mobile, idcard, credit_score, credit_rating, "
        "update_credit, auth_status FROM tp_users WHERE id=%s LIMIT 1",
        (uid,),
    )
    if not user:
        return api_error("用户信息异常 -H")
    if not _string_or_empty(user.get("uuid")):
        return _legacy_upload_error_response()

    user["mobile"] = _mask_mobile(user.get("mobile"))
    idcard_text = _string_or_empty(user.get("idcard"))
    if len(idcard_text) >= 4:
        user["idcard"] = idcard_text[:2] + "****" + idcard_text[-2:]
    else:
        user["idcard"] = idcard_text

    guide = fetch_one("SELECT id FROM tp_guide WHERE uid=%s LIMIT 1", (uid,))
    user["guide"] = _int_or_default(guide.get("id"), 0) if guide else False

    ismv_rows = fetch_all(
        "SELECT id, create_time, update_time, status, name, image, mobile, openid, mid, uid, type "
        "FROM tp_merchant_verifier WHERE uid=%s AND status=%s ORDER BY id DESC",
        (uid, 1),
    )
    user["ismv"] = ismv_rows if ismv_rows else None

    has_tour_writeoff = fetch_one(
        "SELECT id FROM tp_tour_write_off WHERE uid=%s AND type=%s LIMIT 1",
        (uid, 2),
    )
    has_hotel_clock = fetch_one(
        "SELECT id FROM tp_tour_hotel_user_record WHERE uid=%s LIMIT 1",
        (uid,),
    )
    user["is_clock"] = bool(has_tour_writeoff or has_hotel_clock)
    return api_success(user, "请求成功")


@fastapi_app.api_route("/api/user/edit", methods=["GET", "POST"])
async def api_user_edit(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("id"), 0)
    if uid <= 0:
        return api_error("用户ID不能为空！")

    if "nickname" in params:
        nickname = _string_or_empty(params.get("nickname"))
        if not nickname:
            return api_error("请输入内容")

    if "mobile" in params:
        mobile = _string_or_empty(params.get("mobile"))
        if mobile and not _is_valid_mobile(mobile):
            return api_error("手机号错误")

    if "name" in params:
        name = _string_or_empty(params.get("name"))
        if name and not _is_chinese_name(name):
            return api_error("请输入正确的姓名！")

    if "idcard" in params:
        idcard = _string_or_empty(params.get("idcard")).upper()
        if idcard and not _is_valid_idcard(idcard):
            return api_error("请输入正确的身份证号码")

    exists = fetch_one(
        "SELECT id, name, nickname, headimgurl FROM tp_users WHERE id=%s LIMIT 1",
        (uid,),
    )
    if not exists:
        return api_error("当前用户不存在")

    update_data: dict[str, Any] = {"update_time": int(time.time())}
    if "nickname" in params:
        update_data["nickname"] = _string_or_empty(params.get("nickname"))
    if "headimgurl" in params:
        update_data["headimgurl"] = _string_or_empty(params.get("headimgurl"))
    if "name" in params:
        update_data["name"] = _string_or_empty(params.get("name"))
    if "mobile" in params:
        update_data["mobile"] = _string_or_empty(params.get("mobile"))
    if "idcard" in params:
        idcard = _string_or_empty(params.get("idcard")).upper()
        update_data["idcard"] = idcard
        if len(idcard) >= 14:
            update_data["birthday"] = f"{idcard[6:10]}-{idcard[10:12]}-{idcard[12:14]}"
        update_data["email_validated"] = 1

    columns = list(update_data.keys())
    set_clause = ", ".join([f"{c}=%s" for c in columns])
    args = [update_data[c] for c in columns] + [uid]
    affected = execute(f"UPDATE tp_users SET {set_clause} WHERE id=%s", tuple(args))
    if affected >= 0:
        return api_success(affected, "保存成功")
    return api_error("保存失败")


@fastapi_app.api_route("/api/user/coupon_issue_user", methods=["GET", "POST"])
async def api_user_coupon_issue_user(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("参数异常")

    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    page = max(1, _int_or_default(params.get("page"), 1))
    limit = max(1, _int_or_default(params.get("limit"), 10))
    offset = (page - 1) * limit

    where_sql = ["uid=%s"]
    where_args: list[Any] = [uid]
    status_raw = params.get("status")
    if status_raw is not None and str(status_raw) != "":
        where_sql.append("status=%s")
        where_args.append(_int_or_default(status_raw, 0))

    total_row = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_coupon_issue_user WHERE " + " AND ".join(where_sql),
        tuple(where_args),
    ) or {}
    total = _int_or_default(total_row.get("n"), 0)

    rows = fetch_all(
        "SELECT * FROM tp_coupon_issue_user WHERE "
        + " AND ".join(where_sql)
        + " ORDER BY id DESC LIMIT %s, %s",
        tuple(where_args + [offset, limit]),
    )
    if rows:
        issue_ids = sorted(
            {
                _int_or_default(row.get("issue_coupon_id"), 0)
                for row in rows
                if _int_or_default(row.get("issue_coupon_id"), 0) > 0
            }
        )
        issue_map: dict[int, dict[str, Any]] = {}
        if issue_ids:
            placeholders = ",".join(["%s"] * len(issue_ids))
            for issue in fetch_all(
                f"SELECT * FROM tp_coupon_issue WHERE id IN ({placeholders})",
                tuple(issue_ids),
            ):
                issue_map[_int_or_default(issue.get("id"), 0)] = issue
        for row in rows:
            row["coupon_issue"] = issue_map.get(_int_or_default(row.get("issue_coupon_id"), 0))

    return api_success(
        _pagination_payload(total=total, page=page, per_page=limit, rows=rows),
        "请求成功",
    )


@fastapi_app.api_route("/api/user/collection", methods=["GET", "POST"])
async def api_user_collection(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("参数异常")
    if "latitude" not in params or "longitude" not in params:
        return api_error("参数异常")
    if "page" not in params or "limit" not in params:
        return api_error("参数异常")

    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    col_rows = fetch_all("SELECT mid FROM tp_collection WHERE uid=%s", (uid,))
    mids = [_int_or_default(r.get("mid"), 0) for r in col_rows if _int_or_default(r.get("mid"), 0) > 0]
    if not mids:
        return api_error("没有收藏任何商家")

    latitude = _float_or_default(params.get("latitude"), 0.0)
    longitude = _float_or_default(params.get("longitude"), 0.0)
    offset = max(0, _int_or_default(params.get("page"), 0))
    limit = max(1, _int_or_default(params.get("limit"), 10))
    placeholders = ",".join(["%s"] * len(mids))
    rows = fetch_all(
        "SELECT id, status, nickname, image, mobile, do_business_time, address, content, longitude, latitude, class_id, distance "
        "FROM ("
        "SELECT *, ROUND((2 * 6378.137 * ASIN(SQRT("
        "POW(SIN(PI() * (%s - latitude) / 360), 2) + "
        "COS(PI() * 29.504164 / 180) * COS(%s * PI() / 180) * POW(SIN(PI() * (%s - longitude) / 360), 2)"
        "))) * 1000) AS distance "
        "FROM tp_seller"
        ") a WHERE id IN ("
        + placeholders
        + ") ORDER BY distance ASC LIMIT %s, %s",
        tuple([latitude, latitude, longitude] + mids + [offset, limit]),
    )
    for row in rows:
        row["distance"] = _float_or_default(row.get("distance"), 0.0) / 1000
    return api_success(rows, "请求成功")


@fastapi_app.api_route("/api/user/collection_action", methods=["GET", "POST"])
async def api_user_collection_action(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    mid = _int_or_default(params.get("mid"), 0)
    action = _string_or_empty(params.get("action"))
    if uid <= 0 or mid <= 0 or not action:
        return api_error("参数异常")

    if action == "add":
        exists = fetch_one("SELECT id FROM tp_collection WHERE uid=%s AND mid=%s LIMIT 1", (uid, mid))
        if not exists:
            now_ts = int(time.time())
            execute(
                "INSERT INTO tp_collection (uid, mid, create_time, update_time) VALUES (%s, %s, %s, %s)",
                (uid, mid, now_ts, now_ts),
            )
        return api_success("data success", "收藏成功")

    if action == "del":
        execute("DELETE FROM tp_collection WHERE uid=%s AND mid=%s", (uid, mid))
        return api_success([], "取消成功")

    return api_error("参数异常")


@fastapi_app.api_route("/api/user/tour_coupon_group", methods=["GET", "POST"])
async def api_user_tour_coupon_group(request: Request) -> JSONResponse:
    params = await get_params(request)
    group_id_raw = _string_or_empty(params.get("id"))
    if not group_id_raw:
        return api_error("ID不能为空！")
    if not group_id_raw.isdigit():
        return _legacy_upload_error_response()
    group_id = _int_or_default(group_id_raw, 0)
    if group_id <= 0:
        return api_error("ID不能为空！")

    group = fetch_one("SELECT * FROM tp_tour_coupon_group WHERE id=%s LIMIT 1", (group_id,))
    if not group:
        return _legacy_upload_error_response()

    tid = _int_or_default(group.get("tid"), 0)
    coupon_issue_id = _int_or_default(group.get("coupon_issue_id"), 0)
    cid = _int_or_default(group.get("cid"), 0)

    tour = fetch_one("SELECT * FROM tp_tour WHERE id=%s LIMIT 1", (tid,)) if tid > 0 else None
    if tour:
        tour["create_time"] = _fmt_ts_shanghai(tour.get("create_time"))
        tour["update_time"] = _fmt_ts_shanghai(tour.get("update_time"))

    coupon_issue = (
        fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (coupon_issue_id,))
        if coupon_issue_id > 0
        else None
    )
    if coupon_issue:
        coupon_issue["create_time"] = _fmt_ts_shanghai(coupon_issue.get("create_time"))
        coupon_issue["update_time"] = _fmt_ts_shanghai(coupon_issue.get("update_time"))
        coupon_issue["coupon_price"] = _fmt_money_2(coupon_issue.get("coupon_price"))
        coupon_issue["sale_price"] = _fmt_money_2(coupon_issue.get("sale_price"))
        coupon_issue["use_min_price"] = _fmt_money_2(coupon_issue.get("use_min_price"))

    coupon_class = (
        fetch_one("SELECT * FROM tp_coupon_class WHERE id=%s LIMIT 1", (cid,))
        if cid > 0
        else None
    )
    if coupon_class:
        coupon_class["create_time"] = _fmt_ts_shanghai(coupon_class.get("create_time"))
        coupon_class["update_time"] = _fmt_ts_shanghai(coupon_class.get("update_time"))

    seller_id = _int_or_default((tour or {}).get("mid"), 0)
    group["seller"] = (
        fetch_one("SELECT nickname, image FROM tp_seller WHERE id=%s LIMIT 1", (seller_id,))
        if seller_id > 0
        else None
    )

    write_off_rows = fetch_all(
        "SELECT * FROM tp_tour_write_off WHERE tour_coupon_group_id=%s ORDER BY id DESC",
        (group_id,),
    )
    if write_off_rows:
        user_ids = sorted(
            {
                _int_or_default(row.get("uid"), 0)
                for row in write_off_rows
                if _int_or_default(row.get("uid"), 0) > 0
            }
        )
        user_map: dict[int, dict[str, Any]] = {}
        if user_ids:
            placeholders = ",".join(["%s"] * len(user_ids))
            for item in fetch_all(
                f"SELECT id, uuid, headimgurl, nickname, name, mobile FROM tp_users WHERE id IN ({placeholders})",
                tuple(user_ids),
            ):
                user_map[_int_or_default(item.get("id"), 0)] = item
        for row in write_off_rows:
            row["user"] = user_map.get(_int_or_default(row.get("uid"), 0))

    group["tour_write_off"] = write_off_rows
    group["tourist"] = fetch_all(
        "SELECT id, create_time, update_time, sort, status, name, mobile, tid, mid, uid, contract, insurance, "
        "tour_receive_time, tour_price, numbers, tour_writeoff_time FROM tp_tourist WHERE tid=%s",
        (tid,),
    )
    group["create_time"] = _fmt_ts_shanghai(group.get("create_time"))
    group["update_time"] = _fmt_ts_shanghai(group.get("update_time"))
    group["tour"] = tour
    group["couponIssue"] = coupon_issue
    group["couponClass"] = coupon_class
    return api_success(group, "请求成功")


@fastapi_app.api_route("/api/user/writeoff_tour", methods=["GET", "POST"])
async def api_user_writeoff_tour(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")

    params = await get_params(request)
    if not params:
        return _legacy_upload_error_response()
    userid = _int_or_default(params.get("userid"), 0)
    mid = _int_or_default(params.get("mid"), 0)
    coupon_issue_user_id = _int_or_default(params.get("coupon_issue_user_id"), 0)
    use_min_price = _decimal_or_default(params.get("use_min_price"), "0.00")
    orderid = _int_or_default(params.get("orderid"), 0)
    qrcode_url = _string_or_empty(params.get("qrcode_url"))

    longitude = _float_or_default(params.get("longitude"), 0.0)
    latitude = _float_or_default(params.get("latitude"), 0.0)
    vr_longitude = _float_or_default(params.get("vr_longitude"), 0.0)
    vr_latitude = _float_or_default(params.get("vr_latitude"), 0.0)

    if userid <= 0:
        return _legacy_upload_error_response()
    if mid <= 0:
        return _legacy_upload_error_response()
    if coupon_issue_user_id <= 0:
        return _legacy_upload_error_response()

    user = fetch_one("SELECT id, status, salt FROM tp_users WHERE id=%s LIMIT 1", (userid,))
    if not user:
        return _legacy_upload_error_response()
    if _int_or_default(user.get("status"), 0) != 1:
        return _legacy_upload_error_response()

    seller = fetch_one("SELECT id, status, class_id FROM tp_seller WHERE id=%s LIMIT 1", (mid,))
    if not seller:
        return _legacy_upload_error_response()
    if _int_or_default(seller.get("status"), 0) != 1:
        return _legacy_upload_error_response()

    group = fetch_one("SELECT * FROM tp_tour_coupon_group WHERE id=%s LIMIT 1", (coupon_issue_user_id,))
    if not group:
        return _legacy_upload_error_response()
    group_status = _int_or_default(group.get("status"), 0)
    if group_status == 1:
        return api_error("该消费券已使用")
    if group_status == 2:
        return api_error("该消费券已过期")

    if qrcode_url and _string_or_empty(group.get("qrcode_url")) != qrcode_url:
        return api_error("二维码已失效")

    now_ts = int(time.time())
    if _int_or_default(group.get("code_time_expire"), 0) > 0 and _int_or_default(group.get("code_time_expire"), 0) < now_ts:
        return api_error("二维码已过期")

    issue = fetch_one("SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1", (_int_or_default(group.get("coupon_issue_id"), 0),))
    if not issue:
        return api_error("该消费券已无法使用")
    if _string_or_empty(issue.get("status")) == "-1":
        return api_error("该消费券已无法使用")

    if _int_or_default(issue.get("is_threshold"), 0) == 1:
        threshold = _decimal_or_default(issue.get("use_min_price"), "0.00")
        if threshold >= use_min_price:
            return api_error(f"最低消费需要满{issue.get('use_min_price')}才可使用")

    is_permanent = _int_or_default(issue.get("is_permanent"), 0)
    if is_permanent == 2:
        if _int_or_default(issue.get("coupon_time_start"), 0) > now_ts:
            return api_error("该消费券还未到使用时段")
        if _int_or_default(issue.get("coupon_time_end"), 0) < now_ts:
            return api_error("该消费券已过使用时段")
    if is_permanent == 3:
        yxtime = _int_or_default(group.get("receive_time"), 0) + _int_or_default(issue.get("day"), 0) * 86400
        if yxtime > 0 and now_ts > yxtime:
            return api_error("该消费券已经过期")

    if _int_or_default(issue.get("use_store"), 1) != 1:
        use_store_ids = _string_or_empty(issue.get("use_stroe_id"))
        if use_store_ids:
            allow_mid = {int(i) for i in use_store_ids.split(",") if i.isdigit()}
            if allow_mid and mid not in allow_mid:
                return api_error("该消费券无法在该门店下使用")

    tourists = fetch_all(
        "SELECT id, uid, tid FROM tp_tour_issue_user WHERE tid=%s AND type=%s AND issue_coupon_id=%s",
        (
            _int_or_default(group.get("tid"), 0),
            2,
            _int_or_default(issue.get("id"), 0),
        ),
    )
    if not tourists:
        return api_error("该旅行团没有游客无法核销")

    for tourist in tourists:
        tour_issue_user_id = _int_or_default(tourist.get("id"), 0)
        if tour_issue_user_id <= 0:
            continue
        existing = fetch_one(
            "SELECT id FROM tp_tour_write_off WHERE tour_issue_user_id=%s LIMIT 1",
            (tour_issue_user_id,),
        )
        if existing:
            continue
        enstr_salt = f"mock-{coupon_issue_user_id}-{tour_issue_user_id}-{now_ts}"
        execute(
            "INSERT INTO tp_tour_write_off "
            "(create_time, update_time, tour_issue_user_id, tour_coupon_group_id, mid, tid, userid, orderid, enstr_salt, "
            "coupon_title, coupon_price, use_min_price, time_start, time_end, uuno, coupon_issue_id, accounting_id, type, "
            "spot_name, address, longitude, latitude, uid, gid, uw_longitude, uw_latitude, he_longitude, he_latitude) "
            "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            (
                now_ts,
                now_ts,
                tour_issue_user_id,
                coupon_issue_user_id,
                mid,
                _int_or_default(tourist.get("tid"), 0),
                userid,
                orderid,
                enstr_salt,
                _string_or_empty(issue.get("coupon_title")),
                _decimal_or_default(issue.get("coupon_price"), "0.00"),
                _decimal_or_default(issue.get("use_min_price"), "0.00"),
                _int_or_default(issue.get("coupon_time_start"), 0),
                _int_or_default(issue.get("coupon_time_end"), 0),
                _string_or_empty(issue.get("uuno")),
                _int_or_default(issue.get("id"), 0),
                0,
                2,
                "",
                "",
                longitude,
                latitude,
                _int_or_default(tourist.get("uid"), 0),
                0,
                longitude,
                latitude,
                vr_longitude,
                vr_latitude,
            ),
        )

    execute(
        "UPDATE tp_tour_coupon_group SET status=%s, write_use=%s, update_time=%s WHERE id=%s",
        (1, now_ts, now_ts, coupon_issue_user_id),
    )
    execute(
        "UPDATE tp_tour_issue_user SET time_use=%s, status=%s, update_time=%s WHERE tid=%s AND type=%s AND issue_coupon_id=%s",
        (
            now_ts,
            1,
            now_ts,
            _int_or_default(group.get("tid"), 0),
            2,
            _int_or_default(group.get("coupon_issue_id"), 0),
        ),
    )
    return api_success("data success", "核销成功")


@fastapi_app.api_route("/api/user/clock_list", methods=["GET", "POST"])
async def api_user_clock_list(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("用户ID不能为空！")

    system_row = fetch_one("SELECT is_clock_switch FROM tp_system WHERE id=%s", (1,)) or {}
    is_clock_switch = _int_or_default(system_row.get("is_clock_switch"), 0)
    if is_clock_switch == 1:
        rows = fetch_all(
            "SELECT ccc.*, bb.name AS tour_name FROM ("
            "SELECT id, 1 AS tags, clock_time, create_time, images, is_clock, coupon_title, descs, address, tid "
            "FROM tp_tour_write_off WHERE uid=%s AND type=2 "
            "UNION ALL "
            "SELECT id, 2 AS tags, clock_time, create_time, images, is_clock, spot_name AS coupon_title, descs, address, tid "
            "FROM tp_tour_hotel_user_record WHERE uid=%s"
            ") ccc JOIN tp_tour bb ON bb.id = ccc.tid ORDER BY ccc.create_time DESC",
            (uid, uid),
        )
    else:
        rows = fetch_all(
            "SELECT cc.id, 2 AS tags, cc.clock_time, cc.create_time, cc.images, cc.is_clock, cc.spot_name AS coupon_title, "
            "cc.descs, cc.address, cc.tid, bb.name AS tour_name "
            "FROM tp_tour_hotel_user_record cc JOIN tp_tour bb ON bb.id = cc.tid WHERE cc.uid=%s",
            (uid,),
        )
    if not rows:
        return api_error("还没有领取记录")
    return api_success(rows, "请求成功")


@fastapi_app.api_route("/api/user/clock", methods=["GET", "POST"])
async def api_user_clock(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")
    params = await get_params(request)
    if _int_or_default(params.get("clock_uid"), 0) <= 0:
        return api_error("参数异常")
    if _int_or_default(params.get("tour_issue_user_id"), 0) <= 0:
        return api_error("参数异常")
    required_fields = ["spot_name", "images", "address", "longitude", "latitude"]
    if any(not _string_or_empty(params.get(field)) for field in required_fields):
        return api_error("参数异常")

    clock_uid = _int_or_default(params.get("clock_uid"), 0)
    issue_id = _int_or_default(params.get("tour_issue_user_id"), 0)
    row = fetch_one(
        "SELECT id, tid FROM tp_tour_write_off WHERE uid=%s AND id=%s AND type=%s LIMIT 1",
        (clock_uid, issue_id, 2),
    )
    if not row:
        return api_error("信息不存在")

    now_ts = int(time.time())
    agency_user_id = _int_or_default(params.get("agency_user_id"), 0)
    execute(
        "UPDATE tp_tour_write_off SET clock_time=%s, is_clock=%s, spot_name=%s, images=%s, address=%s, descs=%s, "
        "longitude=%s, latitude=%s, gid=%s, update_time=%s WHERE id=%s AND uid=%s",
        (
            now_ts,
            1,
            _string_or_empty(params.get("spot_name")),
            _string_or_empty(params.get("images")),
            _string_or_empty(params.get("address")),
            _string_or_empty(params.get("descs")),
            _float_or_default(params.get("longitude"), 0.0),
            _float_or_default(params.get("latitude"), 0.0),
            agency_user_id if agency_user_id > 0 else 0,
            now_ts,
            issue_id,
            clock_uid,
        ),
    )
    execute(
        "UPDATE tp_tourist SET numbers = numbers + 1, update_time=%s WHERE uid=%s AND tid=%s",
        (now_ts, clock_uid, _int_or_default(row.get("tid"), 0)),
    )
    return api_success("data success", "打卡成功")


@fastapi_app.api_route("/api/user/hotel_clock", methods=["GET", "POST"])
async def api_user_hotel_clock(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")
    params = await get_params(request)
    if _int_or_default(params.get("id"), 0) <= 0:
        return api_error("参数异常")
    required_fields = ["images", "address", "longitude", "latitude"]
    if any(not _string_or_empty(params.get(field)) for field in required_fields):
        return api_error("参数异常")

    record_id = _int_or_default(params.get("id"), 0)
    record = fetch_one(
        "SELECT id, sign_id, is_clock FROM tp_tour_hotel_user_record WHERE id=%s LIMIT 1",
        (record_id,),
    )
    if not record:
        return api_error("信息不存在")
    if _int_or_default(record.get("is_clock"), 0) == 1:
        return api_error("您已经打过卡了，无需操作")

    now_ts = int(time.time())
    agency_user_id = _int_or_default(params.get("agency_user_id"), 0)
    affected = execute(
        "UPDATE tp_tour_hotel_user_record SET clock_time=%s, is_clock=%s, images=%s, address=%s, descs=%s, "
        "longitude=%s, latitude=%s, gid=%s, update_time=%s WHERE id=%s AND is_clock=%s",
        (
            now_ts,
            1,
            _string_or_empty(params.get("images")),
            _string_or_empty(params.get("address")),
            _string_or_empty(params.get("descs")),
            _float_or_default(params.get("longitude"), 0.0),
            _float_or_default(params.get("latitude"), 0.0),
            agency_user_id if agency_user_id > 0 else 0,
            now_ts,
            record_id,
            0,
        ),
    )
    if affected > 0:
        execute(
            "UPDATE tp_tour_hotel_sign SET need_numbers = need_numbers + 1, update_time=%s WHERE id=%s",
            (now_ts, _int_or_default(record.get("sign_id"), 0)),
        )
    return api_success("data success", "打卡成功")


@fastapi_app.api_route("/api/user/feed_back", methods=["GET", "POST"])
async def api_user_feed_back(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("参数异常")
    if not _string_or_empty(params.get("content")):
        return api_error("请输入内容")

    ip = request.client.host if request.client else ""
    now_ts = int(time.time())
    freq = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_feedback WHERE create_ip=%s AND create_time>%s AND uid=%s",
        (ip, now_ts - 600, uid),
    ) or {}
    if _int_or_default(freq.get("n"), 0) >= 3:
        return api_error("对不起, 您的操作过于频繁, 请休息一会儿在来！")

    user = fetch_one("SELECT id, name, mobile FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("未查询到用户信息，请先登录")

    affected = execute(
        "INSERT INTO tp_feedback "
        "(create_time, update_time, status, create_ip, name, uid, content, images, mobile) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            now_ts,
            now_ts,
            0,
            ip,
            _string_or_empty(user.get("name")),
            uid,
            _string_or_empty(params.get("content")),
            _string_or_empty(params.get("images")),
            _string_or_empty(user.get("mobile")),
        ),
    )
    if affected > 0:
        return api_success([], "发布成功")
    return api_error("发布失败")


@fastapi_app.api_route("/api/user/coupon_order", methods=["GET", "POST"])
async def api_user_coupon_order(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("用户ID不能为空！")

    user = fetch_one("SELECT id, uuid, status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    page = max(1, _int_or_default(params.get("page"), 1))
    limit = max(1, _int_or_default(params.get("limit"), 10))
    offset = (page - 1) * limit
    where_sql = ["uuid=%s"]
    where_args: list[Any] = [_string_or_empty(user.get("uuid"))]
    status_raw = params.get("status")
    if status_raw is not None and str(status_raw) != "":
        where_sql.append("payment_status=%s")
        where_args.append(_int_or_default(status_raw, 0))

    total_row = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_coupon_order WHERE " + " AND ".join(where_sql),
        tuple(where_args),
    ) or {}
    total = _int_or_default(total_row.get("n"), 0)
    rows = fetch_all(
        "SELECT * FROM tp_coupon_order WHERE "
        + " AND ".join(where_sql)
        + " ORDER BY create_time DESC LIMIT %s, %s",
        tuple(where_args + [offset, limit]),
    )
    for row in rows:
        row["detail"] = fetch_one(
            "SELECT * FROM tp_coupon_order_item WHERE order_no=%s LIMIT 1",
            (_string_or_empty(row.get("order_no")),),
        )
        _normalize_coupon_order_row(row)
    return api_success(
        _pagination_payload(total=total, page=page, per_page=limit, rows=rows),
        "请求成功",
    )


@fastapi_app.api_route("/api/user/coupon_order_detail", methods=["GET", "POST"])
async def api_user_coupon_order_detail(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("用户ID不能为空！")
    order_no = _string_or_empty(params.get("order_no"))
    if not order_no:
        return api_error("订单ID不能为空")

    user = fetch_one("SELECT id, uuid, status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    order = fetch_one(
        "SELECT * FROM tp_coupon_order WHERE uuid=%s AND order_no=%s LIMIT 1",
        (_string_or_empty(user.get("uuid")), order_no),
    )
    if order:
        order["detail"] = fetch_one(
            "SELECT * FROM tp_coupon_order_item WHERE order_no=%s LIMIT 1",
            (order_no,),
        )
        _normalize_coupon_order_row(order)
    return api_success(order, "请求成功")


@fastapi_app.api_route("/api/user/get_user_coupon_id", methods=["GET", "POST"])
async def api_user_get_user_coupon_id(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("用户ID不能为空！")
    rows = fetch_all(
        "SELECT issue_coupon_id FROM tp_coupon_issue_user WHERE uid=%s",
        (uid,),
    )
    coupon_ids = [_int_or_default(r.get("issue_coupon_id"), 0) for r in rows]
    return api_success(coupon_ids, "请求成功")


@fastapi_app.api_route("/api/user/postTourist", methods=["GET", "POST"])
async def api_user_post_tourist(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("请求方式错误！")
    params = await get_params(request)

    cert_type_map = {
        1: "身份证",
        2: "护照",
        3: "台湾通行证",
        4: "港澳通行证",
        5: "回乡证",
    }
    fullname = _string_or_empty(params.get("fullname"))
    if not fullname:
        return api_error("姓名不能为空！")
    if not _is_chinese_name(fullname):
        return api_error("姓名只能是汉字！")
    if len(fullname) > 10:
        return api_error("姓名长度超出！")

    mobile = _string_or_empty(params.get("mobile"))
    if not mobile:
        return api_error("手机号不能为空！")
    if not _is_valid_mobile(mobile):
        return api_error("手机号格式不符")

    cert_type_raw = params.get("cert_type")
    if cert_type_raw is None or str(cert_type_raw) == "":
        return api_error("证件类型不能为空！")
    cert_type = _int_or_default(cert_type_raw, 0)
    if cert_type not in cert_type_map:
        return api_error("证件类型不存在！")

    cert_id = _string_or_empty(params.get("cert_id"))
    if not cert_id:
        return api_error("证件号不能为空")

    user_id = _int_or_default(request.headers.get("Userid"), 0)
    if user_id <= 0:
        return api_error("用户不能为空！")

    now_ts = int(time.time())
    msg = "添加成功！"
    tourist_id = _int_or_default(params.get("id"), 0)
    if tourist_id > 0:
        affected = execute(
            "UPDATE tp_users_tourist SET mobile=%s, update_time=%s WHERE id=%s",
            (mobile, now_ts, tourist_id),
        )
        if affected < 0:
            return api_error("操作失败")
        msg = "修改成功！"
        return api_success([], msg)

    cert_id_save = cert_id
    if cert_type == 1:
        cert_id_save = cert_id.upper()
        if not _is_valid_idcard(cert_id_save):
            return api_error("身份证号格式不符！")
        has_user = fetch_one(
            "SELECT id FROM tp_users WHERE name=%s AND idcard=%s AND auth_status=%s LIMIT 1",
            (fullname, cert_id_save, 1),
        )
        if not has_user:
            system = fetch_one("SELECT app_code FROM tp_system WHERE id=%s", (1,)) or {}
            if not _string_or_empty(system.get("app_code")) and not _env_enabled("REWRITE_MOCK_IDENTITY", True):
                return api_error("身份认证无法通过")

    affected = execute(
        "INSERT INTO tp_users_tourist "
        "(user_id, fullname, mobile, cert_type, cert_id, status, create_time, update_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
        (user_id, fullname, mobile, cert_type, cert_id_save, 1, now_ts, now_ts),
    )
    if affected <= 0:
        return api_error("操作失败")
    return api_success([], msg)


@fastapi_app.api_route("/api/user/delTourist", methods=["GET", "POST"])
async def api_user_del_tourist(request: Request) -> JSONResponse:
    params = await get_params(request)
    ids = _string_or_empty(params.get("ids"))
    if not ids:
        return api_error("参数错误！")

    user_id = _int_or_default(request.headers.get("Userid"), 0)
    for raw_id in ids.split(","):
        tourist_id = _int_or_default(raw_id, 0)
        if tourist_id > 0:
            execute(
                "DELETE FROM tp_users_tourist WHERE id=%s AND user_id=%s",
                (tourist_id, user_id),
            )
    return api_success([], "删除成功")


@fastapi_app.api_route("/api/user/userClock", methods=["GET", "POST"])
async def api_user_user_clock(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问")
    params = await get_params(request)

    latitude_raw = params.get("latitude")
    longitude_raw = params.get("longitude")
    if latitude_raw is None or longitude_raw is None or _string_or_empty(latitude_raw) == "" or _string_or_empty(longitude_raw) == "":
        return api_error("打卡位置异常")

    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("用户不能为空！")

    user = fetch_one("SELECT id, status, auth_status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")
    if _int_or_default(user.get("auth_status"), 0) != 1:
        return api_error("您还没有认证！暂时无法打卡！")

    qrcode_url = _string_or_empty(params.get("qrcode_url"))
    if not qrcode_url:
        return api_error("二维码信息为空！")
    seller_mark = fetch_one(
        "SELECT * FROM tp_seller_mark_qc WHERE qrcode_url=%s LIMIT 1",
        (qrcode_url,),
    )
    if not seller_mark:
        return api_error("打卡二维码有误！")

    coupon_id = _int_or_default(params.get("couponId"), 0)
    if coupon_id <= 0:
        return api_error("消费卷信息为空！")
    issue_coupon = fetch_one("SELECT id FROM tp_coupon_issue WHERE id=%s LIMIT 1", (coupon_id,))
    if not issue_coupon:
        return api_error("消费卷不存在！")

    seller_id = _int_or_default(seller_mark.get("seller_id"), 0)
    decode_parts = qrcode_url.split("_")
    if decode_parts and decode_parts[0].isdigit() and _int_or_default(decode_parts[0], 0) != seller_id:
        return api_error("打卡二维码有误！")

    seller = fetch_one(
        "SELECT id, status, class_id, address, latitude, longitude FROM tp_seller WHERE id=%s LIMIT 1",
        (seller_id,),
    )
    if not seller:
        return api_error("商户不存在")
    if _int_or_default(seller.get("status"), 0) != 1:
        return api_error("商户已被禁用")

    day_start, day_end = _today_unix_range()
    total_row = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_seller_mark_qc_user_record "
        "WHERE seller_id=%s AND create_time BETWEEN %s AND %s",
        (seller_id, day_start, day_end),
    ) or {}
    threshold = _int_or_default(seller_mark.get("day_threshold_value"), 0)
    if threshold > 0 and _int_or_default(total_row.get("n"), 0) >= threshold:
        return api_error("该商户今日打卡已上限，请明日再试")

    existing = fetch_one(
        "SELECT id FROM tp_seller_mark_qc_user_record "
        "WHERE uid=%s AND seller_id=%s AND create_time BETWEEN %s AND %s LIMIT 1",
        (uid, seller_id, day_start, day_end),
    )
    if existing:
        return api_error("今日已在该商户打卡，请明日再试")

    distance = _calculate_distance_meters(
        _float_or_default(params.get("latitude"), 0.0),
        _float_or_default(params.get("longitude"), 0.0),
        _float_or_default(seller.get("latitude"), 0.0),
        _float_or_default(seller.get("longitude"), 0.0),
    )
    if round(distance, 2) > _float_or_default(seller_mark.get("range"), 0.0):
        return api_error(f"商户位置太远,距离：{round(distance, 2)}米")

    now_ts = int(time.time())
    affected = execute(
        "INSERT INTO tp_seller_mark_qc_user_record "
        "(coupon_id, seller_id, class_id, uid, qc_id, qrcode, mark_location, longitude, latitude, update_time, create_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            coupon_id,
            seller_id,
            _int_or_default(seller.get("class_id"), 0),
            uid,
            _int_or_default(seller_mark.get("id"), 0),
            qrcode_url,
            _string_or_empty(seller.get("address")),
            _float_or_default(params.get("longitude"), 0.0),
            _float_or_default(params.get("latitude"), 0.0),
            now_ts,
            now_ts,
        ),
    )
    if affected > 0:
        return api_success("data success", "打卡成功")
    return api_error("打卡失败，请重新尝试！")


@fastapi_app.api_route("/api/user/getTouristList", methods=["GET", "POST"])
async def api_user_get_tourist_list(request: Request) -> Response:
    query_params = request.query_params
    page_raw = _string_or_empty(query_params.get("page"))
    if not page_raw:
        return _legacy_upload_error_response()

    userid = request.headers.get("Userid") or ""

    try:
        page = int(page_raw)
    except ValueError:
        page = 1

    page_size_raw = _string_or_empty(query_params.get("page_size"))
    if not page_size_raw:
        page_size = 10
    else:
        try:
            page_size = int(page_size_raw)
        except ValueError:
            page_size = 10

    page = max(1, page)
    page_size = max(1, page_size)
    offset = (page - 1) * page_size

    total_row = fetch_one("SELECT COUNT(*) AS n FROM tp_users_tourist WHERE user_id=%s", (userid,)) or {}
    total = int(total_row.get("n") or 0)

    rows = fetch_all(
        "SELECT * FROM tp_users_tourist WHERE user_id=%s LIMIT %s, %s",
        (userid, offset, page_size),
    )

    cert_type_map = {
        "1": "身份证",
        "2": "护照",
        "3": "台湾通行证",
        "4": "港澳通行证",
        "5": "回乡证",
    }
    for row in rows:
        row["cert_type_text"] = cert_type_map.get(str(row.get("cert_type") or ""), "-")

    return api_success(
        _pagination_payload(total=total, page=page, per_page=page_size, rows=rows),
        "",
    )


@fastapi_app.api_route("/api/user/getCertTypeList", methods=["GET", "POST"])
async def api_user_get_cert_type_list() -> JSONResponse:
    return api_success(
        {
            1: "身份证",
            2: "护照",
            3: "台湾通行证",
            4: "港澳通行证",
            5: "回乡证",
        },
        "",
    )


def _screen_area_seed(*, with_name: bool) -> list[dict[str, Any]]:
    names = [
        "榆阳区",
        "横山区",
        "神木市",
        "府谷县",
        "靖边县",
        "定边县",
        "绥德县",
        "米脂县",
        "佳县",
        "吴堡县",
        "清涧县",
        "子洲县",
    ]
    rows: list[dict[str, Any]] = []
    for idx, name in enumerate(names, start=1):
        item: dict[str, Any] = {"id": idx, "value": 0, "writeoff": 0}
        item["name" if with_name else "area"] = name
        rows.append(item)
    return rows


def _ticket_order_payload_by_trade_no(trade_no: str) -> dict[str, Any] | None:
    order = fetch_one(
        "SELECT o.*, s.image AS seller_image, s.nickname AS seller_nickname "
        "FROM tp_ticket_order o LEFT JOIN tp_seller s ON s.id=o.mch_id "
        "WHERE o.trade_no=%s LIMIT 1",
        (trade_no,),
    )
    if not order:
        return None

    detail_list = fetch_all(
        "SELECT * FROM tp_ticket_order_detail WHERE trade_no=%s ORDER BY id ASC",
        (trade_no,),
    )
    rights_map: dict[int, list[dict[str, Any]]] = {}
    if detail_list:
        detail_ids = [int(v.get("id") or 0) for v in detail_list if int(v.get("id") or 0) > 0]
        if detail_ids:
            placeholders = ",".join(["%s"] * len(detail_ids))
            rights_rows = fetch_all(
                f"SELECT * FROM tp_ticket_order_detail_rights WHERE detail_id IN ({placeholders}) ORDER BY id ASC",
                tuple(detail_ids),
            )
            for row in rights_rows:
                rights_map.setdefault(_int_or_default(row.get("detail_id"), 0), []).append(row)

        for detail in detail_list:
            detail["tourist_cert_type_text"] = {
                "1": "身份证",
                "2": "护照",
                "3": "台湾通行证",
                "4": "港澳通行证",
                "5": "回乡证",
            }.get(_string_or_empty(detail.get("tourist_cert_type")), "-")
            detail["refund_status_text"] = {
                "not_refunded": "未退款",
                "fully_refunded": "已退款",
            }.get(_string_or_empty(detail.get("refund_status")), "-")
            detail["qrcode_str"] = f"detail&{_string_or_empty(detail.get('ticket_code'))}&{int(time.time()) + 300}"
            detail["rights_list"] = rights_map.get(_int_or_default(detail.get("id"), 0), [])
            detail.pop("delete_time", None)
            detail.pop("uuid", None)

    out = {
        "id": order.get("id"),
        "trade_no": order.get("trade_no"),
        "out_trade_no": order.get("out_trade_no"),
        "origin_price": order.get("origin_price"),
        "amount_price": order.get("amount_price"),
        "channel": order.get("channel"),
        "create_time": order.get("create_time"),
        "order_status": order.get("order_status"),
        "refund_status": order.get("refund_status"),
        "refund_fee": order.get("refund_fee"),
        "write_off_num": _int_or_default(order.get("writeoff_tourist_num"), 0)
        + _int_or_default(order.get("wirteoff_rights_num"), 0),
        "seller": {
            "image": _string_or_empty(order.get("seller_image")),
            "nickname": _string_or_empty(order.get("seller_nickname")),
        },
        "detail_list": detail_list,
        "qrcode_str": f"order&{_string_or_empty(order.get('trade_no'))}&{int(time.time()) + 300}",
        "rights_qrcode_list": [],
    }
    if detail_list:
        out["ticket_info"] = {
            "id": detail_list[0].get("ticket_id"),
            "title": detail_list[0].get("ticket_title"),
            "date": detail_list[0].get("ticket_date"),
            "cover": detail_list[0].get("ticket_cover"),
            "price": detail_list[0].get("ticket_price"),
            "explain_use": detail_list[0].get("explain_use"),
            "explain_buy": detail_list[0].get("explain_buy"),
        }
    return out


def _query_ticket_details_by_idcard(idcard: str) -> list[dict[str, Any]]:
    rows = fetch_all(
        "SELECT * FROM tp_ticket_order_detail "
        "WHERE ticket_date>=CURDATE() AND tourist_cert_id=%s AND tourist_cert_type=%s "
        "AND enter_time=%s AND refund_status=%s AND refund_progress IN (%s, %s)",
        (idcard, 1, 0, "not_refunded", "init", "refuse"),
    )
    if not rows:
        return []
    trade_nos = sorted({_string_or_empty(v.get("trade_no")) for v in rows if _string_or_empty(v.get("trade_no"))})
    if not trade_nos:
        return []
    placeholders = ",".join(["%s"] * len(trade_nos))
    paid_rows = fetch_all(
        f"SELECT trade_no, order_status FROM tp_ticket_order WHERE trade_no IN ({placeholders})",
        tuple(trade_nos),
    )
    paid_map = {_string_or_empty(v.get("trade_no")): _string_or_empty(v.get("order_status")) for v in paid_rows}
    return [item for item in rows if paid_map.get(_string_or_empty(item.get("trade_no"))) == "paid"]


def _query_ticket_details_by_order_sn(order_sn: str) -> list[dict[str, Any]]:
    order = fetch_one("SELECT trade_no, order_status FROM tp_ticket_order WHERE trade_no=%s LIMIT 1", (order_sn,))
    if not order or _string_or_empty(order.get("order_status")) != "paid":
        return []
    return fetch_all(
        "SELECT * FROM tp_ticket_order_detail "
        "WHERE ticket_date>=CURDATE() AND trade_no=%s "
        "AND enter_time=%s AND refund_status=%s AND refund_progress IN (%s, %s)",
        (_string_or_empty(order.get("trade_no")), 0, "not_refunded", "init", "refuse"),
    )


def _take_ticket_by_codes(*, codes: list[str], request_ip: str) -> tuple[bool, str]:
    if not codes:
        return False, "参数错误！"
    placeholders = ",".join(["%s"] * len(codes))
    details = fetch_all(
        f"SELECT * FROM tp_ticket_order_detail WHERE ticket_code IN ({placeholders}) "
        "AND enter_time=%s AND refund_status=%s",
        tuple(codes + [0, "not_refunded"]),
    )
    if not details:
        return False, "没有找到待取的门票！"

    now_ts = int(time.time())
    for detail in details:
        detail_id = _int_or_default(detail.get("id"), 0)
        trade_no = _string_or_empty(detail.get("trade_no"))
        if detail_id <= 0 or not trade_no:
            continue

        rights_inc = 0
        rights_num = _int_or_default(detail.get("ticket_rights_num"), 0)
        if rights_num > 0:
            rights_rows = fetch_all(
                "SELECT id, detail_id, detail_code FROM tp_ticket_order_detail_rights "
                "WHERE detail_id=%s AND status=%s ORDER BY id ASC",
                (detail_id, 0),
            )
            for right in rights_rows:
                right_id = _int_or_default(right.get("id"), 0)
                if right_id <= 0:
                    continue
                execute(
                    "UPDATE tp_ticket_order_detail_rights "
                    "SET status=%s, writeoff_time=%s, update_time=%s WHERE id=%s AND status=%s",
                    (1, now_ts, now_ts, right_id, 0),
                )
                execute(
                    "INSERT INTO tp_ticket_write_off "
                    "(order_detail_id, order_detail_rights_id, ticket_code, use_device, writeoff_id, writeoff_name, "
                    "use_lat, use_lng, use_address, use_ip, status, create_time) "
                    "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                    (
                        detail_id,
                        right_id,
                        _string_or_empty(right.get("detail_code")),
                        "自助机",
                        0,
                        "自助机取票",
                        1,
                        1,
                        "",
                        request_ip,
                        1,
                        now_ts,
                    ),
                )
                rights_inc += 1
        else:
            execute(
                "INSERT INTO tp_ticket_write_off "
                "(order_detail_id, order_detail_rights_id, ticket_code, use_device, writeoff_id, writeoff_name, "
                "use_lat, use_lng, use_address, use_ip, status, create_time) "
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                (
                    detail_id,
                    0,
                    _string_or_empty(detail.get("ticket_code")),
                    "自助机",
                    0,
                    "自助机取票",
                    1,
                    1,
                    "",
                    request_ip,
                    1,
                    now_ts,
                ),
            )

        execute(
            "UPDATE tp_ticket_order_detail "
            "SET enter_time=%s, writeoff_rights_num=writeoff_rights_num+%s, update_time=%s WHERE id=%s",
            (now_ts, rights_inc, now_ts, detail_id),
        )
        execute(
            "UPDATE tp_ticket_order "
            "SET writeoff_tourist_num=writeoff_tourist_num+1, wirteoff_rights_num=wirteoff_rights_num+%s, update_time=%s "
            "WHERE trade_no=%s",
            (1 if rights_inc > 0 else 0, now_ts, trade_no),
        )
        remain = fetch_one(
            "SELECT id FROM tp_ticket_order_detail "
            "WHERE trade_no=%s AND refund_status=%s AND enter_time=%s LIMIT 1",
            (trade_no, "not_refunded", 0),
        )
        if not remain:
            execute(
                "UPDATE tp_ticket_order SET order_status=%s, update_time=%s WHERE trade_no=%s",
                ("used", now_ts, trade_no),
            )
    return True, "取票成功！"


@fastapi_app.api_route("/api/index/login", methods=["GET", "POST"])
async def api_index_login(request: Request) -> JSONResponse:
    await get_params(request)
    return api_success([], "请求成功")


@fastapi_app.post("/api/index/jia")
async def api_index_jia(request: Request) -> JSONResponse:
    await get_params(request)
    source = "MzI5OWdGUUFoRXY1TkdDeU95QjlWQURNazJydWgyOXRlR3dNaEV2OUtHU21NeE9zYw%3D%3D"
    return api_success(_php_sym_encrypt(source, "bbbbb"), "请求成功")


@fastapi_app.post("/api/index/jie")
async def api_index_jie(request: Request) -> JSONResponse:
    await get_params(request)
    source = "R05RVElHUzVOUS1JLVVPU1pUZWZWQ01MV0NETGU9U2R6UU1nbUFlRVBOTzRhY2dmd1R3R21ZTm5YTmt0SUR3ODNYT09BTGQ3UlQ3YkExaT1IRFROM2FiY3gwOE0tQzlNOVdpPVpTUngt"
    return api_success(_php_sym_decrypt(source, "bbbbb"), "请求成功")


@fastapi_app.post("/api/index/note_index")
async def api_index_note_index(request: Request) -> JSONResponse:
    await get_params(request)
    rows = fetch_all(
        "SELECT * FROM tp_notice WHERE status=%s ORDER BY sort DESC LIMIT 3",
        (1,),
    )
    return api_success(rows, "请求成功")


@fastapi_app.api_route("/api/index/get_area_info", methods=["GET", "POST"])
async def api_index_get_area_info(request: Request) -> Response:
    params = await get_params(request)
    if not params:
        return _legacy_upload_error_response()
    pid = _int_or_default(params.get("pid"), 0)
    rows = fetch_all("SELECT * FROM tp_area WHERE pid=%s", (pid,))
    return api_success(rows, "请求成功")


@fastapi_app.api_route("/api/index/set_user_info", methods=["GET", "POST"])
async def api_index_set_user_info(request: Request) -> Response:
    await get_params(request)
    return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.api_route("/api/index/regeo", methods=["GET", "POST"])
async def api_index_regeo(request: Request) -> Response:
    await get_params(request)
    return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.post("/api/screen/login")
async def api_screen_login(request: Request) -> JSONResponse:
    params = await get_params(request)
    password = _string_or_empty(params.get("password"))
    if not password:
        return api_error("参数错误")
    system = fetch_one("SELECT screen_password FROM tp_system WHERE id=%s", (1,)) or {}
    screen_password = _string_or_empty(system.get("screen_password"))
    if not screen_password:
        return api_error("请前往运营后台设置大屏访问密码")
    if password != screen_password:
        return api_error("密码错误")
    token = f"mock.{_md5_hex_local(password)[:8]}.{int(time.time())}"
    return api_success({"token": token}, "登录成功")


@fastapi_app.post("/api/screen/index")
async def api_screen_index(request: Request) -> Response:
    params = await get_params(request)
    mainline_enabled = _allow_route_mainline(request, "REWRITE_ENABLE_SCREEN_INDEX")
    if not params:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("参数错误")
    if not mainline_enabled:
        return _legacy_upload_error_response()
    data: dict[str, Any] = {}

    issue_total = fetch_one("SELECT COALESCE(SUM(coupon_price * total_count), 0) AS n FROM tp_coupon_issue") or {}
    writeoff_total = fetch_one("SELECT COALESCE(SUM(coupon_price), 0) AS n FROM tp_write_off") or {}
    tour_writeoff_total = fetch_one("SELECT COALESCE(SUM(coupon_price), 0) AS n FROM tp_tour_write_off") or {}
    day_start, day_end = _today_unix_range()
    writeoff_today = fetch_one(
        "SELECT COALESCE(SUM(coupon_price), 0) AS n FROM tp_write_off WHERE create_time BETWEEN %s AND %s",
        (day_start, day_end),
    ) or {}
    tour_writeoff_today = fetch_one(
        "SELECT COALESCE(SUM(coupon_price), 0) AS n FROM tp_tour_write_off WHERE create_time BETWEEN %s AND %s",
        (day_start, day_end),
    ) or {}
    data["toplist"] = [
        {"name": "累计发放金额", "number": _float_or_default(issue_total.get("n"), 0.0)},
        {
            "name": "累计核销金额",
            "number": _float_or_default(writeoff_total.get("n"), 0.0) + _float_or_default(tour_writeoff_total.get("n"), 0.0),
        },
        {
            "name": "今日核销金额",
            "number": _float_or_default(writeoff_today.get("n"), 0.0) + _float_or_default(tour_writeoff_today.get("n"), 0.0),
        },
    ]

    map_data = _screen_area_seed(with_name=True)
    area_rows = fetch_all(
        "SELECT s.area, COUNT(w.id) AS total "
        "FROM tp_write_off w LEFT JOIN tp_seller s ON w.mid=s.id GROUP BY s.area",
    )
    area_count = {_int_or_default(v.get("area"), 0): _int_or_default(v.get("total"), 0) for v in area_rows}
    for item in map_data:
        item["value"] = area_count.get(_int_or_default(item.get("id"), 0), 0)
    data["mapData"] = map_data

    spot_data = _screen_area_seed(with_name=True)
    for item in spot_data:
        item["value"] = []
    spot_rows = fetch_all("SELECT nickname, latitude, longitude, area FROM tp_seller")
    spot_map = {_int_or_default(v.get("id"), 0): v for v in spot_data}
    for row in spot_rows:
        area_id = _int_or_default(row.get("area"), 0)
        if area_id in spot_map:
            spot_map[area_id]["value"].append(row)
    data["spotData"] = spot_data

    issue_count = fetch_one("SELECT COALESCE(SUM(total_count), 0) AS n FROM tp_coupon_issue") or {}
    writeoff_count = fetch_one("SELECT COUNT(*) AS n FROM tp_write_off") or {}
    tour_writeoff_count = fetch_one("SELECT COUNT(*) AS n FROM tp_tour_write_off") or {}
    seller_count = fetch_one("SELECT COUNT(*) AS n FROM tp_seller WHERE status=%s", (1,)) or {}
    verifier_count = fetch_one("SELECT COUNT(*) AS n FROM tp_merchant_verifier WHERE status=%s", (1,)) or {}
    data["list"] = [
        {"id": 1, "name": "发行数量", "number": _int_or_default(issue_count.get("n"), 0), "icons": ""},
        {
            "id": 2,
            "name": "核销数量",
            "number": _int_or_default(writeoff_count.get("n"), 0) + _int_or_default(tour_writeoff_count.get("n"), 0),
            "icons": "",
        },
        {"id": 3, "name": "入住商户", "number": _int_or_default(seller_count.get("n"), 0), "icons": ""},
        {"id": 4, "name": "核验人员", "number": _int_or_default(verifier_count.get("n"), 0), "icons": ""},
    ]

    ages = fetch_all("SELECT age FROM tp_users")
    data_age = [
        {"name": "0~18岁", "value": 0},
        {"name": "18~35岁", "value": 0},
        {"name": "35~55岁", "value": 0},
        {"name": "55以上岁", "value": 0},
    ]
    for row in ages:
        age = _int_or_default(row.get("age"), 0)
        if 0 < age <= 18:
            data_age[0]["value"] += 1
        elif age <= 35:
            data_age[1]["value"] += 1
        elif age <= 55:
            data_age[2]["value"] += 1
        elif age > 55:
            data_age[3]["value"] += 1
    data["dataAge"] = data_age

    total_users = fetch_one("SELECT COUNT(*) AS n FROM tp_users") or {}
    auth_users = fetch_one("SELECT COUNT(*) AS n FROM tp_users WHERE email_validated=%s", (1,)) or {}
    male_users = fetch_one("SELECT COUNT(*) AS n FROM tp_users WHERE sex=%s", (1,)) or {}
    female_users = fetch_one("SELECT COUNT(*) AS n FROM tp_users WHERE sex=%s", (2,)) or {}
    data["dataVali"] = [
        {"name": "总用户量", "value": _int_or_default(total_users.get("n"), 0)},
        {"name": "实名用户量", "value": _int_or_default(auth_users.get("n"), 0)},
    ]
    data["dataSex"] = [
        {"name": "男性", "value": _int_or_default(male_users.get("n"), 0)},
        {"name": "女性", "value": _int_or_default(female_users.get("n"), 0)},
    ]

    coupon_rows = fetch_all(
        "SELECT a.total_count, a.coupon_price, a.id, a.coupon_title, b.title "
        "FROM tp_coupon_issue a LEFT JOIN tp_coupon_class b ON a.cid=b.id",
    )
    write_rows = fetch_all("SELECT coupon_issue_id, COUNT(*) AS total FROM tp_write_off GROUP BY coupon_issue_id")
    write_map = {_int_or_default(v.get("coupon_issue_id"), 0): _int_or_default(v.get("total"), 0) for v in write_rows}
    for row in coupon_rows:
        total_count = _int_or_default(row.get("total_count"), 0)
        price = _float_or_default(row.get("coupon_price"), 0.0)
        write_total = write_map.get(_int_or_default(row.get("id"), 0), 0)
        row["faxing_price"] = round(total_count * price, 2)
        row["writeoff_total"] = write_total
        row["writeoff_price"] = round(write_total * price, 2)
    data["coupon_info_list"] = coupon_rows

    data["listTrend"] = fetch_all(
        "SELECT d.date, IFNULL(r.num,0) AS num, IFNULL(r.price,0) AS price "
        "FROM ("
        "SELECT CURDATE() AS date UNION ALL "
        "SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) UNION ALL "
        "SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) UNION ALL "
        "SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY) UNION ALL "
        "SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY) UNION ALL "
        "SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY) UNION ALL "
        "SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY) UNION ALL "
        "SELECT DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        ") d LEFT JOIN ("
        "SELECT DATE(FROM_UNIXTIME(create_time)) AS create_date, COUNT(*) AS num, SUM(coupon_price) AS price "
        "FROM tp_write_off GROUP BY DATE(FROM_UNIXTIME(create_time))"
        ") r ON r.create_date=d.date ORDER BY d.date ASC"
    )
    return api_success(data, "请求成功")


@fastapi_app.post("/api/screen/list")
async def api_screen_list(request: Request) -> JSONResponse:
    await get_params(request)
    payload: dict[str, Any] = {}
    payload["top_seller_20"] = fetch_all(
        "SELECT COUNT(1) AS total, COALESCE(SUM(aa.coupon_price),0) AS price, "
        "aa.mid, bb.username, bb.nickname, cc.class_name "
        "FROM tp_write_off aa "
        "LEFT JOIN tp_seller bb ON aa.mid=bb.id "
        "LEFT JOIN tp_seller_class cc ON bb.class_id=cc.id "
        "GROUP BY aa.mid ORDER BY total DESC"
    )

    classes = fetch_all("SELECT * FROM tp_coupon_class ORDER BY id ASC")
    coupon_issue_user_20: list[dict[str, Any]] = []
    for item in classes:
        cid = _int_or_default(item.get("id"), 0)
        bucket = dict(item)
        if cid == 3:
            bucket["list"] = fetch_all(
                "SELECT w.*, u.name FROM tp_tour_issue_user w "
                "LEFT JOIN tp_users u ON w.uid=u.id "
                "WHERE w.type=%s ORDER BY w.create_time DESC LIMIT 20",
                (1,),
            )
        elif cid > 0:
            bucket["list"] = fetch_all(
                "SELECT w.*, u.name FROM tp_coupon_issue_user w "
                "LEFT JOIN tp_users u ON w.uid=u.id "
                "WHERE w.issue_coupon_class_id=%s ORDER BY w.create_time DESC LIMIT 20",
                (cid,),
            )
        else:
            bucket["list"] = []
        coupon_issue_user_20.append(bucket)
    payload["top_coupon_issue_user_20"] = coupon_issue_user_20

    top_write_off_20: list[dict[str, Any]] = []
    for item in classes:
        cid = _int_or_default(item.get("id"), 0)
        bucket = dict(item)
        if cid == 3:
            rows = fetch_all(
                "SELECT w.*, i.cid, u.name FROM tp_tour_write_off w "
                "LEFT JOIN tp_coupon_issue i ON w.coupon_issue_id=i.id "
                "LEFT JOIN tp_users u ON w.uid=u.id "
                "WHERE w.type=%s ORDER BY w.create_time DESC LIMIT 20",
                (1,),
            )
        elif cid > 0:
            rows = fetch_all(
                "SELECT w.*, i.cid, u.name FROM tp_write_off w "
                "LEFT JOIN tp_coupon_issue i ON w.coupon_issue_id=i.id "
                "LEFT JOIN tp_users u ON w.uid=u.id "
                "WHERE i.cid=%s ORDER BY w.create_time DESC LIMIT 20",
                (cid,),
            )
        else:
            rows = []
        for row in rows:
            ts = _int_or_default(row.get("create_time"), 0)
            if ts > 0:
                row["new_create_time"] = datetime.fromtimestamp(ts, tz=_TZ_SHANGHAI).strftime("%m月%d %H:%M:%S")
            else:
                row["new_create_time"] = ""
        bucket["list"] = rows
        top_write_off_20.append(bucket)
    payload["top_write_off_20"] = top_write_off_20
    return api_success(payload, "请求成功")


@fastapi_app.post("/api/seller/bindCheckOpenid")
async def api_seller_bind_check_openid(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    mid = _int_or_default(params.get("mid"), 0)
    openid = _string_or_empty(params.get("openid"))
    verifier_id = _int_or_default(params.get("uuid"), 0)

    seller = fetch_one("SELECT id, status FROM tp_seller WHERE id=%s LIMIT 1", (mid,))
    if not seller:
        return api_error("商户不存在")
    if not openid:
        return api_error("参数错误")
    if _int_or_default(seller.get("status"), 0) == 0:
        return api_error("该商户已被禁用")

    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("当前用户不存在")
    if _int_or_default(user.get("status"), 0) == 0:
        return api_error("该用户已被禁用")

    exists = fetch_one("SELECT id FROM tp_merchant_verifier WHERE openid=%s LIMIT 1", (openid,))
    if exists:
        return api_error("您已绑定其他商家!")

    affected = execute(
        "UPDATE tp_merchant_verifier SET openid=%s, uid=%s, update_time=%s WHERE id=%s AND mid=%s",
        (openid, uid, int(time.time()), verifier_id, mid),
    )
    return api_success(1 if affected > 0 else 0, "请求成功")


@fastapi_app.api_route("/api/test/clb", methods=["GET", "POST"])
async def api_test_clb(request: Request) -> Response:
    await get_params(request)
    return Response(status_code=200, content=b"CLB check!!! ", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.api_route("/api/test/index", methods=["GET", "POST"])
async def api_test_index(request: Request) -> Response:
    await get_params(request)
    payload = "test index"
    return Response(status_code=200, content=payload.encode("utf-8"), headers={"content-type": "text/html; charset=UTF-8"})


@fastapi_app.api_route("/api/test/syncdb2", methods=["GET", "POST"])
async def api_test_syncdb2(request: Request) -> Response:
    await get_params(request)
    spec = GOLDEN_BY_ID.get(_LEGACY_TEST_SYNCDB2_CASE_ID)
    if spec is not None and spec.body_type == "raw_base64":
        return _build_stub_response(spec)
    return Response(status_code=404, content=b"", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.api_route("/api/test/tokenTaohua", methods=["GET", "POST"])
async def api_test_token_taohua(request: Request) -> Response:
    await get_params(request)
    if not _allow_route_mainline(request):
        return _legacy_upload_error_response()
    return api_success({"token": "mock-taohua-token"}, "请求成功")


@fastapi_app.api_route("/api/test/rsyncTaohua", methods=["GET", "POST"])
async def api_test_rsync_taohua(request: Request) -> Response:
    await get_params(request)
    return Response(status_code=200, content=b"", headers={"content-type": "text/html; charset=utf-8"})


@fastapi_app.api_route("/api/test/rsyncTaohuaSign", methods=["GET", "POST"])
async def api_test_rsync_taohua_sign(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return _build_json_response({"code": 1, "msg": "请传入有效证件", "time": int(time.time()), "data": ""})
    user = fetch_one("SELECT id, idcard, card_type FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return _build_json_response({"code": 1, "msg": "未检查到用户信息", "time": int(time.time()), "data": user})
    if _int_or_default(user.get("card_type"), 0) != 1:
        return _build_json_response(
            {"code": 1, "msg": "暂不支持其他证件获取信用分", "time": int(time.time()), "data": ""}
        )
    update_data = {"credit_score": 680, "credit_rating": "A", "update_credit": int(time.time())}
    execute(
        "UPDATE tp_users SET credit_score=%s, credit_rating=%s, update_credit=%s WHERE id=%s",
        (update_data["credit_score"], update_data["credit_rating"], update_data["update_credit"], uid),
    )
    return _build_json_response({"code": 0, "msg": "更新成功", "time": int(time.time()), "data": update_data})


@fastapi_app.api_route("/api/test/getUserTaohua", methods=["GET", "POST"])
async def api_test_get_user_taohua(request: Request) -> JSONResponse:
    params = await get_params(request)
    idcard = _string_or_empty(params.get("idcard"))
    if not idcard:
        return _build_json_response({"code": 1, "msg": "请传入有效证件", "time": int(time.time()), "data": ""})
    data = {"idcard": idcard, "credit_score": 680, "credit_rating": "A", "update_credit": int(time.time())}
    return _build_json_response({"code": 0, "msg": "查询成功", "time": int(time.time()), "data": data})


@fastapi_app.api_route("/api/ticket/create_file", methods=["GET", "POST"])
async def api_ticket_create_file(request: Request) -> Response:
    return await api_notify_create_file(request)


@fastapi_app.api_route("/api/ticket/getTravelOrderDetail", methods=["GET", "POST"])
async def api_ticket_get_travel_order_detail(request: Request) -> JSONResponse:
    if request.method.upper() != "GET":
        return api_error("请求方式错误！")
    params = await get_params(request)
    trade_no = _string_or_empty(params.get("trade_no"))
    if not trade_no:
        return api_error("缺少订单参数")
    payload = _ticket_order_payload_by_trade_no(trade_no)
    return api_success(payload, "")


@fastapi_app.api_route("/api/ticket/travelOrderPay", methods=["GET", "POST"])
async def api_ticket_travel_order_pay(request: Request) -> JSONResponse:
    params = await get_params(request)
    code = _string_or_empty(params.get("code"))
    trade_no = _string_or_empty(params.get("trade_no"))
    if not code or not trade_no:
        return api_error("缺少参数！")
    order = fetch_one("SELECT * FROM tp_ticket_order WHERE trade_no=%s LIMIT 1", (trade_no,))
    if not order:
        return api_error("订单不存在")
    status = _string_or_empty(order.get("order_status"))
    if status == "paid":
        return api_error("该订单已经支付")
    if status == "used":
        return api_error("该订单已经使用")
    if status == "cancelled":
        return api_error("该订单已经取消")
    if status == "refunded":
        return api_error("该订单已经退款")
    openid = _openid_from_code(code)
    execute("UPDATE tp_ticket_order SET openid=%s, update_time=%s WHERE id=%s", (openid, int(time.time()), order.get("id")))
    data = {
        "pay": _mock_pay_payload(_string_or_empty(order.get("type")) or "miniapp", trade_no),
        "trade_no": trade_no,
        "amount_price": _fmt_money_2(order.get("amount_price")),
    }
    return api_success(data, "构建支付成功")


@fastapi_app.post("/api/user/guide_tour")
async def api_user_guide_tour(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("用户ID不能为空！")
    guides = fetch_all(
        "SELECT id, create_time, update_time, sort, status, name, mobile, certificates, tid, mid, uid "
        "FROM tp_guide WHERE uid=%s AND status=%s ORDER BY create_time DESC",
        (uid, 1),
    )
    for row in guides:
        tid = _int_or_default(row.get("tid"), 0)
        row["tour"] = fetch_one("SELECT * FROM tp_tour WHERE id=%s LIMIT 1", (tid,)) if tid > 0 else None
        row["tourist"] = fetch_all(
            "SELECT id, create_time, update_time, sort, status, name, mobile, tid, mid, uid, contract, insurance, "
            "tour_receive_time, numbers, tour_writeoff_time "
            "FROM tp_tourist WHERE tid=%s",
            (tid,),
        )
    return api_success(guides, "请求成功")


@fastapi_app.post("/api/user/hotel_tour")
async def api_user_hotel_tour(request: Request) -> JSONResponse:
    params = await get_params(request)
    tid = _int_or_default(params.get("tid"), 0)
    if tid <= 0:
        return api_error("团ID不能为空！")
    signs = fetch_all(
        "SELECT * FROM tp_tour_hotel_sign WHERE tid=%s ORDER BY create_time DESC",
        (tid,),
    )
    for row in signs:
        sign_id = _int_or_default(row.get("id"), 0)
        records = fetch_all(
            "SELECT r.*, u.name AS users_name, u.mobile AS users_mobile, u.nickname AS users_nickname "
            "FROM tp_tour_hotel_user_record r LEFT JOIN tp_users u ON r.uid=u.id "
            "WHERE r.sign_id=%s ORDER BY r.id DESC",
            (sign_id,),
        )
        for item in records:
            item["users"] = {
                "name": _string_or_empty(item.pop("users_name", "")),
                "mobile": _string_or_empty(item.pop("users_mobile", "")),
                "nickname": _string_or_empty(item.pop("users_nickname", "")),
            }
        row["tour_hotel_user_record"] = records
    return api_success(signs, "请求成功")


@fastapi_app.post("/api/user/tour_coupon")
async def api_user_tour_coupon(request: Request) -> JSONResponse:
    params = await get_params(request)
    tid = _int_or_default(params.get("tid"), 0)
    if tid <= 0:
        return api_error("团ID不能为空！")
    rows = fetch_all(
        "SELECT * FROM tp_tour_coupon_group WHERE tid=%s AND cid<>%s AND is_receive=%s",
        (tid, 3, 1),
    )
    for row in rows:
        row["tour"] = fetch_one("SELECT * FROM tp_tour WHERE id=%s LIMIT 1", (_int_or_default(row.get("tid"), 0),))
        row["couponIssue"] = fetch_one(
            "SELECT * FROM tp_coupon_issue WHERE id=%s LIMIT 1",
            (_int_or_default(row.get("coupon_issue_id"), 0),),
        )
        row["couponClass"] = fetch_one(
            "SELECT * FROM tp_coupon_class WHERE id=%s LIMIT 1",
            (_int_or_default(row.get("cid"), 0),),
        )
    return api_success(rows, "请求成功")


@fastapi_app.api_route("/api/user/encryptAES", methods=["GET", "POST"])
async def api_user_encrypt_aes(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")
    params = await get_params(request)
    group_id = _int_or_default(params.get("id"), 0)
    salt = _string_or_empty(params.get("salt"))
    if group_id <= 0 or not salt:
        return api_error("参数异常")
    issue = fetch_one(
        "SELECT * FROM tp_tour_coupon_group WHERE id=%s AND enstr_salt=%s LIMIT 1",
        (group_id, salt),
    )
    if not issue:
        return api_error("数据异常，禁止访问")
    write_off_status = 1 if _int_or_default(issue.get("status"), 0) == 1 else 0
    now_ts = int(time.time())
    if _int_or_default(issue.get("code_time_expire"), 0) < now_ts:
        qrcode_url = _php_sym_encrypt(_string_or_empty(issue.get("enstr_salt")), str(group_id))
        execute(
            "UPDATE tp_tour_coupon_group SET qrcode_url=%s, code_time_create=%s, code_time_expire=%s, update_time=%s WHERE id=%s",
            (qrcode_url, now_ts, now_ts + 300, now_ts, group_id),
        )
        return api_success({"id": group_id, "qrcode_url": qrcode_url, "write_off_status": write_off_status}, "success")
    return api_success(
        {
            "id": _int_or_default(issue.get("id"), 0),
            "qrcode_url": _string_or_empty(issue.get("qrcode_url")),
            "write_off_status": write_off_status,
        },
        "success",
    )


@fastapi_app.api_route("/api/user/add_sign_record", methods=["GET", "POST"])
async def api_user_add_sign_record(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("禁止访问！")
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    tid = _int_or_default(params.get("tid"), 0)
    if uid <= 0 or tid <= 0:
        return api_error("参数异常")
    if not _string_or_empty(params.get("latitude")) or not _string_or_empty(params.get("longitude")):
        return api_error("地理坐标不能为空")
    hotel_name = _string_or_empty(params.get("hotel_name"))
    if not hotel_name:
        return api_error("请输入酒店名称")

    tourists = fetch_all("SELECT id, uid FROM tp_tourist WHERE tid=%s", (tid,))
    if not tourists:
        return api_error("旅行团下还没有游客，请前去生成")
    tourist_uids = [_int_or_default(v.get("uid"), 0) for v in tourists]
    if any(v <= 0 for v in tourist_uids):
        return api_error("还有游客未绑定用户信息，暂时无法生成")

    now_ts = int(time.time())
    limit_row = fetch_one(
        "SELECT COUNT(*) AS n FROM tp_tour_hotel_sign WHERE tid=%s AND create_time>%s",
        (tid, now_ts - 43200),
    ) or {}
    if _int_or_default(limit_row.get("n"), 0) >= 1:
        return api_error("12小时内只能生成一次打卡记录")

    no = _next_order_no()
    mid = _int_or_default(params.get("mid"), 0)
    execute(
        "INSERT INTO tp_tour_hotel_sign "
        "(create_time, update_time, status, no, uid, tid, mid, remark, need_numbers, tourist_numbers, longitude, latitude, hotel_name) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            now_ts,
            now_ts,
            1,
            no,
            uid,
            tid,
            mid,
            _string_or_empty(params.get("remark")),
            0,
            len(tourists),
            _float_or_default(params.get("longitude"), 0.0),
            _float_or_default(params.get("latitude"), 0.0),
            hotel_name,
        ),
    )
    sign = fetch_one("SELECT id FROM tp_tour_hotel_sign WHERE no=%s ORDER BY id DESC LIMIT 1", (no,)) or {}
    sign_id = _int_or_default(sign.get("id"), 0)
    if sign_id <= 0:
        return api_error("创建失败")
    for tourist in tourists:
        execute(
            "INSERT INTO tp_tour_hotel_user_record "
            "(create_time, update_time, sign_id, is_clock, clock_time, spot_name, images, address, longitude, latitude, descs, tid, uid, guid, gid) "
            "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            (
                now_ts,
                now_ts,
                sign_id,
                0,
                0,
                hotel_name,
                "",
                "",
                0,
                0,
                "",
                tid,
                _int_or_default(tourist.get("uid"), 0),
                uid,
                0,
            ),
        )
    return api_success("data success", "创建成功")


@fastapi_app.post("/api/user/addguest")
async def api_user_addguest(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    mid = _int_or_default(params.get("mid"), 0)
    if uid <= 0:
        return api_error("请登录")
    if mid <= 0:
        return api_error("旅行社信息异常")
    mid_sub = _int_or_default(params.get("mid_sub"), 0)

    where_sql = "uid=%s AND mid=%s"
    where_args: list[Any] = [uid, mid]
    if mid_sub > 0:
        where_sql += " AND mid_sub=%s"
        where_args.append(mid_sub)

    exists = fetch_one("SELECT id FROM tp_guest WHERE " + where_sql + " LIMIT 1", tuple(where_args))
    if exists:
        return api_error("您已经报名了")

    user = fetch_one(
        "SELECT id, name, mobile, openid, headimgurl, idcard, nickname FROM tp_users WHERE id=%s LIMIT 1",
        (uid,),
    )
    if not user:
        return api_error("请先注册")

    now_ts = int(time.time())
    affected = execute(
        "INSERT INTO tp_guest "
        "(status, update_time, create_time, openid, name, headimgurl, mobile, idcard, nickname, mid, mid_sub, uid) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            1,
            now_ts,
            now_ts,
            _string_or_empty(user.get("openid")),
            _string_or_empty(user.get("name")),
            _string_or_empty(user.get("headimgurl")),
            _string_or_empty(user.get("mobile")),
            _string_or_empty(user.get("idcard")),
            _string_or_empty(user.get("nickname")),
            mid,
            mid_sub,
            uid,
        ),
    )
    if affected > 0:
        return api_success([], "报名成功")
    return api_error("报名失败")


@fastapi_app.api_route("/api/user/saveDelivery", methods=["GET", "POST"])
async def api_user_save_delivery(request: Request) -> JSONResponse:
    params = await get_params(request)
    if request.method.upper() != "POST":
        return api_error("禁止访问")

    uid = _int_or_default(params.get("uid"), 0)
    if uid <= 0:
        return api_error("用户不能为空！")
    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")

    if not _string_or_empty(params.get("delivery_user")):
        return api_error("收货姓名不能为空")
    if not _string_or_empty(params.get("delivery_address")):
        return api_error("收货地址不能为空")
    if not _string_or_empty(params.get("delivery_phone")):
        return api_error("收货手机号不能为空")

    issue_user_id = _int_or_default(params.get("coupon_issue_user_id"), 0)
    if issue_user_id <= 0:
        return api_error("用户领取记录有误！")
    issue_user = fetch_one("SELECT * FROM tp_coupon_issue_user WHERE id=%s LIMIT 1", (issue_user_id,))
    if not issue_user:
        return api_error("该消费券异常")
    if _int_or_default(issue_user.get("status"), 0) == 1:
        return api_error("该消费券已使用")
    if _int_or_default(issue_user.get("status"), 0) == 2:
        return api_error("该消费券已过期")
    if _string_or_empty(issue_user.get("is_fail")) == "0":
        return api_error("该消费券无效")

    issue = fetch_one(
        "SELECT id, is_permanent, coupon_time_start, coupon_time_end, day FROM tp_coupon_issue WHERE id=%s LIMIT 1",
        (_int_or_default(issue_user.get("issue_coupon_id"), 0),),
    )
    if not issue:
        return api_error("该消费券已无法使用")

    now_ts = int(time.time())
    if _int_or_default(issue.get("is_permanent"), 0) == 2:
        if _int_or_default(issue.get("coupon_time_start"), 0) > now_ts:
            return api_error("该消费券还未到使用时段")
        if _int_or_default(issue.get("coupon_time_end"), 0) < now_ts:
            return api_error("该消费券已过使用时段")
    if _int_or_default(issue.get("is_permanent"), 0) == 3:
        receive_time = _int_or_default(issue_user.get("create_time"), 0)
        if receive_time + _int_or_default(issue.get("day"), 0) * 86400 < now_ts:
            return api_error("该消费券已经过期")

    execute(
        "UPDATE tp_coupon_issue_user SET delivery_user=%s, delivery_address=%s, delivery_phone=%s, "
        "delivery_input_time=%s, update_time=%s WHERE id=%s",
        (
            _string_or_empty(params.get("delivery_user")),
            _string_or_empty(params.get("delivery_address")),
            _string_or_empty(params.get("delivery_phone")),
            now_ts,
            now_ts,
            issue_user_id,
        ),
    )
    return api_success("data success", "提交成功")


@fastapi_app.api_route("/api/user/getLogisticsInformation", methods=["GET", "POST"])
async def api_user_get_logistics_information(request: Request) -> JSONResponse:
    params = await get_params(request)
    uid = _int_or_default(params.get("uid"), 0)
    issue_user_id = _int_or_default(params.get("coupon_issue_user_id"), 0)
    tracking_number = _string_or_empty(params.get("tracking_number"))
    if uid <= 0:
        return api_error("用户不能为空！")
    user = fetch_one("SELECT id, status FROM tp_users WHERE id=%s LIMIT 1", (uid,))
    if not user:
        return api_error("用户不存在")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("用户已被禁用")
    if not tracking_number:
        return api_error("快递单号不能为空")
    if issue_user_id <= 0:
        return api_error("用户领取记录有误！")

    row = fetch_one(
        "SELECT * FROM tp_logistics_information WHERE coupon_issue_user_id=%s AND tracking_number=%s "
        "ORDER BY id DESC LIMIT 1",
        (issue_user_id, tracking_number),
    )
    if not row:
        row = {
            "coupon_issue_user_id": issue_user_id,
            "tracking_number": tracking_number,
            "code": 200,
            "msg": "success",
            "data": [],
            "delivery_status": 0,
            "issign": 0,
            "exp_type": "",
            "exp_name": "",
            "courier": "",
            "courierPhone": "",
        }
    if _int_or_default(row.get("issign"), 0) == 1:
        execute(
            "UPDATE tp_coupon_issue_user SET status=%s, time_use=%s, update_time=%s WHERE id=%s AND status=%s",
            (1, int(time.time()), int(time.time()), issue_user_id, 0),
        )
    return api_success(row, "查询成功")


def _json_list(value: Any) -> list[dict[str, Any]] | None:
    if isinstance(value, list):
        return [v for v in value if isinstance(v, dict)]
    try:
        parsed = json.loads(str(value or ""))
    except Exception:
        return None
    if not isinstance(parsed, list):
        return None
    return [v for v in parsed if isinstance(v, dict)]


def _json_object(value: Any) -> dict[str, Any] | None:
    if isinstance(value, dict):
        return value
    try:
        parsed = json.loads(str(value or ""))
    except Exception:
        return None
    return parsed if isinstance(parsed, dict) else None


def _ticket_price_rows_by_mid(*, mid: int, one_day: str, channel: str) -> list[dict[str, Any]]:
    where = ["seller_id=%s"]
    args: list[Any] = [mid]
    if one_day:
        where.append("date=%s")
        args.append(one_day)
    else:
        where.append("date=%s")
        args.append(datetime.now(_TZ_SHANGHAI).strftime("%Y-%m-%d"))

    fields = ["ticket_id", "stock", "total_stock", "date"]
    if channel == "online":
        fields.append("online_price AS price")
    elif channel == "casual":
        fields.append("casual_price AS price")
    elif channel == "team":
        fields.append("team_price AS price")

    rows = fetch_all(
        "SELECT " + ", ".join(fields) + " FROM tp_ticket_price WHERE " + " AND ".join(where),
        tuple(args),
    )
    if not rows:
        return rows
    ticket_ids = sorted({_int_or_default(v.get("ticket_id"), 0) for v in rows if _int_or_default(v.get("ticket_id"), 0) > 0})
    ticket_map: dict[int, dict[str, Any]] = {}
    if ticket_ids:
        placeholders = ",".join(["%s"] * len(ticket_ids))
        ticket_rows = fetch_all(f"SELECT * FROM tp_ticket WHERE id IN ({placeholders})", tuple(ticket_ids))
        ticket_map = {_int_or_default(v.get("id"), 0): v for v in ticket_rows}
    for row in rows:
        if "price" in row:
            row["price"] = _fmt_money_2(row.get("price"))
        row["ticket"] = ticket_map.get(_int_or_default(row.get("ticket_id"), 0))
    return rows


def _create_window_or_selfservice_detail_rows(
    *,
    trade_no: str,
    uuid: str,
    seller_id: int,
    ticket_data: list[dict[str, Any]],
    ticket_date: str,
    now_ts: int,
    source: str,
) -> tuple[bool, str]:
    for item in ticket_data:
        ticket_id = _int_or_default(item.get("uuno"), 0)
        number = _int_or_default(item.get("number"), 0)
        if ticket_id <= 0 or number <= 0:
            return False, "门票参数错误"
        ticket = fetch_one("SELECT * FROM tp_ticket WHERE id=%s LIMIT 1", (ticket_id,))
        if not ticket:
            return False, f"未找到相关门票信息{ticket_id}"
        if source == "window" and _int_or_default(ticket.get("status"), 0) != 1:
            return False, "门票未上架！"
        price_row = fetch_one(
            "SELECT * FROM tp_ticket_price WHERE ticket_id=%s AND date=%s LIMIT 1",
            (ticket_id, ticket_date),
        )
        if not price_row:
            return False, f"该门票暂未设置报价: {_string_or_empty(ticket.get('title'))}"
        if _int_or_default(price_row.get("stock"), 0) < number:
            return False, f"当前日期门票{_string_or_empty(ticket.get('title'))} 库存不足"

        tourist_rows = item.get("tourist") if isinstance(item.get("tourist"), list) else []
        if source == "selfservice" and number != len(tourist_rows):
            return False, f"{ticket_id}门票数量与出行人信息不一致"

        rights_rows = fetch_all(
            "SELECT id, title FROM tp_ticket_rights WHERE ticket_id=%s ORDER BY id ASC",
            (ticket_id,),
        )
        rights_num = _int_or_default(ticket.get("rights_num"), 0)

        for idx in range(number):
            tourist = tourist_rows[idx] if idx < len(tourist_rows) else {}
            out_trade_no = ("DMP" + _next_order_no() + f"{idx:02d}")[:32]
            out_refund_no = ("REF" + _next_order_no() + f"{idx:02d}")[:50]
            ticket_code = ("TC" + _next_order_no() + f"{idx:02d}")[:30]
            tourist_fullname = _string_or_empty(tourist.get("tourist_fullname"))
            tourist_cert_type = _int_or_default(tourist.get("tourist_cert_type"), 1)
            tourist_cert_id = _string_or_empty(tourist.get("tourist_cert_id"))
            tourist_mobile = _string_or_empty(tourist.get("tourist_mobile"))
            execute(
                "INSERT INTO tp_ticket_order_detail "
                "(uuid, trade_no, out_trade_no, out_refund_no, ticket_code, tourist_fullname, tourist_cert_type, tourist_cert_id, "
                "tourist_mobile, ticket_number, ticket_cate_id, ticket_id, ticket_title, ticket_date, ticket_cover, ticket_price, "
                "ticket_rights_num, writeoff_rights_num, explain_use, explain_buy, enter_time, refund_status, refund_progress, "
                "create_time, update_time) "
                "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                (
                    uuid,
                    trade_no,
                    out_trade_no,
                    out_refund_no,
                    ticket_code,
                    tourist_fullname,
                    tourist_cert_type,
                    tourist_cert_id,
                    tourist_mobile,
                    1,
                    _int_or_default(ticket.get("category_id"), 0),
                    ticket_id,
                    _string_or_empty(ticket.get("title")),
                    ticket_date,
                    _string_or_empty(ticket.get("cover")),
                    _fmt_money_2(price_row.get("online_price")),
                    rights_num,
                    0,
                    _string_or_empty(ticket.get("explain_use")),
                    _string_or_empty(ticket.get("explain_buy")),
                    0,
                    "not_refunded",
                    "init",
                    now_ts,
                    now_ts,
                ),
            )
            detail_row = fetch_one(
                "SELECT id FROM tp_ticket_order_detail WHERE out_trade_no=%s LIMIT 1",
                (out_trade_no,),
            )
            detail_id = _int_or_default((detail_row or {}).get("id"), 0)
            if detail_id <= 0:
                continue
            if rights_num > 0 and rights_rows:
                for right in rights_rows:
                    code = ("RS" + _next_order_no() + str(_int_or_default(right.get("id"), 0)))[:20]
                    execute(
                        "INSERT INTO tp_ticket_order_detail_rights "
                        "(order_id, detail_id, detail_date, detail_code, rights_title, rights_verifier_ids, rights_id, status, "
                        "create_time, update_time, code, seller_id, user_id, uuid, writeoff_time) "
                        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                        (
                            0,
                            detail_id,
                            ticket_date,
                            ticket_code,
                            _string_or_empty(right.get("title")),
                            "",
                            _int_or_default(right.get("id"), 0),
                            0,
                            now_ts,
                            now_ts,
                            code,
                            seller_id,
                            0,
                            uuid,
                            0,
                        ),
                    )

        execute(
            "UPDATE tp_ticket_price SET stock=stock-%s, update_time=%s "
            "WHERE ticket_id=%s AND date=%s AND stock>=%s",
            (number, now_ts, ticket_id, ticket_date, number),
        )

    return True, ""


@fastapi_app.api_route("/selfservice/index/captcha", methods=["GET", "POST"])
def selfservice_index_captcha(request: Request) -> Response:
    if not _allow_route_mainline(request):
        return _legacy_upload_error_response()
    return _mock_captcha_png_response()


@fastapi_app.post("/selfservice/index/selflogin")
async def selfservice_index_selflogin(request: Request) -> JSONResponse:
    params = await get_params(request)
    username = _string_or_empty(params.get("username"))
    password = _string_or_empty(params.get("password"))
    pubkey = _string_or_empty(params.get("pubkey"))
    code = _string_or_empty(params.get("code"))
    for key, value in {"username": username, "password": password, "pubkey": pubkey, "code": code}.items():
        if not value:
            return api_error(f"{key}不能为空")

    row = fetch_one("SELECT * FROM tp_seller WHERE username=%s LIMIT 1", (username,))
    if not row:
        return api_error("帐号或密码错误")

    lock_time = _parse_lock_timestamp(row.get("lock_time"))
    if lock_time > 0 and int(time.time()) - lock_time < 600:
        return api_error("该账号已被锁定、请10分钟后重试")

    encoded = _string_or_empty(row.get("password"))
    if encoded and _md5_hex_local(password) != encoded:
        err_num = _int_or_default(row.get("err_num"), 0)
        if err_num >= 2:
            execute(
                "UPDATE tp_seller SET lock_time=%s, err_num=%s, update_time=%s WHERE id=%s",
                (datetime.now(_TZ_SHANGHAI).strftime("%Y-%m-%d %H:%M:%S"), 0, int(time.time()), _int_or_default(row.get("id"), 0)),
            )
            return api_error("账号密码错误次数超过3次、请10分钟后重试")
        execute(
            "UPDATE tp_seller SET err_num=err_num+1, update_time=%s WHERE id=%s",
            (int(time.time()), _int_or_default(row.get("id"), 0)),
        )
        remain = 3 - (err_num + 1)
        return api_error(f"账号密码错误、剩余{remain}次、请稍后重试")

    execute("UPDATE tp_seller SET err_num=%s, update_time=%s WHERE id=%s", (0, int(time.time()), _int_or_default(row.get("id"), 0)))
    if _int_or_default(row.get("status"), 0) != 1:
        return api_error("用户已被禁用,请于平台联系")

    now_ts = int(time.time())
    execute(
        "UPDATE tp_seller SET loginnum=loginnum+1, last_login_time=%s, last_login_ip=%s, login_time=%s, login_ip=%s, update_time=%s WHERE id=%s",
        (now_ts, request.client.host if request.client else "", now_ts, request.client.host if request.client else "", now_ts, _int_or_default(row.get("id"), 0)),
    )
    row = fetch_one("SELECT * FROM tp_seller WHERE id=%s LIMIT 1", (_int_or_default(row.get("id"), 0),)) or {}
    no = _string_or_empty(row.get("no"))
    token = f"mock.{no}.{now_ts}"
    expiry = now_ts + 3600 * 24 * 30
    execute(
        "UPDATE tp_seller SET signpass=%s, expiry_time=%s, update_time=%s WHERE id=%s",
        (_md5_hex_local(token + no), expiry, now_ts, _int_or_default(row.get("id"), 0)),
    )
    data = {
        "id": _int_or_default(row.get("id"), 0),
        "no": no,
        "username": _string_or_empty(row.get("username")),
        "login_time": datetime.fromtimestamp(now_ts, tz=_TZ_SHANGHAI).strftime("%Y-%m-%d %H:%M:%S"),
        "login_ip": _string_or_empty(row.get("login_ip")),
        "nickname": _string_or_empty(row.get("nickname")),
        "loginnum": _int_or_default(row.get("loginnum"), 0),
        "token": token,
        "businesstr": _sys_encrypt(str(_int_or_default(row.get("id"), 0)), no),
    }
    return api_success(data, "登录成功")


@fastapi_app.api_route("/window/index/captcha", methods=["GET", "POST"])
def window_index_captcha() -> Response:
    return _mock_captcha_png_response()


@fastapi_app.post("/window/index/system")
async def window_index_system(request: Request) -> JSONResponse:
    await get_params(request)
    return api_success(_build_system_payload(slide_tag="index"), "请求成功")


@fastapi_app.post("/window/index/winlogin")
async def window_index_winlogin(request: Request) -> JSONResponse:
    params = await get_params(request)
    username = _string_or_empty(params.get("username"))
    password = _string_or_empty(params.get("password"))
    pubkey = _string_or_empty(params.get("pubkey"))
    code = _string_or_empty(params.get("code"))
    for key, value in {"username": username, "password": password, "pubkey": pubkey, "code": code}.items():
        if not value:
            return api_error(f"{key}不能为空")

    row = fetch_one("SELECT * FROM tp_ticket_user WHERE username=%s LIMIT 1", (username,))
    if not row:
        return api_error("帐号或密码错误")

    lock_time = _parse_lock_timestamp(row.get("lock_time"))
    if lock_time > 0 and int(time.time()) - lock_time < 600:
        return api_error("该账号已被锁定、请10分钟后重试")

    salted = password + _string_or_empty(row.get("salt"))
    encoded = _string_or_empty(row.get("password"))
    if encoded and _md5_hex_local(salted) != encoded:
        err_num = _int_or_default(row.get("err_num"), 0)
        if err_num >= 2:
            execute(
                "UPDATE tp_ticket_user SET lock_time=%s, err_num=%s, update_time=%s WHERE id=%s",
                (datetime.now(_TZ_SHANGHAI).strftime("%Y-%m-%d %H:%M:%S"), 0, int(time.time()), _int_or_default(row.get("id"), 0)),
            )
            return api_error("账号密码错误次数超过3次、请10分钟后重试")
        execute(
            "UPDATE tp_ticket_user SET err_num=err_num+1, update_time=%s WHERE id=%s",
            (int(time.time()), _int_or_default(row.get("id"), 0)),
        )
        remain = 3 - (err_num + 1)
        return api_error(f"账号密码错误、剩余{remain}次、请稍后重试")

    execute(
        "UPDATE tp_ticket_user SET err_num=%s, update_time=%s WHERE id=%s",
        (0, int(time.time()), _int_or_default(row.get("id"), 0)),
    )
    if _int_or_default(row.get("status"), 0) != 1:
        return api_error("用户已被禁用,请于平台联系")

    now_ts = int(time.time())
    execute(
        "UPDATE tp_ticket_user SET loginnum=loginnum+1, last_login_time=%s, last_login_ip=%s, login_time=%s, login_ip=%s, update_time=%s WHERE id=%s",
        (now_ts, request.client.host if request.client else "", now_ts, request.client.host if request.client else "", now_ts, _int_or_default(row.get("id"), 0)),
    )
    row = fetch_one("SELECT * FROM tp_ticket_user WHERE id=%s LIMIT 1", (_int_or_default(row.get("id"), 0),)) or {}
    seller = fetch_one("SELECT id, nickname FROM tp_seller WHERE id=%s LIMIT 1", (_int_or_default(row.get("mid"), 0),)) or {}
    uuid = _string_or_empty(row.get("uuid"))
    token = f"mock.{uuid}.{now_ts}"
    expiry = now_ts + 3600 * 24 * 30
    execute(
        "UPDATE tp_ticket_user SET signpass=%s, expiry_time=%s, update_time=%s WHERE id=%s",
        (_md5_hex_local(token + uuid), expiry, now_ts, _int_or_default(row.get("id"), 0)),
    )
    data = {
        "id": _int_or_default(row.get("id"), 0),
        "uuid": uuid,
        "username": _string_or_empty(row.get("username")),
        "login_time": datetime.fromtimestamp(now_ts, tz=_TZ_SHANGHAI).strftime("%Y-%m-%d %H:%M:%S"),
        "login_ip": _string_or_empty(row.get("login_ip")),
        "nickname": _string_or_empty(row.get("nickname")),
        "loginnum": _int_or_default(row.get("loginnum"), 0),
        "token": token,
        "m_nickname": _string_or_empty(seller.get("nickname")),
        "m_id": _int_or_default(seller.get("id"), 0),
        "businesstr": _sys_encrypt(str(_int_or_default(seller.get("id"), 0)), "mid"),
    }
    return api_success(data, "登录成功")


@fastapi_app.api_route("/window/ticket/getTicketPirce", methods=["GET", "POST"])
async def window_ticket_get_ticket_price(request: Request) -> JSONResponse:
    params = await get_params(request)
    bstr = _string_or_empty(params.get("bstr"))
    if not bstr:
        return api_error("缺少商户参数")
    mid = _parse_mid_from_bstr(bstr, "mid")
    if mid <= 0:
        return api_error("商户信息错误")
    rows = _ticket_price_rows_by_mid(
        mid=mid,
        one_day=_string_or_empty(params.get("oneday")),
        channel=_string_or_empty(params.get("channel")),
    )
    return api_success(rows, "")


@fastapi_app.api_route("/selfservice/ticket/getTicketPirce", methods=["GET", "POST"])
async def selfservice_ticket_get_ticket_price(request: Request) -> JSONResponse:
    params = await get_params(request)
    bstr = _string_or_empty(params.get("bstr"))
    no = _string_or_empty(params.get("no"))
    if not bstr:
        return api_error("缺少商户参数")
    mid = _parse_mid_from_bstr(bstr, no)
    if mid <= 0:
        return api_error("商户信息错误")
    rows = _ticket_price_rows_by_mid(
        mid=mid,
        one_day=_string_or_empty(params.get("oneday")),
        channel=_string_or_empty(params.get("channel")),
    )
    return api_success(rows, "")


@fastapi_app.post("/window/ticket/pay")
async def window_ticket_pay(request: Request) -> JSONResponse:
    params = await get_params(request)
    ticket_data = _json_list(params.get("data"))
    contact = _json_object(params.get("contact"))
    ticket_date = _string_or_empty(params.get("ticket_date"))
    paytype = _string_or_empty(params.get("paytype"))
    uuid = _string_or_empty(params.get("uuid"))
    if ticket_data is None:
        return api_error("data请求的格式不是json")
    if contact is None:
        return api_error("contact请求的格式不是json")
    required = {
        "data": ticket_data,
        "contact": contact,
        "ticket_date": ticket_date,
        "paytype": paytype,
        "uuid": uuid,
    }
    for key, value in required.items():
        if value in (None, "", []):
            return api_error(f"{key}不能为空")

    user = fetch_one("SELECT id, uuid, mid, status FROM tp_ticket_user WHERE uuid=%s LIMIT 1", (uuid,))
    if not user:
        return api_error("未找到用户")
    if _int_or_default(user.get("status"), 0) != 1:
        return api_error("当前账户已被锁定")

    seller = fetch_one("SELECT id, status FROM tp_seller WHERE id=%s LIMIT 1", (_int_or_default(user.get("mid"), 0),))
    if not seller:
        return api_error("该门票所属商户未找到")
    if _int_or_default(seller.get("status"), 0) != 1:
        return api_error("该商户信息异常")

    total_number = 0
    amount_price = Decimal("0.00")
    for item in ticket_data:
        number = _int_or_default(item.get("number"), 0)
        ticket_id = _int_or_default(item.get("uuno"), 0)
        if number <= 0 or ticket_id <= 0:
            return api_error("门票参数错误")
        quotation = fetch_one(
            "SELECT online_price, stock FROM tp_ticket_price WHERE ticket_id=%s AND date=%s LIMIT 1",
            (ticket_id, ticket_date),
        )
        if not quotation:
            return api_error("当前票价不符,请刷新后再试，或联系客服")
        sign_price = _decimal_or_default(item.get("price"), "0.00") / Decimal(str(number))
        if sign_price.quantize(Decimal("0.01")) != _decimal_or_default(quotation.get("online_price"), "0.00"):
            return api_error("当前票价不符,请刷新后再试，或联系客服")
        if _int_or_default(quotation.get("stock"), 0) < number:
            return api_error("库存不足")
        total_number += number
        amount_price += sign_price * Decimal(str(number))
    if total_number <= 0:
        return api_error("请至少购买一张门票")
    if amount_price <= Decimal("0.00"):
        return api_error("消费券面额至少大于0.01，否则无法调起支付")

    now_ts = int(time.time())
    trade_no = _next_order_no()
    execute(
        "INSERT INTO tp_ticket_order "
        "(openid, uuid, mch_id, trade_no, out_trade_no, channel, type, origin_price, amount_price, contact_man, contact_phone, "
        "contact_certno, order_remark, order_status, refund_status, create_lat, create_lng, create_ip, create_time, update_time, payment_status) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            "",
            uuid,
            _int_or_default(seller.get("id"), 0),
            trade_no,
            "MP" + trade_no,
            "window",
            paytype,
            f"{amount_price:.2f}",
            f"{amount_price:.2f}",
            _string_or_empty(contact.get("contact_man")),
            _string_or_empty(contact.get("contact_phone")),
            _string_or_empty(contact.get("contact_certno")),
            "",
            "paid",
            "not_refunded",
            0,
            0,
            request.client.host if request.client else "",
            now_ts,
            now_ts,
            1,
        ),
    )
    ok, msg = _create_window_or_selfservice_detail_rows(
        trade_no=trade_no,
        uuid=uuid,
        seller_id=_int_or_default(seller.get("id"), 0),
        ticket_data=ticket_data,
        ticket_date=ticket_date,
        now_ts=now_ts,
        source="window",
    )
    if not ok:
        return api_error("创建订单失败" + msg)
    return api_success([], "购票成功")


@fastapi_app.post("/selfservice/ticket/submit")
async def selfservice_ticket_submit(request: Request) -> JSONResponse:
    params = await get_params(request)
    ticket_data = _json_list(params.get("data"))
    contact = _json_object(params.get("contact"))
    ticket_date = _string_or_empty(params.get("ticket_date"))
    no = _string_or_empty(params.get("no"))
    if ticket_data is None:
        return api_error("data请求的格式不是json")
    if contact is None:
        return api_error("contact请求的格式不是json")
    required = {
        "no": no,
        "data": ticket_data,
        "contact": contact,
        "ticket_date": ticket_date,
        "contact_man": _string_or_empty(contact.get("contact_man")),
        "contact_phone": _string_or_empty(contact.get("contact_phone")),
        "contact_certno": _string_or_empty(contact.get("contact_certno")),
    }
    for key, value in required.items():
        if value in (None, "", []):
            return api_error(f"{key}不能为空")

    seller = fetch_one("SELECT id, status FROM tp_seller WHERE no=%s LIMIT 1", (no,))
    if not seller:
        return api_error("未找到商户")
    if _int_or_default(seller.get("status"), 0) != 1:
        return api_error("商户异常")
    if ticket_date < datetime.now(_TZ_SHANGHAI).strftime("%Y-%m-%d"):
        return api_error(f"购买门票日期{ticket_date}已过")

    total_number = 0
    amount_price = Decimal("0.00")
    for item in ticket_data:
        number = _int_or_default(item.get("number"), 0)
        tourists = item.get("tourist") if isinstance(item.get("tourist"), list) else []
        if number != len(tourists):
            return api_error(f"{_string_or_empty(item.get('uuno'))}门票数量与出行人信息不一致")
        ticket_id = _int_or_default(item.get("uuno"), 0)
        ticket = fetch_one(
            "SELECT id, title, seller_id, quota_order, rights_num FROM tp_ticket WHERE id=%s LIMIT 1",
            (ticket_id,),
        )
        if not ticket:
            return api_error(f"未找到相关门票信息{ticket_id}")
        quotation = fetch_one(
            "SELECT online_price, stock FROM tp_ticket_price WHERE ticket_id=%s AND date=%s LIMIT 1",
            (ticket_id, ticket_date),
        )
        if not quotation:
            return api_error("该门票暂未设置报价: " + _string_or_empty(ticket.get("title")))
        if _int_or_default(ticket.get("quota_order"), 0) > 0 and number > _int_or_default(ticket.get("quota_order"), 0):
            return api_error("该门票限每单限购" + str(_int_or_default(ticket.get("quota_order"), 0)) + "张")
        if _int_or_default(quotation.get("stock"), 0) < number:
            return api_error("库存不足")
        sign_price = _decimal_or_default(item.get("price"), "0.00") / Decimal(str(max(1, number)))
        if sign_price.quantize(Decimal("0.01")) != _decimal_or_default(quotation.get("online_price"), "0.00"):
            return api_error("当前票价不符,请联系客服")
        total_number += number
        amount_price += sign_price * Decimal(str(number))
    if total_number <= 0:
        return api_error("请至少购买一张门票")
    if amount_price <= Decimal("0.00"):
        return api_error("消费券面额至少大于0.01，否则无法调起支付")

    now_ts = int(time.time())
    trade_no = _next_order_no()
    execute(
        "INSERT INTO tp_ticket_order "
        "(openid, uuid, mch_id, trade_no, out_trade_no, channel, type, origin_price, amount_price, contact_man, contact_phone, "
        "contact_certno, order_remark, order_status, refund_status, create_lat, create_lng, create_ip, create_time, update_time) "
        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            "",
            "0",
            _int_or_default(seller.get("id"), 0),
            trade_no,
            "MP" + trade_no,
            "selfservice",
            "miniapp",
            f"{amount_price:.2f}",
            f"{amount_price:.2f}",
            _string_or_empty(contact.get("contact_man")),
            _string_or_empty(contact.get("contact_phone")),
            _string_or_empty(contact.get("contact_certno")),
            _string_or_empty(params.get("order_remark")),
            "created",
            "not_refunded",
            0,
            0,
            request.client.host if request.client else "",
            now_ts,
            now_ts,
        ),
    )
    ok, msg = _create_window_or_selfservice_detail_rows(
        trade_no=trade_no,
        uuid="0",
        seller_id=_int_or_default(seller.get("id"), 0),
        ticket_data=ticket_data,
        ticket_date=ticket_date,
        now_ts=now_ts,
        source="selfservice",
    )
    if not ok:
        return api_error("创建订单失败" + msg)

    url = f"https://mock-static.local/selfservice/travel/{trade_no}.png"
    execute("UPDATE tp_ticket_order SET travel_wxapp_qrcode=%s, update_time=%s WHERE trade_no=%s", (url, now_ts, trade_no))
    return api_success({"trade_no": trade_no, "url": url}, "订单添加成功")


@fastapi_app.api_route("/selfservice/ticket/getTravelWxappQrcode", methods=["GET", "POST"])
async def selfservice_ticket_get_travel_wxapp_qrcode(request: Request) -> Response:
    params = await get_params(request)
    mainline_enabled = _allow_route_mainline(request, "REWRITE_ENABLE_SELFSERVICE_TRAVEL_QRCODE")
    if not params:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        return api_error("缺少参数！")
    if not mainline_enabled:
        return _legacy_upload_error_response()
    trade_no = _string_or_empty(params.get("trade_no"))
    if not trade_no:
        return api_error("缺少参数！")
    order = fetch_one("SELECT id, trade_no, travel_wxapp_qrcode FROM tp_ticket_order WHERE trade_no=%s LIMIT 1", (trade_no,))
    if not order:
        return api_error("订单不存在")
    url = _string_or_empty(order.get("travel_wxapp_qrcode"))
    if not url:
        url = f"https://mock-static.local/selfservice/travel/{trade_no}.png"
        execute("UPDATE tp_ticket_order SET travel_wxapp_qrcode=%s, update_time=%s WHERE id=%s", (url, int(time.time()), _int_or_default(order.get("id"), 0)))
    return api_success({"trade_no": trade_no, "url": url}, "查询成功")


@fastapi_app.api_route("/selfservice/ticket/travelOrderPay", methods=["GET", "POST"])
async def selfservice_ticket_travel_order_pay(request: Request) -> JSONResponse:
    params = await get_params(request)
    code = _string_or_empty(params.get("code"))
    trade_no = _string_or_empty(params.get("trade_no"))
    if not code or not trade_no:
        return api_error("缺少参数！")
    order = fetch_one("SELECT * FROM tp_ticket_order WHERE trade_no=%s LIMIT 1", (trade_no,))
    if not order:
        return api_error("订单不存在")
    status = _string_or_empty(order.get("order_status"))
    if status == "paid":
        return api_error("该订单已经支付")
    if status == "used":
        return api_error("该订单已经使用")
    if status == "cancelled":
        return api_error("该订单已经取消")
    if status == "refunded":
        return api_error("该订单已经退款")
    openid = _openid_from_code(code)
    execute("UPDATE tp_ticket_order SET openid=%s, update_time=%s WHERE id=%s", (openid, int(time.time()), _int_or_default(order.get("id"), 0)))
    data = {
        "pay": _mock_pay_payload(_string_or_empty(order.get("type")) or "miniapp", trade_no),
        "trade_no": trade_no,
        "amount_price": _fmt_money_2(order.get("amount_price")),
    }
    return api_success(data, "构建支付成功")


@fastapi_app.api_route("/selfservice/ticket/getTradeNo", methods=["GET", "POST"])
async def selfservice_ticket_get_trade_no(request: Request) -> JSONResponse:
    params = await get_params(request)
    trade_no = _string_or_empty(params.get("trade_no"))
    if not trade_no:
        return api_error("缺少参数！")
    order = fetch_one("SELECT payment_status FROM tp_ticket_order WHERE trade_no=%s LIMIT 1", (trade_no,))
    if not order:
        return api_error("订单不存在")
    return api_success(order.get("payment_status"), "查询成功")


@fastapi_app.api_route("/window/ticket/list", methods=["POST"])
async def window_ticket_list(request: Request) -> JSONResponse:
    params = await get_params(request)
    bstr = _string_or_empty(params.get("bstr"))
    if not bstr:
        return api_error("参数错误！")
    mid = _parse_mid_from_bstr(bstr, "mid")
    if mid <= 0:
        return api_error("商户信息错误")

    keyword = _json_object(params.get("keyword"))
    if keyword is None and _string_or_empty(params.get("keyword")):
        return api_error("请输入有效的搜索条件")
    keyword = keyword or {}
    page = max(1, _int_or_default(params.get("page"), 1))
    limit = max(1, _int_or_default(params.get("limit"), 10))
    offset = (page - 1) * limit

    where = ["mch_id=%s", "channel=%s"]
    args: list[Any] = [mid, "window"]
    for key, value in keyword.items():
        if value in (None, "", []):
            continue
        if key in {"contact_man", "contact_phone"}:
            where.append(f"{key} LIKE %s")
            args.append(f"%{value}%")
        elif key == "order_status":
            where.append("order_status=%s")
            args.append(_string_or_empty(value))
        elif key == "create_time" and isinstance(value, list) and len(value) >= 2:
            try:
                start_ts = int(datetime.strptime(str(value[0]), "%Y-%m-%d").replace(tzinfo=_TZ_SHANGHAI).timestamp())
                end_ts = int(
                    datetime.strptime(str(value[1]) + " 23:59:59", "%Y-%m-%d %H:%M:%S")
                    .replace(tzinfo=_TZ_SHANGHAI)
                    .timestamp()
                )
                where.append("create_time BETWEEN %s AND %s")
                args.extend([start_ts, end_ts])
            except Exception:
                continue

    rows = fetch_all(
        "SELECT * FROM tp_ticket_order WHERE "
        + " AND ".join(where)
        + " ORDER BY create_time DESC, id DESC LIMIT %s, %s",
        tuple(args + [offset, limit]),
    )
    for row in rows:
        row["order_status_text"] = _ticket_order_status_text(row.get("order_status"))
        row["refund_status_text"] = _ticket_refund_status_text(row.get("refund_status"))
    cnt_row = fetch_one("SELECT COUNT(*) AS n FROM tp_ticket_order WHERE " + " AND ".join(where), tuple(args)) or {}
    return api_success({"list": rows, "cnt": _int_or_default(cnt_row.get("n"), 0)}, "查询成功")


@fastapi_app.post("/window/ticket/detail")
async def window_ticket_detail(request: Request) -> JSONResponse:
    params = await get_params(request)
    bstr = _string_or_empty(params.get("bstr"))
    trade_no = _string_or_empty(params.get("trade_no"))
    if not bstr or not trade_no:
        return api_error("参数错误")
    mid = _parse_mid_from_bstr(bstr, "mid")
    if mid <= 0:
        return api_error("商户信息错误")

    order = fetch_one(
        "SELECT * FROM tp_ticket_order WHERE mch_id=%s AND channel=%s AND trade_no=%s LIMIT 1",
        (mid, "window", trade_no),
    )
    if not order:
        return api_error("订单不存在")
    order["order_status_text"] = _ticket_order_status_text(order.get("order_status"))
    order["refund_status_text"] = _ticket_refund_status_text(order.get("refund_status"))
    details = fetch_all("SELECT * FROM tp_ticket_order_detail WHERE trade_no=%s ORDER BY id ASC", (trade_no,))
    for row in details:
        row["refund_progress_text"] = _ticket_refund_progress_text(row.get("refund_progress"))
        row["refund_status_text"] = _ticket_refund_status_text(row.get("refund_status"))
        refund_time = _int_or_default(row.get("refund_time"), 0)
        row["refund_time_text"] = (
            datetime.fromtimestamp(refund_time, tz=_TZ_SHANGHAI).strftime("%Y-%m-%d %H:%M:%S") if refund_time > 0 else ""
        )
    order["detail"] = details
    return api_success(order, "查询成功")


@fastapi_app.post("/window/ticket/stats")
async def window_ticket_stats(request: Request) -> JSONResponse:
    params = await get_params(request)
    bstr = _string_or_empty(params.get("bstr"))
    uuid = _string_or_empty(params.get("uuid"))
    if not bstr or not uuid:
        return api_error("参数错误！")
    mid = _parse_mid_from_bstr(bstr, "mid")
    if mid <= 0:
        return api_error("商户信息错误")
    user = fetch_one("SELECT id, mid FROM tp_ticket_user WHERE uuid=%s LIMIT 1", (uuid,))
    if not user:
        return api_error("未找到用户")
    if _int_or_default(user.get("mid"), 0) != mid:
        return api_error("当前用户信息异常")

    day_start, day_end = _today_unix_range()
    base_where = "mch_id=%s AND channel=%s AND uuid=%s"
    base_args: tuple[Any, ...] = (mid, "window", uuid)
    today_row = fetch_one(
        "SELECT COALESCE(SUM(amount_price - refund_fee), 0) AS total FROM tp_ticket_order "
        f"WHERE {base_where} AND create_time BETWEEN %s AND %s",
        base_args + (day_start, day_end),
    ) or {}
    cash_row = fetch_one(
        "SELECT COALESCE(SUM(amount_price - refund_fee), 0) AS total FROM tp_ticket_order "
        f"WHERE {base_where} AND type=%s AND create_time BETWEEN %s AND %s",
        base_args + ("cash", day_start, day_end),
    ) or {}
    non_cash_row = fetch_one(
        "SELECT COALESCE(SUM(amount_price - refund_fee), 0) AS total FROM tp_ticket_order "
        f"WHERE {base_where} AND type<>%s AND create_time BETWEEN %s AND %s",
        base_args + ("cash", day_start, day_end),
    ) or {}

    chart_rows = fetch_all(
        "SELECT DATE(FROM_UNIXTIME(create_time)) AS ref_date, "
        "SUM(IF(type='cash', amount_price - refund_fee, 0)) AS cash_total, "
        "SUM(IF(type<>'cash', amount_price - refund_fee, 0)) AS cash_not_total "
        "FROM tp_ticket_order WHERE DATE(FROM_UNIXTIME(create_time)) >= CURDATE() - INTERVAL 7 DAY "
        "AND mch_id=%s AND channel=%s AND uuid=%s GROUP BY DATE(FROM_UNIXTIME(create_time))",
        base_args,
    )
    chart_map = {_string_or_empty(v.get("ref_date")): v for v in chart_rows}
    date_list: list[str] = []
    for delta in range(7, -1, -1):
        date_list.append((datetime.now(_TZ_SHANGHAI) - timedelta(days=delta)).strftime("%Y-%m-%d"))
    chart_data: list[dict[str, Any]] = []
    for day in date_list:
        row = chart_map.get(day)
        chart_data.append(
            {
                "ref_date": day,
                "cash_total": _fmt_money_2((row or {}).get("cash_total")) or "0.00",
                "cash_not_total": _fmt_money_2((row or {}).get("cash_not_total")) or "0.00",
            }
        )

    return api_success(
        {
            "today_price": _float_or_default(today_row.get("total"), 0.0),
            "cash_total": _float_or_default(cash_row.get("total"), 0.0),
            "cash_not_total": _float_or_default(non_cash_row.get("total"), 0.0),
            "data_chart": chart_data,
        },
        "查询成功",
    )


@fastapi_app.api_route("/window/ticket/queryTourist", methods=["GET", "POST"])
@fastapi_app.api_route("/selfservice/ticket/queryTourist", methods=["GET", "POST"])
async def ticket_query_tourist(request: Request) -> JSONResponse:
    if request.method.upper() != "GET":
        return api_error("请求方式错误！")
    params = await get_params(request)
    idcard = _string_or_empty(params.get("idcard"))
    if not idcard:
        return api_error("参数错误！")
    rows = _query_ticket_details_by_idcard(idcard)
    return api_success(rows, "查询成功")


@fastapi_app.api_route("/window/ticket/queryOrder", methods=["GET", "POST"])
@fastapi_app.api_route("/selfservice/ticket/queryOrder", methods=["GET", "POST"])
async def ticket_query_order(request: Request) -> JSONResponse:
    if request.method.upper() != "GET":
        return api_error("请求方式错误！")
    params = await get_params(request)
    order_sn = _string_or_empty(params.get("order_sn"))
    if not order_sn:
        return api_error("参数错误！")
    rows = _query_ticket_details_by_order_sn(order_sn)
    return api_success(rows, "查询成功")


@fastapi_app.api_route("/window/ticket/takeTicket", methods=["GET", "POST"])
@fastapi_app.api_route("/selfservice/ticket/takeTicket", methods=["GET", "POST"])
async def ticket_take_ticket(request: Request) -> JSONResponse:
    if request.method.upper() != "POST":
        return api_error("请求方式错误！")
    params = await get_params(request)
    codes = _parse_codes(params.get("codes"))
    ok, msg = _take_ticket_by_codes(codes=codes, request_ip=request.client.host if request.client else "")
    if not ok:
        return api_error(msg)
    return api_success([], msg)
@fastapi_app.api_route("/api/notify/pay_async_notice", methods=["POST"])
@fastapi_app.api_route("/api/notify/pay_async_notice/model/{model}", methods=["POST"])
async def api_notify_pay_async_notice(request: Request, model: str | None = None) -> Response:
    params = await get_params(request)
    mainline_enabled = _allow_route_mainline(request, "REWRITE_ENABLE_NOTIFY_CALLBACKS")
    if not params:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        params = {}
    if not mainline_enabled:
        return _legacy_upload_error_response()
    model_name = _normalize_model_name(model or params.get("model"), default="Coupon")
    out_trade_no = str(params.get("out_trade_no") or "")
    log_line = (
        f"{datetime.now(_TZ_SHANGHAI).strftime('%Y-%m-%d %H:%M:%S')} "
        f"event=pay_async_notice model={model_name} out_trade_no={out_trade_no}"
    )
    _append_text_line(Path("runtime") / "notify" / "pay_async_notice.log", log_line)
    return _wechat_success_xml_response()


@fastapi_app.api_route("/api/notify/refund", methods=["POST"])
@fastapi_app.api_route("/api/notify/refund/model/{model}", methods=["POST"])
async def api_notify_refund(request: Request, model: str | None = None) -> Response:
    params = await get_params(request)
    mainline_enabled = _allow_route_mainline(request, "REWRITE_ENABLE_NOTIFY_CALLBACKS")
    if not params:
        if not mainline_enabled:
            return _legacy_upload_error_response()
        params = {}
    if not mainline_enabled:
        return _legacy_upload_error_response()
    model_name = _normalize_model_name(model or params.get("model"), default="Coupon")
    out_trade_no = str(params.get("out_trade_no") or "")
    log_line = (
        f"{datetime.now(_TZ_SHANGHAI).strftime('%Y-%m-%d %H:%M:%S')} "
        f"event=refund_notify model={model_name} out_trade_no={out_trade_no}"
    )
    _append_text_line(Path("runtime") / "notify" / "refund.log", log_line)
    return _wechat_success_xml_response()


@fastapi_app.api_route("/api/notify/create_file", methods=["GET", "POST"])
async def api_notify_create_file(request: Request) -> Response:
    params = await get_params(request)
    name = str(params.get("name") or "").strip()
    path = str(params.get("path") or "").strip()
    content = params.get("content")
    if not name or not path or content is None:
        return _legacy_upload_error_response()

    target = _safe_runtime_write_path(path, name)
    if target is None:
        return api_error("路径非法")

    _append_text_line(target, str(content))
    return api_success(1, "写入成功")


def _build_stub_response(spec: RecordedResponse) -> Response:
    headers = dict(spec.headers or {})
    status = int(spec.status or 200)

    if spec.body_type == "json":
        return JSONResponse(status_code=status, content=spec.body_json, headers=headers)

    if spec.body_type == "raw_base64":
        return Response(status_code=status, content=spec.body_raw or b"", headers=headers)

    # ignore/unknown
    return Response(status_code=status, content=b"", headers=headers)


@fastapi_app.api_route(
    "/{full_path:path}",
    methods=["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD"],
)
async def catch_all(full_path: str, request: Request) -> Response:
    if not get_stub_enabled():
        return api_error("not found", code=404, data={}, status_code=404)

    path = "/" + full_path
    method = request.method.upper()

    case_id = request.headers.get("X-Golden-Case-Id")
    spec = GOLDEN_BY_ID.get(str(case_id)) if case_id else None
    if spec is None:
        spec = GOLDEN.get((method, path))
    if spec is None:
        # allow `.html` fallback even when middleware is disabled
        if path.endswith(".html"):
            spec = GOLDEN.get((method, path[: -len(".html")]))

    if spec is None:
        return api_error("not found", code=404, data={}, status_code=404)

    return _build_stub_response(spec)


app = StripHtmlSuffixMiddleware(fastapi_app)
