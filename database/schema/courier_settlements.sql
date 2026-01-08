-- Schema for table: courier_settlements
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `courier_settlements`;

CREATE TABLE `courier_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `courier_id` bigint NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `type` enum('pay_to_courier','receive_from_courier') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `processed_by` int unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courier_settlements_courier_id_status_index` (`courier_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
