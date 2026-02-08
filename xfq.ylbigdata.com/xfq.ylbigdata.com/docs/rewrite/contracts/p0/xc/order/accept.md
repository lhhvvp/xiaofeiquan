# [xc] POST /xc/order/accept

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/accept`（建议同时兼容：`/xc/order/accept.html`）
- 源码定位：`app/xc/controller/Order.php:56`
- 控制器/方法：`xc/Order.accept()`
- 描述：携程 OTA 单入口（按 `header.serviceName` 分发到具体业务处理）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Content-Type: application/json; charset=utf-8`
- 协议（签名 + AES）：见 `docs/rewrite/ota-xc-protocol.md`
- Header 字段在 JSON `header` 中（不是 HTTP Header）：
  - `accountId` / `serviceName` / `requestTime` / `version` / `sign`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/json`
- Body：JSON，必须包含 `header` 与 `body`（字符串，加密后的内容）
  - `header.serviceName` 用于分发，现网支持的值（见 `app/xc/middleware/Auth.php`）：
    - `VerifyOrder`
    - `CreatePreOrder`
    - `PayPreOrder`
    - `CancelPreOrder`
    - `CancelOrder`
    - `QueryOrder`
    - `DateInventoryModify`
  - `body`：AES 加密串（明文为 JSON 字符串；不同 `serviceName` 明文结构不同，见各自契约）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：JSON
  - 成功/业务失败均遵循：`{ "header": { "resultCode": "<code>", "resultMessage": "<msg>" }, "body": "<string>" }`
  - `body`：
    - 多数 `serviceName` 会返回“加密后的 JSON 字符串”
    - 也存在空字符串（例如 `CancelPreOrder/DateInventoryModify`）
- 协议层错误码（中间件直接返回，不进入业务逻辑；见 `app/xc/middleware/Auth.php`）：
  - `0001`：报文解析失败/缺少 header/body/解密失败
  - `0002`：签名错误
  - `0003`：供应商账户信息不正确
- 未识别 `serviceName`：现网无明确错误响应（会进入空的 `accept()`）；重写时建议按线上回放结果锁定行为

## 5. 副作用与幂等

- 写库/外部调用：取决于 `serviceName`（例如下单/支付/取消会写订单与库存表）
- 幂等策略：各 `serviceName` 不同（例如 `CreatePreOrder` 以 `otaOrderId` 作为幂等键）

## 6. 测试用例（契约验收）

- 正常用例：按 `serviceName` 发送合法签名 + 合法 AES body，返回对应业务的 `resultCode/body`
- 异常用例：
  - 报文非 JSON/缺 header/body：`resultCode=0001`
  - `accountId` 不匹配：`resultCode=0003`
  - `sign` 不匹配：`resultCode=0002`
  - `body` 解密失败：`resultCode=0001`
- 幂等/重放：同一请求（同一 `otaOrderId`）重复提交，返回应稳定且不重复扣库存/重复建单（以各接口契约为准）
