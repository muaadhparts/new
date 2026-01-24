       Table: courier_service_areas
CREATE TABLE `courier_service_areas` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `courier_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `service_radius_km` int unsigned NOT NULL DEFAULT '20' COMMENT 'Service radius in kilometers',
  `price` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `courier_service_areas_coordinates_index` (`latitude`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
