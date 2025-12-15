CREATE TABLE `sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `formatted_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalog_id` (`catalog_id`,`code`),
  KEY `category_id` (`category_id`),
  KEY `idx_sections_full_code` (`full_code`),
  KEY `idx_sections_id_code` (`id`,`full_code`),
  KEY `idx_sections_id_full_code` (`id`,`full_code`),
  KEY `idx_sections_category_catalog` (`category_id`,`catalog_id`),
  KEY `idx_sections_full_code_catalog` (`full_code`,`catalog_id`),
  CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sections_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `newcategories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=184118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
