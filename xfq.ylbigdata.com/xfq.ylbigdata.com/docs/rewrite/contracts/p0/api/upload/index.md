# [api] POST /api/upload/index

## 1. 基本信息

- 路径（兼容要求）：`/api/upload/index`（建议同时兼容：`/api/upload/index.html`）
- 源码定位：`app/api/controller/Upload.php:45`
- 控制器/方法：`api/Upload.index()`
- 描述：文件/图片上传（支持 ckeditor、ueditor、分片上传）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：通常为 `POST`（富文本与分片上传场景）；该端点有 `OPTIONS` 预检处理（直接 `exit`）
- Content-Type：
  - 普通上传：`multipart/form-data`
  - 分片上传（plupload 风格）：`multipart/form-data`（字段名 `file`）
- Query 参数（按现有代码分支）：
  - `from=ckeditor`：走 CKEditor 兼容返回格式
    - `responseType=json`：只返回 1 个 `url`（字符串）；否则 `url` 为数组
  - `from=ueditor`：走 UEditor 分发
    - `action=config|upload_image|upload_video|upload_file|list_image|list_file|...`
  - `upload_type=file`：按“文件”扩展名/大小限制；否则按“图片”限制
- Body（分片上传关键字段，按 `$_REQUEST`）：
  - `chunk`：当前分片序号（从 0 开始）
  - `chunks`：总分片数
- 文件字段：
  - `file`：上传文件（固定字段名；UEditor 也使用 `file`）
  - CKEditor：字段名可能不止一个（代码会遍历 `request()->file()` 的 key 列表）
- 文件限制：
  - 扩展名/大小来自系统配置（`System.upload_*`），并会移除危险后缀（如 `php/asp`）

## 4. 响应

- HTTP 状态码：`200`（但实现中存在 `die(...)` 输出 JSON；需保持内容一致）
- 返回结构：该端点**不使用** `{code,msg,time,data}` envelope，而是多种兼容格式（必须保持）
  1) CKEditor：
     - 成功：`{ "uploaded": true, "url": "<string>" | ["<string>", ...] }`
     - 失败：`{ "uploaded": false, "url": "", "message": "<error>" }`
  2) UEditor：
     - `action=config`：返回配置 JSON（字段较多）
     - 上传成功（复用分片上传完成返回）：`{ code:1, msg:"上传完毕", url:"<url>", title:"<name>", original:"<name>", state:"SUCCESS" }`
  3) 默认/分片上传：
     - 完成：`{ code:1, msg:"上传完毕", url:"<url>", title:"<name>", original:"<name>", state:"SUCCESS" }`
     - 校验/上传失败：`{ code:0, msg:"ERROR:<reason>", url:"" }`
     - 分片未完成：直接输出 JSON-RPC：`{"jsonrpc":"2.0","result":null,"id":"id"}`
- `url` 取值：
  - 本地存储：通常以 `/uploads/...` 开头的路径
  - OSS/七牛/私有 OSS：通常为完整 URL（由 `System.upload_driver` 决定）
- 当前重写阶段（离线 mock）：
  - 无文件：`{code:0,msg:"ERROR:没有选择上传文件",url:""}`
  - 有文件：`{code:0,msg:"Failed to open temp directory.",url:""}`

## 5. 副作用与幂等

- 写库：无（主要落盘/落对象存储）
- 外部调用：可能调用 OSS/七牛/私有 OSS（由 `System.upload_driver` 决定）
- 幂等策略：分片上传依赖临时目录 `.part` 文件合并；重写时要兼容同名分片/重试分片的行为

## 6. 测试用例（契约验收）

- 正常用例：
  - 普通 multipart 上传：返回 `code=1` 且 `url` 非空
  - CKEditor 上传：返回 `uploaded=true` 且 `url` 字段形态符合 `responseType`
  - 分片上传：中间分片返回 JSON-RPC；最后分片返回 `code=1,state=SUCCESS`
- 异常用例：后缀不允许/超出大小/缺 file 字段，返回 `code=0` 或 CKEditor 的 `uploaded=false`
- 幂等/重放：重复上传同一分片不应导致合并文件损坏；最后合并结果应稳定
