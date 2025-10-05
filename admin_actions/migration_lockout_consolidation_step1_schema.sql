-- =====================================================================
-- LOCKOUT CONSOLIDATION MIGRATION - STEP 1: SCHEMA CHANGES
-- =====================================================================
-- Purpose: Create bg_lockout_history table and add tracking columns
-- Date: 2025-10-04
-- =====================================================================

-- Step 1.1: Create bg_lockout_history table
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bg_lockout_history (
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
    lockout_minutes INT COMMENT 'TIMESTAMPDIFF(MINUTE, start_dt, expire_dt)',

    INDEX idx_parent_id (parent_id),
    INDEX idx_ip (ip),
    INDEX idx_session (session_id),
    INDEX idx_create_dt (create_dt),
    INDEX idx_status (status),
    INDEX idx_lockout_minutes (lockout_minutes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Audit trail of all lockout escalations';

-- Step 1.2: Add tracking columns to bg_lockout
-- ---------------------------------------------------------------------
-- Check if columns exist before adding them
SET @exist_first := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'birthday_gold_www' AND TABLE_NAME = 'bg_lockout' AND COLUMN_NAME = 'first_violation_dt');
SET @exist_last := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'birthday_gold_www' AND TABLE_NAME = 'bg_lockout' AND COLUMN_NAME = 'last_violation_dt');
SET @exist_total := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'birthday_gold_www' AND TABLE_NAME = 'bg_lockout' AND COLUMN_NAME = 'total_violations');
SET @exist_level := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'birthday_gold_www' AND TABLE_NAME = 'bg_lockout' AND COLUMN_NAME = 'lockout_level');

SET @sql_first = IF(@exist_first = 0,
    'ALTER TABLE bg_lockout ADD COLUMN first_violation_dt DATETIME NULL COMMENT "When first lockout occurred" AFTER status',
    'SELECT "Column first_violation_dt already exists" as status');
SET @sql_last = IF(@exist_last = 0,
    'ALTER TABLE bg_lockout ADD COLUMN last_violation_dt DATETIME NULL COMMENT "Most recent violation" AFTER first_violation_dt',
    'SELECT "Column last_violation_dt already exists" as status');
SET @sql_total = IF(@exist_total = 0,
    'ALTER TABLE bg_lockout ADD COLUMN total_violations INT DEFAULT 0 COMMENT "Count of all escalations" AFTER last_violation_dt',
    'SELECT "Column total_violations already exists" as status');
SET @sql_level = IF(@exist_level = 0,
    'ALTER TABLE bg_lockout ADD COLUMN lockout_level INT DEFAULT 0 COMMENT "Current escalation level (1-18)" AFTER total_violations',
    'SELECT "Column lockout_level already exists" as status');

PREPARE stmt FROM @sql_first; EXECUTE stmt; DEALLOCATE PREPARE stmt;
PREPARE stmt FROM @sql_last; EXECUTE stmt; DEALLOCATE PREPARE stmt;
PREPARE stmt FROM @sql_total; EXECUTE stmt; DEALLOCATE PREPARE stmt;
PREPARE stmt FROM @sql_level; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Step 1.3: Add indexes to bg_lockout
-- ---------------------------------------------------------------------
-- Check if indexes exist before creating them
SET @idx_active := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'birthday_gold_www' AND TABLE_NAME = 'bg_lockout' AND INDEX_NAME = 'idx_lockout_active');
SET @idx_level := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'birthday_gold_www' AND TABLE_NAME = 'bg_lockout' AND INDEX_NAME = 'idx_lockout_level');
SET @idx_dates := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'birthday_gold_www' AND TABLE_NAME = 'bg_lockout' AND INDEX_NAME = 'idx_violation_dates');

SET @sql_idx_active = IF(@idx_active = 0,
    'CREATE INDEX idx_lockout_active ON bg_lockout(ip, session_id, status, expire_dt)',
    'SELECT "Index idx_lockout_active already exists" as status');
SET @sql_idx_level = IF(@idx_level = 0,
    'CREATE INDEX idx_lockout_level ON bg_lockout(lockout_level)',
    'SELECT "Index idx_lockout_level already exists" as status');
SET @sql_idx_dates = IF(@idx_dates = 0,
    'CREATE INDEX idx_violation_dates ON bg_lockout(first_violation_dt, last_violation_dt)',
    'SELECT "Index idx_violation_dates already exists" as status');

PREPARE stmt FROM @sql_idx_active; EXECUTE stmt; DEALLOCATE PREPARE stmt;
PREPARE stmt FROM @sql_idx_level; EXECUTE stmt; DEALLOCATE PREPARE stmt;
PREPARE stmt FROM @sql_idx_dates; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verification
-- ---------------------------------------------------------------------
SELECT 'Step 1 Complete: Schema changes applied' as status;
SHOW COLUMNS FROM bg_lockout_history;
SHOW COLUMNS FROM bg_lockout LIKE '%violation%';
SHOW COLUMNS FROM bg_lockout LIKE '%level%';
