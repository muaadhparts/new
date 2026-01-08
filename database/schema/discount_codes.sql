-- Schema for table: discount_codes
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `discount_codes`;

CREATE TABLE `discount_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT 'Vendor ID who created the discount code',
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique discount code',
  `type` tinyint NOT NULL COMMENT '0 = Percentage, 1 = Fixed Amount',
  `price` double NOT NULL COMMENT 'Discount value (percentage or fixed amount)',
  `times` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usage limit (null = unlimited)',
  `used` int unsigned NOT NULL DEFAULT '0' COMMENT 'Number of times used',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1 = Active, 0 = Inactive',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `apply_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'category, sub_category, child_category',
  `category` int DEFAULT NULL,
  `sub_category` int DEFAULT NULL,
  `child_category` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discount_codes_user_id_index` (`user_id`),
  KEY `discount_codes_code_index` (`code`),
  KEY `discount_codes_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
