# 本地联跑启动手册（Python 后端 + 原生小程序）

更新时间：`2026-02-09`

适用目录：
- 后端：`xfq.ylbigdata.com/xfq.ylbigdata.com/rewrite_py`
- 前端：`xfq-miniapp/mp-native`

---

## 1. 前置条件

- Docker 与 Docker Compose 可用。
- Node.js 可用（用于 `npm run check`、`npm run mock:smoke`）。
- 微信开发者工具可打开 `xfq-miniapp/mp-native`。

---

## 2. 启动后端（推荐流程）

在仓库根目录执行：

```bash
make db-up
make db-reset-minimal
make py-up
```

说明：
- `db-up`：启动 `mysql + redis`
- `db-reset-minimal`：重置库并加载最小种子与开发鉴权种子
- `py-up`：启动 Python 重写服务（端口 `28080`）

健康检查：

```bash
curl -sS -i -X POST http://127.0.0.1:28080/api/index/system \
  -H 'Token: dev.dev.dev' -H 'Userid: 1'
```

预期：`HTTP/1.1 200 OK` 且 JSON `code=0`。

---

## 3. 小程序配置（真实 API）

文件：`xfq-miniapp/mp-native/miniprogram/config/local.js`

```js
module.exports = {
  baseUrl: 'http://127.0.0.1:28080/api',
  mock: false,
  mockPayment: true,
}
```

说明：
- `mock=false`：请求真实 Python 后端。
- `mockPayment=true`：开发环境跳过真实微信支付调起，便于联调。

---

## 4. 小程序配置（全 Mock）

当后端不可用时，可切到全 mock：

```js
module.exports = {
  mock: true,
  mockPayment: true,
}
```

说明：
- `mock=true` 时框架会走本地 mock 数据。
- 未配置 `baseUrl` 也可运行（内部会回退为 `mock`）。

---

## 5. 前端自检与冒烟

```bash
cd xfq-miniapp/mp-native
npm run check
npm run mock:smoke
```

用途：
- `check`：路由文件存在性 + JS/JSON 语法检查
- `mock:smoke`：主要链路 mock 冒烟

---

## 6. 微信开发者工具联跑步骤

1. 打开项目：`xfq-miniapp/mp-native`
2. 确认 `miniprogram/config/local.js` 已生效
3. 编译后先验证入口链路：`index -> login/getopenid -> user`
4. 再验证交易链路：`tickets -> info -> order/pay(模拟) -> order detail`
5. 执行 B14 人工回归：`xfq-miniapp/doc/b14-next-manual-regression.md`

---

## 7. 常见问题

1. 报错 `baseUrl 未配置`  
   处理：确认 `miniprogram/config/local.js` 存在，且 `module.exports` 返回对象。

2. 报错 `module 'config.js' is not defined, require args is '../config'`  
   处理：检查 `miniprogram/config/index.js` 同级是否存在 `local.js`，并重启开发者工具清缓存编译。

3. 小程序访问本地接口失败  
   处理：确认 `28080` 端口已监听、开发者工具未拦截域名、接口路径为 `/api/*`。

---

## 8. 停止环境

```bash
make py-down
make db-down
```
