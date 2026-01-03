-- Schema for table: treecategories
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `treecategories`;

CREATE TABLE `treecategories` (
  `id` bigint unsigned NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `label_ar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `brand_id` bigint unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `level` tinyint unsigned NOT NULL,
  `path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tree_slug_ctx` (`slug`,`brand_id`,`catalog_id`),
  KEY `catalog_id` (`catalog_id`,`level`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
