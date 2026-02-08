# P0 质量批次 9（可选）

目标：补齐以下 5 个“非空参数（或 query）下仍保持 legacy 500”兼容分支，继续收敛低质量基线的可复现度。

- `p0-quality9-get-selfservice-index-captcha-001`
- `p0-quality9-post-api-system-XdataSummary-001`
- `p0-quality9-post-api-test-tokenTaohua-001`
- `p0-quality9-get-xc-order-testGetOrder-001`
- `p0-quality9-post-xc-order-testGetOrder-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch9
```

2. 用 legacy 重录这 5 个基线：

```bash
make legacy-record-p0-quality-batch9
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch9
make py-check-p0-quality-batch9
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch9
make py-diff-p0-quality-batch9
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/14-seed-p0-quality-batch9.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch9`，与主线 `p0` 隔离。
- 本批次聚焦 legacy 运行时失败分支（500 html），目的是先确保“可重复回放与严格兼容”。
