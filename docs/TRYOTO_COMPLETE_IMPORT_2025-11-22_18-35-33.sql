-- ═══════════════════════════════════════════════════════════════
-- TRYOTO COMPLETE LOCATION DATA IMPORT
-- Generated: 2025-11-22 18:35:33
-- Countries: 1
-- States: 12
-- Cities: 89
-- ═══════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;

-- ═══════════════════════════════════════════════════════════════
-- 1. COUNTRIES
-- ═══════════════════════════════════════════════════════════════

INSERT INTO countries (country_code, country_name, country_name_ar, tax, status)
SELECT 'SA', 'Saudi Arabia', 'المملكة العربية السعودية', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM countries WHERE country_code = 'SA');


-- ═══════════════════════════════════════════════════════════════
-- 2. STATES / REGIONS
-- ═══════════════════════════════════════════════════════════════

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Riyadh Region', 'منطقة الرياض', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Riyadh Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Makkah Region', 'منطقة مكة المكرمة', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Makkah Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Madinah Region', 'منطقة المدينة المنورة', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Madinah Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Eastern Region', 'المنطقة الشرقية', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Eastern Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Tabuk Region', 'منطقة تبوك', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Tabuk Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Al-Qassim Region', 'منطقة القصيم', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Al-Qassim Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Asir Region', 'منطقة عسير', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Asir Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Hail Region', 'منطقة حائل', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Hail Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Najran Region', 'منطقة نجران', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Najran Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Jazan Region', 'منطقة جازان', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Jazan Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Northern Borders Region', 'منطقة الحدود الشمالية', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Northern Borders Region');

INSERT INTO states (country_id, state, state_ar, tax, status, owner_id)
SELECT id, 'Al Jouf Region', 'منطقة الجوف', 0, 1, 0, NOW(), NOW()
FROM countries WHERE country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM states WHERE state = 'Al Jouf Region');


-- ═══════════════════════════════════════════════════════════════
-- 3. CITIES
-- ═══════════════════════════════════════════════════════════════

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Riyadh', 'الرياض', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Riyadh');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Jeddah', 'جدة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Makkah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Jeddah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Mecca', 'مكة المكرمة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Makkah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Mecca');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Medina', 'المدينة المنورة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Madinah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Medina');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Dammam', 'الدمام', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Dammam');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Khobar', 'الخبر', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Khobar');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Dhahran', 'الظهران', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Dhahran');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Tabuk', 'تبوك', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Tabuk Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Tabuk');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Buraidah', 'بريدة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Al-Qassim Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Buraidah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Khamis Mushait', 'خميس مشيط', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Asir Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Khamis Mushait');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Hail', 'حائل', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Hail Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Hail');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Najran', 'نجران', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Najran Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Najran');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Jazan', 'جازان', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Jazan Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Jazan');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Taif', 'الطائف', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Makkah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Taif');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Yanbu', 'ينبع', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Madinah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Yanbu');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Abha', 'أبها', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Asir Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Abha');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Qatif', 'القطيف', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Qatif');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Jubail', 'الجبيل', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Jubail');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Ahsa', 'الأحساء', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Ahsa');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Kharj', 'الخرج', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Kharj');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Arar', 'عرعر', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Northern Borders Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Arar');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Sakaka', 'سكاكا', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Al Jouf Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Sakaka');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Hafar Al Batin', 'حفر الباطن', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Hafar Al Batin');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Majmaah', 'المجمعة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Majmaah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Unaizah', 'عنيزة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Al-Qassim Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Unaizah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Qunfudhah', 'Al Qunfudhah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Makkah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Qunfudhah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Lith', 'Al Lith', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Makkah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Lith');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Rabigh', 'Rabigh', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Makkah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Rabigh');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Wajh', 'Al Wajh', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Tabuk Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Wajh');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Duba', 'Duba', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Tabuk Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Duba');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Ula', 'Al Ula', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Madinah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Ula');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Badr', 'Badr', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Madinah Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Badr');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Dawadmi', 'Al Dawadmi', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Dawadmi');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Zulfi', 'Al Zulfi', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Zulfi');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Shaqra', 'Shaqra', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Shaqra');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Aflaj', 'Al Aflaj', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Aflaj');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Wadi Al Dawasir', 'Wadi Al Dawasir', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Wadi Al Dawasir');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Rass', 'Al Rass', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Al-Qassim Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Rass');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Bukayriyah', 'Al Bukayriyah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Al-Qassim Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Bukayriyah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Bishah', 'Bishah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Asir Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Bishah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Namas', 'Al Namas', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Asir Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Namas');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Muhayil', 'Muhayil', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Asir Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Muhayil');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Samtah', 'Samtah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Jazan Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Samtah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Sabya', 'Sabya', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Jazan Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Sabya');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Abu Arish', 'Abu Arish', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Jazan Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Abu Arish');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Sharurah', 'Sharurah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Najran Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Sharurah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Khafji', 'Al Khafji', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Khafji');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Ras Tanura', 'Ras Tanura', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Ras Tanura');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Qaisumah', 'Qaisumah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Qaisumah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Mubarraz', 'Al Mubarraz', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Mubarraz');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Hofuf', 'Hofuf', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Eastern Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Hofuf');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Turaif', 'Turaif', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Northern Borders Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Turaif');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Rafha', 'Rafha', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Northern Borders Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Rafha');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Qurayyat', 'Qurayyat', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Al Jouf Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Qurayyat');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Quwayiyah', 'Al Quwayiyah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Quwayiyah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Al Muzahimiyah', 'Al Muzahimiyah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Al Muzahimiyah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Diriyah', 'Diriyah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Diriyah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Rumah', 'Rumah', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Rumah');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Dhurma', 'Dhurma', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Dhurma');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Dubai', 'Dubai', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Dubai');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Kuwait City', 'Kuwait City', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Kuwait City');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Hawalli', 'Hawalli', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Hawalli');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Salmiya', 'Salmiya', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Salmiya');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Manama', 'Manama', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Manama');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Doha', 'Doha', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Doha');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Muscat', 'Muscat', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Muscat');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Cairo', 'Cairo', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Cairo');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Amman', 'Amman', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Amman');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Zarqa', 'Zarqa', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Zarqa');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'Irbid', 'Irbid', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'Irbid');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'الرياض', 'الرياض', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'الرياض');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'جدة', 'جدة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'جدة');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'مكة', 'مكة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'مكة');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'المدينة', 'المدينة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'المدينة');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'الدمام', 'الدمام', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'الدمام');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'الخبر', 'الخبر', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'الخبر');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'الظهران', 'الظهران', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'الظهران');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'تبوك', 'تبوك', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'تبوك');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'بريدة', 'بريدة', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'بريدة');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'خميس مشيط', 'خميس مشيط', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'خميس مشيط');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'حائل', 'حائل', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'حائل');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'نجران', 'نجران', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'نجران');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'جازان', 'جازان', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'جازان');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'الطائف', 'الطائف', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'الطائف');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'ينبع', 'ينبع', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'ينبع');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'أبها', 'أبها', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'أبها');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'القطيف', 'القطيف', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'القطيف');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'الجبيل', 'الجبيل', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'الجبيل');

INSERT INTO cities (state_id, city_name, city_name_ar, country_id, status)
SELECT s.id, 'الأحساء', 'الأحساء', c.id, 1, NOW(), NOW()
FROM states s
CROSS JOIN countries c
WHERE s.state = 'Riyadh Region' AND c.country_code = 'SA'
AND NOT EXISTS (SELECT 1 FROM cities WHERE city_name = 'الأحساء');

SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════════
-- END OF IMPORT
-- ═══════════════════════════════════════════════════════════════
