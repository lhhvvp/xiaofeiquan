from __future__ import annotations

import base64
import json
import os
from dataclasses import dataclass
from pathlib import Path
from typing import Any


@dataclass(frozen=True)
class RecordedResponse:
    status: int
    headers: dict[str, str]
    body_type: str
    body_json: Any | None = None
    body_raw: bytes | None = None


def _lower_headers(headers: dict[str, Any] | None) -> dict[str, str]:
    out: dict[str, str] = {}
    for k, v in (headers or {}).items():
        if v is None:
            continue
        out[str(k).lower()] = str(v)
    return out


def load_golden_cases(
    cases_dir: Path,
) -> tuple[dict[tuple[str, str], RecordedResponse], dict[str, RecordedResponse]]:
    by_key: dict[tuple[str, str], RecordedResponse] = {}
    by_id: dict[str, RecordedResponse] = {}

    for p in sorted(cases_dir.rglob("*.json")):
        raw = json.loads(p.read_text(encoding="utf-8"))
        case_id = str(raw.get("id") or p.stem)

        req = raw.get("request") if isinstance(raw.get("request"), dict) else {}
        method = str(req.get("method") or "GET").upper()
        path = str(req.get("path") or "/")

        resp = raw.get("response")
        if not isinstance(resp, dict):
            continue

        status = int(resp.get("status") or 200)
        headers = _lower_headers(resp.get("headers") if isinstance(resp.get("headers"), dict) else {})

        body = resp.get("body") if isinstance(resp.get("body"), dict) else {}
        body_type = str(body.get("type") or "").strip().lower()

        if body_type == "json":
            spec = RecordedResponse(
                status=status,
                headers=headers,
                body_type=body_type,
                body_json=body.get("json"),
            )
            by_id[case_id] = spec
            by_key[(method, path)] = spec
            continue

        if body_type == "raw_base64":
            b64 = str(body.get("base64") or "")
            spec = RecordedResponse(
                status=status,
                headers=headers,
                body_type=body_type,
                body_raw=base64.b64decode(b64.encode("ascii")),
            )
            by_id[case_id] = spec
            by_key[(method, path)] = spec
            continue

        if body_type == "ignore":
            spec = RecordedResponse(
                status=status,
                headers=headers,
                body_type=body_type,
            )
            by_id[case_id] = spec
            by_key[(method, path)] = spec
            continue

        if body_type == "sha256":
            # Baseline file doesn't contain raw bytes; treat as ignored in stub mode.
            spec = RecordedResponse(
                status=status,
                headers=headers,
                body_type="ignore",
            )
            by_id[case_id] = spec
            by_key[(method, path)] = spec
            continue

        # Unknown/empty: still keep status/headers and return empty body.
        spec = RecordedResponse(
            status=status,
            headers=headers,
            body_type="ignore",
        )
        by_id[case_id] = spec
        by_key[(method, path)] = spec

    return by_key, by_id


def get_cases_dirs() -> list[Path]:
    v = (os.environ.get("GOLDEN_CASES_DIR") or "").strip()
    if v:
        parts = [p.strip() for p in v.split(os.pathsep) if p.strip()]
        return [Path(p) for p in parts]
    return [Path(__file__).resolve().parents[2] / "docs" / "rewrite" / "golden" / "cases" / "p0"]


def get_stub_enabled() -> bool:
    v = (os.environ.get("STUB_FROM_GOLDEN") or "1").strip().lower()
    return v not in {"0", "false", "no", "off"}
