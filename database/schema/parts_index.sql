CREATE TABLE `parts_index` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catalog_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `brand_id` bigint unsigned NOT NULL,
  `is_synced` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `subcategory_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `part_number` (`part_number`),
  KEY `catalog_code` (`catalog_code`),
  KEY `catalog_id` (`catalog_id`),
  KEY `brand_id` (`brand_id`),
  KEY `is_synced` (`is_synced`),
  KEY `idx_pi_part_number` (`part_number`)
) ENGINE=InnoDB AUTO_INCREMENT=1263175 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
