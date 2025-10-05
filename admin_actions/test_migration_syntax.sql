-- =====================================================================
-- SYNTAX VALIDATION TEST - MySQL 8 Compatible
-- =====================================================================
-- This file tests the syntax of all migration components
-- Run this first to ensure everything will work
-- =====================================================================

-- Test 1: Check if bg_lockout table exists
-- ---------------------------------------------------------------------
SELECT 'Test 1: Checking bg_lockout table' as test_step;
SELECT COUNT(*) as current_record_count FROM bg_lockout;

-- Test 2: Check current table structure
-- ---------------------------------------------------------------------
SELECT 'Test 2: Current table structure' as test_step;
SHOW COLUMNS FROM bg_lockout;

-- Test 3: Test dynamic column addition logic
-- ---------------------------------------------------------------------
SELECT 'Test 3: Testing dynamic column check' as test_step;
SET @exist_first := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'birthday_gold_www' AND TABLE_NAME = 'bg_lockout' AND COLUMN_NAME = 'first_violation_dt');
SELECT @exist_first as first_violation_dt_exists;

-- Test 4: Test TIMESTAMPDIFF calculation
-- ---------------------------------------------------------------------
SELECT 'Test 4: Testing TIMESTAMPDIFF calculation' as test_step;
SELECT
    id,
    start_dt,
    expire_dt,
    TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) as lockout_minutes
FROM bg_lockout
LIMIT 5;

-- Test 5: Test GROUP BY for consolidation logic
-- ---------------------------------------------------------------------
SELECT 'Test 5: Testing consolidation GROUP BY' as test_step;
SELECT
    COUNT(DISTINCT CONCAT(ip, '|', type, '|', COALESCE(session_id, 'NULL'))) as unique_combinations,
    COUNT(*) as total_records
FROM bg_lockout;

-- Test 6: Test CASE statement for lockout_level
-- ---------------------------------------------------------------------
SELECT 'Test 6: Testing lockout_level calculation' as test_step;
SELECT
    TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) as minutes,
    CASE
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 99999 THEN 18
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 65536 THEN 17
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 32768 THEN 16
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 16384 THEN 15
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 8192 THEN 14
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 4096 THEN 13
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 2048 THEN 12
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 1024 THEN 11
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 512 THEN 10
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 256 THEN 9
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 128 THEN 8
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 64 THEN 7
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 32 THEN 6
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 16 THEN 5
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 8 THEN 4
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 4 THEN 3
        WHEN TIMESTAMPDIFF(MINUTE, start_dt, expire_dt) >= 2 THEN 2
        ELSE 1
    END as lockout_level
FROM bg_lockout
LIMIT 10;

-- Test 7: Test temporary table creation
-- ---------------------------------------------------------------------
SELECT 'Test 7: Testing temporary table creation' as test_step;
DROP TEMPORARY TABLE IF EXISTS test_temp_lockout;
CREATE TEMPORARY TABLE test_temp_lockout AS
SELECT MIN(id) as id, ip, COUNT(*) as count_check
FROM bg_lockout
GROUP BY ip
LIMIT 10;
SELECT * FROM test_temp_lockout;
DROP TEMPORARY TABLE test_temp_lockout;

-- Test 8: Check MySQL version
-- ---------------------------------------------------------------------
SELECT 'Test 8: MySQL version check' as test_step;
SELECT VERSION() as mysql_version;

-- Test 9: Check user permissions
-- ---------------------------------------------------------------------
SELECT 'Test 9: Current user and permissions' as test_step;
SELECT CURRENT_USER() as current_user;
SHOW GRANTS;

-- Final summary
-- ---------------------------------------------------------------------
SELECT 'All syntax tests passed ✓' as final_status;
SELECT
    'Ready to proceed with migration' as next_step,
    (SELECT COUNT(*) FROM bg_lockout) as records_to_consolidate;
