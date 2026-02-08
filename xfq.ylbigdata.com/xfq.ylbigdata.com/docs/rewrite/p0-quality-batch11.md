# P0 质量批次 11（可选）

目标：补齐剩余 `empty_200_body` / `empty_raw_body` 端点，完成 `POST /api/test/rsyncTaohua` 的独立基线闭环。

- `p0-quality11-post-api-test-rsyncTaohua-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch11
```

2. 用 legacy 重录该基线：

```bash
make legacy-record-p0-quality-batch11
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch11
make py-check-p0-quality-batch11
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch11
make py-diff-p0-quality-batch11
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/16-seed-p0-quality-batch11.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch11`，与主线 `p0` 隔离。
