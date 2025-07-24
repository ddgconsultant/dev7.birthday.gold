<?php
// recommend-business.php - User-facing page to submit business recommendations

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$pagetitle = "Recommend a Business";
$messages = array();
$success_message = '';

// Retrieve any messages
$transferpagedata = $system->startpostpage();

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// Handle form submission
if ($app->formposted()) {
    // Get form data
    $business_name = trim($_POST['business_name'] ?? '');
    $home_url = trim($_POST['home_url'] ?? '');
    $signup_url = trim($_POST['signup_url'] ?? '');
    
    // Validate required fields
    if (empty($business_name) || empty($home_url) || empty($signup_url)) {
        $messages[] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Please fill in all required fields</div>';
    } elseif (!filter_var($home_url, FILTER_VALIDATE_URL) || !filter_var($signup_url, FILTER_VALIDATE_URL)) {
        $messages[] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Please enter valid URLs</div>';
    } else {
        // Extract domain from home URL
        $parsed_url = parse_url($home_url);
        $domain = $parsed_url['host'] ?? '';
        $domain = preg_replace('/^www\./', '', $domain);
        
        try {
            // Begin transaction
            $database->beginTransaction();
            
            // Check if company already exists
            $check_sql = "SELECT company_id, company_name, status FROM bg_companies 
                          WHERE (email_domain = :domain OR company_name = :name) 
                          LIMIT 1";
            
            $check_stmt = $database->query($check_sql, [
                'domain' => $domain,
                'name' => $business_name
            ]);
            
            if ($existing = $check_stmt->fetch(PDO::FETCH_ASSOC)) {
                $database->rollBack();
                
                if ($existing['status'] === 'submitted') {
                    $messages[] = '<div class="alert alert-warning"><i class="bi bi-info-circle"></i> This business has already been submitted and is pending review</div>';
                } else {
                    $messages[] = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> This business is already in our directory</div>';
                }
            } else {
                // Insert new company with 'submitted' status
                $insert_sql = "INSERT INTO bg_companies 
                               (parent_company_name, company_name, company_display_name, 
                                company_url, signup_url, email_domain, bgrab_domain,
                                status, company_status, source, create_dt)
                               VALUES 
                               (:parent_name, :company_name, :display_name,
                                :home_url, :signup_url, :email_domain, :bgrab_domain,
                                'submitted', 'submitted', 'user_recommendation', NOW())";
                
                $insert_params = [
                    'parent_name' => $business_name,
                    'company_name' => $business_name,
                    'display_name' => $business_name,
                    'home_url' => $home_url,
                    'signup_url' => $signup_url,
                    'email_domain' => $domain,
                    'bgrab_domain' => $domain
                ];
                
                $database->query($insert_sql, $insert_params);
                $company_id = $database->lastInsertId();
                
                // Store submitter information in bg_company_attributes
                $attr_sql = "INSERT INTO bg_company_attributes 
                             (company_id, type, name, description, status, create_dt)
                             VALUES 
                             (:company_id, 'metadata', 'submitted_by_user_id', :user_id, 'active', NOW())";
                
                $database->query($attr_sql, [
                    'company_id' => $company_id,
                    'user_id' => $current_user_data['user_id']
                ]);
                
                // Store submission timestamp
                $time_sql = "INSERT INTO bg_company_attributes 
                             (company_id, type, name, description, status, create_dt)
                             VALUES 
                             (:company_id, 'metadata', 'submission_timestamp', :timestamp, 'active', NOW())";
                
                $database->query($time_sql, [
                    'company_id' => $company_id,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                // Store user's email for reference
                $email_sql = "INSERT INTO bg_company_attributes 
                              (company_id, type, name, description, status, create_dt)
                              VALUES 
                              (:company_id, 'metadata', 'submitter_email', :email, 'active', NOW())";
                
                $database->query($email_sql, [
                    'company_id' => $company_id,
                    'email' => $current_user_data['email'] ?? ''
                ]);
                
                // Commit transaction
                $database->commit();
                
                // Success message
                $success_message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Thank you for your recommendation! We will review it shortly.</div>';
                
                // Clear the form
                $_POST = array();
            }
            
        } catch (Exception $e) {
            $database->rollBack();
            error_log("Business recommendation error: " . $e->getMessage());
            $messages[] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> An error occurred while submitting your recommendation</div>';
        }
    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
.recommend-form {
    max-width: 600px;
    margin: 0 auto;
}
.form-helper {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}
.url-example {
    font-size: 0.8rem;
    color: #6c757d;
    font-style: italic;
}
</style>';

?>

<!-- Hero Section -->
<div class="content-header-dark no-rounded-corners">
    <div class="container">
        <h1>Recommend a Business</h1>
        <p class="lead mb-4">Know a business that offers birthday rewards? Help us grow our directory by submitting their information below.</p>
    </div>
</div>

<?php
// Display messages
if (!empty($success_message)) {
    echo '<div class="container mt-3">' . $success_message . '</div>';
}
if (!empty($messages)) {
    echo '<div class="container mt-3">';
    foreach ($messages as $message) {
        echo $message;
    }
    echo '</div>';
}

echo '
<div class="container main-content">
    <div class="recommend-form">
        <div class="card" style="margin-top: -2rem; position: relative; z-index: 10;">
            <div class="card-body">
                <form method="post" action="/recommend-business.php">
                    ' . $display->inputcsrf_token() . '
                    
                    <div class="mb-4">
                        <label for="businessName" class="form-label">Business Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="businessName" name="business_name" value="' . htmlspecialchars($_POST['business_name'] ?? '') . '" required>
                        <div class="form-helper">Enter the official business name (e.g., "Starbucks", "Target", "Olive Garden")</div>
                    </div>

                    <div class="mb-4">
                        <label for="homeUrl" class="form-label">Business Website (Home Page) <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="homeUrl" name="home_url" value="' . htmlspecialchars($_POST['home_url'] ?? '') . '" placeholder="https://" required>
                        <div class="url-example">Example: https://www.starbucks.com</div>
                        <div class="form-helper">The main website URL for the business</div>
                    </div>

                    <div class="mb-4">
                        <label for="signupUrl" class="form-label">Birthday Rewards Sign-Up Page <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="signupUrl" name="signup_url" value="' . htmlspecialchars($_POST['signup_url'] ?? '') . '" placeholder="https://" required>
                        <div class="url-example">Example: https://www.starbucks.com/rewards</div>
                        <div class="form-helper">The specific page where customers can sign up for birthday rewards</div>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        Your submission will be reviewed by our team before being added to the directory.
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Submit Recommendation</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-4 text-center text-muted">
            <small>
                <i class="bi bi-shield-check me-1"></i>
                We verify all submissions to ensure quality and accuracy
            </small>
        </div>
    </div>
</div>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>