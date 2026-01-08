-- Schema for table: couriers
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `couriers`;

CREATE TABLE `couriers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_verify` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'No',
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `balance` double DEFAULT '0',
  `total_collected` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_delivered` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_fees_earned` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
