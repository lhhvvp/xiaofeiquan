# Python 重写后端（FastAPI）

当前阶段目标：先把“接口契约（golden）”跑通，再逐接口替换为真实实现。

## 启动

在仓库根目录：

```bash
make py-up
```

默认端口：

- `http://127.0.0.1:28080`

## 运行 P0 验收

```bash
set -a
source xfq.ylbigdata.com/xfq.ylbigdata.com/.env.golden.example
set +a

make py-check-p0
```

## Stub 模式

默认 `STUB_FROM_GOLDEN=1`：未实现的接口会直接按 `GOLDEN_CASES_DIR` 里的基线响应返回（用于快速回归/对齐）。

当前已开始把接口逐步“真实化”：

- `POST /api/index/system`：已改为从 MySQL 读取 `tp_system(id=1)` 并返回同结构数据。

