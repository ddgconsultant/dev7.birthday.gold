<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = 'Personality Results';

// Get specific user result if requested
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$view_user = null;
$result_data = null;

if ($user_id) {
    // Get user details
    $user_sql = "SELECT user_id, first_name, last_name, email FROM bg_users WHERE user_id = :user_id";
    $view_user = $database->getrow($user_sql, ['user_id' => $user_id]);
    
    if ($view_user) {
        // Get their test results
        $result_sql = "SELECT * FROM bg_user_attributes 
                      WHERE user_id = :user_id 
                      AND type = 'true_colors_test' 
                      AND status = 'active'
                      ORDER BY create_dt DESC 
                      LIMIT 1";
        $result = $database->getrow($result_sql, ['user_id' => $user_id]);
        
        if ($result) {
            $result_data = json_decode($result['description'], true);
        }
    }
}

// Define questions for reference
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

.color-badge {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
}

.answer-detail {
    background: #f8f9fa;
    border-left: 4px solid var(--color);
    padding: 1rem;
    margin: 0.5rem 0;
    border-radius: 0 8px 8px 0;
}

.individual-result {
    max-width: 1000px;
    margin: 0 auto;
}
</style>';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="content-header-admin">
    <div class="container text-center">
        <h1><i class="bi bi-palette"></i> Personality Results</h1>
        <p class="lead">Individual staff personality assessment details</p>
    </div>
</div>

<div class="container mt-4">';

// Debug output
if ($user_id && !$view_user) {
    echo '<div class="alert alert-danger">User not found with ID: ' . $user_id . '</div>';
} elseif ($view_user && !$result_data) {
    echo '<div class="alert alert-warning">No True Colors test results found for ' . htmlspecialchars($view_user['first_name'] . ' ' . $view_user['last_name']) . '</div>';
}

if ($view_user && $result_data) {
    // Show individual result detail
    $primary = $true_colors[$result_data['primary_color']];
    $secondary = $true_colors[$result_data['secondary_color']];
    
    echo '
    
    <div class="container mt-4 mb-5">
        <div class="row mb-3">
            <div class="col-12 text-end">
                <a href="/admin/personality-summary.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Summary
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card" style="background: #f8f9fa; border: 3px solid #0d6efd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(13, 110, 253, 0.1);">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-1"><i class="bi bi-person-badge"></i> ' . htmlspecialchars($view_user['first_name'] . ' ' . $view_user['last_name']) . '</h3>
                            <p class="mb-2">' . htmlspecialchars($view_user['email']) . '</p>
                            <small class="text-muted">Assessment completed: ' . date('M j, Y g:i A', strtotime($result_data['test_date'])) . '</small>
                        </div>
                        <div class="col-md-4 text-end">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="result-card" style="--color: ' . $primary['color'] . '">
            <div class="color-header">
                <div class="color-circle"></div>
                <div>
                    <h3>' . $primary['name'] . ' - ' . $primary['title'] . '</h3>
                    <p class="mb-0 text-muted"><strong>Primary Color</strong></p>
                </div>
            </div>
            <p>' . $primary['description'] . '</p>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Key Strengths:</h5>
                    <ul>';
    foreach ($primary['strengths'] as $strength) {
        echo '<li>' . $strength . '</li>';
    }
    echo '
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Communication Style:</h5>
                    <p>' . $primary['communication'] . '</p>
                    
                    <h5>Motivation:</h5>
                    <p>' . $primary['motivation'] . '</p>
                </div>
            </div>
        </div>
        
        <div class="result-card" style="--color: ' . $secondary['color'] . '">
            <div class="color-header">
                <div class="color-circle"></div>
                <div>
                    <h3>' . $secondary['name'] . ' - ' . $secondary['title'] . '</h3>
                    <p class="mb-0 text-muted"><strong>Secondary Color</strong></p>
                </div>
            </div>
            <p>' . $secondary['description'] . '</p>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5>Assessment Details</h5>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Score Breakdown:</h6>';
                        
    foreach ($true_colors_order as $color) {
        $score = $result_data['scores'][$color] ?? 0;
        $percentage = ($score / 5) * 100;
        $color_info = $true_colors[$color];
        $text_color = $color == 'gold' ? '#333' : 'white';
        
        echo '
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="color-badge" style="background: ' . $color_info['color'] . '; color: ' . $text_color . '">' . $color_info['name'] . '</span>
                            <span><strong>' . $score . ' / 5</strong> (' . $percentage . '%)</span>
                        </div>';
    }
    
    echo '
                    </div>
                    <div class="col-md-6">
                        <h6>Under Stress:</h6>
                        <p><strong>' . $primary['name'] . ':</strong> ' . $primary['stress_response'] . '</p>
                        <p><strong>' . $secondary['name'] . ':</strong> ' . $secondary['stress_response'] . '</p>
                    </div>
                </div>
                
                <hr>
                
                <h6>Individual Question Responses:</h6>';
                
    // Show each question and their selected answer
    if (isset($result_data['answers'])) {
        foreach ($result_data['answers'] as $question_num => $selected_color) {
            $question_data = $questions[$question_num];
            $selected_answer = $question_data['answers'][$selected_color];
            $color_info = $true_colors[$selected_color];
            $text_color = $selected_color == 'gold' ? '#333' : 'white';
            
            echo '
                <div class="answer-detail mb-3" style="border-left-color: ' . $color_info['color'] . ';">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong>Question ' . $question_num . ':</strong>
                        <span class="color-badge" style="background: ' . $color_info['color'] . '; color: ' . $text_color . ';">
                            ' . $color_info['name'] . '
                        </span>
                    </div>
                    <p class="mb-2 text-muted">' . $question_data['question'] . '</p>
                    <p class="mb-0"><strong>Selected:</strong> ' . $selected_answer . '</p>
                </div>';
        }
    } else {
        echo '
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i> 
                    Individual answers not available for this assessment. Only score summary is shown above.
                </div>';
    }
    
    echo '
            </div>
        </div>
    </div>';
    
} else {
    // Show message if no user specified
    echo '
    <div class="text-center">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            Please select a staff member from the <a href="/admin/personality-summary.php">Personality Summary</a> to view their results.
        </div>
    </div>';
}

echo '</div>';

$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>