from __future__ import annotations

import time
from typing import Any

from fastapi.encoders import jsonable_encoder
from fastapi.responses import JSONResponse


def api_success(data: Any, msg: str = "请求成功") -> JSONResponse:
    return JSONResponse(
        status_code=200,
        content=jsonable_encoder({"code": 0, "msg": msg, "time": int(time.time()), "data": data}),
        headers={"content-type": "application/json; charset=utf-8"},
    )


def api_error(msg: str, *, code: int = 1, data: Any = None, status_code: int = 200) -> JSONResponse:
    return JSONResponse(
        status_code=status_code,
        content=jsonable_encoder(
            {"code": code, "msg": msg, "time": int(time.time()), "data": [] if data is None else data}
        ),
        headers={"content-type": "application/json; charset=utf-8"},
    )
