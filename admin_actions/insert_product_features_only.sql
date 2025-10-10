-- =====================================================
-- Product Features Population SQL Script (INSERT ONLY)
-- =====================================================
-- This script adds feature metadata to bg_product_features
-- for Gold and Life plans.
--
-- NOTE: You may need to manually delete old entries first if re-running
--
-- Run this with:
-- mysql -h "71.33.250.235" -u "USER" -p "birthday_gold_www" < insert_product_features_only.sql
-- =====================================================

-- =====================================================
-- GOLD PLANS - feature_email only
-- Product IDs: 11, 321, 441 (user gold variants)
--              41, 351, 471 (parental gold variants)
-- =====================================================

-- Product 11 (Gold)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (11, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- Product 321 (Gold)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (321, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- Product 441 (User Gold)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (441, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- Product 41 (Parental Gold)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (41, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- Product 351 (Gold Parental)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (351, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- Product 471 (Family Gold)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (471, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- =====================================================
-- LIFE PLANS - All features
-- Product IDs: 21 (user life)
--              51 (parental life)
-- =====================================================

-- Product 21 (Life) - feature_email
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (21, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- Product 21 (Life) - feature_inbox
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (21, 'v3', NULL, 'feature_inbox',
'{"display_name":"Email Management Dashboard","display_description":"View and manage all birthday-related emails in one place","icon":"bi-inbox-fill","setup_url":"/myaccount/components/feature_inbox.php","settings_url":"/myaccount/mail-box","user_column":"feature_inbox","display_order":2}',
'active', 'show', NOW(), NOW());

-- Product 21 (Life) - feature_premium_support
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (21, 'v3', NULL, 'feature_premium_support',
'{"display_name":"Premium Support Access","display_description":"Priority support via live chat and email","icon":"bi-headset","setup_url":null,"settings_url":"/myaccount/support","user_column":"feature_premium_support","display_order":3}',
'active', 'show', NOW(), NOW());

-- Product 21 (Life) - feature_advanced_analytics
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (21, 'v3', NULL, 'feature_advanced_analytics',
'{"display_name":"Advanced Birthday Analytics","display_description":"Detailed insights and statistics about your birthday rewards","icon":"bi-graph-up-arrow","setup_url":null,"settings_url":"/myaccount/analytics","user_column":"feature_advanced_analytics","display_order":4}',
'active', 'show', NOW(), NOW());

-- Product 51 (Parental Life) - feature_email
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (51, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- Product 51 (Parental Life) - feature_inbox
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (51, 'v3', NULL, 'feature_inbox',
'{"display_name":"Email Management Dashboard","display_description":"View and manage all birthday-related emails in one place","icon":"bi-inbox-fill","setup_url":"/myaccount/components/feature_inbox.php","settings_url":"/myaccount/mail-box","user_column":"feature_inbox","display_order":2}',
'active', 'show', NOW(), NOW());

-- Product 51 (Parental Life) - feature_premium_support
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (51, 'v3', NULL, 'feature_premium_support',
'{"display_name":"Premium Support Access","display_description":"Priority support via live chat and email","icon":"bi-headset","setup_url":null,"settings_url":"/myaccount/support","user_column":"feature_premium_support","display_order":3}',
'active', 'show', NOW(), NOW());

-- Product 51 (Parental Life) - feature_advanced_analytics
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (51, 'v3', NULL, 'feature_advanced_analytics',
'{"display_name":"Advanced Birthday Analytics","display_description":"Detailed insights and statistics about your birthday rewards","icon":"bi-graph-up-arrow","setup_url":null,"settings_url":"/myaccount/analytics","user_column":"feature_advanced_analytics","display_order":4}',
'active', 'show', NOW(), NOW());

-- =====================================================
-- Verification Query (Comment out to skip)
-- =====================================================
-- SELECT
--     product_id,
--     name,
--     JSON_EXTRACT(value, '$.display_name') as display_name,
--     status,
--     display_mode
-- FROM bg_product_features
-- WHERE name LIKE 'feature_%'
-- AND product_id IS NOT NULL
-- AND display_mode = 'show'
-- ORDER BY product_id, JSON_EXTRACT(value, '$.display_order');
