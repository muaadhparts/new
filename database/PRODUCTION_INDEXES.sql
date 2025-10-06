-- ============================================================
-- PRODUCTION DATABASE PERFORMANCE INDEXES
-- Execute these commands ONE BY ONE in your production database
-- ============================================================
-- ⚠️ IMPORTANT: Run these during low-traffic hours if possible
-- Each command should take 10-60 seconds depending on table size
-- ============================================================

-- 1️⃣ Index for illustrations table (most critical - speeds up section queries)
CREATE INDEX IF NOT EXISTS idx_illustrations_section_code
ON illustrations(section_id, code);

-- 2️⃣ Index for callouts table (speeds up callout type filtering)
CREATE INDEX IF NOT EXISTS idx_callouts_illustration_type
ON callouts(illustration_id, callout_type);

-- 3️⃣ Index for newcategories table (speeds up category tree navigation)
-- Note: full_code(50) limits index size for VARCHAR fields
CREATE INDEX IF NOT EXISTS idx_newcategories_level_fullcode
ON newcategories(level, full_code(50));

-- 4️⃣ Index for sections table (speeds up section lookups)
CREATE INDEX IF NOT EXISTS idx_sections_category_catalog
ON sections(category_id, catalog_id);

-- ============================================================
-- VERIFICATION QUERIES
-- Run these AFTER creating indexes to verify they exist
-- ============================================================

-- Check illustrations indexes
SHOW INDEXES FROM illustrations WHERE Key_name = 'idx_illustrations_section_code';

-- Check callouts indexes
SHOW INDEXES FROM callouts WHERE Key_name = 'idx_callouts_illustration_type';

-- Check newcategories indexes
SHOW INDEXES FROM newcategories WHERE Key_name = 'idx_newcategories_level_fullcode';

-- Check sections indexes
SHOW INDEXES FROM sections WHERE Key_name = 'idx_sections_category_catalog';

-- ============================================================
-- PERFORMANCE TEST QUERY (run BEFORE and AFTER indexes)
-- This query should be MUCH faster after indexes are created
-- ============================================================

EXPLAIN SELECT c.*
FROM callouts c
INNER JOIN illustrations i ON c.illustration_id = i.id
INNER JOIN sections s ON i.section_id = s.id
WHERE s.category_id = 3273
  AND s.catalog_id = (SELECT id FROM catalogs WHERE code = 'Y61GL' LIMIT 1)
  AND i.code = '11720N'
  AND c.callout_type = 'part'
LIMIT 50;

-- ============================================================
-- Expected results AFTER indexes:
-- - Query execution time should drop from 60+ seconds to < 1 second
-- - EXPLAIN should show "Using index" instead of "Using filesort"
-- ============================================================
