# B14 下一轮人工回归执行单（Wave 执行版）

更新时间：`2026-02-09`

适用范围：`xfq-miniapp/mp-native`  
目标：把 `migration-tracker.csv` 中 `ready_for_qa=49` 全部推进为 `done`。

---

## 1. 执行规则（强约束）

- 按 Wave 顺序执行，不允许跳波次。
- 每通过 1 个页面，立即回填 `migration-tracker.csv` 状态为 `done`。
- 每个页面必须留证：`页面截图 + 关键交互录屏 + 接口请求日志`。
- 出现 `Blocker/Critical` 立即中断当前 Wave，先修复再继续。

---

## 2. Wave 1（P0 入口与主链路）

完成标准：入口链路可达、登录态正确、列表与详情可用。

- [ ] `pages/index/index`
- [ ] `pages/merchant/merchant`
- [ ] `pages/tickets/tickets`
- [ ] `pages/user/user`
- [ ] `pages/user/login/login`
- [ ] `pages/getopenid/getopenid`
- [ ] `pages/tickets/info`
- [ ] `pages/getopenid/travelorderinfo`

---

## 3. Wave 2（交易与优惠券）

完成标准：领券/用券/核销/订单关联状态一致。

- [ ] `pages/coupon/list`
- [ ] `pages/coupon/coupon`
- [ ] `pages/coupon/my_coupon`
- [ ] `pages/coupon/coupon_CAV`
- [ ] `pages/coupon/coupon_CAV_Group/coupon_CAV_Group`
- [ ] `pages/coupon/logistics`
- [ ] `pages/coupon/lineList`
- [ ] `pages/coupon/lineInfo`
- [ ] `pages/coupon/travelList`
- [ ] `pages/user/order`

---

## 4. Wave 3（用户中心扩展）

完成标准：资料、评价、预约、任务、实名全部可闭环。

- [ ] `pages/user/set`
- [ ] `pages/user/person/list`
- [ ] `pages/user/person/add`
- [ ] `pages/user/collect`
- [ ] `pages/user/comment`
- [ ] `pages/user/commentAdd`
- [ ] `pages/user/qrcode`
- [ ] `pages/user/complaints`
- [ ] `pages/user/attestation`
- [ ] `pages/user/subscribe/subscribe`
- [ ] `pages/user/subscribe/my_list`
- [ ] `pages/user/subscribe/detail`
- [ ] `pages/user/coupon_CAV_subscribe/coupon_CAV_subscribe`
- [ ] `pages/user/signIn/signIn`
- [ ] `pages/user/signIn/info`
- [ ] `pages/user/task/detail`

---

## 5. Wave 4（商家/内容/团尾页）

完成标准：尾页清零，无跳转死链、无样式断层。

- [ ] `pages/merchant/info/info`
- [ ] `pages/merchant/info/merchant`（并入校验）
- [ ] `pages/user/mymap`
- [ ] `pages/news/news`
- [ ] `pages/news/info`
- [ ] `pages/user/agreement`
- [ ] `pages/search/search`
- [ ] `pages/list/list`
- [ ] `pages/user/GroupCoupon/GroupCoupon`
- [ ] `pages/user/GroupCoupon/list`
- [ ] `pages/user/GroupCoupon/my_coupon`
- [ ] `pages/user/order_groupCoupon`
- [ ] `pages/user/GroupCoupon/touristInfo/touristInfo`
- [ ] `pages/user/GroupCoupon/hotelList`
- [ ] `pages/user/GroupCoupon/hotelUserList`

---

## 6. 回填格式（执行人直接复制）

页面：  
结果：`pass/fail`  
证据：`截图路径 / 录屏路径 / 接口日志路径`  
缺陷：`无` 或 `BUG-编号（等级）`  
回填时间：  
执行人：

Wave1 建议直接使用模板：`xfq-miniapp/doc/b14-wave1-manual-regression-log.md`
缺陷统一登记：`xfq-miniapp/doc/b14-defect-register.csv`

---

## 7. 关口判定

- `Go` 条件：
  - `ready_for_qa = 0`
  - `Blocker/Critical = 0`
  - `High = 0`（或豁免单签字）
- `No-Go` 条件：
  - 任一 Wave 未完成
  - 关键链路（登录/支付/退款/核销）任一失败
