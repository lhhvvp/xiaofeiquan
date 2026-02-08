# 携程（xc）对接协议要点（签名 + AES）

> 用于 `xc` 应用下的 OTA 接口（例如 `VerifyOrder/CreatePreOrder/PayPreOrder/...`）。目标是让 Python 重写时严格兼容现网“验签/解密/响应码”行为。

## 请求格式（现网实现）

- Content-Type：`application/json; charset=utf-8`
- Body：JSON，必须包含：
  - `header`：对象
  - `body`：字符串（**加密后的 bodyJsonStr**，不是对象）

`header` 关键字段（以现网中间件读取为准）：

- `accountId`
- `serviceName`
- `requestTime`
- `version`
- `sign`

源码参考：

- 验签与解密：`app/xc/middleware/Auth.php`
- AES 加解密实现：`app/xc/service/XiechengService.php`
- 供应商请求携程：`app/xc/service/XiechengService.php`

## AES 加密/解密（现网实现细节）

- 算法：`AES-128-CBC`
- Padding：PKCS5（现网通过手工填充 + `OPENSSL_ZERO_PADDING` 实现）
- key/iv：来自配置 `config/ota.php`（**重写时必须从环境变量/配置中心读取**）
- 重要：加密结果不是 base64/hex，而是自定义 `encodeBytes`（每个 nibble 映射为 `a`~`p`）

## 签名（现网实现细节）

`signTarget = strtolower(md5(accountId + serviceName + requestTime + bodyStr + version + signKey))`

- `bodyStr` 是“加密后的字符串”（不是解密后的 JSON）
- `signKey` 来自配置 `config/ota.php`（禁止写死到代码/文档）
- 失败返回（中间件直接返回，不进入业务逻辑）：
  - 报文解析失败：`resultCode="0001"`
  - 签名错误：`resultCode="0002"`
  - 供应商账户不正确：`resultCode="0003"`

## 响应格式

- 成功业务响应通常为：
  - `{"header":{"resultCode":"0000","resultMessage":"..."},"body":<string|object>}`
- 认证/报文失败由中间件返回：
  - `{"header":{"resultCode":"0001/0002/0003","resultMessage":"..."}}`

> 注意：`xc` 的“接口路径”在现网可能既存在单入口（如 `accept`）也存在按方法路由的入口（如 `/xc/order/VerifyOrder`）。为保证兼容，建议 Python 侧同时兼容两类入口（以线上回放为最终准）。

## 生成可回放的验签用例向量（本仓库脚本）

为了在“无生产数据”场景下可重复回放/验收，仓库提供了一个向量生成脚本（生成加密 `body` 与 `sign`）：

```bash
python3 scripts/gen_ota_vectors.py xc \
  --service-name system \
  --request-time 19700101000000 \
  --body-json '{}'
```

