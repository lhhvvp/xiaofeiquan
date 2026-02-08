-- P0 golden quality batch#4 seed (dev-only).
-- Scope: make meituan/xc webupload multipart cases pass extension validation
-- and enter deterministic JSON error path (Failed to open temp directory.).
--
-- Covered cases:
-- - p0-quality4-post-meituan-webupload-index-001
-- - p0-quality4-post-xc-webupload-index-001
--
-- NOTE:
-- - Apply on top of 02-seed-minimal.sql + 03-seed-dev-auth.sql.
-- - This dataset is optional and isolated for quality batch#4.

UPDATE `tp_system`
SET
  `upload_image_ext` = 'jpg,png,gif,jpeg,txt',
  `upload_file_ext` = 'rar,zip,avi,rmvb,3gp,flv,mp3,mp4,txt,doc,xls,ppt,pdf,xlsx,docx'
WHERE `id` = 1;
