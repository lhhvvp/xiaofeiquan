# P0 Success Batch 21（成功路径扩展）

目标：在 `p0_success` 套件新增 5 个稳定成功路径回归，覆盖用户券订单查询、票务景区列表与系统任务成功响应。

## 用例清单

- `p0-success-post-api-user-get_user_coupon_id-001`
- `p0-success-post-api-user-coupon_order-001`
- `p0-success-post-api-user-coupon_order_detail-001`
- `p0-success-get-api-ticket-getScenicList-001`
- `p0-success-post-api-system-tableTohtml1-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch21
make legacy-record-p0-success-batch21
make py-check-p0-success-batch21
make py-diff-p0-success-batch21
```
