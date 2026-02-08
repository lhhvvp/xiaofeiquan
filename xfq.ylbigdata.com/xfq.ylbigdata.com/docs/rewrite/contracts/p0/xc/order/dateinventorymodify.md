# [xc] POST /xc/order/DateInventoryModify

## 1. 基本信息

- 路径（兼容要求）：`/xc/order/DateInventoryModify`（建议同时兼容：`/xc/order/DateInventoryModify.html`）
- 源码定位：`app/xc/controller/Order.php:675`
- 控制器/方法：`xc/Order.DateInventoryModify()`
- 描述：资源日期库存同步（携程推送库存；现网仅做存在性校验）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Content-Type: application/json; charset=utf-8`
- 协议（签名 + AES）：见 `docs/rewrite/ota-xc-protocol.md`
- `header.serviceName`：必须为 `DateInventoryModify`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/json`
- Body：`{ header: {...}, body: "<aes-string>" }`
  - 明文 body（JSON，概念级；字段以携程协议为准）：
    - `otaOptionId`
    - `supplierOptionId`：供应商资源编码（现网以 `Ticket.code` 匹配）
    - `inventorys[]`：`[{ date:"YYYY-MM-DD", quantity:"<int>" }]`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{ header: { resultCode, resultMessage }, body: "" }`
  - 本接口现网不返回加密 body（`body` 为空字符串）
- 业务错误码（现网实现 `app/xc/controller/Order.php:675`）：
  - `0000`：操作成功
  - `2103`：资源不存在（现网 message 为“订单不存在！”；重写建议保持 message 以兼容）
  - `2111`：系统异常（捕获异常后兜底）

## 5. 副作用与幂等

- 写库：现网未落库存（仅校验资源存在）；重写如需实现库存同步，必须确保对外返回与错误码兼容
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：资源存在，返回 `resultCode=0000`
- 异常用例：资源不存在，返回 `resultCode=2103`
- 幂等/重放：重复推送同一库存，返回稳定
