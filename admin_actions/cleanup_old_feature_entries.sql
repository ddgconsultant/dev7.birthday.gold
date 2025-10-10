-- =====================================================
-- Cleanup Old Feature Entries
-- =====================================================
-- This deactivates old feature entries that have simple values
-- instead of JSON metadata, to avoid conflicts
-- =====================================================

-- Deactivate old entries with non-JSON values for feature_email
-- These are the old entries with value = '1' instead of JSON
UPDATE bg_product_features
SET status = 'inactive',
    modify_dt = NOW()
WHERE name = 'feature_email'
AND product_id IN (21, 320, 321, 351)
AND display_mode = 'show'
AND status = 'active'
AND value = '1';

-- Also deactivate the OLD tagged entry for 321
UPDATE bg_product_features
SET status = 'inactive',
    modify_dt = NOW()
WHERE name = 'feature_email_OLD'
AND product_id = 321
AND status = 'active';

-- Verification: Show remaining feature entries
SELECT
    id,
    product_id,
    name,
    CASE
        WHEN LENGTH(value) > 50 THEN CONCAT(LEFT(value, 50), '...')
        ELSE value
    END as value_preview,
    status,
    display_mode
FROM bg_product_features
WHERE name LIKE 'feature_%'
AND product_id IS NOT NULL
AND display_mode = 'show'
ORDER BY product_id, name;
