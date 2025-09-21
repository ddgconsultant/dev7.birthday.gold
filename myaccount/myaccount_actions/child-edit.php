<?php
$addClasses[] = 'fileuploader';
$addClasses[] = 'createaccount';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Verify the request has a valid token
if (!$app->formposted('GET')) {
    header('Location: /myaccount/parental-mode');
    exit;
}

$child_id = intval($_GET['id'] ?? 0);

if ($child_id <= 0) {
    $session->set('ALERT_MESSAGE', 'Invalid child account ID');
    header('Location: /myaccount/parental-mode');
    exit;
}

// Verify this child belongs to the current user and get child data
try {
    $child_stmt = $database->prepare("SELECT * FROM bg_users WHERE user_id = :child_id AND feature_parent_id = :parent_id AND account_type = 'minor' AND status = 'active'");
    $child_stmt->execute([
        ':child_id' => $child_id,
        ':parent_id' => $current_user_data['user_id']
    ]);
    
    $child_data = $child_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$child_data) {
        $session->set('ALERT_MESSAGE', 'Child account not found or access denied');
        header('Location: /myaccount/parental-mode');
        exit;
    }
    
} catch (Exception $e) {
    $session->set('ALERT_MESSAGE', 'Error accessing child account: ' . $e->getMessage());
    header('Location: /myaccount/parental-mode');
    exit;
}

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$modalData = [];
$componentConfig = [];
$output_end = '';
$errormessage = '';
$transferpagedata = [];
$newfileuploadedid = false;
$user_id = $child_id; // Use child's ID for image management
$moduleimages=$_SERVER['DOCUMENT_ROOT'] . '/myaccount/module_images';

// Set custom redirect URL for upload handler
$custom_redirect_url = '/myaccount/myaccount_actions/child-edit?id=' . $child_id . '&_token=' . $display->inputcsrf_token('tokenonly');

// Load component configurations for the child
include($moduleimages . '/images_avatars.inc');

#-------------------------------------------------------------------------------
# HANDLE POSTED FORM
#-------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    
    // Handle image upload FIRST - separate from regular form validation
    if (isset($_POST['type']) && $_POST['type'] == 'avatar') {
        session_tracking('Child edit - avatar upload detected', 'Processing avatar upload for child_id: ' . $child_id);
        
        try {
            // Set custom redirect URL for upload handler  
            $custom_redirect_url = '/myaccount/myaccount_actions/child-edit?id=' . $child_id . '&_token=' . $display->inputcsrf_token('tokenonly');
            
            // Temporarily modify the system endpostpage function or capture the redirect
            $transferpagedata = [];
            
            // Use the ACTUAL working profile-images upload handler
            ob_start();
            include($moduleimages . '/profile_image_uploadhandler.inc');
            $upload_output = ob_get_clean();
            
            // Reload child data to show new avatar
            $child_stmt = $database->prepare("SELECT * FROM bg_users WHERE user_id = :child_id AND feature_parent_id = :parent_id AND account_type = 'minor' AND status = 'active'");
            $child_stmt->execute([
                ':child_id' => $child_id,
                ':parent_id' => $current_user_data['user_id']
            ]);
            $child_data = $child_stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            session_tracking('Child edit - avatar upload error', $e->getMessage());
            $session->set('ALERT_MESSAGE', 'Error uploading avatar: ' . $e->getMessage());
        }
        
        // For avatar uploads, redirect back to the child-edit page
        header('Location: /myaccount/myaccount_actions/child-edit?id=' . $child_id . '&_token=' . $display->inputcsrf_token('tokenonly'));
        exit;
    } else {
    
    // Handle regular form updates
    session_tracking('Child edit - regular form update detected', 'Processing form data for child_id: ' . $child_id);
    try {
        $birthday = trim($_POST['dob'] ?? '');
        $first_name = trim($_POST['first'] ?? '');
        $last_name = trim($_POST['last'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        
        if (empty($birthday)) {
            throw new Exception('Birthday is required');
        }
        if (empty($gender)) {
            throw new Exception('Gender is required');
        }
        if (empty($username)) {
            throw new Exception('Username is required');
        }
        if (empty($email)) {
            throw new Exception('Email is required');
        }
        
        // Update child account (no avatar field update - that's handled by image system)
        $update_stmt = $database->prepare("UPDATE bg_users SET 
            first_name = :first_name,
            last_name = :last_name,
            username = :username,
            email = :email,
            gender = :gender,
            birthdate = :birthday,
            modify_dt = NOW()
            WHERE user_id = :child_id AND feature_parent_id = :parent_id");
            
        $update_stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':username' => $username,
            ':email' => $email,
            ':gender' => $gender,
            ':birthday' => $birthday,
            ':child_id' => $child_id,
            ':parent_id' => $current_user_data['user_id']
        ]);
        
        // Update the main users table avatar field to sync with bg_user_attributes
        $sync_avatar_query = $database->prepare("SELECT description FROM bg_user_attributes WHERE user_id = :user_id AND type = 'profile_image' AND name = 'avatar' AND status = 'active' ORDER BY create_dt DESC LIMIT 1");
        $sync_avatar_query->execute([':user_id' => $child_id]);
        $latest_avatar = $sync_avatar_query->fetchColumn();
        
        if ($latest_avatar) {
            $sync_stmt = $database->prepare("UPDATE bg_users SET avatar = :avatar WHERE user_id = :child_id");
            $sync_stmt->execute([':avatar' => $latest_avatar, ':child_id' => $child_id]);
        }
        
        session_tracking('Child account updated', 'child_id: ' . $child_id . ' by parent_id: ' . $current_user_data['user_id']);
        $session->set('ALERT_MESSAGE', 'Child account updated successfully');
        
    } catch (Exception $e) {
        $session->set('ALERT_MESSAGE', 'Error updating child account: ' . $e->getMessage());
        session_tracking('Child account update error', $e->getMessage());
    }
    
    header('Location: /myaccount/parental-mode');
    exit;
    } // End of regular form updates else block
}

$bodycontentclass='';

// Add v7 theme CSS  
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">';

// Add profile images CSS and JS
$additionalstyles .= '
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined">
<link rel="stylesheet" href="/public/js/fileupload-drag-drop/fileUpload.css">
<script src="/public/js/fileupload-drag-drop/dist/fileUpload.js"></script>
<script src="/public/js/core-profile-images.js"></script>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Parse birthday
$birthday_parts = explode('-', $child_data['birthdate']);
$birth_year = $birthday_parts[0] ?? '';
$birth_month = $birthday_parts[1] ?? '';
$birth_day = $birthday_parts[2] ?? '';
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-pencil me-3"></i>Edit Child Account</h1>
            <p class="lead mb-0">Update <?php echo htmlspecialchars($child_data['first_name'] . ' ' . $child_data['last_name']); ?>'s account information</p>
        </div>
    </div>
</div>

<div class="container my-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-end mb-3">
                <a href="/myaccount/parental-mode" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Parental Mode
                </a>
            </div>
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Child Account Details</h5>
                </div>
                <div class="card-body">
                    <form method="post" id="editchild-form" enctype="multipart/form-data">
                        <?php echo $display->inputcsrf_token(); ?>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input class="form-control" name="first" id="first" type="text" placeholder="First Name" value="<?php echo htmlspecialchars($child_data['first_name']); ?>" required />
                                    <label for="first">First Name <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input class="form-control" name="last" id="last" type="text" placeholder="Last Name" value="<?php echo htmlspecialchars($child_data['last_name']); ?>" required />
                                    <label for="last">Last Name <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="input-group">
                                    <div class="form-floating flex-grow-1">
                                        <input class="form-control" name="username" id="username" type="text" placeholder="Username" value="<?php echo htmlspecialchars($child_data['username']); ?>" required />
                                        <label for="username">Username <span class="text-danger">*</span></label>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateNewUsername()" title="Generate new username">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <span id="username-help">Username for login and account identification</span>
                                    <span id="username-availability" class="ms-2"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="email" class="form-control" name="email" id="email" placeholder="Email Address" value="<?php echo htmlspecialchars($child_data['email']); ?>" required>
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                </div>
                                <div class="form-text">Email address for account communications</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group" aria-label="Gender selection">
                                    <input type="radio" class="btn-check" name="gender" id="gender_male" value="male" <?php echo ($child_data['gender'] == 'male') ? 'checked' : ''; ?> required>
                                    <label class="btn btn-outline-primary" for="gender_male">Male</label>
                                    
                                    <input type="radio" class="btn-check" name="gender" id="gender_female" value="female" <?php echo ($child_data['gender'] == 'female') ? 'checked' : ''; ?> required>
                                    <label class="btn btn-outline-primary" for="gender_female">Female</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Birthday <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-floating">
                                            <select class="form-control" name="birth_month" id="birth_month" required>
                                                <option value="">Month</option>
                                                <?php for($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo ($birth_month == sprintf('%02d', $m)) ? 'selected' : ''; ?>>
                                                    <?php echo date('F', mktime(0,0,0,$m,1,2000)); ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                            <label for="birth_month">Month</label>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-floating">
                                            <select class="form-control" name="birth_day" id="birth_day" required>
                                                <option value="">Day</option>
                                                <?php for($d = 1; $d <= 31; $d++): ?>
                                                <option value="<?php echo sprintf('%02d', $d); ?>" <?php echo ($birth_day == sprintf('%02d', $d)) ? 'selected' : ''; ?>>
                                                    <?php echo $d; ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                            <label for="birth_day">Day</label>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-floating">
                                            <select class="form-control" name="birth_year" id="birth_year" required>
                                                <option value="">Year</option>
                                                <?php 
                                                $current_year = date('Y');
                                                $min_year = $current_year - 17; // Max age for minor
                                                $max_year = $current_year; // Born this year
                                                for($y = $max_year; $y >= $min_year; $y--): ?>
                                                <option value="<?php echo $y; ?>" <?php echo ($birth_year == $y) ? 'selected' : ''; ?>>
                                                    <?php echo $y; ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                            <label for="birth_year">Year</label>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="dob" id="dob" value="<?php echo htmlspecialchars($child_data['birthdate']); ?>">
                            </div>
                        </div>
                        
                        <!-- Note: Avatar upload moved below main form to avoid nesting -->
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="/myaccount/parental-mode" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Avatar Upload Section (Separate Form) -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-camera me-2"></i>Profile Picture</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get child's current avatar from bg_user_attributes
                    $avatar_query = $database->prepare("SELECT description FROM bg_user_attributes WHERE user_id = :user_id AND type = 'profile_image' AND name = 'avatar' AND status = 'active' ORDER BY create_dt DESC LIMIT 1");
                    $avatar_query->execute([':user_id' => $child_id]);
                    $current_avatar = $avatar_query->fetchColumn();
                    
                    if (!$current_avatar) {
                        $current_avatar = $child_data['avatar'] ?: '/public/avatars/problemavatar.png';
                    }
                    ?>
                    
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo htmlspecialchars($current_avatar); ?>" 
                             alt="Current Avatar" class="rounded-circle me-3" 
                             style="width: 100px; height: 100px; object-fit: contain;">
                        <div>
                            <div class="mb-2"><strong>Current Profile Picture</strong></div>
                            <small class="text-muted">Upload a new image to replace it</small>
                        </div>
                    </div>
                    
                    <form method="post" enctype="multipart/form-data" class="image-upload-form">
                        <?php echo $display->inputcsrf_token(); ?>
                        <input type="hidden" name="type" value="avatar">
                        
                        <div class="mb-3">
                            <label for="avatar-upload" class="form-label">Choose New Avatar</label>
                            <input type="file" class="form-control" id="avatar-upload" name="files" accept="image/*" required>
                            <div class="form-text">Recommended size: 500x500px. Max file size: 3MB. Formats: JPG, PNG, WebP</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i>Upload New Avatar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generateNewUsername() {
    const firstName = document.getElementById('first').value;
    const lastName = document.getElementById('last').value;
    const birthday = document.getElementById('dob').value;
    
    if (!firstName || !lastName || !birthday) {
        alert('Please fill in first name, last name, and birthday before generating a username.');
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('action', 'generate_username');
    formData.append('first_name', firstName);
    formData.append('last_name', lastName);
    formData.append('birthday', birthday);
    
    fetch('/helper_generateusername', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(username => {
        if (username && username.length > 0) {
            document.getElementById('username').value = username.trim();
            // Trigger availability check
            checkUsernameAvailability(username.trim());
        } else {
            alert('Failed to generate username. Please try again.');
        }
    })
    .catch(error => {
        alert('Error generating username: ' + error.message);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const birthMonth = document.getElementById('birth_month');
    const birthDay = document.getElementById('birth_day');
    const birthYear = document.getElementById('birth_year');
    const dobHidden = document.getElementById('dob');
    const usernameField = document.getElementById('username');
    const availabilitySpan = document.getElementById('username-availability');
    
    let checkTimeout = null;
    
    function checkUsernameAvailability(username) {
        if (username.length < 3) {
            availabilitySpan.textContent = '';
            return;
        }
        
        availabilitySpan.innerHTML = '<i class="spinner-border spinner-border-sm" role="status"></i> Checking...';
        availabilitySpan.className = 'ms-2 text-muted';
        
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        formData.append('type', 'f.username');
        formData.append('username', username);
        formData.append('current_child_id', '<?php echo $child_id; ?>'); // Exclude current child from check
        
        fetch('/helper_checkavailability', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(available => {
            console.log('Username availability response:', available, 'for username:', username);
            
            if (available === '1' || available === '2' || available === 'true') {
                if (available === '2') {
                    availabilitySpan.innerHTML = '<i class="bi bi-check-circle text-info"></i> Current Username';
                    availabilitySpan.className = 'ms-2 text-info';
                } else {
                    availabilitySpan.innerHTML = '<i class="bi bi-check-circle text-success"></i> Available';
                    availabilitySpan.className = 'ms-2 text-success';
                }
                usernameField.classList.remove('is-invalid');
                usernameField.classList.add('is-valid');
            } else {
                availabilitySpan.innerHTML = '<i class="bi bi-x-circle text-danger"></i> Not Available (response: ' + available + ')';
                availabilitySpan.className = 'ms-2 text-danger';
                usernameField.classList.remove('is-valid');
                usernameField.classList.add('is-invalid');
            }
        })
        .catch(error => {
            availabilitySpan.innerHTML = '<i class="bi bi-exclamation-triangle text-warning"></i> Check failed';
            availabilitySpan.className = 'ms-2 text-warning';
        });
    }
    
    // Username availability checking with debounce
    if (usernameField) {
        usernameField.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(() => {
                checkUsernameAvailability(this.value.trim());
            }, 500);
        });
        
        usernameField.addEventListener('blur', function() {
            if (this.value.trim()) {
                checkUsernameAvailability(this.value.trim());
            }
        });
    }
    
    function updateDobField() {
        if (birthMonth && birthDay && birthYear && 
            birthMonth.value && birthDay.value && birthYear.value) {
            dobHidden.value = birthYear.value + '-' + birthMonth.value + '-' + birthDay.value;
        }
    }
    
    if (birthMonth) birthMonth.addEventListener('change', updateDobField);
    if (birthDay) birthDay.addEventListener('change', updateDobField);
    if (birthYear) birthYear.addEventListener('change', updateDobField);
    
    // Form validation
    const form = document.getElementById('editchild-form');
    form.addEventListener('submit', function(e) {
        if (!dobHidden.value) {
            e.preventDefault();
            alert('Please select a complete birth date');
            return;
        }
        
        const selectedGender = document.querySelector('input[name="gender"]:checked');
        if (!selectedGender) {
            e.preventDefault();
            alert('Please select a gender');
            return;
        }
        
        const username = document.querySelector('input[name="username"]').value.trim();
        if (!username) {
            e.preventDefault();
            alert('Please enter a username');
            return;
        }
    });
});
</script>

<?php 
include($dir['core_components'] . '/bg_footer.inc'); 
$app->outputpage();
?>