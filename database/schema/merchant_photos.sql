       Table: merchant_photos
CREATE TABLE `merchant_photos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `merchant_item_id` int unsigned NOT NULL,
  `photo` varchar(191) NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_merchant_photos_merchant_item` (`merchant_item_id`),
  CONSTRAINT `fk_merchant_photos_merchant_item` FOREIGN KEY (`merchant_item_id`) REFERENCES `merchant_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
