       Table: specification_items
CREATE TABLE `specification_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `specification_id` int unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `value_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_spec_value` (`specification_id`,`catalog_id`,`value_id`),
  KEY `catalog_id` (`catalog_id`),
  KEY `idx_si_id_value` (`id`,`value_id`),
  KEY `idx_spec_items_id_value_spec` (`id`,`value_id`,`specification_id`),
  KEY `idx_si_catalog_spec` (`catalog_id`,`specification_id`),
  CONSTRAINT `specification_items_ibfk_1` FOREIGN KEY (`specification_id`) REFERENCES `specifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `specification_items_ibfk_2` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6911 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
