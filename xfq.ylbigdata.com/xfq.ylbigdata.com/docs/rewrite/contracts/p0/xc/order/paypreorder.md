# [xc] POST /xc/order/PayPreOrder

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/PayPreOrder`（建议同时兼容：`/xc/order/PayPreOrder.html`）
- 源码定位：`app/xc/controller/Order.php:361`
- 控制器/方法：`xc/Order.PayPreOrder()`
- 描述：预下单支付确认（更新订单状态并下发核销凭证 `voucherData`）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Content-Type: application/json; charset=utf-8`
- 协议（签名 + AES）：见 `docs/rewrite/ota-xc-protocol.md`
- `header.serviceName`：必须为 `PayPreOrder`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/json`
- Body：`{ header: {...}, body: "<aes-string>" }`
  - 明文 body（JSON，概念级）：
    - `otaOrderId`
    - `supplierOrderId`：供应商订单号（现网 `out_trade_no`）
    - `confirmType`：确认类型（现网在响应里固定返回 `supplierConfirmType=1`）
    - `items[]`：`[{ itemId, PLU }]`（现网会把 `itemId` 写回 `ticket_order_ota_item.item_id`）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{ header: { resultCode, resultMessage }, body }`
  - 成功：`resultCode=0000`
  - `body`：AES 加密后的 JSON 字符串（明文示例结构）：
    - `{"otaOrderId":"...","supplierOrderId":"...","supplierConfirmType":1,"voucherSender":1,"vouchers":[{"itemId":"...","voucherType":3,"voucherCode":"","voucherData":"<urlencode(voucher_data)>","voucherSeatInfo":""}]}`
  - 失败：现网会返回 AES 加密后的空结构（`[]/{}`），并通过 `resultCode/resultMessage` 表达原因
- 业务错误码（现网逻辑，需保持一致；见 `app/xc/controller/Order.php:361`）：
  - `2001`：订单不存在
  - `2101`：订单存在退款
  - `2002`：订单已使用
  - `2102`：订单已取消
  - `2103`：订单已退款
  - `2111`：系统异常（捕获异常后兜底）

## 5. 副作用与幂等

- 写库（概念级）：
  - 更新订单状态：`ticket_order.order_status` 从 `created` → `paid`（并写 `payment_datetime/payment_status/type`）
  - 回写 `itemId`：`ticket_order_ota_item.item_id`
- 外部调用：无（本接口为携程入站；出行通知在现网已注释掉）
- 幂等策略：同一 `supplierOrderId` 重复调用不应产生不同凭证；现网在 `order_status != created` 时不会重复改状态，且按 item 读取固定的 `voucher_data`

## 6. 测试用例（契约验收）

- 正常用例：合法签名 + 合法 AES body，返回 `resultCode=0000` 且 `body` 可解密得到 `vouchers`
- 异常用例：订单不存在/已使用/已取消/已退款/存在退款，返回对应 `resultCode` 与 `resultMessage`
- 幂等/重放：重复提交相同请求，应返回稳定 `vouchers`（不重复写状态、不产生新 voucherData）
