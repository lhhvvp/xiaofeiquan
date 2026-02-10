# B14 就绪度报告（执行中）

报告时间：`2026-02-09 09:13:53 CST`（已追加高风险回归：`2026-02-09 09:25 CST`；Wave1 自动化：`2026-02-09 12:58 CST`）

---

## 1. 当前结论

- 已进入 `B14`，开发侧阻塞项已清零，可执行全量 QA 回归。
- 自动化基线通过，当前风险主要集中在“真实 API + 微信开发者工具人工回归”阶段。
- 当前状态不满足发布条件：`ready_for_qa` 仍有 `49` 条，尚未全部转 `done`。

---

## 2. 量化状态快照

来源：`xfq-miniapp/doc/migration-tracker.csv`

- `done=14`
- `ready_for_qa=49`
- `todo=0`
- `in_progress=0`
- `blocked=0`

`ready_for_qa` 分布：
- `user=17`
- `coupon=9`
- `groupcoupon=7`
- `tabbar=4`
- `content=3`
- `merchant=3`
- `auth=2`
- `tickets=2`
- `misc=2`

---

## 3. 自动化校验结果

执行命令：

```bash
cd xfq-miniapp/mp-native && npm run check
cd xfq-miniapp/mp-native && npm run mock:smoke
```

结果：
- `npm run check`：`OK: pages=62, js=93, json=69, warnings=0`
- `npm run mock:smoke`：`ALL OK (81 steps)`

---

## 4. 高风险 API 回归结果（真实 baseUrl）

执行命令（节选）：

```bash
python3 .../run_golden_cases.py --cases-dir .../p0/miniapp --base-url http://127.0.0.1:28080
make py-check-p0-success-dev
python3 .../run_golden_cases.py --case p0-stub-api-post-api-index-miniwxlogin-001.json ...
python3 .../run_golden_cases.py --case p0-stub-api-post-api-seller-bindCheckOpenid-001.json ...
python3 .../run_golden_cases.py --case p0-stub-api-post-api-ticket-getTravelOrderDetail-001.json ...
python3 .../run_golden_cases.py --case p0-stub-api-post-api-ticket-travelOrderPay-001.json ...
```

结果：
- `p0/miniapp`：`66/68` 通过；失败 `2` 条均为数据面差异（非服务异常）
  - `p0-miniapp-get-api-ticket-getScenicList-001`：`comment_rate` 类型与 `min_price` 值差异
  - `p0-miniapp-get-api-ticket-getTicketPirce-001`：返回库存数组与 baseline 空数组差异
- `p0_success`：`49/49` 全通过
- 认证与 travel 关键接口补跑：
  - `p0-stub-api-post-api-index-miniwxlogin-001`：OK
  - `p0-stub-api-post-api-seller-bindCheckOpenid-001`：OK
  - `p0-stub-api-post-api-ticket-getTravelOrderDetail-001`：OK
  - `p0-stub-api-post-api-ticket-travelOrderPay-001`：OK

判定：
- 高风险链路后端接口可用性已达发布前标准；
- 仍需 DevTools 侧人工确认 UI 与交互一致性后方可 `Go`。

---

## 5. B14 已落地产物

- 全量回归清单：`xfq-miniapp/doc/b14-full-regression-checklist.md`
- 下一轮人工回归单：`xfq-miniapp/doc/b14-next-manual-regression.md`
- Wave1 自动化报告：`xfq-miniapp/doc/b14-wave1-execution-report.md`
- Wave1 人工记录模板：`xfq-miniapp/doc/b14-wave1-manual-regression-log.md`
- 缺陷登记表：`xfq-miniapp/doc/b14-defect-register.csv`
- Go/No-Go 模板：`xfq-miniapp/doc/b14-go-nogo-template.md`
- 发布 Runbook：`xfq-miniapp/doc/release-runbook.md`
- 联跑启动手册：`xfq-miniapp/doc/fullstack-local-startup.md`

---

## 6. 剩余工作（完成 B14 必做）

0. 处理 Wave1 自动化唯一差异项：`p0-miniapp-get-api-ticket-getScenicList-001`（类型/种子口径确认）。  
1. 按 `b14-next-manual-regression.md`（Wave 顺序）执行；Wave1 结果填写到 `b14-wave1-manual-regression-log.md`。  
2. 每通过一个页面，及时将 `migration-tracker.csv` 对应条目改为 `done`。  
3. 缺陷统一登记到 `b14-defect-register.csv`，并清零（Blocker/Critical/High）后再走签字。  
4. 发布前冻结版本，执行一次最终 `check + mock:smoke + 真实链路冒烟`。

---

## 7. 需要你确认

- 是否按“真实 API 全回归”推进 B14 关口（而非仅 mock 通过）？
- Go/No-Go 评审签字人是否确定为：研发负责人 / QA 负责人 / 业务负责人 / 发布负责人？
- 是否确认发布窗口与回滚负责人（用于一次切换执行）？
