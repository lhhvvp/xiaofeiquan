# P0 Success Batch 24（成功路径扩展）

目标：在 `p0_success` 套件新增 5 个稳定成功路径回归，覆盖商家列表/分类、大屏列表与用户认证信息读取。

## 用例清单

- `p0-success-post-api-seller-cate-001`
- `p0-success-post-api-seller-list-001`
- `p0-success-post-api-screen-list-001`
- `p0-success-post-api-user-auth_info-001`
- `p0-success-post-api-user-getTouristList-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch24
make legacy-record-p0-success-batch24
make py-check-p0-success-batch24
make py-diff-p0-success-batch24
```
