<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Staff-only access is already handled by site-controller.php

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
$pagetitle = 'Detailed Color Personality Test';

// Check if user has already taken the test
$existing_sql = "SELECT * FROM bg_user_attributes 
                WHERE user_id = :user_id 
                AND type = 'personality_test' 
                AND status = 'active'
                ORDER BY create_dt DESC 
                LIMIT 1";
$existing_result = $database->getrow($existing_sql, ['user_id' => $current_user_data['user_id']]);

// Show results if requested
$show_results = isset($_GET['results']) || $existing_result;

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted() && isset($_POST['submit_test'])) {
    $answers = $_POST['answers'] ?? [];
    
    // Calculate scores for each color
    $scores = [
        'red' => 0,
        'blue' => 0,
        'green' => 0,
        'yellow' => 0
    ];
    
    foreach ($answers as $answer) {
        if (isset($scores[$answer])) {
            $scores[$answer]++;
        }
    }
    
    // Determine primary and secondary colors
    arsort($scores);
    $colors = array_keys($scores);
    $primary_color = $colors[0];
    $secondary_color = $colors[1];
    
    // Prepare result data
    $result_data = [
        'scores' => $scores,
        'primary_color' => $primary_color,
        'secondary_color' => $secondary_color,
        'primary_score' => $scores[$primary_color],
        'secondary_score' => $scores[$secondary_color],
        'test_date' => date('Y-m-d H:i:s'),
        'answers' => $answers
    ];
    
    // Deactivate old results
    if ($existing_result) {
        $database->query(
            "UPDATE bg_user_attributes SET status = 'inactive' WHERE attribute_id = :id",
            ['id' => $existing_result['attribute_id']]
        );
    }
    
    // Store new results - use description field for JSON, value for primary score
    $insert_sql = "INSERT INTO bg_user_attributes 
                  (user_id, type, name, value, description, string_value, status, create_dt) 
                  VALUES 
                  (:user_id, 'personality_test', 'color_personality', :value, :description, :string_value, 'active', NOW())";
    
    $database->query($insert_sql, [
        'user_id' => $current_user_data['user_id'],
        'value' => $scores[$primary_color], // Store primary color score as integer
        'description' => json_encode($result_data), // Store full JSON in description
        'string_value' => "Primary: {$primary_color}, Secondary: {$secondary_color}" // Summary in string_value
    ]);
    
    // Redirect to results
    header('Location: /staff/personality-test.php?results=1');
    exit;
}

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$additionalstyles .= '
<style>
.personality-test {
    max-width: 800px;
    margin: 0 auto;
    padding-bottom: 100px; /* Add bottom padding */
}
.question-card {
    margin-bottom: 25px;
    border-left: 4px solid #0d6efd;
    transition: all 0.3s ease;
}
.question-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
.answer-option {
    padding: 12px;
    margin: 8px 0;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.answer-option:hover {
    background: #f8f9fa;
    border-color: #0d6efd;
}
.answer-option input[type="radio"] {
    margin-right: 10px;
}
.answer-option.selected {
    background: #e7f3ff;
    border-color: #0d6efd;
}

/* Color badges */
.color-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.color-badge.red {
    background: #ff4444;
    color: white;
}
.color-badge.blue {
    background: #0088ff;
    color: white;
}
.color-badge.green {
    background: #00c851;
    color: white;
}
.color-badge.yellow {
    background: #ffbb33;
    color: #333;
}

/* Results section */
.result-card {
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 20px;
}
.primary-result {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.trait-list {
    list-style: none;
    padding: 0;
}
.trait-list li {
    padding: 8px 0;
    padding-left: 25px;
    position: relative;
}
.trait-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    font-weight: bold;
}

.progress-indicator {
    margin-bottom: 30px;
}
.progress-step {
    flex: 1;
    text-align: center;
    padding: 10px;
    background: #f8f9fa;
    margin: 0 2px;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}
.progress-step.active {
    background: #0d6efd;
    color: white;
    transform: scale(1.05);
}
.progress-step.completed {
    background: #28a745;
    color: white;
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
        <h1><i class="fas fa-palette"></i> Color Personality Test</h1>
        <p class="lead">Discover your personality color and understand your work style</p>
    </div>
</div>';

echo '<div class="container mt-4 personality-test">';

if (!$show_results) {
    // Show the test
    echo '
    <div class="card mb-4">
        <div class="card-body">
            <h5>About This Test</h5>
            <p>This quick personality test will help you understand your dominant personality traits based on four colors:</p>
            <div class="row text-center mb-3">
                <div class="col-md-3">
                    <span class="color-badge red">Red</span>
                    <p class="mt-2 small">Action-oriented, decisive</p>
                </div>
                <div class="col-md-3">
                    <span class="color-badge blue">Blue</span>
                    <p class="mt-2 small">Analytical, precise</p>
                </div>
                <div class="col-md-3">
                    <span class="color-badge green">Green</span>
                    <p class="mt-2 small">Supportive, patient</p>
                </div>
                <div class="col-md-3">
                    <span class="color-badge yellow">Yellow</span>
                    <p class="mt-2 small">Creative, enthusiastic</p>
                </div>
            </div>
            <p class="text-muted">Answer honestly for the most accurate results. There are no right or wrong answers!</p>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div class="progress mb-4" style="height: 25px;">
        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
    </div>
    <p class="text-center text-muted" id="progress-text">0 of 12 questions answered</p>
    
    <form method="POST" id="personality-test-form">
        ' . $display->input_csrftoken();
        
        $questions = [
            1 => [
                'question' => 'When starting a new project, I prefer to:',
                'answers' => [
                    'red' => 'Jump right in and figure it out as I go',
                    'blue' => 'Research thoroughly and create a detailed plan',
                    'green' => 'Collaborate with others and ensure everyone is comfortable',
                    'yellow' => 'Brainstorm creative ideas and explore possibilities'
                ]
            ],
            2 => [
                'question' => 'In meetings, I am most likely to:',
                'answers' => [
                    'red' => 'Push for quick decisions and action items',
                    'blue' => 'Ask detailed questions and analyze data',
                    'green' => 'Ensure everyone has a chance to contribute',
                    'yellow' => 'Share enthusiasm and inspire the team'
                ]
            ],
            3 => [
                'question' => 'When facing a conflict, I tend to:',
                'answers' => [
                    'red' => 'Address it directly and resolve it quickly',
                    'blue' => 'Analyze the situation objectively and find logical solutions',
                    'green' => 'Seek harmony and find compromises',
                    'yellow' => 'Use humor and optimism to diffuse tension'
                ]
            ],
            4 => [
                'question' => 'My ideal work environment is:',
                'answers' => [
                    'red' => 'Fast-paced with clear goals and challenges',
                    'blue' => 'Organized, quiet, and focused',
                    'green' => 'Collaborative and supportive',
                    'yellow' => 'Dynamic, fun, and social'
                ]
            ],
            5 => [
                'question' => 'I feel most productive when:',
                'answers' => [
                    'red' => 'I have control and can make quick decisions',
                    'blue' => 'I have all the information and time to think',
                    'green' => 'The team is working well together',
                    'yellow' => 'I am working on something creative and exciting'
                ]
            ],
            6 => [
                'question' => 'Others would describe me as:',
                'answers' => [
                    'red' => 'Direct, confident, and results-driven',
                    'blue' => 'Logical, thorough, and detail-oriented',
                    'green' => 'Patient, reliable, and supportive',
                    'yellow' => 'Enthusiastic, optimistic, and fun'
                ]
            ],
            7 => [
                'question' => 'When learning something new, I prefer:',
                'answers' => [
                    'red' => 'Practical, hands-on experience',
                    'blue' => 'Detailed documentation and structured learning',
                    'green' => 'Group learning and discussion',
                    'yellow' => 'Interactive and engaging activities'
                ]
            ],
            8 => [
                'question' => 'My communication style is:',
                'answers' => [
                    'red' => 'Brief, direct, and to the point',
                    'blue' => 'Detailed, precise, and factual',
                    'green' => 'Warm, friendly, and considerate',
                    'yellow' => 'Animated, expressive, and storytelling'
                ]
            ],
            9 => [
                'question' => 'I am motivated by:',
                'answers' => [
                    'red' => 'Competition, challenges, and achievements',
                    'blue' => 'Accuracy, quality, and expertise',
                    'green' => 'Helping others and team success',
                    'yellow' => 'Recognition, variety, and new experiences'
                ]
            ],
            10 => [
                'question' => 'Under stress, I tend to:',
                'answers' => [
                    'red' => 'Become more demanding and impatient',
                    'blue' => 'Withdraw and overthink details',
                    'green' => 'Avoid confrontation and become indecisive',
                    'yellow' => 'Become scattered and overly optimistic'
                ]
            ],
            11 => [
                'question' => 'In my free time, I enjoy:',
                'answers' => [
                    'red' => 'Competitive sports or challenging activities',
                    'blue' => 'Reading, puzzles, or strategic games',
                    'green' => 'Spending quality time with family and friends',
                    'yellow' => 'Social events, parties, or creative hobbies'
                ]
            ],
            12 => [
                'question' => 'My approach to deadlines is:',
                'answers' => [
                    'red' => 'Get it done fast and move on to the next task',
                    'blue' => 'Plan carefully to ensure quality and accuracy',
                    'green' => 'Work steadily and ask for help if needed',
                    'yellow' => 'Work best under pressure with bursts of creativity'
                ]
            ]
        ];
        
        foreach ($questions as $num => $q) {
            echo '
            <div class="card question-card">
                <div class="card-body">
                    <h5 class="card-title text-muted" style="font-size: 0.9rem; font-weight: 400;">Question ' . $num . ' of 12</h5>
                    <p class="card-text fw-bold">' . $q['question'] . '</p>
                    <div class="answer-options">';
            
            // Randomize answer order
            $answers = $q['answers'];
            $keys = array_keys($answers);
            shuffle($keys);
            
            foreach ($keys as $color) {
                echo '
                        <div class="answer-option d-flex align-items-center">
                            <input type="radio" name="answers[' . $num . ']" value="' . $color . '" required>
                            <span class="ms-2">' . $answers[$color] . '</span>
                        </div>';
            }
            
            echo '
                    </div>
                </div>
            </div>';
        }
        
        echo '
        <div class="text-center mt-4">
            <button type="submit" name="submit_test" id="submit-btn" class="btn btn-lg btn-secondary" disabled>
                <i class="fas fa-check-circle"></i> Submit Test
            </button>
        </div>
    </form>';
    
} else {
    // Show results
    if ($existing_result) {
        $result_data = json_decode($existing_result['description'], true); // Read JSON from description field
        $primary_color = $result_data['primary_color'];
        $secondary_color = $result_data['secondary_color'];
        $scores = $result_data['scores'];
        
        // Color descriptions
        $color_info = [
            'red' => [
                'title' => 'Red - The Director',
                'description' => 'You are action-oriented, decisive, and results-driven. You thrive on challenges and competition.',
                'strengths' => [
                    'Natural leader',
                    'Quick decision maker',
                    'Goal-oriented',
                    'Direct communicator',
                    'Takes initiative'
                ],
                'work_style' => 'You prefer a fast-paced environment where you can take charge and see immediate results. You value efficiency and getting things done.',
                'tips' => 'Remember to slow down occasionally to consider others perspectives and build stronger relationships with your team.'
            ],
            'blue' => [
                'title' => 'Blue - The Analyst',
                'description' => 'You are analytical, precise, and detail-oriented. You value accuracy and quality in everything you do.',
                'strengths' => [
                    'Attention to detail',
                    'Systematic thinker',
                    'Quality focused',
                    'Problem solver',
                    'Organized and methodical'
                ],
                'work_style' => 'You excel in environments that allow for careful planning and thorough analysis. You prefer having all the facts before making decisions.',
                'tips' => 'Try to balance perfectionism with practical deadlines and remember that sometimes good enough is perfectly acceptable.'
            ],
            'green' => [
                'title' => 'Green - The Supporter',
                'description' => 'You are patient, reliable, and team-oriented. You value harmony and helping others succeed.',
                'strengths' => [
                    'Great listener',
                    'Team player',
                    'Patient and calm',
                    'Loyal and dependable',
                    'Consensus builder'
                ],
                'work_style' => 'You thrive in collaborative environments where you can support others and build strong relationships. You prefer stability and clear expectations.',
                'tips' => 'Do not be afraid to assert yourself and share your valuable ideas. Your perspective is important to the team success.'
            ],
            'yellow' => [
                'title' => 'Yellow - The Socializer',
                'description' => 'You are enthusiastic, creative, and people-oriented. You bring energy and optimism to everything you do.',
                'strengths' => [
                    'Creative thinker',
                    'Excellent communicator',
                    'Inspiring and motivating',
                    'Adaptable and flexible',
                    'Builds rapport easily'
                ],
                'work_style' => 'You excel in dynamic environments that offer variety and social interaction. You bring creativity and enthusiasm to your work.',
                'tips' => 'Focus on follow-through and attention to detail. Channel your enthusiasm into completing projects, not just starting them.'
            ]
        ];
        
        echo '
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card result-card primary-result">
                    <h2 class="mb-3">Your Primary Color</h2>
                    <h3><span class="color-badge ' . $primary_color . '">' . $primary_color . '</span></h3>
                    <h4 class="mt-3">' . $color_info[$primary_color]['title'] . '</h4>
                    <p class="lead">' . $color_info[$primary_color]['description'] . '</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-star text-warning"></i> Your Strengths</h5>
                        <ul class="trait-list">';
                        foreach ($color_info[$primary_color]['strengths'] as $strength) {
                            echo '<li>' . $strength . '</li>';
                        }
                        echo '</ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-briefcase text-primary"></i> Your Work Style</h5>
                        <p>' . $color_info[$primary_color]['work_style'] . '</p>
                        <hr>
                        <p class="text-muted small"><strong>Tip:</strong> ' . $color_info[$primary_color]['tips'] . '</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-body">
                <h5>Your Complete Color Profile</h5>
                <p class="text-muted">This shows how strongly you align with each personality color:</p>';
                
                $max_score = max($scores);
                foreach ($scores as $color => $score) {
                    $percentage = $max_score > 0 ? ($score / $max_score) * 100 : 0;
                    $bg_color = $color == 'red' ? '#ff4444' : 
                               ($color == 'blue' ? '#0088ff' : 
                               ($color == 'green' ? '#00c851' : '#ffbb33'));
                    echo '
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span class="text-uppercase fw-bold">' . $color . '</span>
                        <span>' . $score . ' points</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar" 
                             style="width: ' . $percentage . '%; background-color: ' . $bg_color . '">
                        </div>
                    </div>
                </div>';
                }
                
                echo '
                <hr>
                <p class="text-muted">
                    <strong>Secondary Color:</strong> 
                    <span class="color-badge ' . $secondary_color . '">' . $secondary_color . '</span>
                    - ' . $color_info[$secondary_color]['title'] . '
                </p>
            </div>
        </div>
        
        <div class="text-center mt-4 mb-5">
            <a href="/staff/personality-test" class="btn btn-primary">
                <i class="fas fa-redo"></i> Retake Test
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Results
            </button>
        </div>';
    }
}

echo '</div>'; // Close container

// Add JavaScript here after content
echo '
<script>
$(document).ready(function() {
    // Make entire answer option clickable
    $(document).on("click", ".answer-option", function(e) {
        // Prevent double-firing if clicking directly on radio
        if (e.target.type !== "radio") {
            const radio = $(this).find("input[type=radio]");
            radio.prop("checked", true);
            radio.trigger("change");
        }
    });
    
    // Handle radio button change event  
    $(document).on("change", "input[type=radio]", function() {
        // Remove selected class from all options in this question
        $(this).closest(".answer-options").find(".answer-option").removeClass("selected");
        // Add selected class to the parent option
        $(this).closest(".answer-option").addClass("selected");
        // Update progress
        updateProgress();
    });
    
    function updateProgress() {
        const totalQuestions = $(".question-card").length;
        const answeredQuestions = $("input[type=radio]:checked").length;
        const progress = Math.round((answeredQuestions / totalQuestions) * 100);
        
        console.log("Questions:", totalQuestions, "Answered:", answeredQuestions, "Progress:", progress);
        
        $("#progress-bar").css("width", progress + "%").attr("aria-valuenow", progress);
        $("#progress-text").text(answeredQuestions + " of " + totalQuestions + " questions answered");
        
        if (answeredQuestions === totalQuestions) {
            $("#submit-btn").removeClass("btn-secondary disabled").addClass("btn-success").prop("disabled", false);
        } else {
            $("#submit-btn").removeClass("btn-success").addClass("btn-secondary disabled").prop("disabled", true);
        }
    }
    
    // Initial progress check
    updateProgress();
    
    // Also update on direct radio click
    $("input[type=radio]").on("click", function() {
        updateProgress();
    });
});
</script>';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>