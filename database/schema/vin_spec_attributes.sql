       Table: vin_spec_attributes
CREATE TABLE `vin_spec_attributes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `vin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attribute_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attribute_value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vin_attribute_index` (`vin`,`attribute_code`),
  CONSTRAINT `vin_spec_attributes_ibfk_1` FOREIGN KEY (`vin`) REFERENCES `vin_decoded_cache` (`vin`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1308 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
