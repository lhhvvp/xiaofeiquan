# 美团（meituan）对接协议要点（BA 签名）

> 用于 `meituan` 应用下的 OTA 接口。目标是让 Python 重写时严格兼容现网“BA 验证 + 返回结构”行为。

## 请求（现网实现）

- Content-Type：通常为 `application/json; charset=utf-8`
- 必要 Header（现网从 `$_SERVER` 读取，等价于 HTTP Header）：
  - `Date`：RFC1123 风格时间字符串（现网验签时按该字符串原样参与签名）
  - `Authorization`：`MWS {clientId}:{signature}`
  - `PartnerId`：对接方分配（请求侧发送；现网主要在响应中回传 partnerId）

源码参考：

- BA 验证与签名构造：`app/meituan/service/MeituanService.php`
- 中间件入口：`app/meituan/middleware/Auth.php`

## 签名（现网实现细节）

现网校验逻辑（完全匹配）：

- `string_to_sign = METHOD + " " + URI + "\n" + DATE`
  - `METHOD`：`$_SERVER['REQUEST_METHOD']`
  - `URI`：`$_SERVER['REQUEST_URI']`（包含 path 与 query；重写时务必保持一致）
  - `DATE`：`$_SERVER['HTTP_DATE']`（Header `Date`）
- `signature = base64(hmac_sha1(string_to_sign, client_secret))`
- `Authorization = "MWS " + clientId + ":" + signature`
- 若请求头 `Authorization` 不等于构造值，直接返回错误：`BA验证错误`

密钥来源：

- `clientId/clientSecret/partnerId` 来自 `config/ota.php`（**重写时必须从环境变量/配置中心读取**）

## 生成可回放的验签用例向量（本仓库脚本）

为了在“无生产数据”场景下可重复回放/验收，仓库提供了一个向量生成脚本（生成固定 `Authorization`）：

```bash
python3 scripts/gen_ota_vectors.py meituan \
  --method POST \
  --uri /meituan/index/system.html \
  --date 'Thu, 01 Jan 1970 00:00:00 GMT'
```

## 响应结构（现网实现）

现网存在两类响应风格，契约需按“每个 endpoint”锁死：

1) OTA/对接风格（常见于示例/对接接口）  
通过 `MeituanService::outputSucc/outputError` 或控制器直接返回 JSON 字符串：

- 成功（示例结构）：`{"code":200,"describe":"success","partnerId":<partnerId>, ...业务字段 }`
- 失败（示例结构）：`{"code":300,"describe":"<reason>","partnerId":<partnerId>, ...可选字段 }`

2) 站内风格（部分接口仍使用统一 envelope）  
通过 `BaseController::apiSuccess/apiError` 返回：`{code,msg,time,data}`（例如 `meituan/Index.system()`）。

编码注意：

- OTA/对接风格成功：`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`
- OTA/对接风格失败：`JSON_UNESCAPED_UNICODE`
