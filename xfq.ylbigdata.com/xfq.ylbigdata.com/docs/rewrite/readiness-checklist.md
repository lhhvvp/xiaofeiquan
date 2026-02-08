# 重写准备度检查清单（停机 + 清库 + 接口 1:1 兼容）

你们的硬约束是“**所有对外接口保持一致**”，允许长期停机/清库是优势。准备度是否足够，关键看：**能否在不依赖老系统实现细节的前提下，把接口行为锁死并可验收**。

下面是一份“可落地”的检查清单（含当前仓库已具备的产物与仍缺的关键项）。

## 1) 接口范围是否已锁定（范围基线）

- [x] 对外接口骨架清单：`docs/rewrite/api-inventory.tsv`
- [x] P0 接口清单（小程序/第三方）：`docs/rewrite/p0-interfaces.tsv`
- [x] 小程序实际调用面（67）：`docs/rewrite/miniapp-api-usage.tsv`（并已做覆盖校验）
- [ ] 线上真实流量面（推荐）：从 Nginx/网关日志导出 7–14 天真实路径与方法分布（静态扫描无法 100% 覆盖动态路由/隐式接口）
- [x] 无法获取历史日志时的替代方案：维护模式流量捕获（记录 method/path/query/header 名称/Body 哈希）：`docs/rewrite/traffic-capture.md`

## 2) 契约是否已可验收（不是“写文档”，而是能跑的标准）

- [x] 契约模板：`docs/rewrite/contract-template.md`
- [x] P0 契约目录：`docs/rewrite/contracts/p0/`
- [x] 高风险边界契约已补齐（支付回调/退款、上传、短信、实名、OTA xc/meituan 等）
- [x] golden case 数据集骨架：`docs/rewrite/golden/README.md`
  - [x] P0 每个接口至少 1 个 case（当前已覆盖 193/193，可用脚本校验）
  - [x] 已可用 `legacy-php` 录制 baseline 并写回 case（P0 已录制；仍建议补充线上日志/成功用例）

> 没有 golden case 时，重写验收会退化成“人肉点点点”，且很难保证 1:1。

## 3) 清库是否真的可行（seed / 初始化）

- [x] seed 准备说明：`docs/rewrite/seed.md`
- [x] 代码静态扫描出的“固定 id 依赖”：`docs/rewrite/seed-dependencies.tsv`
- [x] schema + seed 一键化（MySQL + Redis）：`docker-compose.rewrite.yml`（最小 seed：`infra/mysql/02-seed-minimal.sql`）
- [x] 最小可用业务数据集（配合成功路径回放）：`infra/mysql/04-seed-minimal-business.sql`（`make seed-business`）
- [x] 清库重置脚本（保证回放可重复）：`make db-reset-minimal` / `make db-reset-success`（success seed：`infra/mysql/05-seed-success.sql`）

## 4) 外部依赖与密钥是否可迁移（上线前的硬门槛）

- [x] 第三方清单与密钥托管策略（不把真实密钥写进仓库；用环境变量/密钥系统）：`docs/rewrite/secrets.md`（变量模板：`.env.rewrite.example`）
- [ ] 微信支付/退款证书与回调域名策略（尤其 `.html` 回调形态）
- [x] 微信支付/退款、短信、实名 的 mock 与真环境切换开关（用于停机期间本地回放）：`docs/rewrite/secrets.md` + `.env.docker`
- [ ] OSS/对象存储的 mock 与真环境切换开关（上传回放目前用 fixtures + golden 忽略动态字段）

## 5) 非 HTTP 的“隐形系统”是否被纳入重写

- [x] 定时任务/脚本（P0 `SYSTEM_TASK` 接口清单）：`docs/rewrite/system-tasks.tsv`
- [ ] 异步任务/队列（若存在）替代方案与幂等策略
- [ ] 日志/审计/对账落地（支付/核销/退款必做）

## 6) 路由与兼容边界是否明确

- [x] `.html` 后缀兼容策略已写入重写文档
- [x] 统一网关/Nginx 规则样例（Header 透传、上传体积、`.html` 兼容）：`infra/nginx/xfq-rewrite.conf`
- [ ] 统一错误码与 envelope（尤其小程序登录态相关 110–115）

## 结论（经验口径）

目前你们“范围盘点 + P0 契约补齐”已经做了大半，但真正决定重写成败的两件事是：

1. **golden case/回放体系**（把接口行为锁死，可自动验收）
2. **清库后的可重复初始化**（schema + seed + 最小数据集）

把这两件事补齐后，再开写 Python 服务骨架，会明显减少返工与线上对不齐的概率。
