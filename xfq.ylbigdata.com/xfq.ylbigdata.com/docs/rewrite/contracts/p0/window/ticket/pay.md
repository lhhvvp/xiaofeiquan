# [window] POST /window/ticket/pay

## 1. 基本信息

- 路径（兼容要求）：`/window/ticket/pay`（建议同时兼容：`/window/ticket/pay.html`）
- 源码定位：`app/window/controller/Ticket.php:55`
- 控制器/方法：`window/Ticket.pay()`
- 描述：提交订单
- 鉴权（基线）：`window-token(Uuid)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /ticket/pay`

## 2. 鉴权与 Header

- `Token`
- `Uuid`

## 3. 请求

- Content-Type：
- Query 参数：
- Body：
- 文件上传（如有）：

## 4. 响应

- HTTP 状态码：
- 返回结构：
- 错误码：

## 5. 副作用与幂等

- 写库：
- 外部调用：
- 幂等策略：

## 6. 测试用例（契约验收）

- 正常用例：
- 异常用例：
- 幂等/重放：
