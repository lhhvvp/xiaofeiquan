-- P0 golden quality batch#5 seed (dev-only).
-- Scope: no-op seed for deterministic low-quality baseline cleanup cases.
--
-- Covered cases:
-- - p0-quality5-post-api-index-get_area_info-001
-- - p0-quality5-post-api-pay-OrderRefund-001
-- - p0-quality5-post-api-pay-regressionStock-001
-- - p0-quality5-post-api-ticket-OrderRefund-001
-- - p0-quality5-post-api-ticket-OrderRefundDetail-001
-- - p0-quality5-post-api-notify-create_file-001
-- - p0-quality5-post-api-ticket-create_file-001
-- - p0-quality5-post-api-seller-search-001
--
-- NOTE:
-- - Apply on top of 02-seed-minimal.sql + 03-seed-dev-auth.sql.
-- - This dataset is optional and isolated for quality batch#5.
-- - This batch does not require extra business seed rows.

SELECT 1;
