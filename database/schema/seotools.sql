-- Schema for table: seotools
-- Exported: 2026-01-07 23:59:04

DROP TABLE IF EXISTS `seotools`;

CREATE TABLE `seotools` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `google_analytics` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gtm_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `search_console_verification` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bing_verification` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_pixel` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_keys` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
