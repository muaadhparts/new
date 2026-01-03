-- Schema for table: galleries
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `galleries`;

CREATE TABLE `galleries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `catalog_item_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
