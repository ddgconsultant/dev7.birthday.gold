<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

$additionalstyles .= '
<style>
.blog-preview {
    transition: transform 0.2s;
}
.blog-preview:hover {
    transform: translateY(-3px);
}
.blog-meta {
    font-size: 0.9rem;
}
.read-time {
    color: #6c757d;
}
</style>
';

echo '    
<div class="container main-content">
  <div class="row justify-content-center mb-5">
    <div class="col text-center">
      <h1 class="display-5 mb-3 fw-bold">Birthday Gold Blog</h1>
      <p class="lead text-muted">Discover the best birthday rewards, freebies, and celebration tips near you</p>
    </div>
  </div>

  <!-- Featured Post -->
  <div class="row justify-content-center mb-5">
    <div class="col">
      <div class="card blog-preview shadow-sm">
        <div class="card-body">
          <span class="badge bg-primary mb-2">Featured</span>
          <h2 class="h3">
            <a href="/blog/how-to-get-free-birthday-rewards-enrollment-guide" class="text-decoration-none text-dark">
              Celebrate Your Special Day with Free Birthday Rewards Enrollment
            </a>
          </h2>
          <div class="blog-meta mb-3">
            <span class="me-3"><i class="far fa-calendar me-1"></i> January 31, 2025</span>
            <span class="read-time"><i class="far fa-clock me-1"></i> 5 min read</span>
          </div>
          <p class="lead mb-3">Looking for the best birthday freebies online? Learn how to unlock exclusive birthday treat rewards and make your celebration unforgettable with Birthday Gold\'s premium rewards program.</p>
          <a href="/blog/how-to-get-free-birthday-rewards-enrollment-guide" class="btn btn-outline-primary">Read More</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Latest Posts -->
  <div class="row justify-content-center">
    <div class="col">
      <h3 class="mb-4">Latest Guides</h3>
      
      <!-- Post 1 - NEW -->
      <div class="card blog-preview mb-4 shadow-sm">
        <div class="card-body">
          <span class="badge bg-success mb-2">New</span>
          <h2 class="h4">
            <a href="/blog/unlock-the-best-birthday-deals-online" class="text-decoration-none text-dark">
              Unlock the Best Birthday Deals Online: How to Celebrate Without Breaking the Bank
            </a>
          </h2>
          <div class="blog-meta mb-3">
            <span class="me-3"><i class="far fa-calendar me-1"></i> May 23, 2025</span>
            <span class="read-time"><i class="far fa-clock me-1"></i> 7 min read</span>
          </div>
          <p>Discover amazing birthday deals and freebies that let you celebrate without spending a fortune. From free meals to discounts on your favorite products, learn how to make the most of your special day.</p>
          <a href="/blog/unlock-the-best-birthday-deals-online" class="btn btn-outline-primary">Read More</a>
        </div>
      </div>

      <!-- Post 2 - NEW -->
      <div class="card blog-preview mb-4 shadow-sm">
        <div class="card-body">
          <span class="badge bg-success mb-2">New</span>
          <h2 class="h4">
            <a href="/blog/celebrate-big-with-birthday-freebies-no-sign-up-needed" class="text-decoration-none text-dark">
              Celebrate Big with Birthday Freebies – No Sign-Up Needed!
            </a>
          </h2>
          <div class="blog-meta mb-3">
            <span class="me-3"><i class="far fa-calendar me-1"></i> May 23, 2025</span>
            <span class="read-time"><i class="far fa-clock me-1"></i> 5 min read</span>
          </div>
          <p>Get amazing birthday freebies without the hassle of endless email registrations! Discover no sign-up birthday perks and walk-in deals that make your special day even more enjoyable.</p>
          <a href="/blog/celebrate-big-with-birthday-freebies-no-sign-up-needed" class="btn btn-outline-primary">Read More</a>
        </div>
      </div>
      
      <!-- Post 3 -->
      <div class="card blog-preview mb-4 shadow-sm">
        <div class="card-body">
          <h2 class="h4">
            <a href="/blog/maximize-birthday-rewards-benefits-guide" class="text-decoration-none text-dark">
              How to Make the Most of Your Birthday Rewards with Birthday Gold
            </a>
          </h2>
          <div class="blog-meta mb-3">
            <span class="me-3"><i class="far fa-calendar me-1"></i> January 28, 2025</span>
            <span class="read-time"><i class="far fa-clock me-1"></i> 4 min read</span>
          </div>
          <p>Expert tips and strategies to maximize your birthday deals online. Discover how to combine offers, find the best birthday freebies near me, and create the perfect birthday celebration plan.</p>
          <a href="/blog/maximize-birthday-rewards-benefits-guide" class="btn btn-outline-primary">Read More</a>
        </div>
      </div>

      <!-- Post 4 -->
      <div class="card blog-preview mb-4 shadow-sm">
        <div class="card-body">
          <h2 class="h4">
            <a href="/blog/birthday-rewards-program-signup-guide" class="text-decoration-none text-dark">
              How to Get Birthday Rewards with Birthday Gold: Complete Guide
            </a>
          </h2>
          <div class="blog-meta mb-3">
            <span class="me-3"><i class="far fa-calendar me-1"></i> January 25, 2025</span>
            <span class="read-time"><i class="far fa-clock me-1"></i> 6 min read</span>
          </div>
          <p>Your comprehensive guide to birthday rewards enrollment and accessing the best birthday freebies. Learn how Birthday Gold helps you find amazing birthday deals and treats in your area.</p>
          <a href="/blog/birthday-rewards-program-signup-guide" class="btn btn-outline-primary">Read More</a>
        </div>
      </div>

      <!-- Newsletter Signup -->
      <div class="card bg-light border-0 mb-4">
        <div class="card-body text-center py-5">
          <h3 class="h4 mb-3">Never Miss a Birthday Deal!</h3>
          <p class="mb-4">Join our newsletter for exclusive birthday rewards, local freebies, and celebration tips.</p>
          <a href="/signup" class="btn btn-primary">Start Your Free Enrollment</a>
        </div>
      </div>
    </div>
  </div>
</div>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>