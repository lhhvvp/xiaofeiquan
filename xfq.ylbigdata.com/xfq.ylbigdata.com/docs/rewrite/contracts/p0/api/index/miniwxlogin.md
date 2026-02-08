# [api] POST /api/index/miniwxlogin

## 1. 基本信息

- 路径（兼容要求）：`/api/index/miniwxlogin`（建议同时兼容：`/api/index/miniwxlogin.html`）
- 源码定位：`app/api/controller/Index.php:172`
- 控制器/方法：`api/Index.miniwxlogin()`
- 描述：微信小程序登录（重写阶段保留参数校验与未注册分支）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：`POST /index/miniwxlogin`

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/x-www-form-urlencoded`（兼容 query/form）
- 必填参数：
  - `code`：小程序登录凭证

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
  - `code` 缺失：`code=1,msg=参数错误`
  - 当前 mock 登录：`code=4444,msg=未注册,data={openid:"mock-openid"}`

## 5. 副作用与幂等

- 写库：无。
- 外部调用：当前重写阶段不访问微信 `jscode2session`。
- 幂等策略：无状态写入，天然幂等。

## 6. 测试用例（契约验收）

- 正常用例：`code` 为空时返回 `参数错误`（已覆盖 p0）。
- 异常用例：缺参数返回 `参数错误`。
- 幂等/重放：重复请求返回一致。
