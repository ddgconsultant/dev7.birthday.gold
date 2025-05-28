<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



$pagedata['pagetitle']='Best Birthday Freebies & Rewards Online - Birthday Gold';
$pagedata['metakeywords']='Birthday Treat Rewards, Best Birthday Rewards, Best Birthday Freebies, Best Birthday Rewards Online';
$pagedata['metadescriptions']='Enjoy the Best Birthday Rewards Online! Unlock Birthday Treat Rewards & Best Birthday Freebies for an unforgettable celebration. Sign up today!';



include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$additionalstyles .= '
<style>
.icon-circle { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.icon-circle i { font-size: 24px; color: white; }
.bg-amber-500 { background-color: #f59e0b; }
.bg-blue-500 { background-color: #3b82f6; }
.bg-green-500 { background-color: #22c55e; }
</style > <style > .how-it-works-header { text-align: center; margin-bottom: 3rem; }
.how-it-works-header h1 { font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem; }
.how-it-works-header p { color: #4a5568; font-size: 1.25rem; margin-bottom: 2rem; }
.sign-up-btn { background-color: #f59e0b; border: none; padding: 0.75rem 2rem; border-radius: 0.5rem; color: white; font-weight: bold; }
/* Card styles with gradients */
.step-card { border-radius: 1rem; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-bottom: 0; }
.step-card:hover { transform: translateY(-5px); box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15); }
/* Gradient backgrounds for each step */
.step-1 { background: linear-gradient(180deg, #fff9e7 0%, #ffffff 100%); }
.step-2 { background: linear-gradient(180deg, #f0f7ff 0%, #ffffff 100%); }
.step-3 { background: linear-gradient(180deg, #f0fff4 0%, #ffffff 100%); }
/* Matching bullet colors for each step */
.step-1 .step-content li::before { color: #f59e0b;     /* amber-500 */ }
.step-2 .step-content li::before { color: #8b5cf6;     /* purple-500 */ }
.step-3 .step-content li::before { color: #3b82f6;     /* blue-500 */ }
/* Fix double bullet issue and match orange dot style */
/* Base bullet styling */
.step-content ul { list-style: none; padding: 0; margin: 0; }
.step-content li { position: relative; padding-left: 20px; margin-bottom: 1rem; }
/* Common bullet properties */
.step-content li::before { content: ""; position: absolute; left: 0; top: 10px; width: 8px; height: 8px; border-radius: 50%; }
/* Step-specific bullet colors */
.step-1 .step-content li::before { background-color: #f59e0b;     /* amber-500 */ }
.step-2 .step-content li::before { background-color: #3b82f6;     /* blue-500 */ }
.step-3 .step-content li::before { background-color: #22c55e;     /* green-500 */ }
</style >
';

echo '


<!-- How It Works Start -->
<div class="container main-content">
    <div class="how-it-works-header">
    <h1>Best Birthday Freebies & Rewards Online</h1>
        <h2>How Birthday.Gold Works</h2>
        <p>Turn your birthday into a celebration with just three easy steps</p>
        <a href="/signup" class="sign-up-btn">Sign Up Now</a>
    </div>

    <div class="row steps-container">
        <!-- Step 1 -->
        <div class="col-md-4">
            <div class="step-card step-1 h-100">
                <div class="d-flex align-items-center mb-4">
                    <div class="icon-circle bg-amber-500">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <h2 class="fs-2 fw-bold ms-3">Step 1: Join</h2>
                </div>
                <div class="step-content">
                    <ul>
                        <li>Choose your plan: Free (3 rewards) or Premium (30 rewards/year)</li>
                        <li>Enter your basic information and birth date</li>
                        <li>Fill in preferences to ensure the best reward matches</li>
                        <li>Share any dietary restrictions for food-related rewards</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="col-md-4">
            <div class="step-card step-2 h-100">
                <div class="d-flex align-items-center mb-4">
                    <div class="icon-circle bg-blue-500">
                        <i class="bi bi-stars"></i>
                    </div>
                    <h2 class="fs-2 fw-bold ms-3">Step 2: Select</h2>
                </div>
                <div class="step-content">
                    <ul>
                        <li>Browse from over ' . $website['numberofbiz'] . '+ businesses and growing</li>
                        <li>Choose rewards that interest you within your plan limit</li>
                        <li>We automatically handle the enrollment process</li>
                        <li>Get confirmation when each enrollment is complete</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="col-md-4">
            <div class="step-card step-3 h-100">
                <div class="d-flex align-items-center mb-4">
                    <div class="icon-circle bg-green-500">
                        <i class="bi bi-gift"></i>
                    </div>
                    <h2 class="fs-2 fw-bold ms-3">Step 3: Celebrate</h2>
                </div>
                <div class="step-content">
                    <ul>
                        <li>Receive timely reminders before your birthday</li>
                        <li>Celebrate with exclusive perks, surprises, and special treats</li>
                        <li>Track your available rewards in one place</li>
                        <li>Easily manage your reward preferences year-round</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- How It Works End -->
';


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
