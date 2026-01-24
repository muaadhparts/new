       Table: catalog_reviews
CREATE TABLE `catalog_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `catalog_item_id` int unsigned DEFAULT NULL,
  `merchant_item_id` int unsigned DEFAULT NULL,
  `review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rating` tinyint NOT NULL,
  `review_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `catalog_reviews_catalog_item_id_index` (`catalog_item_id`),
  KEY `catalog_reviews_merchant_item_id_index` (`merchant_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
