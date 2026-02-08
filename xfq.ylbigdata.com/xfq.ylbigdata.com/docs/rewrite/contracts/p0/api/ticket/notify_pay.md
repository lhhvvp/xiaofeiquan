# [api] UNKNOWN /api/ticket/notify_pay

## 1. 基本信息

- 路径（兼容要求）：`/api/ticket/notify_pay`（建议同时兼容：`/api/ticket/notify_pay.html`）
- 源码定位：`app/api/controller/Ticket.php:944`
- 控制器/方法：`api/Ticket.notify_pay()`
- 描述：微信支付异步回调入口（重写阶段 mock 应答）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：微信回调常见 `text/xml`（当前不强制）
- Body：回调 XML（当前重写阶段不解析，直接应答成功）。

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：XML
  - `<return_code><![CDATA[SUCCESS]]></return_code>`
  - `<return_msg><![CDATA[OK]]></return_msg>`
- 错误码：无（回调入口统一成功应答，防止第三方重试风暴）。

## 5. 副作用与幂等

- 写库：当前重写阶段未落库（mock）。
- 外部调用：无。
- 幂等策略：固定成功应答，天然幂等。

## 6. 测试用例（契约验收）

- 正常用例：空 body 调用返回 XML `SUCCESS/OK`。
- 异常用例：非法 body 仍返回 XML `SUCCESS/OK`。
- 幂等/重放：重复回调返回一致。
