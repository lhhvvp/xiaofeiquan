# P0 质量批次 5（可选）

目标：把以下 8 个低质量基线用例（5xx/空响应）迁移到可复现的结构化返回分支，且不影响默认 `minimal` 基线流程。

- `p0-quality5-post-api-index-get_area_info-001`
- `p0-quality5-post-api-pay-OrderRefund-001`
- `p0-quality5-post-api-pay-regressionStock-001`
- `p0-quality5-post-api-ticket-OrderRefund-001`
- `p0-quality5-post-api-ticket-OrderRefundDetail-001`
- `p0-quality5-post-api-notify-create_file-001`
- `p0-quality5-post-api-ticket-create_file-001`
- `p0-quality5-post-api-seller-search-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch5
```

2. 用 legacy 重录这 8 个基线：

```bash
make legacy-record-p0-quality-batch5
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch5
make py-check-p0-quality-batch5
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch5
make py-diff-p0-quality-batch5
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/10-seed-p0-quality-batch5.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch5`，与主线 `p0` 隔离。
- 本批次以“参数触发的结构化分支”为主，避免继续固化 `500 html` 与 `empty body` 作为目标行为。
