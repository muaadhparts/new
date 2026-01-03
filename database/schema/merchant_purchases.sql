-- Schema for table: merchant_purchases
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `merchant_purchases`;

CREATE TABLE `merchant_purchases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `purchase_id` int NOT NULL,
  `qty` int NOT NULL,
  `price` double NOT NULL,
  `purchase_number` varchar(191) NOT NULL,
  `status` enum('pending','processing','completed','declined','on delivery') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
