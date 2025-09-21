<?php

// DATA IS OBTAINED FROM: admin/user_components/user-details_modals.inc
echo '
<!-- Administrative User Details Cards -->
<div class="row g-4">
    <!-- Account Information Card -->
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Account Information</h5>
                <i class="bi bi-person-vcard text-primary"></i>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-hash me-2"></i>ID</span>
                        <span>'.htmlspecialchars($workinguserdata['user_id']).'</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-check me-2"></i>Created</span>
                        <span>'.htmlspecialchars($workinguserdata['create_dt']).'</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-envelope me-2"></i>Email</span>
                        <a href="mailto:'.htmlspecialchars($workinguserdata['email']).'">'.htmlspecialchars($workinguserdata['email']).'</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person me-2"></i>Username</span>
                        <span>'.htmlspecialchars($workinguserdata['username']).'</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-shield me-2"></i>Admin</span>
                        <span class="badge bg-'.($workinguserdata['account_admin'] == 'Y' ? 'success' : 'secondary').'">'.
                            htmlspecialchars($workinguserdata['account_admin']).'</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-circle me-2"></i>Status</span>
                       <div class="text-end">  <span class="badge bg-'.($workinguserdata['status'] == 'active' ? 'success' : 'warning').'">'.
                            htmlspecialchars($workinguserdata['status']).'</span>

';

if ($workinguserdata['status']=='pending') {
                            $validatedata['rawdata'] = $workinguserdata['email'];
                            $validatedata['user_id'] = $workinguserdata['user_id'];
                            $validatedata['sendcount'] = 0;
                          $validatedata['action'] = 'getlatest';
                          
                            $validationcodes = $app->getvalidationcodes($validatedata);
                            if ($validationcodes['mini'])
                            echo '<br><span class="small text-muted">Validation Code: 
                            <span 
                                style="cursor: pointer; text-decoration: underline;" 
                                onclick="navigator.clipboard.writeText(\''.htmlspecialchars($validationcodes['mini']??'').'\');">
                                '.htmlspecialchars($validationcodes['mini']??'').'
                            </span>
                        </span>';
}
echo '
</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
';

// PERSONAL DETAILS CARD ========================================
 $profile_images_count = $database->prepare("SELECT COUNT(*) as count FROM bg_user_attributes 
    WHERE user_id = ? AND type = 'profile_image' AND status = 'active'");
$profile_images_count->execute([$workinguserdata['user_id']]);
$pic_count = $profile_images_count->fetch(PDO::FETCH_ASSOC)['count'];

// Get cover banners count
$cover_banners_count = $database->prepare("SELECT COUNT(*) as count FROM bg_user_attributes 
    WHERE user_id = ? AND type = 'profile_image' AND name = 'account_cover' AND status = 'active'");
$cover_banners_count->execute([$workinguserdata['user_id']]);
$cover_count = $cover_banners_count->fetch(PDO::FETCH_ASSOC)['count'];

// Check 2FA status
$two_fa_check = $database->prepare("SELECT COUNT(*) as count FROM bg_user_attributes 
    WHERE user_id = ? AND type = 'security' AND name = '2fa' AND status = 'active'");
$two_fa_check->execute([$workinguserdata['user_id']]);
$has_2fa = $two_fa_check->fetch(PDO::FETCH_ASSOC)['count'] > 0;

// Check security questions
$security_questions_check = $database->prepare("SELECT COUNT(*) as count FROM bg_user_attributes 
    WHERE user_id = ? AND type = 'security' AND name = 'security_questions' AND status = 'active'");
$security_questions_check->execute([$workinguserdata['user_id']]);
$has_security_questions = $security_questions_check->fetch(PDO::FETCH_ASSOC)['count'] > 0;

echo '
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Personal Details</h5>
        <i class="bi bi-person text-success"></i>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush mx-0 px-0">
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-day me-2"></i>Birthday</span>
                    <div class="text-end">
                        <div>'.htmlspecialchars($workinguserdata['birthdate']).'</div>
<div><small class="text-muted">'.htmlspecialchars($qik->plural2($till['days'], 'day')).' away</small></div>
<div><small class="text-muted">'.htmlspecialchars($alive['years']).' years old</small></div>
                    </div>
                </div>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="bi bi-star me-2"></i>Astro Sign</span>
                <a href="'.htmlspecialchars($astrosign_horoscopelink).'" target="link">'.
                    htmlspecialchars($user_astrosigndetails['name']).'</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="bi bi-geo-alt me-2"></i>Location</span>
                <span>'.htmlspecialchars($location).'</span>
            </li>
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-images me-2"></i>Profile Media</span>
                    <div>
                        <span class="badge bg-primary me-2">'.$pic_count.' Photos</span>
                        <span class="badge bg-info">'.$cover_count.' Covers</span>
                    </div>
                </div>
            </li>
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-shield-lock me-2"></i>Security</span>
                    <div>
                        <span class="badge '.($has_2fa ? 'bg-success' : 'text-bg-secondary opacity-50').' me-2">
                            <i class="bi bi-'.($has_2fa ? 'check' : 'x').'-circle me-1"></i>2FA
                        </span>
                   <span class="badge '.($has_security_questions ? 'bg-success' : 'text-bg-secondary opacity-50').'">
    <i class="bi bi-'.($has_security_questions ? 'check' : 'x').'-circle me-1"></i>Security Q&A
</span>
                    </div>
                </div>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="bi bi-telephone me-2"></i>Phone</span>
                <span>'.htmlspecialchars($workinguserdata['phone_number'] ?? 'Not set').'</span>
            </li>
        </ul>
    </div>
</div></div>';


// ENROLLMENT STATUS CARD ========================================
echo '
    <!-- Enrollment Status Card -->
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Enrollment Status</h5>
                <i class="bi bi-card-checklist text-info"></i>
            </div>
            <div class="card-body">

 <div class="row">
                <div class="">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Current Plan:</span>
                        <div><span class="fw-bold">'.htmlspecialchars($user_planddetails['displayname'] ?? 'Unknown Plan').'</span> ('.$workinguserdata['account_product_id'].') </div>
                    </div>
                  
                </div>
   </div>
 <hr>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary rounded-pill me-2">'.
                                htmlspecialchars($businessoutput['counts']['remaining']).'</span>
                            <span class="small">Available</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-warning rounded-pill me-2">'.
                                htmlspecialchars($businessoutput['counts']['pending']).'</span>
                            <span class="small">Pending</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success rounded-pill me-2">'.
                                htmlspecialchars($businessoutput['counts']['success']).'</span>
                            <span class="small">Successful</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-danger rounded-pill me-2">'.
                                htmlspecialchars($businessoutput['counts']['failed']).'</span>
                            <span class="small">Failed</span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="fw-semi-bold">Profile Completion</span>
                    <div class="progress" style="width: 150px">
                        <div class="progress-bar bg-'.htmlspecialchars($profilecompletion['required_percentage_color']).'" 
                             role="progressbar" 
                             style="width: '.htmlspecialchars($profilecompletion['required_percentage']).'%" 
                             aria-valuenow="'.htmlspecialchars($profilecompletion['required_percentage']).'" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            '.htmlspecialchars($profilecompletion['required_percentage']).'%
                        </div>
                    </div>
                </div>
       <hr>
';
if (!empty($workinguserdata['feature_email'])) {
    echo '<div class="d-flex justify-content-between align-items-center mb-2">
            <span><i class="bi bi-envelope me-2"></i>Feature Email</span>
            <span>'.htmlspecialchars($workinguserdata['feature_email']).'</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-inbox me-2"></i>Inbox</span>
            <span class="badge bg-'.($featuremailcount['unread'] > 0 ? 'warning' : 'success').'">
                '.number_format($featuremailcount['unread']).' Messages
            </span>
        </div>';
} else {
    echo '<div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-envelope me-2"></i>Feature Email</span>
            <span class="text-muted">Not Set</span>
        </div>';
}


echo '
            </div>
            <div class="card-footer bg-light text-center">
                <small class="text-muted">Total Rewards: '.count($user_reward_results ?? []).'</small>
            </div>
        </div>
    </div>
</div>';