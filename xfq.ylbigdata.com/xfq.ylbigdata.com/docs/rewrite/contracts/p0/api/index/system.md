# [api] POST /api/index/system

## 1. 基本信息

- 路径（兼容要求）：`/api/index/system`（建议同时兼容：`/api/index/system.html`）
- 源码定位：`app/api/controller/Index.php:91`
- 控制器/方法：`api/Index.system()`
- 描述：返回系统信息
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：`POST /index/system`

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

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
