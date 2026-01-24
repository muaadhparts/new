       Table: vin_spec_mapped
CREATE TABLE `vin_spec_mapped` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vin_id` bigint unsigned NOT NULL,
  `specification_id` int unsigned NOT NULL,
  `specification_item_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vin_spec` (`vin_id`,`specification_id`),
  KEY `specification_id` (`specification_id`),
  KEY `specification_item_id` (`specification_item_id`),
  CONSTRAINT `vin_spec_mapped_ibfk_1` FOREIGN KEY (`vin_id`) REFERENCES `vin_decoded_cache` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vin_spec_mapped_ibfk_2` FOREIGN KEY (`specification_id`) REFERENCES `specifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vin_spec_mapped_ibfk_3` FOREIGN KEY (`specification_item_id`) REFERENCES `specification_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
