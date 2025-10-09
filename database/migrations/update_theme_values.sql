-- ========================================
-- MUAADH EPC Theme Name Migration
-- ========================================
-- This migration updates theme references from legacy names (theme1/2/3)
-- to MUAADH-branded theme names (muaadh_oem/storefront/minimal)
--
-- Created: 2025-01-XX
-- Purpose: Complete rebranding to MUAADH EPC identity
-- ========================================

-- Update existing theme values in generalsettings table
UPDATE generalsettings
SET theme = CASE
    WHEN theme = 'theme1' THEN 'muaadh_oem'
    WHEN theme = 'theme2' THEN 'muaadh_storefront'
    WHEN theme = 'theme3' THEN 'muaadh_minimal'
    ELSE theme
END
WHERE theme IN ('theme1', 'theme2', 'theme3');

-- Update default value for theme column (if supported by your MySQL version)
-- Note: This may require recreating the table or using ALTER TABLE depending on MySQL version
-- For MySQL 8.0+:
ALTER TABLE generalsettings
MODIFY COLUMN theme varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'muaadh_oem';

-- ========================================
-- Verification Queries (run after migration)
-- ========================================

-- Check current theme values
-- SELECT id, theme FROM generalsettings;

-- Verify column default
-- SHOW COLUMNS FROM generalsettings LIKE 'theme';
