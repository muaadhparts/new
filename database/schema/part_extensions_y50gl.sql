CREATE TABLE `part_extensions_y50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `section_id` bigint unsigned NOT NULL,
  `group_id` bigint unsigned NOT NULL,
  `extension_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_period_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pe_group` (`group_id`),
  KEY `idx_pe_key` (`extension_key`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_extension_key` (`extension_key`(50)),
  KEY `idx_part_section_group` (`part_id`,`section_id`,`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=481433 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
