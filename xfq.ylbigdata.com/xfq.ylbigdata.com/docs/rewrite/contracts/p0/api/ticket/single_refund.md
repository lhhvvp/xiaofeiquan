# [api] POST /api/ticket/single_refund

## 1. 基本信息

- 路径（兼容要求）：`/api/ticket/single_refund`（建议同时兼容：`/api/ticket/single_refund.html`）
- 源码定位：`app/api/controller/Ticket.php:660`
- 控制器/方法：`api/Ticket.single_refund()`
- 描述：单游客子单退款申请
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
  - `uuid`
  - `openid`
  - `refund_desc`
  - `out_trade_no`（子单号，`tp_ticket_order_detail.out_trade_no`）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
  - 成功：`code=0,msg=申请成功,data=true`
  - 失败：`code=1,msg=<错误文案>,data=[]`
- 关键错误文案：
  - `<字段>不能为空`
  - `当前用户信息异常，禁止提交`
  - `支付订单不存在!`
  - `该游客已入园，不允许退款!`
  - `该订单已经全额退款!`
  - `该订单已经提交退款`
  - `该订单已经通过退款审核，请稍后查看`
  - `该订单已经完成退款`
  - `未支付订单无法退款!` / `已使用订单无法退款!` / `已取消订单无法退款!`

## 5. 副作用与幂等

- 写库：
  - 新增 `tp_ticket_refunds`（按子单）
  - 更新 `tp_ticket_order_detail.refund_progress=pending_review`
- 外部调用：当前重写阶段为 mock，不直接调用微信退款网关。
- 幂等策略：对已提交/已审核/已完成状态直接拒绝。

## 6. 测试用例（契约验收）

- 正常用例：可退子单返回 `申请成功`。
- 异常用例：空 `refund_desc` 返回 `refund_desc不能为空`。
- 幂等/重放：重复提交同子单时应返回已提交/已完成类错误。
