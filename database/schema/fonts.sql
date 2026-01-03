-- Schema for table: fonts
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `fonts`;

CREATE TABLE `fonts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_default` tinyint DEFAULT '0',
  `font_family` varchar(100) DEFAULT NULL,
  `font_value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
