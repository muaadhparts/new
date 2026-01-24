       Table: shippings
CREATE TABLE `shippings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `operator` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  `integration_type` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'manual',
  `provider` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `name` text,
  `subtitle` text,
  `price` double NOT NULL DEFAULT '0',
  `free_above` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_shippings_provider` (`provider`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1
