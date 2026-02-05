# mp-native（微信小程序原生重构）

本目录为“消费券小程序”的微信原生重构工程（与旧 `uni-app` 工程并行开发）。

## 打开方式
用微信开发者工具打开 `xfq-miniapp/mp-native`（根目录包含 `project.config.json`）。

## 关键说明
- 运行环境与域名/开关等配置约定见：`xfq-miniapp/doc/env-config.md`
- 迁移计划与验收标准见：`xfq-miniapp/doc/native-refactor-plan.md`、`xfq-miniapp/doc/page-dod-checklist.md`
- 如需切换 AppID：修改 `xfq-miniapp/mp-native/project.config.json` 的 `appid`

## 本地配置（baseUrl）
默认 `baseUrl` 为空；本地联调请新建 `xfq-miniapp/mp-native/miniprogram/config/local.js`（参考 `local.example.js`）。
