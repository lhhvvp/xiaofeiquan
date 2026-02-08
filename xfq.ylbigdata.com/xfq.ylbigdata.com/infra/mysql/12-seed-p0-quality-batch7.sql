-- P0 golden quality batch#7 seed (dev-only).
-- Scope: no-op seed for deterministic low-quality baseline cleanup cases.
--
-- Covered cases:
-- - p0-quality7-post-api-user-getTouristList-001
--
-- NOTE:
-- - Apply on top of 02-seed-minimal.sql + 03-seed-dev-auth.sql.
-- - This dataset is optional and isolated for quality batch#7.
-- - This batch does not require extra business seed rows.

SELECT 1;
