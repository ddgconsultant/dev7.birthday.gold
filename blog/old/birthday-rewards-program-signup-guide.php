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
    margin-top: 2rem;
    margin-bottom: 1rem;
}
.blog-content ul {
    margin-bottom: 1.5rem;
}
.blog-content .cta-section {
    background-color: #f8f9fa;
    padding: 2rem;
    border-radius: 0.5rem;
    margin: 2rem 0;
}
</style>
';

echo '    
<div class="container main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">How to Get Birthday Rewards with Birthday Gold</h1>
    <a href="/" class="btn btn-sm btn-outline-secondary">Home</a>
  </div>
';

echo '
  <div class="card">
    <div class="card-body blog-content">
      <div class="lead mb-4">
        Your birthday is more than just a special day—it\'s a chance to treat yourself! At Birthday Gold, we make it easier than ever to unlock exclusive rewards and perks from over 275+ businesses. Whether you\'re a foodie, entertainment lover, or shopping enthusiast, we\'ve got something for everyone.
      </div>

      <p class="mb-4">Here\'s a step-by-step guide to help you make the most of Birthday Gold and discover the best birthday rewards.</p>

      <h2>Step 1: Sign Up for Free with Birthday Gold</h2>
      <p>The first step to accessing incredible birthday perks is signing up for a free account with Birthday Gold. Simply visit our website, create a profile, and start exploring the possibilities.</p>

      <h2>Step 2: Visit the Discover Page</h2>
      <p>Our <strong>Discover</strong> page is your gateway to birthday rewards. It\'s packed with deals across dining, entertainment, shopping, and more. The best part? These deals are personalized to suit your preferences, making it easy to find the rewards that excite you the most.</p>

      <h2>Step 3: Explore 275+ Businesses Offering Perks</h2>
      <p>From popular restaurants to thrilling entertainment venues, we\'ve partnered with a wide variety of businesses to ensure there\'s something for everyone. Here are just a few types of rewards you can enjoy:</p>

      <ul class="list-group list-group-flush mb-4">
        <li class="list-group-item"><strong>Free Meals and Drinks:</strong> Celebrate your day with complimentary dining deals.</li>
        <li class="list-group-item"><strong>Discounted Entertainment:</strong> Treat yourself to movie tickets, live shows, or amusement parks.</li>
        <li class="list-group-item"><strong>Retail Discounts:</strong> Get exclusive birthday savings on your favorite brands.</li>
        <li class="list-group-item"><strong>Special Gifts:</strong> Receive handpicked presents from participating businesses.</li>
      </ul>

      <h2>Step 4: Redeem Your Birthday Rewards</h2>
      <p>Once you\'ve found the deals you love, redeeming them is simple:</p>

      <ol class="list-group list-group-numbered mb-4">
        <li class="list-group-item">Browse the available offers on our Discover page.</li>
        <li class="list-group-item">Follow the instructions for each business—some may require you to present an email, ID, or coupon.</li>
        <li class="list-group-item">Enjoy your rewards and make your birthday unforgettable!</li>
      </ol>

      <div class="card bg-light mb-4">
        <div class="card-body">
          <h2 class="card-title">Why Choose Birthday Gold for Your Birthday Rewards?</h2>
          <ul class="list-group list-group-flush">
            <li class="list-group-item bg-light"><strong>Extensive Options:</strong> With over 275+ participating businesses, your birthday celebrations are limitless.</li>
            <li class="list-group-item bg-light"><strong>Personalized Deals:</strong> Get offers tailored to your preferences for a unique experience.</li>
            <li class="list-group-item bg-light"><strong>Convenience:</strong> All your rewards in one place, making it easy to plan your day.</li>
            <li class="list-group-item bg-light"><strong>Free Membership:</strong> Enjoy all these benefits without spending a dime.</li>
          </ul>
        </div>
      </div>

      <div class="cta-section text-center">
        <h2>Make Your Birthday Extra Special Today</h2>
        <p class="mb-4">With Birthday Gold, celebrating your special day has never been easier. Don\'t miss out on amazing birthday perks—sign up today and discover the perfect way to make your next birthday unforgettable.</p>
        <a href="/discover" class="btn btn-primary btn-lg">Visit the Discover Page Now</a>
      </div>
    </div>
  </div>
</div>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
