# [xc] POST /xc/order/CreatePreOrder

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/CreatePreOrder`（建议同时兼容：`/xc/order/CreatePreOrder.html`）
- 源码定位：`app/xc/controller/Order.php:61`
- 控制器/方法：`xc/Order.CreatePreOrder()`
- 描述：预下单创建（生成 `supplierOrderId`、写入 OTA 订单与明细、扣减库存、返回可用库存）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Content-Type: application/json; charset=utf-8`
- 协议（签名 + AES）：见 `docs/rewrite/ota-xc-protocol.md`
- `header.serviceName`：必须为 `CreatePreOrder`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/json`
- Body：`{ header: {...}, body: "<aes-string>" }`
  - 明文 body（JSON，概念级；字段以携程协议为准）：
    - `otaOrderId`：携程订单号（现网作为幂等键；重复下单会直接返回已存在的订单信息）
    - `contacts[]`：联系人数组（现网会取最后一个联系人写入订单联系人字段）
    - `items[]`：每项包含：
      - `PLU`：产品编码（现网以 `Ticket.code` 匹配）
      - `useStartDate`：使用日期（`YYYY-MM-DD`；若早于当天会报错）
      - `quantity`：数量（现网会校验库存并扣减）
      - `price/cost`：价格字段（用于订单金额计算）
      - `passengers[]`：出行人信息（会写入订单明细）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{ header: { resultCode, resultMessage }, body }`
  - 成功：`resultCode=0000`
  - `body`：AES 加密后的 JSON 字符串（明文示例结构）：
    - `{"otaOrderId":"...","supplierOrderId":"<out_trade_no>","items":[{"PLU":"...","inventorys":{"useDate":"YYYY-MM-DD","quantity":<int>}}]}`
- 业务错误码（现网在不同校验点写入，需保持一致；见 `app/xc/controller/Order.php`）：
  - `1001`：门票不存在
  - `1002`：门票下架/使用日期已过期
  - `1003`：库存不足
  - `1007`：价格不存在
  - `1111`：系统异常（捕获异常后兜底）

## 5. 副作用与幂等

- 写库（概念级）：
  - 创建订单：`ticket_order`（`channel=ota_xc` 等）
  - 创建 OTA 映射：`ticket_order_ota`
  - 创建 OTA 明细：`ticket_order_ota_item`（含核销二维码数据 `voucher_data`）
  - 创建订单明细/权益：`ticket_order_detail`、`ticket_order_detail_rights`
  - 扣减库存：`ticket_price.stock -= quantity`
- 外部调用：无（本接口为携程入站）
- 幂等策略（必须具备）：以 `otaOrderId` 作为幂等键；重复调用不应重复建单/重复扣库存（现网已做“重复下单直接返回”分支）

## 6. 测试用例（契约验收）

- 正常用例：合法签名 + 合法 AES body，库存充足，返回 `resultCode=0000` 且 `body` 可解密得到 `supplierOrderId/items`
- 异常用例：
  - 门票不存在/下架/未配置价格/库存不足：返回对应 `resultCode` 与 `resultMessage`
  - 使用日期早于当天：`resultCode=1002`
- 幂等/重放：同一 `otaOrderId` 重复请求，返回应稳定，且库存/订单不会被重复扣减/重复创建
