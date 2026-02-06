# M4（非核心补齐）自测 / 冒烟用例清单

用于在微信开发者工具内快速验证 M4「内容模块」的关键路径（公告/协议），并将 `migration-tracker.csv` 的相关页面从 `ready_for_qa` 推进到 `done`。

---

## 0. 前置条件
- [ ] 微信开发者工具已打开项目：`xfq-miniapp/mp-native`
- [ ] 方案 A（真实 API）：已配置测试环境 `baseUrl`（创建 `xfq-miniapp/mp-native/miniprogram/config/local.js`）
  - 参考：`xfq-miniapp/doc/env-config.md`
- [ ] 方案 B（暂无真实 API）：启用 mock（不发起真实网络请求）
  - 创建 `xfq-miniapp/mp-native/miniprogram/config/local.js`（不要提交到 git）
  - 示例：`module.exports = { mock: true, mockPayment: true }`
- [ ] 工程自检通过：`cd xfq-miniapp/mp-native && npm run check`
- [ ] mock 冒烟（可选但推荐）：`cd xfq-miniapp/mp-native && npm run mock:smoke`

---

## 1. 公告列表
入口：`pages/user/user` -> 「公告」-> `subpackages/content/news/news`

- [ ] 列表能加载并展示标题/时间/浏览量
- [ ] 空态显示正常（`ui-empty`）
- [ ] 接口失败时错误态可重试（`ui-error`）

---

## 2. 公告详情
入口：公告列表点击任意一条 -> `subpackages/content/news/info?id=<id>`

- [ ] 能加载并展示标题/时间/浏览量
- [ ] 富文本内容可渲染（`rich-text`）
- [ ] 接口失败时错误态可重试

---

## 3. 服务协议 / 隐私政策
入口：`pages/user/user` -> 「服务协议」/「隐私政策」-> `subpackages/content/user/agreement`

- [ ] 「服务协议」能渲染来自 `/index/system` 的 `service` 富文本
- [ ] 「隐私政策」能渲染来自 `/index/system` 的 `policy` 富文本
- [ ] 缺少内容时有明确提示并可重试

---

## 4. 个人资料（昵称/头像）
入口：`pages/user/user` -> 「个人资料」-> `subpackages/user/set`

- [ ] 未登录时提示明确，并能一键跳转登录（登录后可回到该页）
- [ ] 登录后能正常加载资料（`/user/index`）：昵称/头像/手机号/姓名/身份证号
- [ ] 修改昵称后提交成功（`/user/edit`），返回上一页不报错
- [ ] 更换头像：
  - mock 模式：仅预览更新即可
  - 真实 API：`/upload/index` 上传成功后头像 URL 更新

---

## 5. 我的收藏
入口：`pages/user/user` -> 「我的收藏」-> `subpackages/user/collect`

- [ ] 未登录时提示明确，并能一键跳转登录（登录后可回到该页）
- [ ] 登录后列表可加载（`/user/collection`），空态/错误态/重试正常
- [ ] 支持下拉刷新与上拉分页（到达末尾显示 no-more）
- [ ] 点击商户可进入详情：`subpackages/merchant/info/info?id=<id>`

---

## 6. 反馈与建议
入口：`pages/user/user` -> 「反馈与建议」-> `subpackages/user/complaints`

- [ ] 未登录时提示明确，并能一键跳转登录（登录后可回到该页）
- [ ] 必填校验：内容为空时有 Toast 提示
- [ ] 可添加/预览/删除图片（最多 3 张）
- [ ] 提交成功：调用 `/user/feed_back` 返回成功后自动返回上一页

---

## 7. 我的评价 / 发表评论
入口：
- 我的评价：`pages/user/user` -> 「我的评价」-> `subpackages/user/comment`
- 发表评论：`subpackages/user/order_detail` 底部「我要评价」-> `subpackages/user/commentAdd?id=<order_id>`
  - mock 模式：内置一条 `used` 订单 `id=80003` 便于验证入口

- [ ] 我的评价：未登录时提示明确，并能一键跳转登录（登录后可回到该页）
- [ ] 我的评价：登录后列表可加载（`/ticket/getCommentList`，`user_id`），空态/错误态/重试正常
- [ ] 支持下拉刷新与上拉分页（到达末尾显示 no-more）
- [ ] 发表评论：评分/内容必填校验正常
- [ ] 发表评论：提交成功（`/ticket/writeComment`）后返回上一页，并在「我的评价」里可见新评论

---

## 8. 我的预约 / 分时预约
入口：
- 预约下单：`pages/tickets/tickets` -> 进入任意景区详情 `subpackages/tickets/info` -> 「立即预约」-> `subpackages/user/subscribe/subscribe?seller_id=<id>`
- 我的预约：`pages/user/user` -> 「我的预约」-> `subpackages/user/subscribe/my_list`

- [ ] 预约下单：日期/时间段可加载（`/appt/getDatetime`），无数据时空态正常
- [ ] 预约下单：数量调整正常（上限受库存/后端 number/游客数限制）
- [ ] 预约下单：游客选择弹层可用，且“游客人数需与数量一致”
- [ ] 预约下单：提交成功（`/appt/createAppt`）后可进入「我的预约」
- [ ] 我的预约：状态 tab 切换（全部/待核销/已核销/已取消）正常；分页/空态/错误态可重试
- [ ] 预约详情：详情可加载（`/appt/getDetail`），二维码可渲染并支持全屏/复制
- [ ] 预约核销：在「我的」->「扫码核销」扫描预约二维码（`type=subscribe`），进入 `subpackages/user/coupon_CAV_subscribe/coupon_CAV_subscribe` 并提示核销成功（`/appt/writeOff`）
- [ ] 预约详情：待核销状态可取消（`/appt/cancelAppt`），取消后返回列表并可在“已取消”看到记录

---

## 9. 打卡任务 / 打卡
入口：
- 打卡任务列表：`pages/user/user` -> 「打卡任务」-> `subpackages/user/signIn/signIn`
- 去打卡：打卡任务列表任意任务 -> 「打卡」-> `subpackages/user/signIn/info?sid=<spot_id>&type=<type>`

- [ ] 未登录时提示明确，并能一键跳转登录（登录后可回到该页）
- [ ] 打卡任务列表：可加载并按“未打卡优先”展示（`/user/clock_list`）；空态/错误态可重试（`ui-empty`/`ui-error`）
- [ ] 去打卡：授权定位后能自动填充地点信息（`/index/transform`），也可手动修改
- [ ] 去打卡：可选择/预览/删除图片（mock 模式不上传）
- [ ] 提交打卡成功（`/user/clock` 或 `/user/hotel_clock`）后返回列表，任务状态变为“已打卡”

---

## 10. 打卡券任务 / 扫码打卡
入口：`pages/user/user` -> 「打卡券任务」-> `subpackages/user/task/detail?couponId=<id>&couponTitle=<title>`

- [ ] 列表可加载（`/coupon/getUserCouponRecordList`），空态/错误态可重试
- [ ] 点击「打卡」扫码提交（`/user/userClock`）成功后，列表新增一条打卡记录
  - mock 建议扫码内容：`MOCK-CLOCK-101`（可触发 Seller.id=101 的记录）

---

## 11. 当前位置地图
入口：`pages/user/user` -> 「当前位置地图」-> `subpackages/merchant/mymap`

- [ ] 打开后能显示当前位置 marker
- [ ] 点击「复制」可复制经纬度文本
- [ ] 点击「更新位置」可重新定位并更新 marker（无权限时提示明确）

---

## 12. 团购旅行团核销
入口：`pages/user/user` -> 「扫码核销」扫描团购二维码（`type=groupCoupon`）-> `subpackages/coupon/coupon_CAV_Group/coupon_CAV_Group`

- [ ] 能拉取核销详情并展示人数/旅行团/旅行社/使用说明（`/user/tour_coupon_group`）
- [ ] 自动提交核销并提示核销成功（`/user/writeoff_tour`）
  - mock：在 DevTools 的 `scanCode` 模拟器中粘贴：
    `{"id":91001,"qrcode":"MOCK-QR-GROUP-91001","coord":{"latitude":38.285,"longitude":109.734},"type":"groupCoupon"}`

---

## 13. 自测记录与“推进 done”
完成以上用例后，更新：
- [ ] `xfq-miniapp/doc/migration-tracker.csv`：将本次已覆盖页面的 `status` 改为 `done`（`content/pages/news/*`、`content/pages/user/agreement`、`user/pages/user/set`、`user/pages/user/collect`、`user/pages/user/complaints`、`user/pages/user/comment`、`user/pages/user/commentAdd`、`user/pages/user/subscribe/*`、`user/pages/user/coupon_CAV_subscribe/*`、`user/pages/user/signIn/*`、`user/pages/user/task/detail`、`merchant/pages/user/mymap`、`coupon/pages/coupon/coupon_CAV_Group/coupon_CAV_Group`）
- [ ] `notes` 追加自测信息（建议格式）：`self-test: 2026-02-06; devtools; ok` 或 `self-test: 2026-02-06; mock; ok; pending real-api`
