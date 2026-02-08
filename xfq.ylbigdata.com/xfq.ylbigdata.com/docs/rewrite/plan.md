# 重写项目实施计划（停机 + 清库 + 接口 1:1 兼容）

本计划的目标是：在允许长期停机/清库的前提下，把后端从 **PHP（ThinkPHP6 多应用）** 全量重写为 **Python（FastAPI）**，并保证 **所有对外 HTTP 接口保持一致**（URL/方法/参数/Header/返回结构与错误码/回调副作用），同时兼容历史 `.html` 路径。

> 说明：你们当前拿不到「线上 Nginx/网关日志」与「生产三方证书/密钥」。这不阻止开工，但会影响最终上线验收的确定性，因此本计划把“开工条件”和“上线 Gate”严格拆开。

## 0) 本项目的硬约束（不可妥协）

- 对外接口 1:1：路径/方法/query/body/header/返回体/错误码/副作用一致
- 兼容 `.html`：同一路径 `.../action` 与 `.../action.html` 都要可用
- 可清库：允许清空生产数据（但系统配置/管理员/支付配置等必须可重复 seed）
- DB 可替换：不必连接达梦，可用 MySQL 或 PostgreSQL（当前本仓库默认 MySQL + Redis）

## 1) 交付策略（为什么这样做）

核心策略：**契约先行 + golden 回放锁死行为 + 可重复 seed**。

- **契约**：`docs/rewrite/contracts/p0/`（每接口一份）
- **可执行验收**：`docs/rewrite/golden/`（case 文件 + runner），用旧实现录 baseline 或用 “legacy-php（仓库代码）” 录 baseline
- **可重复初始化**：`make db-reset-minimal` / `make db-reset-success`
- **外部依赖先 mock**：支付/短信/实名/OTA 在本地可跑可回放（上线前再替换真实配置）

## 2) 现状（你们已经具备的“开工条件”）

- 范围基线：
  - P0 接口清单：`docs/rewrite/p0-interfaces.tsv`
  - 小程序调用面：`docs/rewrite/miniapp-api-usage.tsv`
  - P0 backlog：`docs/rewrite/p0-backlog.md` / `docs/rewrite/p0-backlog.tsv`
- 验收体系：
  - P0 golden：`docs/rewrite/golden/cases/p0/`（含 OTA 有效验签用例）
  - 成功路径 suite：`docs/rewrite/golden/cases/p0_success/`
- 可重复环境：
  - `docker-compose.rewrite.yml` + `Makefile`（见快速命令）

## 3) 分阶段实施计划（里程碑 + Gate）

### Phase A：验收体系固化（持续维护）

目标：任何改动都能被自动回放验收。

- Gate：
  - `make py-check-p0-dev-minimal` 全绿（P0：不写库为主）
  - `make py-check-p0-success-dev-reset` 全绿（成功路径：会写库）
- 日常规则：
  - 写库用例只放 `p0_success`，每次跑前先 reset DB
  - 动态字段用 golden ignore pointer/keys 锁定（不要让时间戳/订单号毁掉回放稳定性）

### Phase B：Python 平台底座（所有接口共用）

目标：路由/鉴权/响应封装/上传解析/数据库访问稳定可复用。

- 交付物：
  - `.html` 兼容、鉴权中间件、OTA 验签中间件、统一响应封装
  - 维护模式/流量捕获（用于补齐真实流量面）：`docs/rewrite/traffic-capture.md`

### Phase C：高风险边界（优先实现）

目标：先啃最难回归、最容易出事故的边界。

- 优先序（建议）：
  1) OTA：携程/美团（验签、解密、错误码、请求 URI 处理）
  2) 支付/退款：下单、回调、幂等、状态机一致（先 mock，后接真实）
  3) 上传/OSS：multipart 兼容、大小/后缀校验、返回字段稳定
  4) 短信/实名：mock + 错误码映射（上线前接真实）
- Gate：
  - 对应接口 golden 全绿（含失败/成功用例）
  - 写库接口必须可重复回放（配合 `db-reset-*`）

### Phase D：小程序核心业务闭环（按调用面推进）

目标：以小程序 67 个实际调用接口为主线，保证闭环可验收。

- 方法：
  - 每个接口做到：契约（md）+ golden（失败/成功）+ seed（必要数据）+ Python 实现
  - 按 `docs/rewrite/p0-backlog.tsv` 的 `B1-miniapp` 分组逐批推进

### Phase E：剩余 P0 → P1/P2（全量收口）

目标：把非小程序但仍对外的入口补齐（窗口/自助机/其它对接）。

- Gate：所有 tier 的 golden 覆盖达标，且全量回放通过。

### Phase F：上线前 Gate（没有它们就不要切生产）

缺少以下信息时可以开工，但 **不能宣称“生产可用”**：

- 线上真实流量面（Nginx/网关日志 7–14 天）
- 生产三方真实配置/证书：微信支付证书、OSS、短信/实名等

替代方案（拿不到历史日志时）：

- 部署维护模式流量捕获，短期接管域名并记录真实请求形态，再与 `p0-interfaces.tsv` 做差集补齐（见 `docs/rewrite/traffic-capture.md`）。

## 4) 每个接口的 Done 标准（执行口径）

- 契约：`docs/rewrite/contracts/p0/...` 写清楚
  - 认证方案、header、错误码、幂等、是否写库/副作用
- golden：
  - 至少 1 个失败用例（锁定错误结构/错误码）
  - 需要的话补成功用例（写库则放 `p0_success`）
- seed：
  - 清库后能跑通（避免“空库 find(1) 直接崩”）
  - 必要业务数据必须 deterministic（时间戳/随机数固定或在 compare 忽略）
- Python 实现：
  - 本地回放全绿（P0 + success）

## 5) 快速命令（推荐工作流）

- 启动环境：`make legacy-up && make py-up`
- P0 回归（最小 seed）：`make py-check-p0-dev-minimal`
- 成功路径（写库，success seed）：`make py-check-p0-success-dev-reset`

## 6) 相关文档入口

- 准备度清单：`docs/rewrite/readiness-checklist.md`
- 优先级建议：`docs/rewrite/priorities.md`
- P0 backlog：`docs/rewrite/p0-backlog.md`
- golden 规则：`docs/rewrite/golden/README.md`
- seed：`docs/rewrite/seed.md`
- OTA 协议：`docs/rewrite/ota-meituan-protocol.md` / `docs/rewrite/ota-xc-protocol.md`

