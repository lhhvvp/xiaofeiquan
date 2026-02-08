# P0 Success Batch 19（成功路径扩展）

目标：在 `p0_success` 套件新增 5 个稳定成功路径回归，覆盖票务查询与用户基础字典类接口。

## 用例清单

- `p0-success-post-api-coupon-tempApi-001`
- `p0-success-post-api-user-getCertTypeList-001`
- `p0-success-get-api-ticket-getCommentList-001`
- `p0-success-get-api-ticket-getOrderList-001`
- `p0-success-get-api-ticket-getRefundLogList-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch19
make legacy-record-p0-success-batch19
make py-check-p0-success-batch19
make py-diff-p0-success-batch19
```
