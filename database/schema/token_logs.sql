-- Schema for table: token_logs
-- Exported: 2026-01-07 23:59:04

DROP TABLE IF EXISTS `token_logs`;

CREATE TABLE `token_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `message` text,
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
