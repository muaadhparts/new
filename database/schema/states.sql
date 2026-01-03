-- Schema for table: states
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `states`;

CREATE TABLE `states` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `country_id` int NOT NULL DEFAULT '0',
  `state` varchar(111) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state_ar` varchar(111) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `tax` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  `owner_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
