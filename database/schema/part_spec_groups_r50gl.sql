       Table: part_spec_groups_r50gl
CREATE TABLE `part_spec_groups_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `section_id` bigint unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `group_index` int NOT NULL,
  `part_period_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psg_part_section_catalog_r50gl` (`part_id`,`section_id`,`catalog_id`),
  KEY `idx_psg_section_catalog_r50gl` (`section_id`,`catalog_id`),
  KEY `idx_psg_period_r50gl` (`part_period_id`)
) ENGINE=InnoDB AUTO_INCREMENT=429083 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
