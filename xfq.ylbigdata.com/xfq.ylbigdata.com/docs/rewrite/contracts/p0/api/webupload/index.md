# [api] POST /api/webupload/index

## 1. 基本信息

- 路径（兼容要求）：`/api/webupload/index`（建议同时兼容：`/api/webupload/index.html`）
- 源码定位：`app/api/controller/Webupload.php:45`
- 控制器/方法：`api/Webupload.index()`
- 描述：文件/图片上传（不需要鉴权，主要供 Web 侧/富文本使用）
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：通常为 `POST`（富文本与分片上传场景）；该端点有 `OPTIONS` 预检处理（直接 `exit`）
- 请求/参数/分支与返回格式与 `/api/upload/index` 基本一致（同一套实现复制），但 **不需要 `Token/Userid`**
- 关键差异：该端点在 `middleware` 中对 `index` 做了 `except`，因此对外应允许匿名上传（保持现网行为）

## 4. 响应

- 返回结构：与 `/api/upload/index` 相同（ckeditor/ueditor/分片上传多形态）
- 当前重写阶段（离线 mock）：
  - 无文件：`{code:0,msg:"ERROR:没有选择上传文件",url:""}`
  - 有文件：`{code:0,msg:"Failed to open temp directory.",url:""}`

## 5. 副作用与幂等

- 同 `/api/upload/index`（落盘/对象存储 + 分片合并）

## 6. 测试用例（契约验收）

- 正常用例：不带任何鉴权 Header 也能上传成功
- 异常用例/幂等：同 `/api/upload/index`
