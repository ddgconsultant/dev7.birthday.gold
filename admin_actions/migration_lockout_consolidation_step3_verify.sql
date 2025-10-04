-- =====================================================================
-- LOCKOUT CONSOLIDATION MIGRATION - STEP 3: VERIFICATION
-- =====================================================================
-- Purpose: Verify data integrity and migration success
-- Date: 2025-10-04
-- =====================================================================

-- 1. Verify parent record count
-- ---------------------------------------------------------------------
SELECT 'Parent Record Count' as verification_step, COUNT(*) as count FROM bg_lockout;

-- 2. Verify history record count
-- ---------------------------------------------------------------------
SELECT 'History Record Count' as verification_step, COUNT(*) as count FROM bg_lockout_history;

-- 3. Check for orphaned history records
-- ---------------------------------------------------------------------
SELECT 'Orphaned History Records' as verification_step, COUNT(*) as count
FROM bg_lockout_history WHERE parent_id IS NULL;

-- 4. Verify the biggest offender is consolidated
-- ---------------------------------------------------------------------
SELECT
    'Biggest Offender Consolidation' as verification_step,
    p.id,
    p.ip,
    p.session_id,
    p.lockout_level,
    p.total_violations as parent_violations,
    COUNT(h.id) as history_count
FROM bg_lockout p
LEFT JOIN bg_lockout_history h ON h.parent_id = p.id
WHERE p.ip = '185.161.248.202' AND p.session_id = 'k4va7imte7hi5ba6hefbm52tl3'
GROUP BY p.id;

-- 5. Check data integrity (total_violations should match history count)
-- ---------------------------------------------------------------------
SELECT
    'Data Integrity Check' as verification_step,
    COUNT(*) as mismatched_records
FROM (
    SELECT
        p.id,
        p.total_violations as parent_violation_count,
        COUNT(h.id) as history_record_count
    FROM bg_lockout p
    LEFT JOIN bg_lockout_history h ON h.parent_id = p.id
    GROUP BY p.id
    HAVING parent_violation_count != history_record_count
) as mismatches;

-- 6. Verify table sizes
-- ---------------------------------------------------------------------
SELECT
    TABLE_NAME,
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb,
    TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'birthday_gold_www'
AND TABLE_NAME IN ('bg_lockout', 'bg_lockout_history', 'bg_lockout_backup_20251004')
ORDER BY TABLE_NAME;

-- 7. Show top 10 consolidated records
-- ---------------------------------------------------------------------
SELECT
    'Top 10 Offenders' as verification_step,
    p.id,
    p.ip,
    LEFT(p.session_id, 20) as session_id,
    p.lockout_level,
    p.total_violations,
    p.first_violation_dt,
    p.last_violation_dt,
    COUNT(h.id) as history_count
FROM bg_lockout p
LEFT JOIN bg_lockout_history h ON h.parent_id = p.id
GROUP BY p.id
ORDER BY p.total_violations DESC
LIMIT 10;

-- 8. Check index usage on active lockout query
-- ---------------------------------------------------------------------
EXPLAIN SELECT * FROM bg_lockout
WHERE ip = '185.161.248.202'
AND (NOW() between start_dt and expire_dt)
AND status='active'
ORDER BY expire_dt DESC LIMIT 1;

-- 9. Active lockouts summary
-- ---------------------------------------------------------------------
SELECT
    'Active Lockouts Summary' as verification_step,
    COUNT(*) as total_active,
    MIN(lockout_level) as min_level,
    MAX(lockout_level) as max_level,
    AVG(lockout_level) as avg_level,
    SUM(total_violations) as total_all_time_violations
FROM bg_lockout
WHERE status = 'active' AND expire_dt > NOW();

-- 10. Final status
-- ---------------------------------------------------------------------
SELECT
    'Migration Status' as status,
    CASE
        WHEN (SELECT COUNT(*) FROM bg_lockout_history WHERE parent_id IS NULL) = 0
         AND (SELECT COUNT(*) FROM bg_lockout) < 1000
         AND (SELECT COUNT(*) FROM bg_lockout_history) > 700000
        THEN 'SUCCESS ✓'
        ELSE 'REVIEW NEEDED ⚠'
    END as result;
