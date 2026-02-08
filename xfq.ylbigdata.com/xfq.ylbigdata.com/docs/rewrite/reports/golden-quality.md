# Golden 质量审计报告（自动生成）

- 用例总数：`259`
- 检出问题条目：`0`
- 受影响接口数：`0`

## 问题类型统计


## 受影响接口 Top 40


## 处理建议

- 对 `status_5xx` / `legacy_500_html_page` 用例优先重录 baseline。
- 重录前执行 `make db-reset-minimal` 或 `make db-reset-success`，保证可重复。
- 重录后立即执行 `make py-check-p0-dev`，避免把错误基线带入新实现。
