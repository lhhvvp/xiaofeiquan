# [api] UNKNOWN /api/user/miniwxregister

## 1. 基本信息

- 路径（兼容要求）：`/api/user/miniwxregister`（建议同时兼容：`/api/user/miniwxregister.html`）
- 源码定位：`app/api/controller/User.php:1390`
- 控制器/方法：`api/User.miniwxregister()`
- 描述：微信用户注册/绑定（重写阶段保留关键校验分支）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/x-www-form-urlencoded`
- 必填参数：
  - `openid`
  - `mobile`
  - `name`
  - `idcard`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
  - 缺参：`code=1,msg=参数错误`
  - 成功分支：`code=0,msg=注册成功|登录成功,data={token,userinfo}`
  - 失败分支（示例）：`当前微信已经注册` / `手机号错误` / `请输入正确的身份证号码`

## 5. 副作用与幂等

- 写库：可能新增/更新 `tp_users`，并写入 `signpass/expiry_time`。
- 外部调用：无（mock）。
- 幂等策略：受唯一键（`openid/mobile/idcard`）与冲突分支约束。

## 6. 测试用例（契约验收）

- 正常用例：本轮已覆盖缺参分支（p0）。
- 异常用例：空表单返回 `参数错误`。
- 幂等/重放：重复注册同 `openid` 应返回冲突错误。
