-- =====================================================
-- Simplified Product Features (Component-Driven)
-- =====================================================
-- Now features are driven by component files in /myaccount/components/
-- Database only needs to store feature name and basic flags
-- Component handles all display logic and configuration
-- =====================================================

-- First deactivate all old feature entries
UPDATE bg_product_features
SET status = 'inactive',
    modify_dt = NOW()
WHERE name LIKE 'feature_%'
AND product_id IS NOT NULL
AND status = 'active';

-- =====================================================
-- GOLD PLANS - feature_email only
-- =====================================================

-- V2 Gold Plans
INSERT INTO bg_product_features (product_id, version, name, value, status, display_mode, create_dt, modify_dt)
VALUES
(11, 'v2', 'feature_email', '1', 'active', 'show', NOW(), NOW()),    -- Gold user
(41, 'v2', 'feature_email', '1', 'active', 'show', NOW(), NOW()),    -- Gold parental
(71, 'v2', 'feature_email', '1', 'active', 'show', NOW(), NOW());    -- Gold minor

-- V3 Gold Plans
INSERT INTO bg_product_features (product_id, version, name, value, status, display_mode, create_dt, modify_dt)
VALUES
(320, 'v3', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Super Admin Gold
(321, 'v3', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Gold user
(351, 'v3', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Gold parental
(381, 'v3', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Gold minor
(360, 'v3', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Family Plan
(365, 'v3', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Corporate Plan
(369, 'v3', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Business Plan
(301, 'v3', 'feature_email', '1', 'active', 'show', NOW(), NOW());   -- Gift Certificate

-- V7 Gold Plans
INSERT INTO bg_product_features (product_id, version, name, value, status, display_mode, create_dt, modify_dt)
VALUES
(441, 'v7', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Gold user
(471, 'v7', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Family Gold
(481, 'v7', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Business Gold
(491, 'v7', 'feature_email', '1', 'active', 'show', NOW(), NOW()),   -- Gift Certificate Gold
(995501, 'v7', 'feature_email', '1', 'active', 'show', NOW(), NOW()), -- Business Enterprise Monthly
(995511, 'v7', 'feature_email', '1', 'active', 'show', NOW(), NOW()); -- Business Trial Monthly

-- =====================================================
-- LIFE PLANS - All features
-- V2 Life Plans: 20, 21, 51, 81
-- =====================================================

-- V2 Life Plans
INSERT INTO bg_product_features (product_id, version, name, value, status, display_mode, create_dt, modify_dt)
VALUES
(20, 'v2', 'feature_email', '1', 'active', 'show', NOW(), NOW()),
(20, 'v2', 'feature_inbox', '1', 'active', 'show', NOW(), NOW()),
(20, 'v2', 'feature_premium_support', '1', 'active', 'show', NOW(), NOW()),
(20, 'v2', 'feature_advanced_analytics', '1', 'active', 'show', NOW(), NOW()),
(21, 'v2', 'feature_email', '1', 'active', 'show', NOW(), NOW()),
(21, 'v2', 'feature_inbox', '1', 'active', 'show', NOW(), NOW()),
(21, 'v2', 'feature_premium_support', '1', 'active', 'show', NOW(), NOW()),
(21, 'v2', 'feature_advanced_analytics', '1', 'active', 'show', NOW(), NOW()),
(51, 'v2', 'feature_email', '1', 'active', 'show', NOW(), NOW()),
(51, 'v2', 'feature_inbox', '1', 'active', 'show', NOW(), NOW()),
(51, 'v2', 'feature_premium_support', '1', 'active', 'show', NOW(), NOW()),
(51, 'v2', 'feature_advanced_analytics', '1', 'active', 'show', NOW(), NOW()),
(81, 'v2', 'feature_email', '1', 'active', 'show', NOW(), NOW()),
(81, 'v2', 'feature_inbox', '1', 'active', 'show', NOW(), NOW()),
(81, 'v2', 'feature_premium_support', '1', 'active', 'show', NOW(), NOW()),
(81, 'v2', 'feature_advanced_analytics', '1', 'active', 'show', NOW(), NOW());

-- =====================================================
-- SUPER ADMIN / SPECIAL PLANS - All features
-- Product 320 (Super Admin Gold)
-- =====================================================

INSERT INTO bg_product_features (product_id, version, name, value, status, display_mode, create_dt, modify_dt)
VALUES
(320, 'v3', 'feature_inbox', '1', 'active', 'show', NOW(), NOW()),
(320, 'v3', 'feature_premium_support', '1', 'active', 'show', NOW(), NOW()),
(320, 'v3', 'feature_advanced_analytics', '1', 'active', 'show', NOW(), NOW());

-- =====================================================
-- Verification
-- =====================================================
SELECT
    product_id,
    name,
    value,
    status,
    display_mode
FROM bg_product_features
WHERE name LIKE 'feature_%'
AND product_id IS NOT NULL
AND status = 'active'
ORDER BY product_id, name;
