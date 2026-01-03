-- Schema for table: stock_patromin
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `stock_patromin`;

CREATE TABLE `stock_patromin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Discount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8795 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
