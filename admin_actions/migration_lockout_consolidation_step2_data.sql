-- =====================================================================
-- LOCKOUT CONSOLIDATION MIGRATION - STEP 2: DATA MIGRATION
-- =====================================================================
-- Purpose: Copy data to history and consolidate parent records
-- Date: 2025-10-04
-- =====================================================================

-- Step 2.1: Copy all data to history table
-- ---------------------------------------------------------------------
INSERT INTO bg_lockout_history
    (id, parent_id, ip, type, session_id, start_dt, expire_dt, create_dt, modify_dt, status, lockout_minutes)
SELECT
    id,
    NULL as parent_id,
    ip,
    type,
    session_id,
    start_dt,
    expire_dt,
    create_dt,
    modify_dt,
    status,
    TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) as lockout_minutes
FROM bg_lockout;

-- Verify counts match
SELECT
    (SELECT COUNT(*) FROM bg_lockout) as original_count,
    (SELECT COUNT(*) FROM bg_lockout_history) as history_count;

-- Step 2.2: Create backup before consolidation
-- ---------------------------------------------------------------------
CREATE TABLE bg_lockout_backup_20251004 AS SELECT * FROM bg_lockout;

SELECT CONCAT('Backup created with ', COUNT(*), ' records') as backup_status
FROM bg_lockout_backup_20251004;

-- Step 2.3: Create consolidated parent records
-- ---------------------------------------------------------------------
CREATE TEMPORARY TABLE lockout_consolidated AS
SELECT
    MIN(id) as id,
    ip,
    type,
    ANY_VALUE(session_id) as session_id,
    MIN(start_dt) as start_dt,
    MAX(expire_dt) as expire_dt,
    MIN(create_dt) as create_dt,
    MAX(modify_dt) as modify_dt,
    CASE
        WHEN MAX(CASE WHEN status = 'never_block' THEN 1 ELSE 0 END) = 1 THEN 'never_block'
        ELSE 'active'
    END as status,
    MIN(start_dt) as first_violation_dt,
    MAX(start_dt) as last_violation_dt,
    COUNT(*) as total_violations,
    CASE
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 99999 THEN 18
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 65536 THEN 17
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 32768 THEN 16
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 16384 THEN 15
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 8192 THEN 14
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 4096 THEN 13
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 2048 THEN 12
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 1024 THEN 11
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 512 THEN 10
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 256 THEN 9
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 128 THEN 8
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 64 THEN 7
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 32 THEN 6
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 16 THEN 5
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 8 THEN 4
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 4 THEN 3
        WHEN MAX(TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)) >= 2 THEN 2
        ELSE 1
    END as lockout_level
FROM bg_lockout
GROUP BY ip, type, session_id;

-- Verify consolidation
SELECT
    COUNT(*) as consolidated_count,
    SUM(total_violations) as total_original_records,
    MIN(total_violations) as min_violations_per_group,
    MAX(total_violations) as max_violations_per_group
FROM lockout_consolidated;

-- Step 2.4: Replace bg_lockout with consolidated data
-- ---------------------------------------------------------------------
TRUNCATE TABLE bg_lockout;

INSERT INTO bg_lockout
    (id, ip, type, session_id, start_dt, expire_dt, create_dt, modify_dt, status,
     first_violation_dt, last_violation_dt, total_violations, lockout_level)
SELECT * FROM lockout_consolidated;

-- Verify counts
SELECT
    (SELECT COUNT(*) FROM bg_lockout) as new_parent_count,
    (SELECT COUNT(*) FROM bg_lockout_history) as history_count,
    (SELECT COUNT(*) FROM bg_lockout_backup_20251004) as backup_count;

-- Clean up temp table
DROP TEMPORARY TABLE lockout_consolidated;

-- Step 2.5: Link history records to parent records
-- ---------------------------------------------------------------------
UPDATE bg_lockout_history h
INNER JOIN bg_lockout p
    ON h.ip = p.ip
    AND h.type = p.type
    AND COALESCE(h.session_id, '') = COALESCE(p.session_id, '')
SET h.parent_id = p.id;

-- Verify all history records have a parent
SELECT
    COUNT(*) as total_history,
    SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as linked_count,
    SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as orphaned_count
FROM bg_lockout_history;

-- Step 2.6: Add foreign key constraint
-- ---------------------------------------------------------------------
ALTER TABLE bg_lockout_history
    ADD CONSTRAINT fk_lockout_parent
    FOREIGN KEY (parent_id)
    REFERENCES bg_lockout(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

-- Final verification
-- ---------------------------------------------------------------------
SELECT 'Step 2 Complete: Data migration complete' as status;

-- Show sample consolidated record
SELECT
    p.id,
    p.ip,
    p.lockout_level,
    p.total_violations,
    p.first_violation_dt,
    p.last_violation_dt,
    COUNT(h.id) as history_count
FROM bg_lockout p
LEFT JOIN bg_lockout_history h ON h.parent_id = p.id
GROUP BY p.id
ORDER BY p.total_violations DESC
LIMIT 5;
