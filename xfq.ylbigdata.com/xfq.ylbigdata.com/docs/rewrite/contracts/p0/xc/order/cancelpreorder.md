# [xc] POST /xc/order/CancelPreOrder

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/CancelPreOrder`（建议同时兼容：`/xc/order/CancelPreOrder.html`）
- 源码定位：`app/xc/controller/Order.php:468`
- 控制器/方法：`xc/Order.CancelPreOrder()`
- 描述：预下单未支付取消（取消订单并归还库存）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Content-Type: application/json; charset=utf-8`
- 协议（签名 + AES）：见 `docs/rewrite/ota-xc-protocol.md`
- `header.serviceName`：必须为 `CancelPreOrder`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/json`
- Body：`{ header: {...}, body: "<aes-string>" }`
  - 明文 body（JSON，概念级）：`{ "otaOrderId": "..." }`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{ header: { resultCode, resultMessage }, body: "" }`
  - 本接口现网不返回加密 body（`body` 为空字符串）
- 业务错误码（来自 `app/common/model/ticket/Order.php:148`）：
  - `0000`：取消成功 / 订单已取消
  - `2001`：订单不存在
  - `2101`：订单存在退款
  - `2002`：订单已使用
  - `2103`：订单已退款 / 订单已支付 / 订单存在已使用信息
  - `2111`：系统异常（捕获异常后兜底）

## 5. 副作用与幂等

- 写库（概念级）：
  - 更新订单：`ticket_order.order_status` → `cancelled`
  - 归还库存：`ticket_price.stock += quantity`（按 `ticket_order_ota_item` 逐项回退）
- 外部调用：无
- 幂等策略：同一 `otaOrderId` 重复取消应稳定返回 `0000`（现网对已取消订单返回 `订单已取消`）

## 6. 测试用例（契约验收）

- 正常用例：合法签名 + 合法 AES body，订单处于 `created`，返回 `resultCode=0000`
- 异常用例：订单不存在/已使用/已支付/已退款/存在退款，返回对应 `resultCode`
- 幂等/重放：同一 `otaOrderId` 重复取消，库存不应重复回退、状态不应反复变更
