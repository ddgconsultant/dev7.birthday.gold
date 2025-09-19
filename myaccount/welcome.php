<?php
$addClasses[] = 'account';
include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// If user has already completed first profile visit, redirect to myaccount
$first_profile_visit_check = $account->getUserAttribute($current_user_data['user_id'], 'first_profile_visit');
if ($first_profile_visit_check) {
    header('Location: /myaccount/');
    exit;
}

$additionalstyles.='<link rel="stylesheet" href="/public/css/myaccount.css">
<style>
/* Minimal custom styles - using Bootstrap 5 utilities where possible */

/* Transparent containers */
.main-content, .container {
    background-color: transparent !important;
}

/* Confetti Animation */
.confetti-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
}

.confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    background: #f0f;
    animation: confetti-fall linear forwards;
}

@keyframes confetti-fall {
    to {
        transform: translateY(100vh) rotate(360deg);
        opacity: 0;
    }
}

/* Step number circle */
.step-number {
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    margin-top: 0.6rem;
}

/* Get Started Button gradient */
.btn-get-started {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50px;
    animation: pulse 2s infinite;
    font-size:1.8rem
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
    50% { box-shadow: 0 4px 25px rgba(102, 126, 234, 0.6); }
}

.btn-get-started:hover {
    background: linear-gradient(135deg, #764ba2, #667eea);
    transform: translateY(-3px);
    box-shadow: 0 6px 30px rgba(102, 126, 234, 0.7);
}

/* Steps card glass effect */
.steps-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
}

/* Feature icons for account types */
.feature {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea, #764ba2);
}
</style>
';
$header_flush=true;
#include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$paymenttag='';
$birthdayprioritytag='';
$titletag='Welcome!';
$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
if ($till['days']==0) {
$birthdayprioritytag=' Since your birthday is today, you will be prioritized to the front of the line and your registrations will be processed shortly after your selection.  You should be aware, some business do not allow for benefits on same day signups.  We will let you know if you pick any of those. (You\'ll just be early for next year :-)';
$titletag='Happy Birthday!';
}


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
  echo '
  <!-- Welcome Start -->
  <div class="container main-content">
      <div class="text-center">
          <div class="justify-content-center" style="background-color:transparent !important;">
              <div class="col-12">
              <picture>
              <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.webp" type="image/webp">
              <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.gif" alt="🥳" width="128" height="128">
            </picture>
                  <h1 class="display-1">'.$titletag.'</h1>
         
                  ';

                  $plan=$current_user_data['account_plan'];
              #    $plan='gold';
switch ($plan) {

case 'life':


    echo '
    <h6 class="mb-5">Lifetime Plan members have access to special account type.<br>Choose one and we will walk you through the next steps.</h6>
    <section class="pt-4">                
    <div class="container px-lg-5">
            <!-- Account Types -->
        <div class="row gx-lg-5">
            <div class="col-lg-6 col-xxl-4 mb-5 account-type-card" data-target="#individual">
                <div class="card bg-light border-0 h-100">
                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-person"></i></div>
                        <h2 class="fs-4 fw-bold">Individual</h2>
                        <p class="mb-0">Individual Accounts are our standard Lifetime Accounts.  Chose this if it you want all the freebies for yourself.</p>
                        <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#individualModal">Learn More</a>
                    </div>
                    <div class="card-footer bg-light py-3">
                    <a href="/myaccount/setup-individual" class="btn btn-primary">I want this one!</a>
                </div>
                </div>
            </div>
            <div class="col-lg-6 col-xxl-4 mb-5 account-type-card" data-target="#parental">
                <div class="card bg-light border-0 h-100">
                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-people"></i></div>
                        <h2 class="fs-4 fw-bold">Parental</h2>
                        <p class="mb-0">Choose a Parental Account to link and manage up to six children (age 0-16) accounts at reduced prices.</p>
                        <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#parentalModal">Learn More</a>
                        </div>
                    <div class="card-footer bg-light py-3">';

         
                    echo '
                    <a href="/myaccount/setup-parental" class="btn btn-primary">Yes! I have kids to add.</a>
                    ';
          /*   } else {    
                    echo '
                    <a href="#" class="btn btn-primary disabled">Feature Coming Sept. 18</a>
                    ';
            } */
                    echo '
                </div>
                </div>
            </div>
            <div class="col-lg-6 col-xxl-4 mb-5 account-type-card" data-target="#gift-certificate">
                <div class="card bg-light border-0 h-100">
                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-gift"></i></div>
                        <h2 class="fs-4 fw-bold">Gift Certificate</h2>
                        <p class="mb-0">Select this account type if you are intending to give this as a gift to someone.  Gift Certificates do not expire.</p>
                        <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#giftModal">Learn More</a>
                        </div>
                    <div class="card-footer bg-light py-3">
                    <a href="/myaccount/setup-giftcertificate" class="btn btn-primary">Yep. This is a gift.</a>
                </div>
                </div>
            </div>
            <div class="col-lg-6 col-xxl-4 mb-5 account-type-card d-none" data-target="#business">
                <div class="card bg-light border-0 h-100">
                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-briefcase"></i></div>
                        <h2 class="fs-4 fw-bold">Business</h2>
                        <p class="mb-0">Information for Business Account...</p>
                        <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#businessModal">Learn More</a>
                        </div>
                    <div class="card-footer bg-light py-3">
                    <a href="/myaccount/setup-business" class="btn btn-primary">I own a business.</a>
                </div>
                </div>
            </div>
        </div>
    
    </div>
</section>
          




<!-- Modal for Individual Account -->
<div class="modal fade" id="individualModal" tabindex="-1" aria-labelledby="individualModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="individualModalLabel">Individual Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <h2>Individual Account</h2>
      <h4>What it is:</h4>
      <p>An Individual Account is tailored for a single user who wants to enjoy all the exclusive offers, freebies, and VIP experiences offered by our platform. This account type is ideal for those who are looking for a personalized experience and want to take full advantage of the benefits available to them.</p>
      <div class="text-start" >
      <h4>Who it\'s for:</h4>
      <p class="px-4">This account type is perfect for adults who are looking to celebrate their birthdays in a special way. Whether you\'re a student, a working professional, or anyone in between, if you love birthdays and free stuff, this account is for you.</p>
      
      <h4>Features:</h4>
      <ul class="px-5">
          <li><strong>Personalized Offers:</strong> Receive personalized offers and freebies that are tailored to your interests and location.</li>
          <li><strong>VIP Experiences:</strong> Get access to exclusive VIP experiences that are not available to other account types.</li>
          <li><strong>Birthday Priority:</strong> Get prioritized offers and experiences as your birthday approaches.</li>
          <li><strong>Year-Round Deals:</strong> Enjoy special offers and discounts not just on your birthday but all year round.</li>
      </ul>
      
      <h4>Limitations:</h4>
      <p class="px-4">This account type is for individual use only and cannot be used to manage multiple profiles or for children under a certain age.</p>
      
      <h4>Cost:</h4>
      <p class="px-4">Since you are a Lifetime Plan member -- there is no additional costs/fees</p>
      </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>




<!-- Modal for Parental Account -->
<div class="modal fade" id="parentalModal" tabindex="-1" aria-labelledby="parentalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="parentalModalLabel">Parental Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <h2>What is a Parental Account?</h2>
  <p>A Parental Account allows you to manage multiple accounts for your children, all under one main account. This is perfect for parents who want to keep track of rewards and benefits for their entire family.</p>
  
  <div class="text-start">
  <h4>Features:</h4>
  <ul class="px-5">
    <li><strong>Multiple Child Accounts:</strong> Add up to six child accounts (ages 0-16).</li>
    <li><strong>One Dashboard:</strong> Manage all accounts from a single dashboard.</li>
    <li><strong>Discounts:</strong> Enjoy the discounted rate for child accounts.</li>
    <li><strong>Parental Controls:</strong> Customize the settings for each child account.</li>
  </ul>

  <h4>How to Set Up:</h4>
  <ol class="px-5">
    <li>Sign Up for a Parental Account. - Done</li>
    <li>Add each of your children\'s information.</li>
    <li>Pay for the new account(s).</li>
    <li>Customize settings for each child.</li>
    <li>Start enjoying the benefits!</li>
  </ol>

  <h4>Cost:</h4>
  <p class="px-4">There is a $20 per account for each child account that is created for the first three accounts and $10 per account thereafter.  
  These accounts are automatically Lifetime Plan members and they receive all the features and benefits.  The most you\'ll pay for six children is $90.00 ever.</p>

  <p>If you have any more questions about the Parental Account, feel free to <a href="#contactUs">contact us</a>.</p>

  </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



<!-- Modal for Gift Account -->
<div class="modal fade" id="giftModal" tabindex="-1" aria-labelledby="giftModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="giftModalLabel">Gift Certificate Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <h2>Gift Certificate </h2>
      <h4>What it is:</h4>
      <p>A Gift Certificate Account is a special type of account that allows you to gift all the perks and benefits of our service to someone special. It\'s the perfect gift for birthdays, anniversaries, or any other special occasion.</p>
  
      <div class="text-start">
      <h3>Features:</h3>
      <ul>
        <li><strong>Instant Gifting:</strong> This account will be converted to a gift certificate instantly.  You will receive downloadable Gift Certificate that you can give to your special someone.</li>
        <li><strong>No Expiry:</strong> Our gift certificates have no expiration date.</li>
        <li><strong>Personalized Message:</strong> Add a personalized message to make your gift even more special.</li>
        <li><strong>Flexible Usage:</strong> The recipient can use the gift certificate receives this Lifetime Plan account.</li>
      </ul>
    
      <h4>Cost:</h4>
      <ul>
        <li>There is no additional cost to you or the recipient.</li>
      </ul>
    
      <p>If you have any more questions about the Gift Certificate Account, feel free to <a href="#contactUs">contact us</a>.</p>
    
      </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>





<!-- Modal for Business Account -->
<div class="modal fade" id="businessModal" tabindex="-1" aria-labelledby="businessModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="businessModalLabel">Business Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <h2>Individual Account</h2>
      <h4>What it is:</h4>
      <p>An Individual Account is tailored for a single user who wants to enjoy all the exclusive offers, freebies, and VIP experiences offered by our platform. This account type is ideal for those who are looking for a personalized experience and want to take full advantage of the benefits available to them.</p>
      
      <h4>Who it\'s for:</h4>
      <p>This account type is perfect for adults who are looking to celebrate their birthdays in a special way. Whether you\'re a student, a working professional, or anyone in between, if you love birthdays and free stuff, this account is for you.</p>
      
      <h4>Features:</h4>
      <ul class="" style="text-align: left;">
          <li><strong>Personalized Offers:</strong> Receive personalized offers and freebies that are tailored to your interests and location.</li>
          <li><strong>VIP Experiences:</strong> Get access to exclusive VIP experiences that are not available to other account types.</li>
          <li><strong>Birthday Priority:</strong> Get prioritized offers and experiences as your birthday approaches.</li>
          <li><strong>Year-Round Deals:</strong> Enjoy special offers and discounts not just on your birthday but all year round.</li>
      </ul>
      
      <h4>Limitations:</h4>
      <p>This account type is for individual use only and cannot be used to manage multiple profiles or for children under a certain age.</p>
      
      <h4>Cost:</h4>
      <p>Since you are a Lifetime Plan member -- there is no additional costs/fees.</p>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn  btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



</div>
</div>         
</div>
</div>                 
 ';
break;

case 'gold':
echo '
<div class="min-vh-75 py-5 d-flex align-items-center">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left Section - Welcome Message -->
            <div class="col-lg-8">
                <div class="mb-4">
                    <picture>
                        <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f3c6/512.webp" type="image/webp">
                        <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f3c6/512.gif" alt="🏆" width="128" height="128">
                    </picture>
                </div>
                <h1 class="display-4 fw-bold text-dark mb-3">'.$titletag.' '.$current_user_data['first_name'].'!</h1>
                <p class="fs-4 text-secondary mb-4">Welcome to Gold Membership</p>
                '.$birthdayprioritytag.'
                <a class="btn btn-get-started btn-lg text-white fw-bold py-3 px-5 mt-4 w-75" href="/myaccount/enrollment">Let\'s Get Started</a>
            </div>
            
            <!-- Right Section - Steps Card -->
            <div class="col-lg-4">
                <div class="steps-card rounded-3 shadow p-4 text-start">
                    <h3 class="text-uppercase fw-semibold text-secondary small mb-4">Gold Benefits</h3>
                    
                    <div class="d-flex mb-3">
                        <div class="step-number rounded-circle text-white d-flex align-items-center justify-content-center fw-bold me-3 flex-shrink-0">1</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold fs-5 text-dark mb-1">Complete Your Profile</div>
                            <div class="text-muted small">Premium preferences</div>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="step-number rounded-circle text-white d-flex align-items-center justify-content-center fw-bold me-3 flex-shrink-0">2</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold fs-5 text-dark mb-1">Pick Your Rewards</div>
                            <div class="text-muted small">Exclusive offers</div>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="step-number rounded-circle text-white d-flex align-items-center justify-content-center fw-bold me-3 flex-shrink-0">3</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold fs-5 text-dark mb-1">Sit Back & Relax</div>
                            <div class="text-muted small">Priority enrollment</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';
break;
default:
// First profile visit already checked at top of page - if we're here, it's their first visit
echo '
<div class="min-vh-75 py-5 d-flex align-items-center">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left Section - Welcome Message -->
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-dark mb-4">'.$current_user_data['first_name'].', we\'re excited <br>
                that you\'re part of birthday.gold!</h1>
                '.$birthdayprioritytag.'
                <a class="btn btn-get-started btn-lg text-white fw-bold py-3 px-5 mt-4 w-75 f-2" href="/myaccount/profile">Let\'s Get Started</a>
            </div>
            
            <!-- Right Section - Steps Card -->
            <div class="col-lg-4">
                <div class="steps-card rounded-3 shadow p-4 text-start">
                    <h3 class="text-uppercase fw-semibold text-secondary small mb-4 tracking-wide">What Is Next</h3>
                    
                    <div class="d-flex mb-3">
                        <div class="step-number rounded-circle text-white d-flex align-items-center justify-content-center fw-bold me-3 flex-shrink-0">1</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold fs-5 text-dark mb-1">Complete Your Enrollment Profile</div>
                            <div class="text-muted small">Tell us your preferences</div>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="step-number rounded-circle text-white d-flex align-items-center justify-content-center fw-bold me-3 flex-shrink-0">2</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold fs-5 text-dark mb-1">Pick Your Rewards</div>
                            <div class="text-muted small">Choose birthday offers from our list of businesses</div>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="step-number rounded-circle text-white d-flex align-items-center justify-content-center fw-bold me-3 flex-shrink-0">3</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold fs-5 text-dark mb-1">Sit Back & Relax</div>
                            <div class="text-muted small">We\'ll enroll you automatically</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';
break;
}


$footerattribute['postfooter'] = '
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="/public/js/myaccount.js" language="javascript"></script>
<script>
// Confetti Animation
function createConfetti() {
    const container = document.createElement("div");
    container.className = "confetti-container";
    document.body.appendChild(container);
    
    const colors = ["#667eea", "#764ba2", "#f093fb", "#f5576c", "#4facfe", "#00f2fe", "#ffd700", "#ff6b6b"];
    const confettiCount = 150;
    
    for (let i = 0; i < confettiCount; i++) {
        const confetti = document.createElement("div");
        confetti.className = "confetti";
        confetti.style.left = Math.random() * 100 + "%";
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.animationDuration = (Math.random() * 3 + 2) + "s";
        confetti.style.animationDelay = Math.random() * 2 + "s";
        container.appendChild(confetti);
    }
    
    // Remove confetti after animation
    setTimeout(() => {
        container.remove();
    }, 5000);
}

// Trigger confetti on page load
document.addEventListener("DOMContentLoaded", createConfetti);
</script>
';



include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();