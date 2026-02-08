-- Dev-only auth seeds for local legacy replay + golden baseline recording.
-- Token format must contain 3 segments separated by '.' (JWT-like) to pass middleware checks.
--
-- Suggested env values (see `.env.golden.example`):
--   API_TOKEN=dev.dev.dev
--   API_USERID=1
--   WINDOW_TOKEN=dev.dev.dev
--   WINDOW_UUID=dev-window-uuid
--   SELFSERVICE_TOKEN=dev.dev.dev
--   SELFSERVICE_NO=dev-selfservice-no

-- api（小程序）: tp_users.id + signpass=md5(token)
INSERT INTO `tp_users` (`id`, `mobile`, `create_time`, `status`, `signpass`, `expiry_time`)
VALUES (1, '13800000001', UNIX_TIMESTAMP(), 1, MD5('dev.dev.dev'), 4102444800)
ON DUPLICATE KEY UPDATE
  `mobile` = VALUES(`mobile`),
  `status` = VALUES(`status`),
  `signpass` = VALUES(`signpass`),
  `expiry_time` = VALUES(`expiry_time`);

-- window（窗口/售票员）: tp_ticket_user.uuid + signpass=md5(token.uuid)
INSERT INTO `tp_ticket_user` (`uuid`, `username`, `name`, `mobile`, `loginnum`, `err_num`, `signpass`, `expiry_time`)
VALUES ('dev-window-uuid', 'devwindow', 'Dev Window', '13800000002', 0, 0, MD5(CONCAT('dev.dev.dev', 'dev-window-uuid')), 4102444800)
ON DUPLICATE KEY UPDATE
  `username` = VALUES(`username`),
  `name` = VALUES(`name`),
  `mobile` = VALUES(`mobile`),
  `signpass` = VALUES(`signpass`),
  `expiry_time` = VALUES(`expiry_time`);

-- selfservice（自助机/商户）: tp_seller.no + signpass=md5(token.no)
INSERT INTO `tp_seller` (`no`, `nickname`, `status`, `last_login_ip`, `signpass`, `expiry_time`)
VALUES ('dev-selfservice-no', 'Dev Seller', 1, '127.0.0.1', MD5(CONCAT('dev.dev.dev', 'dev-selfservice-no')), 4102444800)
ON DUPLICATE KEY UPDATE
  `nickname` = VALUES(`nickname`),
  `status` = VALUES(`status`),
  `last_login_ip` = VALUES(`last_login_ip`),
  `signpass` = VALUES(`signpass`),
  `expiry_time` = VALUES(`expiry_time`);

