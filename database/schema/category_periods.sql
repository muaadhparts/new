       Table: category_periods
CREATE TABLE `category_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_period` (`category_id`,`begin_date`,`end_date`),
  KEY `idx_cp_category_dates` (`category_id`,`begin_date`,`end_date`),
  CONSTRAINT `category_periods_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `newcategories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=196073 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
