-- Schema for table: countries
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `countries`;

CREATE TABLE `countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) NOT NULL DEFAULT '',
  `country_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `country_name_ar` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `tax` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `is_synced` tinyint(1) NOT NULL DEFAULT '0',
  `synced_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;
