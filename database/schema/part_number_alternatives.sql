-- Schema for table: part_number_alternatives
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `part_number_alternatives`;

CREATE TABLE `part_number_alternatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_part_number` (`part_number`) USING BTREE,
  KEY `idx_group_id` (`group_id`),
  KEY `idx_ska_group_id` (`group_id`),
  KEY `idx_ska_part_number` (`part_number`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=784221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
