CREATE TABLE `product_clicks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `merchant_product_id` int unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_clicks_merchant_product_id_index` (`merchant_product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33055 DEFAULT CHARSET=latin1;
