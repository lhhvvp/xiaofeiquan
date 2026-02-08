# [xc] POST /xc/order/QueryOrder

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/QueryOrder`（建议同时兼容：`/xc/order/QueryOrder.html`）
- 源码定位：`app/xc/controller/Order.php:589`
- 控制器/方法：`xc/Order.QueryOrder()`
- 描述：查询订单状态（返回订单状态码、使用数量、取消数量）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Content-Type: application/json; charset=utf-8`
- 协议（签名 + AES）：见 `docs/rewrite/ota-xc-protocol.md`
- `header.serviceName`：必须为 `QueryOrder`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/json`
- Body：`{ header: {...}, body: "<aes-string>" }`
  - 明文 body（JSON，概念级）：`{ "otaOrderId": "...", "supplierOrderId": "..." }`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{ header: { resultCode, resultMessage }, body }`
  - 成功：`resultCode=0000`，`resultMessage=获取成功`
  - `body`：AES 加密后的 JSON 字符串（明文示例结构）：
    - `{"otaOrderId":"...","supplierOrderId":"...","items":[{"itemId":0,"useStartDate":"YYYY-MM-DD","useEndDate":"YYYY-MM-DD","orderStatus":13,"quantity":1,"useQuantity":0,"cancelQuantity":0}]}`
  - `orderStatus`：现网把内部状态映射为携程状态码（示例）：
    - `created` → `11`（待支付）
    - `paid` → `13`（支付已确认）
    - `cancelled` → `14`（预下单取消成功）
    - `used` → `8`（全部使用）
    - `refunded` → `5`（全部取消）
- 业务错误码：
  - `4001`：订单不存在
  - `4101`：系统异常（捕获异常后兜底）

## 5. 副作用与幂等

- 写库：无（只读查询）
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：合法签名 + 合法 AES body，返回 `resultCode=0000` 且 `body` 可解密得到 items 状态
- 异常用例：订单不存在返回 `resultCode=4001`
- 幂等/重放：重复查询结果应稳定（随订单状态变化而变化）
