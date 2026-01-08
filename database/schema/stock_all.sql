-- Schema for table: stock_all
-- Exported: 2026-01-07 23:59:04

DROP TABLE IF EXISTS `stock_all`;

CREATE TABLE `stock_all` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_quality_id` int unsigned NOT NULL DEFAULT '1',
  `qty` int DEFAULT '0',
  `cost_price` decimal(18,4) DEFAULT '0.0000',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_all_part_unique` (`part_number`),
  UNIQUE KEY `uq_stock_all_sku_bq` (`sku`,`brand_quality_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34009 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
