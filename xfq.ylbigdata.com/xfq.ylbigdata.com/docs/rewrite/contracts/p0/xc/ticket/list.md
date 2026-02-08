# [xc] POST /xc/ticket/list

## 1. 基本信息

- 路径（兼容要求）：`/xc/ticket/list`（建议同时兼容：`/xc/ticket/list.html`）
- 源码定位：`app/xc/controller/Ticket.php:604`
- 控制器/方法：`xc/Ticket.list()`
- 描述：获取订单列表
- 鉴权（基线）：无（业务侧以 `bstr` 解密得到商户）；是否要求鉴权：`no`
- 文档注释（如有）：`POST /ticket/list`

## 2. 鉴权与 Header

- （无需鉴权 Header）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`
- 参数（按现网逻辑）：
  - `bstr`：商户加密串（必填；解密得到 `mid`）
  - `limit`：每页条数，默认 `10`
  - `page`：页码，默认 `1`
  - `keyword`：`string(JSON)`，必填；搜索条件对象（现网要求必须是 JSON 字符串）
    - 支持字段：`contact_man/contact_phone/order_status/create_time([start,end])`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0,msg=查询成功`，`data`：
    - `list`：订单列表（附加 `order_status_text/refund_status_text`）
    - `cnt`：总条数
  - 失败：`code!=0,msg` 为错误原因（示例：`参数错误！` / `请输入有效的搜索条件` / `商户信息错误`）

## 5. 副作用与幂等

- 写库：无（只读）
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：合法 `bstr` + 合法 `keyword` JSON，返回 `code=0` 且含 `list/cnt`
- 异常用例：`keyword` 非 JSON / `bstr` 无效，返回 `code!=0` 且 msg 对应
- 幂等/重放：重复查询返回应稳定（随数据变化而变化）
