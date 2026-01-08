-- Schema for table: part_number_alternative_item
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `part_number_alternative_item`;

CREATE TABLE `part_number_alternative_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `a_id` bigint unsigned NOT NULL,
  `b_id` bigint unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_edge` (`a_id`,`b_id`),
  KEY `idx_a_id` (`a_id`),
  KEY `idx_b_id` (`b_id`),
  CONSTRAINT `fk_edge_a` FOREIGN KEY (`a_id`) REFERENCES `part_number_alternatives` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_edge_b` FOREIGN KEY (`b_id`) REFERENCES `part_number_alternatives` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=102513 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
