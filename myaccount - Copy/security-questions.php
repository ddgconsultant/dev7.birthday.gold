<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

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
include($dir['core_components'] . '/bg_user_leftpanel.inc');



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
                    <label>New Answer Required:</label>
                    <input type="text" name="answer${questionNum}" class="form-control" required 
                           placeholder="Enter your new answer">
                    <small class="text-danger">New answer required for changed question</small>
                `;
            } else {
                answerDiv.innerHTML = `
                    <label>Current Answer:</label>
                    <input type="text" name="answer${questionNum}" class="form-control" 
                           placeholder="************">
                    <small class="text-muted">Current answer is stored securely. Enter a new answer only if you want to change it.</small>
                `;
            }
        });
    });
});
</script>';

echo '
<div class="container main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Security Questions</h2>
    <a href="/myaccount/security-settings" class="btn btn-sm btn-primary">Security Settings</a>
  </div>

';

if ($has_security_questions && !isset($_POST['show_form'])) {
    // Show status and change button
    echo '
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
          <i class="bi bi-shield-lock"></i> Your security questions are configured.<br>
                <small class="text-muted">Last updated: ' . $qik->timeago($latest_modify_dt)['message'] . '</small>
                </div>
            <form method="POST" action="">
                '.$display->inputcsrf_token().'
                <input type="hidden" name="show_form" value="1">
                <button type="submit" class="btn btn-secondary">
                    <i class="bi bi-pencil-square"></i> Change Security Questions
                </button>
            </form>
        </div>
    </div>';
} else {
    // Show form
    echo '
    <p class="mb-4">' . 
        ($has_security_questions ? 
        'Update your security questions and answers below. You must provide a new answer when changing a question.' : 
        'Please select three different security questions and provide answers. These will help you recover your account if needed.') . 
    '</p>';

    if ($success_message) {
        echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
    }
    if ($error_message) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    }

    echo '
    <form method="POST" action="">
        <input type="hidden" name="action" value="update_security_questions">
        '.$display->inputcsrf_token();

    for ($i = 1; $i <= 3; $i++) {
        $current_q = isset($current_questions['security_q' . $i]) ? 
                     $current_questions['security_q' . $i]['question'] : '';
        
        echo '
        <div class="question-block card mb-4">
            <div class="card-body">
                <h5 class="card-title">Security Question ' . $i . '</h5>
                <div class="form-group mb-3">
                    <label>Select Question:</label>
                    <select name="question' . $i . '" 
                            class="form-control" 
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
                <div class="form-group" id="answer-section-' . $i . '">
                    <label>' . ($has_security_questions ? 'Current Answer:' : 'Your Answer:') . '</label>
                    <input type="text" 
                           name="answer' . $i . '" 
                           class="form-control" ' .
                           (!$has_security_questions ? 'required ' : '') . '
                           placeholder="' . ($has_security_questions ? '************' : 'Enter your answer') . '">
                    ' . ($has_security_questions ? 
                        '<small class="text-muted">Current answer is stored securely. Enter a new answer only if you want to change it.</small>' : '') . '
                </div>
            </div>
        </div>';
    }

    echo '
        <div class="mt-4">
            ' . ($has_security_questions ? '
            <a href="" class="btn btn-link">Cancel</a>
            ' : '') . '
            <button type="submit" class="btn btn-primary">
                ' . ($has_security_questions ? 'Update' : 'Save') . ' Security Questions
            </button>
        </div>
    </form>';
}

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
$display_footertype = '';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();