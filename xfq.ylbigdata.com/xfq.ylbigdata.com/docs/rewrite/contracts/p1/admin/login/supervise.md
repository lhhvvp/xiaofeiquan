# [admin] GET /admin/login/supervise

## 1. 基本信息

- 路径（兼容要求）：`/admin/login/supervise`（建议同时兼容：`/admin/login/supervise.html`）
- 源码定位：`app/admin/route/app.php:8`
- 控制器/方法：`admin/login.supervise()`
- 描述：route => login/supervise
- 鉴权（基线）：`session(PHPSESSID)`；是否要求鉴权：`unknown`
- 文档注释（如有）：`GET /admin/supervise`

## 2. 鉴权与 Header

- `Cookie: PHPSESSID=...`

## 3. 请求

- Content-Type：
- Query 参数：
- Body：
- 文件上传（如有）：

## 4. 响应

- HTTP 状态码：
- 返回结构：
- 错误码：

## 5. 副作用与幂等

- 写库：
- 外部调用：
- 幂等策略：

## 6. 测试用例（契约验收）

- 正常用例：
- 异常用例：
- 幂等/重放：
