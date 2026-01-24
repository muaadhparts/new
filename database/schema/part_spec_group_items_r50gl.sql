       Table: part_spec_group_items_r50gl
CREATE TABLE `part_spec_group_items_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `specification_item_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psgi_group_id_r50gl` (`group_id`),
  KEY `idx_psgi_spec_item_r50gl` (`specification_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=769279 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
