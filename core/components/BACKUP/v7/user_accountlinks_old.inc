<?PHP

$linklistpadding = '';
if (!isset($accountlinkspresentation)) {
    $accountlinkspresentation = 'page';

    $linklistpadding = ' mt-5 mb-2';
}


if (!empty($accountlinkspresentation))
    echo '
<div class="card mb-3 overflow-hidden">
<div class="card-header">
<h5 class="mb-0">Account Settings/Links</h5>
</div>
<div class="card-body bg-body-tertiary">
';


echo '
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 my-0 py-0">
';



// List of STAFF LINKS
if ($account->isstaff() ) {
    echo '
<div id="stafflinks" class="mb-5">
<div class="col mb-5">
<div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">
<div class="col-auto navbar-vertical-label">Staff
</div>
<div class="col ps-0">
<hr class="mb-0 navbar-vertical-divider">
</div>
</div>

<div>
    <a href="/staff/" style="text-decoration: none;">
        <h6 class="fs-9 mb-2"><i class="bi bi-person-vcard me-1"></i>Your Staff Profile</h6>
    </a>
</div>

<div>
    <a href="/hr/" style="text-decoration: none;">
        <h6 class="fs-9 mb-2"><i class="bi bi-person-rolodex me-1"></i>Human Resources</h6>
    </a>
</div>

<div>
    <a target="link" href="//whimsical.com/birthday-gold-organization-chart-DLzWNLXvT4wTb8VHD2Q7TH@6HYTAunKLgTVs8vpi5WC98mUWW3PbpNxuKAVmjk196shQQP" style="text-decoration: none;">
        <h6 class="fs-9 mb-2"><i class="bi bi-diagram-3-fill me-2"></i>Organization Chart</h6>
    </a>
</div>

';
}
echo '
</div>
';



// List of SALES LINKS
if ($account->iscconsultant()) {
    echo '
<div id="saleslinks" class="col mb-5">
    <div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">
        <div class="col-auto navbar-vertical-label">Sales Team</div>
        <div class="col ps-0">
            <hr class="mb-0 navbar-vertical-divider">
        </div>
    </div>

    <div>
            <span><i class="bi bi-hdd-network-fill me-1"></i></span>
            <a href="//dev.birthday.gold/"><h6 class="fs-9 mb-2">DEV4</h6></a>
            <span class="mx-3"> | </span>
             <a href="//dev5.birthday.gold/"><h6 class="fs-9 mb-2">DEV5</h6></a>
        
    </div>

    <div>
        <a href="/staff/ccdashboard" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-speedometer me-1"></i>Dashboard</h6>
        </a>
    </div>

    <div>
        <a href="/staff/companylogos" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-building-fill me-1"></i>Companies</h6>
        </a>
    </div>

    <div>
        <a href="/staff/timecards" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-stopwatch me-1"></i>TimeCards</h6>
        </a>
    </div>

    <div>
        <a href="/staff/unlock_timecards" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-unlock me-1"></i>Unlock TimeCards</h6>
        </a>
    </div>

    <div>
        <a href="/admin/user-list" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-person-lines-fill me-1"></i><span style="text-decoration: underline;">User List</span></h6>
        </a>
    </div>

    <div>
        <a href="/hr/form_onboarding" style="text-decoration: none;">
            <h6 class="fs-9 mb-2">Onboarding Forms</h6>
        </a>
    </div>

</div></div>';
}



// List of ADMIN LINKS
if ($account->isadmin()) {
    echo '
<div id="adminlinks" class="col mb-5">
    <div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">
        <div class="col-auto navbar-vertical-label">Admin Backoffice</div>
        <div class="col ps-0">
            <hr class="mb-0 navbar-vertical-divider">
        </div>
    </div>

    <div>
        <a href="/admin/accessmanager/" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-shield-lock me-1"></i>Access Manager</h6>
        </a>
    </div>

        <div>
        <a href="/admin/redirect-projectmanagement" target="_rdwindow" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-shield-lock me-1"></i>Leantime</h6>
        </a>
    </div>

            <div>
        <a href="/admin/redirect-metabase" target="_rdwindow" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-shield-lock me-1"></i>MetaBase</h6>
        </a>
    </div>

    
            <div>
        <a href="/admin/redirect-docs" target="_rdwindow" style="text-decoration: none;">
            <h6 class="fs-9 mb-2"><i class="bi bi-shield-lock me-1"></i>Documize</h6>
        </a>
    </div>


 
    <div>
        <a href="/staff/" style="text-decoration: none;">
            <h6 class="fs-9 mb-2 d-none">Server List</h6>
        </a>
    </div>

    <div>
        <a href="/admin/formbuilder/" style="text-decoration: none;">
            <h6 class="fs-9 mb-2 d-none">Form Builder</h6>
        </a>
    </div>

</div>';
}


// List of ENROLLMENT LINKS
if ($account->isadmin()) {
    $rowCount = $app->admin_getenrollments();

    echo '
<div id="enrollmentlinks" class="col mb-5">
    <div class="col mb-5">
        <div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">
            <div class="col-auto navbar-vertical-label">Enrollment Management</div>
            <div class="col ps-0">
                <hr class="mb-0 navbar-vertical-divider">
            </div>
        </div>

        <div>
            <a target="link" href="/admin/users" style="text-decoration: none;">
                <h6 class="fs-9 mb-2">
                    ENROLLMENTS
                    <span class="badge rounded-pill bg-danger ms-1" style="vertical-align: middle; margin-top: -4px !important;" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $qik->plural2($rowCount, 'Enrollment') . ' to process">' . $rowCount . '</span>
                </h6>
            </a>
        </div>

</div>';


// List of BRAND MANAGEMENT LINKS
echo '
<div id="brandlinks" class="mb-5">
    <div class="col mb-5">
        <div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">
            <div class="col-auto navbar-vertical-label">Brand Management</div>
            <div class="col ps-0">
                <hr class="mb-0 navbar-vertical-divider">
            </div>
        </div>

        <div>
            <a href="/admin/brands" style="text-decoration: none;">
                <h6 class="fs-9 mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Brand Editor</h6>
            </a>
        </div>

        <div>
            <a href="' . $dir['bge_webA'] . '/companysetup.php?filter=finalized" style="text-decoration: none;">
                <h6 class="fs-9 mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Company Setup</h6>
            </a>
        </div>

        <div>
            <a href="/admin_actions/manual_rewards" style="text-decoration: none;">
                <h6 class="fs-9 mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Reward Details</h6>
            </a>
        </div>

        <div>
            <a href="/admin_actions/manual_policies" style="text-decoration: none;">
                <h6 class="fs-9 mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Reward Policies</h6>
            </a>
        </div>

        <div>
            <a href="/admin_actions/rewardprocessors" style="text-decoration: none;">
                <h6 class="fs-9 mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Reward Processor</h6>
            </a>
        </div>

    </div>
</div>
</div>';

}

// List of SYSTEM ADMIN LINKS
if ($account->isstaff('sysops') || $account->isadmin()) {
    echo '
<div id="systemadminlinks" class="col mb-5">
<div class="col mb-5">
<div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">
<div class="col-auto navbar-vertical-label">System Administration
</div>
<div class="col ps-0">
<hr class="mb-0 navbar-vertical-divider">
</div>
</div>

<div>
<a href="/admin/serverlayout" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
Server Layout
</h6>
</a>
</div>

<div>
<a target="link" href="//www.namecheap.com" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
Domain Names
</h6>
</a>
</div>

<div>
<a target="link" href="//www.cheapsslshop.com" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
SSL Certificates
</h6>
</a>
</div>

<div>
<a href="/admin/servicelist.txt" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
Service List
</h6>
</a>
</div>

<div>
<a href="/staff/systemlinks" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
System Links
</h6>
</a>
</div>

<div>
<a target="link" href="/admin/redirect-uptime" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
Uptime Status
</h6>
</a>
</div>

<div>
<a target="link" href="//monitoror.birthday.gold:8080" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
Monitoror
</h6>
</a>
</div>

<div>
<a target="link" href="//ddgconsultant.pagerduty.com/incidents" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
PagerDuty
</h6>
</a>
</div>

<div>
<a target="link" href="http://june01.bday.gold:19999" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
NetData
</h6>
</a>
</div>

<div>
<a target="link" href="//hostinger.com/" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
Hostinger
</h6>
</a>
</div>



<div>
<a href="/admin/redirect-cloudron" target="_rdwindow"  style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench-adjustable-circle me-1"></i>
Cloudron
</h6>
</a>
</div>


</div>
</div>
</div>';
}


if (!empty($accountlinkspresentation)) {
    if ($account->isstaff() || $account->isadmin()) {
        echo '
<div class="col">
<div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">
<div class="col-auto navbar-vertical-label">HelpDesk
</div>
<div class="col ps-0">
<hr class="mb-0 navbar-vertical-divider">
</div>
</div>
<div>
<a target="link" href="//chat.birthdaygold.cloud" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-chat-dots me-1"></i>
RocketChat
</h6>
</a>
</div>
</div>';
    }

    if ($account->isstaff('techops') || $account->isadmin()) {
        echo '
<div class="col">
<div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">
<div class="col-auto navbar-vertical-label">Tech Operations
</div>
<div class="col ps-0">
<hr class="mb-0 navbar-vertical-divider">
</div>
</div>
<div>
<a target="link" href="//swagger.birthday.gold/" style="text-decoration: none;">
<h6 class="fs-9 mb-2">
<i class="bi bi-wrench me-1"></i>
API Swagger
</h6>
</a>
</div>
</div>';
    }

    if ($account->isdeveloper(20)) {
        echo '
<div class="col">
<h6 class="fw-bold">Who can see your profile?<span class="fs-11 ms-1 text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Only the group of selected people can see your profile"><span class="fas fa-question-circle"></span></span></h6>
<div class="ps-2">
<div class="form-check mb-0 lh-1">
<input class="form-check-input" type="radio" value="" id="everyone" name="view-settings" />
<label class="form-check-label mb-0" for="everyone">Everyone
</label>
</div>
<div class="form-check mb-0 lh-1">
<input class="form-check-input" type="radio" value="" id="my-followers" checked="checked" name="view-settings" />
<label class="form-check-label mb-0" for="my-followers">My followers
</label>
</div>
<div class="form-check mb-0 lh-1">
<input class="form-check-input" type="radio" value="" id="only-me" name="view-settings" />
<label class="form-check-label mb-0" for="only-me">Only me
</label>
</div>
</div>
<h6 class="mt-2 fw-bold">Who can tag you ?<span class="fs-11 ms-1 text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Only the group of selected people can tag you"><span class="fas fa-question-circle"></span></span></h6>
<div class="ps-2">
<div class="form-check mb-0 lh-1">
<input class="form-check-input" type="radio" value="" id="tag-everyone" name="tag-settings" />
<label class="form-check-label mb-0" for="tag-everyone">Everyone
</label>
</div>
<div class="form-check mb-0 lh-1">
<input class="form-check-input" type="radio" value="" id="group-members" checked="checked" name="tag-settings" />
<label class="form-check-label mb-0" for="group-members">Group Members
</label>
</div>
</div>
<div class="border-dashed-bottom my-3"></div>
<div class="form-check mb-0 lh-1">
<input class="form-check-input" type="checkbox" id="userSettings1" checked="checked" />
<label class="form-check-label mb-0" for="userSettings1">Allow users to show your followers
</label>
</div>
<div class="form-check mb-0 lh-1">
<input class="form-check-input" type="checkbox" id="userSettings2" checked="checked" />
<label class="form-check-label mb-0" for="userSettings2">Allow users to show your email
</label>
</div>
<div class="form-check mb-0 lh-1">
<input class="form-check-input" type="checkbox" id="userSettings3" />
<label class="form-check-label mb-0" for="userSettings3">Allow users to show your experiences
</label>
</div>
<div class="border-bottom border-dashed my-3"></div>
<div class="form-check form-switch mb-0 lh-1">
<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault" checked="checked" />
<label class="form-check-label mb-0" for="flexSwitchCheckDefault">Make your phone number visible
</label>
</div>
<div class="form-check form-switch mb-0 lh-1">
<input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" />
<label class="form-check-label mb-0" for="flexSwitchCheckChecked">Allow user to follow you
</label>
</div>
</div>';
    }

    echo '
</div>
</div>
<div class="card-footer py-2 text-center"><a class="btn btn-link btn-sm px-0 fw-medium" href="/myaccount/account">All Settings<span class="fas fa-chevron-right ms-1 fs-11"></span></a></div>
</div>
<div class="card mb-3 d-none">
<div class="card-header">
<h5 class="mb-0">Billing Setting</h5>
</div>
<div class="card-body bg-body-tertiary">
<h5>Plan</h5>
<p class="fs-9"><strong>Developer</strong> - Unlimited private repositories</p><a class="btn btn-falcon-default btn-sm" href="#!">Update Plan</a>
</div>
<div class="card-body bg-body-tertiary border-top">
<h5>Payment</h5>
<p class="fs-9">You have not added any payment.</p><a class="btn btn-falcon-default btn-sm" href="#!">Add Payment </a>
</div>
</div>
';

    echo '
<div class="card mb-3 d-none">
<div class="card-header">
<h5 class="mb-0">Change Password</h5>
</div>
<div class="card-body bg-body-tertiary">
<form action="/changepassword" method="post">              
' . $display->inputcsrf_token() . '
<input name="returnto" type="hidden" value="/changepassword">
<div class="mb-3">
<label class="form-label" for="inputcurrentPassword">Current Password</label>
<input class="form-control"name="inputcurrentPassword" id="inputcurrentPassword" type="password" />
</div>
<div class="mb-3">
<label class="form-label" for="inputnewPassword">New Password</label>
<input class="form-control" name="inputnewPassword" id="inputnewPassword" type="password" />
</div>
<div class="mb-3">
<label class="form-label" for="inputconfirmPassword">Confirm Password</label>
<input class="form-control" name="inputconfirmPassword" id="inputconfirmPassword" type="password" />
</div>
<button class="btn btn-primary d-block w-100" type="submit">Change Password </button>
</form>
</div>
</div>
';

    echo '
<div class="card d-none">
    <div class="card-header">
        <h5 class="mb-0">Danger Zone</h5>
    </div>
    <div class="card-body bg-body-tertiary">
        <h5 class="fs-9">Transfer Ownership</h5>
        <p class="fs-10">Transfer this account to another user or to an organization where you have the ability to create repositories.</p><a class="btn btn-falcon-warning d-block" href="#!">Transfer</a>
        <div class="border-bottom border-dashed my-4"></div>
        <h5 class="fs-9">Delete this account</h5>
        <p class="fs-10">Once you delete an account, there is no going back. Please be certain.</p><a class="btn btn-falcon-danger d-block" href="#!">Deactivate Account</a>
    </div>
</div>
</div>
</div>
';
}
