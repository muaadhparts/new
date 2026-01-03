-- Schema for table: category_spec_group_items
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `category_spec_group_items`;

CREATE TABLE `category_spec_group_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `specification_item_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_group_item` (`group_id`,`specification_item_id`),
  KEY `specification_item_id` (`specification_item_id`),
  KEY `idx_csgi_group_spec` (`group_id`,`specification_item_id`),
  CONSTRAINT `category_spec_group_items_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `category_spec_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_spec_group_items_ibfk_2` FOREIGN KEY (`specification_item_id`) REFERENCES `specification_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=417977 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
