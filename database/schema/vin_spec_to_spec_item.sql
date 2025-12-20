CREATE TABLE `vin_spec_to_spec_item` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `vin_attribute_id` bigint NOT NULL,
  `specification_item_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vin_spec_map` (`vin_attribute_id`,`specification_item_id`),
  KEY `specification_item_id` (`specification_item_id`),
  CONSTRAINT `vin_spec_to_spec_item_ibfk_1` FOREIGN KEY (`vin_attribute_id`) REFERENCES `vin_spec_attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vin_spec_to_spec_item_ibfk_2` FOREIGN KEY (`specification_item_id`) REFERENCES `specification_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
