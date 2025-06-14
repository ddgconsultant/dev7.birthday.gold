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

$additionalstyles.='
<style>
.navbar-vertical-label-wrapper {
    display: flex;
    align-items: center;
}

.navbar-vertical-label {
    white-space: nowrap;
    padding-right: 15px; /* Adjust this padding as needed */
    position: relative;
     color: var(--bs-primary); /* Use Bootstrap primary color */
    font-weight: bold; /* Make the label bold */
  
}

.navbar-vertical-divider {
    flex-grow: 1;
    border-top: 1px solid #000; /* Adjust color and thickness as needed */
    margin-top: -2px; /* Move the line up to align better with the text */
}
</style>
';


#-------------------------------------------------------------------------------
# BUILD LINKS
#-------------------------------------------------------------------------------
//-----------------------------------------------------------
// List of Account LINKS
    $accountlinks_display = 0;
    $account_links['account'] = [
        'title' => 'More Account Links',
        'links' => [
            ['text' => 'Login History', 'url' => '/myaccount/loginhistory', 'icon' => 'bi bi-clock-history'],
            ['text' => 'Trusted Devices', 'url' => '/myaccount/loginhistory?view=devices', 'icon' => 'bi bi-tablet'],
            ['text' => 'Notifications', 'url' => '/myaccount/manage-notifications', 'icon' => 'bi bi-bell'],
            [
                'text' => 'Profile Images',
                'url' => '/myaccount/profile-images',
                'icon' => 'bi bi-image',
                'tooltip_icon' => 'bi bi-info-circle',
                'tooltip_attributes' => 'data-bs-toggle="tooltip" data-bs-placement="top" title="Change your profile avatar and cover image'
            ],
            ['text' => 'Security Settings', 'url' => '/myaccount/security-settings', 'icon' => 'bi bi-shield-lock'],
            ['text' => 'BGInbox Settings', 'url' => '/myaccount/manage-mail', 'icon' => 'bi bi-envelope'],
        ]
    ];

    $device_result = $account->user_activedevices($current_user_data['user_id']);
    if (!empty($device_result)) {
        $account_links['account']['links'][] = ['text' => 'Trusted Devices', 'url' => '/myaccount/loginhistory?view=devices', 'icon' => 'bi bi-tablet'];
    }
    

//-----------------------------------------------------------
// List of STAFF LINKS
if ($account->isstaff()) {
    $accountlinks_display = 1;
    $account_links['staff'] = [
        'title' => 'Staff',
        'links' => [
            ['text' => 'Your Staff Profile', 'url' => '/staff/', 'icon' => 'bi bi-person-vcard'],
            ['text' => 'Human Resources', 'url' => '/hr/', 'icon' => 'bi bi-person-rolodex'],
            ['text' => 'Organization Chart', 'url' => '//whimsical.com/birthday-gold-organization-chart-DLzWNLXvT4wTb8VHD2Q7TH@6HYTAunKLgTVs8vpi5WC98mUWW3PbpNxuKAVmjk196shQQP', 'icon' => 'bi bi-diagram-3-fill', 'link_attributes' => 'target="link"'],
            ['text' => 'Corporate Holidays', 'url' => '/admin/redirect_corporateholidays', 'icon' => 'bi bi-calendar3', 'link_attributes' => 'target="link"'],
            ['text' => 'Roadmap', 'url' => '/roadmap', 'icon' => 'bi bi-signpost-split-fill', 'link_attributes' => 'target="link"'],
            /*
            <h6 class="dropdown-header">Staff</h6>
            <a class="dropdown-item" href="/admin/accessmanager/"><i class="bi bi-shield-lock"></i> Access Manager</a>
            <a class="dropdown-item" href="/staff/hr"><i class="bi bi-people-fill"></i> HR</a>
            <a class="dropdown-item" href="/staff/legal"><i class="bi bi-journal-text"></i> Legal</a>
            <a class="dropdown-item" href="/staff/cs"><i class="bi bi-headset"></i> Customer Service</a>
            <a class="dropdown-item" href="/staff/sales"><i class="bi bi-currency-dollar"></i> Sales</a>
            <a class="dropdown-item" href="/chat"><i class="bi bi-chat"></i> RocketChat</a>
            <a class="dropdown-item" href="/redirect_corporateholidays"><i class="bi bi-calendar"></i>Corporate Holidays</a>
            */
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
            ['custom' => '<div class="d-flex align-items-baseline"><i class="bi bi-hdd-network-fill me-1"></i><a href="//dev.birthday.gold/" class="me-2 text-decoration-none"><h6 class="fs-9 mb-0">DEV4</h6></a><span class="mx-2">|</span><a href="//dev5.birthday.gold/" class="ms-2 text-decoration-none"><h6 class="fs-9 mb-0">DEV5</h6></a></div>'],
['text' => 'Dashboard', 'url' => '/staff/ccdashboard', 'icon' => 'bi bi-speedometer'],
            ['text' => 'Companies', 'url' => '/staff/companylogos', 'icon' => 'bi bi-building-fill'],
            ['text' => 'TimeCards', 'url' => '/staff/timecards', 'icon' => 'bi bi-stopwatch'],
            ['text' => 'Unlock TimeCards', 'url' => '/staff/unlock_timecards', 'icon' => 'bi bi-unlock'],
            ['text' => 'User List', 'url' => '/admin/user-list', 'icon' => 'bi bi-person-lines-fill', 'link_attributes' => 'style="text-decoration: underline;"'],
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
            ['text' => 'Leantime', 'url' => '/admin/redirect-projectmanagement', 'icon' => 'bi bi-shield-lock', 'link_attributes' => 'target="rdwindow"'],
            ['text' => 'MetaBase', 'url' => '/admin/redirect-metabase', 'icon' => 'bi bi-shield-lock', 'link_attributes' => 'target="rdwindow"'],
            ['text' => 'Documize', 'url' => '/admin/redirect-docs', 'icon' => 'bi bi-shield-lock', 'link_attributes' => 'target="rdwindow"'],            
            ['text' => 'Blog Editor', 'url' => '/admin/blog_editor', 'icon' => 'bi bi-shield-lock', 'link_attributes' => 'target="rdwindow"'],                   
            ['text' => 'Server List', 'url' => '/staff/', 'icon' => '', 'link_attributes' => 'class="d-none"'],
            ['text' => 'Form Builder', 'url' => '/admin/formbuilder/', 'icon' => '', 'link_attributes' => 'class="d-none"'],
            ['text' => 'API Key Management', 'url' => 'https://dev.birthday.gold/api/keygen', 'icon' => 'bi bi-key',],

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
            ['text' => 'RocketChat', 'url' => '//chat.birthdaygold.cloud', 'icon' => 'bi bi-chat-dots', 'link_attributes' => 'target="link"']
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
<a target="link" href="/admin/redirect-enrollments" style="text-decoration: none;">
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
    'link_count' => 20,
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
            ['text' => 'Domain Names', 'url' => '//www.namecheap.com', 'icon' => 'bi bi-wrench-adjustable-circle', 'link_attributes' => 'target="link"'],
            ['text' => 'SSL Certificates', 'url' => '//www.cheapsslshop.com', 'icon' => 'bi bi-wrench-adjustable-circle', 'link_attributes' => 'target="link"'],
            ['text' => 'Service List', 'url' => '/admin/servicelist.txt', 'icon' => 'bi bi-wrench-adjustable-circle'],
            ['text' => 'System Links', 'url' => '/staff/systemlinks', 'icon' => 'bi bi-wrench-adjustable-circle'],
            ['text' => 'Uptime Status', 'url' => '/admin/redirect-uptime', 'icon' => 'bi bi-wrench-adjustable-circle', 'link_attributes' => 'target="link"'],
            ['text' => 'Monitoror', 'url' => 'http://monitoror.birthday.gold:8080', 'icon' => 'bi bi-wrench-adjustable-circle', 'link_attributes' => 'target="link"'],
            ['text' => 'PagerDuty', 'url' => '//ddgconsultant.pagerduty.com/incidents', 'icon' => 'bi bi-wrench-adjustable-circle', 'link_attributes' => 'target="link"'],
            ['text' => 'NetData', 'url' => 'http://june01.bday.gold:19999', 'icon' => 'bi bi-wrench-adjustable-circle', 'link_attributes' => 'target="link"'],
            ['text' => 'Hostinger', 'url' => '//hostinger.com/', 'icon' => 'bi bi-wrench-adjustable-circle', 'link_attributes' => 'target="link"'],
            ['text' => 'Cloudron', 'url' => '/admin/redirect-cloudron', 'icon' => 'bi bi-wrench-adjustable-circle', 'link_attributes' => 'target="rdwindow"'],
            ['text' => 'Create Vhost', 'url' => '/admin/create_vhost', 'icon' => 'bi bi-server', 'link_attributes' => 'target="vhostwindow"'],
            ['text' => 'HAProxy Dashboard', 'url' => 'http://april21.bday.gold:8080/', 'icon' => 'bi bi-diagram-3', 'link_attributes' => 'target="_blank"'],
            ['text' => 'Mouse Tracker', 'url' => '/admin/mouse-tracker', 'icon' => 'bi bi-mouse', 'link_attributes' => '']

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
            ['text' => 'API Swagger', 'url' => '//swagger.birthday.gold/', 'icon' => 'bi bi-wrench', 'link_attributes' => 'target="link"']
        ]
    ];
}

/*
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
$finallinks_max_links_per_column = 25;
$finallinks_column=1;
// Initialize the current column and link count trackers
$finallinks_current_column_links_count = 0;
$finallinks_total_links_count = 0;

if (isset($account_links) && is_array($account_links) && !empty($account_links)) {

 
echo '<div class="content-panel">
<h3 class="text-secondary fw-bold">Account Settings/Links</h3>';
echo '<div class="card-body row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 my-0 py-0">';


foreach ($account_links as $finallinks_section_name => $finallinks_section_data) {
    if ($finallinks_column==1 && $finallinks_current_column_links_count==0 ) {
        // first set of links
        echo '<div class="mt-3">';
    }

        // Count the number of links in this section
        $finallinks_section_links_count =  $finallinks_section_data['link_count'] ?? count($finallinks_section_data['links']);

        $finallinks_current_column_links_count=$finallinks_current_column_links_count+$finallinks_section_links_count;


    if ($finallinks_current_column_links_count > $finallinks_max_links_per_column) {
        echo '</div>'; // Close the current column
        echo '<div class="mt-3">';
        $finallinks_current_column_links_count = 0; // Reset column link count
    }


    // Check if the section has a "custom" key
    if (isset($finallinks_section_data['custom'])) {
        // If "custom" exists at the section level, output its content directly without wrapping
        echo $finallinks_section_data['custom'];
    } else {
        // Extract title and links for the section
        $finallinks_section_title = $finallinks_section_data['title'];
        $finallinks_section_links = $finallinks_section_data['links'];

    
    
        // Output the section title and links
    echo '
      <div id="'.(str_replace(' ', '', strtolower($finallinks_section_title)).'links').'" class="mb-5">
        <div class="navbar-vertical-label-wrapper  d-flex align-items-center" ' . $linklistpadding . '">
            <div class="navbar-vertical-label">' . $finallinks_section_title . '</div>
        <div class="col ps-0">
<hr class="mb-0 navbar-vertical-divider">
</div>
</div>
        ';

        
        foreach ($finallinks_section_links as $finallinks_link) {
            if (isset($finallinks_link['custom'])) {
                // If the link has a custom key, output the custom HTML directly
                echo $finallinks_link['custom'];
            } else {
                // Construct the anchor tag with link attributes if they exist
                $finallinks_attributes = isset($finallinks_link['link_attributes']) ? ' ' . $finallinks_link['link_attributes'] : '';
                echo '<div><a href="' . $finallinks_link['url'] . '"' . $finallinks_attributes . ' style="text-decoration: none;">';
                echo '<h6 class="fs-9 my-0"><i class="' . $finallinks_link['icon'] . ' me-2"></i>' . $finallinks_link['text'] . '</h6>';
                echo '</a></div>';
            }
        }

        echo '</div>'; // Close the section container

        // Update the current column link count and total link count
        $finallinks_current_column_links_count += $finallinks_section_links_count;
        $finallinks_total_links_count += $finallinks_section_links_count;
    }
}

// Close the final row if still open
if ($finallinks_current_column_links_count > 0) {
    echo '</div>'; // Close the final row
}

// If the total number of links exceeds a certain threshold, you can trigger bg-danger
if ($finallinks_total_links_count > 100) {
    echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 my-0 py-0 bg-danger"></div>';
}
echo '</div></div></div>';
}