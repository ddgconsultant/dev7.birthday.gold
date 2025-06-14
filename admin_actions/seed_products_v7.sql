-- Birthday.Gold v7 Products and Features
-- This creates NEW v7 products without affecting existing v2/v3 data
-- Full backwards compatibility maintained

-- Check what exists first:
-- SELECT version, account_type, account_plan, account_name, price, status FROM bg_products ORDER BY version, account_type, account_plan;

-- ============================================
-- INDIVIDUAL USER PLANS (v7)
-- ============================================

-- Free Individual Plan
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'user', 'free', 'Free Birthday Club', 'Start celebrating with basic birthday rewards', 0, 'active', 'yes', 'optional', '/register', 'individual', 'active');

-- Gold Individual Plan (Recommended)
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'user', 'gold', 'Gold Membership', 'Unlock 500+ birthday rewards and VIP experiences', 2997, 'active', 'yes', 'required', '/register', 'individual', 'active');

-- Lifetime Individual Plan
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'user', 'lifetime', 'Lifetime VIP', 'One payment, birthday rewards forever', 9997, 'active', 'yes', 'required', '/register', 'individual', 'active');

-- ============================================
-- FAMILY/PARENTAL PLANS (v7)
-- ============================================

-- Family Free Plan
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'parental', 'family_free', 'Family Starter', 'Track birthdays for up to 4 family members', 0, 'active', 'yes', 'optional', '/register-parental', 'family', 'active');

-- Family Gold Plan
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'parental', 'family_gold', 'Family Gold Pack', 'Birthday rewards for your whole family (up to 10)', 4997, 'active', 'yes', 'required', '/register-parental', 'family', 'active');

-- Family Lifetime Plan
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'parental', 'family_lifetime', 'Family Forever', 'Lifetime family access with unlimited members', 14997, 'active', 'yes', 'required', '/register-parental', 'family', 'active');

-- ============================================
-- BUSINESS PLANS (v7)
-- ============================================

-- Business Starter
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'business', 'business_starter', 'Business Starter', 'Perfect for small teams (up to 25 employees)', 9997, 'active', 'no', 'required', '/register-business', 'business', 'active');

-- Business Professional
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'business', 'business_pro', 'Business Professional', 'For growing companies (up to 100 employees)', 24997, 'active', 'no', 'required', '/register-business', 'business', 'active');

-- Business Enterprise
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'business', 'business_enterprise', 'Enterprise', 'Unlimited employees with API access', 49997, 'active', 'no', 'required', '/register-business', 'business', 'active');

-- ============================================
-- GIFT CERTIFICATE OPTIONS (v7)
-- ============================================

-- Gift Gold 1 Year
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'giftcertificate', 'gift_gold', 'Gold Gift - 1 Year', 'Give a year of birthday rewards', 2997, 'active', 'no', 'optional', '/register-giftcertificate', 'gift', 'active');

-- Gift Lifetime
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) 
VALUES ('v7', 'giftcertificate', 'gift_lifetime', 'Lifetime Gift', 'The ultimate birthday gift - lifetime access', 9997, 'active', 'no', 'optional', '/register-giftcertificate', 'gift', 'active');

-- ============================================
-- Now add FEATURES for each v7 product
-- ============================================

-- Get the actual product IDs and add features
-- This should be run AFTER inserting the products above

-- Features for Individual Free Plan
INSERT INTO bg_product_features (product_id, version, plan, name, value, status)
SELECT id, 'v7', 'free', 'enrollments', 'Up to 25 birthday programs', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'free'
UNION ALL
SELECT id, 'v7', 'free', 'tracking', 'Basic birthday calendar', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'free'
UNION ALL
SELECT id, 'v7', 'free', 'reminders', 'Email reminders 1 week before', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'free'
UNION ALL
SELECT id, 'v7', 'free', 'support', 'Community forum support', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'free';

-- Features for Individual Gold Plan
INSERT INTO bg_product_features (product_id, version, plan, name, value, status)
SELECT id, 'v7', 'gold', 'enrollments', '500+ birthday programs', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gold'
UNION ALL
SELECT id, 'v7', 'gold', 'automation', 'Auto-enrollment in all programs', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gold'
UNION ALL
SELECT id, 'v7', 'gold', 'tracking', 'Advanced calendar with maps', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gold'
UNION ALL
SELECT id, 'v7', 'gold', 'reminders', 'Email + SMS reminders', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gold'
UNION ALL
SELECT id, 'v7', 'gold', 'vip', 'VIP birthday experiences', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gold'
UNION ALL
SELECT id, 'v7', 'gold', 'support', 'Priority 24/7 support', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gold'
UNION ALL
SELECT id, 'v7', 'gold', 'deals', 'Year-round member deals', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gold';

-- Features for Individual Lifetime Plan
INSERT INTO bg_product_features (product_id, version, plan, name, value, status)
SELECT id, 'v7', 'lifetime', 'everything', 'Everything in Gold forever', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'lifetime'
UNION ALL
SELECT id, 'v7', 'lifetime', 'updates', 'All future features included', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'lifetime'
UNION ALL
SELECT id, 'v7', 'lifetime', 'referral', 'Earn $10 for each referral', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'lifetime'
UNION ALL
SELECT id, 'v7', 'lifetime', 'concierge', 'Birthday concierge service', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'lifetime'
UNION ALL
SELECT id, 'v7', 'lifetime', 'early', 'Early access to new partners', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'lifetime';

-- Features for Family Plans
INSERT INTO bg_product_features (product_id, version, plan, name, value, status)
-- Family Free
SELECT id, 'v7', 'family_free', 'members', 'Up to 4 family members', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_free'
UNION ALL
SELECT id, 'v7', 'family_free', 'calendar', 'Shared family calendar', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_free'
UNION ALL
SELECT id, 'v7', 'family_free', 'programs', '25 programs per member', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_free'
UNION ALL
-- Family Gold
SELECT id, 'v7', 'family_gold', 'members', 'Up to 10 family members', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_gold'
UNION ALL
SELECT id, 'v7', 'family_gold', 'programs', '500+ programs per member', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_gold'
UNION ALL
SELECT id, 'v7', 'family_gold', 'dashboard', 'Family rewards dashboard', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_gold'
UNION ALL
SELECT id, 'v7', 'family_gold', 'sharing', 'Share rewards between members', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_gold'
UNION ALL
-- Family Lifetime
SELECT id, 'v7', 'family_lifetime', 'members', 'Unlimited family members', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_lifetime'
UNION ALL
SELECT id, 'v7', 'family_lifetime', 'inheritance', 'Pass to next generation', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_lifetime'
UNION ALL
SELECT id, 'v7', 'family_lifetime', 'everything', 'All Gold features forever', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'family_lifetime';

-- Features for Business Plans
INSERT INTO bg_product_features (product_id, version, plan, name, value, status)
-- Business Starter
SELECT id, 'v7', 'business_starter', 'employees', 'Up to 25 employees', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_starter'
UNION ALL
SELECT id, 'v7', 'business_starter', 'management', 'Employee birthday dashboard', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_starter'
UNION ALL
SELECT id, 'v7', 'business_starter', 'reporting', 'Monthly reports', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_starter'
UNION ALL
-- Business Pro
SELECT id, 'v7', 'business_pro', 'employees', 'Up to 100 employees', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_pro'
UNION ALL
SELECT id, 'v7', 'business_pro', 'hr', 'HR system integration', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_pro'
UNION ALL
SELECT id, 'v7', 'business_pro', 'analytics', 'Advanced analytics', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_pro'
UNION ALL
-- Business Enterprise
SELECT id, 'v7', 'business_enterprise', 'employees', 'Unlimited employees', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_enterprise'
UNION ALL
SELECT id, 'v7', 'business_enterprise', 'api', 'Full API access', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_enterprise'
UNION ALL
SELECT id, 'v7', 'business_enterprise', 'custom', 'Custom integrations', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_enterprise'
UNION ALL
SELECT id, 'v7', 'business_enterprise', 'dedicated', 'Dedicated account manager', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'business_enterprise';

-- Features for Gift Certificates
INSERT INTO bg_product_features (product_id, version, plan, name, value, status)
-- Gift Gold
SELECT id, 'v7', 'gift_gold', 'duration', '1 year Gold membership', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gift_gold'
UNION ALL
SELECT id, 'v7', 'gift_gold', 'delivery', 'Instant email delivery', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gift_gold'
UNION ALL
SELECT id, 'v7', 'gift_gold', 'custom', 'Custom message & design', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gift_gold'
UNION ALL
-- Gift Lifetime
SELECT id, 'v7', 'gift_lifetime', 'duration', 'Lifetime membership', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gift_lifetime'
UNION ALL
SELECT id, 'v7', 'gift_lifetime', 'premium', 'Premium gift packaging', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gift_lifetime'
UNION ALL
SELECT id, 'v7', 'gift_lifetime', 'transferable', 'Transferable ownership', 'active' FROM bg_products WHERE version = 'v7' AND account_plan = 'gift_lifetime';

-- Verify the data was inserted correctly
-- SELECT p.*, COUNT(f.id) as feature_count 
-- FROM bg_products p 
-- LEFT JOIN bg_product_features f ON p.id = f.product_id 
-- WHERE p.version = 'v7' 
-- GROUP BY p.id;