# [xc] POST /xc/order/CancelOrder

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/CancelOrder`（建议同时兼容：`/xc/order/CancelOrder.html`）
- 源码定位：`app/xc/controller/Order.php:481`
- 控制器/方法：`xc/Order.CancelOrder()`
- 描述：订单取消/退单（按条目退款、回退库存、更新订单与明细退款状态）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Content-Type: application/json; charset=utf-8`
- 协议（签名 + AES）：见 `docs/rewrite/ota-xc-protocol.md`
- `header.serviceName`：必须为 `CancelOrder`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/json`
- Body：`{ header: {...}, body: "<aes-string>" }`
  - 明文 body（JSON，概念级）：
    - `otaOrderId`
    - `supplierOrderId`
    - `confirmType`：`1|2`（现网校验必须为 1 或 2）
    - `items[]`：每项至少包含：
      - `itemId`
      - `PLU`
      - `quantity`（`confirmType=2` 时会校验取消数量）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{ header: { resultCode, resultMessage }, body }`
  - `body`：AES 加密后的 JSON 字符串（明文示例结构）：
    - `{"supplierConfirmType":1,"items":[{"itemId":"..."}]}`
- 业务错误码（现网逻辑，需保持一致；见 `app/xc/controller/Order.php:481`）：
  - `0000`：取消/退单成功
  - `2001`：订单不存在
  - `2002`：订单已使用
  - `2004`：取消数量不符（`confirmType=2` 场景）
  - `2111`：系统异常（捕获异常后兜底）

## 5. 副作用与幂等

- 写库（概念级）：
  - 更新明细退款状态：`ticket_order_detail.refund_status=fully_refunded`、`refund_progress=completed`
  - 回退库存：按明细对应 `ticket_id/date` 对 `ticket_price.stock` 自增
  - 更新订单退款/支付状态：
    - 全退：`refund_status=fully_refunded`、`payment_status=2`、`order_status=refunded`
    - 部分退：`refund_status=partially_refunded`
- 外部调用：无
- 幂等策略：重复提交同一取消请求时，已退款的明细不会再次回退库存（现网按 `refund_status` 判断）

## 6. 测试用例（契约验收）

- 正常用例：合法签名 + 合法 AES body，返回 `resultCode=0000` 且 `body` 可解密得到 `supplierConfirmType/items`
- 异常用例：订单不存在/订单已使用/confirmType 非法/取消数量不符，返回对应 `resultCode`
- 幂等/重放：对同一订单/条目重复取消，不应重复回退库存，不应产生二次退款副作用
