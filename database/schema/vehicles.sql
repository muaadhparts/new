-- Schema for table: vehicles
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `vehicles`;

CREATE TABLE `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort` int DEFAULT '0',
  `beginDate` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `endDate` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `beginYear` int NOT NULL,
  `endYear` int NOT NULL,
  `dateRangeDescription` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `brand_id` int DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `vehicleType` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `shortName` varchar(50) DEFAULT NULL,
  `applicableRegions` varchar(255) DEFAULT NULL,
  `hasNotes` tinyint(1) DEFAULT NULL,
  `models` text,
  `hasModels` tinyint(1) DEFAULT NULL,
  `imagePath` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `largeImagePath` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB AUTO_INCREMENT=354 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
