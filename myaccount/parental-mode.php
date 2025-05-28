<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

$additionalstyles.='
<style>
.account-row {
    border-top: 1px solid #e0e0e0;
}
.account-row:hover {
    background-color: #f0f0f0;
    transition: background-color 0.3s ease;
}
</style>';

$userId = $current_user_data['user_id'];
$query = $database->prepare("SELECT * FROM bg_users WHERE feature_parent_id = :parent_id and `status`='active' and `account_type`='minor'");
$query->bindParam(':parent_id', $userId, PDO::PARAM_INT);
$query->execute();
$childaccount_records = $query->fetchAll(PDO::FETCH_ASSOC);
$minorcount = count($childaccount_records);

echo '
<div class="main-content mt-0 pt-0">
    <div class="card mb-3 mb-lg-0">
        <div class="card-header d-flex flex-between-center justify-content-between bg-warning-subtle">
            <div class="d-flex align-items-center">
                <h5 class="mb-0 me-2">Child Accounts</h5> 
                <span class="badge rounded-pill bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $minorcount . ' Child Accounts">' . $minorcount . '</span>
            </div>
        </div>
        ';

        if ($minorcount >= 6){

echo '  <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>You have reached the maximum number of allowed child accounts (6).
        </div>';


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

<div class="d-flex justify-content-end">
    <a class="mb-4 d-flex align-items-center btn btn-lg btn-success" href="#addchild-form" data-bs-toggle="collapse" aria-expanded="false" aria-controls="addchild-form">
        <span class="circle-dashed"><span class="bi bi-plus"></span></span>
        <span class="ms-3">Add Child</span>
    </a>
</div>

            <div class="collapse" id="addchild-form">
                <form class="row" id="addnewminor" action="/myaccount/myaccount_actions/child-add" method="POST">' . $display->inputcsrf_token() . '
                    <div class="col-3 mb-3 text-lg-end">
                        <label class="form-label" for="first">First Name</label>
                    </div>
                    <div class="col-9 col-sm-7 mb-3">
                        <input class="form-control form-control-sm" name="first" id="first" type="text" required />
                    </div>

                    <div class="col-3 mb-3 text-lg-end">
                        <label class="form-label" for="last">Last Name</label>
                    </div>
                    <div class="col-9 col-sm-7 mb-3">
                        <input class="form-control form-control-sm" name="last" id="last" type="text" value="' . htmlspecialchars($current_user_data['last_name']) . '" required />
                    </div>

                    <div class="col-3 mb-3 text-lg-end">
                        <label class="form-label" for="gender">Gender</label>
                    </div>
                    <div class="col-9 col-sm-7 mb-3">
                        <select class="form-select form-select-sm" name="gender" id="gender" required>
                            <option value="">Select gender...</option>
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                            <option value="O">Other</option>
                        </select>
                    </div>

                    <div class="col-3 text-lg-end">
                        <label class="form-label" for="dob">Birthdate</label>
                    </div>
                    <div class="col-9 col-sm-7 mb-3">
                        <input class="form-control form-control-sm" name="dob" id="dob" type="date" required />
                    </div>

                    <div class="col-3 mb-3 text-lg-end">
                        <label class="form-label">Email Address</label>
                    </div>
                    <div class="col-9 col-sm-7 mb-3">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="useCustomEmail" name="useCustomEmail">
                            <label class="form-check-label" for="useCustomEmail">
                                Use custom email address
                                <i class="bi bi-info-circle ms-1" data-bs-toggle="modal" data-bs-target="#emailInfoModal" style="cursor: pointer;"></i>
                            </label>
                        </div>
                        <div id="emailField"></div>
                    </div>

                    <div class="col-9 col-sm-7 offset-3">
                        <button class="btn btn-primary" type="submit">Save</button>
                    </div>
                </form>
                <div class="border-dashed-bottom my-3"></div>
            </div>';
        }


foreach ($childaccount_records as $row) {
    $young_person = $app->calculateage($row['birthdate']);
    $cid = $row['user_id'];
    $signinbutton = '<a class="btn btn-sm button btn-primary accountswitch px-3 me-2" href="/myaccount/myaccount_actions/switch2minor?id=' . $cid . '&pid=' . $current_user_data['user_id'] . '&_token=' . $display->inputcsrf_token('tokenonly') . '">Switch Account</a>';
    $settingsbutton = '<button class="btn p-0 m-0 pb-1" type="button" data-bs-toggle="collapse" data-bs-target="#minorcontroller' . $row['user_id'] . '" aria-expanded="false" aria-controls="minorcontroller' . $row['user_id'] . '"><i class="bi bi-gear"></i></button>';
    $avatar = !empty($row['avatar']) ? $row['avatar'] : '/public/images/defaultavatar.png';

    echo '
    <div class="account-row d-flex align-items-center justify-content-between my-2 px-3 py-4">
        <a href="#!" class="d-flex align-items-center me-3">
            <img class="img-fluid rounded-circle" src="' . $avatar . '" alt="" width="56" />
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

?>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                <div class='input-group'>
                    <input type='email' class='form-control form-control-sm' name='email' required>
                    <span id='availabilityIndicator' class='ms-2 align-self-center'></span>
                </div>`;
        } else {
            emailField.innerHTML = generatedEmail ? 
                `<div class='input-group'>
                    <span class='input-group-text btn btn-light' onclick='enableEmailEdit(this)'>
                        <i class='bi bi-pencil'></i>
                    </span>
                    <input type='email' class='form-control form-control-sm bg-light' name='email' 
                        value='${generatedEmail}' readonly>
                    <button type='button' class='btn btn-primary btn-sm d-none' id='checkAvailability' 
                        onclick='checkEmailAvailability(this.previousElementSibling.value)'>
                        Check Availability
                    </button>
                    <span id='availabilityIndicator' class='ms-2 align-self-center'></span>
                </div>` : '';
        }
    }

    function enableEmailEdit(pencilBtn) {
        const inputGroup = pencilBtn.closest('.input-group');
        const emailInput = inputGroup.querySelector('input');
        const checkBtn = inputGroup.querySelector('#checkAvailability');
        
        emailInput.readOnly = false;
        emailInput.classList.remove('bg-light');
        checkBtn.classList.remove('d-none');
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
    
    ['last', 'dob'].forEach(id => {
        document.getElementById(id).addEventListener('change', generateEmail);
    });

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
    });

    if (document.getElementById('first').value) {
        generateEmail();
    }
});
</script>

<?php
echo '</div></div></div>';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>