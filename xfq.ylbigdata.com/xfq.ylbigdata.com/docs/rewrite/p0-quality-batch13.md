# P0 质量批次 13（可选）

目标：在主线 `p0` 基线清洗后，保留以下 8 个历史低质量分支（`500 html` / `200 空 body`）作为独立兼容回归，不污染主线 `p0` 验收目标。

- `p0-quality13-post-api-index-get_area_info-legacy500-001`
- `p0-quality13-post-api-pay-OrderRefund-legacy500-001`
- `p0-quality13-post-api-pay-regressionStock-legacy500-001`
- `p0-quality13-post-api-ticket-OrderRefund-legacy500-001`
- `p0-quality13-post-api-ticket-OrderRefundDetail-legacy500-001`
- `p0-quality13-post-api-notify-create_file-legacy500-001`
- `p0-quality13-post-api-ticket-create_file-legacy500-001`
- `p0-quality13-post-api-seller-search-legacy-empty-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch13
```

2. 用 legacy 重录这 8 个基线：

```bash
make legacy-record-p0-quality-batch13
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch13
make py-check-p0-quality-batch13
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch13
make py-diff-p0-quality-batch13
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/18-seed-p0-quality-batch13.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch13`，与主线 `p0` 隔离。
