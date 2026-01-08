-- Schema for table: languages
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `languages`;

CREATE TABLE `languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `language` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `rtl` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
