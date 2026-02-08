# [api] UNKNOWN /api/notify/create_file

## 1. 基本信息

- 路径（兼容要求）：`/api/notify/create_file`（建议同时兼容：`/api/notify/create_file.html`）
- 源码定位：`app/api/controller/Notify.php:173`
- 控制器/方法：`api/Notify.create_file()`
- 描述：内部调试写文件接口（仅开发/回放环境保留）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：`GET` / `POST`
- Content-Type：`application/x-www-form-urlencoded`（推荐）
- 参数：
  - `name`：文件名（必填）
  - `path`：相对目录（必填，仅允许相对路径）
  - `content`：写入内容（必填）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：
  - 成功：`{code:0,msg:"写入成功",data:1}`
  - 失败：`{code:1,msg:"参数错误|路径非法",data:[]}`
- 错误码：沿用 `api` 通用错误码（`code=1`）

## 5. 副作用与幂等

- 写库：无
- 外部调用：无
- 文件副作用：在 `runtime/<path>/<name>` 追加一行内容
- 幂等策略：同参数重复调用会重复追加内容（非幂等）

## 6. 测试用例（契约验收）

- 正常用例：提供 `name/path/content`，返回 `code=0`
- 异常用例：缺少任一参数，返回 `code=1,msg=参数错误`
- 异常用例：`path` 非法（绝对路径或含 `..`），返回 `code=1,msg=路径非法`
