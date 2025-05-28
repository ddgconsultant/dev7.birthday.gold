<?php
$addClasses[] = 'fileuploader';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$modalData = [];
$componentConfig = [];
$output_end = '';
$errormessage = '';
$transferpagedata = [];
$newfileuploadedid = false;


// Verify request and permissions
if (empty($account->isactive())) {
    $transferpage['message'] = '<div class="alert alert-danger">You must be logged in to perform this action.</div>';
    $transferpage['url'] = '/login';
    $system->endpostpage($transferpage);
    exit;
}

// Get and validate user ID
$encoded_user_id = $_GET['u'] ?? '';
if (empty($encoded_user_id)) {
    $transferpage['message'] = '<div class="alert alert-danger">Invalid request: missing user ID.</div>';
    $transferpage['url'] = '/admin/user-list';
    $system->endpostpage($transferpage);
    exit;
}

$user_id = $qik->decodeId($encoded_user_id);

// Verify permissions (only admins or the user themselves)
if (!$account->isadmin() && $user_id != $account->getUserId()) {
    $transferpage['message'] = '<div class="alert alert-danger">You do not have permission to modify this user\'s avatar.</div>';
    $transferpage['url'] = '/admin/user-list';
    $system->endpostpage($transferpage);
    exit;
}

// Generate new avatar URL
$new_avatar = $display->generateAvatarUrl($fileuploader);
if (is_array($new_avatar)) {
    $new_avatar = '/public/avatars/problemavatar.png';
}

// Update avatar in user attributes
$sql = "UPDATE bg_user_attributes 
        SET description = :avatar_url, modify_dt = NOW() 
        WHERE user_id = :user_id 
        AND type = 'profile_image' 
        AND name = 'avatar' 
        AND category = 'primary'";

$params = [
    ':avatar_url' => $new_avatar,
    ':user_id' => $user_id
];

try {
    $stmt = $database->query($sql, $params);
    
    // Check if the update was successful
    if ($stmt->rowCount() > 0) {
        session_tracking('Avatar regenerated', ['user_id' => $user_id, 'new_avatar' => $new_avatar]);
        $transferpage['message'] = '<div class="alert alert-success">Avatar has been successfully regenerated.</div>';
    } else {
        // If no rows were updated, try to insert a new record
        $sql = "INSERT INTO bg_user_attributes 
                (user_id, type, name, description, status, rank, category, create_dt, modify_dt) 
                VALUES 
                (:user_id, 'profile_image', 'avatar', :avatar_url, 'active', 100, 'primary', NOW(), NOW())";
        
        $stmt = $database->query($sql, $params);
        
        if ($stmt->rowCount() > 0) {
            session_tracking('Avatar created', ['user_id' => $user_id, 'new_avatar' => $new_avatar]);
            $transferpage['message'] = '<div class="alert alert-success">Avatar has been successfully created.</div>';
        } else {
            throw new Exception("Failed to update or create avatar record.");
        }
    }
    
    // Determine the return URL (admin user view or profile page)
    if ($account->isadmin() && $user_id != $account->getUserId()) {
        $transferpage['url'] = '/admin/user-details?u=' . $encoded_user_id;
    } else {
        $transferpage['url'] = '/profile';
    }
    
} catch (Exception $e) {
    session_tracking('Avatar regeneration error', ['user_id' => $user_id, 'error' => $e->getMessage()]);
    $transferpage['message'] = '<div class="alert alert-danger">Error regenerating avatar: ' . $e->getMessage() . '</div>';
    $transferpage['url'] = '/admin/user-list';
}

$system->endpostpage($transferpage);
?>