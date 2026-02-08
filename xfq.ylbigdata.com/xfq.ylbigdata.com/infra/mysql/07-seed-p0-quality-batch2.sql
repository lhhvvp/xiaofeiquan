-- P0 golden quality batch#2 seed (dev-only).
-- Scope: make the last two miniapp 500 cases replay as structured JSON
-- (deterministic error path), without touching default minimal baseline.
--
-- Covered cases:
-- - p0-miniapp-post-api-coupon-receive-001
-- - p0-miniapp-post-api-user-writeoff_tour-001
--
-- NOTE:
-- - Apply on top of 02-seed-minimal.sql + 03-seed-dev-auth.sql.
-- - This dataset is optional and isolated for quality batch#2.

SET @seed_time := 1700000000;

-- ensure api dev user can pass auth-dependent checks
UPDATE `tp_users`
SET
  `uuid` = 'dev-user-uuid-1',
  `nickname` = 'Dev User',
  `name` = 'Dev User',
  `idcard` = '610827199001011234',
  `salt` = 'dev-user-salt',
  `mobile` = '13800000001',
  `auth_status` = 1,
  `status` = 1,
  `update_time` = @seed_time
WHERE `id` = 1;

UPDATE `tp_system`
SET
  `is_safe_ip` = '',
  `is_safe_area` = '',
  `is_effective_start` = 0,
  `is_effective_end` = 0,
  `message_send_mail` = 0,
  `is_interval_time` = 0,
  `is_random_number` = 0,
  `is_random_number_extend` = 0
WHERE `id` = 1;

-- seller for /api/user/writeoff_tour
INSERT INTO `tp_seller` (
  `id`, `status`, `nickname`, `last_login_ip`,
  `class_id`, `area`, `longitude`, `latitude`,
  `mobile`, `do_business_time`, `address`,
  `create_time`, `update_time`
) VALUES (
  2001, 1, 'Seed Scenic Q2', '127.0.0.1',
  2, 1, 109.700000, 38.300000,
  '13800002001', '09:00-18:00', 'Seed Q2 Address',
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

-- coupon entities for /api/coupon/receive
INSERT INTO `tp_coupon_class` (`id`, `status`, `title`, `create_time`, `update_time`)
VALUES (201, 1, 'Seed Coupon Class Q2', @seed_time, @seed_time)
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
  `class_id`, `type`, `receive_type`, `sort`, `is_limit_total`, `limit_total`,
  `create_time`, `update_time`,
  `sale_price`, `is_get`, `tips`, `day`, `pid`,
  `is_rollback`, `rollback_num`, `coupon_type`, `provide_count`,
  `rollback_num_extend`, `use_type`, `receive_crowd`, `use_store`, `use_stroe_id`
) VALUES (
  2001, 'SEED_COUPON_UUNO_Q2_2001', 201, 'Seed Coupon Q2', '',
  0, 0, 0,
  100000, 99999, 1, 0,
  10.00, 0, 0.00,
  1, 0, 0,
  1, 1, 1, 50, 1, 1,
  @seed_time, @seed_time,
  10.00, 1, '', 0, 0,
  0, 0, 1, 0,
  0, 2, 1, 1, '0'
) ON DUPLICATE KEY UPDATE
  `cid` = 201,
  `coupon_title` = VALUES(`coupon_title`),
  `total_count` = VALUES(`total_count`),
  `remain_count` = VALUES(`remain_count`),
  `status` = 1,
  `is_del` = 0,
  `coupon_price` = VALUES(`coupon_price`),
  `sale_price` = VALUES(`sale_price`),
  `is_get` = 1,
  `coupon_type` = 1,
  `is_limit_total` = 1,
  `limit_total` = 1,
  `receive_crowd` = 1,
  `use_store` = 1,
  `use_stroe_id` = '0',
  `update_time` = @seed_time;

-- force deterministic "already received" branch (non-stateful for diff)
DELETE FROM `tp_coupon_issue_user` WHERE `uid` = 1 AND `issue_coupon_id` = 2001;

INSERT INTO `tp_coupon_issue_user` (
  `id`, `create_time`, `update_time`, `uid`, `issue_coupon_id`,
  `coupon_title`, `coupon_price`, `use_min_price`, `coupon_create_time`,
  `time_start`, `time_end`, `time_use`, `status`, `is_fail`, `is_limit_total`,
  `issue_coupon_class_id`, `enstr_salt`, `qrcode_url`,
  `code_time_create`, `code_time_expire`,
  `ips`, `longitude`, `latitude`, `expire_time`,
  `is_rollback`, `rollback_numbers`
) VALUES (
  920001, @seed_time, @seed_time, 1, 2001,
  'Seed Coupon Q2', 10.00, 0.00, @seed_time,
  0, 0, 0, 0, '0', 0,
  201, 'seed-q2-salt', 'seed-q2-qrcode',
  @seed_time, @seed_time + 600,
  '127.0.0.1', '0', '0', 4070880000,
  1, 0
) ON DUPLICATE KEY UPDATE
  `uid` = VALUES(`uid`),
  `issue_coupon_id` = VALUES(`issue_coupon_id`),
  `coupon_title` = VALUES(`coupon_title`),
  `coupon_price` = VALUES(`coupon_price`),
  `use_min_price` = VALUES(`use_min_price`),
  `status` = 0,
  `issue_coupon_class_id` = VALUES(`issue_coupon_class_id`),
  `qrcode_url` = VALUES(`qrcode_url`),
  `code_time_create` = VALUES(`code_time_create`),
  `code_time_expire` = VALUES(`code_time_expire`),
  `expire_time` = VALUES(`expire_time`),
  `update_time` = @seed_time;

-- tour entities for /api/user/writeoff_tour
INSERT INTO `tp_tour` (
  `id`, `create_time`, `update_time`,
  `status`, `name`, `no`, `term`, `numbers`,
  `planner`, `mobile`, `line_info`,
  `travel_id`, `spot_ids`, `mid`,
  `term_start`, `term_end`, `area_id`
) VALUES (
  2001, @seed_time, @seed_time,
  3, 'Seed Tour Q2', 'SEED_TOUR_Q2_2001', '2026-02', 1,
  'Seed Planner', '13800002001', '',
  0, '', 2001,
  @seed_time, @seed_time + 86400, '6108'
) ON DUPLICATE KEY UPDATE
  `status` = 3,
  `mid` = 2001,
  `update_time` = @seed_time;

-- status=1 => deterministic JSON error: "该消费券已使用"
INSERT INTO `tp_tour_coupon_group` (
  `id`, `create_time`, `update_time`, `receive_time`, `sort`, `status`,
  `tid`, `coupon_issue_id`, `is_receive`, `cid`,
  `enstr_salt`, `qrcode_url`, `code_time_create`, `code_time_expire`, `write_use`
) VALUES (
  2001, @seed_time, @seed_time, @seed_time, 50, 1,
  2001, 2001, 1, 201,
  'seed-q2-group-salt', 'seed-q2-group-qrcode', @seed_time, @seed_time + 86400, @seed_time
) ON DUPLICATE KEY UPDATE
  `tid` = 2001,
  `coupon_issue_id` = 2001,
  `is_receive` = 1,
  `cid` = 201,
  `status` = 1,
  `qrcode_url` = 'seed-q2-group-qrcode',
  `code_time_create` = @seed_time,
  `code_time_expire` = @seed_time + 86400,
  `write_use` = @seed_time,
  `update_time` = @seed_time;

DELETE FROM `tp_tour_issue_user` WHERE `id` = 920001 OR (`tid` = 2001 AND `uid` = 1 AND `issue_coupon_id` = 2001);

INSERT INTO `tp_tour_issue_user` (
  `id`, `create_time`, `update_time`, `uid`, `tid`, `issue_coupon_id`,
  `coupon_title`, `coupon_price`, `use_min_price`,
  `coupon_create_time`, `time_start`, `time_end`, `time_use`,
  `status`, `is_fail`, `is_limit_total`, `issue_coupon_class_id`,
  `enstr_salt`, `type`, `expire_time`
) VALUES (
  920001, @seed_time, @seed_time, 1, 2001, 2001,
  'Seed Coupon Q2', 10.00, 0.00,
  @seed_time, 0, 0, 0,
  0, 1, 0, 201,
  'seed-q2-tour-issue-salt', 2, 4070880000
) ON DUPLICATE KEY UPDATE
  `uid` = VALUES(`uid`),
  `tid` = VALUES(`tid`),
  `issue_coupon_id` = VALUES(`issue_coupon_id`),
  `coupon_title` = VALUES(`coupon_title`),
  `coupon_price` = VALUES(`coupon_price`),
  `use_min_price` = VALUES(`use_min_price`),
  `status` = 0,
  `type` = 2,
  `expire_time` = VALUES(`expire_time`),
  `update_time` = @seed_time;

-- keep writeoff table clean for this scenario
DELETE FROM `tp_tour_write_off` WHERE `tour_coupon_group_id` = 2001 OR `tour_issue_user_id` = 920001;
