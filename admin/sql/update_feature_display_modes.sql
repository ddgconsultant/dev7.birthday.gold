-- Example updates for feature display modes
-- This demonstrates how to use the new display_mode column

-- Hide internal/system features that shouldn't be shown to users
UPDATE bg_product_features 
SET display_mode = 'hide' 
WHERE name IN (
    'allow_promo',
    'account_verification', 
    'redirect_url',
    'display_grouping',
    'display_grouping_status',
    'internal_notes',
    'system_flag'
)
AND display_mode IS NULL;

-- Set admin-only features that should be visible in admin but not signup
UPDATE bg_product_features 
SET display_mode = 'admin_only' 
WHERE name IN (
    'setup_fee',
    'activation_fee',
    'processing_note',
    'admin_override'
)
AND display_mode IS NULL;

-- Ensure all user-facing features are explicitly set to 'show'
UPDATE bg_product_features 
SET display_mode = 'show' 
WHERE display_mode IS NULL
AND name NOT IN (
    'allow_promo',
    'account_verification', 
    'redirect_url',
    'display_grouping',
    'display_grouping_status',
    'internal_notes',
    'system_flag',
    'setup_fee',
    'activation_fee',
    'processing_note',
    'admin_override'
);

-- View the results
SELECT 
    p.account_name,
    pf.name,
    pf.value,
    pf.display_mode,
    pf.status
FROM bg_product_features pf
JOIN bg_products p ON pf.product_id = p.id
WHERE p.version = 'v7'
ORDER BY p.account_type, p.price, pf.display_mode, pf.name;