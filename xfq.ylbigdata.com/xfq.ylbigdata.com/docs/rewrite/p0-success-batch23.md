# P0 Success Batch 23（成功路径扩展）

目标：在 `p0_success` 套件新增 5 个稳定成功路径回归，覆盖 Index 基础类接口（系统信息/登录等）。

## 用例清单

- `p0-success-post-api-index-jia-001`
- `p0-success-post-api-index-jie-001`
- `p0-success-post-api-index-login-001`
- `p0-success-post-api-index-note_index-001`
- `p0-success-post-api-index-system-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch23
make legacy-record-p0-success-batch23
make py-check-p0-success-batch23
make py-diff-p0-success-batch23
```
