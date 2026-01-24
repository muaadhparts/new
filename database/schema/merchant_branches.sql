       Table: merchant_branches
CREATE TABLE `merchant_branches` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `branch_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `warehouse_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tryoto_warehouse_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Warehouse Code from Tryoto dashboard',
  `country_id` bigint unsigned DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `city_id` int unsigned DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `pickup_points_city_id_index` (`city_id`),
  KEY `pickup_points_coordinates_index` (`latitude`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
