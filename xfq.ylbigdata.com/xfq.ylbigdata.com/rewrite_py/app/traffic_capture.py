from __future__ import annotations

import hashlib
import json
import os
import time
from pathlib import Path
from typing import Any

from fastapi import Request


def _env(name: str, default: str = "") -> str:
    return (os.environ.get(name) or default).strip()


def capture_enabled() -> bool:
    v = _env("MAINTENANCE_CAPTURE", "0").lower()
    return v not in {"", "0", "false", "no", "off"}


def capture_log_path() -> Path:
    v = _env("MAINTENANCE_CAPTURE_LOG", "/tmp/xfq-maintenance-capture.jsonl")
    return Path(v)


def _request_uri(request: Request) -> str:
    path = str(request.scope.get("xfq_original_path") or request.url.path or "/")
    query_bytes = request.scope.get("query_string") or b""
    if not query_bytes:
        return path
    query = query_bytes.decode("latin-1", errors="ignore")
    return f"{path}?{query}"


_SAFE_HEADERS = {
    "host",
    "user-agent",
    "content-type",
    "accept",
    "accept-encoding",
    "accept-language",
    "x-forwarded-for",
    "x-real-ip",
    "x-forwarded-proto",
    "x-forwarded-host",
    "x-forwarded-port",
    "x-request-id",
    "traceparent",
    "tracestate",
    "partnerid",
}


def _safe_header_value(value: str, *, max_len: int = 256) -> str:
    v = value.replace("\r", " ").replace("\n", " ")
    if len(v) > max_len:
        return v[:max_len] + "...(truncated)"
    return v


def build_capture_event(request: Request, body: bytes) -> dict[str, Any]:
    headers_present: list[str] = []
    safe_headers: dict[str, str] = {}
    for k, v in request.headers.items():
        key = str(k).lower()
        headers_present.append(key)
        if key in _SAFE_HEADERS:
            safe_headers[key] = _safe_header_value(str(v))

    content_type = safe_headers.get("content-type") or str(request.headers.get("content-type") or "")
    body_type = "raw"
    if "json" in content_type.lower():
        body_type = "json"
    elif "x-www-form-urlencoded" in content_type.lower():
        body_type = "form"
    elif "multipart/form-data" in content_type.lower():
        body_type = "multipart"

    json_top_keys: list[str] | None = None
    if body_type == "json" and body:
        try:
            obj = json.loads(body.decode("utf-8"))
            if isinstance(obj, dict):
                json_top_keys = sorted(str(k) for k in obj.keys())
        except Exception:
            json_top_keys = None

    return {
        "ts": int(time.time()),
        "method": request.method.upper(),
        "uri": _request_uri(request),
        "path": str(request.url.path or ""),
        "headers_present": sorted(set(headers_present)),
        "safe_headers": safe_headers,
        "body_type": body_type,
        "body_len": len(body),
        "body_sha256": hashlib.sha256(body).hexdigest() if body else "",
        "json_top_keys": json_top_keys,
    }


def append_jsonl(path: Path, event: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    line = json.dumps(event, ensure_ascii=False, separators=(",", ":")).encode("utf-8") + b"\n"
    fd = os.open(str(path), os.O_WRONLY | os.O_CREAT | os.O_APPEND, 0o644)
    try:
        os.write(fd, line)
    finally:
        os.close(fd)

