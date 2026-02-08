# P0 质量批次 8（可选）

目标：补齐以下 7 个“非空参数下的 legacy 运行时 500”兼容分支，确保 rewrite 在默认严格兼容配置下与 legacy 基线一致。

- `p0-quality8-post-api-coupon-getIssueCouponList-001`
- `p0-quality8-post-api-screen-index-001`
- `p0-quality8-post-api-notify-pay_async_notice-001`
- `p0-quality8-post-api-notify-refund-001`
- `p0-quality8-post-api-ticket-notify_pay-001`
- `p0-quality8-post-api-ticket-notify_refund-001`
- `p0-quality8-post-selfservice-ticket-getTravelWxappQrcode-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch8
```

2. 用 legacy 重录这 7 个基线：

```bash
make legacy-record-p0-quality-batch8
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch8
make py-check-p0-quality-batch8
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch8
make py-diff-p0-quality-batch8
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/13-seed-p0-quality-batch8.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch8`，与主线 `p0` 隔离。
- 本批次默认使用“严格兼容开关关闭”的配置（即保持 legacy 500 分支）。
- 若需要在联调环境启用新行为，可在 rewrite 环境变量中打开以下开关：
  - `REWRITE_ENABLE_COUPON_GET_ISSUE_LIST=1`
  - `REWRITE_ENABLE_SCREEN_INDEX=1`
  - `REWRITE_ENABLE_NOTIFY_CALLBACKS=1`
  - `REWRITE_ENABLE_TICKET_NOTIFY_CALLBACKS=1`
  - `REWRITE_ENABLE_SELFSERVICE_TRAVEL_QRCODE=1`
