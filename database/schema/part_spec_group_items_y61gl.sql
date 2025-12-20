CREATE TABLE `part_spec_group_items_y61gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `specification_item_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psGi_group` (`group_id`),
  KEY `idx_psGi_spec_item` (`specification_item_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_specification_item_id` (`specification_item_id`),
  KEY `idx_psgi_group_id_y61gl` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=83167252 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
