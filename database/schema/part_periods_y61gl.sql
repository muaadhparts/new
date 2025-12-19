CREATE TABLE `part_periods_y61gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `begin_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_part_period` (`part_id`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_dates` (`begin_date`,`end_date`),
  KEY `idx_pp_dates_y61gl` (`begin_date`,`end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=8751513 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
