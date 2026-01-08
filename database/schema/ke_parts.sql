-- Schema for table: ke_parts
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `ke_parts`;

CREATE TABLE `ke_parts` (
  `id` int unsigned NOT NULL,
  `availability` int DEFAULT '0',
  `extra` json DEFAULT NULL,
  `full_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `make` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processing_time` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pull_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_part_number` (`part_number`),
  KEY `idx_full_number` (`full_number`),
  KEY `idx_make` (`make`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
