# P0 质量批次 6（可选）

目标：把以下 2 个低质量基线用例（`500 html`）迁移到可复现的结构化返回分支，且不影响默认 `minimal` 基线流程。

- `p0-quality6-get-api-user-getTouristList-001`
- `p0-quality6-post-api-coupon-writeoff-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch6
```

2. 用 legacy 重录这 2 个基线：

```bash
make legacy-record-p0-quality-batch6
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch6
make py-check-p0-quality-batch6
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch6
make py-diff-p0-quality-batch6
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/11-seed-p0-quality-batch6.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch6`，与主线 `p0` 隔离。
- 本批次用例特征：
  - `GET /api/user/getTouristList`：GET + 鉴权头 + 分页参数，返回稳定分页结构。
  - `POST /api/coupon/writeoff`：命中“经纬度未开启”参数校验分支，返回稳定业务错误 JSON。
