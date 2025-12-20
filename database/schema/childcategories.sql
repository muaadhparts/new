CREATE TABLE `childcategories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subcategory_id` int NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `rol` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_slug_subcategory` (`slug`,`subcategory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3753 DEFAULT CHARSET=latin1;
