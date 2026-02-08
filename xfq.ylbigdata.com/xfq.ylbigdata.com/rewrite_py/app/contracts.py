from __future__ import annotations

import csv
import os
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class InterfaceSpec:
    method: str
    path: str
    path_html: str
    app: str
    controller: str
    action: str
    auth: str
    auth_required: str
    desc: str
    file: str
    line: int | None

    @property
    def requires_auth(self) -> bool:
        return (self.auth_required or "").strip().lower() == "yes"


def _normalize_path(value: str) -> str:
    v = (value or "").strip()
    if not v:
        return "/"
    return v if v.startswith("/") else "/" + v


def get_p0_interfaces_path() -> Path:
    v = (os.environ.get("P0_INTERFACES_TSV") or "").strip()
    if v:
        return Path(v)
    return Path(__file__).resolve().parents[2] / "docs" / "rewrite" / "p0-interfaces.tsv"


def load_p0_interfaces(path: Path) -> dict[str, InterfaceSpec]:
    if not path.exists():
        return {}

    specs: dict[str, InterfaceSpec] = {}
    with path.open("r", encoding="utf-8", newline="") as f:
        reader = csv.DictReader(f, delimiter="\t")
        for row in reader:
            if not row:
                continue
            p = _normalize_path(str(row.get("path") or ""))
            if not p:
                continue

            line_raw = str(row.get("line") or "").strip()
            try:
                line = int(line_raw) if line_raw else None
            except ValueError:
                line = None

            specs[p] = InterfaceSpec(
                method=str(row.get("method") or "").strip(),
                path=p,
                path_html=_normalize_path(str(row.get("path_html") or "")),
                app=str(row.get("app") or "").strip(),
                controller=str(row.get("controller") or "").strip(),
                action=str(row.get("action") or "").strip(),
                auth=str(row.get("auth") or "").strip(),
                auth_required=str(row.get("auth_required") or "").strip(),
                desc=str(row.get("desc") or "").strip(),
                file=str(row.get("file") or "").strip(),
                line=line,
            )

    return specs

