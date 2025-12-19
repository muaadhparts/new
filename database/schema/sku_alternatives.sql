CREATE TABLE `sku_alternatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sku` (`sku`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_ska_sku` (`sku`),
  KEY `idx_ska_group_id` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=784221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
