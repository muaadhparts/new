       Table: catalog_item_fitments
CREATE TABLE `catalog_item_fitments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_item_id` int unsigned DEFAULT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `brand_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `catalog_id` (`catalog_id`),
  KEY `brand_id` (`brand_id`),
  KEY `fk_fitments_catalog_items` (`catalog_item_id`),
  CONSTRAINT `fk_fitments_brands` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fitments_catalog_items` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fitments_catalogs` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1263176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
