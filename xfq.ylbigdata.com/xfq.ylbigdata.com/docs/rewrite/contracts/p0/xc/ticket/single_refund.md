# [xc] POST /xc/ticket/single_refund

## 1. 基本信息

- 路径（兼容要求）：`/xc/ticket/single_refund`（建议同时兼容：`/xc/ticket/single_refund.html`）
- 源码定位：`app/xc/controller/Ticket.php:247`
- 控制器/方法：`xc/Ticket.single_refund()`
- 描述：单条退款（按子单 `out_trade_no` 退指定张数）
- 鉴权（基线）：无（业务侧以 `uuid` 识别售票员/商户）；是否要求鉴权：`no`
- 文档注释（如有）：`POST /ticket/refund`

## 2. 鉴权与 Header

- （无需鉴权 Header）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`
- 参数（按现网逻辑）：
  - `refund_desc`：退款原因（必填）
  - `out_trade_no`：子单号（`ticket_order_detail.out_trade_no`，必填）
  - `ticket_number`：退款张数（必填；不能超过子单 `ticket_number`）
  - `uuid`：售票员账号标识（必填）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0,msg=申请成功,data=true`
  - 失败：`code!=0,msg` 为错误原因
- 失败原因（示例，按现网校验）：
  - `请求方式错误` / `<param>不能为空`
  - `支付订单不存在!` / `订单异常`
  - `最多能退X张`
  - `非窗口订单禁止退款`
  - `未支付订单无法退款!` / `已使用订单无法退款!` / `已取消订单无法退款!` / `该订单已经全额退款!`

## 5. 副作用与幂等

- 写库（概念级）：
  - 新增退款记录：`ticket_refunds`
  - 更新子单：`ticket_order_detail`（`refund_progress/refund_status/refund_id/ticket_number/...`）
  - 更新主单：`ticket_order`（累加 `refund_fee`，并可能更新 `refund_status/order_status/payment_status`）
- 外部调用：无（现网仅做账务/状态更新，不直连支付网关）
- 幂等策略：以 `refund_status` 阻止重复退款；已全退的子单会直接拒绝

## 6. 测试用例（契约验收）

- 正常用例：子单存在且可退，返回 `code=0,msg=申请成功,data=true`
- 异常用例：订单未支付/已使用/已全退/数量超限，返回 `code!=0` 且 msg 对应
- 幂等/重放：重复退款同一子单会被状态拦截（不重复写退款记录）
