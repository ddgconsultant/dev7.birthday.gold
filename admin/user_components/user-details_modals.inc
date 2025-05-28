<?php
// Avatar Modal
echo '
<!-- Avatar Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarModalLabel">Profile Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img class="img-fluid rounded-circle" src="'.htmlspecialchars($workinguserdata['avatar']).'" 
                     alt="'.htmlspecialchars($workinguserdata['first_name']).' '.htmlspecialchars($workinguserdata['last_name']).'">
                <div class="mt-3">
                    <a href="/admin/user_components/regenerate-avatar?u='.htmlspecialchars($qik->encodeId($workinguserdata['user_id'])).'" 
                       class="btn btn-sm btn-primary">
                        <i class="bi bi-arrow-repeat"></i> Regenerate Avatar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Account Type/Plan Modal -->
<div class="modal fade" id="accounttypeplanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Account/Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="user_components/set_accounttypeplan.php" method="POST">
                    '.$display->inputcsrf_token().'
                    <input type="hidden" name="uid" value="'.htmlspecialchars($workinguserdata['user_id']).'">
                    
                    <div class="mb-3">
                        <label class="form-label">Account Status</label>
                        <select class="form-select" name="accountstatus">
                            '.$display->list_accountstatus($workinguserdata['status']).'
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <select class="form-select" name="accounttype">
                            '.$display->list_accounttype($workinguserdata['account_type']).'
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Account Plan</label>
                        <select class="form-select" name="accountplan">
                            '.$display->list_accountplan($workinguserdata['account_plan']).'
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="sendMail" name="send_mail" value="yes" checked>
                        <label class="form-check-label" for="sendMail">Send notification email</label>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Referer Modal -->
<div class="modal fade" id="refererModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">'.htmlspecialchars($refererbuttontitle).'</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="user_components/set_referrer.php" method="POST">
                    '.$display->inputcsrf_token().'
                    <input type="hidden" name="referer_action" value="'.htmlspecialchars($refereraction).'">
                    <input type="hidden" name="u" value="'.htmlspecialchars($qik->encodeId($workinguserdata['user_id'])).'">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Referer</label>
                        <select class="form-select" name="referer_id">
                            '.$display->list_users().'
                        </select>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>';

// Include Admin Modal if user is admin
if ($account->isadmin()) {
    echo '
    <!-- Admin Modal -->
    <div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set Admin Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="user_components/set_admin.php" method="POST">
                        '.$display->inputcsrf_token().'
                        <input type="hidden" name="uid" value="'.htmlspecialchars($workinguserdata['user_id']).'">
                        <input type="hidden" name="previousrole" value="'.htmlspecialchars($workinguserdata['account_admin']).'">
                        
                        <div class="mb-3">
                            <label class="form-label">Select Role</label>
                            <select class="form-select" name="role">
                                '.$display->list_accountadmin($workinguserdata['account_admin']).'
                            </select>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="adminSendMail" name="send_mail" value="yes" checked>
                            <label class="form-check-label" for="adminSendMail">Send notification email</label>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>';
}




if ($account->isadmin()) {
    //////////////////////////////////////////////////////////////////////////////////////////////////
    // MANAGE EMPLOYEE
   # if ($workinguserdata['account_plan']=='life') {
    $employeebuttontitle='Make Employee';
    $employeebuttoncolor='primary';
    
    if ($account->isstaff('*', $workinguserdata['user_id'])) {
    $fullName = htmlspecialchars($workinguserdata['first_name'] . ' ' . $workinguserdata['last_name']);
    $employeebuttontitle='Terminate Employee';
    $employeebuttoncolor='danger';
    }
    /*
    echo '
    <div class="row">
    <div class="col-5 col-sm-4">
    <p class="fw-semi-bold mb-0">Employee</p>
    </div>
    <div class="col">
    <p class="fw-semi-bold mb-0">'. $fullName . ' (' . $employee['username']. ')</p>
    </div>
    </div>
    ';
    }
    */
    $employeeaction=strtolower(str_replace(' ', '', $employeebuttontitle));
    
    $currentDate = new DateTime();
    $hireDate = new DateTime('first day of next month');
    $formattedHireDate = $hireDate->format('Y-m-d');
    
    
    if (!$account->isadmin($workinguserdata)){
    #echo '    <button type="button" class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#adminModal">Make Admin</button>    ';
    }
    
    #echo '    <button type="button" class="btn btn-'.$employeebuttoncolor.' btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#employeeModal">' . $employeebuttontitle . '</button>    ';
    
    echo '
    <div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog  modal-lg">
    <div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="employeeModalLabel">Employee Enrollment Form</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
    <form action="user_components/set_employee.php" method="POST" class="form-horizontal">
    ' . $display->inputcsrf_token() . '
    <input type="hidden" name="employee_action" value="' . $employeeaction . '">
    <input type="hidden" name="u" value="' . $qik->encodeId($workinguserdata['user_id']) . '">
    
    <div class="row mb-3">
    <label for="department" class="col-sm-4 col-form-label">Department</label>
    <div class="col-sm-8">
    <input type="text" class="form-control" id="department" name="department" required>
    </div>
    </div>
    
    <div class="row mb-3">
    <label for="title" class="col-sm-4 col-form-label">Job Title</label>
    <div class="col-sm-8">
    <input type="text" class="form-control" id="title" name="title" required>
    </div>
    </div>
    
    <div class="row mb-3">
    <label for="hireDate" class="col-sm-4 col-form-label">Hire Date</label>
    <div class="col-sm-8">
    <input type="date" class="form-control" id="hireDate" name="hire_date" value="'.$formattedHireDate.'" required>
    </div>
    </div>
    
    <div class="row mb-3">
    <label for="status" class="col-sm-4 col-form-label">Employment Status</label>
    <div class="col-sm-8">
    <select class="form-select" id="status" name="employment_status">
    <option value="full-time">Full-Time</option>
    <option value="part-time">Part-Time</option>
    <option value="contract">Contract</option>
    </select>
    </div>
    </div>
    
    <div class="row mb-3">
        <label for="pay" class="col-sm-4 col-form-label">Pay</label>
        <div class="col-sm-8">
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control" id="pay" name="pay" required>
                <span class="input-group-text">per month</span>
            </div>
        </div>
    </div>
    
    <div class="row mb-3">
    <label for="corporateEmail" class="col-sm-4 col-form-label">Corporate Email</label>
    <div class="col-sm-8">
    <input type="email" class="form-control" id="corporateEmail" name="corporate_email" value="@birthday.gold" required>
    </div>
    </div>
    <div class="row mb-3">
    <label for="corporatePassword" class="col-sm-4 col-form-label">Corporate Password</label>
    <div class="col-sm-8">
    <input type="password" class="form-control" id="corporatePassword" name="corporate_password" value="" required>
    </div>
    </div>
    
    <div class="row mb-3">
    <label for="equipment" class="col-sm-4 col-form-label">Equipment Assignment</label>
    <div class="col-sm-8">
    <input type="text" class="form-control" id="equipment" name="equipment" required>
    </div>
    </div>
    
    <div class="row mb-3">
    <label for="manager" class="col-sm-4 col-form-label">Manager</label>
    <div class="col-sm-8">
    <input type="text" class="form-control" id="manager" name="manager" required>
    </div>
    </div>
    
    <div class="row mb-3">
    <label for="workLocation" class="col-sm-4 col-form-label">Work Location</label>
    <div class="col-sm-8">
    <input type="text" class="form-control" id="workLocation" name="work_location" required>
    </div>
    </div>
    
    <div class="row mb-3">
    <label for="notes" class="col-sm-4 col-form-label">Notes</label>
    <div class="col-sm-8">
    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
    </div>
    </div>
    
    <div class="text-end">
    <button type="submit" class="btn btn-primary">Submit</button>
    </div>
    </form>
    </div>
    </div>
    </div>
    </div>
    ';
    }
