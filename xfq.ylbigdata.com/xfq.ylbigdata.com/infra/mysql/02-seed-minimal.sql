-- 最小 seed：保证“清库后至少能跑起来”，避免大量代码依赖 find(1) 直接报错。
-- 注意：真实业务上线前，需要把支付/短信/OSS/OTA 等配置补齐到可用状态。

INSERT INTO tp_system (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO tp_base_payment (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

