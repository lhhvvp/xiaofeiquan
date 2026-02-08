# P0 质量批次 1（可选）

目标：把以下 6 个 miniapp 用例从“历史 500 页面”提升为“结构化 200 JSON”基线，且不影响默认 `minimal` 基线流程。

- `p0-miniapp-get-api-appt-getDetail-001`
- `p0-miniapp-post-api-appt-cancelAppt-001`
- `p0-miniapp-get-api-ticket-getOrderDetail-001`
- `p0-miniapp-post-api-seller-detail-001`
- `p0-miniapp-post-api-user-index-001`
- `p0-miniapp-post-api-user-tour_coupon_group-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch1
```

2. 用 legacy 重录这 6 个基线：

```bash
make legacy-record-p0-quality-batch1
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch1
make py-check-p0-quality-batch1
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch1
make py-diff-p0-quality-batch1
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/06-seed-p0-quality-batch1.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 已独立到 `docs/rewrite/golden/cases/p0_quality_batch1`，不会污染主线 `p0`。
- `py-diff-p0-quality-batch1` 默认不包含 `cancelAppt`（该接口有状态写入，双端串行对比会产生先后顺序偏差）。
- 主线可继续执行 `make py-check-p0-dev-minimal`（当前验证结果：`210/210`）。
- 原先未纳入的 2 个 500 用例已迁移到 `p0_quality_batch2`：`/api/coupon/receive`、`/api/user/writeoff_tour`。
