-- Schema for table: mailing_list
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `mailing_list`;

CREATE TABLE `mailing_list` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
