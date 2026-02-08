# [api] UNKNOWN /api/ticket/cancelRefund

## 1. 基本信息

- 路径（兼容要求）：`/api/ticket/cancelRefund`（建议同时兼容：`/api/ticket/cancelRefund.html`）
- 源码定位：`app/api/controller/Ticket.php:1973`
- 控制器/方法：`api/Ticket.cancelRefund()`
- 描述：取消退款申请（整单/子单）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/x-www-form-urlencoded`
- 必填参数：
  - `type`：`order` 或 `order_detail`
  - `id`：订单 ID 或子单 ID
- 其余信息：通过 Header `Userid` 识别当前用户。

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
  - 成功：`code=0,msg=取消成功！`
  - 失败：`code=1,msg=<错误文案>`
- 关键错误文案：
  - `参数错误！`
  - `用户不存在！`
  - `订单不存在！`
  - `订单状态不符！`
  - `门票不存在！`
  - `该门票状态不符！`

## 5. 副作用与幂等

- 写库：
  - 整单：`tp_ticket_refunds.status=3`，并把相关 `tp_ticket_order_detail.refund_progress` 回置 `init`
  - 子单：对应 `order_detail_no` 的退款记录改 `status=3`，子单回置 `init`
- 外部调用：无。
- 幂等策略：仅对状态匹配记录执行回置；重复调用通常返回状态不符。

## 6. 测试用例（契约验收）

- 正常用例：`type=order|order_detail` 且状态可取消，返回 `取消成功！`。
- 异常用例：`type=login` 或缺参，返回 `参数错误！`。
- 幂等/重放：重复取消同一记录应返回状态类错误。
