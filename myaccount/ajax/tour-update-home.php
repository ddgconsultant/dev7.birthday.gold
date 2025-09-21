<?php
/**
 * Update home location for tours
 */

// Security check - this file should only be included
if (!isset($database) || !isset($current_user_data)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid request']));
}

$address = $_POST['address'] ?? '';
$lat = $_POST['lat'] ?? '';
$lng = $_POST['lng'] ?? '';

if (empty($address)) {
    exit(json_encode(['success' => false, 'message' => 'Address is required']));
}

try {
    // Prepare location data
    $locationData = [
        'address' => $address,
        'lat' => $lat,
        'lng' => $lng
    ];
    
    // Check if attribute exists
    $checkSql = "SELECT attribute_id FROM bg_user_attributes 
                 WHERE user_id = :user_id 
                 AND type = 'tour_settings' 
                 AND name = 'default_home_location' 
                 AND status = 'active' 
                 LIMIT 1";
    $checkStmt = $database->prepare($checkSql);
    $checkStmt->execute([':user_id' => $current_user_data['user_id']]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing
        $updateSql = "UPDATE bg_user_attributes 
                      SET description = :description,
                          string_value = :address,
                          modify_dt = NOW() 
                      WHERE attribute_id = :attribute_id";
        $updateStmt = $database->prepare($updateSql);
        $updateStmt->execute([
            ':description' => json_encode($locationData),
            ':address' => $address,
            ':attribute_id' => $existing['attribute_id']
        ]);
    } else {
        // Insert new
        $insertSql = "INSERT INTO bg_user_attributes 
                      (user_id, type, name, description, string_value, status, create_dt, modify_dt) 
                      VALUES 
                      (:user_id, 'tour_settings', 'default_home_location', :description, :address, 'active', NOW(), NOW())";
        $insertStmt = $database->prepare($insertSql);
        $insertStmt->execute([
            ':user_id' => $current_user_data['user_id'],
            ':description' => json_encode($locationData),
            ':address' => $address
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Home location updated successfully']);
    
} catch (Exception $e) {
    error_log("Error updating home location: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update home location']);
}