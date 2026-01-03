-- Schema for table: vin_catalog_log
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `vin_catalog_log`;

CREATE TABLE `vin_catalog_log` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `vin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catalog_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matched_by` enum('direct','fallback','manual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'direct',
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vin_catalog` (`vin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
