<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$staffid = isset($_GET['i']) ? $qik->decodeId($_GET['i']) : 0;
$is_owner = ($staffid == $current_user_data['user_id']);
$edit_mode = isset($_GET['edit']) && $is_owner; // This allows edit mode only if it's the owner


#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    if (isset($_POST['action']) && $_POST['action'] === 'update_bio' && $is_owner) {
        $bio = trim($_POST['bio']);
        if (strlen($bio) <= 5000) { // Reasonable limit for bio
            // Update or insert bio in user attributes
            $sql = "INSERT INTO bg_user_attributes 
                   (user_id, type, name, description, status, create_dt, category, grouping) 
                   VALUES (:user_id, 'profile', 'job_description', :bio, 'active', NOW(), 'staff', 'team')
                   ON DUPLICATE KEY UPDATE 
                   description = :bio, 
                   modify_dt = NOW()";
            
            $stmt = $database->prepare($sql);
            $stmt->execute([
                'user_id' => $current_user_data['user_id'],
                'bio' => $bio
            ]);
            
            // Redirect to view mode
            header('Location: /meet-our-team.php?i=' . $qik->encodeId($staffid));
            exit;
        }
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Get staff member data
$sql = "SELECT u.*, 
               ua.description as job_description,
               c.display_name as job_title,
               c.label as job_status
        FROM bg_users u 
        LEFT JOIN bg_user_attributes ua ON u.user_id = ua.user_id 
            AND ua.type = 'profile' 
            AND ua.name = 'job_description'
            AND ua.status = 'active'
        LEFT JOIN bg_content c ON c.description = CAST(u.user_id AS CHAR)
            AND c.type = 'Role Description'
            AND c.category = 'Job Listing'
        WHERE u.user_id = :staff_id";
$stmt = $database->prepare($sql);
$stmt->execute(['staff_id' => $staffid]);
$staff_data = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$staff_data) {
    session_tracking('error-redirecting', 'Staff member not found: '. $staffid);
    header('Location: /careers');
    exit;
}

$additionalstyles .= '
<style>
.team-bio {
    white-space: pre-wrap;
    line-height: 1.6;
}
.char-counter {
    font-size: 0.875rem;
    color: #6c757d;
}
.avatar-large {
    width: 200px;
    height: 200px;
    object-fit: cover;
    border-radius: 50%;
}
</style>
';

echo '    
<div class="container main-content my-5 py-5">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Meet Our Team</h2>
    <a href="/careers" class="btn btn-sm btn-outline-secondary">Back To Careers</a>
  </div>


    <div class="row justify-content-center">
        <div class="">
            <div class="card shadow-sm p-5">
                <div class="card-body text-center">
                    <img src="' . (!empty($staff_data['avatar']) ? $staff_data['avatar'] : $website['defaultavatar']) . '" 
                         class="avatar-large mb-4" 
                         alt="' . htmlspecialchars($staff_data['first_name'] . ' ' . $staff_data['last_name']) . '">
                    ';


// Then in the display section, add the job title under the name:
echo '<h2 class="mb-2">' . htmlspecialchars($staff_data['first_name'] . ' ' . $staff_data['last_name']) . '</h2>';
if (!empty($staff_data['job_title'])) {
    echo '<h4 class="text-muted mb-4">' . htmlspecialchars($staff_data['job_title']) . '</h4>';
    if (!empty($staff_data['job_status'])) {
        echo '<div class="badge bg-success mb-4">' . htmlspecialchars($staff_data['job_status']) . '</div>';
    }
}


if ($edit_mode) {
    echo '
    <form method="post" action="" class="text-start">    
                '.$display->inputcsrf_token().'
        <input type="hidden" name="action" value="update_bio">
        <div class="mb-3">
            <label for="bio" class="form-label">Role Biography</label>
            <textarea class="form-control" 
                      id="bio" 
                      name="bio" 
                      rows="10" 
                      maxlength="5000" 
                      onkeyup="updateCharCount(this)">' . htmlspecialchars($staff_data['job_description']) . '</textarea>
            <div class="char-counter mt-2">
                <span id="charCount">0</span>/5000 characters
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="?i=' . $qik->encodeId($staffid) . '" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>';
} else {
    echo '
    <div class="team-bio text-start mb-4">
        ' . nl2br(htmlspecialchars($staff_data['job_description']??'')) . '
    </div>';
    
    if ($is_owner) {
        echo '
        <div class="text-end">
            <a href="?i=' . $qik->encodeId($staffid) . '&edit=1" class="btn btn-outline-primary">
                Edit Job Description
            </a>
        </div>';
    }
}
echo '
                </div>
            </div>
        </div>
    </div>
</div>';

echo '
<script>
function updateCharCount(textarea) {
    const count = textarea.value.length;
    document.getElementById("charCount").textContent = count;
}

document.addEventListener("DOMContentLoaded", function() {
    const textarea = document.getElementById("bio");
    if (textarea) {
        updateCharCount(textarea);
    }
});
</script>';

$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();