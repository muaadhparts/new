-- Schema for table: chat_entries
-- Exported: 2026-01-07 23:59:03

DROP TABLE IF EXISTS `chat_entries`;

CREATE TABLE `chat_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chat_thread_id` int unsigned DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_user` int DEFAULT NULL,
  `recieved_user` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
