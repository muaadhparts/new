CREATE TABLE `alternatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) DEFAULT NULL,
  `alternative` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39747 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
