       Table: account_balances
CREATE TABLE `account_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `party_id` bigint unsigned NOT NULL,
  `counterparty_id` bigint unsigned NOT NULL,
  `balance_type` enum('receivable','payable') COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Total outstanding balance',
  `pending_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Amount pending collection/settlement',
  `settled_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Amount already settled',
  `monetary_unit_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `transaction_count` int unsigned NOT NULL DEFAULT '0',
  `last_transaction_at` timestamp NULL DEFAULT NULL,
  `last_calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_balance` (`party_id`,`counterparty_id`,`balance_type`),
  KEY `account_balances_party_id_balance_type_index` (`party_id`,`balance_type`),
  KEY `account_balances_counterparty_id_balance_type_index` (`counterparty_id`,`balance_type`),
  CONSTRAINT `account_balances_counterparty_id_foreign` FOREIGN KEY (`counterparty_id`) REFERENCES `account_parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `account_balances_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `account_parties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
