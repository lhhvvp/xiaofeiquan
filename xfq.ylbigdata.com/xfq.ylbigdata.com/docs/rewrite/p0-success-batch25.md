# P0 Success Batch 25（成功路径扩展）

目标：在 `p0_success` 套件新增 3 个稳定成功路径回归，覆盖 System Jobs 的 legacy 兼容输出（非 JSON 返回形态）。

## 用例清单

- `p0-success-post-api-system-queryArea-001`
- `p0-success-post-api-system-cleanDb-001`
- `p0-success-post-api-system-notification-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch25
make legacy-record-p0-success-batch25
make py-check-p0-success-batch25
make py-diff-p0-success-batch25
```
