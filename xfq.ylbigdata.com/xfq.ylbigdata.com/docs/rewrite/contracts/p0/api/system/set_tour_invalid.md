# [api] POST /api/system/set_tour_invalid

## 1. 基本信息

- 路径（兼容要求）：`/api/system/set_tour_invalid`（建议同时兼容：`/api/system/set_tour_invalid.html`）
- 源码定位：`app/api/controller/System.php:124`
- 控制器/方法：`api/System.set_tour_invalid()`
- 描述：团状态等于确认团且不能是无效团的，团期超过当前时间 没有游客  没有导游 没有领券的 将团设置无效
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`no`
- 文档注释（如有）：`POST /system/set_tour_invalid`

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
