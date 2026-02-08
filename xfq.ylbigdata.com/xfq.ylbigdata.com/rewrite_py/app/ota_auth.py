from __future__ import annotations

import json
import os
from dataclasses import dataclass
from typing import Any

from fastapi import Request
from fastapi.responses import JSONResponse, Response

from .ota_crypto import meituan_build_authorization, xc_decrypt, xc_sign_target


def _env(name: str) -> str:
    return (os.environ.get(name) or "").strip()


def _request_uri(request: Request) -> str:
    path = str(request.scope.get("xfq_original_path") or request.url.path or "/")
    query_bytes = request.scope.get("query_string") or b""
    if not query_bytes:
        return path
    # Preserve raw bytes as much as possible (legacy uses $_SERVER['REQUEST_URI']).
    query = query_bytes.decode("latin-1", errors="ignore")
    return f"{path}?{query}"


class MeituanAuthError(Exception):
    pass


def enforce_meituan_ba_sign(request: Request) -> None:
    date = str(request.headers.get("Date") or "")
    authorization = str(request.headers.get("Authorization") or "")

    client_id = _env("MEITUAN_CLIENT_ID")
    client_secret = _env("MEITUAN_CLIENT_SECRET")
    if not client_id or not client_secret:
        raise MeituanAuthError("BA验证错误")

    expected = meituan_build_authorization(
        method=request.method.upper(),
        uri=_request_uri(request),
        date=date,
        client_id=client_id,
        client_secret=client_secret,
    )
    if authorization != expected:
        raise MeituanAuthError("BA验证错误")


def meituan_auth_error_response(message: str) -> Response:
    partner_id = _env("MEITUAN_PARTNER_ID") or "0"
    payload = {"code": 300, "describe": message, "partnerId": str(partner_id)}
    body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
    # Legacy returns JSON as `text/html; charset=UTF-8` via `exit(json_encode(...))`.
    return Response(status_code=200, content=body, media_type="text/html; charset=UTF-8")


@dataclass(frozen=True)
class XcAuthError(Exception):
    result_code: str
    result_message: str


async def enforce_xc_aes_md5_sign(request: Request) -> None:
    raw_body = await request.body()
    try:
        obj = json.loads(raw_body.decode("utf-8"))
    except Exception:
        raise XcAuthError(result_code="0001", result_message="报文解析失败")

    if not isinstance(obj, dict) or "header" not in obj or "body" not in obj:
        raise XcAuthError(result_code="0001", result_message="报文解析失败，缺少header或body")

    header = obj.get("header")
    body_str = obj.get("body")
    if not isinstance(header, dict) or not isinstance(body_str, str):
        raise XcAuthError(result_code="0001", result_message="报文解析失败")

    sign_source = str(header.get("sign") or "")
    account_id_header = str(header.get("accountId") or "")
    request_time = str(header.get("requestTime") or "")
    version = str(header.get("version") or "")
    service_name = str(header.get("serviceName") or "")

    account_id = _env("XC_ACCOUNT_ID")
    sign_key = _env("XC_SIGN_KEY")
    aes_key = _env("XC_AES_KEY")
    aes_iv = _env("XC_AES_IV")

    body_json_str = xc_decrypt(body_str, aes_key=aes_key, aes_iv=aes_iv)
    if body_json_str is None:
        raise XcAuthError(result_code="0001", result_message="报文解析失败:body解密错误")

    if account_id != account_id_header:
        raise XcAuthError(result_code="0003", result_message="供应商账户信息不正确")

    sign_target = xc_sign_target(
        account_id=account_id,
        service_name=service_name,
        request_time=request_time,
        body_str=body_str,
        version=version,
        sign_key=sign_key,
    )
    if sign_source != sign_target:
        raise XcAuthError(result_code="0002", result_message="签名错误")

    request.state.xc_body_json_str = body_json_str


def xc_auth_error_response(err: XcAuthError) -> JSONResponse:
    payload: dict[str, Any] = {
        "header": {
            "resultCode": str(err.result_code),
            "resultMessage": str(err.result_message),
        }
    }
    return JSONResponse(status_code=200, content=payload, headers={"content-type": "application/json; charset=utf-8"})
