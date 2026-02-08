# [selfservice] POST /selfservice/index/selflogin

## 1. 基本信息

- 路径（兼容要求）：`/selfservice/index/selflogin`（建议同时兼容：`/selfservice/index/selflogin.html`）
- 源码定位：`app/selfservice/controller/Index.php:64`
- 控制器/方法：`selfservice/Index.selflogin()`
- 描述：系统登录接口，返回 token 用于操作需验证身份的接口
- 鉴权（基线）：`selfservice-token(No)`；是否要求鉴权：`no`
- 文档注释（如有）：`POST /index/selflogin`

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
