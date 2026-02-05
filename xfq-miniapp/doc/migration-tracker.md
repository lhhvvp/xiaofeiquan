# 原生重构迁移跟踪（模板）

建议用法：
- 迁移进度以 `xfq-miniapp/doc/migration-tracker.csv` 为准（可直接用 Excel 打开维护）。
- 每个页面迁移完成后，必须同时更新：`status`、自测人/时间、阻塞项与回归用例链接。
- 单人推进时可忽略 `owner` 字段（保持为空即可）。

**状态定义**
- `todo`：未开始
- `in_progress`：开发中
- `blocked`：被阻塞（必须写明原因与负责人）
- `ready_for_qa`：提测
- `done`：验收通过并合入主分支

**字段说明（CSV 列）**
- `module`：业务模块（tabbar/auth/coupon/merchant/tickets/user/content/groupcoupon/misc）
- `page_path`：旧工程页面 path（`pages.json`）
- `suggested_subpackage`：建议分包（main/coupon/merchant/tickets/user/content）
- `priority`：P0/P1/P2/TBD（需产品/运营/技术共同确认）
- `owner`：页面负责人
- `status`：见上
- `legacy_file`：旧工程 `.vue` 对照路径
- `notes`：接口/埋点/权限/特殊逻辑等备注
