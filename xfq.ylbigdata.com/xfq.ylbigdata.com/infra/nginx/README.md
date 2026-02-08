# Nginx 网关样例（重写期：全部流量转发到 Python）

目标：在“长期停机重写”场景下，最终上线时用 Nginx 将原域名/路径 **原样转发** 到 Python 服务，并保证 OTA 验签所需 Header 不被吞掉。

文件：

- `infra/nginx/xfq-rewrite.conf`：示例 vhost（反向代理到 `rewrite-py`）

注意：

- OTA（美团 BA-sign）验签依赖 `Authorization` / `Date` / `REQUEST_URI`；请确保网关层不改写 `request_uri`，并明确转发 `Authorization/Date/PartnerId`。
- 兼容 `.html`：Python 侧已支持 `/{...}.html` 与无后缀两种形式；网关无需 rewrite。

