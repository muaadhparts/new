CREATE TABLE `delivery_riders` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `vendor_id` int DEFAULT NULL,
  `rider_id` int DEFAULT NULL,
  `pickup_point_id` int DEFAULT NULL,
  `service_area_id` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
