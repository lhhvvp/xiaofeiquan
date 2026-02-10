# B14 Wave1 人工回归记录（填写模板）

模板版本：`v1`  
适用范围：`Wave1（8 个页面）`  
填写时间：  
执行人：  
环境：`dev/trial`  
后端地址：`http://127.0.0.1:28080/api`

---

## 1. 执行前检查

- [ ] `make db-up && make py-up` 已执行，后端接口可访问
- [ ] `miniprogram/config/local.js` 配置为真实 API（`mock=false`）
- [ ] 微信开发者工具缓存已清理并重新编译
- [ ] 已开启 Network 面板并准备导出请求日志

---

## 2. 页面回归记录（逐页填写）

| 序号 | 页面 | 核心检查点 | 结果(pass/fail) | 证据(截图/录屏路径) | 接口日志路径 | 缺陷ID |
|---|---|---|---|---|---|---|
| 1 | `pages/index/index` | 首页渲染、公告区、入口跳转 |  |  |  |  |
| 2 | `pages/merchant/merchant` | 分类筛选、列表分页、详情跳转 |  |  |  |  |
| 3 | `pages/tickets/tickets` | 景区筛选、列表分页、详情跳转 |  |  |  |  |
| 4 | `pages/user/user` | 登录态展示、功能入口、订单区跳转 |  |  |  |  |
| 5 | `pages/user/login/login` | 登录触发、授权弹层、回跳 |  |  |  |  |
| 6 | `pages/getopenid/getopenid` | `wx.login`、openid 绑定流程 |  |  |  |  |
| 7 | `pages/tickets/info` | 景区详情、票种展示、评论弹层 |  |  |  |  |
| 8 | `pages/getopenid/travelorderinfo` | scene 解析、订单详情、支付动作 |  |  |  |  |

---

## 3. 链路回归（跨页）

| 链路 | 步骤 | 结果(pass/fail) | 证据 | 缺陷ID |
|---|---|---|---|---|
| 登录链路 | `user -> login -> getopenid -> user` |  |  |  |
| 商家链路 | `merchant -> merchant/info` |  |  |  |
| 门票链路 | `tickets -> tickets/info` |  |  |  |
| travel链路 | `扫码/scene -> travelorderinfo -> 支付` |  |  |  |

---

## 4. Wave1 结论

- Wave1 总结论：`pass / fail`
- 未通过页面：
- 主要问题：
- 建议动作：

---

## 5. 回填动作（执行后）

- [ ] 将通过页面在 `xfq-miniapp/doc/migration-tracker.csv` 状态改为 `done`
- [ ] 将失败项录入 `xfq-miniapp/doc/b14-defect-register.csv`
- [ ] 更新 `xfq-miniapp/doc/b14-readiness-report.md` 的 Wave1 状态
