<?PHP

#-------------------------------------------------------------------------------
# MANAGE DISPLAY TYPE
#-------------------------------------------------------------------------------
$linksoutput='';
$linklistpadding = '';
if (!isset($accountlinkspresentation)) {
    $accountlinkspresentation = 'page';
    $linklistpadding = ' mt-5 mb-2';
}
$accountlinks_display = false;
$accountlinks_output = '';






#-------------------------------------------------------------------------------
# BUILD LINKS
#-------------------------------------------------------------------------------
//-----------------------------------------------------------
// List of STAFF LINKS
if ($account->isstaff()) {
    $accountlinks_display = 1;
    $account_links['staff'] = [
        'title' => 'Staff',
        'links' => [
            ['text' => 'Your Staff Profile', 'url' => '/staff/', 'icon' => 'bi bi-person-vcard'],
            ['text' => 'Human Resources', 'url' => '/hr/', 'icon' => 'bi bi-person-rolodex'],
            ['text' => 'Organization Chart', 'url' => '//whimsical.com/birthday-gold-organization-chart-DLzWNLXvT4wTb8VHD2Q7TH@6HYTAunKLgTVs8vpi5WC98mUWW3PbpNxuKAVmjk196shQQP', 'icon' => 'bi bi-diagram-3-fill', 'custom' => 'target=link']
        ]
    ];
}


//-----------------------------------------------------------
// List of SALES LINKS
if ($account->iscconsultant()) {
    $accountlinks_display = 2;
    $account_links['sales'] = [
        'title' => 'Sales Team',
        'links' => [
            ['custom' => '<span><i class="bi bi-hdd-network-fill me-1"></i></span><a href="//dev.birthday.gold/"><h6 class="fs-9 mb-2">DEV4</h6></a><span class="mx-3"> | </span><a href="//dev5.birthday.gold/"><h6 class="fs-9 mb-2">DEV5</h6></a>'],
            ['text' => 'Dashboard', 'url' => '/staff/ccdashboard', 'icon' => 'bi bi-speedometer'],
            ['text' => 'Companies', 'url' => '/staff/companylogos', 'icon' => 'bi bi-building-fill'],
            ['text' => 'TimeCards', 'url' => '/staff/timecards', 'icon' => 'bi bi-stopwatch'],
            ['text' => 'Unlock TimeCards', 'url' => '/staff/unlock_timecards', 'icon' => 'bi bi-unlock'],
            ['text' => 'User List', 'url' => '/admin/user-list', 'icon' => 'bi bi-person-lines-fill', 'custom' => 'style="text-decoration: underline;"'],
            ['text' => 'Onboarding Forms', 'url' => '/hr/form_onboarding', 'icon' => 'bi bi-file-earmark-text']
        ]
    ];
}


//-----------------------------------------------------------
// List of ADMIN LINKS
if ($account->isadmin()) {
    $accountlinks_display = 3;
    $account_links['admin'] = [
        'title' => 'Admin Backoffice',
        'links' => [
            ['text' => 'Access Manager', 'url' => '/admin/accessmanager/', 'icon' => 'bi bi-shield-lock'],
            ['text' => 'Leantime', 'url' => '/admin/redirect-projectmanagement', 'icon' => 'bi bi-shield-lock', 'custom' => 'target="rdwindow"'],
            ['text' => 'MetaBase', 'url' => '/admin/redirect-metabase', 'icon' => 'bi bi-shield-lock', 'custom' => 'target="rdwindow"'],
            ['text' => 'Documize', 'url' => '/admin/redirect-docs', 'icon' => 'bi bi-shield-lock', 'custom' => 'target="rdwindow"'],
            ['text' => 'Server List', 'url' => '/staff/', 'icon' => '', 'custom' => 'class="d-none"'],
            ['text' => 'Form Builder', 'url' => '/admin/formbuilder/', 'icon' => '', 'custom' => 'class="d-none"']
        ]
    ];
}


//-----------------------------------------------------------
// List of HELP LINKS
if ($account->isstaff() || $account->isadmin()) {
    $accountlinks_display = 6;
    $account_links['help'] = [
        'title' => 'HelpDesk',
        'links' => [
            ['text' => 'RocketChat', 'url' => '//chat.birthdaygold.cloud', 'icon' => 'bi bi-chat-dots', 'custom' => 'target="link"']
        ]
    ];
}


//-----------------------------------------------------------
// List of ENROLLMENT LINKS
if ($account->isadmin()) {
    $accountlinks_display = 4;
    $rowCount = $app->admin_getenrollments();
    $account_links['enrollment'] = [
        'title' => 'Enrollment Management',
        'links' => [
            ['custom' => '<div>
<a target="link" href="/admin/bgreb_v3/enrollment-list" style="text-decoration: none;">
<h6 class="fs-9 mb-2">ENROLLMENTS
<span class="badge rounded-pill bg-danger ms-1" style="vertical-align: middle; margin-top: -4px !important;" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $qik->plural2($rowCount, 'Enrollment') . ' to process">' . $rowCount . '</span>
</h6></a></div>']
        ]
    ];
}


//-----------------------------------------------------------
// List of BRAND MANAGEMENT LINKS
if ($account->isadmin()) {
$account_links['brand'] = [
    'title' => 'Brand Management',
    'links' => [
        ['text' => 'Brand Editor', 'url' => '/admin/brands', 'icon' => 'bi bi-gear-wide-connected'],
        ['text' => 'Company Setup', 'url' => $dir['bge_webA'] . '/companysetup.php?filter=finalized', 'icon' => 'bi bi-gear-wide-connected'],
        ['text' => 'Reward Details', 'url' => '/admin_actions/manual_rewards', 'icon' => 'bi bi-gear-wide-connected'],
        ['text' => 'Reward Policies', 'url' => '/admin_actions/manual_policies', 'icon' => 'bi bi-gear-wide-connected'],
        ['text' => 'Reward Processor', 'url' => '/admin_actions/rewardprocessors', 'icon' => 'bi bi-gear-wide-connected']
    ]
];
}


//-----------------------------------------------------------
// List of SYSTEM ADMIN LINKS
if ($account->isstaff('sysops') || $account->isadmin()) {
    $accountlinks_display = 5;
    $account_links['system'] = [
        'title' => 'System Administration',
        'links' => [
            ['text' => 'Server Layout', 'url' => '/admin/serverlayout', 'icon' => 'bi bi-wrench-adjustable-circle'],
            ['text' => 'Domain Names', 'url' => '//www.namecheap.com', 'icon' => 'bi bi-wrench-adjustable-circle', 'custom' => 'target="link"'],
            ['text' => 'SSL Certificates', 'url' => '//www.cheapsslshop.com', 'icon' => 'bi bi-wrench-adjustable-circle', 'custom' => 'target="link"'],
            ['text' => 'Service List', 'url' => '/admin/servicelist.txt', 'icon' => 'bi bi-wrench-adjustable-circle'],
            ['text' => 'System Links', 'url' => '/staff/systemlinks', 'icon' => 'bi bi-wrench-adjustable-circle'],
            ['text' => 'Uptime Status', 'url' => '/admin/redirect-uptime', 'icon' => 'bi bi-wrench-adjustable-circle', 'custom' => 'target="link"'],
            ['text' => 'Monitoror', 'url' => '//monitoror.birthday.gold:8080', 'icon' => 'bi bi-wrench-adjustable-circle', 'custom' => 'target="link"'],
            ['text' => 'PagerDuty', 'url' => '//ddgconsultant.pagerduty.com/incidents', 'icon' => 'bi bi-wrench-adjustable-circle', 'custom' => 'target="link"'],
            ['text' => 'NetData', 'url' => 'http://june01.bday.gold:19999', 'icon' => 'bi bi-wrench-adjustable-circle', 'custom' => 'target="link"'],
            ['text' => 'Hostinger', 'url' => '//hostinger.com/', 'icon' => 'bi bi-wrench-adjustable-circle', 'custom' => 'target="link"'],
            ['text' => 'Cloudron', 'url' => '/admin/redirect-cloudron', 'icon' => 'bi bi-wrench-adjustable-circle', 'custom' => 'target="rdwindow"']
        ]
    ];
}


//-----------------------------------------------------------
//-----------------------------------------------------------
// PAGE VIEW MODE LINKS
if (!empty($accountlinkspresentation)) {
    /*
    if ($account->isstaff() || $account->isadmin()) {
        $accountlinks_display = 6;
        $accountlinks_output .= '
<div class="col"><div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '"><div class="col-auto navbar-vertical-label">HelpDesk
</div><div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div></div><div><a target="link" href="//chat.birthdaygold.cloud" style="text-decoration: none;"><h6 class="fs-9 mb-2"><i class="bi bi-chat-dots me-1"></i>
RocketChat</h6></a></div></div>';
    }
*/

//-----------------------------------------------------------
// List of TECH OPERATIONS LINKS
if ($account->isstaff('techops') || $account->isadmin()) {
    $accountlinks_display = 7;
    $account_links['tech'] = [
        'title' => 'Tech Operations',
        'links' => [
            ['text' => 'API Swagger', 'url' => '//swagger.birthday.gold/', 'icon' => 'bi bi-wrench', 'custom' => 'target="link"']
        ]
    ];
}

/*

    //-----------------------------------------------------------
 //-----------------------------------------------------------
// Developer Settings Links
if ($account->isdeveloper(20)) {
    $accountlinks_display = 8;
    $account_links['all'] = [
        'title' => 'Profile Visibility Settings',
        'content' => '
            <div class="col">
                <h6 class="fw-bold">Who can see your profile?<span class="fs-11 ms-1 text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Only the group of selected people can see your profile"><span class="fas fa-question-circle"></span></span></h6>
                <div class="ps-2">
                    <div class="form-check mb-0 lh-1"><input class="form-check-input" type="radio" value="" id="everyone" name="view-settings" /><label class="form-check-label mb-0" for="everyone">Everyone</label></div>
                    <div class="form-check mb-0 lh-1"><input class="form-check-input" type="radio" value="" id="my-followers" checked="checked" name="view-settings" /><label class="form-check-label mb-0" for="my-followers">My followers</label></div>
                    <div class="form-check mb-0 lh-1"><input class="form-check-input" type="radio" value="" id="only-me" name="view-settings" /><label class="form-check-label mb-0" for="only-me">Only me</label></div>
                </div>
                <h6 class="mt-2 fw-bold">Who can tag you ?<span class="fs-11 ms-1 text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Only the group of selected people can tag you"><span class="fas fa-question-circle"></span></span></h6>
                <div class="ps-2">
                    <div class="form-check mb-0 lh-1"><input class="form-check-input" type="radio" value="" id="tag-everyone" name="tag-settings" /><label class="form-check-label mb-0" for="tag-everyone">Everyone</label></div>
                    <div class="form-check mb-0 lh-1"><input class="form-check-input" type="radio" value="" id="group-members" checked="checked" name="tag-settings" /><label class="form-check-label mb-0" for="group-members">Group Members</label></div>
                </div>
                <div class="border-dashed-bottom my-3"></div>
                <div class="form-check mb-0 lh-1"><input class="form-check-input" type="checkbox" id="userSettings1" checked="checked" /><label class="form-check-label mb-0" for="userSettings1">Allow users to show your followers</label></div>
                <div class="form-check mb-0 lh-1"><input class="form-check-input" type="checkbox" id="userSettings2" checked="checked" /><label class="form-check-label mb-0" for="userSettings2">Allow users to show your email</label></div>
                <div class="form-check mb-0 lh-1"><input class="form-check-input" type="checkbox" id="userSettings3" /><label class="form-check-label mb-0" for="userSettings3">Allow users to show your experiences</label></div>
                <div class="border-bottom border-dashed my-3"></div>
                <div class="form-check form-switch mb-0 lh-1"><input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault" checked="checked" /><label class="form-check-label mb-0" for="flexSwitchCheckDefault">Make your phone number visible</label></div>
                <div class="form-check form-switch mb-0 lh-1"><input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" /><label class="form-check-label mb-0" for="flexSwitchCheckChecked">Allow user to follow you</label></div>
            </div>'
    ];
}



    //-----------------------------------------------------------
    $account_links['billing']= '
</div></div><div class="card-footer py-2 text-center">
<a class="btn btn-link btn-sm px-0 fw-medium" href="/myaccount/account">All Settings<span class="fas fa-chevron-right ms-1 fs-11"></span></a></div></div><div class="card mb-3 d-none"><div class="card-header"><h5 class="mb-0">Billing Setting</h5></div><div class="card-body bg-body-tertiary"><h5>Plan</h5><p class="fs-9"><strong>Developer</strong> - Unlimited private repositories</p>
<a class="btn btn-falcon-default btn-sm" href="#!">Update Plan</a></div><div class="card-body bg-body-tertiary border-top"><h5>Payment</h5><p class="fs-9">You have not added any payment.</p>
<a class="btn btn-falcon-default btn-sm" href="#!">Add Payment </a></div>

</div>
';

     $account_links['password']='
<div class="card mb-3 d-none"><div class="card-header"><h5 class="mb-0">Change Password</h5></div><div class="card-body bg-body-tertiary"><form action="/changepassword" method="post">              
' . $display->inputcsrf_token() . '
<input name="returnto" type="hidden" value="/changepassword"><div class="mb-3"><label class="form-label" for="inputcurrentPassword">Current Password</label><input class="form-control"name="inputcurrentPassword" id="inputcurrentPassword" type="password" /></div><div class="mb-3"><label class="form-label" for="inputnewPassword">New Password</label><input class="form-control" name="inputnewPassword" id="inputnewPassword" type="password" /></div><div class="mb-3"><label class="form-label" for="inputconfirmPassword">Confirm Password</label><input class="form-control" name="inputconfirmPassword" id="inputconfirmPassword" type="password" /></div><button class="btn btn-primary d-block w-100" type="submit">Change Password </button></form></div></div>
';

$account_links['transfer']= '
<div class="card d-none"><div class="card-header"><h5 class="mb-0">Danger Zone</h5></div><div class="card-body bg-body-tertiary"><h5 class="fs-9">Transfer Ownership</h5><p class="fs-10">Transfer this account to another user or to an organization where you have the ability to create repositories.</p>
<a class="btn btn-falcon-warning d-block" href="#!">Transfer</a>
<div class="border-bottom border-dashed my-4"></div><h5 class="fs-9">Delete this account</h5><p class="fs-10">Once you delete an account, there is no going back. Please be certain.</p>
<a class="btn btn-falcon-danger d-block" href="#!">Deactivate Account</a></div></div></div>

</div>
';
}

*/

if (!empty($accountlinkspresentation)) {
   $linksoutput.= '
<div class="card mb-3 overflow-hidden"><div class="card-header"><h5 class="mb-0">Account Settings/Links</h5></div><div class="card-body bg-body-tertiary">

</div>';
}
}




#-------------------------------------------------------------------------------
# DISPLAY LINKS
#-------------------------------------------------------------------------------
$linklistpadding = '';
if (!isset($accountlinkspresentation)) {
    $accountlinkspresentation = 'page';

    $linklistpadding = ' mt-5 mb-2';
}
$accountlinks_display = false;
$accountlinks_output = '';

if (!empty($accountlinkspresentation)) {
    $accountlinks_output .= '
<div class="card mb-3 overflow-hidden">
<div class="card-header">
<h5 class="mb-0">Account Settings/Links</h5>
</div>
<div class="card-body bg-body-tertiary">
';
}





#-------------------------------------------------------------------------------
# DISPLAY card links
#-------------------------------------------------------------------------------
// Maximum links per column before a new column starts
// Maximum links per column before a new column starts
$max_links_per_column = 15;

// Initialize the current column and link count trackers
$current_column = 1;
$current_column_links_count = 0;

// Start the first column
echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 my-0 py-0">';

foreach ($account_links as $section_name => $section_data) {
    // Check if the section has a "custom" key
    if (isset($section_data['custom'])) {
        // If "custom" exists at the section level, output its content directly without wrapping
        echo $section_data['custom'];
    } else {
        // Extract title and links for the section
        $section_title = $section_data['title'];
        $section_links = $section_data['links'];

        // Count the number of links in this section
        $section_links_count = count($section_links);

        // If adding this section exceeds the max links per column, close the current column and start a new one
        if ($current_column_links_count + $section_links_count > $max_links_per_column) {
            echo "</div>"; // Close the current column
            $current_column++;
            $current_column_links_count = 0;
            echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 my-0 py-0">'; // Start a new column
        }

        // Output the section title and links
        echo '<div class="col mb-5">';
        echo '<div class="row navbar-vertical-label-wrapper ' . $linklistpadding . '">';
        echo '<div class="col-auto navbar-vertical-label">' . $section_title . '</div>';
        echo '<div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>';
        echo '</div>';

        foreach ($section_links as $link) {
            if (isset($link['custom'])) {
                // If the link has a custom key, output the custom HTML directly
                echo $link['custom'];
            } else {
                // Otherwise, construct the HTML for the link
                echo '<div><a href="' . $link['url'] . '" style="text-decoration: none;">';
                echo '<h6 class="fs-9 mb-2"><i class="' . $link['icon'] . ' me-1"></i>' . $link['text'] . '</h6>';
                echo '</a></div>';
            }
        }

        echo '</div>'; // Close the section container

        // Update the current column link count
        $current_column_links_count += $section_links_count;
    }
}

// Close the final column if still open
if ($current_column_links_count > 0) {
    echo "</div>"; // Close the final column
}
