       Table: account_parties
CREATE TABLE `account_parties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `party_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Source table: users, couriers, shippings, etc',
  `reference_id` bigint unsigned DEFAULT NULL COMMENT 'ID in source table',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique code: platform, merchant_5, courier_12, shipping_tryoto',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_parties_code_unique` (`code`),
  KEY `account_parties_party_type_index` (`party_type`),
  KEY `account_parties_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `account_parties_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
