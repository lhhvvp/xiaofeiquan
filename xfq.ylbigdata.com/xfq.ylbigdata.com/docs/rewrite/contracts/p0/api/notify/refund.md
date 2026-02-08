# [api] POST /api/notify/refund/model/{model}.html

## 1. 基本信息

- 路径（兼容要求）：
  - 微信退款回调（代码里拼接的 notify_url）：`/api/notify/refund/model/{model}.html`
  - 建议同时兼容（便于运维/排障）：`/api/notify/refund?model={model}`（可选）
- 源码定位：`app/api/controller/Notify.php:102`
- 控制器/方法：`api/Notify.refund()`
- 描述：微信退款结果异步通知（回调验签 + 落库/更新订单退款状态）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- 无需 `Token/Userid`（第三方回调）。
- 以微信退款回调为准：通常 `POST` + `Content-Type: text/xml` / `application/xml`。

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`text/xml` 或 `application/xml`（微信退款回调 XML）
- Path 参数：
  - `model`：退款模型（代码 `ucfirst`，首字母大写）；已知：`Coupon`
- Body：微信退款通知 XML（包含 `req_info` 加密字段；由 `yansongda/pay` 负责验签与解析/解密）
  - 关键字段（最小集，实际字段以微信官方为准）：`return_code/req_info/out_trade_no/refund_status/out_refund_no/refund_id/refund_fee/success_time/...`
  - 特殊约定：`out_trade_no` 形如 `XFQ{order_no}`，代码会做 `substr(out_trade_no, 3)` 得到内部 `order_no`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：XML（微信退款通知要求的 success 响应）
  - 典型响应：`<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>`
- 错误码：无（该接口不返回 JSON envelope）

> 现有实现无论处理成功/失败最终都会返回 success XML；重写时建议保留这一对外行为，同时通过日志/指标追踪失败原因。

## 5. 副作用与幂等

- 写库（概念级）：
  - 更新订单：`CouponOrder`（当 `refund_status=SUCCESS` 时写 `is_refund=2`、`payment_status=2`、`update_time`）
  - 更新退款记录：`BaseRefunds`（按 `order_no + refund_id + model + status=0` 查找，写入 `refund_status/success_time/refund_recv_accout/settlement_refund_fee/status` 等）
- 外部调用：无（微信已回调到本接口）
- 幂等策略（必须具备）：微信可能重复回调；若退款已处理（订单已退款/退款记录已终态），应直接返回 success XML 且不重复写状态。

## 6. 测试用例（契约验收）

- 正常用例：合法签名 + `return_code=SUCCESS` 且 `refund_status=SUCCESS`，订单与退款记录更新，返回 success XML
- 异常用例：
  - 签名/解析失败：仍返回 success XML（与现网一致），但必须记录错误日志
  - `refund_status!=SUCCESS`：记录失败状态（或仅记录），返回 success XML
- 幂等/重放：同一退款单重复回调多次，不应重复更新或触发重复业务动作
