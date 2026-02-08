# [selfservice] UNKNOWN /selfservice/webupload/index

## 1. 基本信息

- 路径（兼容要求）：`/selfservice/webupload/index`（建议同时兼容：`/selfservice/webupload/index.html`）
- 源码定位：`app/selfservice/controller/Webupload.php:45`
- 控制器/方法：`selfservice/Webupload.index()`
- 描述：自助机匿名上传入口（重写阶段统一上传 mock）
- 鉴权（基线）：`selfservice-token(No)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`POST`
- Content-Type：
  - `application/x-www-form-urlencoded`
  - `multipart/form-data`
- 参数：兼容历史上传分支参数（`from/upload_type/action`）。

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：
  - 无文件：`{code:0,msg:"ERROR:没有选择上传文件",url:""}`
  - 有文件：`{code:0,msg:"Failed to open temp directory.",url:""}`

## 5. 副作用与幂等

- 写库：无。
- 外部调用：无（mock）。
- 幂等策略：无状态写入，天然幂等。

## 6. 测试用例（契约验收）

- 正常用例：不带鉴权请求返回错误 JSON（已覆盖 p0）。
- 异常用例：参数异常时返回稳定错误 JSON。
- 幂等/重放：重复请求返回一致。
