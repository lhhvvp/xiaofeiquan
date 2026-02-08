# [window] UNKNOWN /window/upload/index

## 1. 基本信息

- 路径（兼容要求）：`/window/upload/index`（建议同时兼容：`/window/upload/index.html`）
- 源码定位：`app/window/controller/Upload.php:45`
- 控制器/方法：`window/Upload.index()`
- 描述：窗口端上传入口（重写阶段统一上传 mock）
- 鉴权（基线）：`window-token(Uuid)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Token`
- `Uuid`

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

- 正常用例：空表单返回 `ERROR:没有选择上传文件`（已覆盖 p0）。
- 异常用例：鉴权缺失由中间件返回。
- 幂等/重放：重复请求返回一致。
