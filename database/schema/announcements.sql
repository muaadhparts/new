-- Schema for table: announcements
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `announcements`;

CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Large','TopSmall','BottomSmall') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
