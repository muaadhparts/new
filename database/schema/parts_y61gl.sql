       Table: parts_y61gl
CREATE TABLE `parts_y61gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int DEFAULT '1',
  `callout` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_part` (`part_number`,`label_en`,`qty`,`callout`),
  KEY `idx_p_callout` (`callout`),
  KEY `idx_part_number` (`part_number`(50)),
  KEY `idx_callout` (`callout`(50)),
  KEY `idx_label_en` (`label_en`(100)),
  KEY `idx_label_ar` (`label_ar`(100)),
  KEY `idx_part_callout` (`part_number`(50),`callout`(50))
) ENGINE=InnoDB AUTO_INCREMENT=27299 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
