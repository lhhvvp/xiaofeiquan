# [meituan] GET /meituan/ticket/getMt

## 1. 基本信息

- 路径（兼容要求）：`/meituan/ticket/getMt`（建议同时兼容：`/meituan/ticket/getMt.html`）
- 源码定位：`app/meituan/controller/Ticket.php:43`
- 控制器/方法：`meituan/Ticket.getMt()`
- 描述：调试接口：服务端主动请求美团测试地址并回显响应
- 鉴权（基线）：`meituan(BA-sign)`；是否要求鉴权：`no`
- 文档注释（如有）：` `

## 2. 鉴权与 Header

- （无需鉴权 Header；按第三方/业务需要补充）

## 3. 请求

- HTTP Method：`GET`（现网未限制 method）
- Query/Body：无

## 4. 响应

- HTTP 状态码：`200`
- 返回结构：JSON 字符串（由 `MeituanService::outputSucc/outputError` 直接 `exit` 输出）
  - 成功：`{"code":200,"describe":"success","partnerId":...,...}`
  - 失败：`{"code":300,"describe":"<reason>","partnerId":...,...}`

## 5. 副作用与幂等

- 写库：无
- 外部调用：向美团测试 URI 发起请求（URI 在现网代码中硬编码）
- 幂等策略：天然幂等（取决于外部接口）

## 6. 测试用例（契约验收）

- 正常用例：请求返回 `code=200` 且 `partnerId` 存在（其余字段取决于美团测试接口返回）
- 异常用例：外部请求失败时返回 `code=300,describe=<reason>`
- 幂等/重放：重复调用结果取决于外部接口（本端点自身无状态）
