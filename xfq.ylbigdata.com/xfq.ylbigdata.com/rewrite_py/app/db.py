from __future__ import annotations

import os
from contextlib import contextmanager
from typing import Any, Iterator

import pymysql


def _env(name: str, default: str) -> str:
    return (os.environ.get(name) or default).strip()


def _db_config() -> dict[str, Any]:
    return {
        "host": _env("DB_HOST", "mysql"),
        "port": int(_env("DB_PORT", "3306")),
        "user": _env("DB_USER", "xfq"),
        "password": _env("DB_PASSWORD", "xfq"),
        "database": _env("DB_NAME", "xfq_v2"),
        "charset": _env("DB_CHARSET", "utf8mb4"),
        "cursorclass": pymysql.cursors.DictCursor,
        "autocommit": True,
    }


@contextmanager
def mysql_conn() -> Iterator[pymysql.connections.Connection]:
    conn = pymysql.connect(**_db_config())
    try:
        yield conn
    finally:
        conn.close()


def fetch_one(sql: str, params: tuple[Any, ...] | None = None) -> dict[str, Any] | None:
    with mysql_conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params or ())
            row = cur.fetchone()
            return dict(row) if row else None


def fetch_all(sql: str, params: tuple[Any, ...] | None = None) -> list[dict[str, Any]]:
    with mysql_conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params or ())
            rows = cur.fetchall()
            return [dict(r) for r in (rows or [])]


def execute(sql: str, params: tuple[Any, ...] | None = None) -> int:
    with mysql_conn() as conn:
        with conn.cursor() as cur:
            n = cur.execute(sql, params or ())
            return int(n or 0)
