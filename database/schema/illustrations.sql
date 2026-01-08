-- Schema for table: illustrations
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `illustrations`;

CREATE TABLE `illustrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `data_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_illustration` (`section_id`,`category_id`,`code`,`data_code`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_illustrations_category` FOREIGN KEY (`category_id`) REFERENCES `newcategories` (`id`),
  CONSTRAINT `fk_illustrations_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=184118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
