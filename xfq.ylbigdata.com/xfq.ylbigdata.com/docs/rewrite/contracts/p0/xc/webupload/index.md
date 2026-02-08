# [xc] POST /xc/webupload/index

## 1. 基本信息

- 路径（兼容要求）：`/xc/webupload/index`（建议同时兼容：`/xc/webupload/index.html`）
- 源码定位：`app/xc/controller/Webupload.php:45`
- 控制器/方法：`xc/Webupload.index()`
- 描述：文件/图片上传（不需要鉴权；实现与 `xc/upload/index` 同构）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：通常为 `POST`
- 请求/参数/分支与返回格式与 `/xc/upload/index` 基本一致（ckeditor/ueditor/分片上传多形态）

## 4. 响应

- 返回结构：与 `/xc/upload/index` 相同（不使用 `{code,msg,time,data}` envelope）

## 5. 副作用与幂等

- 同 `/xc/upload/index`

## 6. 测试用例（契约验收）

- 正常用例：不带任何鉴权 Header 也能上传成功（返回结构同 `/xc/upload/index`）
- 异常用例：后缀不允许/超出大小/缺 file 字段，返回 `code=0` 或 CKEditor 的 `uploaded=false`
- 幂等/重放：重复上传同一分片不应导致合并文件损坏
