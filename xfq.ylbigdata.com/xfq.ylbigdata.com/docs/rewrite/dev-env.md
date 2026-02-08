# 本地开发环境（建议：MySQL + Redis）

目的：在“允许停机 + 允许清库”的前提下，把开发/回放/验收环境一键化。

## 1) 启动数据库

在仓库根目录执行：

```bash
docker compose -f docker-compose.rewrite.yml up -d mysql redis
```

也可以用 Makefile 快捷命令：

```bash
make db-up
```

如果你们没有可用的旧系统，可直接启动“legacy PHP（当前仓库代码）”用于录制 baseline：

```bash
make legacy-up
```

如果你的 docker 环境缺少/损坏 `buildx` 插件导致构建失败，可临时用经典 builder：

```bash
DOCKER_BUILDKIT=0 docker compose -f docker-compose.rewrite.yml up -d --build legacy-php
```

legacy 服务默认地址：

- `http://127.0.0.1:18080`

Python 重写服务（stub from golden）默认地址：

- `http://127.0.0.1:28080`

默认连接信息：

- MySQL：`127.0.0.1:13306`
  - db：`xfq_v2`
  - user：`xfq`
  - pass：`xfq`
- Redis：`127.0.0.1:16379`

初始化内容：

- schema：来自 `DDL_xfq_v2.sql`
- seed：
  - `xfq.ylbigdata.com/xfq.ylbigdata.com/infra/mysql/02-seed-minimal.sql`（避免空库直接报错）
  - `xfq.ylbigdata.com/xfq.ylbigdata.com/infra/mysql/03-seed-dev-auth.sql`（本地回放用 dev 鉴权数据）

可选：加载“最小业务数据集”（用于成功路径用例/回放）：

```bash
make seed-business
```

> 注意：加载后部分接口输出会变化，建议同步 `--record` 更新 golden baseline。

## 1.1) 一键清库重置（推荐：让回放可重复）

涉及“下单/支付/短信”等写库接口时，数据库状态会变化；为了让 golden 回放可重复，建议每次回放前都把库重置到可控状态：

```bash
make db-reset-minimal
```

如果要跑“成功路径”用例（含 ticket/coupon 等最小业务数据）：

```bash
make db-reset-success
```

## 2) Golden case 工作流

先补齐/生成 case：

```bash
cd xfq.ylbigdata.com/xfq.ylbigdata.com
python3 scripts/generate_miniapp_golden_cases.py
python3 scripts/generate_p0_golden_stubs.py
python3 scripts/check_golden_coverage.py --tier p0
```

或在仓库根目录用 Makefile：

```bash
make golden-miniapp
make golden-p0-stubs
make golden-p0-check
```

如果没有线上旧系统，建议直接对 `legacy-php` 录制 baseline（只用于锁定接口输出，不代表生产数据）：

```bash
make legacy-record-p0
```

### 成功路径用例（独立 suite）

为了避免“成功路径写库”影响日常回放，成功路径用例单独放在 `docs/rewrite/golden/cases/p0_success/`。

录制（legacy）：

```bash
make db-reset-success
make legacy-record-p0-success
```

验证（python rewrite）：

```bash
make db-reset-success
make py-check-p0-success
```

录制旧系统 baseline（示例）：

```bash
set -a
source .env.golden
set +a

python3 scripts/run_golden_cases.py \
  --cases-dir docs/rewrite/golden/cases/p0/miniapp \
  --base-url http://LEGACY_HOST \
  --record
```

验证新系统（示例）：

```bash
python3 scripts/run_golden_cases.py \
  --cases-dir docs/rewrite/golden/cases/p0/miniapp \
  --base-url http://NEW_HOST
```
