# [api] POST /api/pay/submit

## 1. 基本信息

- 路径（兼容要求）：`/api/pay/submit`（建议同时兼容：`/api/pay/submit.html`）
- 源码定位：`app/api/controller/Pay.php:44`
- 控制器/方法：`api/Pay.submit()`
- 描述：提交订单
- 鉴权（基线）：`api-token(Userid)`；是否要求鉴权：`yes`
- 文档注释（如有）：`POST /pay/submit`

## 2. 鉴权与 Header

- `Token`
- `Userid`
- `Pip(可选，仅大屏)`

## 3. 请求

- HTTP Method：`POST`
- Content-Type：通常为 `application/x-www-form-urlencoded`（小程序默认）；后端通过 `Request::param()` 取参，等价支持 query/form（以线上行为为准）
- 参数（最小集，按现有代码）：
  - `uid`：`int`，必填；必须与 Header `Userid` 一致（否则报 `用户信息异常 -H`）
  - `openid`：`string`，必填；付款人 OpenID
  - `coupon_uuno`：`string`，必填；消费券 uuno（用于查询券信息）
  - `data`：`string(JSON)`，必填；示例：`{"uuno":"<coupon_uuno>","number":1,"price":"10.00"}`
    - `uuno`：必须与 `coupon_uuno` 对应（库存扣减使用该值）
    - `number`：购买数量（>0）
    - `price`：购买单价（>0，且与券配置 `sale_price` 需一致，按 2 位小数比较）
  - `type`：`string`，可选；支付类型：`miniapp/mp/wap/app`，默认 `miniapp`

## 4. 响应

- HTTP 状态码：`200`
- 返回结构（JSON envelope）：`{ code, msg, time, data }`
  - 成功：`code=0`，`msg=订单添加成功`，`data`：
    - `pay`：拉起微信支付所需参数
      - `type=miniapp`：通常为对象（`timeStamp/nonceStr/package/signType/paySign/...`，以 `yansongda/pay` 返回为准）
      - `type=wap`：通常为 `mweb_url` 字符串
      - `type=app`：历史实现会从返回中截取 `{...}` 片段作为字符串（需以线上基线为准）
    - `order_no`：内部订单号（不含 `XFQ` 前缀）
    - `amount_price`：订单金额（字符串或数字，以线上为准）
  - 失败：`code!=0`，`msg` 为具体原因，`data` 通常为空对象/数组（以线上为准）
- 错误码（已知片段，完整以契约测试补齐）：
  - `110/111/112/113/115`：鉴权失败（见 `app/api/middleware/Auth.php`）
  - `1`：常规业务失败（例如 `当前用户信息异常` / `请至少购买一张消费券` / `消费券购买价异常，请查证` 等）
  - `3`：购买次数达到上限（`购买已达上限`）

## 5. 副作用与幂等

- 写库（概念级）：
  - 新增订单：`CouponOrder`
  - 新增订单明细：`CouponOrderItem`
  - 扣减库存：`CouponIssue.remain_count -= number`
  - 新增支付记录：`BasePaydata`（用于后续支付回调落库）
- 外部调用：微信统一下单（由 `yansongda/pay` 发起）
- 幂等策略：现有实现**非幂等**（重复调用会生成新订单并再次扣减库存）；如需增强幂等，必须同时保持对外返回与库存/订单副作用一致

## 6. 测试用例（契约验收）

- 正常用例：合法 Token + 参数合法，返回 `code=0` 且含 `pay/order_no/amount_price`
- 异常用例：
  - `openid` 缺失：`code=1`，`msg=当前用户信息异常`
  - `uid != Userid`：`code=1`，`msg` 以 `用户信息异常 -H` 开头
  - `data.number=0`：`code=1`，`msg=请至少购买一张消费券`
  - `price<=0`：`code=1`，`msg=消费券面额至少大于0.01，否则无法调起支付`
  - 购买次数超限：`code=3`，`msg=购买已达上限`
- 幂等/重放：重复调用会产生多笔订单/多次扣库存（现状）；重写后需通过契约用例明确是否允许改变这一行为
