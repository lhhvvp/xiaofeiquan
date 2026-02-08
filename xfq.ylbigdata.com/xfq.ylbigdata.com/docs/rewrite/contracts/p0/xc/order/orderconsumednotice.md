# [xc] GET /xc/order/OrderConsumedNotice

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/OrderConsumedNotice`（建议同时兼容：`/xc/order/OrderConsumedNotice.html`）
- 源码定位：`app/xc/controller/Order.php:851`
- 控制器/方法：`xc/Order.OrderConsumedNotice()`
- 描述：核销通知（测试接口，触发向携程发起核销通知；直接 `var_dump` 输出）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`GET`
- Query 参数：
  - `out_trade_no`：供应商订单号（`ticket_order.out_trade_no`）
- Body：无

## 4. 响应

- HTTP 状态码：`200`（通常）
- 返回结构：纯文本（PHP `var_dump($result)` 的输出），随后 `die`
- 错误码：无固定结构（调试接口；建议重写时保留路径但默认关闭或仅内网可达）

## 5. 副作用与幂等

- 写库：无（取决于 `NoticeService` 实现）
- 外部调用：调用携程通知接口（见 `app/xc/service/NoticeService`）
- 幂等策略：未定义（调试接口，不建议对外暴露）

## 6. 测试用例（契约验收）

- 正常用例：传入存在的 `out_trade_no`，返回 `var_dump` 输出（文本）并 `die`
- 异常用例：`out_trade_no` 不存在/NoticeService 异常时返回不稳定（调试接口）
- 幂等/重放：调试接口不建议做幂等保证
