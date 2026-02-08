# 重写（Python + React）接口兼容资料

目标：后端由 PHP（ThinkPHP6 多应用）重写为 Python，管理端可用 React 重做，但**所有对外 HTTP 接口保持一致**（URL、方法、参数、Header、返回结构/错误码、回调响应与副作用）。

本目录提供三类产物：

- `docs/rewrite/api-inventory.tsv`：对外接口清单（可由脚本持续生成）
- `docs/rewrite/p0-interfaces.tsv`：P0（机器/第三方）接口清单（由脚本生成）
- `docs/rewrite/contracts/p0/`：P0 接口契约草稿（每接口 1 份，脚本生成）
- `docs/rewrite/golden/`：契约验收用 golden case（回放/对比脚本 + 模板）
  - `docs/rewrite/golden/cases/p0/`：P0 最小回归集（尽量不写库，适合频繁跑）
  - `docs/rewrite/golden/cases/p0_success/`：P0 成功路径集（会写库，建议配合 `make db-reset-success` 跑）
- `docs/rewrite/miniapp-api-usage.tsv`：小程序（mp-native）调用到的 API 路径清单（由脚本生成，用于优先级/回归）
- `docs/rewrite/contract-template.md`：接口契约模板（每个接口 1 份）
- `docs/rewrite/priorities.md`：重写优先级/分阶段建议
- `docs/rewrite/plan.md`：重写项目实施计划（里程碑 + Gate + 每接口 Done 标准）
- `docs/rewrite/master-plan.md`：重写主计划（执行版）
- `docs/rewrite/feasibility.md`：可行性评估与落地建议（停机/清库场景）
- `docs/rewrite/readiness-checklist.md`：重写准备度检查清单（建议按此补齐缺口）
- `docs/rewrite/p0-migration-board.tsv`：P0 接口迁移看板（implemented/stub-only）
- `docs/rewrite/reports/rewrite-status.md`：自动状态报告（每次执行可刷新）
- `docs/rewrite/reports/golden-quality.md`：golden 基线质量审计报告
- `docs/rewrite/reports/rewrite-execution-report.md`：阶段执行报告（含待确认项）
- `docs/rewrite/secrets.md`：密钥与第三方配置治理（环境变量/密钥系统）
- `docs/rewrite/seed.md`：允许清库场景下的一键初始化（schema + seed）准备清单
- `docs/rewrite/seed-dependencies.tsv`：静态扫描出的“固定 id 依赖”候选清单（用于 seed 排查）
- `docs/rewrite/system-tasks.tsv`：P0 `SYSTEM_TASK`（定时任务）接口清单
- `docs/rewrite/dev-env.md`：本地开发环境（MySQL + Redis + golden 工作流）
- `docs/rewrite/p0-quality-batch1.md`：P0 质量批次 1（6 个 case 独立基线）
- `docs/rewrite/p0-quality-batch2.md`：P0 质量批次 2（2 个 case 独立基线）
- `docs/rewrite/p0-quality-batch3.md`：P0 质量批次 3（4 个 upload/webupload multipart case 独立基线）
- `docs/rewrite/p0-quality-batch4.md`：P0 质量批次 4（2 个 meituan/xc webupload multipart case 独立基线）
- `docs/rewrite/p0-quality-batch5.md`：P0 质量批次 5（8 个结构化分支 case 独立基线）
- `docs/rewrite/p0-quality-batch6.md`：P0 质量批次 6（2 个结构化分支 case 独立基线）
- `docs/rewrite/p0-quality-batch7.md`：P0 质量批次 7（1 个结构化分支 case 独立基线）
- `docs/rewrite/p0-quality-batch8.md`：P0 质量批次 8（7 个严格兼容分支 case 独立基线）
- `docs/rewrite/p0-quality-batch9.md`：P0 质量批次 9（5 个严格兼容分支 case 独立基线）
- `docs/rewrite/p0-quality-batch10.md`：P0 质量批次 10（7 个 empty-200 分支 case 独立基线）
- `docs/rewrite/p0-quality-batch11.md`：P0 质量批次 11（1 个 empty-200 剩余分支 case 独立基线）
- `docs/rewrite/p0-quality-batch12.md`：P0 质量批次 12（5 个 legacy-500 分支转独立回归）
- `docs/rewrite/p0-quality-batch13.md`：P0 质量批次 13（8 个 legacy 低质量分支转独立回归）
- `docs/rewrite/p0-quality-batch14.md`：P0 质量批次 14（9 个 legacy 低质量分支转独立回归）
- `docs/rewrite/p0-quality-batch15.md`：P0 质量批次 15（2 个 legacy 低质量分支转独立回归）
- `docs/rewrite/p0-quality-batch16.md`：P0 质量批次 16（8 个 legacy empty-200 分支转独立回归）
- `docs/rewrite/p0-quality-batch17.md`：P0 质量批次 17（12 个 legacy 500 分支转独立回归）
- `docs/rewrite/p0-success-batch18.md`：P0 成功批次 18（4 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch19.md`：P0 成功批次 19（5 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch20.md`：P0 成功批次 20（4 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch21.md`：P0 成功批次 21（5 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch22.md`：P0 成功批次 22（5 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch23.md`：P0 成功批次 23（5 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch24.md`：P0 成功批次 24（5 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch25.md`：P0 成功批次 25（5 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch26.md`：P0 成功批次 26（5 个成功路径稳态回归）
- `docs/rewrite/p0-success-batch27.md`：P0 成功批次 27（4 个成功路径稳态回归）
- `docs/rewrite/traffic-capture.md`：无法拿到线上 Nginx/网关日志时的替代方案（维护模式流量捕获）
- `docs/rewrite/ota-xc-protocol.md`：携程对接协议要点（签名 + AES）
- `docs/rewrite/ota-meituan-protocol.md`：美团对接协议要点（BA 签名）

## 快速生成接口清单

在项目根目录（`xfq.ylbigdata.com/xfq.ylbigdata.com`）执行：

```bash
python3 scripts/generate_api_inventory.py
```

会生成/刷新：

- `docs/rewrite/api-inventory.tsv`

该文件是“骨架清单”：优先覆盖**所有 Controller 公共方法**与**显式 Route 规则**，并尝试解析 `@api` 注释（若存在）。

## 快速生成 P0 契约草稿

```bash
python3 scripts/generate_contract_stubs.py --tier p0
```

会生成/刷新：

- `docs/rewrite/p0-interfaces.tsv`
- `docs/rewrite/contracts/p0/`

P0 范围默认为：`api/window/selfservice/xc/meituan`（面向小程序/前端调用与第三方回调/对接的接口）。

同理也可生成：

```bash
python3 scripts/generate_contract_stubs.py --tier p1   # admin/seller/travel/travelv2
python3 scripts/generate_contract_stubs.py --tier p2   # index/mobile/handheld
```

## 提取小程序调用接口清单

在项目根目录（`xfq.ylbigdata.com/xfq.ylbigdata.com`）执行：

```bash
python3 scripts/extract_miniapp_api_usage.py
```

会生成/刷新：

- `docs/rewrite/miniapp-api-usage.tsv`

并可检查“小程序调用路径是否都在 P0 清单里”：

```bash
python3 scripts/check_miniapp_coverage.py
```

也可基于小程序服务层自动生成第一批 golden case 骨架，并检查覆盖：

```bash
python3 scripts/generate_miniapp_golden_cases.py
python3 scripts/check_golden_miniapp_coverage.py
```

也可一键生成 P0 全量 stub（把 golden 覆盖率补到 193/193）：

```bash
python3 scripts/generate_p0_golden_stubs.py
python3 scripts/check_golden_coverage.py --tier p0
```

也可生成“迁移看板 + 状态报告”：

```bash
python3 scripts/generate_rewrite_status_report.py
```

也可审计 golden 基线质量（识别 500 页、空响应等低质量用例）：

```bash
python3 scripts/audit_golden_quality.py
```

## ThinkPHP 路由与兼容要点（做契约时必须写清）

- 多应用默认形态通常为：`/{app}/{controller}/{action}`  
- 项目 `config/route.php` 设置了 `url_html_suffix = html`，实务中大量接口以 `.html` 结尾（例如支付回调 URL 在代码里直接拼了 `.html`）。
- 因此新系统建议同时兼容两种形式：
  - `/{app}/{controller}/{action}`
  - `/{app}/{controller}/{action}.html`

> 注意：仅靠源码静态扫描无法 100% 还原线上真实路径（例如动态路由、Nginx 规则、域名绑定等）。清单用于“范围盘点 + 契约落地”，最终以**线上流量回放/契约测试**验收。

## 鉴权（按应用分层的事实基线）

以下是从现有中间件/基类代码总结的“兼容基线”（写契约时把 Header/错误码固定下来）：

- `api`：`Token` + `Userid`（可选 `Pip` 代表大屏来源），返回包裹通常为 `code/msg/time/data`。
- `window`：`Token` + `Uuid`，返回包裹 `code/msg/time/data`。
- `selfservice`：`Token` + `No`，返回包裹 `code/msg/time/data`。
- `admin`/`seller`/`travel`：Session（`PHPSESSID`），多数返回 HTML（也可能在 Ajax 时返回 JSON）。
- `xc`（携程）：请求体含 `header/body`，并做 AES 解密 + md5 签名校验；响应结构为 `header.resultCode/resultMessage`（部分接口还带 `body`）。
- `meituan`：BA 签名校验（`Authorization/Date/PartnerId` 等 Header），响应结构偏业务方约定（`code/describe/partnerId/...`）。

## `api-inventory.tsv` 字段说明

- `app`：应用名（`app/<app>/...`）
- `controller`：控制器相对路径（不含 `.php`）
- `action`：方法名（默认视为可路由 Action）
- `route_guess` / `route_guess_html`：按默认规则推断的路径
- `doc_method` / `doc_path` / `doc_desc`：若方法上方有 `@api` / `@apiDescription` 注释则解析出来
- `auth_required`：基于中间件 `except/only` 的静态推断（仅供排期参考，最终以契约测试为准）
- `file` / `line`：定位源码

## 下一步建议

1. 先定“契约验收体系”：为每个接口补齐 `contract-template.md`，并建立 golden test / 流量回放。
2. 先攻高风险边界：支付回调/退款、OTA（携程/美团）、上传/OSS、短信与实名认证。
3. 清库也需要初始化：系统配置、支付配置、管理员账号等必须有可重复的 seed 流程。

建议把“写库的成功路径”用例独立出来，并在每次回放前一键重置数据库：

```bash
make db-reset-success
make legacy-record-p0-success   # 或 make py-check-p0-success
```

## Golden case（契约验收/回放）

见：`docs/rewrite/golden/README.md`

## 清库初始化（schema + seed）

见：`docs/rewrite/seed.md`

## 准备度清单

见：`docs/rewrite/readiness-checklist.md`
