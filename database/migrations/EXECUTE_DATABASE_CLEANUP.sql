-- ========================================
-- MUAADH EPC - Database Cleanup
-- استعلامات التنفيذ المباشر
-- ========================================
-- استخدام: قم بنسخ ولصق هذه الاستعلامات في phpMyAdmin أو MySQL Workbench
-- أو نفذها عبر command line:
-- mysql -uroot -pROOT -D new < EXECUTE_DATABASE_CLEANUP.sql
-- ========================================

USE `new`;

-- ========================================
-- 1. تحديث جدول seotools (إلزامي)
-- ========================================
-- تحديث meta_keys من "Muaadh,Ocean,..." إلى كلمات MUAADH EPC المناسبة

UPDATE seotools
SET
    meta_keys = 'MUAADH,EPC,Auto Parts,OEM Parts,Aftermarket,Genuine Parts,Car Parts,Vehicle Parts,Spare Parts,Auto Catalog,قطع غيار,سيارات,قطع أصلية',
    meta_description = 'MUAADH EPC - Professional auto parts catalog with AI-assisted search. Find genuine OEM and aftermarket parts for all vehicle models. كتالوج قطع الغيار الاحترافي مع بحث ذكي.'
WHERE id = 1;

-- التحقق من التحديث:
SELECT 'seotools updated:' AS message, meta_keys, meta_description FROM seotools WHERE id = 1;


-- ========================================
-- 2. تحديث القيمة الافتراضية لعمود theme (إلزامي)
-- ========================================
-- تغيير القيمة الافتراضية من 'theme1' إلى 'muaadh_oem'

ALTER TABLE generalsettings
MODIFY COLUMN theme varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'muaadh_oem';

-- التحقق من التحديث:
SELECT 'generalsettings.theme column default updated' AS message;
SHOW COLUMNS FROM generalsettings LIKE 'theme';


-- ========================================
-- 3. (اختياري) تحديث قيمة theme الحالية
-- ========================================
-- ⚠️ ملاحظة: موقعك يستخدم حالياً theme4
--
-- الخيارات:
-- أ) الاحتفاظ بـ theme4 (لا تنفذ شيء)
-- ب) التغيير إلى muaadh_oem (احذف -- من السطر التالي)
-- ج) التغيير إلى muaadh_storefront
-- د) التغيير إلى muaadh_minimal

-- الخيار ب: التغيير إلى muaadh_oem (نفذ إذا أردت):
-- UPDATE generalsettings SET theme = 'muaadh_oem' WHERE id = 1;

-- الخيار ج: التغيير إلى muaadh_storefront (نفذ إذا أردت):
-- UPDATE generalsettings SET theme = 'muaadh_storefront' WHERE id = 1;

-- الخيار د: التغيير إلى muaadh_minimal (نفذ إذا أردت):
-- UPDATE generalsettings SET theme = 'muaadh_minimal' WHERE id = 1;


-- ========================================
-- 4. (اختياري) تحديث عنوان الموقع
-- ========================================
-- القيمة الحالية: 'PARTSTSORE' (يوجد خطأ إملائي)
-- يمكنك تحديثها إلى اسم احترافي:

-- UPDATE generalsettings SET title = 'MUAADH EPC - Auto Parts Catalog' WHERE id = 1;


-- ========================================
-- 5. استعلامات التحقق النهائية
-- ========================================

SELECT '=== Final Verification ===' AS message;

-- عرض إعدادات SEO:
SELECT 'SEO Settings:' AS section, id, meta_keys, meta_description FROM seotools;

-- عرض الإعدادات العامة:
SELECT 'General Settings:' AS section, id, title, theme FROM generalsettings;

-- عرض تعريف عمود theme:
SELECT 'Theme Column Definition:' AS section;
SHOW COLUMNS FROM generalsettings LIKE 'theme';

SELECT '=== Cleanup Completed Successfully! ===' AS message;


-- ========================================
-- نهاية الاستعلامات
-- ========================================

/*
✅ بعد تنفيذ هذا الملف:

1. امسح كاش Laravel:
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear

2. راجع موقعك للتأكد من:
   - ظهور meta tags الجديدة في مصدر HTML
   - عمل التيم بشكل صحيح

3. إذا اخترت تغيير theme من theme4:
   - تأكد من وجود ملفات التيم الجديد
   - اختبر جميع صفحات الموقع

© 2025 MUAADH EPC - All Rights Reserved
*/
