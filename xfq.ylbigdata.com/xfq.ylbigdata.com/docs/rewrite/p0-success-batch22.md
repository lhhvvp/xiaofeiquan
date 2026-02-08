# P0 Success Batch 22（成功路径扩展）

目标：在 `p0_success` 套件新增 5 个稳定成功路径回归，覆盖预约与公告查询类接口（读多写少）。

## 用例清单

- `p0-success-get-api-appt-getDatetime-001`
- `p0-success-get-api-appt-getList-001`
- `p0-success-get-api-index-note_detail-001`
- `p0-success-post-api-index-note_detail-001`
- `p0-success-post-api-index-note_list-001`

## 依赖

- 运行前执行：`make db-reset-success`
- 使用开发鉴权变量（`API_TOKEN/API_USERID` 等）

## 执行命令

```bash
make db-reset-p0-success-batch22
make legacy-record-p0-success-batch22
make py-check-p0-success-batch22
make py-diff-p0-success-batch22
```
