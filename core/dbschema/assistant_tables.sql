-- Voice Assistant Integration Tables
-- Uses existing bg_validations for linking codes

-- OAuth tokens for voice assistants
CREATE TABLE IF NOT EXISTS `bg_assistant_tokens` (
    `token_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `platform` ENUM('google', 'alexa', 'siri') NOT NULL,
    `device_id` VARCHAR(100) NULL,
    `access_token` VARCHAR(255) NOT NULL UNIQUE,
    `refresh_token` VARCHAR(255) NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL,
    `last_used` DATETIME NULL,
    INDEX `idx_user_platform` (`user_id`, `platform`),
    INDEX `idx_access_token` (`access_token`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Track voice queries for analytics
CREATE TABLE IF NOT EXISTS `bg_assistant_queries` (
    `query_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NULL,
    `platform` VARCHAR(20) NOT NULL,
    `intent` VARCHAR(100) NULL,
    `query_text` TEXT NULL,
    `response_text` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_user_queries` (`user_id`, `created_at`),
    INDEX `idx_platform_intent` (`platform`, `intent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- OAuth authorization codes for account linking
CREATE TABLE IF NOT EXISTS `bg_oauth_codes` (
    `code_id` INT PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(255) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `client_id` VARCHAR(255) NOT NULL,
    `redirect_uri` TEXT NOT NULL,
    `platform` VARCHAR(20) NULL,
    `created_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    INDEX `idx_code` (`code`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;