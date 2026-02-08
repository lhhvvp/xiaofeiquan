from __future__ import annotations

import base64
import hashlib
import hmac
import json
import re
import time
from typing import Any

from fastapi import Request

from .db import fetch_one
from .responses import api_error


_RE_BEARER = re.compile(r"bearer", re.IGNORECASE)


def _md5_hex(value: str) -> str:
    return hashlib.md5(value.encode("utf-8")).hexdigest()


def _get_request_token(request: Request) -> str | None:
    header = request.headers.get("Token")
    if header is None:
        return None
    token = _RE_BEARER.sub("", str(header)).strip()
    return token or None


def _token_has_3_segments(token: str) -> bool:
    return len(token.split(".")) == 3


def _json_b64url_decode(segment: str) -> dict[str, Any] | None:
    seg = segment.encode("ascii", errors="ignore")
    seg += b"=" * ((4 - (len(seg) % 4)) % 4)
    try:
        raw = base64.urlsafe_b64decode(seg)
        obj = json.loads(raw.decode("utf-8"))
        return obj if isinstance(obj, dict) else None
    except Exception:
        return None


def _b64url_encode(data: bytes) -> str:
    return base64.urlsafe_b64encode(data).rstrip(b"=").decode("ascii")


def _jwt_get_user_id(token: str) -> Any | None:
    parts = token.split(".")
    if len(parts) != 3:
        return None

    header = _json_b64url_decode(parts[0]) or {}
    payload = _json_b64url_decode(parts[1])
    if payload is None:
        return None

    if (header.get("alg") or "").upper() != "HS256":
        return None

    signing_input = f"{parts[0]}.{parts[1]}".encode("ascii", errors="ignore")
    secret = b"coupon"
    sig = hmac.new(secret, signing_input, hashlib.sha256).digest()
    if not hmac.compare_digest(_b64url_encode(sig), parts[2]):
        return None

    issuer = "https://xfq.dianfengcms.com"
    audience = "https://xfq.dianfengcms.com"
    token_id = "3f2g57a92aa"

    if payload.get("iss") != issuer:
        return None

    aud = payload.get("aud")
    if isinstance(aud, list):
        if audience not in aud:
            return None
    elif aud != audience:
        return None

    if payload.get("jti") != token_id:
        return None

    now = int(time.time())
    try:
        exp = int(payload.get("exp") or 0)
        nbf = int(payload.get("nbf") or 0)
    except Exception:
        return None

    if exp and now > exp:
        return None
    if nbf and now < nbf:
        return None

    return payload.get("user_id")


def _api_auth(request: Request) -> None:
    token = _get_request_token(request)
    if not token:
        raise RuntimeError("token不能为空")
    if not _token_has_3_segments(token):
        raise ValueError("token格式错误")

    userid = request.headers.get("Userid")
    if not userid:
        raise KeyError("用户编码不能为空")

    pip = request.headers.get("Pip")
    if pip is not None and str(pip).strip() != "":
        if str(pip).strip() != "LvjUbYFOWNOGAa/YkeXZ4A==":
            raise PermissionError("禁止访问")

        user_id = _jwt_get_user_id(token)
        if str(userid) != str(user_id):
            raise LookupError("token格式错误")
        if not user_id:
            raise TimeoutError("token已过期")
        return

    row = fetch_one(
        "SELECT signpass, expiry_time FROM tp_users WHERE id=%s AND signpass=%s",
        (userid, _md5_hex(token)),
    )
    if not row:
        raise LookupError("token信息错误")

    expiry = int(row.get("expiry_time") or 0)
    if int(time.time()) - expiry > 0:
        raise TimeoutError("token已过期")


def _window_auth(request: Request) -> None:
    token = _get_request_token(request)
    if not token:
        raise RuntimeError("token不能为空")
    if not _token_has_3_segments(token):
        raise ValueError("token格式错误")

    uuid = request.headers.get("Uuid")
    if not uuid:
        raise KeyError("用户编码不能为空")

    row = fetch_one(
        "SELECT signpass, expiry_time FROM tp_ticket_user WHERE uuid=%s AND signpass=%s",
        (uuid, _md5_hex(f"{token}{uuid}")),
    )
    if not row:
        raise LookupError("token信息错误")

    expiry = int(row.get("expiry_time") or 0)
    if int(time.time()) - expiry > 0:
        raise TimeoutError("token已过期")


def _selfservice_auth(request: Request) -> None:
    token = _get_request_token(request)
    if not token:
        raise RuntimeError("token不能为空")
    if not _token_has_3_segments(token):
        raise ValueError("token格式错误")

    no = request.headers.get("No")
    if not no:
        raise KeyError("用户编码不能为空")

    row = fetch_one(
        "SELECT signpass, expiry_time FROM tp_seller WHERE no=%s AND signpass=%s",
        (no, _md5_hex(f"{token}{no}")),
    )
    if not row:
        raise LookupError("token信息错误")

    expiry = int(row.get("expiry_time") or 0)
    if int(time.time()) - expiry > 0:
        raise TimeoutError("token已过期")


def enforce_auth(request: Request, scheme: str) -> None:
    scheme = (scheme or "").strip()
    if scheme == "api-token(Userid)":
        _api_auth(request)
        return
    if scheme == "window-token(Uuid)":
        _window_auth(request)
        return
    if scheme == "selfservice-token(No)":
        _selfservice_auth(request)
        return


def auth_error_response(exc: BaseException) -> Any:
    msg = str(exc)
    if isinstance(exc, RuntimeError) and msg == "token不能为空":
        return api_error("token不能为空", code=112, data=[])
    if isinstance(exc, ValueError) and msg == "token格式错误":
        return api_error("token格式错误", code=112, data=[])
    if isinstance(exc, KeyError) and msg == "用户编码不能为空":
        return api_error("用户编码不能为空", code=112, data=[])
    if isinstance(exc, PermissionError) and msg == "禁止访问":
        return api_error("禁止访问", code=115, data=[])
    if isinstance(exc, LookupError) and msg == "token格式错误":
        return api_error("token格式错误", code=110, data=[])
    if isinstance(exc, LookupError) and msg == "token信息错误":
        return api_error("token信息错误", code=113, data=[])
    if isinstance(exc, TimeoutError) and msg == "token已过期":
        return api_error("token已过期", code=111, data=[])

    # fallback: keep behavior close to legacy "apiError"
    return api_error("token信息错误", code=113, data=[])

