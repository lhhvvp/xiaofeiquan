# [xc] GET /xc/index/change

## 1. 基本信息

- 路径（兼容要求）：`/xc/index/change`（建议同时兼容：`/xc/index/change.html`）
- 源码定位：`app/xc/controller/Index.php:51`
- 控制器/方法：`xc/Index.change()`
- 描述：测试接口（固定返回 JSON 字符串）
- 鉴权（基线）：`xc(AES+md5-sign)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`GET`（现网未限制 method）
- Query/Body：无

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：字符串（不是 envelope）
  - 固定返回：`{"code":200,"describe":"成功","partnerId":703}`
- 错误码：无

## 5. 副作用与幂等

- 写库：
- 外部调用：
- 幂等策略：

## 6. 测试用例（契约验收）

- 正常用例：请求返回固定 JSON 字符串（`code=200,describe=成功`）
- 异常用例：无（固定输出）
- 幂等/重放：重复请求返回应稳定
