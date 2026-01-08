-- Schema for table: merchant_commissions
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `merchant_commissions`;

CREATE TABLE `merchant_commissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `fixed_commission` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Fixed amount added to price',
  `percentage_commission` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Percentage markup on price',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_commissions_user_id_unique` (`user_id`),
  CONSTRAINT `merchant_commissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
