# [api] UNKNOWN /api/user/auth_info

## 1. 基本信息

- 路径（兼容要求）：`/api/user/auth_info`（建议同时兼容：`/api/user/auth_info.html`）
- 源码定位：`app/api/controller/User.php:82`
- 控制器/方法：`api/User.auth_info()`
- 描述：查询用户实名认证信息
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/x-www-form-urlencoded`（兼容 query/form）
- 必填参数：
  - `uid`：用户 ID

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
  - 成功：`code=0,msg=请求成功,data={name,mobile,idcard,auth_status}`
  - 失败：`code=1,msg=用户ID不能为空！` 或 `当前用户不存在`

## 5. 副作用与幂等

- 写库：无。
- 外部调用：无。
- 幂等策略：查询接口，天然幂等。

## 6. 测试用例（契约验收）

- 正常用例：`uid=1` 返回 `name/mobile/idcard/auth_status`（已覆盖 p0）。
- 异常用例：缺 `uid` 返回 `用户ID不能为空！`。
- 幂等/重放：重复请求返回一致。
