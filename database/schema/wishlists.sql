CREATE TABLE `wishlists` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `merchant_product_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_wishlists_merchant_product` (`merchant_product_id`),
  CONSTRAINT `fk_wishlists_merchant_product` FOREIGN KEY (`merchant_product_id`) REFERENCES `merchant_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=latin1;
