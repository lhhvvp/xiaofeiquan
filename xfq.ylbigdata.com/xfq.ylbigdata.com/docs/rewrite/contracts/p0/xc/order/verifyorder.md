# [xc] POST /xc/order/VerifyOrder

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/VerifyOrder`（建议同时兼容：`/xc/order/VerifyOrder.html`）
- 源码定位：`app/xc/controller/Order.php:719`
- 控制器/方法：`xc/Order.VerifyOrder()`
- 描述：描述：下单验证接口在客人下单时提前将下单信息提交给供应商系统进行校验，及时告知客人是否可以预定成功，有助于提高订单预定成功率和提升客人预定体验。
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Content-Type: application/json; charset=utf-8`
- 协议（签名 + AES）：见 `docs/rewrite/ota-xc-protocol.md`
- `header.serviceName`：必须为 `VerifyOrder`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/json`
- Body：`{ header: {...}, body: "<aes-string>" }`
  - 明文 body（JSON，概念级；字段以携程协议为准）：
    - `contacts[]`：至少包含 `name/mobile`
    - `items[]`：每项包含：
      - `PLU`：产品编码（现网以 `Ticket.tkno` 匹配）
      - `useStartDate`：使用日期（`YYYY-MM-DD`）
      - `passengers[]`：出行人，至少包含 `name/mobile/cardType/cardNo`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{ header: { resultCode, resultMessage }, body }`
  - 成功：`resultCode=0000`，`resultMessage=验证成功`
  - `body`：AES 加密后的 JSON 字符串，明文为数组：
    - `[{ "PLU": "<PLU>", "inventorys": [ { "useDate": "YYYY-MM-DD", "quantity": <int> } ] }]`
- 业务错误码（来自现网实现 `app/xc/controller/Order.php:719`，需保持一致）：
  - `1005`：出行人信息缺失
  - `1006`：出行人信息校验失败（姓名/手机号/证件号）
  - `1001`：产品 PLU 不存在/错误
  - `1002`：产品已下架
  - `1007`：产品价格不存在/未设置
  - `1004`：限购（每单限购/每天限购）
  - `1003`：库存不足

## 5. 副作用与幂等

- 写库：无（只读校验；会读取产品/价格/限购/库存）
- 外部调用：无
- 幂等策略：天然幂等（同样输入应返回同样结果；库存变化除外）

## 6. 测试用例（契约验收）

- 正常用例：合法签名 + 合法 AES body，库存充足，返回 `resultCode=0000` 且 `body` 可解密为 inventory 列表
- 异常用例：
  - passengers 缺字段：`resultCode=1005/1006`
  - PLU 不存在/下架/未配置价格：`resultCode=1001/1002/1007`
  - 库存不足：`resultCode=1003`，且 `body`（成功时才有）
- 幂等/重放：同一请求重复提交，返回应稳定（不应写库/不应扣库存）
