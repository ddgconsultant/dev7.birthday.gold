<?php
/**
 * Save selected business location for tour
 */

// Security check
if (!isset($database) || !isset($current_user_data)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid request']));
}

$companyId = $_POST['company_id'] ?? 0;
$locationId = $_POST['location_id'] ?? 0;
$tourDate = $_POST['tour_date'] ?? date('Y-m-d');

if (!$companyId || !$locationId) {
    exit(json_encode(['success' => false, 'message' => 'Company ID and Location ID required']));
}

try {
    // Update the tour record with the new location
    $sql = "UPDATE bg_user_tours 
            SET location_id = :location_id,
                modify_dt = NOW() 
            WHERE user_id = :user_id 
            AND company_id = :company_id 
            AND calendar_dt = :tour_date";
    
    $stmt = $database->prepare($sql);
    $result = $stmt->execute([
        ':location_id' => $locationId,
        ':user_id' => $current_user_data['user_id'],
        ':company_id' => $companyId,
        ':tour_date' => $tourDate
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Location updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No tour found to update']);
    }
    
} catch (Exception $e) {
    error_log("Error saving location: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to save location']);
}