-- Schema for table: wallet_logs
-- Exported: 2026-01-07 23:59:04

DROP TABLE IF EXISTS `wallet_logs`;

CREATE TABLE `wallet_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `reward_point` double DEFAULT '0',
  `reward_dolar` double NOT NULL DEFAULT '0',
  `txn_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `amount` double DEFAULT '0',
  `currency_sign` blob,
  `currency_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_value` double NOT NULL DEFAULT '0',
  `method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `txnid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'plus, minus',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
