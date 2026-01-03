-- Schema for table: alternatives
-- Exported: 2026-01-03 04:17:45

DROP TABLE IF EXISTS `alternatives`;

CREATE TABLE `alternatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(255) DEFAULT NULL,
  `alternative` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39747 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
