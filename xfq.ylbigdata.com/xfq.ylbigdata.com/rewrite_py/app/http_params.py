from __future__ import annotations

import json
import urllib.parse
from typing import Any

from fastapi import Request


def _flatten_qs(parsed: dict[str, list[str]]) -> dict[str, str]:
    out: dict[str, str] = {}
    for k, values in (parsed or {}).items():
        if not values:
            out[str(k)] = ""
            continue
        out[str(k)] = str(values[-1])
    return out


async def get_params(request: Request) -> dict[str, Any]:
    params: dict[str, Any] = {}

    # Query params first (body overrides on conflict).
    for k, v in request.query_params.multi_items():
        params[str(k)] = v

    ct = (request.headers.get("content-type") or "").lower()
    if request.method.upper() in {"GET", "HEAD", "OPTIONS"}:
        return params

    body = await request.body()
    if not body:
        return params

    if "application/json" in ct:
        try:
            obj = json.loads(body.decode("utf-8"))
            if isinstance(obj, dict):
                params.update(obj)
        except Exception:
            return params
        return params

    if "application/x-www-form-urlencoded" in ct:
        parsed = urllib.parse.parse_qs(body.decode("utf-8"), keep_blank_values=True)
        params.update(_flatten_qs(parsed))
        return params

    # multipart/form-data not needed for current P0 set
    return params

