# [xc] POST /xc/ticket/pay

## 1. 基本信息

- 路径（兼容要求）：`/xc/ticket/pay`（建议同时兼容：`/xc/ticket/pay.html`）
- 源码定位：`app/xc/controller/Ticket.php:51`
- 控制器/方法：`xc/Ticket.pay()`
- 描述：提交订单
- 鉴权（基线）：无（业务侧以 `uuid` 识别售票员/商户）；是否要求鉴权：`no`
- 文档注释（如有）：`POST /ticket/pay`

## 2. 鉴权与 Header

- （无需鉴权 Header）
- 兼容备注：现网 `xc` 应用中存在 OTA 中间件（AES+签名）与“窗口售票”接口混用的情况；重写建议以线上回放/真实调用方为准，至少保证该端点可按表单参数调用

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`
- 参数（按现网逻辑）：
  - `data`：`string(JSON)`，必填；门票&出行人信息数组（每项至少包含：`uuno/number/price/fullname/cert_type/cert_id`，字段以现网前端为准）
  - `contact`：`string(JSON)`，必填；联系人信息：`{"contact_man":"...","contact_phone":"..."}`
  - `ticket_date`：`string`，必填；使用日期（`YYYY-MM-DD`）
  - `paytype`：`string`，必填；支付方式（现网用于写订单字段）
  - `uuid`：`string`，必填；售票员账号标识（用于查询 `ticket_user`）

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0,msg=订单添加成功,data=[]`
  - 失败：`code!=0,msg` 为错误原因
- 失败原因（示例，按现网校验）：
  - `请求方式错误`
  - `data请求的格式不是json` / `contact请求的格式不是json`
  - `<param>不能为空`
  - `未找到用户` / `当前账户已被锁定`
  - `未找到相关门票信息...` / `该门票暂未设置报价...` / `库存不足...`

## 5. 副作用与幂等

- 写库（概念级）：
  - 创建订单：`ticket_order`（现网写 `channel=window`）
  - 创建明细：`ticket_order_detail`
  - 扣减库存：`ticket_price.stock -= number`
- 外部调用：无
- 幂等策略：无（重复提交会重复建单/扣库存）

## 6. 测试用例（契约验收）

- 正常用例：参数齐全且库存充足，返回 `code=0,msg=订单添加成功`
- 异常用例：缺参/用户不存在/票种不存在/库存不足，返回 `code!=0` 且 msg 对应
- 幂等/重放：重复提交同一请求会产生多笔订单（现状）
