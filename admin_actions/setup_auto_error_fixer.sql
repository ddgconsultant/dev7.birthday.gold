-- Auto Error Fixer Database Schema
-- Creates table and initial configuration for the auto error fixing system

CREATE TABLE IF NOT EXISTS bg_auto_error_fixes (
    fix_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Error identification
    error_hash VARCHAR(64) UNIQUE COMMENT 'MD5(file:line:type:message)',
    error_file VARCHAR(500) NOT NULL,
    error_line INT NOT NULL,
    error_message TEXT NOT NULL,
    error_type VARCHAR(50) NOT NULL COMMENT 'Fatal error, Warning, Notice, etc.',
    error_context TEXT COMMENT '20 lines of code around error',

    -- Occurrence tracking
    first_seen DATETIME NOT NULL,
    last_seen DATETIME NOT NULL,
    occurrence_count INT DEFAULT 1,

    -- Fix workflow
    fix_status VARCHAR(50) DEFAULT 'pending_analysis' COMMENT 'pending_analysis|pending_review|approved_pending_apply|applied|rejected|failed_to_apply|auto_ignored',

    -- AI analysis
    ai_analyzed_dt DATETIME NULL,
    ai_model VARCHAR(100) COMMENT 'e.g., claude-3-5-sonnet-20241022',
    ai_confidence INT COMMENT '0-100',
    ai_fix_type VARCHAR(50) COMMENT 'null_check, typo, missing_var, etc.',
    ai_explanation TEXT,
    ai_review_reason TEXT COMMENT 'Why it needs review (if applicable)',

    -- Code changes
    original_code TEXT COMMENT 'Backup for rollback',
    proposed_fix TEXT COMMENT 'Full fixed code block',
    line_start INT COMMENT 'Where fix starts',
    line_end INT COMMENT 'Where fix ends',

    -- Application tracking
    applied_dt DATETIME NULL,
    applied_by VARCHAR(50) COMMENT 'auto_scheduler',
    git_commit_hash VARCHAR(40) COMMENT 'Git SHA after applying',
    syntax_check_passed BOOLEAN,
    apply_error_message TEXT COMMENT 'Error if application failed',

    -- Review tracking
    reviewed_by INT NULL COMMENT 'user_id from bg_users',
    reviewed_dt DATETIME NULL,
    review_notes TEXT,
    review_token VARCHAR(64) COMMENT 'Unique token for review link',

    -- Metadata
    created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status (fix_status),
    INDEX idx_error_hash (error_hash),
    INDEX idx_review_token (review_token),
    INDEX idx_first_seen (first_seen),
    INDEX idx_last_seen (last_seen),
    INDEX idx_error_file (error_file)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Auto Error Fixer: Tracks PHP errors and AI-generated fixes';

-- Insert initial configuration
INSERT INTO bg_config (`config_type`, `config_key`, `config_value`, `status`, `display_order`)
VALUES
    ('auto_error_fixer', 'last_run_timestamp', '2025-01-01 00:00:00', '1', 0),
    ('auto_error_fixer', 'max_errors_per_run', '5', '1', 1),
    ('auto_error_fixer', 'max_fixes_per_run', '10', '1', 2),
    ('auto_error_fixer', 'enabled', 'true', '1', 3),
    ('auto_error_fixer', 'notification_channel', 'rocketchat', '1', 4),
    ('auto_error_fixer', 'min_ai_confidence', '85', '1', 5),
    ('auto_error_fixer', 'base_review_url', 'https://dev7.birthday.gold/admin/error-fix-review.php', '1', 6)
ON DUPLICATE KEY UPDATE
    `config_value`=VALUES(`config_value`);

-- Create blacklist patterns table
CREATE TABLE IF NOT EXISTS bg_auto_error_fixer_blacklist (
    blacklist_id INT AUTO_INCREMENT PRIMARY KEY,
    pattern_type VARCHAR(50) NOT NULL COMMENT 'file_path|error_message|file_pattern',
    pattern VARCHAR(500) NOT NULL,
    reason TEXT,
    created_by INT NULL,
    created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
    `status` VARCHAR(20) DEFAULT 'active' COMMENT 'active|inactive',

    INDEX idx_pattern_type (pattern_type),
    INDEX idx_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Blacklist patterns for auto error fixer';

-- Insert default blacklist patterns (security-critical files)
INSERT INTO bg_auto_error_fixer_blacklist (`pattern_type`, `pattern`, `reason`, `status`)
VALUES
    ('file_pattern', '%/core/classes/class.database.php', 'Database class - manual review only', 'active'),
    ('file_pattern', '%/core/classes/class.payment.php', 'Payment processing - manual review only', 'active'),
    ('file_pattern', '%/core/classes/class.security.php', 'Security class - manual review only', 'active'),
    ('file_pattern', '%authentication%', 'Authentication code - manual review only', 'active'),
    ('file_pattern', '%login%', 'Login code - manual review only', 'active'),
    ('file_pattern', '%/core/config/%', 'Configuration files - manual review only', 'active')
ON DUPLICATE KEY UPDATE `reason`=VALUES(`reason`);
