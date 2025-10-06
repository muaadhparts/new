-- =====================================================
-- 🚀 QUICK FIX: Copy & Paste هذه الأوامر في phpMyAdmin
-- =====================================================
-- ⏱️ الوقت: 2-5 دقائق
-- 🎯 النتيجة: من 90s إلى < 3s
-- =====================================================

-- ✅ Index 1: illustrations (الأهم!)
CREATE INDEX IF NOT EXISTS idx_illustrations_section_code
ON illustrations(section_id, code);

-- ✅ Index 2: callouts
CREATE INDEX IF NOT EXISTS idx_callouts_illustration_type
ON callouts(illustration_id, callout_type);

-- ✅ Index 3: newcategories
CREATE INDEX IF NOT EXISTS idx_newcategories_level_fullcode
ON newcategories(level, full_code(50));

-- ✅ Index 4: sections
CREATE INDEX IF NOT EXISTS idx_sections_category_catalog
ON sections(category_id, catalog_id);

-- =====================================================
-- 🔍 التحقق من النجاح (نفّذ بعد الأوامر السابقة)
-- =====================================================

-- يجب أن يرجع 4 نتائج (واحدة لكل index)
SELECT
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as COLUMNS
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN (
    'idx_illustrations_section_code',
    'idx_callouts_illustration_type',
    'idx_newcategories_level_fullcode',
    'idx_sections_category_catalog'
  )
GROUP BY TABLE_NAME, INDEX_NAME;

-- ✅ إذا رجع 4 صفوف = نجح!
-- ❌ إذا رجع أقل من 4 = بعض Indexes فشلت

-- =====================================================
-- 📊 اختبار الأداء (اختياري)
-- =====================================================

-- قبل Indexes: سيأخذ 30-90 ثانية
-- بعد Indexes: سيأخذ < 0.5 ثانية
EXPLAIN SELECT c.*
FROM callouts c
INNER JOIN illustrations i ON c.illustration_id = i.id
INNER JOIN sections s ON i.section_id = s.id
WHERE s.category_id = 3273
  AND s.catalog_id = (SELECT id FROM catalogs WHERE code = 'Y61GL' LIMIT 1)
  AND i.code = '11720N'
  AND c.callout_type = 'part'
LIMIT 50;

-- انظر للعمود "rows":
-- قبل: 500,000+   ← بطيء!
-- بعد: 50-100     ← سريع!

-- =====================================================
-- 🗑️ حذف Indexes (إذا احتجت التراجع)
-- =====================================================
-- ⚠️ لا تنفّذ هذه الأوامر إلا إذا أردت حذف Indexes

-- DROP INDEX idx_illustrations_section_code ON illustrations;
-- DROP INDEX idx_callouts_illustration_type ON callouts;
-- DROP INDEX idx_newcategories_level_fullcode ON newcategories;
-- DROP INDEX idx_sections_category_catalog ON sections;

-- =====================================================
-- 📋 ملخص
-- =====================================================
-- 1. انسخ الأوامر الأربعة الأولى (CREATE INDEX)
-- 2. الصق في phpMyAdmin → SQL tab
-- 3. اضغط "Go" أو "تنفيذ"
-- 4. انتظر 2-5 دقائق
-- 5. نفّذ أمر التحقق (SELECT)
-- 6. اختبر الموقع - يجب أن يعمل بسرعة!
-- =====================================================
