<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Security Questions - Birthday Gold';
$pagedata['metakeywords'] = 'Birthday Gold Security Questions, Account Recovery, Security Settings';
$pagedata['metadescriptions'] = 'Set up security questions to help recover your Birthday Gold account and enhance security.';

// Additional styles
$additionalstyles = '
<style>
/* Security Questions Styles - Following security-settings pattern */
.security-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}

.security-hero::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.05); opacity: 0.8; }
}

.security-hero h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.security-hero p {
    font-size: 1.1rem;
    opacity: 0.9;
    position: relative;
    z-index: 1;
    margin-bottom: 0;
}

/* Security Cards */
.security-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 0;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.security-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.security-card-header {
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.security-card-icon {
    font-size: 2rem;
    margin-right: 1rem;
    color: #6f42c1;
}

.security-card-title {
    display: flex;
    align-items: center;
    margin: 0;
}

.security-card-title h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #212529;
}

.security-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-configured {
    background: #d4edda;
    color: #155724;
}

.status-not-configured {
    background: #fff3cd;
    color: #856404;
}

.security-card-body {
    padding: 1.5rem;
}

.security-description {
    color: #6c757d;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

/* Question Cards */
.question-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 0;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.question-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.question-header {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.question-number {
    width: 32px;
    height: 32px;
    background: #6f42c1;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
}

.question-body {
    padding: 1.5rem;
}

/* Form Styles */
.form-select, .form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem;
    transition: all 0.3s ease;
}

.form-select:focus, .form-control:focus {
    border-color: #6f42c1;
    box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
}

/* Button Styles */
.btn-security-primary {
    background: #198754;
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-security-primary:hover {
    background: #157347;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(25, 135, 84, 0.3);
}

.btn-security-secondary {
    background: transparent;
    color: #495057;
    border: 2px solid #dee2e6;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-security-secondary:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    color: #212529;
}

/* Alert Improvements */
.alert {
    border-radius: 8px;
    border: none;
    padding: 1rem 1.5rem;
}

.alert-info {
    background: #e8f4fd;
    color: #0c5460;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
}

/* Status Card */
.status-card {
    background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.status-card::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
}

.status-card h4 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.status-card p {
    opacity: 0.9;
    position: relative;
    z-index: 1;
    margin-bottom: 0;
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6f42c1;
    text-decoration: none;
    font-weight: 600;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.back-link:hover {
    color: #5a32a3;
    transform: translateX(-3px);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .security-hero h1 {
        font-size: 1.75rem;
    }
    
    .security-card-header {
        flex-direction: column;
        align-items: start;
        gap: 1rem;
    }
    
    .question-header {
        padding: 1rem;
    }
    
    .btn-security-primary,
    .btn-security-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>
';

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$security_questions = [
    'What was the name of your first pet?',
    'What city were you born in?',
    'What was your childhood nickname?',
    'What was the first concert you attended?',
    'What was the make and model of your first car?',
    'What elementary school did you attend?',
    'What is your mother\'s maiden name?',
    'What is the name of the street you grew up on?',
    'What was your favorite subject in high school?',
    'What is your favorite movie from your childhood?',
    'What was the name of your favorite childhood teacher?',
    'What was your first job?',
    'What is the middle name of your oldest sibling?',
    'What was the destination of your first airplane ride?',
    'What was the name of your first best friend?',
    'What was your favorite restaurant in college?',
    'What was the mascot of your high school?',
    'What was the first video game you remember playing?',
    'What is the name of the hospital where you were born?',
    'What was your grandmother\'s favorite recipe?'
];

$success_message = '';
$error_message = '';



#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
// Fetch existing security questions
$sql = "SELECT name, description, string_value, modify_dt 
        FROM bg_user_attributes 
        WHERE user_id = :user_id 
        AND type = 'security' 
        AND category = 'security' 
        AND `grouping` = 'security_questions' 
        AND status = 'active'
        ORDER BY name";

$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $current_user_data['user_id']]);
$latest_modify_dt = null;
while ($row = $stmt->fetch()) {
    $current_questions[$row['name']] = json_decode($row['string_value'], true);
    // Track the latest modification date by direct string comparison
    if ($latest_modify_dt === null || $row['modify_dt'] > $latest_modify_dt) {
        $latest_modify_dt = $row['modify_dt'];
    }
}

$has_security_questions = (count($current_questions) === 3);

if ($app->formposted()) {
    if (isset($_POST['action']) && $_POST['action'] == 'update_security_questions') {
        $selected_questions = [];
        $questions_to_update = [];
        
        // Validate questions first
        for ($i = 1; $i <= 3; $i++) {
            if (!isset($_POST["question$i"]) || empty($_POST["question$i"])) {
                $error_message = 'Please select all questions';
                break;
            }
            
            $current_question = $has_security_questions ? 
                              $current_questions['security_q' . $i]['question'] : '';
            
            // Check if this question is being changed
            if ($_POST["question$i"] !== $current_question) {
                $questions_to_update[] = $i;
            }
            
            // Check for duplicates with existing unchanged questions and new selections
            if (in_array($_POST["question$i"], $selected_questions)) {
                $error_message = 'Please select different questions for each slot';
                break;
            }
            
            $selected_questions[] = $_POST["question$i"];
        }
        
        // If questions are valid, check answers
        if (empty($error_message)) {
            if ($has_security_questions) {
                // Update mode: verify answers provided for changed questions
                foreach ($questions_to_update as $q_num) {
                    if (!isset($_POST["answer$q_num"]) || empty(trim($_POST["answer$q_num"]))) {
                        $error_message = 'Please provide answers for all changed questions';
                        break;
                    }
                }
            } else {
                // New setup: verify all answers provided
                for ($i = 1; $i <= 3; $i++) {
                    if (!isset($_POST["answer$i"]) || empty(trim($_POST["answer$i"]))) {
                        $error_message = 'Please provide all answers';
                        break;
                    }
                }
            }
        }
        
        // Save if everything validates
        if (empty($error_message)) {
            try {
                for ($i = 1; $i <= 3; $i++) {
                    // Determine if this question needs updating
                    $needs_update = !$has_security_questions || 
                                  in_array($i, $questions_to_update) ||
                                  (isset($_POST["answer$i"]) && !empty(trim($_POST["answer$i"])));
                    
                    if ($needs_update) {
                        // Delete existing if any
                        $delete_sql = "DELETE FROM bg_user_attributes 
                                     WHERE user_id = :user_id 
                                     AND type = 'security' 
                                     AND name = :name 
                                     AND category = 'security'";
                        
                        $stmt = $database->prepare($delete_sql);
                        $stmt->execute([
                            'user_id' => $current_user_data['user_id'],
                            'name' => 'security_q' . $i
                        ]);

                        // Insert new/updated question
                        $insert_sql = "INSERT INTO bg_user_attributes 
                                     (user_id, type, name, description, status, 
                                      string_value, `grouping`, category, visibility, formatting) 
                                     VALUES 
                                     (:user_id, 'security', :name, :description, 'active',
                                      :string_value, 'security_questions', 'security', 'private', 'json')";
                        
                        $answer = isset($_POST["answer$i"]) && !empty(trim($_POST["answer$i"])) ?
                                 password_hash(trim($_POST["answer$i"]), PASSWORD_DEFAULT) :
                                 $current_questions['security_q' . $i]['answer'];
                        
                        $stmt = $database->prepare($insert_sql);
                        $stmt->execute([
                            'user_id' => $current_user_data['user_id'],
                            'name' => 'security_q' . $i,
                            'description' => $_POST["question$i"],
                            'string_value' => json_encode([
                                'question' => $_POST["question$i"],
                                'answer' => $answer
                            ])
                        ]);
                    }
                }
                
  
        $success_message = $has_security_questions ? 
            'Your security questions have been updated successfully.' : 
            'Your security questions have been set up successfully.';

        session_tracking('Security questions ' . ($has_security_questions ? 'updated' : 'configured') . ' successfully');
        $pagemessage = '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
                       $success_message . '</div>';
        $transferpage['url'] = '/myaccount/security-questions';
        $transferpage['message'] = $pagemessage;
        $system->endpostpage($transferpage);
        exit;

    } catch (Exception $e) {
        error_log("Security question update failed: " . $e->getMessage());
        session_tracking('Security question update failed: ' . $e->getMessage());
        $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                       'An error occurred while saving your security questions' . '</div>';
        $transferpage['url'] = '/myaccount/security-questions';
        $transferpage['message'] = $pagemessage;
        $system->endpostpage($transferpage);
        exit;
    }
}

    }
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
#include($dir['core_components'] . '/bg_user_leftpanel.inc');



$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);
$success_message=$transferpagedata['message'];

// Add JavaScript for dynamic UI
echo '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const questionSelects = document.querySelectorAll("select[name^=\'question\']");
    questionSelects.forEach(select => {
        select.addEventListener("change", function() {
            const questionNum = this.name.replace("question", "");
            const answerDiv = document.getElementById("answer-section-" + questionNum);
            const originalValue = this.getAttribute("data-original-value");
            
            if (this.value !== originalValue) {
                answerDiv.innerHTML = `
                    <label class="form-label fw-semibold">New Answer Required:</label>
                    <input type="text" name="answer${questionNum}" class="form-control" required 
                           placeholder="Enter your new answer">
                    <small class="text-danger mt-1 d-block"><i class="bi bi-exclamation-circle me-1"></i>New answer required for changed question</small>
                `;
            } else {
                answerDiv.innerHTML = `
                    <label class="form-label fw-semibold">Current Answer:</label>
                    <input type="text" name="answer${questionNum}" class="form-control" 
                           placeholder="************">
                    <small class="text-muted mt-1 d-block"><i class="bi bi-lock-fill me-1"></i>Current answer is stored securely. Enter a new answer only if you want to change it.</small>
                `;
            }
        });
    });
});
</script>';

echo '
<div class="container my-4 pt-5">
    
    <!-- Back Link -->
    <a href="/myaccount/security-settings" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Security Settings
    </a>
    
    <!-- Security Hero Section -->
    <div class="security-hero">
        <h1 class="text-white">Security Questions</h1>
        <p>Set up recovery questions to help protect your account</p>
    </div>

';

if ($has_security_questions && !isset($_POST['show_form'])) {
    // Show status card
    echo '
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-question-circle-fill security-card-icon"></i>
                <h3>Security Questions Status</h3>
            </div>
            <div class="security-status">
                <span class="status-badge status-configured">Configured</span>
            </div>
        </div>
        <div class="security-card-body">
            <p class="security-description">
                Your security questions are set up and ready to help you recover your account if needed. 
                These questions provide an additional layer of security and account recovery options.
            </p>
            
            <div class="status-card">
                <h4><i class="bi bi-shield-check me-2"></i>Questions Configured</h4>
                <p>Last updated ' . $qik->timeago($latest_modify_dt)['message'] . '</p>
            </div>
            
            <form method="POST" action="">
                '.$display->inputcsrf_token().'
                <input type="hidden" name="show_form" value="1">
                <button type="submit" class="btn btn-security-primary">
                    <i class="bi bi-pencil-square me-2"></i>Change Security Questions
                </button>
            </form>
        </div>
    </div>';
} else {
    // Show form
    echo '
    <div class="security-card mb-4">
        <div class="security-card-header">
            <div class="security-card-title">
                <i class="bi bi-question-circle-fill security-card-icon"></i>
                <h3>' . ($has_security_questions ? 'Update Security Questions' : 'Set Up Security Questions') . '</h3>
            </div>
            <div class="security-status">
                <span class="status-badge ' . ($has_security_questions ? 'status-configured' : 'status-not-configured') . '">
                    ' . ($has_security_questions ? 'Updating' : 'Setup Required') . '
                </span>
            </div>
        </div>
        <div class="security-card-body">
            <p class="security-description">' . 
                ($has_security_questions ? 
                'Update your security questions and answers below. You must provide a new answer when changing a question.' : 
                'Please select three different security questions and provide answers. These will help you recover your account if needed.') . 
            '</p>';

    if ($success_message) {
        echo '<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($success_message) . '</div>';
    }
    if ($error_message) {
        echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($error_message) . '</div>';
    }

    echo '
        </div>
    </div>
    
    <form method="POST" action="">
        <input type="hidden" name="action" value="update_security_questions">
        '.$display->inputcsrf_token();

    for ($i = 1; $i <= 3; $i++) {
        $current_q = isset($current_questions['security_q' . $i]) ? 
                     $current_questions['security_q' . $i]['question'] : '';
        
        echo '
        <div class="question-card">
            <div class="question-header">
                <div class="question-number">' . $i . '</div>
                <h5 class="mb-0">Security Question ' . $i . '</h5>
            </div>
            <div class="question-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Question:</label>
                    <select name="question' . $i . '" 
                            class="form-select" 
                            required 
                            data-original-value="' . htmlspecialchars($current_q) . '">
                        <option value="">Choose a question...</option>';
        
        foreach ($security_questions as $question) {
            $selected = ($question === $current_q) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($question) . '"' . $selected . '>' . 
                 htmlspecialchars($question) . '</option>';
        }
        
        echo '
                    </select>
                </div>
                <div id="answer-section-' . $i . '">
                    <label class="form-label fw-semibold">' . ($has_security_questions ? 'Current Answer:' : 'Your Answer:') . '</label>
                    <input type="text" 
                           name="answer' . $i . '" 
                           class="form-control" ' .
                           (!$has_security_questions ? 'required ' : '') . '
                           placeholder="' . ($has_security_questions ? '************' : 'Enter your answer') . '">
                    ' . ($has_security_questions ? 
                        '<small class="text-muted mt-1 d-block"><i class="bi bi-lock-fill me-1"></i>Current answer is stored securely. Enter a new answer only if you want to change it.</small>' : 
                        '<small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Choose a memorable answer that only you would know.</small>') . '
                </div>
            </div>
        </div>';
    }

    echo '
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-security-primary">
                <i class="bi bi-check-circle me-2"></i>' . ($has_security_questions ? 'Update' : 'Save') . ' Security Questions
            </button>
            ' . ($has_security_questions ? '
            <a href="/myaccount/security-questions" class="btn btn-security-secondary">Cancel</a>
            ' : '') . '
        </div>
    </form>';
}

echo '</div>';
echo '</div></div>';
$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();