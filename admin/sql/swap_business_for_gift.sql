-- Disable business products for signup display
UPDATE bg_products 
SET display_grouping_status = 'inactive' 
WHERE account_type = 'business' 
AND version = 'v7';

-- Enable gift certificate products for signup display
UPDATE bg_products 
SET display_grouping_status = 'active' 
WHERE account_type = 'giftcertificate' 
AND version = 'v7';

-- Optional: Update the display order in bg_account_types if that table exists
-- This will make gift certificates appear where business was
UPDATE bg_account_types 
SET display_order = 3 
WHERE account_type = 'giftcertificate' 
AND version = 'v7';

UPDATE bg_account_types 
SET display_order = 99, status = 'inactive'
WHERE account_type = 'business' 
AND version = 'v7';

-- Verify the changes
SELECT 
    p.account_type,
    p.account_plan,
    p.account_name,
    p.status,
    p.display_grouping_status,
    at.display_order,
    at.status as type_status
FROM bg_products p
LEFT JOIN bg_account_types at ON p.account_type = at.account_type AND at.version = p.version
WHERE p.version = 'v7'
AND p.account_type IN ('business', 'giftcertificate')
ORDER BY at.display_order, p.account_type, p.price;