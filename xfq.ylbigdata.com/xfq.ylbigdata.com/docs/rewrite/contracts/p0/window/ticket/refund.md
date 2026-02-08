# [window] POST /window/ticket/refund

## 1. 基本信息

- 路径（兼容要求）：`/window/ticket/refund`（建议同时兼容：`/window/ticket/refund.html`）
- 源码定位：`app/window/controller/Ticket.php:280`
- 控制器/方法：`window/Ticket.refund()`
- 描述：窗口端整单退款申请
- 鉴权（基线）：`window-token(Uuid)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /ticket/refund`

## 2. 鉴权与 Header

- `Token`
- `Uuid`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/x-www-form-urlencoded`
- 必填参数：
  - `refund_desc`
  - `out_trade_no`
  - `uuid`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
  - 成功：`code=0,msg=申请成功`
  - 失败：`code=1,msg=<字段>不能为空`

## 5. 副作用与幂等

- 写库：当前重写阶段仅做参数校验，暂不落库。
- 外部调用：无（mock）。
- 幂等策略：无状态变更，天然幂等。

## 6. 测试用例（契约验收）

- 正常用例：完整参数返回 `申请成功`。
- 异常用例：空表单返回 `refund_desc不能为空`。
- 幂等/重放：重复请求结果一致。
