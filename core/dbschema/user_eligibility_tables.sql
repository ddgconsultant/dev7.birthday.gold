-- User Eligibility System Tables
-- Purpose: Track and display enrollment eligibility issues at the company level
-- Created: 2025-08-02

-- Main eligibility tracking table
CREATE TABLE IF NOT EXISTS bg_user_eligibility (
    member_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    reason_id TINYINT UNSIGNED NOT NULL,
    last_checked TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (member_id, company_id),
    INDEX idx_company (company_id),
    INDEX idx_last_checked (last_checked)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED COMMENT='Tracks user eligibility issues for each company';

-- Reason lookup table
CREATE TABLE IF NOT EXISTS bg_eligibility_reasons (
    id TINYINT UNSIGNED PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    message VARCHAR(100) NOT NULL,
    category ENUM('profile', 'verification', 'security', 'restriction', 'account') NOT NULL,
    INDEX idx_category (category),
    INDEX idx_code (code)
) ENGINE=InnoDB COMMENT='Lookup table for eligibility failure reasons';

-- Seed eligibility reasons
INSERT INTO bg_eligibility_reasons (id, code, message, category) VALUES
-- Profile Issues (1-20)
(1, 'incomplete_profile', 'Complete your profile to enroll', 'profile'),
(2, 'missing_phone', 'Phone number required', 'profile'),
(3, 'missing_email', 'Email address required', 'profile'),
(4, 'missing_birthdate', 'Birth date required', 'profile'),
(5, 'missing_address', 'Address required', 'profile'),
(6, 'missing_name', 'Full name required', 'profile'),
(7, 'invalid_phone', 'Valid phone number required', 'profile'),
(8, 'invalid_email', 'Valid email address required', 'profile'),

-- Verification Issues (21-40)
(21, 'email_unverified', 'Email verification required', 'verification'),
(22, 'phone_unverified', 'Phone verification required', 'verification'),
(23, 'identity_unverified', 'Identity verification required', 'verification'),
(24, 'address_unverified', 'Address verification required', 'verification'),

-- Password/Security Issues (40-60)
(40, 'password_requirements', 'Password does not meet requirements', 'security'),
(41, 'missing_password', 'Password required', 'security'),
(42, 'weak_password', 'Stronger password required (8+ characters)', 'security'),
(43, 'password_no_number', 'Password must include a number', 'security'),
(44, 'password_no_special', 'Password must include special character', 'security'),
(45, 'password_no_uppercase', 'Password must include uppercase letter', 'security'),
(46, 'password_common', 'Password too common - choose another', 'security'),
(47, 'password_expired', 'Password expired - update required', 'security'),
(48, 'mfa_required', 'Two-factor authentication required', 'security'),

-- Restriction Issues (61-80)
(61, 'age_restriction', 'Age requirement not met', 'restriction'),
(62, 'location_restricted', 'Not available in your area', 'restriction'),
(63, 'country_restricted', 'Not available in your country', 'restriction'),
(64, 'employee_only', 'Employee accounts only', 'restriction'),
(65, 'invite_only', 'Invitation required', 'restriction'),

-- Account Status Issues (81-100)
(81, 'account_suspended', 'Account suspended', 'account'),
(82, 'account_inactive', 'Account inactive too long', 'account'),
(83, 'payment_required', 'Valid payment method required', 'account'),
(84, 'terms_not_accepted', 'Accept terms and conditions', 'account'),
(85, 'min_purchases', 'Minimum purchase requirement not met', 'account'),
(86, 'account_too_new', 'Account age requirement not met', 'account'),
(87, 'fraud_flag', 'Account under review', 'account')
ON DUPLICATE KEY UPDATE 
    message = VALUES(message),
    category = VALUES(category);

-- Company requirements table (for storing company-specific eligibility rules)
CREATE TABLE IF NOT EXISTS bg_company_requirements (
    company_id BIGINT UNSIGNED PRIMARY KEY,
    requires_phone TINYINT(1) DEFAULT 0,
    requires_birthdate TINYINT(1) DEFAULT 1,
    requires_address TINYINT(1) DEFAULT 0,
    requires_email_verification TINYINT(1) DEFAULT 0,
    requires_phone_verification TINYINT(1) DEFAULT 0,
    minimum_age TINYINT UNSIGNED DEFAULT NULL,
    maximum_age TINYINT UNSIGNED DEFAULT NULL,
    restricted_states TEXT DEFAULT NULL COMMENT 'JSON array of restricted state codes',
    restricted_countries TEXT DEFAULT NULL COMMENT 'JSON array of restricted country codes',
    password_min_length TINYINT UNSIGNED DEFAULT 8,
    password_requires_number TINYINT(1) DEFAULT 0,
    password_requires_special TINYINT(1) DEFAULT 0,
    password_requires_uppercase TINYINT(1) DEFAULT 0,
    custom_requirements TEXT DEFAULT NULL COMMENT 'JSON object for additional requirements',
    create_dt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modify_dt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_modify_dt (modify_dt)
) ENGINE=InnoDB COMMENT='Company-specific eligibility requirements';