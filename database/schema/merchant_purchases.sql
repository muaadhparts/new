-- Schema for table: merchant_purchases
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `merchant_purchases`;

CREATE TABLE `merchant_purchases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `purchase_id` int NOT NULL,
  `qty` int NOT NULL,
  `price` double NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `packing_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `courier_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_type` enum('merchant','platform') NOT NULL DEFAULT 'platform',
  `shipping_type` enum('merchant','platform','courier','pickup') DEFAULT NULL,
  `money_received_by` enum('merchant','platform','courier') NOT NULL DEFAULT 'platform',
  `payment_gateway_id` int unsigned DEFAULT NULL,
  `shipping_id` int unsigned DEFAULT NULL,
  `courier_id` int unsigned DEFAULT NULL,
  `merchant_location_id` int unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `purchase_number` varchar(191) NOT NULL,
  `status` enum('pending','processing','completed','declined','on delivery') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
