-- Upgrade user_id=20 to business account for testing business claim feature

-- Update the user's account type to 'business'
UPDATE bg_users 
SET account_type = 'business',
    modify_dt = NOW()
WHERE user_id = 20;

-- Verify the update
SELECT user_id, email, firstname, lastname, account_type 
FROM bg_users 
WHERE user_id = 20;