<?php
$addClasses[] = 'fileuploader';
$addClasses[] = 'account';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass='';

// Add v7 theme CSS
$additionalstyles = '<link rel="stylesheet" href="/public/css/v7/bg_theme.css">';

// Reload user data to ensure we have the latest information (including avatar)
$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');

// Check if this is the first visit to parental mode
$userId = $current_user_data['user_id'];

// Check for first parental visit attribute
$stmt = $database->prepare("SELECT * FROM bg_user_attributes WHERE user_id = :user_id AND name = 'first_parental_visit' AND status = 'active' LIMIT 1");
$stmt->execute([':user_id' => $userId]);
$first_visit_record = $stmt->fetch(PDO::FETCH_ASSOC);
$is_first_visit = empty($first_visit_record);

// If it's first visit, mark it
if ($is_first_visit) {
    $stmt = $database->prepare("INSERT INTO bg_user_attributes (user_id, type, name, string_value, status, create_dt, modify_dt)
                                VALUES (:user_id, 'system', 'first_parental_visit', :timestamp, 'active', NOW(), NOW())");
    $stmt->execute([
        ':user_id' => $userId,
        ':timestamp' => date('Y-m-d H:i:s')
    ]);
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-people me-3"></i>Parental Mode</h1>
            <p class="lead mb-0">Manage child accounts and monitor their birthday rewards activity</p>
        </div>
    </div>
</div>

<?php

$additionalstyles .= '
<style>
.account-row {
    border-top: 1px solid #e0e0e0;
}
.account-row:hover {
    background-color: #f0f0f0;
    transition: background-color 0.3s ease;
}

/* Security-style card header */
.parental-card-header {
    padding: 1.5rem;
    background: #e9ecef;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: nowrap;
    gap: 1rem;
}

.parental-card-title {
    display: flex;
    align-items: center;
    margin: 0;
}

.parental-card-title h5 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #212529;
}

.parental-card-icon {
    font-size: 2rem;
    margin-right: 1rem;
    color: #6f42c1;
}

/* Pill-shaped button */
.btn-add-child {
    background: #198754;
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-add-child:hover {
    background: #157347;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(25, 135, 84, 0.3);
}

.btn-add-child:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Square badge style */
.badge-square {
    border-radius: 4px !important;
    padding: 0.25rem 0.75rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
}

/* Pill-shaped switch account button */
.btn-switch-account {
    border-radius: 25px !important;
    padding: 0.375rem 1.5rem !important;
    font-weight: 500;
    font-size: 1rem;
}

/* Pill-shaped management buttons */
.collapse .btn-sm {
    border-radius: 20px !important;
    padding: 0.25rem 1rem !important;
    font-weight: 500;
}

/* Collapse card styling */
.collapse .card {
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Form card frame */
#addchild-form .card {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    background-color: #f8f9fa;
}

/* Responsive form width */
@media (min-width: 992px) {
    #addchild-form .card {
        width: 50%;
    }
}

@media (max-width: 991px) {
    #addchild-form .card {
        width: 100%;
        max-width: 100% !important;
    }
}
</style>';

// $userId already defined above at line 12
$query = $database->prepare("SELECT * FROM bg_users WHERE feature_parent_id = :parent_id and `status`='active' and `account_type`='minor'");
$query->bindParam(':parent_id', $userId, PDO::PARAM_INT);
$query->execute();
$childaccount_records = $query->fetchAll(PDO::FETCH_ASSOC);
$minorcount = count($childaccount_records);

echo '
<div class="container my-5 pt-5">';

// Show first visit welcome message
if ($is_first_visit) {
    $children_remaining = 6 - $minorcount;
    $children_message = '';
    if ($children_remaining > 0) {
        $children_message = ' (you can add ' . $children_remaining . ' more)';
    } elseif ($children_remaining == 0) {
        $children_message = ' (maximum reached)';
    }

    echo '
    <div class="alert alert-primary alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-start">
            <i class="bi bi-info-circle-fill fs-4 me-3 mt-1"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-2">Welcome to Parental Mode!</h5>
                <p class="mb-3 text-muted">As a parent, you can manage birthday rewards for your children and yourself. Here\'s what you can do:</p>
                <ul class="mb-0 text-muted">
                    <li>Add Child Accounts: Create profiles for children 16 and younger' . $children_message . '</li>
                    <li>Manage Your Own Profile: Don\'t forget to set up birthday rewards for yourself too!</li>
                    <li>Switch Between Accounts: Easily switch to manage enrollments for each family member</li>
                </ul>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>';
}

// Always show action buttons (right-aligned)
echo '
    <div class="d-flex gap-2 justify-content-end mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#parentalGuideModal">
            <i class="bi bi-book me-2"></i>View Full Guide
        </button>
        <a href="/myaccount/profile" class="btn btn-outline-primary">
            <i class="bi bi-person-circle me-2"></i>Manage My Profile
        </a>
    </div>';

echo '
    <div class="card mb-3 mb-lg-0">
        <div class="parental-card-header">
            <div class="parental-card-title">
                <i class="bi bi-people-fill parental-card-icon"></i>
                <div>
                    <h5 class="mb-0">Child Accounts
                        <i class="bi bi-info-circle text-muted ms-2"
                           style="font-size: 1rem; cursor: pointer;"
                           data-bs-toggle="modal"
                           data-bs-target="#parentalGuideModal"
                           title="View parental guide"></i>
                    </h5>
                    <span class="badge badge-square bg-secondary mt-1" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $minorcount . ' Child Accounts">' . $minorcount . ' Active</span>
                </div>
            </div>
            ' . ($minorcount < 6 ? '
            <a class="btn-add-child" href="#addchild-form" data-bs-toggle="collapse" aria-expanded="false" aria-controls="addchild-form">
                <i class="bi bi-plus-circle"></i>
                Add Child
            </a>' : '
            <button class="btn-add-child" disabled data-bs-toggle="tooltip" data-bs-placement="left" title="You have reached the maximum number of child accounts. Each family plan includes up to 6 children.">
                <i class="bi bi-x-circle"></i>
                Max. 6 Reached
            </button>') . '
        </div>
        ';

        if ($minorcount >= 6){

echo '  <div class="card-body">';


        } else {

echo '
        <div class="card-body">
            <div class="alert alert-info mb-4">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="bi bi-info-circle me-2"></i>Child accounts are only for children 16 and younger.</li>
                    <li class="mb-2"><i class="bi bi-info-circle me-2"></i>Children will only be shown businesses that allow children to be enroll in their reward programs.</li>
                    <li class="mb-2"><i class="bi bi-info-circle me-2"></i>Parents are responsible for all activity between businesses and their children.</li>
                    <li class="mb-2"><i class="bi bi-info-circle me-2"></i>You can have a total of six child accounts.</li>
                    <li><i class="bi bi-info-circle me-2"></i>Child accounts will automatically be disconnected from the parental account after their 16th birthday.</li>
                </ul>
            </div>


            <div class="collapse" id="addchild-form">
                <div class="card mt-3 mx-auto" style="max-width: 800px;">
                    <div class="card-body">
                        <form class="" id="addnewminor" action="/myaccount/myaccount_actions/child-add" method="POST">' . $display->inputcsrf_token() . '
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input class="form-control" name="first" id="first" type="text" placeholder="First Name" required />
                                        <label for="first">First Name <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input class="form-control" name="last" id="last" type="text" placeholder="Last Name" value="' . htmlspecialchars($current_user_data['last_name']) . '" required />
                                        <label for="last">Last Name <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2" style="max-width: 400px;">
                                        <div class="flex-fill">
                                            <select class="form-control" id="birth_month" name="birth_month">
                                                <option value="">Month</option>';
                                            
                                                $months = [
                                                    "01" => "01 - January", "02" => "02 - February", "03" => "03 - March",
                                                    "04" => "04 - April", "05" => "05 - May", "06" => "06 - June",
                                                    "07" => "07 - July", "08" => "08 - August", "09" => "09 - September",
                                                    "10" => "10 - October", "11" => "11 - November", "12" => "12 - December"
                                                ];
                                                foreach ($months as $value => $label) {
                                                    echo "<option value=\"$value\">$label</option>";
                                                }
                                             
                                                echo '
                                            </select>
                                        </div>
                                        <div style="width: 95px;">
                                            <select class="form-control" id="birth_day" name="birth_day">
                                                <option value="">Day</option>
                                          ';
                                          
                                                for ($i = 1; $i <= 31; $i++) {
                                                    $day = str_pad($i, 2, "0", STR_PAD_LEFT);
                                                    echo "<option value=\"$day\">$i</option>";
                                                }
                                                echo '
                                            </select>
                                        </div>
                                        <div style="width: 120px;">
                                            <select class="form-control" id="birth_year" name="birth_year">
                                                <option value="">Year</option>
                                       ';
                                                $current_year = date("Y");
                                                $min_age_year = $current_year - 16;
                                                $start_year = $current_year - 20; // Only show last 20 years for child accounts
                                                for ($i = $current_year; $i >= $start_year; $i--) {
                                                    $class = ($i < $min_age_year) ? "class=\"text-danger\"" : "";
                                                    echo "<option value=\"$i\" $class>$i</option>";
                                                }
                                      echo '
                                            </select>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Child must be 16 or younger</small>
                                    <input type="hidden" id="dob" name="dob" value="" />
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <select class="form-select" name="gender" id="gender" required>
                                            <option value="">Select gender...</option>
                                            <option value="M">Male</option>
                                            <option value="F">Female</option>
                                            <option value="O">Other</option>
                                        </select>
                                        <label for="gender">Gender <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" id="useCustomEmail" name="useCustomEmail">
                                        <label class="form-check-label" for="useCustomEmail">
                                            Use custom email address
                                            <i class="bi bi-info-circle ms-1" data-bs-toggle="modal" data-bs-target="#emailInfoModal" style="cursor: pointer;"></i>
                                        </label>
                                    </div>
                                    <div id="emailField"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <hr class="my-3">
                                    <button class="btn btn-primary" type="submit">Save Child Account</button>
                                    <button class="btn btn-secondary ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#addchild-form">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>';
        }


foreach ($childaccount_records as $row) {
    $young_person = $app->calculateage($row['birthdate']);
    $cid = $row['user_id'];
    $signinbutton = '<a class="btn btn-primary btn-switch-account accountswitch me-2" href="/myaccount/myaccount_actions/switch2minor?id=' . $cid . '&pid=' . $current_user_data['user_id'] . '&_token=' . $display->inputcsrf_token('tokenonly') . '">Switch Account</a>';
    $settingsbutton = '<button class="btn btn-light p-2" type="button" data-bs-toggle="collapse" data-bs-target="#minorcontroller' . $row['user_id'] . '" aria-expanded="false" aria-controls="minorcontroller' . $row['user_id'] . '"><i class="bi bi-gear fs-4"></i></button>';
    // Get avatar from bg_user_attributes first (proper image management system), then fallback to bg_users.avatar
    $avatar_stmt = $database->prepare("SELECT description FROM bg_user_attributes WHERE user_id = :user_id AND type = 'profile_image' AND name = 'avatar' AND status = 'active' ORDER BY create_dt DESC LIMIT 1");
    $avatar_stmt->execute([':user_id' => $row['user_id']]);
    $avatar = $avatar_stmt->fetchColumn();
    
    // Fallback to bg_users.avatar if no avatar in attributes
    if (!$avatar) {
        $avatar = !empty($row['avatar']) ? $row['avatar'] : '/public/images/defaultavatar.png';
    }

    echo '
    <div class="account-row d-flex align-items-center justify-content-between my-2 px-3 py-4">
        <a href="#!" class="d-flex align-items-center me-3">
            <img class="rounded-circle" src="' . $avatar . '" alt="" style="width: 75px; height: 75px; object-fit: contain;" />
        </a>
        <div class="flex-grow-1 ps-3">
            <h6 class="fs-9 mb-1"><a href="#!">' . htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) . '</a></h6>
            <p class="text-1000 mb-0">' . htmlspecialchars($row['birthdate']) . ' • ' . htmlspecialchars($young_person['agetag']) . ' old</p>
            <p>' . htmlspecialchars($row['username']) . '</p>
        </div>
        <div class="d-flex align-items-center">
            ' . $signinbutton . '
            <div class="ms-2">' . $settingsbutton . '</div>
        </div>
    </div>
    <div class="collapse" id="minorcontroller' . $row['user_id'] . '">
        <div class="card card-body ms-5 mt-2 mb-3">
            <h6 class="mb-3">Manage Child Account</h6>
            <div class="d-flex gap-2 flex-wrap">
                <a href="/myaccount/myaccount_actions/child-edit?id=' . $cid . '&_token=' . $display->inputcsrf_token('tokenonly') . '" 
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1 d-none d-md-inline"></i><span class="d-md-none">Edit</span><span class="d-none d-md-inline">Edit Profile</span>
                </a>
                <a href="/myaccount/myaccount_actions/child-password-reset?id=' . $cid . '&_token=' . $display->inputcsrf_token('tokenonly') . '" 
                   class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-key me-1 d-none d-md-inline"></i><span class="d-md-none">Reset Password</span><span class="d-none d-md-inline">Reset Password</span>
                </a>
                <button type="button" 
                        class="btn btn-sm btn-outline-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#deactivateModal"
                        data-child-id="' . $cid . '"
                        data-child-name="' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '">
                    <i class="bi bi-x-circle me-1 d-none d-md-inline"></i><span class="d-md-none">Delete</span><span class="d-none d-md-inline">Deactivate Account</span>
                </button>
            </div>
            <div class="mt-3 text-muted small">
                <i class="bi bi-info-circle me-1"></i>
                Account created: ' . (new DateTime($row['create_dt']))->format('M d, Y') . '
            </div>
        </div>
    </div>';
}

echo '</div>
    </div>
</div>';

echo '
<div class="modal fade" id="emailInfoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Important Email Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>By default, we\'ll create a mybdaygold.com email address for your child based on their name. 
                This allows you to maintain parental control over the account.</p>
                <p>If you choose to use a custom email address, please note that your child could potentially
                change their password and take control of the reward account. Make sure you trust your child
                with this responsibility before using a custom email.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>';

echo '
<!-- Deactivate Child Account Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Deactivate Child Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate the account for <strong id="childName"></strong>?</p>
                <p>This action will:</p>
                <ul>
                    <li>Make the account inactive</li>
                    <li>Prevent the child from logging in</li>
                    <li>Preserve all account data for potential reactivation</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeactivate" class="btn btn-danger">
                    <i class="bi bi-x-circle me-1"></i>Deactivate Account
                </a>
            </div>
        </div>
    </div>
</div>';

?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    <?php if ($is_first_visit): ?>
    // Auto-show the parental guide modal on first visit
    setTimeout(function() {
        var guideModal = new bootstrap.Modal(document.getElementById('parentalGuideModal'));
        guideModal.show();
    }, 1000);
    <?php endif; ?>
    
    const form = document.getElementById('addnewminor');
    const emailCheckbox = document.getElementById('useCustomEmail');
    const emailField = document.getElementById('emailField');
    let hasShownModal = false;
    let generatedEmail = '';

    function generateEmail() {
        const firstName = document.getElementById('first').value;
        const lastName = document.getElementById('last').value;
        const dob = document.getElementById('dob').value;

        if (firstName) {
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value);
            formData.append('type', 'f.email');
            formData.append('first_name', firstName);
            formData.append('last_name', lastName || '');
            formData.append('birthday', dob || '');

            fetch('/helper_generateusername', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(email => {
                generatedEmail = email;
                updateEmailField();
                // Automatically check availability of generated email
                if (email && !emailCheckbox.checked) {
                    checkEmailAvailability(email);
                }
            });
        }
    }

    function checkEmailAvailability(email) {
        const emailInput = document.querySelector('input[name="email"]');
        const availabilitySpan = document.getElementById('availabilityIndicator');
        
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        formData.append('type', 'f.email');
        formData.append('username', email.toLowerCase().replace('@mybdaygold.com', ''));

        fetch('/helper_checkavailability', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(available => {
            console.log('Availability response:', available);
            if (available === '1') {
                emailInput.classList.add('border-success');
                availabilitySpan.textContent = 'Available';
                availabilitySpan.classList.remove('text-danger');
                availabilitySpan.classList.add('text-success');
            } else {
                emailInput.classList.remove('border-success');
                availabilitySpan.textContent = 'Not Available';
                availabilitySpan.classList.remove('text-success');
                availabilitySpan.classList.add('text-danger');
            }
        });
    }

    function updateEmailField() {
        if (emailCheckbox.checked) {
            emailField.innerHTML = `
                <div class='form-floating'>
                    <input type='email' class='form-control' name='email' id='email' placeholder='Email Address' required>
                    <label for='email'>Email Address <span class='text-danger'>*</span></label>
                </div>
                <span id='availabilityIndicator' class='ms-2'></span>`;
        } else {
            emailField.innerHTML = `
                <div class='position-relative'>
                    <div class='form-floating'>
                        <input type='email' class='form-control bg-light pe-5' name='email' id='email' 
                            value='${generatedEmail || ''}' placeholder='Email Address' readonly>
                        <label for='email'>Email Address (Auto-generated)</label>
                    </div>
                    <button type='button' class='btn btn-sm btn-light position-absolute' 
                        style='top: 50%; right: 10px; transform: translateY(-50%); padding: 0.25rem 0.5rem;'
                        onclick='enableEmailEdit(this)'>
                        <i class='bi bi-pencil'></i>
                    </button>
                </div>
                <div class='d-flex justify-content-between align-items-center mt-1'>
                    <span id='availabilityIndicator'></span>
                    <button type='button' class='btn btn-sm btn-primary d-none' id='checkAvailability' 
                        onclick='checkEmailAvailability(document.getElementById("email").value)'>
                        Check Availability
                    </button>
                </div>`;
        }
    }

    function enableEmailEdit(pencilBtn) {
        const parentDiv = pencilBtn.closest('.position-relative').parentElement;
        const emailInput = parentDiv.querySelector('input[name="email"]');
        const checkBtn = parentDiv.querySelector('#checkAvailability');
        
        emailInput.readOnly = false;
        emailInput.classList.remove('bg-light');
        checkBtn.classList.remove('d-none');
        pencilBtn.classList.add('d-none');
        emailInput.focus();
    }

    function validateAge(birthdate) {
        const today = new Date();
        const birth = new Date(birthdate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        return age <= 16;
    }

    window.enableEmailEdit = enableEmailEdit;
    window.checkEmailAvailability = checkEmailAvailability;

    document.getElementById('first').addEventListener('input', generateEmail);
    document.getElementById('last').addEventListener('change', generateEmail);
    
    // Birthday dropdown sync
    const birthMonth = document.getElementById('birth_month');
    const birthDay = document.getElementById('birth_day');
    const birthYear = document.getElementById('birth_year');
    const dobHidden = document.getElementById('dob');
    
    function updateDobField() {
        if (birthMonth && birthDay && birthYear && 
            birthMonth.value && birthDay.value && birthYear.value) {
            dobHidden.value = birthYear.value + '-' + birthMonth.value + '-' + birthDay.value;
            generateEmail(); // Generate email when birthday is complete
        }
    }
    
    if (birthMonth) birthMonth.addEventListener('change', updateDobField);
    if (birthDay) birthDay.addEventListener('change', updateDobField);
    if (birthYear) birthYear.addEventListener('change', updateDobField);

    emailCheckbox.addEventListener('change', function() {
        if (!hasShownModal && this.checked) {
            new bootstrap.Modal(document.getElementById('emailInfoModal')).show();
            hasShownModal = true;
        }
        updateEmailField();
    });

    form.addEventListener('submit', function(e) {
        const dob = document.getElementById('dob').value;
        
        if (!validateAge(dob)) {
            e.preventDefault();
            alert('Child accounts are only available for children 16 and younger');
            return;
        }

        const existingAccounts = document.querySelectorAll('.account-row').length;
        if (existingAccounts >= 6) {
            e.preventDefault();
            alert('You can only add up to 6 children for free');
            return;
        }
        
        // Ensure email has a value
        const emailInput = document.querySelector('input[name="email"]');
        if (!emailInput || !emailInput.value) {
            e.preventDefault();
            alert('Please wait for the email to be generated or check the "Use custom email address" option');
            return;
        }
    });

    // Initialize email field on page load
    updateEmailField();
    
    if (document.getElementById('first').value) {
        generateEmail();
    }
    
    // Handle deactivate modal
    const deactivateModal = document.getElementById('deactivateModal');
    if (deactivateModal) {
        deactivateModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const childId = button.getAttribute('data-child-id');
            const childName = button.getAttribute('data-child-name');
            
            document.getElementById('childName').textContent = childName;
            document.getElementById('confirmDeactivate').href = '/myaccount/myaccount_actions/child-delete?id=' + childId + '&_token=<?php echo $display->inputcsrf_token('tokenonly'); ?>';
        });
    }
});
</script>

<?php
// Add the parental guide modal (always available since button is always shown)
echo '
<!-- Parental Guide Modal -->
<div class="modal fade" id="parentalGuideModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="bi bi-book me-2"></i>Parent Guide: Getting Started</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h5 class="text-primary fw-bold mb-2"><i class="bi bi-person-circle me-2"></i>Manage Your Profile</h5>
                    <p class="small">Update your own birthday information and preferences:</p>
                    <ul style="font-size: 0.85rem;">
                        <li>Click <strong>"Manage My Profile"</strong> to edit your information</li>
                        <li>Update your birthday, contact details, and preferences</li>
                        <li>You\'ll get birthday rewards too!</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h5 class="text-primary fw-bold mb-2"><i class="bi bi-people me-2"></i>Add Your Children</h5>
                    <p class="small">Create accounts for children 16 and younger:</p>
                    <ul style="font-size: 0.85rem;">
                        <li>Click the <strong>"Add Child"</strong> button above</li>
                        <li>Enter their information (name, birthdate, gender)</li>
                        <li>You can add up to 6 children total</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h5 class="text-primary fw-bold mb-2"><i class="bi bi-arrow-left-right me-2"></i>Switch Between Accounts</h5>
                    <p class="small">Easily manage each family member:</p>
                    <ul style="font-size: 0.85rem;">
                        <li>Use <strong>"Switch Account"</strong> to access each profile</li>
                        <li>Update information as your children grow</li>
                        <li>Monitor activity for each account</li>
                    </ul>
                </div>

                <div class="mb-4 border border-primary rounded p-3 bg-light">
                    <h5 class="text-primary fw-bold mb-2"><i class="bi bi-gift-fill me-2"></i>Pick Birthday Enrollments (Do This Last!)</h5>
                    <p class="small mb-2">After setting up profiles, choose birthday reward programs:</p>
                    <ul style="font-size: 0.85rem;" class="mb-0">
                        <li>Switch to each family member\'s account</li>
                        <li>Visit <strong>"Enrollment Picker"</strong> to select businesses</li>
                        <li>Choose age-appropriate restaurants, stores, and services</li>
                        <li>Each family member can have their own selections</li>
                    </ul>
                </div>

                <div class="alert alert-info mb-0">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>Remember:</strong> These are flexible flows - you can do them in any order that works for you, but save enrollment picking for last!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="/myaccount/profile" class="btn btn-primary">
                    <i class="bi bi-person-circle me-2"></i>Manage My Profile Now
                </a>
            </div>
        </div>
    </div>
</div>';

echo '</div>';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>