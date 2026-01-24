       Table: sku_alternatives
CREATE TABLE `sku_alternatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sku` (`part_number`),
  KEY `idx_group_id` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=784221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
