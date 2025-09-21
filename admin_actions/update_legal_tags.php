<?php
// Update existing legal policies with proper JSON tags for review periods
include(dirname(__FILE__) . '/../core/site-controller.php');

echo "Updating tags for existing legal policies...\n\n";

// Fetch all existing legal/policy records
$sql = "SELECT id, name, display_name, category, type, tags, modify_dt 
        FROM bg_content 
        WHERE category IN ('Policies', 'legal', 'Legal') 
        AND status = 'active'
        ORDER BY id";

try {
    $policies = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($policies)) {
        echo "No active legal policies found in bg_content table.\n";
        exit;
    }
    
    echo "Found " . count($policies) . " policies to update.\n\n";
    
    $updated_count = 0;
    $skipped_count = 0;
    
    foreach ($policies as $policy) {
        echo "Processing: {$policy['display_name']} (ID: {$policy['id']})\n";
        
        // Parse existing tags if any
        $existing_tags = [];
        if (!empty($policy['tags'])) {
            $decoded = json_decode($policy['tags'], true);
            if (is_array($decoded)) {
                $existing_tags = $decoded;
                echo "  - Has existing tags: " . count($existing_tags) . " keys\n";
            }
        }
        
        // Check if review_period already exists
        if (isset($existing_tags['review_period'])) {
            echo "  - Already has review_period: {$existing_tags['review_period']} days\n";
            echo "  - Skipping (no update needed)\n\n";
            $skipped_count++;
            continue;
        }
        
        // Determine appropriate review period based on policy type/name
        $review_period = 180; // Default 6 months
        
        // Set specific review periods based on policy type
        $name_lower = strtolower($policy['name'] ?? '');
        $display_lower = strtolower($policy['display_name'] ?? '');
        
        if (strpos($name_lower, 'privacy') !== false || strpos($display_lower, 'privacy') !== false) {
            $review_period = 90;  // Privacy policies - quarterly review
        } elseif (strpos($name_lower, 'terms') !== false || strpos($display_lower, 'terms') !== false) {
            $review_period = 180; // Terms of service - 6 months
        } elseif (strpos($name_lower, 'gdpr') !== false || strpos($display_lower, 'gdpr') !== false) {
            $review_period = 180; // GDPR - 6 months (compliance requirement)
        } elseif (strpos($name_lower, 'ccpa') !== false || strpos($display_lower, 'ccpa') !== false) {
            $review_period = 180; // CCPA - 6 months
        } elseif (strpos($name_lower, 'cookie') !== false || strpos($display_lower, 'cookie') !== false) {
            $review_period = 365; // Cookie policy - annual
        } elseif (strpos($name_lower, 'dmca') !== false || strpos($display_lower, 'dmca') !== false) {
            $review_period = 365; // DMCA - annual
        } elseif (strpos($name_lower, 'refund') !== false || strpos($display_lower, 'refund') !== false) {
            $review_period = 90;  // Refund policy - quarterly
        } elseif (strpos($name_lower, 'acceptable') !== false || strpos($display_lower, 'acceptable') !== false) {
            $review_period = 180; // Acceptable use - 6 months
        } elseif (strpos($name_lower, 'data') !== false || strpos($display_lower, 'data') !== false) {
            $review_period = 90;  // Data policies - quarterly
        }
        
        // Build updated tags array
        $updated_tags = array_merge($existing_tags, [
            'review_period' => $review_period,
            'last_legal_review' => date('Y-m-d', strtotime($policy['modify_dt'])),
            'owner' => $existing_tags['owner'] ?? 'Legal Team',
            'auto_review_enabled' => true
        ]);
        
        // Determine priority if not set
        if (!isset($updated_tags['priority'])) {
            if ($review_period <= 90) {
                $updated_tags['priority'] = 'high';
            } elseif ($review_period <= 180) {
                $updated_tags['priority'] = 'medium';
            } else {
                $updated_tags['priority'] = 'low';
            }
        }
        
        // JSON encode the tags
        $tags_json = json_encode($updated_tags);
        
        // Update the database (don't update modify_dt - we want to preserve the last actual content change)
        $update_sql = "UPDATE bg_content 
                      SET tags = :tags
                      WHERE id = :id";
        
        try {
            $database->query($update_sql, [
                'tags' => $tags_json,
                'id' => $policy['id']
            ]);
            
            echo "  ✓ Updated with review_period: {$review_period} days\n";
            echo "  - Priority: {$updated_tags['priority']}\n";
            echo "  - Owner: {$updated_tags['owner']}\n";
            $updated_count++;
            
        } catch (Exception $e) {
            echo "  ✗ Error updating: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "===========================================\n";
    echo "Update complete!\n";
    echo "Updated: $updated_count policies\n";
    echo "Skipped: $skipped_count policies (already had review_period)\n\n";
    
    // Show summary of review schedules
    echo "Review Schedule Summary:\n";
    echo "-------------------------------------------\n";
    
    $summary_sql = "SELECT 
        tags,
        COUNT(*) as count
    FROM bg_content 
    WHERE category IN ('Policies', 'legal', 'Legal') 
    AND status = 'active'
    GROUP BY JSON_EXTRACT(tags, '$.review_period')";
    
    // Since JSON_EXTRACT might not work, do it in PHP
    $all_policies = $database->query("SELECT tags FROM bg_content WHERE category IN ('Policies', 'legal', 'Legal') AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
    
    $review_periods = [];
    foreach ($all_policies as $p) {
        $tags = json_decode($p['tags'], true);
        if (isset($tags['review_period'])) {
            $period = $tags['review_period'];
            if (!isset($review_periods[$period])) {
                $review_periods[$period] = 0;
            }
            $review_periods[$period]++;
        }
    }
    
    ksort($review_periods);
    
    foreach ($review_periods as $days => $count) {
        $frequency = '';
        if ($days <= 30) {
            $frequency = 'Monthly';
        } elseif ($days <= 90) {
            $frequency = 'Quarterly';
        } elseif ($days <= 180) {
            $frequency = 'Semi-Annually';
        } elseif ($days <= 365) {
            $frequency = 'Annually';
        } else {
            $frequency = 'Custom';
        }
        
        echo "• {$days} days ({$frequency}): {$count} policies\n";
    }
    
    echo "\n-------------------------------------------\n";
    echo "You can now test the scheduler:\n";
    echo "https://dev7.birthday.gold/admin_actions/scheduler--legalhubreview_reminder.php?debug=1\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>