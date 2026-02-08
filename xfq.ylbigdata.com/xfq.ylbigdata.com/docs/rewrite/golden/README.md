# Golden Case（契约验收 / 流量回放）说明

目标：把“接口契约”落到可执行的验收标准，确保 **PHP → Python 重写后所有对外接口保持一致**（URL/方法/参数/Header/返回体/错误码/副作用）。

> 说明：本项目允许停机/清库，因此更推荐 “先把接口行为锁死（golden）→ 再实现 → 最后一次性切换” 的路线。

## 目录结构约定

- `docs/rewrite/golden/cases/`：golden case 文件（JSON）
  - `docs/rewrite/golden/cases/p0/`：P0（小程序/第三方）优先
- `docs/rewrite/golden/fixtures/`：回放用例用到的附件（如上传文件）

## Case 文件格式（最小集合）

每个 case 是一个 `*.json` 文件，核心字段：

- `id`：唯一标识（建议：`p0-<app>-<controller>-<action>-001`）
- `request`：
  - `method`：`GET/POST/...`
  - `path`：例如 `/api/user/smsVerification`（也可以写 `.html` 变体）
  - `query`：可选，支持 dict / 二元组数组 / 原始字符串
  - `headers`：可选
  - `body`：可选
    - `type`：`none` / `json` / `form` / `raw` / `multipart`
- `response`：用于保存“基线响应”（由旧系统录制或人工填写）
- `compare`：比较策略（可选）
  - `mode`：`json` / `raw`
  - `ignore_json_keys`：递归忽略字段（常用：`["time"]`）
  - `ignore_json_pointers`：精确忽略（JSON Pointer，例如 `["/data/traceId"]`）

模板见：`docs/rewrite/golden/case-template.json`

## 避免把密钥写进仓库（推荐）

case 里的字符串支持环境变量占位符：`${VAR_NAME}`。

例如：

- `Token: "${API_TOKEN}"`
- `Userid: "${API_USERID}"`

运行回放时从环境变量读取，避免把 token/签名等敏感值写入文件。

建议在后端根目录使用 `.env.golden` 管理这些环境变量（示例见：`.env.golden.example`）。

## 同一路径多用例的“基线选择”

某些接口会同时存在“失败用例（不写库）”与“成功用例（会写库）”，甚至在不同 suite（`p0` / `p0_success`）中对同一 `method+path` 录制了不同 baseline。

为避免 stub server 只能按 `(method, path)` 取 1 份响应导致冲突：

- `scripts/run_golden_cases.py` 会自动在请求中附加 `X-Golden-Case-Id: <case.id>`。
- `rewrite-py` 的 stub 模式会优先按 `X-Golden-Case-Id` 返回对应 baseline。

该 Header 只用于本地回放/验收，不属于对外契约；线上实现不应依赖它。

## 用法

1) 覆盖率检查（每个接口至少 1 个 case）

```bash
python3 scripts/check_golden_coverage.py --tier p0
```

可选强制 `.html` 变体也要覆盖：

```bash
python3 scripts/check_golden_coverage.py --tier p0 --require-html
```

2) 生成小程序（mp-native）case 骨架（建议作为第一批）

```bash
python3 scripts/extract_miniapp_api_usage.py
python3 scripts/generate_miniapp_golden_cases.py
python3 scripts/check_golden_miniapp_coverage.py
```

3) 生成 P0 全量 stub（把覆盖率补到 193/193）

```bash
python3 scripts/generate_p0_golden_stubs.py
python3 scripts/check_golden_coverage.py --tier p0
```

> 提示：stub 用于“先占位 + 先录制错误响应/基础行为”；高风险接口（支付回调/OTA/上传等）建议再单独补真实用例与 fixtures。

4) 录制旧系统基线（把 response 写回 case 文件）

如果你们没有可访问的旧系统，可先起一个 **legacy PHP（当前仓库代码）** 作为参考实现（在仓库根目录执行）：

```bash
make legacy-up
make legacy-record-p0
```

legacy 默认地址为 `http://127.0.0.1:18080`（见 `docs/rewrite/dev-env.md`）。

```bash
python3 scripts/run_golden_cases.py \
  --cases-dir docs/rewrite/golden/cases/p0 \
  --base-url http://LEGACY_HOST \
  --record
```

5) 验证新系统（与已录制基线对比）

```bash
python3 scripts/run_golden_cases.py \
  --cases-dir docs/rewrite/golden/cases/p0 \
  --base-url http://NEW_HOST
```

6) 直接对比两套系统（不依赖本地 baseline）

```bash
python3 scripts/run_golden_cases.py \
  --cases-dir docs/rewrite/golden/cases/p0 \
  --base-url http://NEW_HOST \
  --diff-against http://LEGACY_HOST
```

> 说明：脚本默认不使用 `HTTP_PROXY/HTTPS_PROXY`（避免代理导致回放不稳定）；如确需走代理，添加 `--use-proxy-env`。
