# [xc] GET /xc/index/captcha

## 1. 基本信息

- 路径（兼容要求）：`/xc/index/captcha`（建议同时兼容：`/xc/index/captcha.html`）
- 源码定位：`app/xc/controller/Index.php:92`
- 控制器/方法：`xc/Index.captcha()`
- 描述：验证码图片
- 鉴权（基线）：无；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header）
- 兼容备注：现网 `xc/Index` 源码缺少 Captcha 引用且中间件配置疑似不一致；重写侧建议与 `window/index/captcha` 行为保持一致（以回放为准）

## 3. 请求

- HTTP Method：`GET`（现网未限制 method）
- Query/Body：无

## 4. 响应

- HTTP 状态码：`200`
- 返回 Content-Type：通常为 `image/png`（以验证码组件实现为准）
- 返回体：图片二进制
- 错误码：无（图片响应）

## 5. 副作用与幂等

- 写库：
- 外部调用：
- 幂等策略：

## 6. 测试用例（契约验收）

- 正常用例：请求返回验证码图片（`image/png`）
- 异常用例：验证码组件异常时返回 500/错误响应（需回放补齐）
- 幂等/重放：重复请求图片不同是正常现象（验证码本身非幂等），但返回类型需稳定
