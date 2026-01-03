-- Schema for table: stocks
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `stocks`;

CREATE TABLE `stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` int unsigned NOT NULL,
  `location` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int DEFAULT '0',
  `sell_price` decimal(18,4) DEFAULT NULL,
  `comp_cost` decimal(18,4) DEFAULT NULL,
  `cost_price` decimal(18,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stocks_part_location_unique` (`part_number`,`location`),
  KEY `stocks_part_index` (`part_number`),
  KEY `stocks_branch_index` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=875676 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
