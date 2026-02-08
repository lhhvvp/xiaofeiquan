# [api] POST /api/index/getuserphonenumber

## 1. 基本信息

- 路径（兼容要求）：`/api/index/getuserphonenumber`（建议同时兼容：`/api/index/getuserphonenumber.html`）
- 源码定位：`app/api/controller/Index.php:133`
- 控制器/方法：`api/Index.getuserphonenumber()`
- 描述：微信小程序手机号解密入口（重写阶段为离线 mock）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：`POST /index/getuserphonenumber`

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：`application/x-www-form-urlencoded`（兼容 query/form）
- 必填参数：
  - `code`：小程序临时凭证

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：`{code,msg,time,data}`
  - 失败：`code=1,msg=参数错误`（`code` 缺失）
  - 失败：`code=1,msg=获取错误,请重试！`（非 success seed）
  - 成功（success seed）：`code=0,msg=请求成功`，`data` 含 `errcode/errmsg/phone_info`

## 5. 副作用与幂等

- 写库：无。
- 外部调用：当前重写阶段不直接调用微信接口（mock）。
- 幂等策略：无状态写入，天然幂等。

## 6. 测试用例（契约验收）

- 正常用例：`code` 为空时返回 `参数错误`（已覆盖 p0）。
- 异常用例：缺参数返回 `参数错误`。
- 幂等/重放：重复请求返回一致。
