# 密钥与第三方配置治理（重写期/上线期）

目标：在“PHP → Python 全量重写”过程中，**避免把真实密钥写入仓库**，并让开发/回放/上线的配置可控、可审计、可轮换。

## 原则（必须）

- 任何真实密钥/证书/私钥 **不进 git**（包括 `config/*.php`、golden case、日志）。
- 统一用 **环境变量 / 密钥系统** 注入（开发用 `.env.local`，生产用 Vault/云 KMS/CI Secret）。
- case 文件只允许用占位符：`${VAR_NAME}`（见 `docs/rewrite/golden/README.md`）。
- 需要证书文件（如微信退款）时，用 **挂载文件路径** + 环境变量指向，不把 pem 放进仓库。

## 你们需要准备的“配置清单”（建议按域名拆分）

**基础设施**

- MySQL：`DB_HOST/DB_PORT/DB_USER/DB_PASSWORD/DB_NAME`
- Redis：`REDIS_HOST/REDIS_PORT/REDIS_PASSWORD`（如有）

**小程序/站内接口鉴权（用于回放/本地联调）**

- `API_TOKEN/API_USERID`
- `WINDOW_TOKEN/WINDOW_UUID`
- `SELFSERVICE_TOKEN/SELFSERVICE_NO`

示例见：`xfq.ylbigdata.com/xfq.ylbigdata.com/.env.golden.example`

**OTA（携程 xc / 美团 meituan）**

- xc：
  - `XC_ACCOUNT_ID`
  - `XC_SIGN_KEY`
  - `XC_AES_KEY`
  - `XC_AES_IV`
- meituan：
  - `MEITUAN_CLIENT_ID`
  - `MEITUAN_CLIENT_SECRET`
  - `MEITUAN_PARTNER_ID`

协议要点见：

- `xfq.ylbigdata.com/xfq.ylbigdata.com/docs/rewrite/ota-xc-protocol.md`
- `xfq.ylbigdata.com/xfq.ylbigdata.com/docs/rewrite/ota-meituan-protocol.md`

**支付（微信）**

- 小程序 appid、mch_id、mch_key（以及 v2/v3 版本选型）
- 回调域名/回调路径（含 `.html` 变体）
- 退款证书（pem）挂载路径

> 建议：开发环境先用 mock provider 跑通“下单/回调/幂等/状态机”，再接真支付。

**上传/OSS/短信/实名**

- OSS：endpoint/bucket/ak/sk/路径规则
- 短信：provider key + 模板 id + 频控策略
- 实名：provider key + 错误码映射

## 推荐文件（不提交到 git）

- `xfq.ylbigdata.com/xfq.ylbigdata.com/.env.local`：开发者本机配置
- `xfq.ylbigdata.com/xfq.ylbigdata.com/.env.staging`：测试环境配置（由 CI/密钥系统注入）

仓库提供变量名模板：`xfq.ylbigdata.com/xfq.ylbigdata.com/.env.rewrite.example`

## Dev-only mock 开关（用于离线回放）

在“没有生产密钥/不想触发真实第三方调用”的重写期，可以在开发环境开启 mock：

- `REWRITE_MOCK_WECHAT_PAY=1`
- `REWRITE_MOCK_WECHAT_REFUND=1`
- `REWRITE_MOCK_SMS=1`
- `REWRITE_MOCK_IDENTITY=1`

这些开关应仅用于本地/测试环境；生产环境必须关闭并接入真实证书/密钥。
