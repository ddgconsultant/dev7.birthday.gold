-- Celebration Messages for Birthday Gold Plans
-- Insert plan-specific celebration messages into bg_product_features table
-- Includes product_id and version from bg_products table

-- USER_FREE Plan (Product ID: 431, Version: v7)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(431, 'v7', 'user_free', 'celebration_title', 'Welcome to Birthday Gold!', 'active'),
(431, 'v7', 'user_free', 'celebration_subtitle', 'Your free account is ready{NAME}!', 'active'),
(431, 'v7', 'user_free', 'celebration_message', 'You\'re all set with basic birthday tracking and reminders. Start building your birthday reward collection today!', 'active'),
(431, 'v7', 'user_free', 'celebration_next_step_1', 'Complete your profile with birthday and preferences', 'active'),
(431, 'v7', 'user_free', 'celebration_next_step_2', 'Browse available birthday rewards in your area', 'active'),
(431, 'v7', 'user_free', 'celebration_next_step_3', 'Set up birthday reminders and notifications', 'active'),
(431, 'v7', 'user_free', 'celebration_next_step_4', 'Consider upgrading to Gold for premium rewards', 'active'),
(431, 'v7', 'user_free', 'celebration_button_text', 'Go to Your Dashboard', 'active');

-- USER_GOLD Plan (Product ID: 441, Version: v7)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(441, 'v7', 'user_gold', 'celebration_title', 'Welcome to Birthday Gold Premium!', 'active'),
(441, 'v7', 'user_gold', 'celebration_subtitle', 'Your Gold membership is now active{NAME}!', 'active'),
(441, 'v7', 'user_gold', 'celebration_message', 'You now have full access to all birthday rewards and VIP experiences from hundreds of premium businesses!', 'active'),
(441, 'v7', 'user_gold', 'celebration_next_step_1', 'Explore your expanded premium reward catalog', 'active'),
(441, 'v7', 'user_gold', 'celebration_next_step_2', 'Set up automated enrollment for maximum convenience', 'active'),
(441, 'v7', 'user_gold', 'celebration_next_step_3', 'Access exclusive VIP birthday experiences', 'active'),
(441, 'v7', 'user_gold', 'celebration_next_step_4', 'Customize your premium notification preferences', 'active'),
(441, 'v7', 'user_gold', 'celebration_button_text', 'Access Your Premium Dashboard', 'active');

-- USER_LIFE Plan (Product ID: 451, Version: v7) - Currently inactive but included for future use
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(451, 'v7', 'user_life', 'celebration_title', 'Welcome to Birthday Gold Lifetime!', 'active'),
(451, 'v7', 'user_life', 'celebration_subtitle', 'Your lifetime access is confirmed{NAME}!', 'active'),
(451, 'v7', 'user_life', 'celebration_message', 'Congratulations! You now have lifetime access to all birthday rewards. Never worry about renewals again!', 'active'),
(451, 'v7', 'user_life', 'celebration_next_step_1', 'Explore your unlimited lifetime reward access', 'active'),
(451, 'v7', 'user_life', 'celebration_next_step_2', 'Set up permanent automated enrollments', 'active'),
(451, 'v7', 'user_life', 'celebration_next_step_3', 'Access all current and future premium features', 'active'),
(451, 'v7', 'user_life', 'celebration_next_step_4', 'Share the lifetime benefit with family referrals', 'active'),
(451, 'v7', 'user_life', 'celebration_button_text', 'Access Your Lifetime Dashboard', 'active');

-- FAMILY_FREE Plan (Product ID: 461, Version: v7)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(461, 'v7', 'family_free', 'celebration_title', 'Welcome to Birthday Gold Family!', 'active'),
(461, 'v7', 'family_free', 'celebration_subtitle', 'Your family account is ready{NAME}!', 'active'),
(461, 'v7', 'family_free', 'celebration_message', 'You can now track birthdays for up to 3 family members and start earning birthday rewards for the whole family!', 'active'),
(461, 'v7', 'family_free', 'celebration_next_step_1', 'Add up to 3 family member profiles', 'active'),
(461, 'v7', 'family_free', 'celebration_next_step_2', 'Set up birthday tracking for each family member', 'active'),
(461, 'v7', 'family_free', 'celebration_next_step_3', 'Browse family-friendly birthday rewards', 'active'),
(461, 'v7', 'family_free', 'celebration_next_step_4', 'Consider upgrading to Family Gold for more members', 'active'),
(461, 'v7', 'family_free', 'celebration_button_text', 'Manage Your Family Account', 'active');

-- FAMILY_GOLD Plan (Product ID: 471, Version: v7)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(471, 'v7', 'family_gold', 'celebration_title', 'Welcome to Birthday Gold Family Premium!', 'active'),
(471, 'v7', 'family_gold', 'celebration_subtitle', 'Your Family Gold membership is active{NAME}!', 'active'),
(471, 'v7', 'family_gold', 'celebration_message', 'Your family now has access to premium birthday rewards for up to 6 family members. Make every birthday special!', 'active'),
(471, 'v7', 'family_gold', 'celebration_next_step_1', 'Add up to 6 family member profiles', 'active'),
(471, 'v7', 'family_gold', 'celebration_next_step_2', 'Set up premium rewards for each family member', 'active'),
(471, 'v7', 'family_gold', 'celebration_next_step_3', 'Access exclusive family birthday experiences', 'active'),
(471, 'v7', 'family_gold', 'celebration_next_step_4', 'Customize notifications for all family birthdays', 'active'),
(471, 'v7', 'family_gold', 'celebration_button_text', 'Access Your Family Premium Dashboard', 'active');

-- GIFT_GOLD Plan (Product ID: 491, Version: v7)
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(491, 'v7', 'gift_gold', 'celebration_title', 'Birthday Gold Gift Certificate Activated!', 'active'),
(491, 'v7', 'gift_gold', 'celebration_subtitle', 'Your gift certificate is ready{NAME}!', 'active'),
(491, 'v7', 'gift_gold', 'celebration_message', 'You\'ve given the gift of birthday rewards! The recipient will have access to premium birthday experiences and rewards.', 'active'),
(491, 'v7', 'gift_gold', 'celebration_next_step_1', 'Share the gift certificate with the recipient', 'active'),
(491, 'v7', 'gift_gold', 'celebration_next_step_2', 'Help them set up their Birthday Gold account', 'active'),
(491, 'v7', 'gift_gold', 'celebration_next_step_3', 'They can start earning rewards immediately', 'active'),
(491, 'v7', 'gift_gold', 'celebration_next_step_4', 'Check your email for gift certificate details', 'active'),
(491, 'v7', 'gift_gold', 'celebration_button_text', 'View Gift Certificate Details', 'active');

-- BUSINESS_STARTER Plan (Product ID: 481, Version: v7) - Currently inactive but included
INSERT INTO bg_product_features (product_id, version, plan, name, value, status) VALUES
(481, 'v7', 'business_starter', 'celebration_title', 'Welcome to Birthday Gold Business!', 'active'),
(481, 'v7', 'business_starter', 'celebration_subtitle', 'Your business account is activated{NAME}!', 'active'),
(481, 'v7', 'business_starter', 'celebration_message', 'Your team can now manage employee birthdays for up to 10 team members. Make workplace celebrations special!', 'active'),
(481, 'v7', 'business_starter', 'celebration_next_step_1', 'Add up to 10 employee profiles', 'active'),
(481, 'v7', 'business_starter', 'celebration_next_step_2', 'Set up automated birthday notifications', 'active'),
(481, 'v7', 'business_starter', 'celebration_next_step_3', 'Access business-friendly reward options', 'active'),
(481, 'v7', 'business_starter', 'celebration_next_step_4', 'Configure team celebration preferences', 'active'),
(481, 'v7', 'business_starter', 'celebration_button_text', 'Access Your Business Dashboard', 'active');

-- DEFAULT/FALLBACK Messages (for any unspecified plans) - No product_id needed for fallback
INSERT INTO bg_product_features (plan, name, value, status) VALUES
('default', 'celebration_title', 'Welcome to Birthday Gold!', 'active'),
('default', 'celebration_subtitle', 'Your account is ready{NAME}!', 'active'),
('default', 'celebration_message', 'You\'re all set to start receiving amazing birthday rewards from hundreds of businesses. We\'ll automatically enroll you in birthday programs as your special day approaches.', 'active'),
('default', 'celebration_next_step_1', 'Complete your profile with preferences', 'active'),
('default', 'celebration_next_step_2', 'Browse and select birthday reward programs', 'active'),
('default', 'celebration_next_step_3', 'Verify your account to unlock all features', 'active'),
('default', 'celebration_next_step_4', 'Check your email for tips and special offers', 'active'),
('default', 'celebration_button_text', 'Go to Your Dashboard', 'active');