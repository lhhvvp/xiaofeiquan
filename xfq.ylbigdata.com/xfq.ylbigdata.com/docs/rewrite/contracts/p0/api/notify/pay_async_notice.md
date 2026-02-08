# [api] POST /api/notify/pay_async_notice/model/{model}.html

## 1. 基本信息

- 路径（兼容要求）：
  - 微信支付回调（代码里拼接的 notify_url）：`/api/notify/pay_async_notice/model/{model}.html`
  - 建议同时兼容（便于运维/排障）：`/api/notify/pay_async_notice?model={model}`（可选）
- 源码定位：`app/api/controller/Notify.php:40`
- 控制器/方法：`api/Notify.pay_async_notice()`
- 描述：微信支付结果异步通知（回调验签 + 落库/发券）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- 无需 `Token/Userid`（第三方回调）。
- 以微信支付回调为准：通常 `POST` + `Content-Type: text/xml` / `application/xml`。

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`text/xml` 或 `application/xml`（微信支付回调 XML）
- Path 参数：
  - `model`：支付模型（代码 `ucfirst`，首字母大写）；已知：`Coupon`
- Body：微信支付支付结果通知 XML（由 `yansongda/pay` 负责验签与解析）
  - 关键字段（最小集，实际字段以微信官方为准）：`result_code/out_trade_no/transaction_id/total_fee/openid/time_end/...`
  - 特殊约定：`out_trade_no` 形如 `XFQ{order_no}`，代码会做 `substr(out_trade_no, 3)` 得到内部 `order_no`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：XML（微信支付要求的 success 响应）
  - 典型响应：`<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>`
- 错误码：无（该接口不返回 JSON envelope）

> 现有实现无论处理成功/失败最终都会返回 `pay->success()->send()`（即 success XML），避免微信重复通知；重写时建议保留这一对外行为，同时把失败原因记日志并可观测。

## 5. 副作用与幂等

- 写库（概念级）：
  - 更新支付记录：`BasePaydata`（按 `order_no + model + status=0` 更新为已支付，写入 `transaction_id/total_fee/time_end/...`）
  - 更新订单：`CouponOrder`（写入 `payment_trade/payment_status/payment_datetime/status/payment_code/payment_data_id/update_time`）
  - 若 `model == Coupon`：为用户发券（`CouponIssueUser::issueUserCoupon(...)`）
- 外部调用：无（微信已回调到本接口）
- 幂等策略（必须具备）：微信可能重复回调；若订单/支付记录已是“已支付”，应直接返回 success XML 且不重复发券/重复写状态。

## 6. 测试用例（契约验收）

- 正常用例：合法签名 + `result_code=SUCCESS`，订单与支付记录更新，发券成功，返回 success XML
- 异常用例：
  - 签名/解析失败：仍返回 success XML（与现网一致），但必须记录错误日志
  - `result_code!=SUCCESS`：不更新订单（或仅记录失败），返回 success XML
- 幂等/重放：同一 `out_trade_no` 连续回调多次，不应重复更新为新值、不应重复发券
