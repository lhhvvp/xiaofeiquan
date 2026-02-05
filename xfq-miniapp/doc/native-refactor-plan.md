# 消费券小程序（微信原生）重构计划（草案）

> 适用范围：`E:\proj\消费券\消费券代码\xfq-miniapp`  
> 旧工程（对照）：`xfq-miniapp/xfq-miniapp`（`uni-app` / Vue2）  
> 新工程（拟建）：`xfq-miniapp/mp-native`（微信小程序原生）

**版本**：v1.0  
**最后更新**：2026-02-05  
**目标上线**：TBD  
**负责人/评审人**：TBD

---

## 0. 执行附件与入口
- 文档索引：`xfq-miniapp/doc/README.md`
- 迁移跟踪：`xfq-miniapp/doc/migration-tracker.csv`
- 页面验收 DoD：`xfq-miniapp/doc/page-dod-checklist.md`
- 决策记录：`xfq-miniapp/doc/decision-log.md`
- 风险台账：`xfq-miniapp/doc/risk-register.csv`
- 发布/灰度/回滚 Runbook：`xfq-miniapp/doc/release-runbook.md`
- 可观测性规范与事件表：`xfq-miniapp/doc/observability-spec.md`、`xfq-miniapp/doc/observability-events.csv`
- 多环境配置矩阵：`xfq-miniapp/doc/env-config.md`、`xfq-miniapp/doc/env-matrix.csv`
- 旧工程扫描脚本：`xfq-miniapp/doc/tools/scan-legacy.ps1`

---

## 1. 背景与目标

### 1.1 背景（为什么要做）
- 现状为 `uni-app + Vue2`，当前只投放微信端，多端诉求为 0。
- 主要痛点：**维护成本高**（框架适配/差异处理/工程不透明导致排障成本高），**微信新特性落地慢**（受框架封装与版本兼容限制）。

### 1.2 重构目标（做到什么程度算成功）
1. **功能等价**：核心业务链路与线上版本一致（登录/领券/用券/商家/门票/订单/支付/退款/评价等）。
2. **工程可维护**：模块边界清晰、公共能力统一封装、可观测（日志/埋点/错误上报）、有可执行的发布与回滚流程。
3. **微信原生能力可快速接入**：新 API/新组件/新渲染特性可在 1~3 天内完成 PoC 并进入迭代。
4. **性能与包体可控**：分包策略明确；首屏与列表页满足目标性能预算（见第 9 章）。

### 1.3 非目标（本次不做或后置）
- 不在本次重构中改变后端业务逻辑与数据结构（除非为兼容/性能必须调整，需走接口评审）。
- 不追求 UI 全量重设计（优先保证业务等价与工程质量）。
- 不把“所有页面一次性上线”作为目标（采用分阶段灰度/分模块验收）。

---

## 2. 现状盘点（基于仓库扫描）

### 2.1 旧工程关键事实（用于迁移评估）
- 工程目录：`xfq-miniapp/xfq-miniapp`
- 页面路由：`xfq-miniapp/xfq-miniapp/pages.json`
  - 有效页面 **63 个**（其中 `tabBar` 4 个：`index` / `merchant` / `tickets` / `user`）
- 公共能力：
  - 请求封装：`xfq-miniapp/xfq-miniapp/httpRequest.js`（Token/Userid 头、网络检测、统一登录跳转、`wx.reportEvent` 监控）
  - 通用工具：`xfq-miniapp/xfq-miniapp/common/common.js`（缓存 TTL、定位授权、支付封装、格式化等）
  - 全局状态：`xfq-miniapp/xfq-miniapp/store/index.js`（Vuex：merchant/groupCoupon/uerInfo/刷新标记等）
- 关键业务能力线索（用于重构优先级）：
  - 支付：`uni.requestPayment`（`tickets/order`、`user/order_detail`、`getopenid/travelorderinfo` 等页面）
  - 定位：`uni.getLocation` + 授权/缓存（多页面依赖）
  - 二维码：`components/my-qrcode` + 页面展示
  - 埋点：`common/baidu/mtj-wx-sdk.js` + `wx.reportEvent`
- 配置：
  - `manifest.json` 中 `mp-weixin` 已开启 `usingComponents`、`lazyCodeLoading`
  - `project.config.json` 与 `manifest.json` 的 `appid` **可能不一致**（需确认线上真实 AppID 与项目配置来源）

### 2.2 当前页面清单（用于迁移拆分）
> 以 `pages.json` 为准；页面按模块分组，便于分包与迁移排期。

**TabBar**
- `pages/index/index`
- `pages/merchant/merchant`
- `pages/tickets/tickets`
- `pages/user/user`

**登录/授权**
- `pages/user/login/login`
- `pages/getopenid/getopenid`
- `pages/getopenid/travelorderinfo`

**优惠券**
- `pages/coupon/list`
- `pages/coupon/coupon`
- `pages/coupon/my_coupon`
- `pages/coupon/logistics`
- `pages/coupon/lineList`
- `pages/coupon/lineInfo`
- `pages/coupon/travelList`
- `pages/coupon/coupon_CAV`
- `pages/coupon/coupon_CAV_Group/coupon_CAV_Group`

**商家**
- `pages/merchant/info/info`
- `pages/merchant/info/merchant`
- `pages/user/mymap`

**门票**
- `pages/tickets/info`
- `pages/tickets/order`

**内容/公告**
- `pages/news/news`
- `pages/news/info`

**搜索/列表**
- `pages/search/search`
- `pages/list/list`

**用户中心（订单/资料/售后/评价等）**
- `pages/user/order`
- `pages/user/my_order`
- `pages/user/order_detail`
- `pages/user/pay_order`
- `pages/user/pay_detail`
- `pages/user/paySuccess`
- `pages/common/paySuccess`
- `pages/user/refunded`
- `pages/user/my_order_refund`
- `pages/user/order_refund_detail`
- `pages/user/order_CAV`
- `pages/user/order_CAV_info`
- `pages/user/order_groupCoupon`
- `pages/user/coupon_CAV_order/coupon_CAV_order`
- `pages/user/coupon_CAV_order/coupon_CAV_user`
- `pages/user/coupon_CAV_subscribe/coupon_CAV_subscribe`
- `pages/user/set`
- `pages/user/attestation`
- `pages/user/person/add`
- `pages/user/person/list`
- `pages/user/collect`
- `pages/user/agreement`
- `pages/user/comment`
- `pages/user/commentAdd`
- `pages/user/qrcode`
- `pages/user/complaints`
- `pages/user/signIn/signIn`
- `pages/user/signIn/info`
- `pages/user/subscribe/subscribe`
- `pages/user/subscribe/my_list`
- `pages/user/subscribe/detail`
- `pages/user/task/detail`

**团购/旅行团相关**
- `pages/user/GroupCoupon/GroupCoupon`
- `pages/user/GroupCoupon/list`
- `pages/user/GroupCoupon/my_coupon`
- `pages/user/order_groupCoupon`
- `pages/user/GroupCoupon/touristInfo/touristInfo`
- `pages/user/GroupCoupon/hotelList`
- `pages/user/GroupCoupon/hotelUserList`

> 注：上面的清单用于“拆分、排期、分包”；是否全部 P0 取决于上线策略（见第 6 章）。

---

## 3. 目标技术方案（微信原生）

### 3.1 总体原则（降低维护成本的关键）
- **把“公共能力”做成稳定层**：请求/鉴权/缓存/埋点/权限/错误处理统一到 `services/`、`utils/`，页面只写业务逻辑。
- **减少“页面直接调后端”的散点**：所有接口调用走 `services/api/*`，便于接口变更与 Mock。
- **把“工程化”变成强约束**：Lint/格式化/类型/目录约定/提交规范/发布规范。
- **分包先行**：一开始就按模块拆分 subpackage，避免后期包体返工。

### 3.2 新工程目录结构（建议）
```
xfq-miniapp/mp-native/
  miniprogram/
    app.ts | app.js
    app.json
    app.wxss
    sitemap.json
    assets/
    components/
    pages/               # tabBar 及少量主包页面
    subpackages/
      coupon/
      merchant/
      tickets/
      user/
      content/
    services/
      request.ts         # 统一请求封装（Token/错误/重试/Loading）
      auth.ts            # 登录/刷新/实名校验跳转策略
      api/               # 分模块 API（coupon.ts、user.ts...）
      monitor.ts         # wx.reportEvent + 日志上报
    utils/
      cache.ts           # TTL cache（兼容旧 key：uerInfo/coord）
      date.ts            # 日期格式化（moment -> dayjs 或兼容封装）
      permission.ts      # 定位/相册等授权与引导
      navigation.ts      # 跳转封装（带埋点/防抖/重复点击保护）
    store/               # 可选：全局状态（轻量 store 或 mobx）
    styles/
      tokens.wxss
      mixins.wxss
  project.config.json
  package.json
  tsconfig.json          # 若启用 TS
```

### 3.3 语言与工程化选型（建议）
- **TypeScript：建议启用**
  - 价值：降低重构期与长期的“隐式错误/接口字段变更”成本；对大项目更关键。
  - 若团队短期不接受：允许“先 JS，后 TS”，但至少把 `services/`、`utils/`、`store/` 先 TS 化。
- **依赖管理**：使用 npm（微信开发者工具“构建 npm”），统一版本与 lockfile。
- **代码规范**：ESLint + Prettier（CI 强制，避免风格争议消耗人力）。

### 3.4 关键基础能力设计（必须先做）
1. **请求层（request）**
   - baseURL（支持 dev/test/prod 切换）
   - 自动注入 `Token`/`Userid`（兼容旧 `uerInfo`）
   - 统一错误码处理（旧逻辑含 110~114/用户异常等）
   - Loading 策略：可配置（全局/静默/并发计数）
   - 监控：请求耗时/状态码/错误信息（对齐旧 `businessMonitor`）
2. **鉴权（auth）**
   - 微信 `wx.login` -> 后端换 `token/openid/uid`
   - 统一“未登录/实名未完成”的路由守卫策略
   - 兼容升级：保留旧 storage key，避免用户升级后频繁掉线
3. **缓存（cache）**
   - TTL cache（对齐 `myCache`），统一序列化，提供 clear/namespace
4. **定位与权限（permission）**
   - 定位获取/失败回退/设置页引导（对齐旧 `authorize/getLocation`）
5. **全局状态（store）**
   - 建议最小化：只存“跨页面、频繁读取”的数据（例如 `uerInfo`、部分首页缓存）
   - 方案 A（更原生、更轻）：自建轻量 store + subscribe（无外部依赖）
   - 方案 B（更成熟）：`mobx-miniprogram`（建议在 PoC 阶段确定）

---

## 4. 重构策略（如何保证做得完）

### 4.1 总体策略：并行开发 + 分阶段上线
- 保留旧工程作为**行为基准**（功能、接口、UI、埋点对齐）。
- 新工程独立目录开发（减少互相污染），通过里程碑逐步达到可替换状态。
- 上线采用 **灰度/分批**：先内部体验版 -> 小流量灰度 -> 全量替换。

### 4.2 禁止“大爆炸式一次性替换”
一次性全量重写的主要风险：
- 回归成本不可控（63 页 + 复杂支付/定位/二维码）
- 线上问题定位困难（缺少对照与灰度）
- 需求插入导致无限延期（范围漂移）

因此采用“模块验收 + 灰度切换”的方式，将风险拆散。

### 4.3 范围控制（防止重构工程变成无底洞）
建立三条硬规则：
1. **重构期默认不接“非必要新需求”**（除非能明显降低迁移成本或属于合规/线上阻断）。
2. 新增需求必须标记：是否影响迁移路径、是否可在旧工程先上线。
3. 每个里程碑都有“必须项/可选项”，可选项不阻塞上线。

---

## 5. 里程碑与交付物（建议）

> 时间为“建议节奏”，需结合团队规模与业务复杂度调整。

### M0：启动与基线（1 周）
- 输出：本文档 v1.0（评审通过）、页面/接口清单、分包方案草图、风险清单
- 决策：TS/Store/UI 组件库是否采用（必须在 M1 结束前定稿）

### M1：新工程骨架与基础设施（1~2 周）
- 交付：
  - 新项目可在微信开发者工具运行
  - request/auth/cache/monitor/permission 基础能力可用
  - 统一 UI 基础组件（导航栏/空态/列表加载/弹窗）最小集
  - CI（至少 lint + 基础构建检查）
- 验收：登录态可跑通、请求监控上报可用、分包配置落地

#### M1 封板清单（必须项）
- [x] 分包已落地（`subpackages/*`），路由可正常跳转
- [x] request/auth/cache/monitor/location 已可用并被页面实际使用
- [x] 登录态可跑通（`/index/miniwxlogin`），storage 兼容旧 `uerInfo`
- [x] 自检脚本可运行：`cd xfq-miniapp/mp-native && npm run check`（或 `node tools/check-miniprogram.js`）
- [x] CI 集成：至少接入 `npm run check`（避免低级错误进入主分支）
- [x] UI 最小组件集沉淀到 `components/`（空态/列表 footer/错误态/按钮样式）

### M2：P0 主链路（2~4 周）
建议优先打通“用户从打开到完成一次关键转化”的闭环：
- 登录/授权：`user/login`、`getopenid/getopenid`
- TabBar 4 页最小可用：`index`/`merchant`/`tickets`/`user`
- 核心业务：领券/券列表/券详情/我的券
- 商家详情/地图页（依赖定位）
- 门票详情/下单页（不一定含支付，视业务）

### M3：交易闭环（2~3 周）
- 支付链路（`requestPayment`）+ 支付成功页
- 订单列表/详情/售后（退款/退票）核心路径
- 二维码展示/核销相关页面（若为核心业务则提升到 M2）

### M4：非核心但必须功能补齐（2~4 周）
- 公告/搜索/收藏/投诉/评价/签到/任务/团购旅行团等
- 对齐旧工程的埋点、日志、异常处理

### M5：全面回归与性能/包体优化（1~2 周）
- 包体分包复查、冗余资源清理、长列表优化
- 全量回归用例通过（见第 9 章）
- 灰度策略与回滚开关准备

### M6：灰度上线与全量切换（1~2 周）
- 体验版/灰度/全量
- 线上监控指标达标（崩溃率、接口错误率、支付失败率等）
- 切换后 2 周内保留旧工程紧急修复能力（必要时回滚）

---

## 6. 页面迁移策略与优先级（建议）

### 6.1 分包建议（按模块拆）
- **主包（pages/）**：仅 TabBar 页 + 登录/授权最小集 + 通用落地页
  - `pages/index/index`
  - `pages/merchant/merchant`
  - `pages/tickets/tickets`
  - `pages/user/user`
  - `pages/user/login/login`
  - `pages/getopenid/getopenid`
- **分包 subpackages/**
  - `coupon`：券列表/详情/我的券/物流/路线/旅行团相关券
  - `merchant`：商家详情/门店详情/地图
  - `tickets`：门票详情/下单
  - `user`：订单/售后/评价/资料/签到/任务等
  - `content`：公告/协议等

> 目标：主包控制在最小，避免后期因 2MB 限制返工。

### 6.2 优先级定义（用于排期与灰度）
- **P0**：没有它无法完成“打开-登录-浏览-下单/领券/用券”主链路
- **P1**：非主链路但高频/售后强相关/投诉风险高
- **P2**：低频功能、可后置

建议先把每个页面标注 P0/P1/P2（由产品/运营/技术共同确认），并建立“页面验收单”（见第 9 章）。

---

## 7. 接口与数据契约（关键保障）

### 7.1 接口清单与归口
目标：从“页面直接拼接口”改为“接口归口到 `services/api/*`”。

落地动作：
1. 通过脚本扫描旧工程，生成“接口清单”（URL、调用页面、参数、返回字段样例）。
   - 脚本：`xfq-miniapp/doc/tools/scan-legacy.ps1`
   - 产物：`xfq-miniapp/doc/legacy-pages.txt`、`xfq-miniapp/doc/legacy-endpoints.json`、`xfq-miniapp/doc/legacy-storage-keys.json`、`xfq-miniapp/doc/legacy-uni-apis.json`、`xfq-miniapp/doc/legacy-wx-apis.json` 等
2. 与后端确认：错误码、鉴权、分页、幂等、支付回调、退款流程。
3. 形成接口契约文档（可用 Swagger/Apifox/Markdown 均可）。

### 7.2 升级兼容（存储 key/登录态）
旧工程使用的关键 storage：
- `uerInfo`（注意拼写）
- `coord`（定位缓存）

迁移建议：
- 新工程继续兼容读取旧 key，并在必要时做一次性迁移（例如写入 `userInfo` 但保留 `uerInfo`）。
- 任何会导致“用户全量掉线”的变更必须提前灰度验证。

---

## 8. 测试与质量保障（把回归成本降下来）

### 8.1 测试分层
- **单元测试**：`utils/`、`services/`（请求签名、缓存 TTL、错误码处理等）
- **组件测试**：关键基础组件（空态、分页列表、二维码组件封装）
- **端到端测试（E2E）**：支付前流程、领券/用券、订单详情、退款等关键链路

### 8.2 回归用例（建议产物）
- 建议为每个页面输出 5~10 条“关键路径用例”，并且与旧工程对齐期望结果。
- 对支付/退款/核销等高风险链路，增加“异常场景用例”（网络断开、token 过期、重复点击、接口超时等）。

---

## 9. 性能/包体/可观测性预算（上线门槛）

### 9.1 建议的硬门槛（可调整）
- 主包体积：< 1.5MB（给后续迭代留余量）
- 首屏关键指标：进入首页到可交互时间（TTI）可接受（需用真机统计）
- 列表页：长列表滑动不卡顿（必要时使用分页/虚拟列表/骨架屏）

### 9.2 可观测性（必须有）
- 请求监控：成功率、耗时分布、状态码、业务错误码
- 关键链路埋点：登录成功、领券成功、支付成功/失败、退款成功/失败
- JS 错误收集：页面报错、Promise 未捕获

---

## 10. 发布、灰度与回滚（保证“敢上线”）

### 10.1 灰度策略
- 体验版 -> 小流量灰度（按渠道/版本/用户分组）-> 全量
- 灰度阶段必须有“关键指标看板”（支付失败率、接口错误率、崩溃率）

### 10.2 回滚策略
- 新旧工程共存一段时间：
  - 若采用“新小程序替换旧小程序”：需保留旧版本可快速回滚（紧急发布通道/灰度开关）
  - 若采用“同一小程序内页面切换”：可通过配置开关决定走新/旧页面（推荐，但实现需要更多工程设计）

> 具体选择由产品与运维策略决定，M0 必须定下来。

---

## 11. 组织与协作（减少沟通损耗）

### 11.1 建议的角色分工
- Tech Lead：架构/工程化/质量门槛/里程碑把控
- 业务开发：按模块负责（coupon/merchant/tickets/user/content）
- 后端对接：接口契约与联调，支付/退款/核销等关键链路兜底
- QA：用例库、回归节奏、灰度观察

### 11.2 工作流建议
- 每个页面迁移必须包含：接口归口、错误处理、埋点、用例、自测记录
- PR 强制 code review（至少 1 个 reviewer）
- 每周一次里程碑检查：完成度、风险、范围漂移

---

## 12. 风险清单（必须显式管理）

| 风险 | 触发信号 | 影响 | 应对策略 |
|---|---|---|---|
| 范围漂移 | 重构期持续插需求 | 无限延期 | 需求分级 + 里程碑“必须项/可选项” |
| 支付/退款回归不足 | 灰度期失败率升高 | 线上事故 | 关键链路用例 + 真实环境演练 + 灰度看板 |
| 包体超限 | 主包持续增长 | 无法发布 | 早期分包 + 资源审计 + 依赖控制 |
| 团队对原生不熟 | 进度持续偏差 | 成本上升 | 先做 P0 PoC + 编码规范 + Pair review |
| AppID/配置混乱 | 多套配置无来源 | 发布风险 | M0 确认唯一来源，写入文档与 CI |

---

## 附录 A：下一步需要确认的问题（M0 必须定稿）
1. 线上真实 **AppID**、多环境域名（dev/test/prod）、静态资源域名与 CDN 策略。
2. 是否启用 **TypeScript**（推荐）与全局状态方案（自建 or MobX）。
3. 是否引入 UI 组件库（TDesign/Vant/自研），以及 UI 复用策略（是否需要全量一致）。
4. 新旧切换策略：新小程序替换 or 同 App 内开关切换。
5. 灰度与回滚的组织流程（谁负责发布、谁负责监控、多久可回滚）。
