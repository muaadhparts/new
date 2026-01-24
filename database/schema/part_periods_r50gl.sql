       Table: part_periods_r50gl
CREATE TABLE `part_periods_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `begin_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pp_dates_r50gl` (`begin_date`,`end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=530888 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
