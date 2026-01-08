-- Schema for table: catalog_item_clicks
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `catalog_item_clicks`;

CREATE TABLE `catalog_item_clicks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catalog_item_id` int NOT NULL,
  `merchant_item_id` int unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_clicks_merchant_product_id_index` (`merchant_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33076 DEFAULT CHARSET=latin1;
