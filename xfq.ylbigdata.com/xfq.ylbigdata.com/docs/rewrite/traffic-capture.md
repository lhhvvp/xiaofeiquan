# 无法获取线上日志时：维护模式流量捕获（可选）

当你们拿不到历史 Nginx/网关日志（7–14 天）时，仍可以开始重写；但为了降低“接口遗漏/隐式路径遗漏”的风险，建议在域名切换前/停机期间短暂部署一个 **维护模式流量捕获** 服务：

- 所有请求直接返回“维护中”，同时把请求的 **method/path/query/header 名称/Body 哈希** 记录到本地 JSONL 文件。
- 该方式不会记录敏感 Header 值（只记录 header 名称 + 少量安全字段），也不保存业务 JSON 的值（只保存顶层 key），用于补齐“真实流量面”。

## 启用方式（rewrite-py）

`rewrite-py` 支持通过环境变量开启捕获：

- `MAINTENANCE_CAPTURE=1`：开启捕获并短路所有请求
- `MAINTENANCE_CAPTURE_LOG=/path/to/xfq-maintenance-capture.jsonl`：日志输出路径（JSON Lines）

示例（docker compose 覆盖）：

```bash
MAINTENANCE_CAPTURE=1 \
MAINTENANCE_CAPTURE_LOG=/tmp/xfq-maintenance-capture.jsonl \
docker compose -f docker-compose.rewrite.yml up -d --build rewrite-py
```

日志文件默认写在容器内；如要持久化到宿主机，请自行挂载 volume。

## 输出格式（JSONL）

每行一条事件（字段可能随版本增加）：

- `ts`：unix 时间戳
- `method`：HTTP 方法
- `uri`：原始请求 URI（含 `.html` 与 query）
- `headers_present`：出现过的 header 名称（小写）
- `safe_headers`：少量安全 header（如 `content-type`、`user-agent`）
- `body_len` / `body_sha256`：用于去重与统计
- `json_top_keys`：若为 JSON，记录顶层 key（不记录值）

## 如何用它补齐接口范围

1. 捕获一段时间（例如 24–72 小时），得到 JSONL。
2. 从 JSONL 统计“出现过的 method+path”，与 `docs/rewrite/p0-interfaces.tsv` 做差集：
   - 差集即“静态盘点遗漏的真实调用面”，需要补契约 + golden 用例 + 实现。

