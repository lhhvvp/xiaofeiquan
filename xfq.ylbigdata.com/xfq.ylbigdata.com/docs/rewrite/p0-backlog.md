# P0 重写 Backlog（自动生成）

- 接口总数：193
- 小程序（mp-native）实际调用覆盖：67

## 分组统计

- `A0-external-callbacks`：38
- `A1-money`：15
- `A2-identity-upload`：12
- `B1-miniapp`：56
- `B2-jobs`：15
- `C-remaining`：57

## 标签统计

- `AUTH_LOGIN`：3
- `BUSINESS`：113
- `OTA_MT`：9
- `OTA_XC`：26
- `PAY_API`：5
- `PAY_NOTIFY`：3
- `REFUND`：10
- `SMS_ID`：3
- `SYSTEM_TASK`：15
- `UPLOAD`：6

## 建议实现顺序（按分组）

- A0 外部回调/对接（支付通知、OTA）：先做，便于尽早联调第三方
- A1 资金链路（支付/退款）：先做，逻辑复杂且风险高
- A2 身份/上传（短信、实名认证、文件上传）：先做，牵涉外部依赖与安全
- B1 小程序调用接口：按业务闭环补齐并用契约测试锁定

## Top 30（按当前排序）

- `UNKNOWN` `/meituan/index/captcha` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/index/captcha.md`
- `UNKNOWN` `/meituan/index/change` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/index/change.md`
- `UNKNOWN` `/meituan/index/demo` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/index/demo.md`
- `POST` `/meituan/index/system` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/index/system.md`
- `POST` `/meituan/index/winlogin` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/index/winlogin.md`
- `UNKNOWN` `/meituan/ticket/getMt` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/ticket/getmt.md`
- `POST` `/meituan/ticket/pay` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/ticket/pay.md`
- `UNKNOWN` `/meituan/upload/index` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/upload/index.md`
- `UNKNOWN` `/meituan/webupload/index` (OTA_MT, miniapp=no) -> `docs/rewrite/contracts/p0/meituan/webupload/index.md`
- `UNKNOWN` `/xc/index/captcha` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/index/captcha.md`
- `UNKNOWN` `/xc/index/change` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/index/change.md`
- `POST` `/xc/index/system` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/index/system.md`
- `POST` `/xc/index/winlogin` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/index/winlogin.md`
- `UNKNOWN` `/xc/order/CancelOrder` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/cancelorder.md`
- `UNKNOWN` `/xc/order/CancelPreOrder` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/cancelpreorder.md`
- `UNKNOWN` `/xc/order/CreatePreOrder` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/createpreorder.md`
- `UNKNOWN` `/xc/order/DateInventoryModify` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/dateinventorymodify.md`
- `UNKNOWN` `/xc/order/OrderConsumedNotice` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/orderconsumednotice.md`
- `UNKNOWN` `/xc/order/OrderTravelNotice` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/ordertravelnotice.md`
- `UNKNOWN` `/xc/order/PayPreOrder` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/paypreorder.md`
- `UNKNOWN` `/xc/order/QueryOrder` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/queryorder.md`
- `UNKNOWN` `/xc/order/VerifyOrder` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/verifyorder.md`
- `UNKNOWN` `/xc/order/accept` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/accept.md`
- `UNKNOWN` `/xc/order/testGetOrder` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/order/testgetorder.md`
- `UNKNOWN` `/xc/ticket/OrderRefund` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/ticket/orderrefund.md`
- `UNKNOWN` `/xc/ticket/OrderRefundDetail` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/ticket/orderrefunddetail.md`
- `POST` `/xc/ticket/detail` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/ticket/detail.md`
- `UNKNOWN` `/xc/ticket/getTicketPirce` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/ticket/getticketpirce.md`
- `POST` `/xc/ticket/list` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/ticket/list.md`
- `POST` `/xc/ticket/pay` (OTA_XC, miniapp=no) -> `docs/rewrite/contracts/p0/xc/ticket/pay.md`
