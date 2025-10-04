-- =====================================================================
-- LOCKOUT CONSOLIDATION MIGRATION - STEP 1: SCHEMA (SIMPLE VERSION)
-- =====================================================================
-- MySQL 8 Compatible - No IF NOT EXISTS syntax
-- Purpose: Create bg_lockout_history table and add tracking columns
-- Date: 2025-10-04
-- =====================================================================

-- Step 1.1: Create bg_lockout_history table
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS bg_lockout_history;

CREATE TABLE bg_lockout_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT NULL COMMENT 'References bg_lockout.id',
    ip VARCHAR(62),
    type VARCHAR(255),
    session_id VARCHAR(255),
    start_dt DATETIME,
    expire_dt DATETIME,
    create_dt DATETIME,
    modify_dt DATETIME,
    status VARCHAR(32) DEFAULT 'active',
    lockout_minutes BIGINT COMMENT 'TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)',

    INDEX idx_parent_id (parent_id),
    INDEX idx_ip (ip),
    INDEX idx_session (session_id),
    INDEX idx_create_dt (create_dt),
    INDEX idx_status (status),
    INDEX idx_lockout_minutes (lockout_minutes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Audit trail of all lockout escalations';

SELECT 'bg_lockout_history table created' as status;

-- Step 1.2: Add tracking columns to bg_lockout (if they don't exist)
-- ---------------------------------------------------------------------
-- Add first_violation_dt
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'birthday_gold_www'
  AND TABLE_NAME = 'bg_lockout'
  AND COLUMN_NAME = 'first_violation_dt';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE bg_lockout ADD COLUMN first_violation_dt DATETIME NULL COMMENT "When first lockout occurred" AFTER status',
    'SELECT "first_violation_dt already exists" as msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add last_violation_dt
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'birthday_gold_www'
  AND TABLE_NAME = 'bg_lockout'
  AND COLUMN_NAME = 'last_violation_dt';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE bg_lockout ADD COLUMN last_violation_dt DATETIME NULL COMMENT "Most recent violation" AFTER first_violation_dt',
    'SELECT "last_violation_dt already exists" as msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add total_violations
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'birthday_gold_www'
  AND TABLE_NAME = 'bg_lockout'
  AND COLUMN_NAME = 'total_violations';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE bg_lockout ADD COLUMN total_violations INT DEFAULT 0 COMMENT "Count of all escalations" AFTER last_violation_dt',
    'SELECT "total_violations already exists" as msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add lockout_level
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'birthday_gold_www'
  AND TABLE_NAME = 'bg_lockout'
  AND COLUMN_NAME = 'lockout_level';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE bg_lockout ADD COLUMN lockout_level INT DEFAULT 0 COMMENT "Current escalation level (1-18)" AFTER total_violations',
    'SELECT "lockout_level already exists" as msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Tracking columns added to bg_lockout' as status;

-- Step 1.3: Add indexes to bg_lockout (if they don't exist)
-- ---------------------------------------------------------------------
-- Add idx_lockout_active
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'birthday_gold_www'
  AND TABLE_NAME = 'bg_lockout'
  AND INDEX_NAME = 'idx_lockout_active';

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_lockout_active ON bg_lockout(ip, session_id, status, expire_dt)',
    'SELECT "idx_lockout_active already exists" as msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add idx_lockout_level
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'birthday_gold_www'
  AND TABLE_NAME = 'bg_lockout'
  AND INDEX_NAME = 'idx_lockout_level';

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_lockout_level ON bg_lockout(lockout_level)',
    'SELECT "idx_lockout_level already exists" as msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add idx_violation_dates
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'birthday_gold_www'
  AND TABLE_NAME = 'bg_lockout'
  AND INDEX_NAME = 'idx_violation_dates';

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_violation_dates ON bg_lockout(first_violation_dt, last_violation_dt)',
    'SELECT "idx_violation_dates already exists" as msg');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Indexes added to bg_lockout' as status;

-- Verification
-- ---------------------------------------------------------------------
SELECT 'Step 1 Complete: Schema changes applied' as status;

SELECT 'New columns on bg_lockout:' as verification;
SHOW COLUMNS FROM bg_lockout LIKE '%violation%';
SHOW COLUMNS FROM bg_lockout LIKE 'lockout_level';

SELECT 'New table bg_lockout_history:' as verification;
SHOW COLUMNS FROM bg_lockout_history;

SELECT 'Indexes on bg_lockout:' as verification;
SHOW INDEX FROM bg_lockout WHERE Key_name LIKE 'idx_lockout%' OR Key_name LIKE 'idx_violation%';
