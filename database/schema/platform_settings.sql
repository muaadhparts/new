       Table: platform_settings
CREATE TABLE `platform_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Setting group: branding, mail, payment, etc',
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Setting key within group',
  `value` json DEFAULT NULL COMMENT 'Setting value (JSON for flexibility)',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'Value type: string, boolean, integer, json, file',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Human-readable description',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_settings_group_key_unique` (`group`,`key`),
  KEY `platform_settings_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
