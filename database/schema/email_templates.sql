CREATE TABLE `email_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email_subject` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `email_body` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
