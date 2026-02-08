# [meituan] POST /meituan/index/winlogin

## 1. 基本信息

- 路径（兼容要求）：`/meituan/index/winlogin`（建议同时兼容：`/meituan/index/winlogin.html`）
- 源码定位：`app/meituan/controller/Index.php:497`
- 控制器/方法：`meituan/Index.winlogin()`
- 描述：系统登录接口，返回 token 用于操作需验证身份的接口
- 鉴权（基线）：`meituan(BA-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /index/winlogin`

## 2. 鉴权与 Header

- `Authorization`
- `Date`
- `PartnerId`
- `Content-Type: application/json`
- 协议（BA 签名）：见 `docs/rewrite/ota-meituan-protocol.md`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常 `application/x-www-form-urlencoded`（表单提交）；实现通过 `Request::param()` 取参，等价支持 query/form
- 参数（按现网逻辑）：
  - `username`：账号（必填）
  - `password`：密码（必填；现网会与用户表 salt 拼接后 md5 校验）
  - `pubkey`：sm2 加密串（必填；现网注释掉了解密流程）
  - `code`：验证码（必填；`Captcha::check`）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0,msg=登录成功`，`data`：
    - `token`：用于后续接口鉴权的 Token
    - `id/uuid/username/nickname/loginnum/login_time/login_ip`
    - `m_id/m_nickname/businesstr`：关联商户信息（加密串）
  - 失败：`code!=0`，`msg` 为错误原因
- 失败原因（示例，按现网逻辑）：
  - `<param>不能为空`
  - `验证码错误`
  - `帐号或密码错误`
  - `该账号已被锁定、请10分钟后重试`
  - `账号密码错误次数超过3次、请10分钟后重试`
  - `用户已被禁用,请于平台联系`

> 注意：若 BA 验签失败，中间件会直接返回 `{"code":300,"describe":"BA验证错误","partnerId":...}`（非 envelope）。

## 5. 副作用与幂等

- 写库（概念级）：
  - 登录统计：更新 `ticket_user.loginnum/last_login_time/last_login_ip/login_time/login_ip/err_num/lock_time`
  - 生成并入库 token：写 `ticket_user.signpass/expiry_time`
- 外部调用：无
- 幂等策略：无（多次登录会更新登录时间/次数并刷新 token）

## 6. 测试用例（契约验收）

- 正常用例：合法 BA Header + 参数齐全且验证码正确，返回 `code=0,msg=登录成功` 且含 `token`
- 异常用例：
  - BA Header 缺失/签名不匹配：返回 `code=300,describe=BA验证错误`
  - 缺参/验证码错误/密码错误/账号锁定：返回 `code!=0` 且 `msg` 对应
- 幂等/重放：重复登录会刷新 token 与登录时间（现状）
