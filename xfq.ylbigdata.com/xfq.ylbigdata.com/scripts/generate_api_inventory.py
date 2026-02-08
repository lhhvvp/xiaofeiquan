#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import re
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable, Optional


METHOD_RE = re.compile(r"\bpublic\s+(?:static\s+)?function\s+([A-Za-z_]\w*)\s*\(")
ROUTE_RE = re.compile(
    r"""\bRoute::(?P<method>get|post|put|delete|patch|options|any)\(\s*
    (?P<q1>['"])(?P<path>.*?)(?P=q1)\s*,\s*
    (?P<q2>['"])(?P<target>.*?)(?P=q2)""",
    re.IGNORECASE | re.VERBOSE,
)
API_DOC_RE = re.compile(r"@api\s*\{(?P<method>[A-Za-z]+)\}\s*(?P<path>\S+)")
API_DESC_RE = re.compile(r"@apiDescription\s*(?P<desc>.*)$")
ALLOWED_DOC_METHODS = {"GET", "POST", "PUT", "DELETE", "PATCH", "OPTIONS", "HEAD", "ANY"}

IGNORE_METHODS = {
    "__construct",
    "initialize",
    "__call",
    "__get",
    "__set",
    "__isset",
    "__unset",
    "__destruct",
}


@dataclass(frozen=True)
class InventoryRow:
    app: str
    controller: str
    action: str
    route_guess: str
    route_guess_html: str
    doc_method: str
    doc_path: str
    doc_desc: str
    auth: str
    auth_required: str
    file: str
    line: int


def _read_lines(path: Path) -> list[str]:
    return path.read_text(encoding="utf-8", errors="ignore").splitlines()


def _app_default_auth(app: str) -> str:
    app = app.lower()
    if app == "api":
        return "api-token(Userid)"
    if app == "window":
        return "window-token(Uuid)"
    if app == "selfservice":
        return "selfservice-token(No)"
    if app in {"admin", "seller", "travel"}:
        return "session(PHPSESSID)"
    if app == "xc":
        return "xc(AES+md5-sign)"
    if app == "meituan":
        return "meituan(BA-sign)"
    if app in {"index", "mobile"}:
        return "public"
    return "unknown"


def _controller_from_path(controller_file: Path, app_dir: Path) -> str:
    rel = controller_file.relative_to(app_dir / "controller")
    return str(rel.with_suffix("")).replace("\\", "/")


def _guess_route(app: str, controller: str, action: str) -> tuple[str, str]:
    # URLs in this project are typically called with lower-case controller (and sometimes camelCase action),
    # so we normalize controller to lower-case but keep action as-is.
    controller_norm = controller.replace("\\", "/").lower()
    base = f"/{app}/{controller_norm}/{action}"
    return base, f"{base}.html"


def _extract_doc_for_method(lines: list[str], method_line_idx: int) -> tuple[str, str, str]:
    """
    Best-effort: find a PHPDoc block immediately above the method and parse @api/@apiDescription.
    """
    start = max(0, method_line_idx - 80)
    doc_start: Optional[int] = None
    for i in range(method_line_idx - 1, start - 1, -1):
        line = lines[i].strip()
        if re.search(r"\b(public|protected|private)\s+function\b", line):
            break  # crossed into previous method
        if line.startswith("/**"):
            doc_start = i
            break
    if doc_start is None:
        return "", "", ""

    doc_lines = lines[doc_start:method_line_idx]
    doc_method = ""
    doc_path = ""
    doc_desc = ""
    for raw in doc_lines:
        m = API_DOC_RE.search(raw)
        if m:
            doc_method = m.group("method").upper()
            doc_path = m.group("path")
        d = API_DESC_RE.search(raw)
        if d:
            doc_desc = d.group("desc").strip()

    # Some methods use placeholder docs like `@api {method}path`; ignore these.
    if doc_method and doc_method not in ALLOWED_DOC_METHODS:
        doc_method = ""
    if doc_path and not doc_path.strip().startswith("/"):
        doc_path = ""
        doc_method = ""
    return doc_method, doc_path, doc_desc


def _extract_auth_exceptions(lines: list[str]) -> tuple[bool, set[str], set[str]]:
    """
    Parse patterns like:
      protected $middleware = [ Auth::class => ['except' => ['a','b']] ];
      protected $middleware = [ Auth::class => ['only' => ['a','b']] ];
    Returns: (has_auth_middleware, except_set, only_set)
    """
    text = "\n".join(lines)
    if "Auth::class" not in text:
        return False, set(), set()

    except_set: set[str] = set()
    only_set: set[str] = set()

    # except
    m = re.search(r"Auth::class\s*=>\s*\[\s*'except'\s*=>\s*\[(?P<body>.*?)\]\s*\]", text, re.S)
    if m:
        except_set.update(re.findall(r"'([^']+)'", m.group("body")))

    # only
    m = re.search(r"Auth::class\s*=>\s*\[\s*'only'\s*=>\s*\[(?P<body>.*?)\]\s*\]", text, re.S)
    if m:
        only_set.update(re.findall(r"'([^']+)'", m.group("body")))

    return True, except_set, only_set


def iter_controller_actions(project_root: Path) -> Iterable[InventoryRow]:
    app_root = project_root / "app"
    for app_dir in sorted(p for p in app_root.iterdir() if p.is_dir()):
        app = app_dir.name
        if app in {"common", "command"}:
            continue
        controller_dir = app_dir / "controller"
        if not controller_dir.exists():
            continue

        for controller_file in sorted(controller_dir.rglob("*.php")):
            lines = _read_lines(controller_file)
            has_auth, except_set, only_set = _extract_auth_exceptions(lines)
            default_auth = _app_default_auth(app)

            for idx, raw in enumerate(lines):
                m = METHOD_RE.search(raw)
                if not m:
                    continue
                action = m.group(1)
                if action in IGNORE_METHODS:
                    continue
                controller = _controller_from_path(controller_file, app_dir)
                route_guess, route_guess_html = _guess_route(app, controller, action)

                doc_method, doc_path, doc_desc = _extract_doc_for_method(lines, idx)

                if has_auth:
                    if only_set:
                        auth_required = "yes" if action in only_set else "no"
                    else:
                        auth_required = "no" if action in except_set else "yes"
                else:
                    auth_required = "unknown"

                yield InventoryRow(
                    app=app,
                    controller=controller,
                    action=action,
                    route_guess=route_guess,
                    route_guess_html=route_guess_html,
                    doc_method=doc_method,
                    doc_path=doc_path,
                    doc_desc=doc_desc,
                    auth=default_auth,
                    auth_required=auth_required,
                    file=str(controller_file.relative_to(project_root)).replace("\\", "/"),
                    line=idx + 1,
                )


def iter_explicit_routes(project_root: Path) -> Iterable[InventoryRow]:
    app_root = project_root / "app"
    for route_file in sorted(app_root.glob("*/route/*.php")):
        app = route_file.parent.parent.name
        lines = _read_lines(route_file)
        default_auth = _app_default_auth(app)
        for idx, raw in enumerate(lines):
            m = ROUTE_RE.search(raw)
            if not m:
                continue
            http_method = m.group("method").upper()
            path_rule = m.group("path")
            target = m.group("target")

            # target usually like login/index
            target_parts = target.split("/")
            controller = target_parts[0] if target_parts else ""
            action = target_parts[1] if len(target_parts) > 1 else ""

            route_guess = f"/{app}/{path_rule}".replace("//", "/")
            route_guess_html = f"{route_guess}.html"

            yield InventoryRow(
                app=app,
                controller=controller,
                action=action,
                route_guess=route_guess,
                route_guess_html=route_guess_html,
                doc_method=http_method,
                doc_path=route_guess,
                doc_desc=f"route => {target}",
                auth=default_auth,
                auth_required="unknown",
                file=str(route_file.relative_to(project_root)).replace("\\", "/"),
                line=idx + 1,
            )


def write_tsv(rows: list[InventoryRow], output_path: Path) -> None:
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with output_path.open("w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f, delimiter="\t")
        writer.writerow(
            [
                "app",
                "controller",
                "action",
                "route_guess",
                "route_guess_html",
                "doc_method",
                "doc_path",
                "doc_desc",
                "auth",
                "auth_required",
                "file",
                "line",
            ]
        )
        for r in rows:
            writer.writerow(
                [
                    r.app,
                    r.controller,
                    r.action,
                    r.route_guess,
                    r.route_guess_html,
                    r.doc_method,
                    r.doc_path,
                    r.doc_desc,
                    r.auth,
                    r.auth_required,
                    r.file,
                    r.line,
                ]
            )


def main() -> int:
    parser = argparse.ArgumentParser(description="Generate external API inventory (best-effort).")
    parser.add_argument(
        "--project-root",
        type=Path,
        default=Path(__file__).resolve().parent.parent,
        help="ThinkPHP project root (default: scripts/..)",
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=None,
        help="Output TSV path (default: docs/rewrite/api-inventory.tsv)",
    )
    args = parser.parse_args()

    project_root: Path = args.project_root.resolve()
    output_path: Path = (
        args.output.resolve()
        if args.output
        else (project_root / "docs" / "rewrite" / "api-inventory.tsv")
    )

    rows = list(iter_controller_actions(project_root)) + list(iter_explicit_routes(project_root))
    rows.sort(key=lambda r: (r.app.lower(), r.controller.lower(), r.action.lower(), r.file, r.line))
    write_tsv(rows, output_path)

    # lightweight stats
    stats: dict[str, int] = {}
    for r in rows:
        stats[r.app] = stats.get(r.app, 0) + 1
    top = ", ".join(f"{k}={v}" for k, v in sorted(stats.items()))
    print(f"Wrote {len(rows)} rows to {output_path}")
    print(f"Rows by app: {top}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
