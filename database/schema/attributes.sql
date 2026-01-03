-- Schema for table: attributes
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `attributes`;

CREATE TABLE `attributes` (
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
