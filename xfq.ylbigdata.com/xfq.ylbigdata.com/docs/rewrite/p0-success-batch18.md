# P0 Success Batch 18（成功路径稳态扩展）

目标：在 `p0_success` 套件新增 4 个可重复执行、与 legacy 对齐的成功路径回归用例，增强“非 500/非空响应”主线回归覆盖。

## 用例清单

- `p0-success-post-api-coupon-index-001`
- `p0-success-post-api-index-get_area_info-001`
- `p0-success-post-api-seller-search-001`
- `p0-success-post-api-user-delTourist-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch18
make legacy-record-p0-success-batch18
make py-check-p0-success-batch18
make py-diff-p0-success-batch18
```
