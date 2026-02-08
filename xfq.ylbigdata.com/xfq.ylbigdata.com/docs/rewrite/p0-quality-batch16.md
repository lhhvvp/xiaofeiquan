# P0 质量批次 16（可选）

目标：将以下 8 个历史 `200 空 body` 基线从主线 `p0` 抽离为独立兼容回归，主线改为“状态级校验”以避免空响应污染质量审计。

- `p0-quality16-post-api-index-regeo-legacy-empty-001`
- `p0-quality16-post-api-index-set_user_info-legacy-empty-001`
- `p0-quality16-post-api-pay-aaa-legacy-empty-001`
- `p0-quality16-post-api-system-rollback_remain_count-legacy-empty-001`
- `p0-quality16-post-api-system-rollback_remain_count_extend-legacy-empty-001`
- `p0-quality16-post-api-system-rollback_set_data-legacy-empty-001`
- `p0-quality16-post-api-system-set_tour_invalid-legacy-empty-001`
- `p0-quality16-post-api-test-rsyncTaohua-legacy-empty-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch16
```

2. 用 legacy 重录这 8 个基线：

```bash
make legacy-record-p0-quality-batch16
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch16
make py-check-p0-quality-batch16
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch16
make py-diff-p0-quality-batch16
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/21-seed-p0-quality-batch16.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch16`，与主线 `p0` 隔离。
