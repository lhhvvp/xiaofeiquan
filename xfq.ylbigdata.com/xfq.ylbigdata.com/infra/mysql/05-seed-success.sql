-- Success-path seed dataset (dev-only; safe to clear/redo).
-- Goal: enable offline "happy path" golden cases without production data:
--   - WeChat pay params (mock mode)
--   - SMS verification (mock mode)
--   - Identity verification (mock mode)
--   - Ticket browse (scenic -> ticket list -> price)
--   - Coupon purchase (pay/submit) prerequisites
--
-- NOTE:
-- - This file is expected to be used together with:
--     02-seed-minimal.sql
--     03-seed-dev-auth.sql
-- - Do NOT put real credentials here.

SET @seed_time := 1700000000;

-- --- System config ---
UPDATE `tp_system`
SET
  `upload_driver` = 1,
  `app_code` = 'mock-app-code',
  `update_time` = @seed_time
WHERE `id` = 1;

-- --- WeChat payment config (dev dummy) ---
UPDATE `tp_base_payment`
SET
  `wechat_appid` = 'wx-dev-appid',
  `wechat_mch_id` = '1900000001',
  `wechat_mch_key` = 'dev-wechat-key',
  `update_time` = @seed_time
WHERE `id` = 1;

-- --- Ensure user#1 has openid/uuid for pay flows ---
UPDATE `tp_users`
SET
  `uuid` = COALESCE(NULLIF(`uuid`, ''), 'dev-user-uuid-1'),
  `openid` = COALESCE(`openid`, 'dev-openid-1'),
  `nickname` = COALESCE(NULLIF(`nickname`, ''), 'Dev User'),
  `update_time` = @seed_time
WHERE `id` = 1;

-- --- Dedicated user#2 for auth_identity success replay ---
INSERT INTO `tp_users` (
  `id`, `mobile`, `create_time`, `status`, `signpass`, `expiry_time`,
  `auth_status`, `openid`, `uuid`, `name`, `idcard`, `update_time`
) VALUES (
  2, '13800000004', @seed_time, 1, MD5('dev.dev.dev'), 4102444800,
  0, 'dev-openid-2', 'dev-user-uuid-2', '', '', @seed_time
) ON DUPLICATE KEY UPDATE
  `mobile` = VALUES(`mobile`),
  `status` = VALUES(`status`),
  `signpass` = VALUES(`signpass`),
  `expiry_time` = VALUES(`expiry_time`),
  `auth_status` = VALUES(`auth_status`),
  `openid` = VALUES(`openid`),
  `uuid` = VALUES(`uuid`),
  `name` = VALUES(`name`),
  `idcard` = VALUES(`idcard`),
  `update_time` = @seed_time;

-- --- Ticket success seed (attach to existing dev seller id=281) ---
INSERT INTO `tp_ticket_category` (
  `id`, `seller_id`, `title`, `status`, `sort`, `create_time`, `update_time`
) VALUES (
  1, 281, 'Seed Category', 1, 50, @seed_time, @seed_time
) ON DUPLICATE KEY UPDATE
  `seller_id` = VALUES(`seller_id`),
  `title` = VALUES(`title`),
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`),
  `update_time` = @seed_time;

INSERT INTO `tp_ticket` (
  `id`, `seller_id`, `category_id`, `title`, `cover`,
  `quota`, `quota_order`, `rights_num`, `code`,
  `status`, `sort`, `create_time`, `update_time`
) VALUES (
  1, 281, 1, 'Seed Ticket', '',
  0, 99, 0, 'SEED_TICKET_1',
  1, 50, @seed_time, @seed_time
) ON DUPLICATE KEY UPDATE
  `seller_id` = VALUES(`seller_id`),
  `category_id` = VALUES(`category_id`),
  `title` = VALUES(`title`),
  `cover` = VALUES(`cover`),
  `quota` = VALUES(`quota`),
  `quota_order` = VALUES(`quota_order`),
  `rights_num` = VALUES(`rights_num`),
  `code` = VALUES(`code`),
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`),
  `update_time` = @seed_time;

INSERT INTO `tp_ticket_price` (
  `ticket_id`, `seller_id`, `date`,
  `online_price`, `casual_price`, `team_price`,
  `stock`, `total_stock`, `create_time`, `update_time`
) VALUES (
  1, 281, CURDATE(),
  100.00, 100.00, 100.00,
  100000, 100000, @seed_time, @seed_time
) ON DUPLICATE KEY UPDATE
  `seller_id` = VALUES(`seller_id`),
  `online_price` = VALUES(`online_price`),
  `casual_price` = VALUES(`casual_price`),
  `team_price` = VALUES(`team_price`),
  `stock` = VALUES(`stock`),
  `total_stock` = VALUES(`total_stock`),
  `update_time` = @seed_time;

-- --- Coupon issue seed (minimal fields for /api/pay/submit happy path) ---
INSERT INTO `tp_coupon_issue` (
  `id`, `uuno`, `cid`, `coupon_title`, `coupon_icon`,
  `limit_time`, `start_time`, `end_time`,
  `total_count`, `remain_count`, `status`, `is_del`,
  `coupon_price`, `is_threshold`, `use_min_price`,
  `is_permanent`, `coupon_time_start`, `coupon_time_end`,
  `class_id`, `type`, `receive_type`, `sort`,
  `create_time`, `update_time`,
  `sale_price`, `is_get`
) VALUES (
  1, 'SEED_COUPON_UUNO_1', 1, 'Seed Coupon', '',
  0, 0, 0,
  100000, 100000, 1, 0,
  10.00, 0, 0.00,
  1, 0, 0,
  1, 1, 1, 50,
  @seed_time, @seed_time,
  10.00, 1
) ON DUPLICATE KEY UPDATE
  `uuno` = VALUES(`uuno`),
  `cid` = VALUES(`cid`),
  `coupon_title` = VALUES(`coupon_title`),
  `coupon_icon` = VALUES(`coupon_icon`),
  `total_count` = VALUES(`total_count`),
  `remain_count` = VALUES(`remain_count`),
  `status` = VALUES(`status`),
  `is_del` = VALUES(`is_del`),
  `coupon_price` = VALUES(`coupon_price`),
  `sale_price` = VALUES(`sale_price`),
  `is_get` = VALUES(`is_get`),
  `update_time` = @seed_time;
