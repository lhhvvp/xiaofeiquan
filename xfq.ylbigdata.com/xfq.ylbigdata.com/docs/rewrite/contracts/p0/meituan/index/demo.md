# [meituan] ANY /meituan/index/demo

## 1. 基本信息

- 路径（兼容要求）：`/meituan/index/demo`（建议同时兼容：`/meituan/index/demo.html`）
- 源码定位：`app/meituan/controller/Index.php:60`
- 控制器/方法：`meituan/Index.demo()`
- 描述：测试接口（固定返回带 `body` 的 JSON 字符串）
- 鉴权（基线）：`meituan(BA-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Authorization`
- `Date`
- `PartnerId`
- `Content-Type: application/json`
- 协议（BA 签名）：见 `docs/rewrite/ota-meituan-protocol.md`

## 3. 请求

- HTTP Method：任意（现网未限制 method；但 BA 签名会把实际 method 参与计算）
- Query/Body：无

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：字符串（不是 `{code,msg,time,data}` envelope）
  - 固定返回：`{"code":200,"describe":"success","partnerId":703,"body":[...]}`

> 注意：若 BA 验签失败，中间件会直接返回 `{"code":300,"describe":"BA验证错误","partnerId":...}`。

## 5. 副作用与幂等

- 写库：无
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：携带合法 BA Header，请求返回固定 JSON 字符串且含 `body` 数组
- 异常用例：BA Header 缺失/签名不匹配，返回 `code=300,describe=BA验证错误`
- 幂等/重放：重复请求返回应稳定
