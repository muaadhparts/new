-- Schema for table: vin_models
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `vin_models`;

CREATE TABLE `vin_models` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_code` (`model_code`)
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
