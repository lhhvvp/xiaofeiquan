# [xc] POST /xc/ticket/detail

## 1. 基本信息

- 路径（兼容要求）：`/xc/ticket/detail`（建议同时兼容：`/xc/ticket/detail.html`）
- 源码定位：`app/xc/controller/Ticket.php:672`
- 控制器/方法：`xc/Ticket.detail()`
- 描述：订单详情
- 鉴权（基线）：无（业务侧以 `bstr` 解密得到商户）；是否要求鉴权：`no`
- 文档注释（如有）：`POST /ticket/detail`

## 2. 鉴权与 Header

- （无需鉴权 Header）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`
- 参数（按现网逻辑）：
  - `bstr`：商户加密串（必填；解密得到 `mid`）
  - `trade_no`：主单号（必填）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0,msg=查询成功`，`data`：
    - 主单信息（附加 `order_status_text/refund_status_text`）
    - `detail`：子单数组（附加 `refund_progress_text/refund_status_text`）
  - 失败：`code!=0,msg` 为错误原因（示例：`参数错误` / `商户信息错误` / `订单不存在`）

## 5. 副作用与幂等

- 写库：无（只读）
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：合法 `bstr` + 存在的 `trade_no`，返回 `code=0` 且含 `detail` 子单数组
- 异常用例：订单不存在/参数错误/商户不匹配，返回 `code!=0` 且 msg 对应
- 幂等/重放：重复查询返回应稳定
