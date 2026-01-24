       Table: favorite_sellers
CREATE TABLE `favorite_sellers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `catalog_item_id` int unsigned DEFAULT NULL,
  `merchant_item_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `favorites_catalog_item_id_index` (`catalog_item_id`),
  KEY `favorites_merchant_item_id_index` (`merchant_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=220 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
