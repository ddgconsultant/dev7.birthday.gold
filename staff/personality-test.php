<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff-only access is already handled by site-controller.php

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$pagetitle = 'True Colors Team Style Assessment';

// Check if user has already taken the test
$existing_sql = "SELECT * FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = 'true_colors_test' 
                AND status = 'active'
                ORDER BY create_dt DESC 
                LIMIT 1";
$existing_result = $database->getrow($existing_sql, ['user_id' => $current_user_data['user_id']]);

// Check if showing completion message or retaking
$show_completed = isset($_GET['completed']);
$retaking = isset($_GET['retake']);

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_POST['submit_test'])) {
    $answers = $_POST['answers'] ?? [];
    
    // Calculate scores for each color based on True Colors mapping
    // A = Orange, B = Gold, C = Blue, D = Green
    $scores = [
        'orange' => 0,
        'gold' => 0,
        'blue' => 0,
        'green' => 0
    ];
    
    // Process answers - each answer contributes to its color
    foreach ($answers as $question_num => $answer) {
        if (isset($scores[$answer])) {
            $scores[$answer]++;
        }
    }
    
    // Determine primary and secondary colors
    arsort($scores);
    $colors = array_keys($scores);
    $primary_color = $colors[0];
    $secondary_color = $colors[1];
    
    // Prepare result data with individual answers
    $result_data = [
        'primary_color' => $primary_color,
        'secondary_color' => $secondary_color,
        'scores' => $scores,
        'answers' => $answers, // Store individual question answers
        'test_date' => date('Y-m-d H:i:s')
    ];
    
    // Deactivate old results
    if ($existing_result) {
        $database->query(
            "UPDATE bg_user_attributes SET status = 'inactive' WHERE attribute_id = :id",
            ['id' => $existing_result['attribute_id']]
        );
    }
    
    // Save results to database - use description field for JSON, value for primary score
    $save_sql = "INSERT INTO bg_user_attributes 
                 (user_id, type, name, value, description, string_value, status, create_dt) 
                 VALUES 
                 (:user_id, 'true_colors_test', 'true_colors_personality', :value, :description, :string_value, 'active', NOW())";
    
    $database->query($save_sql, [
        'user_id' => $current_user_data['user_id'],
        'value' => $scores[$primary_color], // Store primary color score as integer
        'description' => json_encode($result_data), // Store full JSON in description
        'string_value' => "Primary: {$primary_color}, Secondary: {$secondary_color}" // Summary in string_value
    ]);
    
    // Assessment completed - Stacey can check results in admin interface
    
    // Redirect to completion page instead of showing results
    header('Location: /staff/personality-test.php?completed=1');
    exit;
}

// Color profiles for results
$color_profiles = [
    'orange' => [
        'name' => 'Orange',
        'title' => 'Action-Oriented Leader',
        'description' => 'You are spontaneous, flexible, and thrive on variety and challenge. You prefer direct communication and quick results.',
        'strengths' => ['High energy and enthusiasm', 'Quick decision making', 'Adaptable to change', 'Natural risk-taker', 'Results-focused'],
        'communication' => 'Be direct, allow freedom, focus on results not rigid rules',
        'motivation' => 'Variety, challenge, recognition for quick wins',
        'delegation' => 'Short-term, high-energy projects',
        'stress_response' => 'May become disorganized or impulsive',
        'color' => '#FF6B35'
    ],
    'gold' => [
        'name' => 'Gold', 
        'title' => 'Organized Stabilizer',
        'description' => 'You value structure, dependability, and clear processes. You are thorough and responsible in your approach.',
        'strengths' => ['Highly organized', 'Reliable and punctual', 'Detail-oriented', 'Follows procedures', 'Quality-focused'],
        'communication' => 'Give clear instructions and timelines',
        'motivation' => 'Stability, clear structure, recognition for responsibility',
        'delegation' => 'Procedures, quality control, planning tasks',
        'stress_response' => 'May become rigid or overly critical',
        'color' => '#FFD700'
    ],
    'blue' => [
        'name' => 'Blue',
        'title' => 'People-Focused Collaborator', 
        'description' => 'You are compassionate, cooperative, and value relationships. You work best in harmonious team environments.',
        'strengths' => ['Strong interpersonal skills', 'Empathetic and caring', 'Team-oriented', 'Conflict resolver', 'Supportive of others'],
        'communication' => 'Show empathy, value their input',
        'motivation' => 'Harmony, team connection, appreciation for efforts',
        'delegation' => 'Team-building roles, customer/patient interactions',
        'stress_response' => 'May take criticism personally, avoid conflict',
        'color' => '#4A90E2'
    ],
    'green' => [
        'name' => 'Green',
        'title' => 'Analytical Problem-Solver',
        'description' => 'You are logical, independent, and prefer to work with facts and data. You value competence and expertise.',
        'strengths' => ['Logical and analytical', 'Independent worker', 'Problem-solving skills', 'Data-driven decisions', 'High standards'],
        'communication' => 'Present facts, give space to think',
        'motivation' => 'Autonomy, opportunities for mastery, problem-solving challenges',
        'delegation' => 'Research, data analysis, technical projects',
        'stress_response' => 'May withdraw, overanalyze, become perfectionistic',
        'color' => '#50C878'
    ]
];

// Define questions in existing format style
$questions = [
    1 => [
        'question' => 'When starting new tasks, I prefer to:',
        'answers' => [
            'orange' => 'Act on a moment\'s notice and jump right in',
            'gold' => 'Plan with organized, structured approach', 
            'blue' => 'Collaborate with others and work in teams',
            'green' => 'Think through problems logically first'
        ]
    ],
    2 => [
        'question' => 'My natural approach to work is:',
        'answers' => [
            'orange' => 'Flexible and able to improvise as needed',
            'gold' => 'Following rules and respecting established authority',
            'blue' => 'Valuing harmony and maintaining close relationships', 
            'green' => 'Being curious and always seeking more knowledge'
        ]
    ],
    3 => [
        'question' => 'In challenging situations, I tend to:',
        'answers' => [
            'orange' => 'Take risks and seek adventure',
            'gold' => 'Stay dependable and punctual',
            'blue' => 'Remain compassionate and loyal',
            'green' => 'Be analytical and objective'
        ]
    ],
    4 => [
        'question' => 'What energizes me most at work:',
        'answers' => [
            'orange' => 'Variety and excitement in my tasks',
            'gold' => 'Stability and clear expectations',
            'blue' => 'Being empathetic and supportive of others',
            'green' => 'Independent, in-depth work projects'
        ]
    ],
    5 => [
        'question' => 'My colleagues would describe me as:',
        'answers' => [
            'orange' => 'Energetic and competitive', 
            'gold' => 'Responsible and thorough',
            'blue' => 'Caring and focused on connection',
            'green' => 'Someone who values facts and logic'
        ]
    ]
];

$additionalstyles = '
<style>
.personality-test {
    max-width: 900px;
    margin: 0 auto;
}

.question-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-left: 4px solid var(--bs-primary);
}

.question-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #2c3e50;
}

.answer-option {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.answer-option:hover {
    border-color: var(--bs-primary);
    background: #e3f2fd;
}

.answer-option.selected {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
}

.color-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    color: white;
    font-weight: bold;
    margin: 0.25rem;
}

.color-badge.orange { background: #FF6B35; }
.color-badge.gold { background: #FFD700; color: #333; }
.color-badge.blue { background: #4A90E2; }
.color-badge.green { background: #50C878; }

.result-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-left: 6px solid var(--color);
}

.color-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.color-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--color);
    margin-right: 1rem;
}

.score-bars {
    margin-top: 2rem;
}

.color-score-bar {
    height: 30px;
    border-radius: 15px;
    margin: 10px 0;
    display: flex;
    align-items: center;
    padding: 0 15px;
    color: white;
    font-weight: bold;
}

</style>';

// No additionalscripts here - will add inline after jQuery loads

$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

// Staff header
echo '
<div class="content-header-staff">
    <div class="container text-center">
        <h1><i class="bi bi-palette"></i> True Colors Team Style Assessment</h1>
        <p class="lead">Discover your work style and communication preferences</p>
    </div>
</div>';

echo '<div class="container mt-4 personality-test">';

if ($show_completed) {
    // Show completion message
    echo '
    <div class="text-center">
        <div class="card">
            <div class="card-body py-5">
                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                <h2 class="mt-3">Assessment Completed!</h2>
                <p class="lead">Thank you for completing the True Colors Team Style Assessment.</p>
                <p class="text-muted">Your results have been submitted and will be reviewed personally with you.</p>
                <div class="mt-4">
                    <a href="/staff/" class="btn btn-primary">Return to Staff Dashboard</a>
                </div>
            </div>
        </div>
    </div>';
    
} else if (!$existing_result || $retaking) {
    // Show the test
    echo '
    <div class="card mb-4">
        <div class="card-body">
            <h5>About This Assessment</h5>';
            
    if ($retaking) {
        echo '
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle"></i> You are retaking the True Colors Team Style Assessment. Your new results will replace your previous assessment.
            </div>';
    }
    
    echo '
            <p>This assessment will help explore the different ways you approach work, communication, and problem-solving.</p>
            <p class="text-muted">Choose the option that best describes you for each question. Answer honestly for the most accurate results!</p>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div id="progress-container" class="bg-white p-3 mb-4 shadow-sm border rounded">
        <div class="progress" style="height: 25px;">
            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
        </div>
        <p class="text-center text-muted mt-2" id="progress-text">0 of 5 questions answered</p>
    </div>
    
    <form method="POST" id="personality-test-form">
        ' . $display->input_csrftoken();
        
        foreach ($questions as $q_num => $q_data) {
            echo '
            <div class="question-card" data-question="' . $q_num . '">
                <div class="question-title">
                    <span class="badge bg-primary me-2">' . $q_num . '</span>
                    ' . $q_data['question'] . '
                </div>
                <div class="answers-container">';
                
                foreach ($q_data['answers'] as $color => $answer_text) {
                    echo '
                    <div class="answer-option" data-color="' . $color . '">
                        <input type="radio" name="answers[' . $q_num . ']" value="' . $color . '" id="q' . $q_num . '_' . $color . '" style="display: none;">
                        <label for="q' . $q_num . '_' . $color . '" style="cursor: pointer; margin: 0; width: 100%;">
                            ' . $answer_text . '
                        </label>
                    </div>';
                }
                
                echo '
                </div>
            </div>';
        }
        
        echo '
        <div class="text-center mb-5">
            <button type="submit" name="submit_test" class="btn btn-primary btn-lg" id="submit-btn" disabled>
                <i class="bi bi-check-circle"></i> Submit Assessment
            </button>
        </div>
    </form>';
    
} else {
    // Already completed - show message with retake option
    echo '
    <div class="text-center">
        <div class="card">
            <div class="card-body py-5">
                <i class="bi bi-clipboard-check text-info" style="font-size: 4rem;"></i>
                <h2 class="mt-3">Assessment Already Completed</h2>
                <p class="lead">You have already completed the True Colors Team Style Assessment.</p>
                <p class="text-muted">Your results have been submitted for personal review.</p>
                <div class="mt-4">
                    <a href="/staff/personality-test.php?retake=1" class="btn btn-warning me-2">
                        <i class="bi bi-arrow-clockwise"></i> Retake Assessment
                    </a>
                    <a href="/staff/" class="btn btn-primary">Return to Staff Dashboard</a>
                    <a href="/staff/detailed-color-test" class="btn btn-outline-secondary">Try Detailed Color Test</a>
                </div>
            </div>
        </div>
    </div>';
}

echo '</div>';

// Add JavaScript for form interactions
echo '
<script>
$(document).ready(function() {
    let totalQuestions = 5;
    let answeredQuestions = 0;
    
    // Handle answer selection
    $(".answer-option").click(function() {
        let questionCard = $(this).closest(".question-card");
        let questionNum = questionCard.data("question");
        
        // Remove selected state from all options in this question
        questionCard.find(".answer-option").removeClass("selected");
        questionCard.find("input[type=radio]").prop("checked", false);
        
        // Add selected state to clicked option
        $(this).addClass("selected");
        $(this).find("input[type=radio]").prop("checked", true);
        
        // Update progress
        updateProgress();
    });
    
    function updateProgress() {
        answeredQuestions = $("input[type=radio]:checked").length;
        let percentage = (answeredQuestions / totalQuestions) * 100;
        
        $("#progress-bar").css("width", percentage + "%");
        $("#progress-text").text(answeredQuestions + " of " + totalQuestions + " questions answered");
        
        // Enable submit button when all questions answered
        if (answeredQuestions === totalQuestions) {
            $("#submit-btn").prop("disabled", false);
        } else {
            $("#submit-btn").prop("disabled", true);
        }
    }
    
    // Initialize progress
    updateProgress();
});
</script>';

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>