CREATE TABLE `counters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('referral','browser') NOT NULL DEFAULT 'referral',
  `referral` varchar(255) DEFAULT NULL,
  `total_count` int NOT NULL DEFAULT '0',
  `todays_count` int NOT NULL DEFAULT '0',
  `today` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=latin1;
