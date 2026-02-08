# P0 质量批次 12（可选）

目标：在主线 `p0` 基线清洗后，保留以下 5 个历史 `500 html` 分支作为独立兼容回归（不污染主线 `p0`）。

- `p0-quality12-post-api-upload-index-legacy500-001`
- `p0-quality12-post-api-webupload-index-legacy500-001`
- `p0-quality12-post-selfservice-upload-index-legacy500-001`
- `p0-quality12-post-selfservice-webupload-index-legacy500-001`
- `p0-quality12-post-meituan-webupload-index-legacy500-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch12
```

2. 用 legacy 重录这 5 个基线：

```bash
make legacy-record-p0-quality-batch12
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch12
make py-check-p0-quality-batch12
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch12
make py-diff-p0-quality-batch12
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/17-seed-p0-quality-batch12.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch12`，与主线 `p0` 隔离。
