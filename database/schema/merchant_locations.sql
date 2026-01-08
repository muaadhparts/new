-- Schema for table: merchant_locations
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `merchant_locations`;

CREATE TABLE `merchant_locations` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `city_id` int unsigned DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `pickup_points_city_id_index` (`city_id`),
  KEY `pickup_points_coordinates_index` (`latitude`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
