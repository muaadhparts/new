# 📊 توثيق الفهارس الديناميكية للجداول

## 🎯 الهدف
تحسين أداء الاستعلامات على 600+ جدول ديناميكي من خلال إنشاء فهارس محسّنة لكل أنماط الاستعلامات المستخدمة في المشروع.

---

## 📋 جداول المشروع الديناميكية

### 1. **parts_{catalog_code}**
**الغرض:** تخزين بيانات القطع لكل كتالوج

**الأعمدة المفهرسة:**
- `id` - Primary Key (تلقائي)
- `part_number` - رقم القطعة
- `callout` - رمز الكول آوت
- `label_en` - الاسم بالإنجليزية
- `label_ar` - الاسم بالعربية

**الفهارس المُنشأة:**
```sql
-- فهرس فردي على part_number
idx_part_number (part_number(50))

-- فهرس فردي على callout
idx_callout (callout(50))

-- فهرس فردي على label_en
idx_label_en (label_en(100))

-- فهرس فردي على label_ar
idx_label_ar (label_ar(100))

-- فهرس مركب على part_number + callout
idx_part_callout (part_number(50), callout(50))
```

**الاستعلامات المُحسّنة:**
```php
// VehicleSearchBox.php:512-515
DB::table($partsTable)
    ->where('part_number', 'like', "{$cleanQuery}%")
    ->orWhere('callout', 'like', "{$cleanQuery}%");

// VehicleSearchBox.php:555-558
DB::table($partsTable)
    ->where('label_en', 'like', "{$cleanQuery}%")
    ->orWhere('label_ar', 'like', "{$cleanQuery}%");

// VehicleSearchBox.php:272-282 (البحث بالاسم)
DB::table("$partsTable as p")
    ->where('p.label_en', 'like', "%{$w}%")
    ->orWhere('p.label_ar', 'like', "%{$w}%");
```

---

### 2. **section_parts_{catalog_code}**
**الغرض:** ربط القطع بالأقسام (many-to-many)

**الأعمدة المفهرسة:**
- `id` - Primary Key (تلقائي)
- `part_id` - معرف القطعة
- `section_id` - معرف القسم

**الفهارس المُنشأة:**
```sql
-- فهرس فردي على part_id
idx_part_id (part_id)

-- فهرس فردي على section_id
idx_section_id (section_id)

-- فهرس مركب على section_id + part_id
idx_section_part (section_id, part_id)
```

**الاستعلامات المُحسّنة:**
```php
// CalloutController.php:325-330
DB::table("{$partsTable} as p")
    ->join("{$sectionPartsTable} as sp", 'sp.part_id', '=', 'p.id')
    ->where('sp.section_id', $sectionId)
    ->where('p.callout', $calloutKey);

// VehicleSearchBox.php:525-528
DB::table("$partsTable as p")
    ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
    ->join('sections as s', 's.id', '=', 'sp.section_id')
    ->whereIn('s.full_code', $allowedCodes);
```

---

### 3. **part_periods_{catalog_code}**
**الغرض:** تخزين فترات صلاحية القطع

**الأعمدة المفهرسة:**
- `id` - Primary Key (تلقائي)
- `part_id` - معرف القطعة
- `begin_date` - تاريخ البداية
- `end_date` - تاريخ النهاية

**الفهارس المُنشأة:**
```sql
-- فهرس فردي على part_id
idx_part_id (part_id)

-- فهرس مركب على begin_date + end_date
idx_dates (begin_date, end_date)
```

**الاستعلامات المُحسّنة:**
```php
// CalloutController.php:336-342
DB::table("{$groupTable} as g")
    ->leftJoin("{$periodTable} as pp", 'pp.id', '=', 'g.part_period_id')
    ->whereIn('g.part_id', $partIds)
    ->select('pp.begin_date', 'pp.end_date');
```

---

### 4. **part_spec_groups_{catalog_code}**
**الغرض:** مجموعات المواصفات للقطع

**الأعمدة المفهرسة:**
- `id` - Primary Key (تلقائي)
- `part_id` - معرف القطعة
- `section_id` - معرف القسم
- `catalog_id` - معرف الكتالوج
- `part_period_id` - معرف الفترة
- `group_index` - رقم المجموعة

**الفهارس المُنشأة:**
```sql
-- فهرس فردي على part_id
idx_part_id (part_id)

-- فهرس فردي على section_id
idx_section_id (section_id)

-- فهرس فردي على catalog_id
idx_catalog_id (catalog_id)

-- فهرس فردي على part_period_id
idx_part_period_id (part_period_id)

-- فهرس مركب على part_id + section_id + catalog_id
idx_part_section_catalog (part_id, section_id, catalog_id)
```

**الاستعلامات المُحسّنة:**
```php
// CalloutController.php:336-342 (الاستعلام الأكثر استخداماً)
DB::table("{$groupTable} as g")
    ->leftJoin("{$periodTable} as pp", 'pp.id', '=', 'g.part_period_id')
    ->whereIn('g.part_id', $partIds)
    ->where('g.section_id', $sectionId)
    ->where('g.catalog_id', $catalogId);
```

---

### 5. **part_spec_group_items_{catalog_code}**
**الغرض:** عناصر المواصفات لكل مجموعة

**الأعمدة المفهرسة:**
- `id` - Primary Key (تلقائي)
- `group_id` - معرف المجموعة
- `specification_item_id` - معرف عنصر المواصفة

**الفهارس المُنشأة:**
```sql
-- فهرس فردي على group_id
idx_group_id (group_id)

-- فهرس فردي على specification_item_id
idx_specification_item_id (specification_item_id)
```

**الاستعلامات المُحسّنة:**
```php
// CalloutController.php:346-351
DB::table("{$itemTable} as gi")
    ->join('specification_items as si', 'si.id', '=', 'gi.specification_item_id')
    ->join('specifications as s', 's.id', '=', 'si.specification_id')
    ->whereIn('gi.group_id', $groupIds);
```

---

### 6. **part_extensions_{catalog_code}**
**الغرض:** بيانات إضافية للقطع (key-value)

**الأعمدة المفهرسة:**
- `id` - Primary Key (تلقائي)
- `part_id` - معرف القطعة
- `section_id` - معرف القسم
- `group_id` - معرف المجموعة
- `extension_key` - مفتاح البيانات الإضافية
- `extension_value` - قيمة البيانات الإضافية

**الفهارس المُنشأة:**
```sql
-- فهرس فردي على part_id
idx_part_id (part_id)

-- فهرس فردي على section_id
idx_section_id (section_id)

-- فهرس فردي على group_id
idx_group_id (group_id)

-- فهرس فردي على extension_key
idx_extension_key (extension_key(50))

-- فهرس مركب على part_id + section_id + group_id
idx_part_section_group (part_id, section_id, group_id)
```

**الاستعلامات المُحسّنة:**
```php
// CalloutController.php:443-448
DB::table($extTable)
    ->where('part_id', $part['part_id'])
    ->where('section_id', $sectionId)
    ->whereIn('group_id', $matchedGroupIds)
    ->select('extension_key', 'extension_value');
```

---

## 🗃️ الجداول الثابتة (إضافية)

### parts_index
**الغرض:** فهرس مركزي لجميع القطع عبر كل الكتالوجات

**الفهارس:**
```sql
idx_part_number (part_number(50))
idx_catalog_code (catalog_code(20))
idx_part_catalog (part_number(50), catalog_code(20))
```

**الاستعلامات المُحسّنة:**
```php
// CompatibilityService.php:18-29
DB::table('parts_index')
    ->join('catalogs', 'catalogs.code', '=', 'parts_index.catalog_code')
    ->where('parts_index.part_number', $sku);
```

### sections
```sql
idx_full_code (full_code(50))
idx_catalog_id (catalog_id)
```

### specification_items
```sql
idx_specification_id (specification_id)
idx_catalog_id (catalog_id)
idx_value_id (value_id(50))
```

### category_spec_groups
```sql
idx_category_id (category_id)
idx_catalog_id (catalog_id)
idx_category_period_id (category_period_id)
```

### category_spec_group_items
```sql
idx_group_id (group_id)
idx_specification_item_id (specification_item_id)
```

### category_periods
```sql
idx_category_id (category_id)
idx_dates (begin_date, end_date)
```

---

## 🚀 طريقة التنفيذ

### الخطوة 1: تشغيل السكريبت
```bash
mysql -u root -p your_database < database/create_dynamic_indexes.sql
```

### الخطوة 2: مراقبة التقدم
السكريبت سيقوم بـ:
1. تعطيل فحص المفاتيح الأجنبية مؤقتاً
2. إنشاء Stored Procedure
3. المرور على كل جدول في catalogs
4. التحقق من وجود كل فهرس قبل إنشائه
5. إنشاء الفهارس الديناميكية
6. إنشاء فهارس الجداول الثابتة
7. تفعيل فحص المفاتيح الأجنبية

### الخطوة 3: التحقق من النتائج
```sql
-- عرض جميع الفهارس على جدول معين
SHOW INDEX FROM parts_toyotacode;

-- عد الفهارس المُنشأة
SELECT
    TABLE_NAME,
    COUNT(DISTINCT INDEX_NAME) as index_count
FROM information_schema.statistics
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'parts_%'
GROUP BY TABLE_NAME
ORDER BY TABLE_NAME;
```

---

## ⚡ تحسينات الأداء المتوقعة

### قبل الفهارس:
- استعلام بحث بـ part_number: **~500ms - 2000ms**
- استعلام JOIN مع section_parts: **~800ms - 3000ms**
- استعلام مع مواصفات: **~1500ms - 5000ms**

### بعد الفهارس:
- استعلام بحث بـ part_number: **~10ms - 50ms** ⚡ (تحسين 10-40x)
- استعلام JOIN مع section_parts: **~20ms - 100ms** ⚡ (تحسين 10-30x)
- استعلام مع مواصفات: **~50ms - 200ms** ⚡ (تحسين 10-25x)

---

## 📝 ملاحظات مهمة

### 1. حجم الفهارس
- كل جدول سيأخذ مساحة إضافية بحجم: **~5-20% من حجم الجدول**
- مع 600 جدول، توقع زيادة **~10-30 GB** في حجم قاعدة البيانات
- هذه المساحة مستحقة بالكامل مقابل تحسين الأداء

### 2. وقت التنفيذ
- السكريبت قد يستغرق **15-60 دقيقة** مع 600 جدول
- يعتمد على:
  - حجم الجداول
  - سرعة القرص
  - حمل الخادم الحالي

### 3. الصيانة
- الفهارس تُحدّث تلقائياً مع INSERT/UPDATE/DELETE
- لا تحتاج صيانة دورية إلا في حالات نادرة
- يُنصح بـ `ANALYZE TABLE` شهرياً للجداول الكبيرة

### 4. الجداول الجديدة
عند إضافة كتالوج جديد:
```sql
-- قم بتشغيل الـ Stored Procedure مرة أخرى
CALL CreateDynamicIndexes();
```

---

## 🔍 استعلامات التحقق والمراقبة

### عرض جميع الفهارس في قاعدة البيانات
```sql
SELECT
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as COLUMNS,
    INDEX_TYPE,
    NON_UNIQUE
FROM information_schema.statistics
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'parts_%'
GROUP BY TABLE_NAME, INDEX_NAME, INDEX_TYPE, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;
```

### التحقق من استخدام الفهرس في استعلام
```sql
-- استخدم EXPLAIN لمعرفة أي فهرس يُستخدم
EXPLAIN SELECT * FROM parts_toyotacode
WHERE part_number LIKE 'ABC123%';

-- يجب أن ترى:
-- possible_keys: idx_part_number, idx_part_callout
-- key: idx_part_number
-- type: range
```

### إحصائيات الفهارس
```sql
SELECT
    TABLE_NAME,
    INDEX_NAME,
    CARDINALITY,
    INDEX_LENGTH / 1024 / 1024 as SIZE_MB
FROM information_schema.statistics s
JOIN information_schema.tables t USING (TABLE_SCHEMA, TABLE_NAME)
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'parts_%'
AND INDEX_NAME != 'PRIMARY'
ORDER BY SIZE_MB DESC
LIMIT 20;
```

---

## 🎯 الخلاصة

تم تصميم هذه الفهارس بناءً على تحليل دقيق للاستعلامات الفعلية المستخدمة في:
- `CalloutController.php` - API للكول آوت
- `VehicleSearchBox.php` - البحث في القطع
- `CompatibilityService.php` - التحقق من التوافق

كل فهرس له هدف محدد ويحسّن استعلام معين. النتيجة النهائية:
- ✅ استجابة أسرع للمستخدم
- ✅ تقليل الحمل على الخادم
- ✅ دعم أفضل لـ 600+ جدول ديناميكي
- ✅ قابلية توسع للمستقبل

---

**تاريخ الإنشاء:** 2025-01-09
**الإصدار:** 1.0
**الحالة:** جاهز للتنفيذ
