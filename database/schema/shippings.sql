-- Schema for table: shippings
-- Exported: 2026-01-07 23:59:04

DROP TABLE IF EXISTS `shippings`;

CREATE TABLE `shippings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `provider` varchar(50) NOT NULL DEFAULT 'manual',
  `title` text,
  `subtitle` text,
  `price` double NOT NULL DEFAULT '0',
  `free_above` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_shippings_provider` (`provider`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;
