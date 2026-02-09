# B14 全量回归执行清单（一次切换前）

适用项目：`xfq-miniapp/mp-native`  
执行批次：`B14`  
目标：将 `migration-tracker.csv` 中全部 `ready_for_qa` 页面推进到 `done`，并满足一次切换发布闸门。

---

## 1. 执行规则（强约束）

- 仅允许 `B14` 执行期间修复 `Blocker/Critical/High`，禁止新增需求。
- 每一条回归项必须有证据（截图/录屏/日志）并可追溯。
- 同一条缺陷必须包含：复现步骤、影响范围、修复提交、回归结论。
- 任一 `Blocker/Critical` 未闭环，直接判定 `No-Go`。

---

## 2. 回归前准备

- [ ] 配置真实联调环境：`xfq-miniapp/mp-native/miniprogram/config/local.js`
- [ ] 已确认 `baseUrl` 可访问，且开发者工具开启或配置合法域名
- [ ] 已准备真实联调账号：普通用户、核销商户用户（含 `mid`）
- [ ] 已准备关键业务数据：可购票景区、可领券、可退款订单、可核销二维码
- [ ] 已明确本轮版本号、构建号、执行人、开始时间

---

## 3. 自动化校验（每次提测必跑）

执行命令：

```bash
cd xfq-miniapp/mp-native && npm run check
cd xfq-miniapp/mp-native && npm run mock:smoke
```

- [ ] `npm run check` 通过
- [ ] `npm run mock:smoke` 通过
- [ ] 命令输出已存档到回归记录

---

## 4. 业务回归矩阵（真实 API）

### 4.1 TabBar 与登录授权
- [ ] `pages/index/index` 首页可加载，券区块与公告交互正常
- [ ] `pages/merchant/merchant` 商家列表、筛选、下拉分页正常
- [ ] `pages/tickets/tickets` 景区列表、筛选、分页正常
- [ ] `pages/user/user` 我的页入口、登录态显示、菜单跳转正常
- [ ] `pages/user/login/login` 登录流程可达，回跳逻辑正常
- [ ] `pages/getopenid/getopenid` 授权绑定流程可达

### 4.2 优惠券主链路
- [ ] 券列表：`subpackages/coupon/list`
- [ ] 券详情：`subpackages/coupon/coupon`
- [ ] 我的券：`subpackages/coupon/my_coupon`
- [ ] 核销页：`subpackages/coupon/coupon_CAV`
- [ ] 核销记录：`subpackages/user/order_CAV` + `subpackages/user/order_CAV_info`

### 4.3 门票与订单支付链路
- [ ] 景区详情：`subpackages/tickets/info`
- [ ] 门票下单：`subpackages/tickets/order`
- [ ] 门票订单列表：`subpackages/user/my_order`
- [ ] 门票订单详情：`subpackages/user/order_detail`
- [ ] 支付成功页：`pages/user/paySuccess` / `pages/common/paySuccess`
- [ ] 支付订单：`subpackages/user/pay_order` + `subpackages/user/pay_detail`

### 4.4 退款与售后链路
- [ ] 退款申请：`pages/user/refunded`
- [ ] 退款列表：`subpackages/user/my_order_refund`
- [ ] 退款详情：`subpackages/user/order_refund_detail`
- [ ] 退款状态在订单详情/列表联动正确

### 4.5 用户中心扩展
- [ ] 个人资料：`subpackages/user/set`
- [ ] 常用游客：`subpackages/user/person/list` + `subpackages/user/person/add`
- [ ] 收藏：`subpackages/user/collect`
- [ ] 评价：`subpackages/user/comment` + `subpackages/user/commentAdd`
- [ ] 反馈：`subpackages/user/complaints`
- [ ] 实名：`pages/user/attestation`

### 4.6 预约与任务链路
- [ ] 分时预约：`subpackages/user/subscribe/subscribe`
- [ ] 我的预约：`subpackages/user/subscribe/my_list`
- [ ] 预约详情：`subpackages/user/subscribe/detail`
- [ ] 预约核销：`subpackages/user/coupon_CAV_subscribe/coupon_CAV_subscribe`
- [ ] 打卡任务：`subpackages/user/signIn/signIn` + `subpackages/user/signIn/info`
- [ ] 打卡券任务：`subpackages/user/task/detail`

### 4.7 内容与协议
- [ ] 公告列表/详情：`subpackages/content/news/news` + `subpackages/content/news/info`
- [ ] 服务协议/隐私政策：`subpackages/content/user/agreement`

### 4.8 B13 尾页链路（必须二次确认）
- [ ] 物流信息：`pages/coupon/logistics`
- [ ] 聚合券列表：`pages/list/list`
- [ ] 团任务页：`pages/user/GroupCoupon/GroupCoupon`
- [ ] 团券列表/详情：`pages/user/GroupCoupon/list` + `pages/user/GroupCoupon/my_coupon`
- [ ] 团核销记录：`pages/user/order_groupCoupon`
- [ ] 团游客信息：`pages/user/GroupCoupon/touristInfo/touristInfo`
- [ ] 酒店任务与代打卡：`pages/user/GroupCoupon/hotelList` + `pages/user/GroupCoupon/hotelUserList`
- [ ] 线路列表/详情：`pages/coupon/lineList` + `pages/coupon/lineInfo`
- [ ] 旅行社线路：`pages/coupon/travelList`
- [ ] travel 订单详情：`pages/getopenid/travelorderinfo`

---

## 5. 非功能回归

- [ ] 下拉刷新、分页、空态、错误态一致性
- [ ] 弱网下提示与重试逻辑可用
- [ ] 重复点击防抖（支付、提交、核销）可用
- [ ] 关键页面首屏耗时可接受（记录关键时间）
- [ ] 分享路径、扫码路径、回跳路径可用

---

## 6. 缺陷分级与闸门

- Blocker：阻断发布，必须修复
- Critical：核心链路不可用，必须修复
- High：默认必须修复（豁免需签字）
- Medium/Low：可排后续版本，但必须登记

发布闸门：
- [ ] Blocker = 0
- [ ] Critical = 0
- [ ] High = 0 或签字豁免
- [ ] `migration-tracker.csv` 无 `ready_for_qa`

---

## 7. 结果回填（执行后）

- [ ] 已更新 `xfq-miniapp/doc/migration-tracker.csv`（全部改为 `done`）
- [ ] 已更新 `xfq-miniapp/doc/b14-readiness-report.md`
- [ ] 已输出 `Go/No-Go` 评审结论（见 `b14-go-nogo-template.md`）

