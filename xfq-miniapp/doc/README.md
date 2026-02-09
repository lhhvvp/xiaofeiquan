# 文档索引（从这里开始）

本目录用于保障“微信小程序原生重构”可被稳定推进、可跟踪、可验收、可上线与可回滚。

## 1) 总体方案
- `xfq-miniapp/doc/native-refactor-plan.md`：重构目标、架构、里程碑、分包、范围控制、风险与灰度回滚策略。
- `xfq-miniapp/doc/native-1to1-master-plan.md`：1:1 还原 + UI 优化 + 一次切换的强约束执行总计划。

## 2) 执行与验收
- `xfq-miniapp/doc/migration-tracker.csv`：页面迁移跟踪表（建议用 Excel 维护）。  
- `xfq-miniapp/doc/batch-execution-checklist.md`：逐批执行单（Batch 模板、闸门、签字规则、预排批次）。  
- `xfq-miniapp/doc/page-dod-checklist.md`：页面迁移 DoD + 高风险链路加严清单（支付/定位/核销）。  
- `xfq-miniapp/doc/m3-smoke-test.md`：M3（交易闭环）自测/冒烟用例清单（支付/订单/退款/核销）。  
- `xfq-miniapp/doc/m4-smoke-test.md`：M4（非核心补齐）自测/冒烟用例清单（公告/协议）。  
- `xfq-miniapp/doc/b14-full-regression-checklist.md`：B14 全量回归执行清单（一次切换前总闸门）。  
- `xfq-miniapp/doc/b14-go-nogo-template.md`：B14 Go/No-Go 评审模板（发布签字单）。  
- `xfq-miniapp/doc/b14-readiness-report.md`：B14 当前就绪度快照与剩余工作。  
- `xfq-miniapp/mp-native/tools/check-miniprogram.js`：原生小程序自检（路由文件存在性 + JS/JSON 语法校验）。运行：`cd xfq-miniapp/mp-native && npm run check`
- `xfq-miniapp/mp-native/tools/mock-smoke-test.js`：mock 模式冒烟自测（覆盖主要接口与 mockPayment）。运行：`cd xfq-miniapp/mp-native && npm run mock:smoke`

## 3) 治理（保证“做得完”的关键补充）
- `xfq-miniapp/doc/decision-log.md`：关键决策记录（避免反复争论与返工）。
- `xfq-miniapp/doc/risk-register.csv`：风险台账（责任到人、触发信号与应对动作）。
- `xfq-miniapp/doc/release-runbook.md`：发布/灰度/回滚操作手册（可直接照着跑）。
- `xfq-miniapp/doc/observability-spec.md`：埋点/监控/日志规范与事件表模板。
- `xfq-miniapp/doc/env-config.md`：多环境配置与域名/开关/密钥管理约定。

## 4) 旧工程扫描产物（对齐与迁移依据）
运行脚本：
`powershell -ExecutionPolicy Bypass -File xfq-miniapp/doc/tools/scan-legacy.ps1`

输出文件：
- `xfq-miniapp/doc/legacy-pages.json`、`xfq-miniapp/doc/legacy-pages.txt`
- `xfq-miniapp/doc/legacy-endpoints.json`、`xfq-miniapp/doc/legacy-endpoints.txt`
- `xfq-miniapp/doc/legacy-storage-keys.json`、`xfq-miniapp/doc/legacy-storage-keys.txt`
- `xfq-miniapp/doc/legacy-wx-apis.json`、`xfq-miniapp/doc/legacy-wx-apis.txt`
- `xfq-miniapp/doc/legacy-uni-apis.json`、`xfq-miniapp/doc/legacy-uni-apis.txt`
