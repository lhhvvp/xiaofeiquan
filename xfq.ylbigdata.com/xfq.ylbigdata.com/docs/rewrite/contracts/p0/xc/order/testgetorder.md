# [xc] GET /xc/order/testGetOrder

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/testGetOrder`（建议同时兼容：`/xc/order/testGetOrder.html`）
- 源码定位：`app/xc/controller/Order.php:860`
- 控制器/方法：`xc/Order.testGetOrder()`
- 描述：调试接口：查询 OTA 订单与条目（直接 `var_dump` 输出）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`GET`
- Query 参数：
  - `out_trade_no`：供应商订单号（`ticket_order_ota.out_trade_no`）
- Body：无

## 4. 响应

- HTTP 状态码：`200`（通常）
- 返回结构：纯文本（PHP `var_dump($order_info)` 的输出），随后 `die`
- 错误码：无固定结构（调试接口）

## 5. 副作用与幂等

- 写库：
- 外部调用：
- 幂等策略：

## 6. 测试用例（契约验收）

- 正常用例：传入存在的 `out_trade_no`，返回 OTA 订单信息的 `var_dump` 文本并 `die`
- 异常用例：`out_trade_no` 不存在时输出为空数组/报错信息（以现网为准）
- 幂等/重放：调试接口不建议做幂等保证
