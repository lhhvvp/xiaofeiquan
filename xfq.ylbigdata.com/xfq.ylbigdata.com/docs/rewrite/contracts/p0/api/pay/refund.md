# [api] POST /api/pay/refund

## 1. 基本信息

- 路径（兼容要求）：`/api/pay/refund`（建议同时兼容：`/api/pay/refund.html`）
- 源码定位：`app/api/controller/Pay.php:199`
- 控制器/方法：`api/Pay.refund()`
- 描述：提交退款
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /pay/refund`

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`（小程序默认）；后端通过 `Request::param()` 取参，等价支持 query/form（以线上行为为准）
- 参数（最小集，按现有代码）：
  - `uid`：`int`，必填；必须与 Header `Userid` 一致（否则报 `用户信息异常 -H`）
  - `openid`：`string`，必填；付款人 OpenID（需与 uid 绑定的用户一致）
  - `order_no`：`string`，必填；内部订单号（不含 `XFQ` 前缀）
  - `order_remark`：`string`，必填；退款原因
  - `coupon_issue_user_id`：`int|string`，必填；领券记录 ID（用于校验该券是否已使用）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0`，`msg=申请成功`，`data=true`（布尔值；以线上为准）
  - 失败：`code!=0`，`msg` 为具体原因
- 错误码（已知片段，完整以契约测试补齐）：
  - `110/111/112/113/115`：鉴权失败（见 `app/api/middleware/Auth.php`）
  - `1`：常规业务失败（示例：`领取记录不存在` / `退款信息错误` / `支付订单不存在!` / `订单正在退款中或者已经退款` / `该消费券已使用,无法退款` 等）
  - 特殊分支：当订单 `payment_trade` 为空时，历史代码走 `jsonReturn('该订单未支付成功无法退款')`，该返回结构需以线上基线回放确认

## 5. 副作用与幂等

- 写库（概念级）：
  - 回退库存：`CouponIssue.remain_count += number_count`
  - 发起退款：创建 `BaseRefunds` 记录；并把 `CouponOrder.is_refund` 置为 `1(退款中)`，写 `order_remark`
  - 将领券记录标记为无效/不可用（`CouponIssueUser.is_fail` 字段，具体语义以线上为准）
- 外部调用：微信退款接口（由 `yansongda/pay` 发起）；最终结果以退款回调 `POST /api/notify/refund/model/{model}.html` 为准
- 幂等策略（部分具备）：
  - 若订单 `is_refund > 0`，接口直接拒绝（避免重复退款）
  - 若需增强幂等（例如重复提交同一 `order_no`），必须确保库存回退与退款请求不被重复执行

## 6. 测试用例（契约验收）

- 正常用例：合法 Token + 订单已支付 + 券未使用，返回 `code=0,msg=申请成功,data=true`
- 异常用例：
  - `coupon_issue_user_id` 缺失：`code=1,msg=领取记录不存在`
  - 订单不存在：`code=1,msg=支付订单不存在!`
  - 订单未支付：需回放确认 `jsonReturn` 的实际返回结构与 code
  - 订单退款中/已退款：`code=1,msg=订单正在退款中或者已经退款`
  - 券已使用：`code=1,msg=该消费券已使用,无法退款`
- 幂等/重放：同一 `order_no` 重复提交，应稳定返回“已退款/退款中”且不重复回退库存/重复发起退款
