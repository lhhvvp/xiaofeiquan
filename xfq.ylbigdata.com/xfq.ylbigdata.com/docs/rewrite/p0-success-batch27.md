# P0 Success Batch 27（成功路径扩展）

目标：在 `p0_success` 套件新增 4 个稳定成功路径回归，覆盖 OTA（美团/携程）成功校验与上传类接口成功响应。

## 用例清单

- `p0-success-post-meituan-index-system-html-001`
- `p0-success-post-meituan-webupload-index-001`
- `p0-success-post-xc-index-system-html-001`
- `p0-success-post-xc-webupload-index-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch27
make legacy-record-p0-success-batch27
make py-check-p0-success-batch27
make py-diff-p0-success-batch27
```
