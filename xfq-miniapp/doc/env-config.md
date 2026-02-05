# 多环境配置与域名管理（约定）

目标：保证开发/测试/生产环境可控且可复现，避免“域名白名单/配置来源混乱”导致联调与上线失败。

---

## 1. 环境定义
微信小程序运行环境：
- `develop`：开发版（开发者工具/开发者）
- `trial`：体验版（测试/产品验收）
- `release`：线上正式版

建议的后端环境：
- `dev`：后端开发环境
- `test`：后端测试环境
- `prod`：后端生产环境

> 需要确认：小程序的 `develop/trial/release` 分别对应哪个后端环境（见 `env-matrix.csv`）。

---

## 2. 配置来源（必须唯一）
建议采用“代码内配置 + 运行时选择”的方式：
- 在 `config/` 下维护 `dev/test/prod` 三份配置（baseURL、静态资源域名、埋点上报地址等）
- 运行时通过 `wx.getAccountInfoSync().miniProgram.envVersion` 选择 `develop/trial/release` 对应配置

本次原生工程（`xfq-miniapp/mp-native`）的约定：
- 默认 `baseUrl` **为空**（避免误连生产环境）
- 本地联调时通过 `xfq-miniapp/mp-native/miniprogram/config/local.js` 覆盖（该文件已在 `xfq-miniapp/mp-native/.gitignore` 忽略）
- 参考模板：`xfq-miniapp/mp-native/miniprogram/config/local.example.js`

严禁：
- 同一配置散落在多个文件、靠口头约定切换
- 通过手工改代码切环境后忘记改回

---

## 3. 微信域名白名单（上线阻断项）
需要整理并落表：
- request 合法域名
- uploadFile 合法域名
- downloadFile 合法域名
- websocket 合法域名（如有）

建议在 M0 就确定并由专人维护，变更必须走评审。

---

## 4. 配置矩阵（模板）
- `xfq-miniapp/doc/env-matrix.csv`
