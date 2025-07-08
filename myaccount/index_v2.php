<?PHP
$addClasses[] = 'Mail';
$addClasses[] = 'TimeClock';
$addClasses[] = 'fileuploader';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Temporary error reporting for debugging
if ($mode == 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

$businessselectorurl = '/myaccount/businessselect';

$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$transferpagedata = [];

$uploadTmpDir = $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/';

// Create directory if it doesn't exist
if (!file_exists($uploadTmpDir)) {
  mkdir($uploadTmpDir, 0777, true);
}

#-------------------------------------------------------------------------------
# HANDLE FIRST PROFILE VISIT
#-------------------------------------------------------------------------------
$response = $account->getUserAttribute($current_user_data['user_id'], 'first_profile_visit');
if (!$response && $current_user_data['account_type'] != 'minor') {

  switch ($current_user_data['account_type']) {
    case 'giftcertificate':
      header('location: /setup-giftcertificate');
      break;


    default:
      header('location: /myaccount/myaccount_actions/setup-individual');
      exit;
  }
}

$till = $app->getTimeTilBirthday($current_user_data['birthdate']);
$session->unset('display_birthday_banner');
$display_birthday_link=false;
#-------------------------------------------------------------------------------
# HANDLE BIRTHDAY NOTIFICATION
#-------------------------------------------------------------------------------
if ($till['days']==0) {
  $response = $account->getUserAttribute($current_user_data['user_id'], 'myaccount_redirect_happybirthday_'.date('Y'));

if (!$response) {
  $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt, start_dt, end_dt)
  VALUES (:user_id, 'page_redirect', 'myaccount_redirect_happybirthday_".date('Y')."', '/myaccount/happy-birthday-to-you', 'active', 100, NOW(), NOW(), '".date('Y')."-01-01', '".date('Y')."-12-31 23:59:59')";
  $stmt = $database->query($sql, [':user_id' => $current_user_data['user_id']]);   
}
$session->set('display_birthday_banner', true);
$display_birthday_link=true;
}

#-------------------------------------------------------------------------------
# HANDLE ATTRIBUTE REDIRECT
#-------------------------------------------------------------------------------
$sql = "SELECT description FROM bg_user_attributes WHERE user_id = :user_id AND `name` like 'myaccount_redirect%' AND `status`='active' limit 1";
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $current_user_data['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
  header('location: ' . $result['description']);
  exit;
}

#-------------------------------------------------------------------------------
# HANDLE THE A REFRESH REQUEST
#-------------------------------------------------------------------------------
if ($app->formposted('GET')) {
  if (isset($_GET['refresh'])) {
    $current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
    header('location: /myaccount/');
    exit;
  }
}

#-------------------------------------------------------------------------------
# GATHER DATA FOR DISPLAY
#-------------------------------------------------------------------------------
$pageoutput = '';

$transferpagedata = $system->startpostpage($transferpagedata);
$pageoutput .= '' . $display->formaterrormessage($transferpagedata['message']);

// Include necessary components for data - MUST be included first to get variables
include($dir['core_components'] . '/user_getaccountdetails.inc');

$birthdates = $account->getBirthdates($current_user_data['birthdate']);
$plandetail_result = $app->plandetail();
// Handle if plandetail returns string or array
if (is_array($plandetail_result)) {
    $planname = $plandetail_result;
} else {
    $planname = ['planname' => $plandetail_result ?: 'Basic'];
}
$alive = $app->calculateage($current_user_data['birthdate']);
$avatar = '/public/images/defaultavatar.png';
$avatarbuttontag = 'Upload';
if (!empty($current_user_data['avatar'])) {
  $avatar = $current_user_data['avatar'];
  $avatarbuttontag = 'Change';
}

if ($current_user_data['account_type'] == 'minor') $minorbg = 'bg-info-subtle';
else $minorbg = '';

// Check failed enrollments
$failed_enrollments_query = "SELECT COUNT(*) as failed_count 
                           FROM bg_user_companies 
                           WHERE user_id = :user_id 
                           AND `status` = 'failed' 
                           AND reason = 'Missing Data Element'";

$stmt = $database->prepare($failed_enrollments_query);
$stmt->execute(['user_id' => $current_user_data['user_id']]);
$failed_count = $stmt->fetch()['failed_count'];

// Profile completion status
if ($profilecompletion['required_percentage'] == 100) {
    $profile_status = 'complete';
    $profile_message = 'Your enrollment profile looks great!';
    $profile_class = 'success';
} elseif ($failed_count > 0) {
    $profile_status = 'danger';
    $profile_message = sprintf(
        'Your enrollment profile is only %d%% complete. Reward enrollments cannot be processed until missing information is provided.',
        $profilecompletion['required_percentage']
    );
    $profile_class = 'danger';
} else {
    $profile_status = 'warning';
    $profile_message = sprintf(
        'Your enrollment profile is %d%% complete. Please complete your profile to ensure smooth processing of reward enrollments.',
        $profilecompletion['required_percentage']
    );
    $profile_class = 'warning';
}

// Get account messages
$message = $account->getaccountmessages();

// Calculate member since date
$create_dt = new DateTime($current_user_data['create_dt']);
$now = new DateTime();
$interval = $now->diff($create_dt);

// Format member since
$member_since = $create_dt->format('M Y');

// Get greeting based on time
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour < 17) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}

// Get initials for avatar
$first_initial = strtoupper(substr($current_user_data['first_name'], 0, 1));
$last_initial = strtoupper(substr($current_user_data['last_name'], 0, 1));
$initials = $first_initial . $last_initial;

// Get zodiac sign
if (method_exists($app, 'getZodiacInfo')) {
    $zodiac_result = $app->getZodiacInfo($current_user_data['birthdate']);
    // Check if it's an array or string
    if (is_array($zodiac_result)) {
        $zodiac = $zodiac_result;
    } else {
        $zodiac = ['name' => $zodiac_result ?: 'Unknown'];
    }
} else {
    $zodiac = ['name' => 'Unknown'];
}

// Last login can be displayed later if we have that data available
$last_login_text = 'Active';

// Get rewards count
$rewards_count = isset($user_reward_results) ? count($user_reward_results) : 0;

// Use existing enrollment counts from businessoutput
$enrollment_counts = isset($businessoutput['counts']) ? $businessoutput['counts'] : [];

// Set default values if not set (in case user_getaccountdetails.inc didn't set them)
if (!isset($funfact_content)) {
    $funfact_content = '';
}
if (!isset($tillanniversary)) {
    $tillanniversary = ['days' => 365];
}
if (!isset($accountlinks_display)) {
    $accountlinks_display = false;
}
if (!isset($accountlinks_output)) {
    $accountlinks_output = '';
}

$bodycontentclass = '';
$header_spacer='60px';
$page_title = "My Account - Birthday Gold";
$page_description = "Manage your Birthday Gold account and birthday rewards";

// Modern CSS based on birthday-gold-account.html
$additionalstyles = '
<style>
/* Reset and scope styles to modern-account-wrapper */
.modern-account-wrapper {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    color: var(--text-primary);
    background: var(--bg-secondary);
    min-height: 100vh;
    margin: -1rem -15px; /* Offset Bootstrap container padding */
    padding: 0;
}

.modern-account-wrapper * {
    box-sizing: border-box;
}

:root {
    --primary: #F59E0B;
    --primary-glow: #FCD34D;
    --secondary: #7C3AED;
    --accent: #EC4899;
    --success: #10B981;
    --danger: #EF4444;
    --warning: #F59E0B;
    --bg-primary: #FFFFFF;
    --bg-secondary: #F9FAFB;
    --bg-card: #FFFFFF;
    --text-primary: #111827;
    --text-secondary: #6B7280;
    --border: rgba(0, 0, 0, 0.08);
    --gradient-gold: linear-gradient(135deg, #F59E0B, #FCD34D);
    --gradient-purple: linear-gradient(135deg, #7C3AED, #EC4899);
    --gradient-light: linear-gradient(180deg, #FFFFFF, #F9FAFB);
    --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Animated Background */
.modern-account-wrapper::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(245, 158, 11, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(124, 58, 237, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(236, 72, 153, 0.05) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.modern-account-wrapper {
    position: relative;
    overflow-x: hidden;
}

/* Container */
.account-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
    position: relative;
    z-index: 1;
}

/* Hero Profile Section */
.hero-section {
    background: var(--gradient-light);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 2.5rem 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    background-size: cover;
    background-position: center;
}

.hero-section::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.3) 100%);
    z-index: 1;
    border-radius: 24px;
}

.hero-section::after {
    content: "";
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
    opacity: 0.2;
    animation: pulse 4s ease-in-out infinite;
    z-index: 2;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.2; }
    50% { transform: scale(1.1); opacity: 0.3; }
}

.hero-content {
    position: relative;
    z-index: 3;
}

/* Update text colors for banner background */
.hero-section .profile-info h1 {
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    background: none;
    -webkit-text-fill-color: white;
}

.hero-section .meta-item {
    color: rgba(255,255,255,0.9);
}

.hero-section .meta-item span:first-child {
    color: var(--primary-glow);
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--gradient-gold);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    color: white;
    box-shadow: 0 0 30px rgba(245, 158, 11, 0.3);
    position: relative;
    border: 3px solid white;
}

.avatar-badge {
    position: absolute;
    bottom: -5px;
    right: -5px;
    background: var(--primary);
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 100px;
    font-weight: 600;
}

.avatar-badge.free {
    background: var(--secondary);
}

.profile-info h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(to right, var(--text-primary), var(--primary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.profile-meta {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.meta-item span:first-child {
    color: var(--primary);
}

/* Alert Messages */
.alert-modern {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: start;
    gap: 1rem;
    box-shadow: var(--shadow-sm);
}

.alert-modern.alert-success {
    background: rgba(16, 185, 129, 0.05);
    border-color: rgba(16, 185, 129, 0.2);
}

.alert-modern.alert-warning {
    background: rgba(245, 158, 11, 0.05);
    border-color: rgba(245, 158, 11, 0.2);
}

.alert-modern.alert-danger {
    background: rgba(239, 68, 68, 0.05);
    border-color: rgba(239, 68, 68, 0.2);
}

.alert-icon {
    font-size: 1.5rem;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.action-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    display: block;
    box-shadow: var(--shadow-sm);
}

.action-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--gradient-gold);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    text-decoration: none;
    color: inherit;
}

.action-card:hover::before {
    opacity: 0.05;
}

.action-card.primary {
    background: var(--gradient-gold);
    color: white;
    border: none;
}

.action-card.primary:hover {
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
    color: white;
}

.action-icon {
    width: 60px;
    height: 60px;
    background: rgba(245, 158, 11, 0.1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.action-card.primary .action-icon {
    background: rgba(255, 255, 255, 0.2);
}

.action-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.action-description {
    font-size: 0.875rem;
    opacity: 0.8;
    line-height: 1.5;
}

/* Main Grid Layout */
.main-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

/* Cards */
.card-modern {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
    margin-bottom: 1.5rem;
}

.card-modern:hover {
    box-shadow: var(--shadow-md);
}

.card-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.card-title-modern {
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-title-icon {
    font-size: 1.5rem;
}

/* Progress Bar */
.progress-container {
    margin: 1.5rem 0;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.progress-bar-modern {
    height: 8px;
    background: var(--bg-secondary);
    border-radius: 100px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: var(--gradient-gold);
    border-radius: 100px;
    position: relative;
    animation: progressGlow 2s ease-in-out infinite;
    transition: width 0.3s ease;
}

@keyframes progressGlow {
    0%, 100% { box-shadow: 0 0 10px rgba(245, 158, 11, 0.5); }
    50% { box-shadow: 0 0 20px rgba(245, 158, 11, 0.8); }
}

/* Data Table */
.data-table-modern {
    width: 100%;
    border-collapse: collapse;
}

.data-table-modern th,
.data-table-modern td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.data-table-modern th {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: var(--bg-secondary);
}

.data-table-modern tr:hover {
    background: var(--bg-secondary);
}

/* Badge */
.badge-modern {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.badge-modern.success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.badge-modern.warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.badge-modern.danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Settings Grid */
.settings-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

.settings-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.settings-link:hover {
    background: rgba(245, 158, 11, 0.05);
    border-color: var(--primary);
    transform: translateX(4px);
    text-decoration: none;
    color: var(--text-primary);
}

.settings-link-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.settings-icon {
    color: var(--primary);
    font-size: 1.25rem;
}

.settings-arrow {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

/* Fun Facts */
.fun-facts {
    display: grid;
    gap: 1rem;
}

.fact-item {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.fact-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.fact-value {
    font-weight: 600;
    color: var(--primary);
}

/* Profile Alert */
.profile-alert {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}

.profile-alert.success {
    background: rgba(16, 185, 129, 0.05);
    border-color: rgba(16, 185, 129, 0.2);
}

.profile-alert.warning {
    background: rgba(245, 158, 11, 0.05);
    border-color: rgba(245, 158, 11, 0.2);
}

.profile-alert.danger {
    background: rgba(239, 68, 68, 0.05);
    border-color: rgba(239, 68, 68, 0.2);
}

.profile-alert-content {
    display: flex;
    align-items: start;
    gap: 1rem;
}

.profile-alert-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.profile-alert-body {
    flex: 1;
}

.profile-alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.profile-alert-title {
    font-weight: 600;
    font-size: 1.125rem;
}

.profile-alert-action {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.profile-alert.success .profile-alert-icon { color: var(--success); }
.profile-alert.warning .profile-alert-icon { color: var(--warning); }
.profile-alert.danger .profile-alert-icon { color: var(--danger); }

.profile-alert.success .profile-alert-action {
    background: var(--success);
    color: white;
}

.profile-alert.warning .profile-alert-action {
    background: var(--warning);
    color: white;
}

.profile-alert.danger .profile-alert-action {
    background: var(--danger);
    color: white;
}

/* Responsive Design */
@media (min-width: 768px) {
    .account-container {
        padding: 2rem;
    }

    .hero-section {
        padding: 3rem;
    }

    .main-grid {
        grid-template-columns: 2fr 1fr;
    }

    .settings-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .quick-actions {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-section,
.card-modern,
.action-card {
    animation: fadeIn 0.6s ease-out;
}

.card-modern:nth-child(2) { animation-delay: 0.1s; }
.card-modern:nth-child(3) { animation-delay: 0.2s; }
.action-card:nth-child(2) { animation-delay: 0.1s; }
.action-card:nth-child(3) { animation-delay: 0.2s; }

/* Override Bootstrap mobile spacing */
@media (max-width: 767px) {
    .container-fluid {
        padding: 0 !important;
    }
    
    .account-container {
        padding: 1rem;
    }
}

/* Space for the header */
.account-container {
    margin-top: 60px;
}
</style>';

// Set flag to use custom layout
$use_custom_layout = true;
$header_flush = true; // This prevents extra spacing after header

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>

<!-- Modern Account Wrapper to isolate styles -->
<div class="modern-account-wrapper">
<!-- Main Container -->
<div class="account-container">
    <!-- Hero Profile Section -->
    <?php 
    // The cover banner is set in bg_user_profileheader.inc which handles all banner logic

    
include($dir['core_components'] . '/bg_user_profileheader.inc');
   /*
    <section class="hero-section" <?php if (!empty($coverbanner)): ?>style="background-image: url('<?php echo htmlspecialchars($coverbanner); ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
        <div class="hero-content">
            <div class="profile-header">
                <div class="avatar" <?php if (!empty($avatar) && $avatar != '/public/images/defaultavatar.png'): ?>style="background-image: url('<?php echo htmlspecialchars($avatar); ?>'); background-size: cover; background-position: center; border-radius: 50%;"<?php endif; ?>>
                    <?php if (empty($avatar) || $avatar == '/public/images/defaultavatar.png'): ?>
                        <?php echo $initials; ?>
                    <?php endif; ?>
                    <?php 
                    // Show badge based on account type
                    $badge_text = '';
                    $badge_class = 'avatar-badge';
                    if($current_user_data['account_type'] == 'paid') {
                        $badge_text = 'GOLD';
                    } elseif($current_user_data['account_type'] == 'free') {
                        $badge_text = 'FREE';
                        $badge_class .= ' free';
                    }
                    
                    if($badge_text):
                    ?>
                    <span class="<?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($current_user_data['first_name']); ?></h1>
                    <div class="profile-meta">
                        <div class="meta-item">
                            <span>🎂</span>
                            <span><?php echo $qik->plural2($till['days'], 'day'); ?> away</span>
                        </div>
                        <div class="meta-item">
                            <span>📅</span>
                            <span>Member since <?php echo $member_since; ?></span>
                        </div>
                        <div class="meta-item">
                            <span>📍</span>
                            <span><?php echo htmlspecialchars($current_user_data['city'] . ', ' . $current_user_data['state']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span>🎨</span>
                            <span><?php echo $zodiac['name']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

 */
    // Display account messages
    if (!empty($message)) {
        echo $message;
    }

    // Display birthday link
    if($display_birthday_link) {
        echo '<div class="alert-modern alert-success">
            <div class="alert-icon">🎂</div>
            <div class="flex-grow-1">
                <a href="/myaccount/happy-birthday-to-you" class="fw-bold text-decoration-none text-success">
                    View your special birthday message again.
                </a>
            </div>
        </div>';
    }
    ?>

    <!-- Quick Actions -->
    <section class="quick-actions">
        <a href="/myaccount/collect" class="action-card primary">
            <div class="action-icon">📋</div>
            <h3 class="action-title">Pick</h3>
            <p class="action-description">Choose from <?php echo $website['numberofbiz']; ?>+ businesses and start earning birthday rewards</p>
        </a>
        
        <a href="/myaccount/redeem" class="action-card">
            <div class="action-icon">🎁</div>
            <h3 class="action-title">Redeem</h3>
            <p class="action-description">You have <?php echo $rewards_count; ?> rewards ready to claim</p>
        </a>
        
        <a href="/myaccount/celebrate" class="action-card">
            <div class="action-icon">🎂</div>
            <h3 class="action-title">Celebrate</h3>
            <p class="action-description"><?php echo $qik->plural2($till['days'], 'day'); ?> away</p>
        </a>
    </section>

    <!-- Main Grid -->
    <div class="main-grid">
        <!-- Left Column -->
        <div class="container">
            <?php
            // Profile completion alert (show if not complete)
            if ($profile_status !== 'complete') {
            ?>
            <div class="profile-alert <?php echo $profile_class; ?>">
                <div class="profile-alert-content">
                    <div class="profile-alert-icon">
                        <?php echo $profile_status === 'danger' ? '⚠️' : '⚠️'; ?>
                    </div>
                    <div class="profile-alert-body">
                        <div class="profile-alert-header">
                            <h5 class="profile-alert-title">
                                <?php 
                                if ($profile_status === 'danger') {
                                    echo 'Action Required: Reward Enrollments Failed';
                                } else {
                                    echo 'Enrollment Profile Incomplete';
                                }
                                ?>
                            </h5>
                            <a href="/myaccount/profile" class="profile-alert-action">
                                <?php echo $profile_status === 'danger' ? 'Complete Missing Information' : 'Complete Profile'; ?>
                            </a>
                        </div>
                        <p class="mb-0"><?php echo $profile_message; ?></p>
                        <?php if ($failed_count > 0): ?>
                        <p class="mb-0 mt-2"><strong><?php echo $failed_count; ?> reward enrollment<?php echo $failed_count > 1 ? 's have' : ' has'; ?> failed due to missing profile information.</strong></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php } ?>

            <!-- Enrollments Summary -->
            <div class="card-modern">
                <div class="card-header-modern">
                    <h2 class="card-title-modern">
                        <span class="card-title-icon">📊</span>
                        Enrollment Summary
                    </h2>
                    <a href="/myaccount/enrollment" class="badge-modern success">Dashboard</a>
                </div>
                
                <div class="progress-container">
                    <div class="progress-header">
                        <span>
                            <?php 
                            if ($businessoutput['counts']['remaining'] == 0) {
                                echo '<i class="bi bi-cart-x-fill text-danger me-2"></i>You ran out of enrollments. You\'ll receive ' . $businessoutput['counts']['plan_total'] . ' more in ' . $qik->plural2($tillanniversary['days'], 'day') . '.';
                            } else {
                                echo '<i class="bi bi-cart-plus-fill text-success me-2"></i>You have ' . $qik->plural2($businessoutput['counts']['remaining'], 'enrollment') . ' remaining.';
                            }
                            ?>
                        </span>
                        <span class="text-secondary"><?php echo $rewards_count; ?> Rewards Available</span>
                    </div>
                    <div class="progress-bar-modern">
                        <div class="progress-fill" style="width: <?php echo ($enrollment_counts['active'] / max($businessoutput['counts']['plan_total'], 1)) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="enrollments-table">
                    <table class="data-table-modern">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Active</td>
                                <td><?php echo $enrollment_counts['active'] ?? 0; ?></td>
                                <td><span class="badge-modern success">Active</span></td>
                            </tr>
                            <tr>
                                <td>Pending</td>
                                <td><?php echo $enrollment_counts['pending'] ?? 0; ?></td>
                                <td><span class="badge-modern warning">Processing</span></td>
                            </tr>
                            <tr>
                                <td>Successful</td>
                                <td><?php echo $enrollment_counts['success'] ?? 0; ?></td>
                                <td><span class="badge-modern success">Complete</span></td>
                            </tr>
                            <tr>
                                <td>Failed</td>
                                <td><?php echo $enrollment_counts['failed'] ?? 0; ?></td>
                                <td><span class="badge-modern danger">Failed</span></td>
                            </tr>
                            <tr>
                                <td># of Rewards</td>
                                <td><?php echo $rewards_count; ?></td>
                                <td><span class="badge-modern success">Ready</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <a href="/myaccount/enrollment-history" class="btn btn-primary">View enrollments</a>
                </div>
            </div>

            <!-- Fun Facts -->
            <div class="card-modern">
                <div class="card-header-modern">
                    <h2 class="card-title-modern">
                        <span class="card-title-icon">✨</span>
                        Fun Facts
                    </h2>
                    <a href="/myaccount/fun-facts" class="text-primary text-decoration-none">Discover more ></a>
                </div>
                
                <div class="fun-facts">
                    <?php echo $funfact_content; ?>
                </div>
            </div>

            <?php if ($profile_status === 'complete'): ?>
            <!-- Profile completion success (show at bottom if complete) -->
            <div class="profile-alert success">
                <div class="profile-alert-content">
                    <div class="profile-alert-icon">✅</div>
                    <div class="profile-alert-body">
                        <div class="profile-alert-header">
                            <h5 class="profile-alert-title">Enrollment Profile Complete</h5>
                            <a href="/myaccount/profile" class="badge-modern success">Edit Profile</a>
                        </div>
                        <p class="mb-0">Your enrollment profile looks great!</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="sidebar">
            <!-- Settings -->
            <div class="card-modern">
                <div class="card-header-modern">
                    <h2 class="card-title-modern">
                        <span class="card-title-icon">⚙️</span>
                        Settings
                    </h2>
                    <a href="/myaccount/settings" class="text-secondary">
                        <i class="bi bi-gear"></i>
                    </a>
                </div>
                
                <div class="settings-grid">
                    <a href="/myaccount/account" class="settings-link">
                        <div class="settings-link-content">
                            <span class="settings-icon">✏️</span>
                            <span>Account Settings</span>
                        </div>
                        <span class="settings-arrow">›</span>
                    </a>
                    <a href="/myaccount/notifications#settings" class="settings-link">
                        <div class="settings-link-content">
                            <span class="settings-icon">🔔</span>
                            <span>Manage Notifications</span>
                        </div>
                        <span class="settings-arrow">›</span>
                    </a>
                    <a href="/myaccount/security-settings" class="settings-link">
                        <div class="settings-link-content">
                            <span class="settings-icon">🛡️</span>
                            <span>Security Settings</span>
                        </div>
                        <span class="settings-arrow">›</span>
                    </a>
                    <a href="/myaccount/parental-mode" class="settings-link">
                        <div class="settings-link-content">
                            <span class="settings-icon">👤</span>
                            <span>Parental Mode</span>
                        </div>
                        <span class="settings-arrow">›</span>
                    </a>
                    <a href="/myaccount/invite" class="settings-link">
                        <div class="settings-link-content">
                            <span class="settings-icon">👍</span>
                            <span>Invite Friends</span>
                        </div>
                        <span class="settings-arrow">›</span>
                    </a>
                </div>
            </div>

            <!-- Profile Details -->
            <div class="card-modern">
                <div class="card-header-modern">
                    <h2 class="card-title-modern">
                        <span class="card-title-icon">👤</span>
                        Profile Details
                    </h2>
                </div>
                
                <div class="fun-facts">
                    <div class="fact-item">
                        <span class="fact-label">Plan</span>
                        <span class="fact-value"><?php echo ucfirst($planname['planname']); ?></span>
                    </div>
                    <div class="fact-item">
                        <span class="fact-label">Completion</span>
                        <span class="fact-value badge-modern <?php echo $profile_class; ?>"><?php echo $profilecompletion['required_percentage']; ?>% Complete</span>
                    </div>
                    <?php if(!empty($current_user_data['profile_military']) || !empty($current_user_data['profile_educator']) || !empty($current_user_data['profile_firstresponder'])): ?>
                    <div class="fact-item">
                        <span class="fact-label">Bonus Chance</span>
                        <span class="fact-value">
                            <?php 
                            $bonuses = [];
                            if($current_user_data['profile_military']) $bonuses[] = 'Military 🎖️';
                            if($current_user_data['profile_educator']) $bonuses[] = 'Educator 📚';
                            if($current_user_data['profile_firstresponder']) $bonuses[] = 'First Responder 🚨';
                            echo implode(', ', $bonuses);
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="fact-item">
                        <span class="fact-label">Birthday</span>
                        <span class="fact-value"><?php echo $current_user_data['birthdate']; ?></span>
                    </div>
                    <div class="fact-item">
                        <span class="fact-label">Joined</span>
                        <span class="fact-value"><?php echo $create_dt->format('Y'); ?></span>
                    </div>
                    <div class="fact-item">
                        <span class="fact-label">Last Login</span>
                        <span class="fact-value"><?php echo $last_login_text; ?></span>
                    </div>
                </div>
            </div>

            <?php
            // Other Features / Links
            $accountlinkspresentation = '';
            include($dir['core_components'] . '/user_accountlinks.inc');
            if ($accountlinks_display !== false && !empty($accountlinks_output)) {
            ?>
            <div class="card-modern">
                <div class="card-header-modern">
                    <h2 class="card-title-modern">
                        <span class="card-title-icon">🔗</span>
                        Other Features
                    </h2>
                </div>
                
                <?php echo $accountlinks_output; ?>
                
                <div class="mt-3">
                    <a href="/myaccount/account" class="btn btn-sm btn-primary">Go to account settings</a>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
</div><!-- End modern-account-wrapper -->

<?php
$footerattribute['bottomfooter'] = '
<script>
$(document).ready(function() {
    // Profile image upload functionality
    $("#uploadBtn").click(function() {
        $("#profile-image").click();
    });

    $("#profile-image").change(function() {
        $("#profileavatarupload").submit();
    });
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>