# P0 质量批次 3（可选）

目标：把以下 4 个 upload/webupload 用例从“历史 500 页面（空 form）”提升为“结构化 200 JSON（multipart 真实上传路径）”基线，且不影响默认 `minimal` 基线流程。

- `p0-quality3-post-api-upload-index-001`
- `p0-quality3-post-api-webupload-index-001`
- `p0-quality3-post-selfservice-upload-index-001`
- `p0-quality3-post-selfservice-webupload-index-001`

## 使用方式

1. 重置并加载批次 seed：

```bash
make db-reset-p0-quality-batch3
```

2. 用 legacy 重录这 4 个基线：

```bash
make legacy-record-p0-quality-batch3
```

3. 重置到同一数据面并校验 rewrite：

```bash
make db-reset-p0-quality-batch3
make py-check-p0-quality-batch3
```

4. 可选：双端直接差异比较：

```bash
make db-reset-p0-quality-batch3
make py-diff-p0-quality-batch3
```

## 注意事项

- 本批次 seed 位于 `infra/mysql/08-seed-p0-quality-batch3.sql`，不会自动并入 `db-reset-minimal`。
- 本批次 case 位于 `docs/rewrite/golden/cases/p0_quality_batch3`，与主线 `p0` 隔离。
- 本批次依赖 `tp_system` 上传扩展名白名单包含 `txt`，以进入稳定可复现的 multipart 错误分支：
  - `code=0,msg=Failed to open temp directory.,url=""`
