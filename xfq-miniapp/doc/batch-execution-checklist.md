# Batch 执行清单（强约束版）

适用范围：`xfq-miniapp/mp-native`  
配套总计划：`xfq-miniapp/doc/native-1to1-master-plan.md`  
执行模式：`一次切换上线（无灰度）`

---

## 1. 使用规则（必须遵守）

- 每个 Batch 必须有唯一编号：`B01/B02/...`。
- 同一时间仅允许 `1` 个 Batch 处于 `in_progress`。
- 不通过闸门不得进入下一批次。
- 每个 Batch 都要同步更新 `xfq-miniapp/doc/migration-tracker.csv`。
- 所有证据必须可追溯（截图、录屏、接口日志、回归结果）。

---

## 2. Batch 生命周期

- `planned`：范围已定义，未开发。
- `in_progress`：开发进行中。
- `dev_done`：开发完成，自测通过。
- `qa_pass`：QA 通过，缺陷闭环。
- `merged`：合并主分支，进入下一批次。
- `blocked`：存在阻断问题，禁止推进。

---

## 3. 每批次必填模板（复制后直接执行）

### 3.1 批次头信息
- Batch 编号：
- 业务域：
- 页面范围（pagePath）：
- 负责人：
- 开始日期：
- 目标完成日期：
- 依赖接口：
- 风险等级（Low/Medium/High）：

### 3.2 范围冻结检查（进入开发前）
- [ ] 页面范围已冻结（无临时加页）。
- [ ] 旧版基线页面已截屏/录屏留档。
- [ ] 接口契约已确认（路径、方法、参数、错误码）。
- [ ] UI 优化范围已确认（仅视觉，不改行为路径）。
- [ ] 与其他 Batch 的边界明确（无交叉改动）。

### 3.3 开发检查（开发中）
- [ ] 页面 `wxml/wxss/js` 按旧版结构完成迁移。
- [ ] 全局样式与组件 token 使用正确（无硬编码风格污染）。
- [ ] tabBar/导航/按钮/卡片样式符合统一规范。
- [ ] 登录态与 storage 兼容（`uerInfo` 等）。
- [ ] 空态/错误态/加载态齐全。
- [ ] 防重复点击与异常分支已处理。
- [ ] 页面埋点和错误上报已接入。

### 3.4 自测检查（dev_done 前）
- [ ] 正常路径通过（至少 1 条完整业务链路）。
- [ ] 异常路径通过（至少 2 条：断网、参数异常或接口失败）。
- [ ] 页面回退与重进状态正确。
- [ ] 下拉刷新/分页行为正确（适用页面）。
- [ ] 命令通过：`cd xfq-miniapp/mp-native && npm run check`
- [ ] 命令通过：`cd xfq-miniapp/mp-native && npm run mock:smoke`

### 3.5 QA 检查（qa_pass 前）
- [ ] 视觉对比通过（结构一致 + UI 优化符合规范）。
- [ ] 交互对比通过（关键按钮、跳转、提示一致）。
- [ ] 接口契约对比通过（无破坏性变更）。
- [ ] Blocker/Critical 缺陷为 `0`。
- [ ] High 缺陷为 `0` 或已获签字豁免。

### 3.6 合并检查（merged 前）
- [ ] 代码评审通过。
- [ ] `migration-tracker.csv` 对应页面状态已更新。
- [ ] 批次报告已归档（见 3.7）。
- [ ] 下个 Batch 的依赖已确认。

### 3.7 批次报告（必须归档）
- 批次结论：`pass/fail`
- 完成页面清单：
- 未完成项与原因：
- 缺陷统计（Blocker/Critical/High/Medium/Low）：
- 风险遗留：
- 回归证据链接：
- 签字（研发/QA/负责人）：

---

## 4. 预排批次（建议执行顺序）

### B01 基础视觉底座
- 范围：全局样式、色板、字号、间距、tabBar 图标、公共组件视觉统一。
- 目标：建立可复用 UI token 和统一页面骨架。

### B02 用户中心主页（高优先）
- 范围：`pages/user/user` + 其直达入口按钮逻辑。
- 目标：完成旧版“我的页”结构 1:1 还原并做视觉优化。

### B03 首页与登录授权
- 范围：`pages/index/index`、`pages/user/login/login`、`pages/getopenid/getopenid`。
- 目标：完成进站与登录闭环。

### B04 商家与搜索
- 范围：`pages/merchant/merchant`、`subpackages/merchant/search/search`、`subpackages/merchant/info/info`。
- 目标：完成商家列表、详情、搜索链路。

### B05 门票主链路
- 范围：`pages/tickets/tickets`、`subpackages/tickets/info`、`subpackages/tickets/order`。
- 目标：完成门票浏览到下单链路。

### B06 优惠券核心
- 范围：`subpackages/coupon/list`、`subpackages/coupon/coupon`、`subpackages/coupon/my_coupon`。
- 目标：完成领券、券详情、我的券。

### B07 订单与支付
- 范围：`subpackages/user/my_order`、`subpackages/user/order_detail`、`subpackages/user/pay_order`、`subpackages/user/pay_detail`、`pages/user/paySuccess`。
- 目标：完成支付订单和门票订单链路。

### B08 退款与售后
- 范围：`pages/user/refunded`、`subpackages/user/my_order_refund`、`subpackages/user/order_refund_detail`。
- 目标：完成退款申请与记录闭环。

### B09 核销链路
- 范围：`subpackages/user/order_CAV`、`subpackages/user/order_CAV_info`、`subpackages/user/coupon_CAV_order/*`、`subpackages/user/coupon_CAV_subscribe/*`、`subpackages/coupon/coupon_CAV*`。
- 目标：完成扫码核销、核销记录与详情。

### B10 用户扩展模块
- 范围：`subpackages/user/set`、`subpackages/user/person/*`、`subpackages/user/collect`、`subpackages/user/comment*`、`subpackages/user/complaints`。
- 目标：完成资料、游客、收藏、评价、投诉。

### B11 预约与任务
- 范围：`subpackages/user/subscribe/*`、`subpackages/user/signIn/*`、`subpackages/user/task/detail`。
- 目标：完成预约、签到、任务闭环。

### B12 内容与协议
- 范围：`subpackages/content/news/*`、`subpackages/content/user/agreement`。
- 目标：完成公告与协议页一致性。

### B13 团相关与尾页清零
- 范围：`GroupCoupon`、`line*`、`travel*`、`list`、`tickets/getopenid/travelorderinfo` 等剩余页。
- 目标：清零 `todo/in_progress`。

### B14 全量回归与发布准备
- 范围：全项目。
- 目标：一次切换前的全量闸门清零。

---

## 5. 缺陷分级与阻断规则

- Blocker：阻断发布，必须修复。
- Critical：核心流程不可用，必须修复。
- High：高风险错误，默认必须修复；仅可在三方签字下豁免。
- Medium/Low：可排入后续版本，但必须建单跟踪。

阻断条件（任一命中即 `blocked`）：
- 存在 Blocker/Critical。
- 契约发生破坏性变化。
- 关键链路（登录/支付/退款/核销）任一失败。
- 自检命令或 mock 冒烟不通过。

---

## 6. 一次切换发布前总清单（总闸门）

- [ ] 所有 Batch 状态为 `merged`。
- [ ] `migration-tracker.csv` 无 `todo/in_progress`。
- [ ] 全量回归通过率 `100%`。
- [ ] 发布冻结期仅含 blocker 修复。
- [ ] Go/No-Go 评审签字完成。
- [ ] 回滚脚本与回滚负责人已就位。

---

## 7. Go / No-Go 签字页（发布前）

- 版本号：
- 发布时间窗：
- 研发负责人（签字）：
- QA 负责人（签字）：
- 业务负责人（签字）：
- 发布负责人（签字）：
- 结论：`Go / No-Go`
- 备注：

---

## 8. B14 当前执行记录（2026-02-09）

- 执行状态：`in_progress`
- 开发状态：`todo/in_progress/blocked = 0`
- 待验收状态：`ready_for_qa = 49`
- 自动化校验：
  - `npm run check`：通过（`pages=62, js=93, json=69, warnings=0`）
  - `npm run mock:smoke`：通过（`ALL OK (81 steps)`）
- B14 产物：
  - `xfq-miniapp/doc/b14-full-regression-checklist.md`
  - `xfq-miniapp/doc/b14-go-nogo-template.md`
  - `xfq-miniapp/doc/b14-readiness-report.md`
