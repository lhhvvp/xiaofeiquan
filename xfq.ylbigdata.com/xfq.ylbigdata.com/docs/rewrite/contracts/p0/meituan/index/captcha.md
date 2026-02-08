# [meituan] GET /meituan/index/captcha

## 1. 基本信息

- 路径（兼容要求）：`/meituan/index/captcha`（建议同时兼容：`/meituan/index/captcha.html`）
- 源码定位：`app/meituan/controller/Index.php:487`
- 控制器/方法：`meituan/Index.captcha()`
- 描述：验证码图片
- 鉴权（基线）：`meituan(BA-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Authorization`
- `Date`
- `PartnerId`
- `Content-Type: application/json`
- 协议（BA 签名）：见 `docs/rewrite/ota-meituan-protocol.md`

## 3. 请求

- HTTP Method：`GET`（现网未限制 method）
- Query/Body：无

## 4. 响应

- HTTP 状态码：`200`
- 返回 Content-Type：通常为 `image/png`（以验证码组件实现为准）
- 返回体：图片二进制

> 注意：若 BA 验签失败，中间件会先返回 `{"code":300,"describe":"BA验证错误","partnerId":...}`（JSON），不会返回图片。

## 5. 副作用与幂等

- 写库：无
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：携带合法 BA Header，返回验证码图片（`image/png`）
- 异常用例：BA Header 缺失/签名不匹配，返回 `code=300,describe=BA验证错误`（JSON）
- 幂等/重放：重复请求图片不同是正常现象（验证码本身非幂等），但返回类型需稳定
