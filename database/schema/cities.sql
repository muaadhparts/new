CREATE TABLE `cities` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `city_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `country_id` int NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `tryoto_supported` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cities_country_id_tryoto_supported_index` (`country_id`,`tryoto_supported`),
  KEY `cities_latitude_longitude_index` (`latitude`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=6230 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
