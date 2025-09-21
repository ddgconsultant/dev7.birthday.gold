<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get user data
$user_id = $current_user_data['user_id'];


// Include headers - EXACTLY like tour-list.php
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Content Header Dark Section - MATCHING tour-list.php structure -->
<div class="content-header-dark">
    <div class="container">
        <div class="text-center">
            <h1 class="mb-3"><i class="bi bi-share me-3"></i>Social Post Awards</h1>
            <p class="lead mb-0">Post about Birthday Gold with #birthdaygold and earn allocations</p>
        </div>
    </div>
</div>

<div class="container my-5 pt-5">
    <div class="social-container">
        
            <!-- Left Column: Form and Submission Table -->
            <div class="col-lg-6">
   
          
            </div>
            
            <!-- Right Column: Instructions and Examples -->
            <div class="col-lg-6">
                <div class="card instruction-card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">How It Works</h3>
                        <ol>
                            <li>Create a post on your favorite social media platform</li>
                            <li>Include <strong>#birthdaygold</strong> in your post</li>
                            <li>Make sure your post is public (not private or friends-only)</li>
                            <li>Submit the post URL for verification</li>
                            <li>Earn 1 allocation once verified (limit: 1 per day)</li>
                        </ol>
                        <div class="alert alert-warning">
                            <strong>Important:</strong> Your post must be publicly visible and contain the hashtag <strong>#birthdaygold</strong> to be verified.
                        </div>
                    </div>
                </div>
                
                <div class="card instruction-card">
                    <div class="card-body">
                        <h3 class="card-title">Example Posts</h3>
                        <p class="text-muted">Click any example below to copy it to your clipboard, then customize with your own experience!</p>
                        
                        <div class="example-post" onclick="copyExamplePost(this, 'Just discovered @birthdaygold - getting free birthday rewards from my favorite brands all year long! 🎂🎉 #birthdaygold')">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-twitter-x platform-icon"></i>
                                <div>
                                    <strong>Twitter/X</strong>
                                    <p class="mb-0 example-text">Just discovered @birthdaygold - getting free birthday rewards from my favorite brands all year long! 🎂🎉 #birthdaygold</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="example-post" onclick="copyExamplePost(this, 'Birthday month is even better with Birthday Gold! So many free rewards and special offers 🎁 Check it out at birthday.gold #birthdaygold')">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-facebook platform-icon"></i>
                                <div>
                                    <strong>Facebook</strong>
                                    <p class="mb-0 example-text">Birthday month is even better with Birthday Gold! So many free rewards and special offers 🎁 Check it out at birthday.gold #birthdaygold</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="example-post" onclick="copyExamplePost(this, 'Birthday rewards all year? Yes please! 🎈 Just signed up for Birthday Gold and already got my first rewards! #birthdaygold #birthdayrewards')">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-instagram platform-icon"></i>
                                <div>
                                    <strong>Instagram</strong>
                                    <p class="mb-0 example-text">Birthday rewards all year? Yes please! 🎈 Just signed up for Birthday Gold and already got my first rewards! #birthdaygold #birthdayrewards</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="example-post" onclick="copyExamplePost(this, 'POV: You found Birthday Gold and now get free stuff from restaurants and stores every month 🎂✨ #birthdaygold #birthdayfreebies #rewards')">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-tiktok platform-icon"></i>
                                <div>
                                    <strong>TikTok</strong>
                                    <p class="mb-0 example-text">POV: You found Birthday Gold and now get free stuff from restaurants and stores every month 🎂✨ #birthdaygold #birthdayfreebies #rewards</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?PHP
// Footer - EXACTLY like tour-list.php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>