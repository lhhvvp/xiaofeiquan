# P0 Success Batch 26（成功路径扩展）

目标：在 `p0_success` 套件新增 3 个稳定成功路径回归，覆盖上传类接口（multipart）在成功 seed 下的稳定回放。

## 用例清单

- `p0-success-post-api-webupload-index-001`
- `p0-success-post-selfservice-upload-index-001`
- `p0-success-post-selfservice-webupload-index-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch26
make legacy-record-p0-success-batch26
make py-check-p0-success-batch26
make py-diff-p0-success-batch26
```
