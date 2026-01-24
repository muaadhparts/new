       Table: merchant_payments
CREATE TABLE `merchant_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `operator` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  `subtitle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topup` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('manual','automatic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'manual',
  `information` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `keyword` varchar(191) DEFAULT NULL,
  `monetary_unit_id` varchar(191) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '*',
  `checkout` int NOT NULL DEFAULT '1',
  `deposit` int NOT NULL DEFAULT '1',
  `subscription` int NOT NULL DEFAULT '1',
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1
