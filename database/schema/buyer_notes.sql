-- Schema for table: buyer_notes
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `buyer_notes`;

CREATE TABLE `buyer_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `catalog_item_id` int unsigned DEFAULT NULL,
  `merchant_item_id` int unsigned DEFAULT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
