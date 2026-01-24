       Table: catalog_item_code_mappings
CREATE TABLE `catalog_item_code_mappings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catalog_item_id` int unsigned DEFAULT NULL,
  `quality_brand_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_catalog_item_code_mappings_item_code` (`item_code`),
  KEY `idx_catalog_item_code_mappings_catalog_item` (`catalog_item_id`),
  KEY `idx_catalog_item_code_mappings_brand_quality` (`quality_brand_id`),
  CONSTRAINT `fk_catalog_item_code_mappings_brand_quality` FOREIGN KEY (`quality_brand_id`) REFERENCES `quality_brands` (`id`),
  CONSTRAINT `fk_catalog_item_code_mappings_catalog_item` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=728528 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
