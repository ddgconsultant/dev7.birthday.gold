<?php

include_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

/**
 * Feature Setup Router & Display Driver
 *
 * MODES:
 * 1. 'setup' (default): Checks for unconfigured features and redirects to setup
 *    - Called by myaccount/index.php
 *    - Redirects to feature component if setup needed
 *    - Redirects to home if no valid feature exists
 *
 * 2. 'settings': Renders feature list for settings page
 *    - Called by myaccount/settings.php with $featuremode='settings'
 *    - Includes each feature component in settings mode
 *    - Components echo their display HTML
 *
 * This ensures that:
 * - New paid/gold members set up their features after checkout
 * - Upgraded members set up new features they gained
 * - Settings page dynamically displays features from components
 * - Each feature controls its own display logic
 * - Session caching reduces database queries
 * - Proper redirection when no features need setup
 */

// Don't include site-controller - already loaded by parent page
// Assumes $current_user_data, $app, and $database are available

$featuremode = $featuremode ?? 'setup';

// Check session for cached feature details first (with TTL of 5 minutes)
$session_key = 'user_features_' . $current_user_data['user_id'];
$session_ttl_key = $session_key . '_ttl';
$cache_ttl_seconds = 300; // 5 minutes

$product_features = null;
$use_cached = false;

// Check if we have valid cached data in session
if (isset($_SESSION[$session_key]) && isset($_SESSION[$session_ttl_key])) {
    if (time() < $_SESSION[$session_ttl_key]) {
        $product_features = $_SESSION[$session_key];
        $use_cached = true;
        error_log('[SETUP-FEATURES] Using cached features from session for user ' . $current_user_data['user_id']);
    } else {
        // Cache expired, clean it up
        unset($_SESSION[$session_key]);
        unset($_SESSION[$session_ttl_key]);
    }
}

// If no cached data, get from database
if (!$use_cached) {
    // Get all product features for user's current plan from database
    $sql = "SELECT name, value, status
            FROM bg_product_features
            WHERE product_id = :product_id
            AND name LIKE 'feature_%'
            AND display_mode = 'show'
            AND status = 'active'
            ORDER BY name ASC";

    $stmt = $database->prepare($sql);
    $stmt->execute(['product_id' => $current_user_data['account_product_id']]);
    $product_features = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cache in session with TTL
    $_SESSION[$session_key] = $product_features;
    $_SESSION[$session_ttl_key] = time() + $cache_ttl_seconds;
    error_log('[SETUP-FEATURES] Cached features in session for user ' . $current_user_data['user_id']);
}

// If no features are defined for this product, continue
if (empty($product_features)) {
    return;
}

#-------------------------------------------------------------------------------
# MODE: SETTINGS - Display features for settings page
#-------------------------------------------------------------------------------
if ($featuremode === 'settings') {
    // Loop through each feature and include its component in settings mode
    foreach ($product_features as $feature) {
        $feature_name = $feature['name'];
        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/myaccount/components/' . $feature_name . '.php';

        if (file_exists($component_file)) {
            // Include the component - it will echo its settings display HTML
            include($component_file);
        } else {
            error_log('[SETUP-FEATURES] Missing component file for: ' . $feature_name);
        }
    }
    return; // Done rendering features
}

#-------------------------------------------------------------------------------
# MODE: SETUP - Check for unconfigured features and redirect
#-------------------------------------------------------------------------------
// Feature priority configuration (higher number = higher priority)
// Critical features that must be setup immediately get priority 100+
// Important features get priority 50-99
// Optional features get priority 0-49
$feature_priorities = [
    'feature_email' => 100,      // Critical - needed for all enrollments
    'feature_inbox' => 90,        // Very important - email management
    'feature_birthday_reminders' => 70,  // Important - core functionality
    'feature_business_enrollments' => 60, // Important for business features
    'feature_celebration_tour' => 40,     // Nice to have
    'feature_premium_support' => 30,      // Optional
    'feature_support' => 30,              // Optional
    'feature_advanced_analytics' => 20,   // Optional
];

// Check for existing setup progress in bg_user_attributes
$existing_progress = null;
$sql = "SELECT description FROM bg_user_attributes
        WHERE user_id = :user_id
        AND type = 'feature_setup'
        AND name = 'feature_setup_progress'
        AND status = 'active'
        LIMIT 1";
$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $current_user_data['user_id']]);
$result = $stmt->fetch();

if ($result && !empty($result['description'])) {
    $existing_progress = json_decode($result['description'], true);
    error_log('[SETUP-FEATURES] Found existing progress for user ' . $current_user_data['user_id'] . ': ' . $result['description']);
}

// Track which features need setup with their priorities
$features_needing_setup = [];

// Define which features have actual columns in bg_users
$features_with_columns = ['feature_email', 'feature_parent_id', 'feature_company_id', 'feature_giftcode'];

// Check each feature to see if it needs initialization
foreach ($product_features as $feature) {
    $feature_name = $feature['name']; // e.g., 'feature_email', 'feature_inbox', etc.

    $is_configured = false;

    // Check if feature is configured based on whether it has a column or not
    if (in_array($feature_name, $features_with_columns)) {
        // Feature has a column in bg_users, check if it's configured
        $is_configured = !empty($current_user_data[$feature_name]);
    } else {
        // Feature doesn't have a column, check bg_user_attributes for completion
        $sql = "SELECT attribute_id FROM bg_user_attributes
                WHERE user_id = :user_id
                AND type = 'feature_completion'
                AND name = :name
                AND status = 'completed'
                LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'user_id' => $current_user_data['user_id'],
            'name' => $feature_name . '_completed'
        ]);
        $is_configured = ($stmt->fetch() !== false);
    }

    // If feature is not configured, add it to the setup list
    if (!$is_configured) {
        // Get priority for this feature (default to 0 if not defined)
        $priority = isset($feature_priorities[$feature_name]) ? $feature_priorities[$feature_name] : 0;

        // Check if the component file exists
        $component_file = $_SERVER['DOCUMENT_ROOT'] . '/myaccount/components/' . $feature_name . '.php';
        if (file_exists($component_file)) {
            // Feature is available and has a component file
            $features_needing_setup[] = [
                'name' => $feature_name,
                'priority' => $priority,
                'is_critical' => ($priority >= 100)
            ];
        } else {
            error_log('[SETUP-FEATURES] Missing component file for: ' . $feature_name);
        }
    }
}

// If no features need setup, continue with normal flow
if (empty($features_needing_setup)) {
    // Clear any cached feature setup redirect in session
    if (isset($_SESSION['feature_setup_redirect'])) {
        unset($_SESSION['feature_setup_redirect']);
    }

    // Mark feature setup as complete in bg_user_attributes
    $sql = "UPDATE bg_user_attributes
            SET status = 'completed',
                description = :description,
                modify_dt = NOW()
            WHERE user_id = :user_id
            AND type = 'feature_setup'
            AND name = 'feature_setup_progress'
            AND status = 'active'";

    $completed_data = json_encode([
        'status' => 'completed',
        'completed_at' => date('Y-m-d H:i:s'),
        'all_features_configured' => true
    ]);

    $stmt = $database->prepare($sql);
    $stmt->execute([
        'user_id' => $current_user_data['user_id'],
        'description' => $completed_data
    ]);

    return;
}

// Sort features by priority (highest first)
usort($features_needing_setup, function($a, $b) {
    return $b['priority'] - $a['priority'];
});

// Get the highest priority feature that needs setup
$current_feature = $features_needing_setup[0]['name'];

// Build the component path
$component_path = '/myaccount/components/' . $current_feature . '.php';

// Store the feature setup state in session for tracking
$_SESSION['feature_setup_redirect'] = [
    'feature' => $current_feature,
    'priority' => $features_needing_setup[0]['priority'],
    'is_critical' => $features_needing_setup[0]['is_critical'],
    'total_remaining' => count($features_needing_setup),
    'timestamp' => time()
];

// Also store in bg_user_attributes for persistent tracking
$attribute_name = 'feature_setup_progress';
$attribute_data = json_encode([
    'current_feature' => $current_feature,
    'priority' => $features_needing_setup[0]['priority'],
    'is_critical' => $features_needing_setup[0]['is_critical'],
    'remaining_features' => array_map(function($f) { return $f['name']; }, $features_needing_setup),
    'total_remaining' => count($features_needing_setup),
    'last_updated' => date('Y-m-d H:i:s')
]);

// Check if attribute already exists
$sql = "SELECT attribute_id FROM bg_user_attributes
        WHERE user_id = :user_id
        AND type = 'feature_setup'
        AND name = :name
        LIMIT 1";
$stmt = $database->prepare($sql);
$stmt->execute([
    'user_id' => $current_user_data['user_id'],
    'name' => $attribute_name
]);
$existing = $stmt->fetch();

if ($existing) {
    // Update existing attribute
    $sql = "UPDATE bg_user_attributes
            SET description = :description,
                modify_dt = NOW(),
                status = 'active'
            WHERE attribute_id = :attribute_id";
    $stmt = $database->prepare($sql);
    $stmt->execute([
        'description' => $attribute_data,
        'attribute_id' => $existing['attribute_id']
    ]);
} else {
    // Insert new attribute
    $sql = "INSERT INTO bg_user_attributes
            (user_id, type, name, description, status, create_dt, modify_dt)
            VALUES (:user_id, 'feature_setup', :name, :description, 'active', NOW(), NOW())";
    $stmt = $database->prepare($sql);
    $stmt->execute([
        'user_id' => $current_user_data['user_id'],
        'name' => $attribute_name,
        'description' => $attribute_data
    ]);
}

// For non-critical features, check if user has explicitly skipped setup
if (!$features_needing_setup[0]['is_critical']) {
    // Check both session and database for skip status
    $skip_key = 'skip_feature_' . $current_feature . '_' . date('Y-m-d');
    $is_skipped = isset($_SESSION[$skip_key]);

    // Also check database for persistent skip
    if (!$is_skipped) {
        $sql = "SELECT attribute_id FROM bg_user_attributes
                WHERE user_id = :user_id
                AND type = 'feature_skip'
                AND name = :name
                AND status = 'active'
                AND DATE(create_dt) = CURDATE()
                LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'user_id' => $current_user_data['user_id'],
            'name' => $current_feature . '_skipped'
        ]);
        $is_skipped = $stmt->fetch() !== false;
    }

    if ($is_skipped) {
        // User has skipped this feature, check for next one
        if (count($features_needing_setup) > 1) {
            // There are more features to setup, try the next one
            $next_feature = $features_needing_setup[1];
            $_SESSION['feature_setup_redirect']['feature'] = $next_feature['name'];
            $_SESSION['feature_setup_redirect']['priority'] = $next_feature['priority'];
            $_SESSION['feature_setup_redirect']['is_critical'] = $next_feature['is_critical'];
            $component_path = '/myaccount/components/' . $next_feature['name'] . '.php';

            error_log('[SETUP-FEATURES] User skipped ' . $current_feature . ', redirecting to ' . $next_feature['name']);
        } else {
            // No more features to setup, continue with normal flow
            unset($_SESSION['feature_setup_redirect']);
            return;
        }
    }
}

// Log for debugging
error_log('[SETUP-FEATURES] User ' . $current_user_data['user_id'] . ' needs feature setup: ' . $current_feature . ' (priority: ' . $features_needing_setup[0]['priority'] . ')');

// Check if we're already on a feature setup page to prevent redirect loops
$current_path = $_SERVER['REQUEST_URI'];
if (strpos($current_path, '/myaccount/components/') !== false) {
    // We're already on a feature setup page, don't redirect
    return;
}

// Redirect to the feature component for setup
header('Location: ' . $component_path);
exit;
