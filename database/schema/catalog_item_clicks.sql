CREATE TABLE `catalog_item_clicks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catalog_item_id` int unsigned NOT NULL,
  `merchant_item_id` int unsigned DEFAULT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `catalog_item_clicks_catalog_item_id_index` (`catalog_item_id`),
  KEY `catalog_item_clicks_merchant_item_id_index` (`merchant_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
