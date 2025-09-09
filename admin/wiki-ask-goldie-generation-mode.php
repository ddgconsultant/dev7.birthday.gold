<?php
/**
 * Wiki Documentation: Ask Goldie Generation Mode
 */

include('../core/site-controller.php');

// Staff only access
if (!$account->isstaff()) {
    header('Location: /');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask Goldie Generation Mode - Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .wiki-nav {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            position: sticky;
            top: 20px;
        }
        .code-block {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
        }
        .generation-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        .feature-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .badge-enabled {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-staff {
            background-color: #fff3cd;
            color: #856404;
        }
        h2 {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        h2:first-of-type {
            border-top: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Navigation Sidebar -->
            <div class="col-md-3">
                <div class="wiki-nav">
                    <h5 class="mb-3"><i class="bi bi-book"></i> Navigation</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="#overview">Overview</a></li>
                        <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                        <li class="nav-item"><a class="nav-link" href="#generations">Generation Definitions</a></li>
                        <li class="nav-item"><a class="nav-link" href="#language-styles">Language Styles</a></li>
                        <li class="nav-item"><a class="nav-link" href="#testing">Testing & Debugging</a></li>
                        <li class="nav-item"><a class="nav-link" href="#configuration">Configuration</a></li>
                        <li class="nav-item"><a class="nav-link" href="#technical">Technical Details</a></li>
                        <li class="nav-item"><a class="nav-link" href="#troubleshooting">Troubleshooting</a></li>
                    </ul>
                    <hr>
                    <a href="/ask-goldie.php" class="btn btn-primary btn-sm w-100 mb-2">
                        <i class="bi bi-chat-dots"></i> Go to Ask Goldie
                    </a>
                    <a href="/admin/" class="btn btn-secondary btn-sm w-100">
                        <i class="bi bi-arrow-left"></i> Back to Admin
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <h1 class="mb-4">
                    <i class="bi bi-robot"></i> Ask Goldie Generation Mode
                    <span class="feature-badge badge-enabled">ENABLED</span>
                    <span class="feature-badge badge-staff">STAFF FEATURE</span>
                </h1>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> <strong>Feature Status:</strong> 
                    Generation Mode is currently <strong>ENABLED</strong> and automatically adapts Goldie's language based on user age.
                </div>

                <!-- Overview Section -->
                <h2 id="overview">Overview</h2>
                <p>
                    The Ask Goldie Generation Mode is an intelligent feature that automatically detects a user's generation based on their 
                    birthdate and adapts the AI assistant's communication style accordingly. This creates a more personalized and relatable 
                    experience for users across different age groups.
                </p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-check-circle text-success"></i> Key Features</h5>
                                <ul>
                                    <li>Automatic generation detection from birthdate</li>
                                    <li>Adaptive language styles for each generation</li>
                                    <li>Transparent to users (no visible indication)</li>
                                    <li>Staff-only visibility badge</li>
                                    <li>Testing mode for staff</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-gear text-primary"></i> Configuration</h5>
                                <ul>
                                    <li>Database: <code>bg_config</code> table</li>
                                    <li>Config Type: <code>ask_goldie</code></li>
                                    <li>Config Key: <code>generation_mode</code></li>
                                    <li>Current Value: <code>1</code> (Enabled)</li>
                                    <li>Can be toggled on/off</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- How It Works -->
                <h2 id="how-it-works">How It Works</h2>
                <ol>
                    <li><strong>User Authentication:</strong> When a user visits Ask Goldie, the system checks if they're logged in</li>
                    <li><strong>Generation Detection:</strong> If logged in and has a birthdate, the system calculates their generation</li>
                    <li><strong>Style Assignment:</strong> Each generation is mapped to a specific communication style</li>
                    <li><strong>AI Prompt Modification:</strong> The AI receives special instructions for the detected generation</li>
                    <li><strong>Response Generation:</strong> Goldie responds using generation-appropriate language</li>
                    <li><strong>Staff Visibility:</strong> Staff members see a badge indicating the active generation</li>
                </ol>

                <!-- Generation Definitions -->
                <h2 id="generations">Generation Definitions</h2>
                
                <div class="generation-card">
                    <h4><i class="bi bi-stars"></i> Gen Alpha (Born 2013+)</h4>
                    <p><strong>Age Range:</strong> 11 and younger</p>
                    <p><strong>Characteristics:</strong> Digital natives, grew up with tablets/smartphones, highly visual learners</p>
                    <p><strong>Style Code:</strong> <code>simple_fun</code></p>
                </div>

                <div class="generation-card">
                    <h4><i class="bi bi-phone"></i> Gen Z (Born 1997-2012)</h4>
                    <p><strong>Age Range:</strong> 12-27 years old</p>
                    <p><strong>Characteristics:</strong> Social media natives, value authenticity, diverse and inclusive</p>
                    <p><strong>Style Code:</strong> <code>casual_trendy</code></p>
                </div>

                <div class="generation-card">
                    <h4><i class="bi bi-laptop"></i> Millennials (Born 1981-1996)</h4>
                    <p><strong>Age Range:</strong> 28-43 years old</p>
                    <p><strong>Characteristics:</strong> Tech-savvy, value experiences, collaborative</p>
                    <p><strong>Style Code:</strong> <code>friendly_relatable</code></p>
                </div>

                <div class="generation-card">
                    <h4><i class="bi bi-tv"></i> Gen X (Born 1965-1980)</h4>
                    <p><strong>Age Range:</strong> 44-59 years old</p>
                    <p><strong>Characteristics:</strong> Independent, pragmatic, work-life balance focused</p>
                    <p><strong>Style Code:</strong> <code>straightforward</code></p>
                </div>

                <div class="generation-card">
                    <h4><i class="bi bi-briefcase"></i> Baby Boomers (Born 1946-1964)</h4>
                    <p><strong>Age Range:</strong> 60-78 years old</p>
                    <p><strong>Characteristics:</strong> Work-oriented, value loyalty, prefer formal communication</p>
                    <p><strong>Style Code:</strong> <code>professional</code></p>
                </div>

                <div class="generation-card">
                    <h4><i class="bi bi-book"></i> Silent Generation (Born before 1946)</h4>
                    <p><strong>Age Range:</strong> 79+ years old</p>
                    <p><strong>Characteristics:</strong> Traditional values, respect hierarchy, formal communicators</p>
                    <p><strong>Style Code:</strong> <code>formal</code></p>
                </div>

                <!-- Language Styles -->
                <h2 id="language-styles">Language Styles</h2>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Generation</th>
                                <th>Style</th>
                                <th>Example Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Gen Alpha</strong></td>
                                <td>Simple, fun, educational</td>
                                <td>"That's super cool! Birthday Gold helps you celebrate birthdays in awesome ways! 🎉"</td>
                            </tr>
                            <tr>
                                <td><strong>Gen Z</strong></td>
                                <td>Casual, trendy, emojis</td>
                                <td>"No cap, Birthday Gold is fire! We're all about making birthdays absolutely slay 🎂✨"</td>
                            </tr>
                            <tr>
                                <td><strong>Millennials</strong></td>
                                <td>Friendly, relatable</td>
                                <td>"Hey there! Birthday Gold is here to help you celebrate in style - you deserve it!"</td>
                            </tr>
                            <tr>
                                <td><strong>Gen X</strong></td>
                                <td>Direct, practical</td>
                                <td>"Birthday Gold provides birthday celebration services. Here's what you need to know."</td>
                            </tr>
                            <tr>
                                <td><strong>Baby Boomers</strong></td>
                                <td>Professional, respectful</td>
                                <td>"Thank you for your interest. Birthday Gold offers comprehensive birthday services."</td>
                            </tr>
                            <tr>
                                <td><strong>Silent Gen</strong></td>
                                <td>Formal, courteous</td>
                                <td>"Good day. I would be pleased to assist you with Birthday Gold's services."</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Testing Section -->
                <h2 id="testing">Testing & Debugging</h2>
                
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Staff Only:</strong> 
                    These testing features are only available to staff members.
                </div>

                <h4>Force Generation Mode</h4>
                <p>Staff can force a specific generation for testing purposes using URL parameters:</p>
                
                <div class="code-block">
                    <strong>Force Gen Z:</strong><br>
                    /ask-goldie.php?force_gen=z<br>
                    /ask-goldie.php?force_gen=genz<br><br>
                    
                    <strong>Force Millennial:</strong><br>
                    /ask-goldie.php?force_gen=millennial<br>
                    /ask-goldie.php?force_gen=mil<br><br>
                    
                    <strong>Force Gen X:</strong><br>
                    /ask-goldie.php?force_gen=x<br>
                    /ask-goldie.php?force_gen=genx<br><br>
                    
                    <strong>Force Baby Boomer:</strong><br>
                    /ask-goldie.php?force_gen=boomer<br><br>
                    
                    <strong>Force Silent Generation:</strong><br>
                    /ask-goldie.php?force_gen=silent<br><br>
                    
                    <strong>Force Gen Alpha:</strong><br>
                    /ask-goldie.php?force_gen=alpha<br><br>
                    
                    <strong>Clear Forced Mode:</strong><br>
                    /ask-goldie.php?clear_force_gen=1
                </div>

                <h4>Visual Indicators</h4>
                <ul>
                    <li>Staff see a blue badge next to "Staff Mode" showing the active generation</li>
                    <li>When forced, the badge shows "(Forced)" after the generation name</li>
                    <li>Regular users see no indication of generation mode</li>
                </ul>

                <h4>Quick Test Links</h4>
                <div class="btn-group" role="group">
                    <a href="/ask-goldie.php?force_gen=z" class="btn btn-outline-primary" target="_blank">Test Gen Z</a>
                    <a href="/ask-goldie.php?force_gen=millennial" class="btn btn-outline-primary" target="_blank">Test Millennial</a>
                    <a href="/ask-goldie.php?force_gen=x" class="btn btn-outline-primary" target="_blank">Test Gen X</a>
                    <a href="/ask-goldie.php?force_gen=boomer" class="btn btn-outline-primary" target="_blank">Test Boomer</a>
                    <a href="/ask-goldie.php?clear_force_gen=1" class="btn btn-outline-danger" target="_blank">Clear Force</a>
                </div>

                <!-- Configuration Section -->
                <h2 id="configuration">Configuration</h2>
                
                <h4>Database Configuration</h4>
                <div class="code-block">
                    Table: bg_config<br>
                    config_type: 'ask_goldie'<br>
                    config_key: 'generation_mode'<br>
                    config_value: '1' (enabled) or '0' (disabled)<br>
                    config_data: 'Enable generation-specific language in Ask Goldie based on user age'
                </div>

                <h4>Enable/Disable Generation Mode</h4>
                <div class="code-block">
                    -- Enable Generation Mode<br>
                    UPDATE bg_config <br>
                    SET config_value = '1' <br>
                    WHERE config_type = 'ask_goldie' AND config_key = 'generation_mode';<br><br>
                    
                    -- Disable Generation Mode<br>
                    UPDATE bg_config <br>
                    SET config_value = '0' <br>
                    WHERE config_type = 'ask_goldie' AND config_key = 'generation_mode';
                </div>

                <h4>Setup Script</h4>
                <p>Run the setup script to initialize or reset the configuration:</p>
                <div class="code-block">
                    php /admin_actions/setup_goldie_generation_mode.php
                </div>

                <!-- Technical Details -->
                <h2 id="technical">Technical Details</h2>
                
                <h4>File Locations</h4>
                <ul>
                    <li><strong>Main Implementation:</strong> <code>/ask-goldie.php</code></li>
                    <li><strong>Setup Script:</strong> <code>/admin_actions/setup_goldie_generation_mode.php</code></li>
                    <li><strong>This Documentation:</strong> <code>/admin/wiki-ask-goldie-generation-mode.php</code></li>
                </ul>

                <h4>Session Variables</h4>
                <div class="code-block">
                    $_SESSION['user_generation'] - Stores the detected/forced generation<br>
                    $_SESSION['generation_style'] - Stores the style code for AI prompts<br>
                    $_SESSION['forced_generation'] - Boolean indicating if generation is forced
                </div>

                <h4>Implementation Flow</h4>
                <pre class="code-block">
1. User visits /ask-goldie.php
2. System checks bg_config for generation_mode status
3. If enabled:
   a. Check for staff force_gen parameter
   b. If not forced, detect from user birthdate
   c. Store generation in session
4. When processing message:
   a. Retrieve generation style from session
   b. Add generation-specific instructions to AI prompt
5. Display generation badge for staff users
                </pre>

                <h4>AI Prompt Modification</h4>
                <p>The system adds generation-specific instructions to the AI prompt based on the detected generation:</p>
                <div class="code-block">
                    // Example for Gen Z<br>
                    GENERATION STYLE (Gen Z):<br>
                    - Use casual, trendy language with modern slang appropriately<br>
                    - Include emojis occasionally 🎉 🎂<br>
                    - Be energetic and enthusiastic<br>
                    - Use phrases like 'no cap', 'it's giving', 'slay', 'fire' when natural<br>
                    - Keep it real and authentic
                </div>

                <!-- Troubleshooting -->
                <h2 id="troubleshooting">Troubleshooting</h2>
                
                <div class="accordion" id="troubleshootingAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#trouble1">
                                Generation not being detected
                            </button>
                        </h2>
                        <div id="trouble1" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <strong>Possible causes:</strong>
                                <ul>
                                    <li>User not logged in</li>
                                    <li>User has no birthdate in their profile</li>
                                    <li>Generation mode is disabled in bg_config</li>
                                </ul>
                                <strong>Solution:</strong> Check user's birthdate field and ensure generation_mode = 1 in bg_config
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble2">
                                Forced generation not working
                            </button>
                        </h2>
                        <div id="trouble2" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <strong>Possible causes:</strong>
                                <ul>
                                    <li>Not logged in as staff</li>
                                    <li>Incorrect parameter format</li>
                                    <li>Session already has forced generation</li>
                                </ul>
                                <strong>Solution:</strong> Clear forced generation first with ?clear_force_gen=1, then set new force
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trouble3">
                                Badge not showing for staff
                            </button>
                        </h2>
                        <div id="trouble3" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <strong>Possible causes:</strong>
                                <ul>
                                    <li>Not logged in as staff</li>
                                    <li>Generation mode disabled</li>
                                    <li>No generation detected</li>
                                </ul>
                                <strong>Solution:</strong> Ensure you're logged in as staff and generation mode is enabled
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-5 pt-4 border-top text-muted">
                    <p>
                        <i class="bi bi-info-circle"></i> 
                        Last Updated: <?php echo date('F j, Y'); ?> | 
                        Feature Version: 1.0 | 
                        Status: <span class="badge bg-success">Active</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>