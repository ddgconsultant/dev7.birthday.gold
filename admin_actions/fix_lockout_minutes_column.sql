-- =====================================================================
-- FIX: Change lockout_minutes from INT to BIGINT
-- =====================================================================
-- Issue: Some lockout_minutes values exceed INT max (2,147,483,647)
-- Solution: Change column type to BIGINT
-- =====================================================================

-- Fix the bg_lockout_history table
ALTER TABLE bg_lockout_history
MODIFY COLUMN lockout_minutes BIGINT COMMENT 'TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)';

-- Verify the change
SELECT 'Column type updated to BIGINT' as status;

SHOW COLUMNS FROM bg_lockout_history LIKE 'lockout_minutes';

-- Show the problematic values that caused the error
SELECT
    'Records with extreme lockout_minutes values:' as info,
    COUNT(*) as count
FROM bg_lockout
WHERE TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) > 2147483647;

-- Ready to proceed with step 2
SELECT 'Ready to run migration_lockout_consolidation_step2_data.sql' as next_step;
