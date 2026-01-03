-- Schema for table: pickups
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `pickups`;

CREATE TABLE `pickups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `location` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
