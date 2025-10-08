-- ============================================================================
-- سكريبت توليد الفهارس الديناميكية لجداول الكتالوج
-- ============================================================================
-- هذا السكريبت ينشئ فهارس لـ 600+ جدول ديناميكي
-- الجداول المستهدفة:
--   - parts_{catalog_code}
--   - section_parts_{catalog_code}
--   - part_periods_{catalog_code}
--   - part_spec_groups_{catalog_code}
--   - part_spec_group_items_{catalog_code}
--   - part_extensions_{catalog_code}
-- ============================================================================

-- تعطيل فحص المفاتيح الأجنبية مؤقتاً لتسريع الإنشاء
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. إنشاء Stored Procedure لتوليد الفهارس ديناميكياً
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS CreateDynamicIndexes$$

CREATE PROCEDURE CreateDynamicIndexes()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE catalog_code VARCHAR(50);
    DECLARE table_name VARCHAR(100);
    DECLARE index_exists INT;

    -- Cursor لجلب جميع أكواد الكتالوج من جدول catalogs
    DECLARE catalog_cursor CURSOR FOR
        SELECT LOWER(code) FROM catalogs WHERE code IS NOT NULL AND code != '';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN catalog_cursor;

    catalog_loop: LOOP
        FETCH catalog_cursor INTO catalog_code;
        IF done THEN
            LEAVE catalog_loop;
        END IF;

        -- ========================================================================
        -- 1.1 فهارس جدول parts_{catalog_code}
        -- ========================================================================
        SET table_name = CONCAT('parts_', catalog_code);

        -- التحقق من وجود الجدول
        IF (SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = table_name) > 0 THEN

            -- Index على part_number (للبحث بـ prefix)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_number');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_number` (`part_number`(50))');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على callout (للبحث السريع)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_callout');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_callout` (`callout`(50))');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على label_en (للبحث بالاسم)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_label_en');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_label_en` (`label_en`(100))');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على label_ar (للبحث بالعربي)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_label_ar');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_label_ar` (`label_ar`(100))');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Composite Index على part_number + callout (لأداء أفضل)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_callout');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_callout` (`part_number`(50), `callout`(50))');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

        END IF;

        -- ========================================================================
        -- 1.2 فهارس جدول section_parts_{catalog_code}
        -- ========================================================================
        SET table_name = CONCAT('section_parts_', catalog_code);

        IF (SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = table_name) > 0 THEN

            -- Index على part_id (JOIN مع parts)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_id` (`part_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على section_id (للفلترة)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_section_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_section_id` (`section_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Composite Index على section_id + part_id (للاستعلامات المتكررة)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_section_part');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_section_part` (`section_id`, `part_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

        END IF;

        -- ========================================================================
        -- 1.3 فهارس جدول part_periods_{catalog_code}
        -- ========================================================================
        SET table_name = CONCAT('part_periods_', catalog_code);

        IF (SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = table_name) > 0 THEN

            -- Index على part_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_id` (`part_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على begin_date و end_date (للفلترة بالفترات)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_dates');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_dates` (`begin_date`, `end_date`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

        END IF;

        -- ========================================================================
        -- 1.4 فهارس جدول part_spec_groups_{catalog_code}
        -- ========================================================================
        SET table_name = CONCAT('part_spec_groups_', catalog_code);

        IF (SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = table_name) > 0 THEN

            -- Index على part_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_id` (`part_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على section_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_section_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_section_id` (`section_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على catalog_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_catalog_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_catalog_id` (`catalog_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على part_period_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_period_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_period_id` (`part_period_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Composite Index (الاستعلام الأكثر تكراراً)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_section_catalog');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_section_catalog` (`part_id`, `section_id`, `catalog_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

        END IF;

        -- ========================================================================
        -- 1.5 فهارس جدول part_spec_group_items_{catalog_code}
        -- ========================================================================
        SET table_name = CONCAT('part_spec_group_items_', catalog_code);

        IF (SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = table_name) > 0 THEN

            -- Index على group_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_group_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_group_id` (`group_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على specification_item_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_specification_item_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_specification_item_id` (`specification_item_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

        END IF;

        -- ========================================================================
        -- 1.6 فهارس جدول part_extensions_{catalog_code}
        -- ========================================================================
        SET table_name = CONCAT('part_extensions_', catalog_code);

        IF (SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = table_name) > 0 THEN

            -- Index على part_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_id` (`part_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على section_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_section_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_section_id` (`section_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على group_id
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_group_id');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_group_id` (`group_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Index على extension_key (للبحث السريع)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_extension_key');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_extension_key` (`extension_key`(50))');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Composite Index (الاستعلام الأكثر شيوعاً)
            SET index_exists = (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = table_name
                AND index_name = 'idx_part_section_group');

            IF index_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_part_section_group` (`part_id`, `section_id`, `group_id`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

        END IF;

    END LOOP;

    CLOSE catalog_cursor;

    SELECT CONCAT('✅ تم إنشاء الفهارس بنجاح لجميع الكتالوجات') AS Result;

END$$

DELIMITER ;

-- ============================================================================
-- 2. إنشاء فهارس إضافية للجداول الثابتة المستخدمة في الاستعلامات
-- ============================================================================

-- فهارس جدول parts_index (يستخدمه CompatibilityService)
ALTER TABLE parts_index
    ADD INDEX IF NOT EXISTS idx_part_number (part_number(50)),
    ADD INDEX IF NOT EXISTS idx_catalog_code (catalog_code(20)),
    ADD INDEX IF NOT EXISTS idx_part_catalog (part_number(50), catalog_code(20));

-- فهارس جدول sections
ALTER TABLE sections
    ADD INDEX IF NOT EXISTS idx_full_code (full_code(50)),
    ADD INDEX IF NOT EXISTS idx_catalog_id (catalog_id);

-- فهارس جدول specification_items
ALTER TABLE specification_items
    ADD INDEX IF NOT EXISTS idx_specification_id (specification_id),
    ADD INDEX IF NOT EXISTS idx_catalog_id (catalog_id),
    ADD INDEX IF NOT EXISTS idx_value_id (value_id(50));

-- فهارس جدول category_spec_groups
ALTER TABLE category_spec_groups
    ADD INDEX IF NOT EXISTS idx_category_id (category_id),
    ADD INDEX IF NOT EXISTS idx_catalog_id (catalog_id),
    ADD INDEX IF NOT EXISTS idx_category_period_id (category_period_id);

-- فهارس جدول category_spec_group_items
ALTER TABLE category_spec_group_items
    ADD INDEX IF NOT EXISTS idx_group_id (group_id),
    ADD INDEX IF NOT EXISTS idx_specification_item_id (specification_item_id);

-- فهارس جدول category_periods
ALTER TABLE category_periods
    ADD INDEX IF NOT EXISTS idx_category_id (category_id),
    ADD INDEX IF NOT EXISTS idx_dates (begin_date, end_date);

-- ============================================================================
-- 3. تشغيل Stored Procedure لإنشاء الفهارس الديناميكية
-- ============================================================================

CALL CreateDynamicIndexes();

-- ============================================================================
-- 4. تفعيل فحص المفاتيح الأجنبية
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 5. تحليل الجداول بعد إنشاء الفهارس (لتحديث الإحصائيات)
-- ============================================================================

-- ملاحظة: يمكنك تشغيل ANALYZE TABLE على جميع الجداول لتحديث الإحصائيات
-- لكن هذا قد يستغرق وقتاً طويلاً مع 600+ جدول

-- مثال لتحليل جدول واحد:
-- ANALYZE TABLE parts_catalog_code;

-- ============================================================================
-- 6. سكريبت للتحقق من الفهارس المنشأة
-- ============================================================================

-- للتحقق من الفهارس على جدول معين:
-- SHOW INDEX FROM parts_catalog_code;

-- للحصول على قائمة بجميع الفهارس في قاعدة البيانات:
-- SELECT
--     TABLE_NAME,
--     INDEX_NAME,
--     GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as COLUMNS
-- FROM information_schema.statistics
-- WHERE TABLE_SCHEMA = DATABASE()
-- AND TABLE_NAME LIKE 'parts_%'
-- GROUP BY TABLE_NAME, INDEX_NAME
-- ORDER BY TABLE_NAME, INDEX_NAME;

-- ============================================================================
-- تم الانتهاء من السكريبت
-- ============================================================================
