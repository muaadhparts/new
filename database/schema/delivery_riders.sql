-- Schema for table: delivery_riders
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `delivery_riders`;

CREATE TABLE `delivery_riders` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `purchase_id` int DEFAULT NULL,
  `merchant_id` int DEFAULT NULL,
  `rider_id` int DEFAULT NULL,
  `pickup_point_id` int DEFAULT NULL,
  `service_area_id` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
