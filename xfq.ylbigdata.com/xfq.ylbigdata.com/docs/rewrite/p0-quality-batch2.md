# P0 质量批次 2（可选）

目标：把以下 2 个 miniapp 用例从“历史 500 页面”提升为“结构化 200 JSON”基线，且不影响默认 `minimal` 基线流程。

- `p0-miniapp-post-api-coupon-receive-001`
- `p0-miniapp-post-api-user-writeoff_tour-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch2
```

2. 用 legacy 重录这 2 个基线：

```bash
make legacy-record-p0-quality-batch2
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch2
make py-check-p0-quality-batch2
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch2
make py-diff-p0-quality-batch2
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/07-seed-p0-quality-batch2.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch2`，与主线 `p0` 隔离。
- 本批次采用“可复现结构化错误分支”（非 500 HTML）：
  - `/api/coupon/receive`：`code=3,msg=已领取过该优惠劵!`
  - `/api/user/writeoff_tour`：`code=1,msg=该消费券已使用`
