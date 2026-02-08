# [xc] POST /xc/ticket/getTicketPirce

## 1. 基本信息

- 路径（兼容要求）：`/xc/ticket/getTicketPirce`（建议同时兼容：`/xc/ticket/getTicketPirce.html`）
- 源码定位：`app/xc/controller/Ticket.php:563`
- 控制器/方法：`xc/Ticket.getTicketPirce()`
- 描述：获取门票价格与库存（按商户 + 日期 + 渠道返回不同价格字段）
- 鉴权（基线）：无（业务侧以 `bstr` 解密得到商户）；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`
- 参数（按现网逻辑）：
  - `bstr`：商户加密串（必填；`sys_decryption(bstr,'mid')` 得到 `mid`）
  - `oneday`：`YYYY-MM-DD`，可选；不传默认为当天
  - `channel`：可选；`online|casual|team`，决定返回的价格字段名

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0`，`data` 为价格列表数组（带 `ticket` 关联信息）
  - 失败：`code!=0,msg` 为错误原因（示例：`缺少商户参数` / `商户信息错误`）

## 5. 副作用与幂等

- 写库：无（只读）
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：合法 `bstr` + `oneday/channel`，返回 `code=0` 且 `data` 为价格列表
- 异常用例：`bstr` 缺失/解密失败，返回 `code!=0` 且 msg 对应
- 幂等/重放：重复查询返回应稳定（随库存/价格变动而变化）
