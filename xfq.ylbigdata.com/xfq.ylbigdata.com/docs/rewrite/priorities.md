# 重写优先级与分阶段建议（全接口兼容，允许清库/停机）

你们的约束是：**所有对外接口保持一致**；优势是：允许停机、允许清库。经验上最稳的交付方式是“契约先行 + 分域推进”。

## Phase 0：范围与验收体系（先做，否则后面必返工）

- 生成接口清单：`python3 scripts/generate_api_inventory.py`
- 为每个接口补齐契约：按 `docs/rewrite/contract-template.md`
- 建立契约测试/回放框架（建议做到可在 CI 一键跑完）：
  - 覆盖率检查：`python3 scripts/check_golden_coverage.py --tier p0`
  - 小程序 case 骨架：`python3 scripts/generate_miniapp_golden_cases.py`
  - P0 stub（先占位/先录制基础行为）：`python3 scripts/generate_p0_golden_stubs.py`
  - 回放/对比：`python3 scripts/run_golden_cases.py --cases-dir docs/rewrite/golden/cases/p0 --base-url http://HOST`
- 敏感配置治理：把 AK/密钥/密码从代码移到环境变量/密钥管理，并规划轮换

交付物：

- 全量接口清单 + 每个接口的契约草稿
- golden case 数据集（最小可用集覆盖关键链路）

## Phase 1：平台底座（所有应用共用）

优先把“所有接口都会用到”的东西做稳：

- 路由兼容：支持 `/{app}/{controller}/{action}` + 可选 `.html`
- 统一错误码与返回包裹（不同应用的 envelope 要分别兼容）
- 鉴权与会话：
  - `api/window/selfservice` 的 Token Header 规则
  - `admin/seller/travel` 的 Session 规则
  - `xc/meituan` 的签名/加密校验
- Redis：缓存 + session
- 文件上传：multipart 兼容、大小/后缀校验

## Phase 2：高风险边界（先啃硬骨头）

这些模块“最难回归、最容易出事故”，应优先实现并用契约测试锁死：

- 微信支付/退款：下单、回调验签、幂等、状态机一致
- OTA：
  - 携程：AES 解密/加密、md5 签名、请求/响应结构
  - 美团：BA 签名、header 规则、错误响应
- OSS 上传：路径规则、bucket 选择、返回 URL 结构
- 短信/实名认证/快递查询：外部 API 调用、超时/重试、错误码映射

## Phase 3：核心业务域（券/票/核销/订单）

按“业务闭环”交付（每条闭环必须全链路可测）：

- 用户/登录/资料
- 券：发放、领取、核销、回滚（含定时任务相关接口/逻辑）
- 票：售卖、订单、退款
- 结算：对账、汇总、导出

## Phase 4：后台（React 重做，但后端接口与入口保持一致）

- React 可以换 UI，但建议保持：
  - 原有入口 URL 可访问（外部链接、收藏、回调白名单不变）
  - 原有表单提交/跳转行为（至少从功能角度一致）
- 把历史“页面直出”的能力逐步替换为 API + SPA，但对外 URL 不变（服务端可统一返回 React Index）。

## Definition of Done（每个接口完成标准）

- 契约文档齐全（含错误码/幂等/副作用）
- 契约测试通过（与基线一致）
- 关键链路回归通过（支付/OTA/核销/退款等）
- 日志与监控就绪（可定位问题、可追责）
