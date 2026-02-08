# [xc] INTERNAL /xc/ticket/OrderRefundDetail

## 1. 基本信息

- 路径（兼容要求）：`/xc/ticket/OrderRefundDetail`（建议同时兼容：`/xc/ticket/OrderRefundDetail.html`）
- 源码定位：`app/xc/controller/Ticket.php:336`
- 控制器/方法：`xc/Ticket.OrderRefundDetail()`
- 描述：内部方法：单条退款落库逻辑（被 `POST /xc/ticket/single_refund` 调用）
- 鉴权（基线）：不作为对外 HTTP 接口；是否要求鉴权：`n/a`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- 不建议暴露为 HTTP 接口（现网为 `public` 但属于内部调用）

## 3. 请求

- 现网函数签名：`OrderRefundDetail($order, $order_detail, $refund_desc, $ticket_number, $uuid)`
- 若被误当作 HTTP 调用：入参绑定与返回不稳定（可能直接 500），重写建议直接不暴露或返回 404

## 4. 响应

- 现网返回：`true`（成功）或 `string`（失败原因）

## 5. 副作用与幂等

- 写库：创建 `ticket_refunds`、更新 `ticket_order_detail`、更新 `ticket_order`、累加 `refund_fee`
- 外部调用：无
- 幂等策略：由上层接口通过 `refund_status` 等状态控制

## 6. 测试用例（契约验收）

- 不作为对外 HTTP 接口：不提供契约测试用例（由上层 `/xc/ticket/single_refund` 覆盖）
