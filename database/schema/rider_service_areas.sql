CREATE TABLE `rider_service_areas` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `rider_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `price` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
