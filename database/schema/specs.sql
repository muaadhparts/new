-- Schema for table: specs
-- Exported: 2026-01-07 23:59:04

DROP TABLE IF EXISTS `specs`;

CREATE TABLE `specs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attributable_id` int DEFAULT NULL,
  `attributable_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `input_name` varchar(255) DEFAULT NULL,
  `price_status` int NOT NULL DEFAULT '1' COMMENT '0 - hide, 1- show	',
  `details_status` int NOT NULL DEFAULT '1' COMMENT '0 - hide, 1- show	',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=latin1;
