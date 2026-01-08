-- Schema for table: rewards
-- Exported: 2026-01-07 23:59:04

DROP TABLE IF EXISTS `rewards`;

CREATE TABLE `rewards` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_amount` double NOT NULL DEFAULT '0',
  `reward` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
