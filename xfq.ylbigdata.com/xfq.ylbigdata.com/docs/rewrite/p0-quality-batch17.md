# P0 质量批次 17（可选）

目标：将以下 12 个历史 `500 html` 基线从主线 `p0` 抽离为独立兼容回归，并用请求头开关将主线切换到可结构化校验（`200 JSON/XML/PNG`）。

- `p0-quality17-post-api-coupon-getIssueCouponList-legacy500-001`
- `p0-quality17-post-api-coupon-receive-legacy500-001`
- `p0-quality17-post-api-notify-pay_async_notice-legacy500-001`
- `p0-quality17-post-api-notify-refund-legacy500-001`
- `p0-quality17-post-api-screen-index-legacy500-001`
- `p0-quality17-post-api-system-XdataSummary-legacy500-001`
- `p0-quality17-post-api-test-tokenTaohua-legacy500-001`
- `p0-quality17-post-api-ticket-notify_pay-legacy500-001`
- `p0-quality17-post-api-ticket-notify_refund-legacy500-001`
- `p0-quality17-get-selfservice-index-captcha-legacy500-001`
- `p0-quality17-post-selfservice-ticket-getTravelWxappQrcode-legacy500-001`
- `p0-quality17-post-xc-order-testGetOrder-legacy500-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch17
```

2. 用 legacy 重录这 12 个基线：

```bash
make legacy-record-p0-quality-batch17
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch17
make py-check-p0-quality-batch17
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch17
make py-diff-p0-quality-batch17
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/22-seed-p0-quality-batch17.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch17`，与主线 `p0` 隔离。
- 主线 `p0` 对应 case 通过请求头 `X-Rewrite-Enable: 1` 进入结构化分支；不带该请求头仍保持 legacy 兼容分支。
