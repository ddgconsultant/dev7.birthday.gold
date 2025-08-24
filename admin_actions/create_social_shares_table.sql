-- Create table for social media share verifications
-- Run this migration with proper database permissions:
-- mysql -u root -p birthday_gold_www < create_social_shares_table.sql

CREATE TABLE IF NOT EXISTS bg_social_shares (
    share_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform VARCHAR(20),
    post_url VARCHAR(500) UNIQUE,
    hashtag_verified BOOLEAN DEFAULT FALSE,
    content_snippet TEXT,
    verification_data JSON,
    allocation_awarded BOOLEAN DEFAULT FALSE,
    allocation_id INT,
    status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
    submit_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
    verify_dt DATETIME,
    INDEX idx_user_date (user_id, submit_dt),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES bg_users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Grant permissions if needed
-- GRANT SELECT, INSERT, UPDATE, DELETE ON birthday_gold_www.bg_social_shares TO 'your_app_user'@'localhost';