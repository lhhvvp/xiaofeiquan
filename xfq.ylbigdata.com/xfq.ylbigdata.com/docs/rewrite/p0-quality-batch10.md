# P0 质量批次 10（可选）

目标：补齐以下 7 个“非空参数下仍保持 legacy 200 空响应”兼容分支，继续收敛 `empty_200_body` / `empty_raw_body` 基线。

- `p0-quality10-post-api-index-regeo-001`
- `p0-quality10-post-api-index-set_user_info-001`
- `p0-quality10-post-api-pay-aaa-001`
- `p0-quality10-post-api-system-rollback_remain_count-001`
- `p0-quality10-post-api-system-rollback_remain_count_extend-001`
- `p0-quality10-post-api-system-rollback_set_data-001`
- `p0-quality10-post-api-system-set_tour_invalid-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch10
```

2. 用 legacy 重录这 7 个基线：

```bash
make legacy-record-p0-quality-batch10
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch10
make py-check-p0-quality-batch10
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch10
make py-diff-p0-quality-batch10
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/15-seed-p0-quality-batch10.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch10`，与主线 `p0` 隔离。
- `POST /api/test/rsyncTaohua` 已在后续 `P0 质量批次 11` 独立闭环。
