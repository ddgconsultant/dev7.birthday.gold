<?php
/**
 * AJAX endpoint to fetch feature details from bg_content
 * Uses intelligent fallback with coalescing:
 * 1. {plan}_{feature}-{version}-{value} (most specific)
 * 2. {plan}_{feature}-{version}
 * 3. {plan}_{feature}
 * 4. {feature} (generic fallback)
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set JSON header
header('Content-Type: application/json');

// Get POST parameters
$feature = $_POST['feature'] ?? '';
$value = $_POST['value'] ?? '';
$plan = $_POST['plan'] ?? '';
$version = $_POST['version'] ?? 'v7';
$label = $_POST['label'] ?? '';

// Validate input
if (empty($feature)) {
    echo json_encode([
        'success' => false,
        'message' => 'Feature identifier is required'
    ]);
    exit;
}

// Build potential content keys to search (in order of specificity)
$content_keys = [];

// Most specific: plan_feature-version-value
if (!empty($plan) && !empty($version) && !empty($value)) {
    // Normalize value for key (replace spaces, special chars)
    $normalized_value = strtolower(str_replace([' ', '/', ','], '_', $value));
    $content_keys[] = "{$plan}_{$feature}-{$version}-{$normalized_value}";
}

// Plan and version specific
if (!empty($plan) && !empty($version)) {
    $content_keys[] = "{$plan}_{$feature}-{$version}";
}

// Plan specific
if (!empty($plan)) {
    $content_keys[] = "{$plan}_{$feature}";
}

// Generic feature (fallback)
$content_keys[] = $feature;

// Query bg_content with COALESCE fallback
$placeholders = array_fill(0, count($content_keys), '?');
$sql = "SELECT 
            name,
            display_name,
            label,
            description,
            content,
            tags,
            version,
            category,
            type
        FROM bg_content
        WHERE name IN (" . implode(',', $placeholders) . ")
        AND status = 'active'
        ORDER BY FIELD(name, " . implode(',', $placeholders) . ")
        LIMIT 1";

// Prepare parameters (content_keys twice for IN and FIELD)
$params = array_merge($content_keys, $content_keys);

try {
    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Parse content for structured data
        $response = [
            'success' => true,
            'display_name' => $result['display_name'] ?? $label,
            'description' => $result['description'] ?? '',
            'content' => $result['content'] ?? '',
            'matched_key' => $result['name'],
            'category' => $result['category'] ?? '',
            'type' => $result['type'] ?? ''
        ];
        
        // Parse tags for additional structured data
        if (!empty($result['tags'])) {
            $tags = json_decode($result['tags'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Extract structured data from tags
                if (isset($tags['how_it_works'])) {
                    $response['how_it_works'] = $tags['how_it_works'];
                }
                if (isset($tags['benefits']) && is_array($tags['benefits'])) {
                    $response['benefits'] = $tags['benefits'];
                }
                if (isset($tags['requirements'])) {
                    $response['requirements'] = $tags['requirements'];
                }
                if (isset($tags['examples'])) {
                    $response['examples'] = $tags['examples'];
                }
            }
        }
        
        // If no content found in database, generate default content
        if (empty($response['description']) && empty($response['content'])) {
            $response['description'] = generateDefaultDescription($feature, $value, $label);
        }
        
        echo json_encode($response);
    } else {
        // No content found, return generated description
        echo json_encode([
            'success' => true,
            'display_name' => $label,
            'description' => generateDefaultDescription($feature, $value, $label),
            'content' => '',
            'generated' => true,
            'searched_keys' => $content_keys // For debugging
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
}

/**
 * Generate a default description based on feature name and value
 */
function generateDefaultDescription($feature, $value, $label) {
    $description = '';
    
    // Clean up the feature name
    $clean_feature = str_replace(['feature_', '_value', '_'], ' ', $feature);
    $clean_feature = trim(ucwords($clean_feature));
    
    // Generate description based on value
    if ($value === 'Unlimited' || $value === '∞') {
        $description = "Enjoy unlimited {$clean_feature} with no restrictions. This feature allows you to maximize your marketing efforts without worrying about limits.";
    } elseif ($value === 'Enabled' || $value === 'Yes' || $value === 'Included') {
        $description = "{$label} is fully enabled in your plan, providing you with complete access to this capability.";
    } elseif (is_numeric($value)) {
        $description = "Your plan includes up to {$value} {$clean_feature}. This allocation is designed to meet the needs of growing businesses.";
    } elseif ($value === 'Advanced' || $value === 'Full Access' || $value === 'Complete') {
        $description = "You have advanced access to {$clean_feature}, including all premium features and capabilities.";
    } elseif ($value === 'Priority' || $value === '24/7') {
        $description = "Receive priority {$clean_feature} to ensure your business runs smoothly at all times.";
    } else {
        $description = "{$label}: {$value}. This feature is configured to provide optimal performance for your business needs.";
    }
    
    // Add category-specific information
    if (strpos($feature, 'marketing') !== false) {
        $description .= " Perfect for reaching and engaging your customer base effectively.";
    } elseif (strpos($feature, 'security') !== false) {
        $description .= " Ensures your data and customer information remain protected.";
    } elseif (strpos($feature, 'analytics') !== false) {
        $description .= " Gain valuable insights to make data-driven decisions.";
    } elseif (strpos($feature, 'automation') !== false) {
        $description .= " Save time and increase efficiency with automated processes.";
    } elseif (strpos($feature, 'support') !== false) {
        $description .= " Our team is here to help you succeed.";
    }
    
    return $description;
}
?>