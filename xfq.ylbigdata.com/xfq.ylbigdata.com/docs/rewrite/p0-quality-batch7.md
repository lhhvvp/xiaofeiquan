# P0 质量批次 7（可选）

目标：把以下 1 个低质量基线用例（`500 html`）迁移到可复现的结构化返回分支，且不影响默认 `minimal` 基线流程。

- `p0-quality7-post-api-user-getTouristList-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch7
```

2. 用 legacy 重录这 1 个基线：

```bash
make legacy-record-p0-quality-batch7
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch7
make py-check-p0-quality-batch7
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch7
make py-diff-p0-quality-batch7
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/12-seed-p0-quality-batch7.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch7`，与主线 `p0` 隔离。
- 本批次用例特征：
  - `POST /api/user/getTouristList`：通过 query 传分页参数（`page/page_size`）命中 legacy 的可用分支。
