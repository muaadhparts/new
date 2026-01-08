-- Schema for table: merchant_tax_settings
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `merchant_tax_settings`;

CREATE TABLE `merchant_tax_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VAT',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_tax_settings_user_id_unique` (`user_id`),
  KEY `merchant_tax_settings_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
