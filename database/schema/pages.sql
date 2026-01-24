       Table: pages
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL slug: terms, privacy, refund',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Page title',
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Arabic title',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Page content (HTML)',
  `content_ar` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Arabic content',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
