CREATE TABLE `callouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `illustration_id` bigint unsigned DEFAULT NULL,
  `callout_type` enum('part','hardware','section','basic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callout_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applicable` tinyint(1) DEFAULT '0',
  `selective_fit` tinyint(1) DEFAULT '0',
  `rectangle_top` int DEFAULT NULL,
  `rectangle_left` int DEFAULT NULL,
  `rectangle_right` int DEFAULT NULL,
  `rectangle_bottom` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `illustration_id` (`illustration_id`),
  KEY `idx_callouts_illustration_type` (`illustration_id`,`callout_type`),
  CONSTRAINT `fk_callouts_illustration_id` FOREIGN KEY (`illustration_id`) REFERENCES `illustrations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3584155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
