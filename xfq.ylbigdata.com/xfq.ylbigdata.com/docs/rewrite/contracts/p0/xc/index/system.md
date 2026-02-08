# [xc] POST /xc/index/system

## 1. 基本信息

- 路径（兼容要求）：`/xc/index/system`（建议同时兼容：`/xc/index/system.html`）
- 源码定位：`app/xc/controller/Index.php:64`
- 控制器/方法：`xc/Index.system()`
- 描述：返回系统信息
- 鉴权（基线）：无；是否要求鉴权：`no`
- 文档注释（如有）：`POST /index/system`

## 2. 鉴权与 Header

- （无需鉴权 Header）
- 兼容备注：现网 `xc/Index` 的中间件配置与该接口形态不一致（源码疑似拷贝/未完成），建议以线上回放确认；重写侧建议保证该端点可匿名访问

## 3. 请求

- HTTP Method：`POST`（现网文档注释为 POST；实现本身不依赖 method）
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

## 5. 副作用与幂等

- 写库：无（只读）
- 外部调用：无
- 幂等策略：天然幂等

## 6. 测试用例（契约验收）

- 正常用例：不带任何鉴权 Header，返回 `code=0,msg=请求成功` 且 `data` 字段齐全
- 异常用例：DB/配置异常时返回非 0 code（需回放补齐）
- 幂等/重放：重复请求返回应稳定
