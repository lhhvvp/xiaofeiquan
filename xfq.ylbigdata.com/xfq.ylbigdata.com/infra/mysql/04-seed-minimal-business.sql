-- Minimal business seed dataset (dev-only).
-- Goal: provide a small, repeatable dataset for "success path" golden cases:
--   1 scenic seller + 1 ticket category + 1 ticket + 1 day price/stock.
--
-- NOTE: Applying this seed may change existing golden baselines (e.g. scenic list/min_price).

-- Scenic seller (stable id=1)
INSERT INTO `tp_seller` (
  `id`, `status`, `nickname`, `last_login_ip`,
  `class_id`, `area`, `longitude`, `latitude`,
  `mobile`, `do_business_time`, `address`,
  `create_time`, `update_time`
) VALUES (
  1, 1, 'Seed Scenic', '127.0.0.1',
  2, 1, 109.700000, 38.300000,
  '13800000000', '09:00-18:00', 'Seed Address',
  UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
) ON DUPLICATE KEY UPDATE
  `status` = VALUES(`status`),
  `nickname` = VALUES(`nickname`),
  `last_login_ip` = VALUES(`last_login_ip`),
  `class_id` = VALUES(`class_id`),
  `area` = VALUES(`area`),
  `longitude` = VALUES(`longitude`),
  `latitude` = VALUES(`latitude`),
  `mobile` = VALUES(`mobile`),
  `do_business_time` = VALUES(`do_business_time`),
  `address` = VALUES(`address`),
  `update_time` = VALUES(`update_time`);

-- Ticket category (stable id=1, for seller_id=1)
INSERT INTO `tp_ticket_category` (
  `id`, `seller_id`, `title`, `status`, `sort`, `create_time`, `update_time`
) VALUES (
  1, 1, 'Seed Category', 1, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
) ON DUPLICATE KEY UPDATE
  `seller_id` = VALUES(`seller_id`),
  `title` = VALUES(`title`),
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`),
  `update_time` = VALUES(`update_time`);

-- Ticket (stable id=1, for seller_id=1)
INSERT INTO `tp_ticket` (
  `id`, `seller_id`, `category_id`, `title`, `cover`,
  `quota`, `quota_order`, `rights_num`, `code`,
  `status`, `sort`, `create_time`, `update_time`
) VALUES (
  1, 1, 1, 'Seed Ticket', '',
  0, 99, 0, 'SEED_TICKET_1',
  1, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
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
  `update_time` = VALUES(`update_time`);

-- Ticket daily price/stock (for today)
INSERT INTO `tp_ticket_price` (
  `ticket_id`, `seller_id`, `date`,
  `online_price`, `casual_price`, `team_price`,
  `stock`, `total_stock`, `create_time`, `update_time`
) VALUES (
  1, 1, CURDATE(),
  100.00, 100.00, 100.00,
  100000, 100000, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
) ON DUPLICATE KEY UPDATE
  `seller_id` = VALUES(`seller_id`),
  `online_price` = VALUES(`online_price`),
  `casual_price` = VALUES(`casual_price`),
  `team_price` = VALUES(`team_price`),
  `stock` = VALUES(`stock`),
  `total_stock` = VALUES(`total_stock`),
  `update_time` = VALUES(`update_time`);

