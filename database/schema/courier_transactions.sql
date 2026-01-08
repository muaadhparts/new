-- Schema for table: courier_transactions
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `courier_transactions`;

CREATE TABLE `courier_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `courier_id` bigint NOT NULL,
  `delivery_courier_id` bigint DEFAULT NULL,
  `settlement_id` bigint DEFAULT NULL,
  `type` enum('fee_earned','cod_collected','settlement_paid','settlement_received','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_before` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courier_transactions_courier_id_created_at_index` (`courier_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
