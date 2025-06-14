-- Sample data for bg_products and bg_product_features tables
-- This shows how to structure the dynamic plan data

-- IMPORTANT: This script ADDS data, it does NOT delete existing data
-- Check for existing records before running to avoid duplicates

-- First, let's check what products already exist
-- SELECT * FROM bg_products WHERE version = 'v3';
-- SELECT * FROM bg_product_features WHERE version = 'v3';

-- Insert Products for Individual Users
-- Use INSERT IGNORE or check for existence first
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) VALUES
-- Free Plan
('v3', 'user', 'free', 'Free Plan', 'Start your birthday journey with basic features', 0, 'active', 'yes', 'optional', '/register', 'individual', 'active'),
-- Gold Plan (Most Popular)
('v3', 'user', 'gold', 'Gold Membership', 'Unlock all birthday rewards and VIP experiences', 2997, 'active', 'yes', 'required', '/register', 'individual', 'active'),
-- Lifetime Plan
('v3', 'user', 'lifetime', 'Lifetime Access', 'Never pay again - lifetime birthday rewards', 9997, 'active', 'yes', 'required', '/register', 'individual', 'active');

-- Insert Products for Family/Parental Accounts
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) VALUES
-- Family Free Plan
('v3', 'parental', 'family_free', 'Family Starter', 'Track birthdays for your whole family', 0, 'active', 'yes', 'optional', '/register-parental', 'family', 'active'),
-- Family Gold Plan
('v3', 'parental', 'family_gold', 'Family Gold', 'Birthday rewards for the whole family', 4997, 'active', 'yes', 'required', '/register-parental', 'family', 'active'),
-- Family Lifetime
('v3', 'parental', 'family_lifetime', 'Family Forever', 'Lifetime access for your entire family', 14997, 'active', 'yes', 'required', '/register-parental', 'family', 'active');

-- Insert Products for Business Accounts
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) VALUES
-- Business Basic
('v3', 'business', 'business_basic', 'Business Basic', 'Perfect for small teams', 9997, 'active', 'no', 'required', '/register-business', 'business', 'active'),
-- Business Pro
('v3', 'business', 'business_pro', 'Business Pro', 'For growing companies', 24997, 'active', 'no', 'required', '/register-business', 'business', 'active');

-- Insert Products for Gift Certificates
INSERT INTO bg_products (version, account_type, account_plan, account_name, description, price, status, allow_promo, account_verification, redirect_url, display_grouping, display_grouping_status) VALUES
-- Gift Certificate
('v3', 'giftcertificate', 'gift_gold', 'Gift of Gold', 'Give the gift of birthday rewards', 2997, 'active', 'no', 'optional', '/register-giftcertificate', 'gift', 'active');

-- Now insert features for each product
-- Note: You'll need to get the actual product IDs after insertion
-- This example assumes IDs, but you should use a subquery or get the actual IDs

-- Features for Free Plan (assuming product_id = 1)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(1, 'v3', 'free', 'tracking', 'Basic birthday tracking', 'active'),
(1, 'v3', 'free', 'offers', 'Access to 50+ birthday offers', 'active'),
(1, 'v3', 'free', 'reminders', 'Email reminders only', 'active'),
(1, 'v3', 'free', 'support', 'Community support', 'active');

-- Features for Gold Plan (assuming product_id = 2)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(2, 'v3', 'gold', 'tracking', 'Advanced birthday tracking & calendar', 'active'),
(2, 'v3', 'gold', 'offers', 'Access to 500+ birthday offers', 'active'),
(2, 'v3', 'gold', 'automation', 'Automatic enrollment in programs', 'active'),
(2, 'v3', 'gold', 'reminders', 'Email & SMS reminders', 'active'),
(2, 'v3', 'gold', 'vip', 'VIP experiences and upgrades', 'active'),
(2, 'v3', 'gold', 'support', 'Priority 24/7 support', 'active'),
(2, 'v3', 'gold', 'deals', 'Year-round exclusive deals', 'active');

-- Features for Lifetime Plan (assuming product_id = 3)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(3, 'v3', 'lifetime', 'everything', 'Everything in Gold', 'active'),
(3, 'v3', 'lifetime', 'updates', 'Lifetime updates & new features', 'active'),
(3, 'v3', 'lifetime', 'referral', 'Earn rewards for referrals', 'active'),
(3, 'v3', 'lifetime', 'concierge', 'Personal birthday concierge', 'active'),
(3, 'v3', 'lifetime', 'early', 'Early access to new partners', 'active');

-- Features for Family Plans (assuming product_ids 4, 5, 6)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
-- Family Free
(4, 'v3', 'family_free', 'members', 'Track up to 4 family members', 'active'),
(4, 'v3', 'family_free', 'calendar', 'Shared family calendar', 'active'),
(4, 'v3', 'family_free', 'reminders', 'Basic reminders for all', 'active'),
-- Family Gold  
(5, 'v3', 'family_gold', 'members', 'Track up to 10 family members', 'active'),
(5, 'v3', 'family_gold', 'automation', 'Auto-enrollment for everyone', 'active'),
(5, 'v3', 'family_gold', 'dashboard', 'Family rewards dashboard', 'active'),
(5, 'v3', 'family_gold', 'sharing', 'Share rewards between members', 'active'),
-- Family Lifetime
(6, 'v3', 'family_lifetime', 'members', 'Unlimited family members', 'active'),
(6, 'v3', 'family_lifetime', 'inheritance', 'Pass down to next generation', 'active'),
(6, 'v3', 'family_lifetime', 'everything', 'All Gold features forever', 'active');

-- Features for Business Plans (assuming product_ids 7, 8)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
-- Business Basic
(7, 'v3', 'business_basic', 'employees', 'Up to 25 employees', 'active'),
(7, 'v3', 'business_basic', 'management', 'Employee birthday management', 'active'),
(7, 'v3', 'business_basic', 'reporting', 'Basic analytics & reporting', 'active'),
-- Business Pro
(8, 'v3', 'business_pro', 'employees', 'Unlimited employees', 'active'),
(8, 'v3', 'business_pro', 'api', 'API access for integration', 'active'),
(8, 'v3', 'business_pro', 'branding', 'Custom branding options', 'active'),
(8, 'v3', 'business_pro', 'analytics', 'Advanced analytics suite', 'active');

-- Features for Gift Certificate (assuming product_id 9)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(9, 'v3', 'gift_gold', 'duration', '1 year Gold membership', 'active'),
(9, 'v3', 'gift_gold', 'delivery', 'Instant digital delivery', 'active'),
(9, 'v3', 'gift_gold', 'customization', 'Personalized message', 'active'),
(9, 'v3', 'gift_gold', 'transferable', 'Transferable to anyone', 'active');

-- Query to properly link features with product IDs
-- This should be run after inserting products to get correct IDs:
/*
UPDATE bg_product_features f
JOIN bg_products p ON p.account_plan = f.plan AND p.version = f.version
SET f.product_id = p.id
WHERE f.version = 'v3';
*/