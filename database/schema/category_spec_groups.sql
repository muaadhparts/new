-- Schema for table: category_spec_groups
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `category_spec_groups`;

CREATE TABLE `category_spec_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `group_index` int unsigned DEFAULT '0',
  `category_period_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cat_spec_group` (`category_id`,`catalog_id`,`group_index`,`category_period_id`),
  KEY `catalog_id` (`catalog_id`),
  KEY `category_period_id` (`category_period_id`),
  KEY `idx_csg_category_catalog` (`category_id`,`catalog_id`),
  CONSTRAINT `category_spec_groups_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `newcategories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_spec_groups_ibfk_2` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_spec_groups_ibfk_3` FOREIGN KEY (`category_period_id`) REFERENCES `category_periods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=277224 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
