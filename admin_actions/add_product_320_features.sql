-- =====================================================
-- Add Features for Product 320 (Super Admin Gold)
-- =====================================================

-- First, deactivate the old entry with value='1'
UPDATE bg_product_features
SET status = 'inactive',
    modify_dt = NOW()
WHERE name = 'feature_email'
AND product_id = 320
AND value = '1';

-- Add feature_email with proper JSON metadata for Product 320
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (320, 'v3', NULL, 'feature_email',
'{"display_name":"Birthday Gold Inbox","display_description":"Managed email address for all your birthday enrollments","icon":"bi-envelope-paper-heart","setup_url":"/myaccount/components/feature_email.php","settings_url":"/myaccount/mail-box#settings","user_column":"feature_email","display_order":1}',
'active', 'show', NOW(), NOW());

-- Since this is a Super Admin account, give it all Life features
INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (320, 'v3', NULL, 'feature_inbox',
'{"display_name":"Email Management Dashboard","display_description":"View and manage all birthday-related emails in one place","icon":"bi-inbox-fill","setup_url":"/myaccount/components/feature_inbox.php","settings_url":"/myaccount/mail-box","user_column":"feature_inbox","display_order":2}',
'active', 'show', NOW(), NOW());

INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (320, 'v3', NULL, 'feature_premium_support',
'{"display_name":"Premium Support Access","display_description":"Priority support via live chat and email","icon":"bi-headset","setup_url":null,"settings_url":"/myaccount/support","user_column":"feature_premium_support","display_order":3}',
'active', 'show', NOW(), NOW());

INSERT INTO bg_product_features (product_id, version, plan, name, value, status, display_mode, create_dt, modify_dt)
VALUES (320, 'v3', NULL, 'feature_advanced_analytics',
'{"display_name":"Advanced Birthday Analytics","display_description":"Detailed insights and statistics about your birthday rewards","icon":"bi-graph-up-arrow","setup_url":null,"settings_url":"/myaccount/analytics","user_column":"feature_advanced_analytics","display_order":4}',
'active', 'show', NOW(), NOW());

-- Verification
SELECT
    id,
    product_id,
    name,
    CASE
        WHEN LENGTH(value) > 50 THEN CONCAT(LEFT(value, 50), '...')
        ELSE value
    END as value_preview,
    status,
    display_mode
FROM bg_product_features
WHERE product_id = 320
AND name LIKE 'feature_%'
ORDER BY JSON_EXTRACT(value, '$.display_order');
