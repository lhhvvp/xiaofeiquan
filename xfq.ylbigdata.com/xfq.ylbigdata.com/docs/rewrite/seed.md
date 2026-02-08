# 清库后的初始化（schema + seed）准备清单

你们允许清库/停机，这会极大降低“历史数据迁移”的复杂度，但也引入一个新的硬要求：**所有依赖配置/基础数据的接口必须在空库下可一键初始化并跑通**。

## 你们需要准备什么

- 数据库选型与方言：MySQL / PostgreSQL（二选一先落地）
- 一键初始化流程：
  - schema（建表/索引/约束）
  - seed（基础配置/账号/支付参数等）
  - 可重复执行（幂等）
- “清库后仍能跑”的最小业务数据集（用于 golden case 回放）

## seed 的最小范围（建议先从这里开始）

以下属于“没有就会全站报错/关键接口不可用”的典型项：

- 系统基础配置（站点开关、签名盐、默认城市/门店、OSS/短信/实名/支付开关等）
- 支付配置（微信/支付宝/退款证书路径或密钥引用、回调域名、渠道开关）
- 管理端账号与权限（管理员、角色、菜单/权限映射）
- 业务字典/枚举（票种、订单状态映射、第三方渠道编码映射等）
- 第三方对接账号（携程 accountId、美团 partnerId 等：建议只存“引用名”，真实密钥走环境变量/密钥系统）

## 最小可用 seed（已落地）

仓库已提供 MySQL 的最小 seed 脚本（用于空库下避免 `find(1)` 类依赖直接报错）：

- `xfq.ylbigdata.com/xfq.ylbigdata.com/infra/mysql/02-seed-minimal.sql`

另外提供一份“本地回放专用”的鉴权 seed（用于把 golden case 跑起来，不代表生产账号/数据）：

- `xfq.ylbigdata.com/xfq.ylbigdata.com/infra/mysql/03-seed-dev-auth.sql`
  - 对应的 dev token/账号在 `xfq.ylbigdata.com/xfq.ylbigdata.com/.env.golden.example`

并提供一键起库（MySQL + Redis）的 compose：

- `docker-compose.rewrite.yml`

## 最小可用业务数据集（可选，建议用于“成功路径”用例）

仓库提供一份“可重复执行”的最小业务数据 seed（1 个景区商户 + 1 个票种分类 + 1 个票种 + 今日报价与库存）：

- `xfq.ylbigdata.com/xfq.ylbigdata.com/infra/mysql/04-seed-minimal-business.sql`

加载方式（需要本地已启动 `xfq-rewrite-mysql` 容器）：

```bash
make seed-business
```

> 注意：加载业务 seed 可能会改变部分接口输出（例如景区列表/min_price），建议在同一次变更里同步 `--record` 录制新的 golden baseline。

## 成功路径 seed（离线联调/回放，推荐）

如果你们希望在“没有生产数据/没有第三方真实密钥”的情况下把关键链路跑通（支付/短信/实名/票务浏览/下单），建议使用 success seed：

- `xfq.ylbigdata.com/xfq.ylbigdata.com/infra/mysql/05-seed-success.sql`

该 seed 只用于本地/测试回放，特点是：

- 覆盖支付/短信/实名等“外部依赖”的本地 mock 开关所需配置（不包含真实密钥）
- 提供稳定的业务数据（例如 seller=281 的票务数据、消费券 issue）

配套的一键“清库重置”Make 目标：

```bash
make db-reset-minimal   # schema + minimal + dev-auth
make db-reset-success   # 在 minimal 基础上额外加载 05-success
```

## 从代码里静态扫描“硬编码依赖”

旧代码里常见 `Model::find(1)` / `Db::name('xxx')->find(1)` 这种写法，代表“空库下必须存在某些固定 id 行”，否则接口直接报错。

可先用脚本扫描出一份候选清单：

```bash
python3 scripts/scan_seed_dependencies.py
```

会生成：

- `docs/rewrite/seed-dependencies.tsv`

然后把这些条目逐一归类为：

- 需要 seed（固定行必须存在）
- 应改为配置（不应绑死 id=1）
- 应由接口创建（运行时自动补齐）

> 注意：静态扫描只能给出“疑似点”，最终仍以 golden case 回放为准。

## 当前扫描结果（已生成）

本仓库已生成一版 `docs/rewrite/seed-dependencies.tsv`（可用作 seed 排查起点）；后续如果 PHP 代码有变动，建议重新跑一次脚本刷新。
