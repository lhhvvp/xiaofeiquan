# [api] POST /api/user/smsVerification

## 1. 基本信息

- 路径（兼容要求）：`/api/user/smsVerification`（建议同时兼容：`/api/user/smsVerification.html`）
- 源码定位：`app/api/controller/User.php:1644`
- 控制器/方法：`api/User.smsVerification()`
- 描述：实名认证/换绑手机号时发送短信验证码（写入短信日志）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`（小程序默认）；后端通过 `get_params()` 取参，等价支持 query/form（以线上为准）
- 参数（按现有代码）：
  - `mobile`：`string`，必填；手机号（会做手机号格式校验）
  - `uid`：`int`，必填；用户 ID（同时要求与 Header `Userid` 一致，否则会被 `BaseController` 拦截为 `用户信息异常 -H`）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0`，`msg=发送成功`，`data=[]`
  - 失败：`code!=0`，`msg` 为失败原因
- 错误码/错误消息（已知片段，完整以契约测试补齐）：
  - `code=1,msg=参数错误`：缺少必填参数
  - `code=1,msg=手机号错误`：手机号格式不合法
  - `code=1,msg=对不起, 超出发送频率,过一会儿再试！`：同一 `uid` 1 小时内发送次数 ≥ 3
  - `code=1,msg=发送失败:<供应商msg>`：短信供应商返回失败
  - `110/111/112/113/115`：鉴权失败（见 `app/api/middleware/Auth.php`）

## 5. 副作用与幂等

- 写库（概念级）：新增 `users_sms_log`（记录 `uid/mobile/sms_code/expire_time/smsid/...`）
- 外部调用：短信供应商 API（敏感配置/密钥不得写死，需迁移到环境变量/配置中心）
- 幂等策略：无（每次调用都会生成新验证码并写日志）；频控规则（≥3/小时）属于对外可见行为，需保持一致

## 6. 测试用例（契约验收）

- 正常用例：合法 Token + 合法手机号，返回 `code=0,msg=发送成功`
- 异常用例：缺参/手机号非法/超频，返回对应 `msg`
- 幂等/重放：连续请求 3 次后第 4 次在 1 小时内应被拒绝（保持现网频控）
