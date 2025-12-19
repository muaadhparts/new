-- MySQL Schema Reference (READ ONLY)
-- This file is for documentation and AI agents only
-- DO NOT EXECUTE THIS FILE ON ANY ENVIRONMENT

-- ============================================================
-- TABLE: `addons`
-- ============================================================
CREATE TABLE `addons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `keyword` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uninstall_files` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `admin_user_conversations`
-- ============================================================
CREATE TABLE `admin_user_conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` enum('Ticket','Dispute') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `admin_user_messages`
-- ============================================================
CREATE TABLE `admin_user_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `admins`
-- ============================================================
CREATE TABLE `admins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL DEFAULT '0',
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shop_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `affliate_bonuses`
-- ============================================================
CREATE TABLE `affliate_bonuses` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `refer_id` int NOT NULL,
  `bonus` double NOT NULL DEFAULT '0',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `customer_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `alternatives`
-- ============================================================
CREATE TABLE `alternatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) DEFAULT NULL,
  `alternative` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `arrival_sections`
-- ============================================================
CREATE TABLE `arrival_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `header` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `photo` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `up_sale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` timestamp(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `attribute_options`
-- ============================================================
CREATE TABLE `attribute_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attribute_id` int DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `attributes`
-- ============================================================
CREATE TABLE `attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attributable_id` int DEFAULT NULL,
  `attributable_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `input_name` varchar(255) DEFAULT NULL,
  `price_status` int NOT NULL DEFAULT '1' COMMENT '0 - hide, 1- show	',
  `details_status` int NOT NULL DEFAULT '1' COMMENT '0 - hide, 1- show	',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `banners`
-- ============================================================
CREATE TABLE `banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Large','TopSmall','BottomSmall') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `blog_categories`
-- ============================================================
CREATE TABLE `blog_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `blogs`
-- ============================================================
CREATE TABLE `blogs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `views` int NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '1',
  `meta_tag` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `brand_qualities`
-- ============================================================
CREATE TABLE `brand_qualities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_brand_qualities_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `brand_regions`
-- ============================================================
CREATE TABLE `brand_regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint unsigned NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `brand_regions_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_brand_regions_brand_id` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `brands`
-- ============================================================
CREATE TABLE `brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ar` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `callouts`
-- ============================================================
CREATE TABLE `callouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `illustration_id` bigint unsigned DEFAULT NULL,
  `callout_type` enum('part','hardware','section','basic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callout_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applicable` tinyint(1) DEFAULT '0',
  `selective_fit` tinyint(1) DEFAULT '0',
  `rectangle_top` int DEFAULT NULL,
  `rectangle_left` int DEFAULT NULL,
  `rectangle_right` int DEFAULT NULL,
  `rectangle_bottom` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `illustration_id` (`illustration_id`),
  KEY `idx_callouts_illustration_type` (`illustration_id`,`callout_type`),
  CONSTRAINT `fk_callouts_illustration_id` FOREIGN KEY (`illustration_id`) REFERENCES `illustrations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `catalogs`
-- ============================================================
CREATE TABLE `catalogs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `new_id` int DEFAULT '0',
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_id` bigint unsigned DEFAULT NULL,
  `brand_region_id` bigint unsigned DEFAULT NULL,
  `label_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort` int DEFAULT '0',
  `beginDate` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endDate` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beginYear` int NOT NULL,
  `endYear` int NOT NULL,
  `dateRangeDescription` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicleType` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shortName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hasNotes` tinyint(1) DEFAULT NULL,
  `models` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `hasModels` tinyint(1) DEFAULT NULL,
  `imagePath` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `largeImagePath` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `brand_id` (`brand_id`),
  KEY `brand_region_id` (`brand_region_id`),
  CONSTRAINT `catalogs_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `catalogs_ibfk_2` FOREIGN KEY (`brand_region_id`) REFERENCES `brand_regions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_catalogs_brand_id` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `fk_catalogs_brand_region_id` FOREIGN KEY (`brand_region_id`) REFERENCES `brand_regions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `categories`
-- ============================================================
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_categories_is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `category_periods`
-- ============================================================
CREATE TABLE `category_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `begin_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_period` (`category_id`,`begin_date`,`end_date`),
  KEY `idx_cp_category_dates` (`category_id`,`begin_date`,`end_date`),
  CONSTRAINT `category_periods_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `newcategories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `category_spec_group_items`
-- ============================================================
CREATE TABLE `category_spec_group_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `specification_item_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_group_item` (`group_id`,`specification_item_id`),
  KEY `specification_item_id` (`specification_item_id`),
  KEY `idx_csgi_group_spec` (`group_id`,`specification_item_id`),
  CONSTRAINT `category_spec_group_items_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `category_spec_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_spec_group_items_ibfk_2` FOREIGN KEY (`specification_item_id`) REFERENCES `specification_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `category_spec_groups`
-- ============================================================
CREATE TABLE `category_spec_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `group_index` int unsigned DEFAULT '0',
  `category_period_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cat_spec_group` (`category_id`,`catalog_id`,`group_index`,`category_period_id`),
  KEY `catalog_id` (`catalog_id`),
  KEY `category_period_id` (`category_period_id`),
  KEY `idx_csg_category_catalog` (`category_id`,`catalog_id`),
  CONSTRAINT `category_spec_groups_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `newcategories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_spec_groups_ibfk_2` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_spec_groups_ibfk_3` FOREIGN KEY (`category_period_id`) REFERENCES `category_periods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `childcategories`
-- ============================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `cities`
-- ============================================================
CREATE TABLE `cities` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `state_id` int NOT NULL,
  `city_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `city_name_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `country_id` int NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `tryoto_supported` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cities_country_id_tryoto_supported_index` (`country_id`,`tryoto_supported`),
  KEY `cities_latitude_longitude_index` (`latitude`,`longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `cities_new`
-- ============================================================
CREATE TABLE `cities_new` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `city_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_id` mediumint unsigned NOT NULL,
  `state_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `country_id` mediumint unsigned NOT NULL,
  `country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IANA timezone identifier (e.g., America/New_York)',
  `created_at` timestamp NOT NULL DEFAULT '2014-01-01 12:01:01',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `flag` tinyint(1) NOT NULL DEFAULT '1',
  `wikiDataId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  PRIMARY KEY (`id`),
  KEY `cities_test_ibfk_1` (`state_id`),
  KEY `cities_test_ibfk_2` (`country_id`),
  CONSTRAINT `cities_new_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states_new` (`id`),
  CONSTRAINT `cities_new_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries_new` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `comments`
-- ============================================================
CREATE TABLE `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `merchant_product_id` bigint unsigned DEFAULT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `conversations`
-- ============================================================
CREATE TABLE `conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_user` int NOT NULL,
  `recieved_user` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `counters`
-- ============================================================
CREATE TABLE `counters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('referral','browser') NOT NULL DEFAULT 'referral',
  `referral` varchar(255) DEFAULT NULL,
  `total_count` int NOT NULL DEFAULT '0',
  `todays_count` int NOT NULL DEFAULT '0',
  `today` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `countries`
-- ============================================================
CREATE TABLE `countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) NOT NULL DEFAULT '',
  `country_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `country_name_ar` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `tax` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `is_synced` tinyint(1) NOT NULL DEFAULT '0',
  `synced_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `countries_new`
-- ============================================================
CREATE TABLE `countries_new` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `numeric_code` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_full_code` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phonecode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capital` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_symbol` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tld` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `native` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region_id` mediumint unsigned DEFAULT NULL,
  `subregion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subregion_id` mediumint unsigned DEFAULT NULL,
  `nationality` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `translations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `emoji` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emojiU` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `flag` tinyint(1) NOT NULL DEFAULT '1',
  `wikiDataId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  PRIMARY KEY (`id`),
  KEY `country_continent` (`region_id`),
  KEY `country_subregion` (`subregion_id`),
  CONSTRAINT `country_continent_final` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`),
  CONSTRAINT `country_subregion_final` FOREIGN KEY (`subregion_id`) REFERENCES `subregions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `coupons`
-- ============================================================
CREATE TABLE `coupons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` tinyint NOT NULL,
  `price` double NOT NULL,
  `times` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `used` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '1',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `coupon_type` varchar(255) DEFAULT NULL,
  `category` int DEFAULT NULL,
  `sub_category` int DEFAULT NULL,
  `child_category` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `currencies`
-- ============================================================
CREATE TABLE `currencies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sign` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` double NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `delivery_riders`
-- ============================================================
CREATE TABLE `delivery_riders` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `vendor_id` int DEFAULT NULL,
  `rider_id` int DEFAULT NULL,
  `pickup_point_id` int DEFAULT NULL,
  `service_area_id` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `deposits`
-- ============================================================
CREATE TABLE `deposits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `deposit_number` varchar(255) DEFAULT NULL,
  `currency` blob,
  `currency_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` double DEFAULT '0',
  `currency_value` double DEFAULT '0',
  `method` varchar(255) DEFAULT NULL,
  `txnid` varchar(255) DEFAULT NULL,
  `flutter_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `email_templates`
-- ============================================================
CREATE TABLE `email_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email_subject` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `email_body` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `failed_jobs`
-- ============================================================
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `faqs`
-- ============================================================
CREATE TABLE `faqs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `favorite_sellers`
-- ============================================================
CREATE TABLE `favorite_sellers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `vendor_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `fonts`
-- ============================================================
CREATE TABLE `fonts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_default` tinyint DEFAULT '0',
  `font_family` varchar(100) DEFAULT NULL,
  `font_value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `galleries`
-- ============================================================
CREATE TABLE `galleries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `generalsettings`
-- ============================================================
CREATE TABLE `generalsettings` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `logo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sign` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colors` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme_primary` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#c3002f',
  `theme_primary_hover` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#a00025',
  `theme_primary_dark` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#8a0020',
  `theme_primary_light` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#fef2f4',
  `theme_secondary` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#1a1a1a',
  `theme_secondary_hover` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#333333',
  `loader` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_loader` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_talkto` tinyint(1) NOT NULL DEFAULT '1',
  `talkto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_language` tinyint(1) NOT NULL DEFAULT '1',
  `is_loader` tinyint(1) NOT NULL DEFAULT '1',
  `is_disqus` tinyint(1) NOT NULL DEFAULT '0',
  `disqus` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `guest_checkout` tinyint(1) NOT NULL DEFAULT '0',
  `currency_format` tinyint(1) NOT NULL DEFAULT '0',
  `withdraw_fee` double NOT NULL DEFAULT '0',
  `withdraw_charge` double NOT NULL DEFAULT '0',
  `shipping_cost` double NOT NULL DEFAULT '0',
  `mail_driver` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_host` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_port` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_encryption` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_user` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_pass` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_smtp` tinyint(1) NOT NULL DEFAULT '0',
  `is_comment` tinyint(1) NOT NULL DEFAULT '1',
  `is_currency` tinyint(1) NOT NULL DEFAULT '1',
  `is_affilate` tinyint(1) NOT NULL DEFAULT '1',
  `affilate_charge` int NOT NULL DEFAULT '0',
  `affilate_banner` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fixed_commission` double NOT NULL DEFAULT '0',
  `percentage_commission` double NOT NULL DEFAULT '0',
  `multiple_shipping` tinyint(1) NOT NULL DEFAULT '0',
  `multiple_packaging` tinyint NOT NULL DEFAULT '0',
  `vendor_ship_info` tinyint(1) NOT NULL DEFAULT '0',
  `reg_vendor` tinyint(1) NOT NULL DEFAULT '0',
  `footer_color` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `copyright_color` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `copyright` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_admin_loader` tinyint(1) NOT NULL DEFAULT '0',
  `is_verification_email` tinyint(1) NOT NULL DEFAULT '0',
  `wholesell` int NOT NULL DEFAULT '0',
  `is_capcha` tinyint(1) NOT NULL DEFAULT '0',
  `capcha_secret_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capcha_site_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_banner_404` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_banner_500` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_popup` tinyint(1) NOT NULL DEFAULT '0',
  `popup_background` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `breadcrumb_banner` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_logo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_image` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_color` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_secure` tinyint(1) NOT NULL DEFAULT '0',
  `is_report` tinyint(1) NOT NULL,
  `footer_logo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `show_stock` tinyint(1) NOT NULL DEFAULT '0',
  `is_maintain` tinyint(1) NOT NULL DEFAULT '0',
  `header_color` enum('light','dark') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `maintain_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_buy_now` tinyint NOT NULL,
  `version` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affilate_product` tinyint(1) NOT NULL DEFAULT '1',
  `verify_product` tinyint(1) NOT NULL DEFAULT '0',
  `page_count` int NOT NULL DEFAULT '0',
  `flash_count` int NOT NULL DEFAULT '0',
  `hot_count` int NOT NULL DEFAULT '0',
  `new_count` int NOT NULL DEFAULT '0',
  `sale_count` int NOT NULL DEFAULT '0',
  `best_seller_count` int NOT NULL DEFAULT '0',
  `popular_count` int NOT NULL DEFAULT '0',
  `top_rated_count` int NOT NULL DEFAULT '0',
  `big_save_count` int NOT NULL DEFAULT '0',
  `trending_count` int NOT NULL DEFAULT '0',
  `seller_product_count` int NOT NULL DEFAULT '0',
  `wishlist_count` int NOT NULL DEFAULT '0',
  `vendor_page_count` int NOT NULL DEFAULT '0',
  `min_price` double NOT NULL DEFAULT '0',
  `max_price` double NOT NULL DEFAULT '0',
  `post_count` tinyint(1) NOT NULL DEFAULT '0',
  `product_page` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `wishlist_page` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_contact_seller` tinyint(1) NOT NULL DEFAULT '0',
  `is_debug` tinyint(1) NOT NULL DEFAULT '0',
  `decimal_separator` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thousand_separator` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_cookie` tinyint(1) NOT NULL DEFAULT '0',
  `product_affilate` tinyint(1) NOT NULL DEFAULT '0',
  `product_affilate_bonus` int NOT NULL DEFAULT '0',
  `is_reward` int NOT NULL DEFAULT '0',
  `reward_point` int NOT NULL DEFAULT '0',
  `reward_dolar` int NOT NULL DEFAULT '0',
  `physical` tinyint NOT NULL DEFAULT '1',
  `digital` tinyint NOT NULL DEFAULT '1',
  `license` tinyint NOT NULL DEFAULT '1',
  `listing` tinyint NOT NULL DEFAULT '1',
  `affilite` tinyint NOT NULL DEFAULT '1',
  `partner_title` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `partner_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deal_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deal_details` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deal_time` date DEFAULT NULL,
  `deal_background` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'theme4',
  `facebook_pixel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme_secondary_light` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#4c3533',
  `theme_text_primary` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#1f0300',
  `theme_text_secondary` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#4c3533',
  `theme_text_muted` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#796866',
  `theme_text_light` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#9a8e8c',
  `theme_bg_body` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_bg_light` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f8f7f7',
  `theme_bg_gray` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#e9e6e6',
  `theme_bg_dark` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#030712',
  `theme_success` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#27be69',
  `theme_warning` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#fac03c',
  `theme_danger` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f2415a',
  `theme_info` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#0ea5e9',
  `theme_border` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#d9d4d4',
  `theme_border_light` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#e9e6e6',
  `theme_border_dark` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#c7c0bf',
  `theme_header_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_footer_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#030712',
  `theme_footer_text` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_footer_link_hover` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#c3002f',
  `theme_font_primary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Poppins',
  `theme_font_heading` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Saira',
  `theme_font_size_base` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '16px',
  `theme_font_size_sm` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '14px',
  `theme_font_size_lg` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '18px',
  `theme_line_height` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '1.5',
  `theme_radius_xs` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '3px',
  `theme_radius_sm` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '4px',
  `theme_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '8px',
  `theme_radius_lg` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '12px',
  `theme_radius_xl` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '16px',
  `theme_radius_pill` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '50px',
  `theme_shadow_xs` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 1px 2px rgba(0,0,0,0.04)',
  `theme_shadow_sm` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 1px 3px rgba(0,0,0,0.06)',
  `theme_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 2px 8px rgba(0,0,0,0.1)',
  `theme_shadow_lg` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 4px 16px rgba(0,0,0,0.15)',
  `theme_shadow_xl` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 8px 30px rgba(0,0,0,0.2)',
  `theme_spacing_xs` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '4px',
  `theme_spacing_sm` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '8px',
  `theme_spacing` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '16px',
  `theme_spacing_lg` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '24px',
  `theme_spacing_xl` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '32px',
  `theme_btn_padding_x` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '24px',
  `theme_btn_padding_y` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '12px',
  `theme_btn_font_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '14px',
  `theme_btn_font_weight` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '600',
  `theme_btn_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '8px',
  `theme_btn_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `theme_card_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_card_border` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#e9e6e6',
  `theme_card_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '12px',
  `theme_card_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 2px 8px rgba(0,0,0,0.08)',
  `theme_card_hover_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 4px 16px rgba(0,0,0,0.12)',
  `theme_card_padding` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '20px',
  `theme_header_height` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '80px',
  `theme_header_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 2px 10px rgba(0,0,0,0.1)',
  `theme_header_text` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#1f0300',
  `theme_nav_link_color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#1f0300',
  `theme_nav_link_hover` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#c3002f',
  `theme_nav_font_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '15px',
  `theme_nav_font_weight` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '500',
  `theme_footer_padding` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '60px',
  `theme_footer_text_muted` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#d9d4d4',
  `theme_footer_link` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_footer_border` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#374151',
  `theme_product_title_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '14px',
  `theme_product_title_weight` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '500',
  `theme_product_price_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '16px',
  `theme_product_price_weight` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '700',
  `theme_product_card_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '12px',
  `theme_product_img_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '8px',
  `theme_product_hover_scale` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '1.02',
  `theme_modal_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_modal_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '16px',
  `theme_modal_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 25px 50px rgba(0,0,0,0.25)',
  `theme_modal_backdrop` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'rgba(0,0,0,0.5)',
  `theme_modal_header_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f8f7f7',
  `theme_table_header_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f8f7f7',
  `theme_table_header_text` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#1f0300',
  `theme_table_border` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#e9e6e6',
  `theme_table_hover_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f8f7f7',
  `theme_table_stripe_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#fafafa',
  `theme_input_height` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '48px',
  `theme_input_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_input_border` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#d9d4d4',
  `theme_input_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '8px',
  `theme_input_focus_border` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#c3002f',
  `theme_input_focus_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 0 0 3px rgba(195,0,47,0.1)',
  `theme_input_placeholder` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#9a8e8c',
  `theme_badge_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '20px',
  `theme_badge_padding` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '4px 12px',
  `theme_badge_font_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '12px',
  `theme_badge_font_weight` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '600',
  `theme_chip_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f8f7f7',
  `theme_chip_text` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#4c3533',
  `theme_chip_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '6px',
  `theme_chip_border` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#e9e6e6',
  `theme_scrollbar_width` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '10px',
  `theme_scrollbar_track` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f1f1f1',
  `theme_scrollbar_thumb` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#c1c1c1',
  `theme_scrollbar_thumb_hover` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#a1a1a1',
  `theme_transition_fast` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'all 0.15s ease',
  `theme_transition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'all 0.3s ease',
  `theme_transition_slow` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'all 0.5s ease',
  `theme_search_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_search_border` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#e9e6e6',
  `theme_search_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '50px',
  `theme_search_height` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '50px',
  `theme_search_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 4px 15px rgba(0,0,0,0.08)',
  `theme_category_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `theme_category_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '12px',
  `theme_category_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 2px 8px rgba(0,0,0,0.08)',
  `theme_category_hover_shadow` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0 8px 25px rgba(0,0,0,0.15)',
  `theme_pagination_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '40px',
  `theme_pagination_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '8px',
  `theme_pagination_gap` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '5px',
  `theme_alert_radius` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '8px',
  `theme_alert_padding` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '16px 20px',
  `theme_breadcrumb_bg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#f8f7f7',
  `theme_breadcrumb_separator` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '/',
  `theme_breadcrumb_text` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#796866',
  `theme_facebook` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#1877f2',
  `theme_twitter` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#1da1f2',
  `theme_instagram` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#e4405f',
  `theme_whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#25d366',
  `theme_youtube` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ff0000',
  `theme_linkedin` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#0a66c2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `illustrations`
-- ============================================================
CREATE TABLE `illustrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `data_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_illustration` (`section_id`,`category_id`,`code`,`data_code`),
  KEY `category_id` (`category_id`),
  KEY `idx_illustrations_section_code` (`section_id`,`code`),
  CONSTRAINT `fk_illustrations_category` FOREIGN KEY (`category_id`) REFERENCES `newcategories` (`id`),
  CONSTRAINT `fk_illustrations_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `imported_files`
-- ============================================================
CREATE TABLE `imported_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `imported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_file_name` (`file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `languages`
-- ============================================================
CREATE TABLE `languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `language` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `rtl` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `licenses`
-- ============================================================
CREATE TABLE `licenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `license_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','expired','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  `license_type` enum('standard','extended','developer','unlimited') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `max_domains` int NOT NULL DEFAULT '1',
  `used_domains` int NOT NULL DEFAULT '0',
  `activated_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `features` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `licenses_license_key_unique` (`license_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `merchant_products`
-- ============================================================
CREATE TABLE `merchant_products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `brand_quality_id` bigint unsigned NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `previous_price` decimal(10,2) DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT NULL COMMENT 'Product weight in kg (overrides product.weight)',
  `length` decimal(10,2) DEFAULT NULL COMMENT 'Product length in cm (overrides product.length)',
  `width` decimal(10,2) DEFAULT NULL COMMENT 'Product width in cm (overrides product.width)',
  `height` decimal(10,2) DEFAULT NULL COMMENT 'Product height in cm (overrides product.height)',
  `is_discount` tinyint(1) DEFAULT '0',
  `discount_date` date DEFAULT NULL,
  `whole_sell_qty` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `whole_sell_discount` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `preordered` tinyint(1) DEFAULT '0',
  `minimum_qty` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock_check` int DEFAULT '0',
  `popular` int DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_popular` int NOT NULL DEFAULT '0',
  `licence_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_qty` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `license` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ship` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_condition` tinyint(1) NOT NULL DEFAULT '2',
  `color_all` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `color_price` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `policy` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `features` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `colors` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `size` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_qty` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_price` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_product_user` (`product_id`,`user_id`,`brand_quality_id`),
  KEY `fk_mp_user` (`user_id`),
  KEY `idx_mp_brand_quality` (`brand_quality_id`),
  KEY `idx_mp_product_status` (`product_id`,`status`),
  KEY `idx_mp_status` (`status`),
  CONSTRAINT `fk_mp_brand_quality` FOREIGN KEY (`brand_quality_id`) REFERENCES `brand_qualities` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `messages`
-- ============================================================
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_user` int DEFAULT NULL,
  `recieved_user` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `migrations`
-- ============================================================
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `newcategories`
-- ============================================================
CREATE TABLE `newcategories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint unsigned NOT NULL,
  `brand_region_id` bigint unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `level` tinyint unsigned NOT NULL,
  `spec_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parents_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `formatted_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Applicability` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `label_ar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_newcategories_unique` (`parent_id`,`brand_region_id`,`catalog_id`,`full_code`),
  KEY `brand_region_id` (`brand_region_id`),
  KEY `brand_id` (`brand_id`),
  KEY `catalog_id` (`catalog_id`),
  KEY `idx_newcategories_level_fullcode` (`level`,`full_code`(50)),
  KEY `idx_nc_catalog_brand_level` (`catalog_id`,`brand_id`,`level`),
  KEY `idx_nc_full_code` (`full_code`),
  KEY `idx_nc_spec_key` (`catalog_id`,`spec_key`,`level`),
  CONSTRAINT `fk_nc_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `fk_nc_brand_region` FOREIGN KEY (`brand_region_id`) REFERENCES `brand_regions` (`id`),
  CONSTRAINT `fk_nc_catalog` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`),
  CONSTRAINT `fk_nc_parent` FOREIGN KEY (`parent_id`) REFERENCES `newcategories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `nissan_credentials`
-- ============================================================
CREATE TABLE `nissan_credentials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cookie` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `notifications`
-- ============================================================
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `vendor_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `conversation_id` int DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `order_tracks`
-- ============================================================
CREATE TABLE `order_tracks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `orders`
-- ============================================================
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `cart` json NOT NULL,
  `method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `totalQty` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pay_amount` double NOT NULL,
  `txnid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charge_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `customer_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `customer_country` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `customer_city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `customer_zip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `shipping_country` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `shipping_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `shipping_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `shipping_city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `shipping_zip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `order_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `coupon_code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','completed','declined','on delivery') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `affilate_user` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affilate_charge` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_sign` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_name` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_value` double NOT NULL,
  `shipping_cost` double NOT NULL DEFAULT '0',
  `packing_cost` double NOT NULL DEFAULT '0',
  `tax` double NOT NULL,
  `tax_location` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dp` tinyint(1) NOT NULL DEFAULT '0',
  `pay_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `vendor_shipping_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_packing_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `wallet_price` double NOT NULL DEFAULT '0',
  `is_shipping` tinyint NOT NULL DEFAULT '1',
  `shipping_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `customer_shipping_choice` json DEFAULT NULL COMMENT 'Customer selected shipping company data per vendor',
  `shipping_status` json DEFAULT NULL COMMENT 'Shipping status per vendor with tracking info',
  `packing_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `customer_state` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_state` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount` int NOT NULL DEFAULT '0',
  `affilate_users` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `commission` double NOT NULL DEFAULT '0',
  `riders` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `packages`
-- ============================================================
CREATE TABLE `packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `subtitle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `price` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `pages`
-- ============================================================
CREATE TABLE `pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `meta_tag` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `header` tinyint(1) NOT NULL DEFAULT '0',
  `footer` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `pagesettings`
-- ============================================================
CREATE TABLE `pagesettings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `contact_email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `phone` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fax` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `site` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `best_seller_banner` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `best_seller_banner_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `big_save_banner` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `big_save_banner_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `best_seller_banner1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `best_seller_banner_link1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `big_save_banner1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `big_save_banner_link1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `big_save_banner_subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `big_save_banner_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `big_save_banner_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rightbanner1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rightbanner2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rightbannerlink1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rightbannerlink2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `home` tinyint(1) NOT NULL DEFAULT '0',
  `blog` tinyint(1) NOT NULL DEFAULT '0',
  `faq` tinyint(1) NOT NULL DEFAULT '0',
  `contact` tinyint(1) NOT NULL DEFAULT '0',
  `category` tinyint(1) NOT NULL DEFAULT '0',
  `arrival_section` tinyint(1) NOT NULL DEFAULT '1',
  `our_services` tinyint(1) NOT NULL DEFAULT '1',
  `slider` tinyint(1) NOT NULL DEFAULT '0',
  `partner` tinyint(1) NOT NULL DEFAULT '1',
  `top_big_trending` tinyint(1) NOT NULL DEFAULT '0',
  `top_banner` int NOT NULL DEFAULT '1',
  `large_banner` int NOT NULL DEFAULT '1',
  `bottom_banner` int NOT NULL DEFAULT '1',
  `best_selling` int NOT NULL DEFAULT '1',
  `newsletter` int NOT NULL DEFAULT '1',
  `deal_of_the_day` int NOT NULL DEFAULT '1',
  `best_sellers` tinyint NOT NULL DEFAULT '1',
  `third_left_banner` int NOT NULL,
  `popular_products` tinyint NOT NULL DEFAULT '1',
  `flash_deal` tinyint NOT NULL DEFAULT '1',
  `top_brand` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `part_extensions_y50gl`
-- ============================================================
CREATE TABLE `part_extensions_y50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `section_id` bigint unsigned NOT NULL,
  `group_id` bigint unsigned NOT NULL,
  `extension_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_period_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pe_group` (`group_id`),
  KEY `idx_pe_key` (`extension_key`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_extension_key` (`extension_key`(50)),
  KEY `idx_part_section_group` (`part_id`,`section_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `part_periods_r50gl`
-- ============================================================
CREATE TABLE `part_periods_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `begin_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_part_period` (`part_id`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_dates` (`begin_date`,`end_date`),
  KEY `idx_pp_dates_r50gl` (`begin_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `part_periods_y61gl`
-- ============================================================
CREATE TABLE `part_periods_y61gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `begin_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_part_period` (`part_id`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_dates` (`begin_date`,`end_date`),
  KEY `idx_pp_dates_y61gl` (`begin_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `part_spec_group_items_r50gl`
-- ============================================================
CREATE TABLE `part_spec_group_items_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `specification_item_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psGi_group` (`group_id`),
  KEY `idx_psGi_spec_item` (`specification_item_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_specification_item_id` (`specification_item_id`),
  KEY `idx_psgi_group_id_r50gl` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `part_spec_group_items_y61gl`
-- ============================================================
CREATE TABLE `part_spec_group_items_y61gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `specification_item_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psGi_group` (`group_id`),
  KEY `idx_psGi_spec_item` (`specification_item_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_specification_item_id` (`specification_item_id`),
  KEY `idx_psgi_group_id_y61gl` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `part_spec_groups_r50gl`
-- ============================================================
CREATE TABLE `part_spec_groups_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `section_id` bigint unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `group_index` int NOT NULL,
  `part_period_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psG_part_section` (`part_id`,`section_id`),
  KEY `idx_psG_group_lookup` (`part_id`,`section_id`,`catalog_id`,`category_id`,`group_index`,`part_period_id`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_catalog_id` (`catalog_id`),
  KEY `idx_part_period_id` (`part_period_id`),
  KEY `idx_part_section_catalog` (`part_id`,`section_id`,`catalog_id`),
  KEY `idx_psg_part_section_catalog_r50gl` (`part_id`,`section_id`,`catalog_id`),
  KEY `idx_psg_part_id_r50gl` (`part_id`),
  KEY `idx_psg_section_id_r50gl` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `part_spec_groups_y61gl`
-- ============================================================
CREATE TABLE `part_spec_groups_y61gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `section_id` bigint unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `group_index` int NOT NULL,
  `part_period_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psG_part_section` (`part_id`,`section_id`),
  KEY `idx_psG_group_lookup` (`part_id`,`section_id`,`catalog_id`,`category_id`,`group_index`,`part_period_id`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_catalog_id` (`catalog_id`),
  KEY `idx_part_period_id` (`part_period_id`),
  KEY `idx_part_section_catalog` (`part_id`,`section_id`,`catalog_id`),
  KEY `idx_psg_part_section_catalog_y61gl` (`part_id`,`section_id`,`catalog_id`),
  KEY `idx_psg_part_id_y61gl` (`part_id`),
  KEY `idx_psg_section_id_y61gl` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `parts_index`
-- ============================================================
CREATE TABLE `parts_index` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catalog_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `brand_id` bigint unsigned NOT NULL,
  `is_synced` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `subcategory_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `part_number` (`part_number`),
  KEY `catalog_code` (`catalog_code`),
  KEY `catalog_id` (`catalog_id`),
  KEY `brand_id` (`brand_id`),
  KEY `is_synced` (`is_synced`),
  KEY `idx_pi_part_number` (`part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `parts_r50gl`
-- ============================================================
CREATE TABLE `parts_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int DEFAULT '1',
  `callout` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_part` (`part_number`,`label_en`,`qty`,`callout`),
  KEY `idx_part_number` (`part_number`(50)),
  KEY `idx_callout` (`callout`(50)),
  KEY `idx_label_en` (`label_en`(100)),
  KEY `idx_label_ar` (`label_ar`(100)),
  KEY `idx_part_callout` (`part_number`(50),`callout`(50)),
  KEY `idx_p_callout_r50gl` (`callout`),
  KEY `idx_p_part_number_r50gl` (`part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `parts_y61gl`
-- ============================================================
CREATE TABLE `parts_y61gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int DEFAULT '1',
  `callout` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_part` (`part_number`,`label_en`,`qty`,`callout`),
  KEY `idx_p_callout` (`callout`),
  KEY `idx_part_number` (`part_number`(50)),
  KEY `idx_callout` (`callout`(50)),
  KEY `idx_label_en` (`label_en`(100)),
  KEY `idx_label_ar` (`label_ar`(100)),
  KEY `idx_part_callout` (`part_number`(50),`callout`(50)),
  KEY `idx_p_part_number_y61gl` (`part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `password_reset_tokens`
-- ============================================================
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `payment_gateways`
-- ============================================================
CREATE TABLE `payment_gateways` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `subtitle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('manual','automatic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'manual',
  `information` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `keyword` varchar(191) DEFAULT NULL,
  `currency_id` varchar(191) NOT NULL DEFAULT '*',
  `checkout` int NOT NULL DEFAULT '1',
  `deposit` int NOT NULL DEFAULT '1',
  `subscription` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `personal_access_tokens`
-- ============================================================
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `pickup_points`
-- ============================================================
CREATE TABLE `pickup_points` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `pickups`
-- ============================================================
CREATE TABLE `pickups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `location` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `product_clicks`
-- ============================================================
CREATE TABLE `product_clicks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `merchant_product_id` int unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_clicks_merchant_product_id_index` (`merchant_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `product_fitments`
-- ============================================================
CREATE TABLE `product_fitments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `category_id` int NOT NULL,
  `subcategory_id` int NOT NULL,
  `childcategory_id` int NOT NULL,
  `rol` tinyint unsigned DEFAULT NULL,
  `beginYear` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prod_cat_sub_child` (`product_id`,`category_id`,`subcategory_id`,`childcategory_id`),
  KEY `idx_cat` (`category_id`),
  KEY `idx_sub` (`subcategory_id`),
  KEY `idx_child` (`childcategory_id`),
  CONSTRAINT `fk_pf_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pf_child` FOREIGN KEY (`childcategory_id`) REFERENCES `childcategories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pf_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pf_sub` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `products`
-- ============================================================
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int NOT NULL DEFAULT '2',
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_type` enum('normal','affiliate') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `affiliate_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int unsigned NOT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `childcategory_id` int unsigned DEFAULT NULL,
  `label_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LargePNG/SVG/noimage.png',
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SmallPNG/SVG/noimage.png',
  `file` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT '1.00',
  `policy` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `views` int unsigned NOT NULL DEFAULT '0',
  `tags` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_meta` tinyint(1) NOT NULL DEFAULT '0',
  `meta_tag` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `youtube` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Physical','Digital','License','Listing') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `platform` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `measure` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `featured` tinyint unsigned NOT NULL DEFAULT '0',
  `best` tinyint unsigned NOT NULL DEFAULT '0',
  `top` tinyint unsigned NOT NULL DEFAULT '0',
  `hot` tinyint unsigned NOT NULL DEFAULT '0',
  `latest` tinyint unsigned NOT NULL DEFAULT '0',
  `big` tinyint unsigned NOT NULL DEFAULT '0',
  `trending` tinyint(1) NOT NULL DEFAULT '0',
  `sale` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_catalog` tinyint(1) NOT NULL DEFAULT '0',
  `catalog_id` int NOT NULL DEFAULT '0',
  `cross_products` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `length` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL COMMENT 'Product width in cm for volumetric weight calculation',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sku` (`sku`) USING BTREE,
  KEY `idx_products_sku` (`sku`),
  KEY `idx_products_category_id` (`category_id`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `attributes` (`attributes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `ratings`
-- ============================================================
CREATE TABLE `ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `merchant_product_id` int unsigned DEFAULT NULL,
  `review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rating` tinyint NOT NULL,
  `review_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ratings_merchant_product_id_index` (`merchant_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `replies`
-- ============================================================
CREATE TABLE `replies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `comment_id` int unsigned NOT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `reports`
-- ============================================================
CREATE TABLE `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `merchant_product_id` int unsigned DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reports_merchant_product_id_index` (`merchant_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `reviews`
-- ============================================================
CREATE TABLE `reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `rewards`
-- ============================================================
CREATE TABLE `rewards` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_amount` double NOT NULL DEFAULT '0',
  `reward` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `rider_service_areas`
-- ============================================================
CREATE TABLE `rider_service_areas` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `rider_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `price` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `riders`
-- ============================================================
CREATE TABLE `riders` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_verify` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'No',
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `balance` double DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `roles`
-- ============================================================
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `section_parts_r50gl`
-- ============================================================
CREATE TABLE `section_parts_r50gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_section_part` (`section_id`,`part_id`,`category_id`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_section_part` (`section_id`,`part_id`),
  KEY `idx_sp_section_part_r50gl` (`section_id`,`part_id`),
  KEY `idx_sp_part_id_r50gl` (`part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `section_parts_y61gl`
-- ============================================================
CREATE TABLE `section_parts_y61gl` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_section_part` (`section_id`,`part_id`,`category_id`),
  KEY `idx_part_id` (`part_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_section_part` (`section_id`,`part_id`),
  KEY `idx_sp_section_part_y61gl` (`section_id`,`part_id`),
  KEY `idx_sp_part_id_y61gl` (`part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `sections`
-- ============================================================
CREATE TABLE `sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `formatted_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalog_id` (`catalog_id`,`code`),
  KEY `category_id` (`category_id`),
  KEY `idx_sections_full_code` (`full_code`),
  KEY `idx_sections_id_code` (`id`,`full_code`),
  KEY `idx_sections_id_full_code` (`id`,`full_code`),
  KEY `idx_sections_category_catalog` (`category_id`,`catalog_id`),
  KEY `idx_sections_full_code_catalog` (`full_code`,`catalog_id`),
  CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sections_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `newcategories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `seotools`
-- ============================================================
CREATE TABLE `seotools` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `google_analytics` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `facebook_pixel` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_keys` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `services`
-- ============================================================
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `settings`
-- ============================================================
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `shipment_status_logs`
-- ============================================================
CREATE TABLE `shipment_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `vendor_id` bigint unsigned DEFAULT NULL,
  `tracking_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipment_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `status_ar` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `message_ar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status_date` timestamp NULL DEFAULT NULL,
  `raw_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `shippings`
-- ============================================================
CREATE TABLE `shippings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `provider` varchar(50) NOT NULL DEFAULT 'manual',
  `title` text,
  `subtitle` text,
  `price` double NOT NULL DEFAULT '0',
  `free_above` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_shippings_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `sku_alternative_item`
-- ============================================================
CREATE TABLE `sku_alternative_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `a_id` bigint unsigned NOT NULL,
  `b_id` bigint unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_edge` (`a_id`,`b_id`),
  KEY `idx_a_id` (`a_id`),
  KEY `idx_b_id` (`b_id`),
  CONSTRAINT `fk_edge_a` FOREIGN KEY (`a_id`) REFERENCES `sku_alternatives` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_edge_b` FOREIGN KEY (`b_id`) REFERENCES `sku_alternatives` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `sku_alternatives`
-- ============================================================
CREATE TABLE `sku_alternatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sku` (`sku`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_ska_sku` (`sku`),
  KEY `idx_ska_group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `sliders`
-- ============================================================
CREATE TABLE `sliders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `subtitle_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `subtitle_size` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle_color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle_anime` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `title_size` varchar(50) DEFAULT NULL,
  `title_color` varchar(50) DEFAULT NULL,
  `title_anime` varchar(50) DEFAULT NULL,
  `details_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `details_size` varchar(50) DEFAULT NULL,
  `details_color` varchar(50) DEFAULT NULL,
  `details_anime` varchar(50) DEFAULT NULL,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `social_links`
-- ============================================================
CREATE TABLE `social_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `social_providers`
-- ============================================================
CREATE TABLE `social_providers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `provider_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `socialsettings`
-- ============================================================
CREATE TABLE `socialsettings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `facebook` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gplus` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `twitter` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `linkedin` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dribble` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_status` tinyint NOT NULL DEFAULT '1',
  `g_status` tinyint NOT NULL DEFAULT '1',
  `t_status` tinyint NOT NULL DEFAULT '1',
  `l_status` tinyint NOT NULL DEFAULT '1',
  `d_status` tinyint NOT NULL DEFAULT '1',
  `f_check` tinyint DEFAULT NULL,
  `g_check` tinyint DEFAULT NULL,
  `fclient_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fclient_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fredirect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gclient_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gclient_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gredirect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `specification_items`
-- ============================================================
CREATE TABLE `specification_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `specification_id` int unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `value_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_spec_value` (`specification_id`,`catalog_id`,`value_id`),
  KEY `catalog_id` (`catalog_id`),
  KEY `idx_si_id_value` (`id`,`value_id`),
  KEY `idx_spec_items_id_value_spec` (`id`,`value_id`,`specification_id`),
  KEY `idx_si_catalog_spec` (`catalog_id`,`specification_id`),
  CONSTRAINT `specification_items_ibfk_1` FOREIGN KEY (`specification_id`) REFERENCES `specifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `specification_items_ibfk_2` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `specifications`
-- ============================================================
CREATE TABLE `specifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_specifications_id_name` (`id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `states`
-- ============================================================
CREATE TABLE `states` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `country_id` int NOT NULL DEFAULT '0',
  `state` varchar(111) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state_ar` varchar(111) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `tax` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  `owner_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `states_new`
-- ============================================================
CREATE TABLE `states_new` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_id` mediumint unsigned NOT NULL,
  `country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax` double NOT NULL DEFAULT '0',
  `fips_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `iso2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_id` int NOT NULL DEFAULT '0',
  `iso3166_2` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int DEFAULT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `native` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IANA timezone identifier (e.g., America/New_York)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `flag` tinyint(1) NOT NULL DEFAULT '1',
  `wikiDataId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  PRIMARY KEY (`id`),
  KEY `country_region` (`country_id`),
  CONSTRAINT `country_region_final` FOREIGN KEY (`country_id`) REFERENCES `countries_new` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `stock_all`
-- ============================================================
CREATE TABLE `stock_all` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_quality_id` int unsigned NOT NULL DEFAULT '1',
  `qty` int DEFAULT '0',
  `cost_price` decimal(18,4) DEFAULT '0.0000',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_all_part_unique` (`part_number`),
  UNIQUE KEY `uq_stock_all_sku_bq` (`sku`,`brand_quality_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `stock_patromin`
-- ============================================================
CREATE TABLE `stock_patromin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Discount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `stocks`
-- ============================================================
CREATE TABLE `stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_number` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` int unsigned NOT NULL,
  `location` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int DEFAULT '0',
  `sell_price` decimal(18,4) DEFAULT NULL,
  `comp_cost` decimal(18,4) DEFAULT NULL,
  `cost_price` decimal(18,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stocks_part_location_unique` (`part_number`,`location`),
  KEY `stocks_part_index` (`part_number`),
  KEY `stocks_branch_index` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `subcategories`
-- ============================================================
CREATE TABLE `subcategories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name_ar` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `beginYear` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `subcategories_old`
-- ============================================================
CREATE TABLE `subcategories_old` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `key2` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `subscribers`
-- ============================================================
CREATE TABLE `subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `subscriptions`
-- ============================================================
CREATE TABLE `subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double NOT NULL DEFAULT '0',
  `days` int NOT NULL,
  `allowed_products` int NOT NULL DEFAULT '0',
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `telescope_entries`
-- ============================================================
CREATE TABLE `telescope_entries` (
  `sequence` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `family_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `telescope_entries_tags`
-- ============================================================
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `telescope_monitoring`
-- ============================================================
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `token_logs`
-- ============================================================
CREATE TABLE `token_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `message` text,
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `tokens`
-- ============================================================
CREATE TABLE `tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `accessToken` text NOT NULL,
  `refreshToken` text NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `transactions`
-- ============================================================
CREATE TABLE `transactions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `untitled_table_1632`
-- ============================================================
CREATE TABLE `untitled_table_1632` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `user_notifications`
-- ============================================================
CREATE TABLE `user_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `user_subscriptions`
-- ============================================================
CREATE TABLE `user_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subscription_id` int NOT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_sign` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` double NOT NULL DEFAULT '0',
  `days` int NOT NULL,
  `allowed_products` int NOT NULL DEFAULT '0',
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Free',
  `txnid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charge_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flutter_id` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `payment_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `users`
-- ============================================================
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `address` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_provider` tinyint NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `verification_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email_verified` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `affilate_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `affilate_income` double NOT NULL DEFAULT '0',
  `shop_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `owner_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `shop_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `shop_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reg_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `shop_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `shop_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `shop_image` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `g_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `t_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `l_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_vendor` tinyint(1) NOT NULL DEFAULT '0',
  `f_check` tinyint(1) NOT NULL DEFAULT '0',
  `g_check` tinyint(1) NOT NULL DEFAULT '0',
  `t_check` tinyint(1) NOT NULL DEFAULT '0',
  `l_check` tinyint(1) NOT NULL DEFAULT '0',
  `mail_sent` tinyint(1) NOT NULL DEFAULT '0',
  `shipping_cost` double NOT NULL DEFAULT '0',
  `current_balance` double NOT NULL DEFAULT '0',
  `date` date DEFAULT NULL,
  `ban` tinyint(1) NOT NULL DEFAULT '0',
  `balance` double NOT NULL DEFAULT '0',
  `admin_commission` double NOT NULL DEFAULT '0',
  `reward` double NOT NULL DEFAULT '0',
  `email_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `vendor_orders`
-- ============================================================
CREATE TABLE `vendor_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` int NOT NULL,
  `qty` int NOT NULL,
  `price` double NOT NULL,
  `order_number` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','completed','declined','on delivery') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `vendor_stock_updates`
-- ============================================================
CREATE TABLE `vendor_stock_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT 'Vendor user ID',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Uploaded file name',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Uploaded file path',
  `update_type` enum('manual','automatic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `status` enum('pending','processing','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_rows` int NOT NULL DEFAULT '0',
  `updated_rows` int NOT NULL DEFAULT '0',
  `failed_rows` int NOT NULL DEFAULT '0',
  `error_log` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_stock_updates_user_id_index` (`user_id`),
  KEY `vendor_stock_updates_status_index` (`status`),
  KEY `vendor_stock_updates_update_type_index` (`update_type`),
  CONSTRAINT `vendor_stock_updates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `verifications`
-- ============================================================
CREATE TABLE `verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `attachments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Verified','Declined') DEFAULT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `admin_warning` tinyint(1) NOT NULL DEFAULT '0',
  `warning_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `vin_catalog_log`
-- ============================================================
CREATE TABLE `vin_catalog_log` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `vin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catalog_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matched_by` enum('direct','fallback','manual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'direct',
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vin_catalog` (`vin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `vin_decoded_cache`
-- ============================================================
CREATE TABLE `vin_decoded_cache` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_id` bigint unsigned DEFAULT NULL,
  `brand_region_id` bigint unsigned DEFAULT NULL,
  `catalog_id` bigint unsigned DEFAULT NULL,
  `catalogCode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelCode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buildDate` date DEFAULT NULL,
  `modelBeginDate` date DEFAULT NULL,
  `modelEndDate` date DEFAULT NULL,
  `shortName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catalogType` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dataRegion` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catMarket` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nmc_vehicleType` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vin_model_id` bigint unsigned DEFAULT NULL,
  `raw_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vin` (`vin`),
  KEY `idx_catalog_code` (`catalogCode`),
  KEY `idx_brand_region` (`brand_id`,`dataRegion`),
  KEY `brand_region_id` (`brand_region_id`),
  KEY `catalog_id` (`catalog_id`),
  KEY `vin_model_id` (`vin_model_id`),
  CONSTRAINT `vin_decoded_cache_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vin_decoded_cache_ibfk_2` FOREIGN KEY (`brand_region_id`) REFERENCES `brand_regions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vin_decoded_cache_ibfk_3` FOREIGN KEY (`catalog_id`) REFERENCES `catalogs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vin_decoded_cache_ibfk_4` FOREIGN KEY (`vin_model_id`) REFERENCES `vin_models` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `vin_models`
-- ============================================================
CREATE TABLE `vin_models` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_code` (`model_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `vin_spec_attributes`
-- ============================================================
CREATE TABLE `vin_spec_attributes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `vin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attribute_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attribute_value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vin_attribute_index` (`vin`,`attribute_code`),
  CONSTRAINT `vin_spec_attributes_ibfk_1` FOREIGN KEY (`vin`) REFERENCES `vin_decoded_cache` (`vin`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `vin_spec_mapped`
-- ============================================================
CREATE TABLE `vin_spec_mapped` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vin_id` bigint unsigned NOT NULL,
  `specification_id` int unsigned NOT NULL,
  `specification_item_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vin_spec` (`vin_id`,`specification_id`),
  KEY `specification_id` (`specification_id`),
  KEY `specification_item_id` (`specification_item_id`),
  CONSTRAINT `vin_spec_mapped_ibfk_1` FOREIGN KEY (`vin_id`) REFERENCES `vin_decoded_cache` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vin_spec_mapped_ibfk_2` FOREIGN KEY (`specification_id`) REFERENCES `specifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vin_spec_mapped_ibfk_3` FOREIGN KEY (`specification_item_id`) REFERENCES `specification_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `vin_spec_to_spec_item`
-- ============================================================
CREATE TABLE `vin_spec_to_spec_item` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `vin_attribute_id` bigint NOT NULL,
  `specification_item_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vin_spec_map` (`vin_attribute_id`,`specification_item_id`),
  KEY `specification_item_id` (`specification_item_id`),
  CONSTRAINT `vin_spec_to_spec_item_ibfk_1` FOREIGN KEY (`vin_attribute_id`) REFERENCES `vin_spec_attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vin_spec_to_spec_item_ibfk_2` FOREIGN KEY (`specification_item_id`) REFERENCES `specification_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `wishlists`
-- ============================================================
CREATE TABLE `wishlists` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `merchant_product_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_wishlists_merchant_product` (`merchant_product_id`),
  CONSTRAINT `fk_wishlists_merchant_product` FOREIGN KEY (`merchant_product_id`) REFERENCES `merchant_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: `withdraws`
-- ============================================================
CREATE TABLE `withdraws` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acc_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iban` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acc_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `swift` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `amount` float DEFAULT NULL,
  `fee` float DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `status` enum('pending','completed','rejected') NOT NULL DEFAULT 'pending',
  `type` enum('user','vendor','rider') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

