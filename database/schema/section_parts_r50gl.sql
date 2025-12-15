CREATE TABLE `section_parts_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_section_part` (`section_id`,`part_id`,`category_id`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_section_part` (`section_id`,`part_id`),
  KEY `idx_sp_section_part_r50gl` (`section_id`,`part_id`),
  KEY `idx_sp_part_id_r50gl` (`part_id`)
) ENGINE=InnoDB AUTO_INCREMENT=336461 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
