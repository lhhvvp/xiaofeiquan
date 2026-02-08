# [api] UNKNOWN /api/pay/OrderRefund

## 1. 基本信息

- 路径（兼容要求）：`/api/pay/OrderRefund`（建议同时兼容：`/api/pay/OrderRefund.html`）
- 源码定位：`app/api/controller/Pay.php:255`
- 控制器/方法：`api/Pay.OrderRefund()`
- 描述：历史内部方法被框架暴露；重写阶段已标准化为稳定错误返回
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：`GET/POST`（兼容历史访问形态）
- Content-Type：任意（当前不解析）
- 参数：无固定公开参数。

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
- 当前行为：固定返回 `code=1,msg=参数错误`，用于替代历史 `500` 页面。

## 5. 副作用与幂等

- 写库：无。
- 外部调用：无。
- 幂等策略：固定错误响应，天然幂等。

## 6. 测试用例（契约验收）

- 正常用例：任意请求均返回 `参数错误`。
- 异常用例：鉴权缺失时仍按鉴权中间件返回。
- 幂等/重放：重复请求结果一致。
