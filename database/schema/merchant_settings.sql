       Table: merchant_settings
CREATE TABLE `merchant_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` int unsigned NOT NULL COMMENT 'FK to users.id (merchant)',
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Setting group',
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Setting key within group',
  `value` json DEFAULT NULL COMMENT 'Setting value (JSON)',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_settings_unique` (`merchant_id`,`group`,`key`),
  KEY `merchant_settings_merchant_group` (`merchant_id`,`group`),
  KEY `merchant_settings_group_index` (`group`),
  CONSTRAINT `merchant_settings_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
