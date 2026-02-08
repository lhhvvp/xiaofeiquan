# [xc] POST /xc/ticket/stats

## 1. 基本信息

- 路径（兼容要求）：`/xc/ticket/stats`（建议同时兼容：`/xc/ticket/stats.html`）
- 源码定位：`app/xc/controller/Ticket.php:702`
- 控制器/方法：`xc/Ticket.stats()`
- 描述：报表统计
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`no`
- 文档注释（如有）：`POST /ticket/stats`

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`
- 参数（按现网逻辑）：
  - `bstr`：商户加密串（必填；解密得到 `mid`）
  - `uuid`：售票员账号标识（必填；会校验该用户归属商户）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0,msg=查询成功`，`data`：
    - `today_price`：今日累计收款（`SUM(amount_price - refund_fee)`）
    - `cash_total`：今日现金收款
    - `cash_not_total`：今日非现金收款
    - `data_chart`：最近 7 天每日统计（按日期聚合 `cash_total/cash_not_total`）
  - 失败：`code!=0,msg` 为错误原因（示例：`参数错误！` / `商户信息错误` / `未找到用户` / `当前用户信息异常`）

## 5. 副作用与幂等

- 写库：无（只读统计）
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：合法 `bstr` + `uuid`，返回 `code=0` 且含 `today_price/data_chart`
- 异常用例：商户解密失败/用户不存在/用户不属于商户，返回 `code!=0` 且 msg 对应
- 幂等/重放：重复查询返回应稳定（随订单数据变化而变化）
