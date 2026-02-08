# P0 质量批次 15（可选）

目标：在主线 `p0` 基线清洗后，保留以下 2 个历史低质量分支（`500 html`）作为独立兼容回归，不污染主线 `p0` 验收目标。

- `p0-quality15-post-api-user-writeoff_tour-legacy500-001`
- `p0-quality15-post-api-user-getTouristList-legacy500-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch15
```

2. 用 legacy 重录这 2 个基线：

```bash
make legacy-record-p0-quality-batch15
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch15
make py-check-p0-quality-batch15
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch15
make py-diff-p0-quality-batch15
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/20-seed-p0-quality-batch15.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch15`，与主线 `p0` 隔离。
