<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
// always use single PHP BLOCK, ECHO block statements. 
// Do not use Short Echo Tags, Short Tags, Multiple PHP Tags or Nowdoc/Heredoc syntax
// access to /myaccount and /admin pages are controlled by the site-controller.php file.

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
.blog-content h2 {
    color: #333;
    margin-top: 2rem;
    margin-bottom: 1.5rem;
}
.blog-content p {
    line-height: 1.7;
    margin-bottom: 1.5rem;
}
.blog-content ul {
    margin-bottom: 2rem;
}
.blog-content .cta-section {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 2rem;
    margin: 2rem 0;
}
</style>
';

echo '    
<div class="container main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Celebrate Your Special Day with Free Birthday Rewards Enrollment</h1>
        <a href="/" class="btn btn-sm btn-outline-secondary">Home</a>
    </div>
';

echo '
    <div class="card">
        <div class="card-body blog-content">
            <div class="lead mb-4">
                <p>Who doesn\'t love being treated like royalty on their birthday? Imagine receiving exclusive perks, discounts, and surprises to make your special day even more memorable—all for free! At Birthday Gold, we believe that birthdays are meant to be celebrated in style, which is why we offer a hassle-free way to enroll in our Birthday Rewards Program.</p>
            </div>

            <h2>Why Join a Birthday Rewards Program?</h2>
            <p>Enrolling in a birthday rewards program isn\'t just about signing up—it\'s about making your celebration extraordinary. Here\'s why you should join today:</p>
            
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item"><strong>Exclusive Discounts:</strong> Enjoy special deals on your favorite items or services.</li>
                <li class="list-group-item"><strong>Freebies Galore:</strong> Who doesn\'t love a birthday freebie? From complimentary gifts to services, we\'ve got you covered.</li>
                <li class="list-group-item"><strong>VIP Treatment:</strong> Get early access to promotions, events, and other special perks.</li>
                <li class="list-group-item"><strong>Easy and Free Enrollment:</strong> Our program is 100% free to join, and signing up takes just a few clicks.</li>
            </ul>

            <h2>How to Sign Up for Free?</h2>
            <p>Getting started with our Birthday Rewards Program is as easy as 1-2-3!</p>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">1. Visit Our Website</h5>
                            <p class="card-text">Go to <a href="https://birthday.gold/" class="text-decoration-none">birthday.gold</a> and click on the "Sign Up" button.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">2. Fill Out the Simple Form</h5>
                            <p class="card-text">Provide your name, email, and birthday. We\'ll handle the rest!</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">3. Start Earning Rewards</h5>
                            <p class="card-text">Once enrolled, you\'ll receive notifications about your birthday perks as your special day approaches.</p>
                        </div>
                    </div>
                </div>
            </div>

            <h2>What Rewards Can You Expect?</h2>
            <div class="row mb-4">
                <div class="col-lg-8">
                    <ul class="list-group">
                        <li class="list-group-item d-flex">
                            <i class="bi bi-gift me-3"></i>
                            <div>
                                <strong>Free Birthday Gifts:</strong>
                                <p class="mb-0">Handpicked treats just for you.</p>
                            </div>
                        </li>
                        <li class="list-group-item d-flex">
                            <i class="bi bi-tag me-3"></i>
                            <div>
                                <strong>Exclusive Birthday Discounts:</strong>
                                <p class="mb-0">Enjoy unbeatable offers available only to members.</p>
                            </div>
                        </li>
                        <li class="list-group-item d-flex">
                            <i class="bi bi-star me-3"></i>
                            <div>
                                <strong>Surprise Bonuses:</strong>
                                <p class="mb-0">Get rewarded with additional perks throughout your birthday month.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Make This Birthday Your Best Yet</h2>
            <p>At Birthday Gold, we\'re committed to making your birthday a celebration to remember. Whether it\'s indulging in a complimentary gift, enjoying massive savings, or simply feeling appreciated, our Birthday Rewards Program is designed to bring joy to your special day—all at no cost to you.</p>

            <div class="cta-section text-center">
                <h2 class="mb-3">Don\'t Wait—Enroll for Free Today!</h2>
                <p class="mb-4">Your next birthday could be your most rewarding one yet. Enroll in our Birthday Rewards Program today and let us take care of the celebrations.</p>
                <a href="/signup" class="btn btn-primary btn-lg">Sign Up Now</a>
            </div>
        </div>
    </div>
</div>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
