-- Schema for table: catalog_events
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `catalog_events`;

CREATE TABLE `catalog_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purchase_id` int unsigned DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `merchant_id` int DEFAULT NULL,
  `catalog_item_id` int DEFAULT NULL,
  `chat_thread_id` int unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
