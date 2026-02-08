# P0 质量批次 14（可选）

目标：在主线 `p0` 基线清洗后，保留以下 9 个历史低质量分支（`500 html` / 参数触发异常分支）作为独立兼容回归，不污染主线 `p0` 验收目标。

- `p0-quality14-get-api-appt-getDetail-legacy500-001`
- `p0-quality14-get-api-ticket-getOrderDetail-legacy500-001`
- `p0-quality14-post-api-appt-cancelAppt-legacy500-001`
- `p0-quality14-post-api-seller-detail-legacy500-001`
- `p0-quality14-post-api-user-index-legacy500-001`
- `p0-quality14-post-api-user-tour_coupon_group-legacy500-001`
- `p0-quality14-post-api-coupon-writeoff-legacy500-001`
- `p0-quality14-post-api-user-getTouristList-legacy500-001`
- `p0-quality14-post-xc-webupload-index-legacy500-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch14
```

2. 用 legacy 重录这 9 个基线：

```bash
make legacy-record-p0-quality-batch14
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch14
make py-check-p0-quality-batch14
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch14
make py-diff-p0-quality-batch14
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/19-seed-p0-quality-batch14.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch14`，与主线 `p0` 隔离。
