#!/usr/bin/env python3
from __future__ import annotations

import argparse
import base64
import dataclasses
import difflib
import hashlib
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any


ENV_PATTERN = re.compile(r"\$\{([A-Za-z_][A-Za-z0-9_]*)\}")


def _expand_env_str(s: str, missing: set[str]) -> str:
    def repl(m: re.Match[str]) -> str:
        name = m.group(1)
        if name not in os.environ:
            missing.add(name)
            return m.group(0)
        return os.environ[name]

    return ENV_PATTERN.sub(repl, s)


def expand_env(obj: Any, *, missing: set[str]) -> Any:
    if isinstance(obj, str):
        return _expand_env_str(obj, missing)
    if isinstance(obj, list):
        return [expand_env(v, missing=missing) for v in obj]
    if isinstance(obj, dict):
        return {k: expand_env(v, missing=missing) for k, v in obj.items()}
    return obj


def normalize_path(value: str) -> str:
    v = (value or "").strip()
    if not v:
        return "/"
    if not v.startswith("/"):
        v = "/" + v
    return v


def pick_content_type(headers: dict[str, str], default: str | None = None) -> str | None:
    for k, v in headers.items():
        if k.lower() == "content-type":
            return v
    return default


def build_query(query_value: Any) -> str:
    if query_value is None:
        return ""
    if isinstance(query_value, str):
        q = query_value.strip()
        if not q:
            return ""
        return q[1:] if q.startswith("?") else q
    if isinstance(query_value, dict):
        # keep insertion order (py3.7+ dict preserves order)
        return urllib.parse.urlencode(list(query_value.items()), doseq=True)
    if isinstance(query_value, list):
        # list of pairs: [[k,v], ...] or [(k,v), ...]
        pairs: list[tuple[str, str]] = []
        for item in query_value:
            if isinstance(item, (list, tuple)) and len(item) == 2:
                pairs.append((str(item[0]), str(item[1])))
        return urllib.parse.urlencode(pairs, doseq=True)
    raise ValueError(f"unsupported query type: {type(query_value)}")


def _multipart_encode(parts: list[dict[str, Any]], fixtures_root: Path) -> tuple[bytes, str]:
    boundary = f"----golden-{int(time.time() * 1000)}-{os.getpid()}"
    boundary_bytes = boundary.encode("utf-8")
    out = bytearray()

    for part in parts:
        name = part.get("name")
        if not name:
            continue

        out.extend(b"--")
        out.extend(boundary_bytes)
        out.extend(b"\r\n")

        if "file_path" in part:
            file_path = Path(str(part["file_path"]))
            if not file_path.is_absolute():
                file_path = fixtures_root / file_path
            filename = part.get("filename") or file_path.name
            content_type = part.get("content_type") or "application/octet-stream"
            data = file_path.read_bytes()

            out.extend(
                f'Content-Disposition: form-data; name="{name}"; filename="{filename}"\r\n'.encode(
                    "utf-8"
                )
            )
            out.extend(f"Content-Type: {content_type}\r\n\r\n".encode("utf-8"))
            out.extend(data)
            out.extend(b"\r\n")
            continue

        value = part.get("value")
        out.extend(
            f'Content-Disposition: form-data; name="{name}"\r\n\r\n'.encode("utf-8")
        )
        out.extend(str(value if value is not None else "").encode("utf-8"))
        out.extend(b"\r\n")

    out.extend(b"--")
    out.extend(boundary_bytes)
    out.extend(b"--\r\n")
    content_type = f"multipart/form-data; boundary={boundary}"
    return bytes(out), content_type


def json_pointer_delete(obj: Any, pointer: str) -> None:
    if not pointer:
        return
    if not pointer.startswith("/"):
        # accept dotted path as a convenience: a.b.c -> /a/b/c
        pointer = "/" + pointer.replace(".", "/")

    tokens = pointer.split("/")[1:]

    def decode(t: str) -> str:
        return t.replace("~1", "/").replace("~0", "~")

    def walk(current: Any, idx: int) -> None:
        if idx >= len(tokens):
            return
        token = decode(tokens[idx])
        last = idx == len(tokens) - 1

        if token == "*":
            if isinstance(current, list):
                for item in current:
                    walk(item, idx + 1)
            elif isinstance(current, dict):
                for item in current.values():
                    walk(item, idx + 1)
            return

        if isinstance(current, dict):
            if token not in current:
                return
            if last:
                current.pop(token, None)
                return
            walk(current[token], idx + 1)
            return

        if isinstance(current, list):
            try:
                i = int(token)
            except ValueError:
                return
            if i < 0 or i >= len(current):
                return
            if last:
                # cannot delete index safely without shifting; set to None
                current[i] = None
                return
            walk(current[i], idx + 1)

    walk(obj, 0)


def drop_keys_recursive(obj: Any, ignore_keys: set[str]) -> Any:
    if isinstance(obj, list):
        return [drop_keys_recursive(v, ignore_keys) for v in obj]
    if isinstance(obj, dict):
        out: dict[str, Any] = {}
        for k, v in obj.items():
            if k in ignore_keys:
                continue
            out[k] = drop_keys_recursive(v, ignore_keys)
        return out
    return obj


@dataclasses.dataclass
class HttpResult:
    status: int
    headers: dict[str, str]
    body: bytes


def http_call(
    *,
    base_url: str,
    method: str,
    path: str,
    query: Any,
    headers: dict[str, str],
    body_spec: dict[str, Any] | None,
    timeout: float,
    fixtures_root: Path,
    use_proxy_env: bool,
) -> HttpResult:
    path = normalize_path(path)
    q = build_query(query)
    url = base_url.rstrip("/") + path
    if q:
        url = url + ("&" if "?" in url else "?") + q

    data: bytes | None = None
    effective_headers = dict(headers or {})

    if body_spec:
        body_type = (body_spec.get("type") or "none").strip().lower()
        if body_type == "none":
            data = None
        elif body_type == "json":
            payload = body_spec.get("json")
            data = json.dumps(payload, ensure_ascii=False).encode("utf-8")
            effective_headers.setdefault("Content-Type", "application/json; charset=utf-8")
        elif body_type == "form":
            form = body_spec.get("form") or {}
            if not isinstance(form, dict):
                raise ValueError("body.form must be an object")
            data = urllib.parse.urlencode(list(form.items()), doseq=True).encode("utf-8")
            effective_headers.setdefault(
                "Content-Type", "application/x-www-form-urlencoded; charset=utf-8"
            )
        elif body_type == "raw":
            if "base64" in body_spec:
                data = base64.b64decode(str(body_spec.get("base64") or ""))
            else:
                data = str(body_spec.get("text") or "").encode("utf-8")
        elif body_type == "multipart":
            parts = body_spec.get("parts") or []
            if not isinstance(parts, list):
                raise ValueError("body.parts must be an array")
            data, content_type = _multipart_encode(parts, fixtures_root)
            effective_headers.setdefault("Content-Type", content_type)
        else:
            raise ValueError(f"unsupported body.type: {body_type}")

    req = urllib.request.Request(url=url, method=method.upper())
    for k, v in (effective_headers or {}).items():
        req.add_header(str(k), str(v))

    opener = (
        urllib.request.build_opener()
        if use_proxy_env
        else urllib.request.build_opener(urllib.request.ProxyHandler({}))
    )

    try:
        with opener.open(req, data=data, timeout=timeout) as resp:
            body = resp.read()
            headers_out: dict[str, str] = {}
            for k, v in resp.headers.items():
                headers_out[k] = v
            return HttpResult(status=resp.status, headers=headers_out, body=body)
    except urllib.error.HTTPError as e:
        body = e.read() if hasattr(e, "read") else b""
        headers_out = dict(getattr(e, "headers", {}) or {})
        return HttpResult(status=int(getattr(e, "code", 0) or 0), headers=headers_out, body=body)


def wait_http_ready(
    *,
    base_url: str,
    timeout_seconds: float,
    timeout_per_try: float,
    use_proxy_env: bool,
    path: str = "/health",
) -> None:
    if timeout_seconds <= 0:
        return

    start = time.time()
    deadline = start + timeout_seconds
    last_err: Exception | None = None

    while time.time() < deadline:
        try:
            http_call(
                base_url=base_url,
                method="GET",
                path=path,
                query=None,
                headers={"User-Agent": "golden-runner/1.0"},
                body_spec=None,
                timeout=timeout_per_try,
                fixtures_root=Path.cwd(),
                use_proxy_env=use_proxy_env,
            )
            return
        except Exception as e:  # noqa: PERF203
            last_err = e
            time.sleep(0.2)

    if last_err is not None:
        waited = int(time.time() - start)
        print(
            f"WARNING: base url not ready after {waited}s: {base_url} ({type(last_err).__name__}: {last_err})",
            file=sys.stderr,
        )


def decode_body_as_json(body: bytes) -> Any | None:
    try:
        return json.loads(body.decode("utf-8"))
    except Exception:
        return None


def format_json(obj: Any) -> str:
    return json.dumps(obj, ensure_ascii=False, indent=2, sort_keys=True) + "\n"


def diff_text(a: str, b: str, fromfile: str, tofile: str) -> str:
    return "".join(
        difflib.unified_diff(
            a.splitlines(keepends=True),
            b.splitlines(keepends=True),
            fromfile=fromfile,
            tofile=tofile,
        )
    )


def to_recorded_response(result: HttpResult) -> dict[str, Any]:
    ct = pick_content_type(result.headers, "")
    parsed_json = decode_body_as_json(result.body) if ct and "json" in ct.lower() else None

    body_obj: dict[str, Any]
    if parsed_json is not None:
        body_obj = {"type": "json", "json": parsed_json}
    else:
        # avoid bloating repo for large bodies
        if len(result.body) > 16 * 1024:
            body_obj = {
                "type": "sha256",
                "sha256": hashlib.sha256(result.body).hexdigest(),
                "length": len(result.body),
            }
        else:
            body_obj = {"type": "raw_base64", "base64": base64.b64encode(result.body).decode("ascii")}

    return {
        "status": result.status,
        "headers": {"Content-Type": ct} if ct else {},
        "body": body_obj,
    }


def compare_results(
    *,
    case_file: Path,
    case_id: str,
    expected: dict[str, Any],
    actual: HttpResult,
    compare: dict[str, Any] | None,
    label_expected: str,
    label_actual: str,
) -> tuple[bool, str]:
    compare = compare or {}
    mode = (compare.get("mode") or "").strip().lower()

    expected_status = int(expected.get("status") or 0)
    if expected_status and actual.status != expected_status:
        return False, f"[{case_id}] status mismatch: {actual.status} != {expected_status} ({case_file})"

    expected_body = expected.get("body") or {}
    expected_body_type = (expected_body.get("type") or "").strip().lower()

    if expected_body_type == "ignore":
        return True, f"[{case_id}] OK (body ignored)"

    if mode == "raw":
        if expected_body_type == "raw_base64":
            exp = base64.b64decode(str(expected_body.get("base64") or ""))
            if exp != actual.body:
                return (
                    False,
                    f"[{case_id}] raw body mismatch (len {len(actual.body)} != {len(exp)}) ({case_file})",
                )
            return True, f"[{case_id}] OK"
        if expected_body_type == "sha256":
            exp_hash = str(expected_body.get("sha256") or "")
            act_hash = hashlib.sha256(actual.body).hexdigest()
            if act_hash != exp_hash:
                return False, f"[{case_id}] sha256 mismatch ({case_file})"
            return True, f"[{case_id}] OK"
        if expected_body_type == "text":
            exp_text = str(expected_body.get("text") or "")
            act_text = actual.body.decode("utf-8", errors="replace")
            if exp_text != act_text:
                d = diff_text(exp_text, act_text, label_expected, label_actual)
                return False, f"[{case_id}] text mismatch ({case_file})\n{d}"
            return True, f"[{case_id}] OK"

    # default to json compare when possible
    exp_json: Any | None = None
    if expected_body_type == "json":
        exp_json = expected_body.get("json")
    elif expected_body_type == "raw_base64":
        try:
            exp_raw = base64.b64decode(str(expected_body.get("base64") or ""))
        except Exception:
            exp_raw = None
        if exp_raw is not None:
            exp_json = decode_body_as_json(exp_raw)
    elif expected_body_type == "text":
        exp_json = decode_body_as_json(str(expected_body.get("text") or "").encode("utf-8"))

    act_json = decode_body_as_json(actual.body)
    if exp_json is None or act_json is None:
        # fallback to raw compare when cannot parse json
        exp_raw = None
        if expected_body_type == "raw_base64":
            exp_raw = base64.b64decode(str(expected_body.get("base64") or ""))
        if exp_raw is not None and exp_raw == actual.body:
            return True, f"[{case_id}] OK"
        return False, f"[{case_id}] response is not JSON, please set compare.mode=raw ({case_file})"

    ignore_keys = set(compare.get("ignore_json_keys") or [])
    ignore_pointers = list(compare.get("ignore_json_pointers") or [])

    exp_norm = drop_keys_recursive(exp_json, ignore_keys)
    act_norm = drop_keys_recursive(act_json, ignore_keys)
    for ptr in ignore_pointers:
        json_pointer_delete(exp_norm, str(ptr))
        json_pointer_delete(act_norm, str(ptr))

    if exp_norm == act_norm:
        return True, f"[{case_id}] OK"

    a = format_json(exp_norm)
    b = format_json(act_norm)
    d = diff_text(a, b, label_expected, label_actual)
    return False, f"[{case_id}] JSON mismatch ({case_file})\n{d}"


def iter_case_files(cases_dir: Path, explicit_files: list[Path]) -> list[Path]:
    if explicit_files:
        return [p if p.is_absolute() else (Path.cwd() / p) for p in explicit_files]
    return sorted(cases_dir.rglob("*.json"))


def main() -> int:
    parser = argparse.ArgumentParser(description="Record/compare golden cases against an HTTP server.")
    parser.add_argument(
        "--repo-root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="Repository root (default: scripts/..).",
    )
    parser.add_argument(
        "--cases-dir",
        type=Path,
        default=None,
        help="Cases directory (default: docs/rewrite/golden/cases/p0).",
    )
    parser.add_argument(
        "--case",
        action="append",
        type=Path,
        default=[],
        help="Run a single case file (repeatable).",
    )
    parser.add_argument("--base-url", required=True, help="Target base url, e.g. http://127.0.0.1:8000")
    parser.add_argument(
        "--diff-against",
        default=None,
        help="Optional base url to diff against (legacy vs new), no file baseline needed.",
    )
    parser.add_argument(
        "--record",
        action="store_true",
        help="Record baseline response from --base-url back into case files.",
    )
    parser.add_argument(
        "--timeout",
        type=float,
        default=20.0,
        help="HTTP timeout seconds (default: 20).",
    )
    parser.add_argument(
        "--fail-fast",
        action="store_true",
        help="Stop at first failure.",
    )
    parser.add_argument(
        "--use-proxy-env",
        action="store_true",
        help="Respect HTTP_PROXY/HTTPS_PROXY env (default: disabled for deterministic local replay).",
    )
    parser.add_argument(
        "--wait-ready-seconds",
        type=float,
        default=20.0,
        help="Wait for target to accept connections before running cases (default: 20; set 0 to disable).",
    )
    args = parser.parse_args()

    repo_root: Path = args.repo_root
    cases_dir = args.cases_dir or (repo_root / "docs" / "rewrite" / "golden" / "cases" / "p0")
    if not cases_dir.exists():
        raise SystemExit(f"missing dir: {cases_dir}")

    fixtures_root = repo_root / "docs" / "rewrite" / "golden" / "fixtures"

    case_files = iter_case_files(cases_dir, args.case)
    if not case_files:
        print(f"no case files under: {cases_dir}")
        return 1

    failures = 0
    missing_env: set[str] = set()

    wait_http_ready(
        base_url=args.base_url,
        timeout_seconds=float(args.wait_ready_seconds or 0),
        timeout_per_try=min(2.0, float(args.timeout or 20.0)),
        use_proxy_env=args.use_proxy_env,
    )
    if args.diff_against:
        wait_http_ready(
            base_url=args.diff_against,
            timeout_seconds=float(args.wait_ready_seconds or 0),
            timeout_per_try=min(2.0, float(args.timeout or 20.0)),
            use_proxy_env=args.use_proxy_env,
        )

    for p in case_files:
        raw_src = json.loads(p.read_text(encoding="utf-8"))
        case_id = str(raw_src.get("id") or p.stem)

        raw = expand_env(raw_src, missing=missing_env)

        req = raw.get("request") or {}
        method = str(req.get("method") or "GET").upper()
        path = str(req.get("path") or "/")
        headers = dict(req.get("headers") or {})
        headers.setdefault("User-Agent", "golden-runner/1.0")
        # Allow the rewrite stub server to disambiguate multiple baselines for the same (method, path).
        headers.setdefault("X-Golden-Case-Id", case_id)
        query = req.get("query")
        body_spec = req.get("body") if isinstance(req.get("body"), dict) else None

        if args.diff_against:
            try:
                left = http_call(
                    base_url=args.base_url,
                    method=method,
                    path=path,
                    query=query,
                    headers=headers,
                    body_spec=body_spec,
                    timeout=args.timeout,
                    fixtures_root=fixtures_root,
                    use_proxy_env=args.use_proxy_env,
                )
                right = http_call(
                    base_url=args.diff_against,
                    method=method,
                    path=path,
                    query=query,
                    headers=headers,
                    body_spec=body_spec,
                    timeout=args.timeout,
                    fixtures_root=fixtures_root,
                    use_proxy_env=args.use_proxy_env,
                )
            except Exception as e:
                failures += 1
                print(f"[{case_id}] ERROR {method} {path}: {type(e).__name__}: {e} ({p})")
                if args.fail_fast:
                    break
                continue
            exp = to_recorded_response(right)
            ok, msg = compare_results(
                case_file=p,
                case_id=case_id,
                expected=exp,
                actual=left,
                compare=raw.get("compare") if isinstance(raw.get("compare"), dict) else None,
                label_expected=args.diff_against,
                label_actual=args.base_url,
            )
            print(msg)
            if not ok:
                failures += 1
                if args.fail_fast:
                    break
            continue

        if args.record:
            try:
                result = http_call(
                    base_url=args.base_url,
                    method=method,
                    path=path,
                    query=query,
                    headers=headers,
                    body_spec=body_spec,
                    timeout=args.timeout,
                    fixtures_root=fixtures_root,
                    use_proxy_env=args.use_proxy_env,
                )
            except Exception as e:
                failures += 1
                print(f"[{case_id}] ERROR {method} {path}: {type(e).__name__}: {e} ({p})")
                if args.fail_fast:
                    break
                continue
            raw["response"] = to_recorded_response(result)
            # Keep request placeholders (e.g. ${API_TOKEN}) in repo; only update response baseline.
            raw_src["response"] = raw["response"]
            p.write_text(json.dumps(raw_src, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
            print(f"[{case_id}] RECORDED {method} {path} -> {result.status} ({p})")
            continue

        expected = raw.get("response")
        if not isinstance(expected, dict):
            print(f"[{case_id}] missing response baseline, run with --record first ({p})")
            failures += 1
            if args.fail_fast:
                break
            continue

        try:
            result = http_call(
                base_url=args.base_url,
                method=method,
                path=path,
                query=query,
                headers=headers,
                body_spec=body_spec,
                timeout=args.timeout,
                fixtures_root=fixtures_root,
                use_proxy_env=args.use_proxy_env,
            )
        except Exception as e:
            failures += 1
            print(f"[{case_id}] ERROR {method} {path}: {type(e).__name__}: {e} ({p})")
            if args.fail_fast:
                break
            continue
        ok, msg = compare_results(
            case_file=p,
            case_id=case_id,
            expected=expected,
            actual=result,
            compare=raw.get("compare") if isinstance(raw.get("compare"), dict) else None,
            label_expected="baseline",
            label_actual=args.base_url,
        )
        print(msg)
        if not ok:
            failures += 1
            if args.fail_fast:
                break

    if missing_env:
        names = ", ".join(sorted(missing_env))
        print(f"\nERROR: missing env vars used in cases: {names}", file=sys.stderr)
        return 2

    if failures:
        print(f"\nFAILED: {failures}/{len(case_files)} cases", file=sys.stderr)
        return 1

    print(f"\nOK: {len(case_files)} cases passed")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
