# P0 Success Golden Cases（会写库）

本目录用于存放“成功路径”用例（happy path）。这些用例通常会写库（如下单、发短信、实名、上传），因此建议每次回放前都先把数据库重置到可控状态：

```bash
make db-reset-success
```

录制 legacy baseline：

```bash
make legacy-up
make db-reset-success
make legacy-record-p0-success
```

验证 Python rewrite：

```bash
make py-up
make db-reset-success
make py-check-p0-success
```

说明：`auth_identity` 成功用例使用专用用户 `id=2`（由 `05-seed-success.sql` 初始化），避免与主用户认证状态冲突。
