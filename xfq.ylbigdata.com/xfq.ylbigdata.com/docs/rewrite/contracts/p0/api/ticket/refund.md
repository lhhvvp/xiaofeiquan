# [api] POST /api/ticket/refund

## 1. 基本信息

- 路径（兼容要求）：`/api/ticket/refund`（建议同时兼容：`/api/ticket/refund.html`）
- 源码定位：`app/api/controller/Ticket.php:802`
- 控制器/方法：`api/Ticket.refund()`
- 描述：整单退款申请（小程序）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /ticket/refund`

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/x-www-form-urlencoded`（兼容 query/form）
- 必填参数：
  - `uuid`：用户 UUID
  - `openid`：用户 OpenID
  - `refund_desc`：退款原因
  - `out_trade_no`：订单号（`tp_ticket_order.out_trade_no`）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
  - 成功：`code=0,msg=申请成功,data=true`
  - 失败：`code=1,msg=<错误文案>,data=[]`
- 关键错误文案：
  - `<字段>不能为空`
  - `当前用户信息异常，禁止提交`
  - `支付订单不存在!`
  - `未支付订单无法退款!` / `已使用订单无法退款!` / `已取消订单无法退款!`
  - `该订单已经全额退款!`
  - `该订单中已有游客使用，不允许全退！`
  - `该订单中已有游客退款，不允许全退！`
  - `该订单中已有游客有退款行为，不允许全退！`

## 5. 副作用与幂等

- 写库：
  - 新增 `tp_ticket_refunds`（状态 `PENDING`）
  - 更新 `tp_ticket_order_detail` 为 `refund_progress=pending_review`
- 外部调用：当前重写阶段为 mock，不直接调用微信退款网关。
- 幂等策略：当前按状态校验阻止明显重复提交；同单并发幂等仍需二阶段增强。

## 6. 测试用例（契约验收）

- 正常用例：合法 token + 全字段 + 订单可退，返回 `申请成功`。
- 异常用例：任意必填缺失时返回 `<字段>不能为空`。
- 幂等/重放：同订单重复调用应稳定返回状态类错误，不应重复推进退款状态。
