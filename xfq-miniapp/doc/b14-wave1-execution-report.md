# B14 Wave 1 执行报告（自动化阶段）

执行时间：`2026-02-09`
执行范围：`Wave 1（P0 入口与主链路）`

---

## 1. 页面范围

- `pages/index/index`
- `pages/merchant/merchant`
- `pages/tickets/tickets`
- `pages/user/user`
- `pages/user/login/login`
- `pages/getopenid/getopenid`
- `pages/tickets/info`
- `pages/getopenid/travelorderinfo`

---

## 2. 执行命令（真实 API）

```bash
API_TOKEN=dev.dev.dev API_USERID=1 python3 .../run_golden_cases.py \
  --cases-dir .../p0/miniapp \
  --case .../p0-miniapp-post-api-index-system-001.json \
  --case .../p0-miniapp-post-api-seller-cate-001.json \
  --case .../p0-miniapp-post-api-seller-list-001.json \
  --case .../p0-miniapp-post-api-seller-detail-001.json \
  --case .../p0-miniapp-get-api-ticket-getScenicList-001.json \
  --case .../p0-miniapp-get-api-ticket-getTicketList-001.json \
  --case .../p0-miniapp-get-api-ticket-getCommentList-001.json \
  --case .../p0-miniapp-post-api-user-index-001.json \
  --base-url http://127.0.0.1:28080
```

```bash
API_TOKEN=dev.dev.dev API_USERID=1 python3 .../run_golden_cases.py \
  --cases-dir .../p0/stubs/api \
  --case .../p0-stub-api-post-api-index-miniwxlogin-001.json \
  --case .../p0-stub-api-post-api-seller-bindCheckOpenid-001.json \
  --case .../p0-stub-api-post-api-ticket-getTravelOrderDetail-001.json \
  --case .../p0-stub-api-post-api-ticket-travelOrderPay-001.json \
  --base-url http://127.0.0.1:28080
```

```bash
API_TOKEN=dev.dev.dev API_USERID=1 python3 .../run_golden_cases.py \
  --cases-dir .../p0_success \
  --case .../p0-success-post-api-index-system-001.json \
  --case .../p0-success-post-api-seller-cate-001.json \
  --case .../p0-success-post-api-seller-list-001.json \
  --case .../p0-success-post-api-seller-search-001.json \
  --case .../p0-success-get-api-ticket-getScenicList-001.json \
  --case .../p0-success-get-api-ticket-getTicketList-001.json \
  --base-url http://127.0.0.1:28080
```

---

## 3. 自动化结果

- `p0/miniapp`：`7/8` 通过，`1` 条失败
  - 失败用例：`p0-miniapp-get-api-ticket-getScenicList-001`
  - 差异类型：数据面差异（`comment_rate: 0 -> 0.0`，`min_price: null -> "100.00"`）
- `p0/stubs/api`（登录 + travel 关键链路）：`4/4` 通过
- `p0_success`（Wave1 相关）：`6/6` 通过

判定：
- Wave1 后端接口链路整体可用；
- 当前自动化阻断仅剩 `getScenicList` baseline 与数据类型/种子差异。

---

## 4. 未完成项（需人工）

- 微信开发者工具人工回归（UI/交互/路由）尚未执行，`Wave1` 页面状态仍保持 `ready_for_qa`。
- 人工执行请按：`xfq-miniapp/doc/b14-next-manual-regression.md` 的 Wave1 顺序。

---

## 5. 建议处理

1. 先确认 `getScenicList` 的兼容策略：
   - 保持现状并更新 baseline；或
   - 在后端做类型归一（保持 legacy 风格）。
2. 先跑完 Wave1 人工回归并留证，再批量将 8 个页面从 `ready_for_qa` 更新为 `done`。
