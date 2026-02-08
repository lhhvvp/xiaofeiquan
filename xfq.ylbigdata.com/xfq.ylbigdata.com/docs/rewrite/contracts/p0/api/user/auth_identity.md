# [api] POST /api/user/auth_identity

## 1. 基本信息

- 路径（兼容要求）：`/api/user/auth_identity`（建议同时兼容：`/api/user/auth_identity.html`）
- 源码定位：`app/api/controller/User.php:1167`
- 控制器/方法：`api/User.auth_identity()`
- 描述：身份二要素认证（姓名+身份证）+ 绑定/更新手机号
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`（小程序默认）；后端通过 `get_params()` 取参，等价支持 query/form（以线上为准）
- 参数（最小集，按现有代码）：
  - `uid`：`int`，必填；用户 ID（同时要求与 Header `Userid` 一致，否则会被 `BaseController` 拦截为 `用户信息异常 -H`）
  - `mobile`：`string`，必填；手机号（格式校验）
  - `name`：`string`，必填；姓名（会做过滤与校验）
  - `idcard`：`string`，必填；身份证号（会做校验，并派生 `age/birthday/sex/zodiac/starsign/province/city/district` 等字段）
  - `tags`：`int`，可选；当 `tags=1` 表示“变更手机号”，需要额外验证码
  - `smsCode`：`string`，当 `tags=1` 时必填；短信验证码（需在 `users_sms_log` 中存在且未过期）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0`，`msg=认证成功`，`data`：
    - 可能为第三方返回的 `result_message`（字符串）
    - 或 `无需2要素认证`（当命中“已有信用分且信息一致”分支）
  - 失败：`code!=0`，`msg` 为失败原因
- 错误码/错误消息（已知片段，完整以契约测试补齐）：
  - `code=1,msg=参数错误`：缺参
  - `code=1,msg=验证码错误|验证码已过期`：`tags=1` 分支下短信验证码校验失败
  - `code=1,msg=用户不存在|当前用户已经认证`：用户状态不允许
  - `code=1,msg=当前手机号已经绑定其他微信...`：手机号已被其他用户占用
  - `code=1,msg=当前身份证号已经绑定其他手机号:...`：身份证已被其他用户占用
  - `code=1,msg=请配置认证代码`：系统未配置身份认证 AppCode
  - 第三方认证失败映射（示例）：`姓名和身份证号不匹配` / `同一名字30分钟内只能认证10次` / `认证失败` / `身份认证无法通过`
  - `110/111/112/113/115`：鉴权失败（见 `app/api/middleware/Auth.php`）

## 5. 副作用与幂等

- 写库（概念级）：
  - 新增认证日志：`users_auth_log`
  - 更新用户信息：`users`（`auth_status/mobile/name/idcard/...` 等）
- 外部调用：阿里云身份二要素认证 API（敏感配置/密钥不得写死，需迁移到环境变量/配置中心）
- 幂等策略（部分具备）：
  - 若 `users.auth_status==1` 直接拒绝（对外可见行为）
  - 若“已有信用分且姓名/身份证一致”，无需调用外部认证接口，直接更新并返回成功（对外可见行为）

## 6. 测试用例（契约验收）

- 正常用例：合法 Token + 合法参数，返回 `code=0,msg=认证成功`
- 异常用例：缺参/手机号不合法/身份证不合法/已认证/手机号或身份证占用/验证码错误等，返回对应 `msg`
- 幂等/重放：重复提交已认证用户，应稳定返回 `当前用户已经认证`
