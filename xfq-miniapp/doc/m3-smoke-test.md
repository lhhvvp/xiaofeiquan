# M3（交易闭环）自测 / 冒烟用例清单

用于在微信开发者工具内快速验证 M3 关键链路（支付/订单/退款/核销），并将 `migration-tracker.csv` 的相关页面从 `in_progress` 推进到 `done`。

---

## 0. 前置条件
- [ ] 微信开发者工具已打开项目：`xfq-miniapp/mp-native`
- [ ] 已构建 npm（若项目依赖 npm）：开发者工具「工具 -> 构建 npm」
- [ ] 方案 A（真实 API）：已配置测试环境 `baseUrl`：创建 `xfq-miniapp/mp-native/miniprogram/config/local.js`
  - 参考：`xfq-miniapp/doc/env-config.md`
  - 示例（不要提交到 git）：`module.exports = { baseUrl: 'https://test-api.example.com' }`
- [ ] 方案 B（暂无真实 API）：启用 mock（不发起真实网络请求）
  - 创建 `xfq-miniapp/mp-native/miniprogram/config/local.js`（不要提交到 git）
  - 示例：`module.exports = { mock: true, mockPayment: true }`
- [ ] （仅方案 A 需要）开发者工具已开启「不校验合法域名」或已在小程序后台配置 request 合法域名（否则请求会失败）
- [ ] （仅方案 A 需要）准备测试数据（至少一组即可）：
  - [ ] 可购买的门票/景区数据（能走到 `/ticket/pay`）
  - [ ] 至少 1 笔「未支付」的支付订单（券购买订单，能走到 `/pay/submit`）
  - [ ] 至少 1 笔「已支付」的门票订单（用于退款/二维码展示）

---

## 1. 基础可用性（开屏即能发现配置问题）
- [ ] 打开「我的」页：`pages/user/user`
  - [ ] 若未配置 `baseUrl`，页面提示文案明确（不会静默失败）
  - [ ] 已配置 `baseUrl`，按钮入口可点击跳转

---

## 2. 门票下单 + 微信支付（主路径）
入口：`pages/tickets/tickets` -> 选择景区 -> `subpackages/tickets/info` -> 选择门票 -> `subpackages/tickets/order`

- [ ] 下单页信息校验
  - [ ] 不选日期/不填联系人/游客人数不匹配时，有明确 Toast 提示
  - [ ] 提交按钮具备防重复点击（按钮 loading）
- [ ] 支付拉起
  - [ ] 调用 `/ticket/pay` 返回后能拉起 `wx.requestPayment`
  - [ ] 支付成功跳转：`pages/user/paySuccess`
- [ ] 支付成功页
  - [ ] 「查看订单」跳转：`subpackages/user/my_order?state=`

异常场景（至少验证 1 个）：
- [ ] 支付取消：取消后提示「已取消支付」，不进入成功页
- [ ] 支付失败：提示「支付失败，请稍后重试」（不会卡死在 loading）

---

## 3. 门票订单列表/详情 + 二维码展示
入口：`pages/user/user` -> 「门票订单」-> `subpackages/user/my_order` -> 点击进入 `subpackages/user/order_detail`

- [ ] 订单列表
  - [ ] 下拉刷新可用
  - [ ] 分页加载可用（到底提示 no-more）
  - [ ] 空态展示 `ui-empty`；请求失败展示 `ui-error` 且可重试
- [ ] 订单详情
  - [ ] 显示订单状态/金额/订单号（可复制）
  - [ ] `paid` 状态展示二维码（canvas 渲染正常）
  - [ ] 权益二维码切换（picker）可用；不可用时提示明确
- [ ] 待支付订单支付
  - [ ] 详情页「立即购买」能拉起支付
  - [ ] 按钮具备防重复点击（loading/disabled）

---

## 4. 退款（单人/全部）+ 退款记录
入口：`subpackages/user/order_detail` -> 游客列表「退款」/「全部退款」-> `pages/user/refunded`

- [ ] 退款提交
  - [ ] 必填校验（退款备注不能为空）
  - [ ] 提交按钮具备防重复点击（loading）
  - [ ] 提交成功后返回上一页，并可在订单详情看到退款状态变化（如：审核中/完成退款）

退款记录：
- [ ] `pages/user/user` -> 「售后（退款记录）」-> `subpackages/user/my_order_refund`
  - [ ] 列表分页/空态/错误态正常
  - [ ] 点击进入 `subpackages/user/order_refund_detail`，字段展示完整
  - [ ] 订单号可复制

---

## 5. 支付订单（券购买）列表/详情 + 支付/退款
入口：`pages/user/user` -> 「支付订单」-> `subpackages/user/pay_order`

- [ ] 列表分页/空态/错误态正常
- [ ] 未支付订单
  - [ ] 点击「立即支付」能拉起支付
  - [ ] 按钮具备防重复点击（loading/disabled）
  - [ ] 支付成功后列表状态刷新
- [ ] 已支付订单退款
  - [ ] 打开退款弹窗，退款理由必填
  - [ ] 提交按钮具备防重复点击（loading/disabled）
  - [ ] 提交成功后列表状态刷新（显示已退款）
- [ ] 订单详情：`subpackages/user/pay_detail`
  - [ ] 能正常进入并展示字段

---

## 6. 扫码核销（券 / 门票）
入口：`pages/user/user` -> 「扫码核销」-> `wx.scanCode`

- [ ] 前置：当前账号具备 `mid`（否则入口不显示/或提示未绑定）
- [ ] 扫码结果为 JSON，按 `type` 分流到对应核销页

### 6.1 券核销（`type === 'user'`）
跳转：`subpackages/coupon/coupon_CAV`
- [ ] 核销页：
  - [ ] `idToCoupon` 能查到券信息并展示
  - [ ] `writeoff` 返回成功/失败时状态提示明确

### 6.2 门票核销（整单，`type === 'order'`）
跳转：`subpackages/user/coupon_CAV_order/coupon_CAV_order`
- [ ] 能调用 `/ticket/writeOff` 并提示核销结果
- [ ] 能展示订单信息（`/ticket/getOrderDetail`）

### 6.3 门票核销（单人，`type === 'order_user'`）
跳转：`subpackages/user/coupon_CAV_order/coupon_CAV_user`
- [ ] 能调用 `/ticket/writeOff` 并提示核销结果
- [ ] 能展示游客/票种信息（`/ticket/getOrderDetailDetail`）

### 6.4 核销记录（商户）
入口：`pages/user/user` -> 「核销记录」-> `subpackages/user/order_CAV` -> `subpackages/user/order_CAV_info`
- [ ] 列表分页/空态/错误态正常
- [ ] 点击进入详情页，能展示核销时间与使用细则
- [ ] 「适用于」跳转到对应商户列表：`subpackages/coupon/list`

---

## 7. 自测记录与“推进 done”的操作
当以上用例通过后，更新：
- [ ] `xfq-miniapp/doc/migration-tracker.csv`：将已验证的相关页面 `status` 改为 `done`
- [ ] `notes` 追加自测信息（建议格式）：`self-test: 2026-02-05; devtools; ok`

建议优先推进为 `done` 的页面（M3 相关）：
- `subpackages/tickets/order`
- `pages/user/paySuccess`
- `pages/common/paySuccess`
- `subpackages/user/my_order`
- `subpackages/user/order_detail`
- `pages/user/refunded`
- `subpackages/user/my_order_refund`
- `subpackages/user/order_refund_detail`
- `subpackages/user/pay_order`
- `subpackages/user/pay_detail`
- `subpackages/user/coupon_CAV_order/coupon_CAV_order`
- `subpackages/user/coupon_CAV_order/coupon_CAV_user`
