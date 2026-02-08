# [meituan] POST /meituan/ticket/pay

## 1. 基本信息

- 路径（兼容要求）：`/meituan/ticket/pay`（建议同时兼容：`/meituan/ticket/pay.html`）
- 源码定位：`app/meituan/controller/Ticket.php:37`
- 控制器/方法：`meituan/Ticket.pay()`
- 描述：提交订单
- 鉴权（基线）：`meituan(BA-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /ticket/pay`

## 2. 鉴权与 Header

- `Authorization`
- `Date`
- `PartnerId`
- `Content-Type: application/json`
- 协议（BA 签名）：见 `docs/rewrite/ota-meituan-protocol.md`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：任意（现网实现未读取入参；仅作为占位接口）
- Query/Body：未使用

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：纯文本
  - 现网固定输出：`222`（`echo 222; die;`）

> 注意：该接口在现网属于未实现/占位状态；重写时若需要真正对接美团下单，必须以线上回放与美团协议为准另补契约。BA 验签失败仍会返回 `{"code":300,"describe":"BA验证错误","partnerId":...}`。

## 5. 副作用与幂等

- 写库：无（现网未实现）
- 外部调用：无（现网未实现）
- 幂等策略：不适用（现网固定输出）

## 6. 测试用例（契约验收）

- 正常用例：合法 BA Header，请求返回纯文本 `222`
- 异常用例：BA Header 缺失/签名不匹配，返回 `code=300,describe=BA验证错误`
- 幂等/重放：重复请求返回应稳定为 `222`
