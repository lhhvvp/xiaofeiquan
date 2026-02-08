-- P0 golden quality batch#1 seed (dev-only).
-- Scope: make selected miniapp cases replay as structured 200 responses
-- instead of legacy framework 500 HTML pages.
--
-- Covered cases:
-- - p0-miniapp-get-api-appt-getDetail-001
-- - p0-miniapp-post-api-appt-cancelAppt-001
-- - p0-miniapp-get-api-ticket-getOrderDetail-001
-- - p0-miniapp-post-api-seller-detail-001
-- - p0-miniapp-post-api-user-index-001
-- - p0-miniapp-post-api-user-tour_coupon_group-001
--
-- NOTE:
-- - Apply on top of 02-seed-minimal.sql + 03-seed-dev-auth.sql.
-- - This dataset is intentionally optional and should not be merged into
--   default minimal reset to avoid changing unrelated baselines.

SET @seed_time := 1700000000;

-- user profile (for /api/user/index and auth-dependent flows)
UPDATE `tp_users`
SET
  `uuid` = 'dev-user-uuid-1',
  `openid` = 'dev-openid-1',
  `nickname` = 'Dev User',
  `name` = 'Dev User',
  `idcard` = '610827199001011234',
  `auth_status` = 1,
  `update_time` = @seed_time
WHERE `id` = 1;

-- scenic seller for /api/seller/detail and relation fields
INSERT INTO `tp_seller` (
  `id`, `status`, `nickname`, `last_login_ip`,
  `class_id`, `area`, `longitude`, `latitude`,
  `mobile`, `do_business_time`, `address`,
  `create_time`, `update_time`
) VALUES (
  1, 1, 'Seed Scenic', '127.0.0.1',
  2, 1, 109.700000, 38.300000,
  '13800000000', '09:00-18:00', 'Seed Address',
  @seed_time, @seed_time
) ON DUPLICATE KEY UPDATE
  `status` = VALUES(`status`),
  `nickname` = VALUES(`nickname`),
  `class_id` = VALUES(`class_id`),
  `area` = VALUES(`area`),
  `longitude` = VALUES(`longitude`),
  `latitude` = VALUES(`latitude`),
  `mobile` = VALUES(`mobile`),
  `do_business_time` = VALUES(`do_business_time`),
  `address` = VALUES(`address`),
  `update_time` = @seed_time;

-- appt record for /api/appt/getDetail and /api/appt/cancelAppt
INSERT INTO `tp_ticket_appt_log` (
  `id`, `code`, `seller_id`, `user_id`, `date`, `time_start`, `time_end`,
  `fullname`, `idcard`, `phone`, `number`, `status`,
  `lat`, `lng`, `address`, `ip`, `cancel_time`, `create_time`
) VALUES (
  1, 'SEED_APPT_LOG_000001', 1, 1, CURDATE(), 32400, 36000,
  'Dev User', '610827199001011234', '13800000001', 1, 0,
  0, 0, 'Seed Address', '127.0.0.1', 0, @seed_time
) ON DUPLICATE KEY UPDATE
  `seller_id` = VALUES(`seller_id`),
  `user_id` = VALUES(`user_id`),
  `date` = VALUES(`date`),
  `time_start` = VALUES(`time_start`),
  `time_end` = VALUES(`time_end`),
  `fullname` = VALUES(`fullname`),
  `idcard` = VALUES(`idcard`),
  `phone` = VALUES(`phone`),
  `number` = VALUES(`number`),
  `status` = 0,
  `cancel_time` = 0,
  `create_time` = @seed_time;

-- ticket order for /api/ticket/getOrderDetail
INSERT INTO `tp_ticket_order` (
  `id`, `openid`, `uuid`, `mch_id`,
  `trade_no`, `out_trade_no`,
  `channel`, `type`,
  `origin_price`, `amount_price`, `payment_terminal`,
  `order_status`, `refund_status`,
  `create_lat`, `create_lng`, `create_ip`,
  `create_time`, `update_time`
) VALUES (
  1, 'dev-openid-1', 'dev-user-uuid-1', 1,
  'SEED_ORDER_0001', 'SEED_OUT_0001',
  'online', 'miniapp',
  100.00, 100.00, 1,
  'paid', 'not_refunded',
  0, 0, '127.0.0.1',
  @seed_time, @seed_time
) ON DUPLICATE KEY UPDATE
  `openid` = VALUES(`openid`),
  `uuid` = VALUES(`uuid`),
  `mch_id` = VALUES(`mch_id`),
  `trade_no` = VALUES(`trade_no`),
  `out_trade_no` = VALUES(`out_trade_no`),
  `origin_price` = VALUES(`origin_price`),
  `amount_price` = VALUES(`amount_price`),
  `order_status` = 'paid',
  `refund_status` = 'not_refunded',
  `update_time` = @seed_time;

INSERT INTO `tp_ticket_order_detail` (
  `id`, `uuid`, `trade_no`, `out_trade_no`, `ticket_code`,
  `tourist_fullname`, `tourist_cert_type`, `tourist_cert_id`, `tourist_mobile`,
  `ticket_number`, `ticket_cate_id`, `ticket_id`, `ticket_title`, `ticket_date`,
  `ticket_cover`, `ticket_price`,
  `ticket_rights_num`, `writeoff_rights_num`,
  `explain_use`, `explain_buy`, `enter_time`,
  `refund_status`, `refund_progress`,
  `create_time`, `update_time`
) VALUES (
  1, 'dev-user-uuid-1', 'SEED_ORDER_0001', 'SEED_OUT_0001', 'SEED_TICKET_CODE_0001',
  'Seed Tourist', 1, '610827199001011234', '13800000001',
  1, 1, 1, 'Seed Ticket', CURDATE(),
  '', 100.00,
  0, 0,
  '', '', @seed_time,
  'not_refunded', 'init',
  @seed_time, @seed_time
) ON DUPLICATE KEY UPDATE
  `uuid` = VALUES(`uuid`),
  `trade_no` = VALUES(`trade_no`),
  `out_trade_no` = VALUES(`out_trade_no`),
  `tourist_fullname` = VALUES(`tourist_fullname`),
  `tourist_cert_id` = VALUES(`tourist_cert_id`),
  `tourist_mobile` = VALUES(`tourist_mobile`),
  `ticket_number` = VALUES(`ticket_number`),
  `ticket_title` = VALUES(`ticket_title`),
  `ticket_date` = VALUES(`ticket_date`),
  `ticket_price` = VALUES(`ticket_price`),
  `refund_status` = 'not_refunded',
  `refund_progress` = 'init',
  `update_time` = @seed_time;

-- coupon class/issue for relation hydration in user tour group detail
INSERT INTO `tp_coupon_class` (`id`, `status`, `title`, `create_time`, `update_time`)
VALUES (1, 1, 'Seed Coupon Class', @seed_time, @seed_time)
ON DUPLICATE KEY UPDATE
  `status` = 1,
  `title` = VALUES(`title`),
  `update_time` = @seed_time;

INSERT INTO `tp_coupon_issue` (
  `id`, `uuno`, `cid`, `coupon_title`, `coupon_icon`,
  `limit_time`, `start_time`, `end_time`,
  `total_count`, `remain_count`, `status`, `is_del`,
  `coupon_price`, `is_threshold`, `use_min_price`,
  `is_permanent`, `coupon_time_start`, `coupon_time_end`,
  `class_id`, `type`, `receive_type`, `sort`,
  `create_time`, `update_time`,
  `sale_price`, `is_get`, `tips`, `day`, `pid`,
  `is_rollback`, `rollback_num`, `coupon_type`, `provide_count`,
  `rollback_num_extend`, `use_type`, `receive_crowd`, `use_store`, `use_stroe_id`
) VALUES (
  1, 'SEED_COUPON_UUNO_1', 1, 'Seed Coupon', '',
  0, 0, 0,
  100000, 100000, 1, 0,
  10.00, 0, 0.00,
  1, 0, 0,
  1, 1, 1, 50,
  @seed_time, @seed_time,
  10.00, 1, '', 0, 0,
  0, 0, 1, 0,
  0, 2, 1, 1, '0'
) ON DUPLICATE KEY UPDATE
  `cid` = VALUES(`cid`),
  `coupon_title` = VALUES(`coupon_title`),
  `total_count` = VALUES(`total_count`),
  `remain_count` = VALUES(`remain_count`),
  `status` = 1,
  `is_del` = 0,
  `coupon_price` = VALUES(`coupon_price`),
  `sale_price` = VALUES(`sale_price`),
  `is_get` = 1,
  `coupon_type` = 1,
  `receive_crowd` = 1,
  `use_store` = 1,
  `use_stroe_id` = '0',
  `update_time` = @seed_time;

-- tour/coupon-group for /api/user/tour_coupon_group
INSERT INTO `tp_tour` (
  `id`, `create_time`, `update_time`,
  `status`, `name`, `no`, `term`, `numbers`,
  `planner`, `mobile`, `line_info`,
  `travel_id`, `spot_ids`, `mid`,
  `term_start`, `term_end`, `area_id`
) VALUES (
  1, @seed_time, @seed_time,
  3, 'Seed Tour', 'SEED_TOUR_0001', '2026-02', 1,
  'Seed Planner', '13800000000', '',
  0, '', 1,
  @seed_time, @seed_time + 86400, '6108'
) ON DUPLICATE KEY UPDATE
  `status` = 3,
  `mid` = 1,
  `update_time` = @seed_time;

INSERT INTO `tp_tour_coupon_group` (
  `id`, `create_time`, `update_time`, `receive_time`, `sort`, `status`,
  `tid`, `coupon_issue_id`, `is_receive`, `cid`,
  `enstr_salt`, `qrcode_url`, `code_time_create`, `code_time_expire`, `write_use`
) VALUES (
  1, @seed_time, @seed_time, @seed_time, 50, 0,
  1, 1, 1, 1,
  'seed-salt', 'seed-qrcode', @seed_time, @seed_time + 300, 0
) ON DUPLICATE KEY UPDATE
  `tid` = 1,
  `coupon_issue_id` = 1,
  `is_receive` = 1,
  `cid` = 1,
  `status` = 0,
  `qrcode_url` = 'seed-qrcode',
  `code_time_create` = @seed_time,
  `code_time_expire` = @seed_time + 300,
  `update_time` = @seed_time;
