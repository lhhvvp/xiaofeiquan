# [meituan] POST /meituan/upload/index

## 1. 基本信息

- 路径（兼容要求）：`/meituan/upload/index`（建议同时兼容：`/meituan/upload/index.html`）
- 源码定位：`app/meituan/controller/Upload.php:45`
- 控制器/方法：`meituan/Upload.index()`
- 描述：文件/图片上传（支持 ckeditor、ueditor、分片上传；实现与 `api/upload/index` 同构）
- 鉴权（基线）：`meituan(BA-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Authorization`
- `Date`
- `PartnerId`
- `Content-Type: application/json`
- 协议（BA 签名）：见 `docs/rewrite/ota-meituan-protocol.md`

## 3. 请求

- HTTP Method：通常为 `POST`（富文本与分片上传场景）；该端点有 `OPTIONS` 预检处理（直接 `exit`）
- Content-Type：
  - 普通上传：`multipart/form-data`
  - 分片上传：`multipart/form-data`（字段名 `file`）
- Query 参数（按现有代码分支）：
  - `from=ckeditor`：走 CKEditor 兼容返回格式（支持多文件字段）
  - `from=ueditor`：走 UEditor 分发（`action=config|upload_image|upload_video|upload_file|list_image|list_file|...`）
  - `upload_type=file`：按“文件”扩展名/大小限制；否则按“图片”限制
- 分片字段（按 `$_REQUEST`）：`chunk/chunks`
- 文件字段：`file`

## 4. 响应

- HTTP 状态码：`200`（实现中存在 `die(...)` 输出 JSON）
- 返回结构：该端点**不使用** `{code,msg,time,data}` envelope，而是多种兼容格式（必须保持）
  1) CKEditor：
     - 成功：`{ "uploaded": true, "url": "<string>" | ["<string>", ...] }`
     - 失败：`{ "uploaded": false, "url": "", "message": "<error>" }`
  2) UEditor：
     - `action=config`：返回配置 JSON
     - 上传成功：`{ code:1, msg:"上传完毕", url:"<url>", title:"<name>", original:"<name>", state:"SUCCESS" }`
  3) 默认/分片上传：
     - 分片未完成：`{"jsonrpc":"2.0","result":null,"id":"id"}`
     - 完成：`{ code:1, msg:"上传完毕", url:"<url>", title:"<name>", original:"<name>", state:"SUCCESS" }`
     - 失败：`{ code:0, msg:"ERROR:<reason>", url:"" }`

> 注意：若 BA 验签失败，中间件会直接返回 `{"code":300,"describe":"BA验证错误","partnerId":...}`（JSON），不会进入上传逻辑。

## 5. 副作用与幂等

- 写库：无（落盘/对象存储）
- 外部调用：可能调用 OSS/七牛/私有 OSS（由系统配置的上传驱动决定）
- 幂等策略：分片上传依赖临时 `.part` 文件合并；重写需兼容分片重试与合并逻辑

## 6. 测试用例（契约验收）

- 正常用例：
  - 合法 BA Header + multipart 上传，返回 `code=1` 或富文本约定结构（按 `from` 分支）
  - 分片上传：中间分片返回 JSON-RPC，最后分片返回 `state=SUCCESS`
- 异常用例：
  - BA Header 缺失/签名不匹配：返回 `code=300,describe=BA验证错误`
  - 后缀不允许/超出大小/缺 file 字段：返回 `code=0` 或 CKEditor 的 `uploaded=false`
- 幂等/重放：重复上传同一分片不应导致合并文件损坏
