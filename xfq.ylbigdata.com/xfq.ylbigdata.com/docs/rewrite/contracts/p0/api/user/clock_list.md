# [api] POST /api/user/clock_list

## 1. 基本信息

- 路径（兼容要求）：`/api/user/clock_list`（建议同时兼容：`/api/user/clock_list.html`）
- 源码定位：`app/api/controller/User.php:665`
- 控制器/方法：`api/User.clock_list()`
- 描述：获取用户核销记录 = 打卡记录
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /user/clock_list`

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

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
