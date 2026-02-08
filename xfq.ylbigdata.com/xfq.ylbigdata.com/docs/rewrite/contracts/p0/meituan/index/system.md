# [meituan] POST /meituan/index/system

## 1. 基本信息

- 路径（兼容要求）：`/meituan/index/system`（建议同时兼容：`/meituan/index/system.html`）
- 源码定位：`app/meituan/controller/Index.php:459`
- 控制器/方法：`meituan/Index.system()`
- 描述：返回系统信息
- 鉴权（基线）：`meituan(BA-sign)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /index/system`

## 2. 鉴权与 Header

- `Authorization`
- `Date`
- `PartnerId`
- `Content-Type: application/json`
- 协议（BA 签名）：见 `docs/rewrite/ota-meituan-protocol.md`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：任意（无入参）
- Query/Body：无

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0,msg=请求成功`
  - `data` 字段（现网返回子集）：
    - `service/policy/name/logo/copyright/act_rule/tel`
    - `is_open_api/message_code/is_queue_number/is_qrcode_number/is_clock_switch`
    - `slide`：轮播对象（`Slide.tags=index,status=1` 的一条记录）
- 错误码：无固定（通常仅 DB 异常才会失败；需回放补齐）

> 注意：若 BA 验签失败，中间件会直接返回 `{"code":300,"describe":"BA验证错误","partnerId":...}`（非 envelope），该行为需要保持一致。

## 5. 副作用与幂等

- 写库：无（只读）
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：携带合法 BA Header（见协议文档），返回 `code=0,msg=请求成功` 且 `data` 字段齐全
- 异常用例：BA Header 缺失/签名不匹配，返回 `{"code":300,"describe":"BA验证错误","partnerId":...}`（非 envelope）
- 幂等/重放：重复请求返回应稳定
