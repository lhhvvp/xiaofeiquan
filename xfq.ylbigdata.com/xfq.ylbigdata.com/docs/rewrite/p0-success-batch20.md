# P0 Success Batch 20（成功路径扩展）

目标：在 `p0_success` 套件新增 4 个稳定成功路径回归，覆盖用户动作链路与系统任务成功响应。

## 用例清单

- `p0-success-post-api-user-coupon_issue_user-001`
- `p0-success-post-api-user-collection_action-add-001`
- `p0-success-post-api-user-collection_action-del-001`
- `p0-success-post-api-system-tableTohtml-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch20
make legacy-record-p0-success-batch20
make py-check-p0-success-batch20
make py-diff-p0-success-batch20
```
