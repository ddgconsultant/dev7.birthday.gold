<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$signupmode = 'tabletkiosk';
header('location: /signup-route?signupmode=tabletkiosk');
exit;

$headerattribute['additionalcss'] = '<link rel="stylesheet" href="/public/css/myaccount.css">
<style>
.feature {
width: 90px;  /* Set width */
height: 90px;  /* Set height */
display: flex;
align-items: center;
justify-content: center;
}

.feature i {
font-size: 48px;  /* Increase icon size */
}

.tooltip {
  z-index: 1039 !important;  /* Assuming the modal z-index is 1040 */
}


</style>
';

$headerattribute['rawheader'] = true;
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$paymenttag = '';
$birthdayprioritytag = '';
$titletag = 'Sign Up!';
#$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
#if ($till['days']==0) {
#$birthdayprioritytag=' Since your birthday is today, you will be prioritized to the front of the line and your registrations will be processed shortly after your selection.  You should be aware, some business do not allow for benefits on same day signups.  We will let you know if you pick any of those. (You\'ll just be early for next year :-)';
#$titletag='Happy Birthday!';
#}


$transferpage = $system->startpostpage();
if (empty($transferpage['message']))
  $transferpage['message'] = $session->get('force_error_message', '');
$session->unset('force_error_message');

#breakpoint($transferpage);
#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
echo '
  <!-- Welcome Start -->
  <div class="container py-5 px-lg-0 flex-grow-1 text-center animated" data-wow-delay="0.1s">
  <div class="row justify-content-center text-center">
         <div class="col-12 px-sm-0 mx-sm-0 px-lg-0">
              <picture>
              <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.webp" type="image/webp">
              <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.gif" alt="🥳" width="128" height="128">
            </picture>
                  <h1 class="display-1">Sign Up!</h1>
                  <h3 class="mb-4 pb-2 pb-lg-5">Choose how you want your birthday.gold.</h3>
                  </div>
                  ';


echo '
<section class="col-12 text-center px-lg-0">
    <div class="row text-center">
          
        ';

# INDIVIDUAL ACCOUNT
#-------------------------------------------------------------------------------
echo '
<div class="col-12 col-md-12 col-lg-4 mb-5 mt-2 account-type-card" data-target="#individual">
    <div class="card bg-light border-0 h-100">
        <div class="card-body text-center p-4 p-lg-3 pt-0 pt-lg-0">
            <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-person"></i></div>
            <h2 class="fs-4 fw-bold">Individual</h2>
            <p class="mb-0">Choose this if it you want all the freebies for yourself.</p>
            <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#individualModal">Learn More</a>
            <form name="accounttype" method="POST" action="/signup">
            ' . $display->inputcsrf_token() . '
            ' . $transferpage['message'] . $display->formaterrormessage($transferpage['message']) . '
          <div class="my-3 pt-4 fw-bold h4">Pick A Plan <a href="/plans">
          <i class="bi bi-question-square-fill" ' . $display->tooltip('Click to view Plans') . '></i></a></div>
<div class="mx-n5">
<input type="radio" class="btn-check btn-lg" name="account_plan" value="free" id="btnradio1" autocomplete="off">
<label class="btn btn-lg btn-outline-success" for="btnradio1">Free</label>

<input type="radio" class="btn-check btn-lg" name="account_plan" value="gold" id="btnradio2" autocomplete="off">
<label class="btn btn-lg btn-outline-success" for="btnradio2">Gold</label>

<input type="radio" class="btn-check btn-lg" name="account_plan" value="life" id="btnradio3" autocomplete="off">
<label class="btn btn-lg btn-outline-success" for="btnradio3">Lifetime</label>
</div>

        </div>
        <div class="card-footer bg-light py-3">
        <input type="hidden" name="account_type" value="individual">    
        <!-- Wrapper div with tooltip -->
        <div id="tooltipDiv1" ' . $display->tooltip("Select a plan first 👆") . '>
            <button type="submit" id="usersubmitBtn" class="btn btn-lg button btn-primary" disabled>I want this one!</button>
        </div>

    </div>
</form>
    </div>
</div>
';

# PARENTAL ACCOUNT
#-------------------------------------------------------------------------------
echo '
            <div class="col-12 col-md-12 col-lg-4 mb-5 mt-2 account-type-card"  data-target="#parental">
                <div class="card bg-light border-0 h-100">
                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-people"></i></div>
                        <h2 class="fs-4 fw-bold">Parental</h2>
                        <p class="mb-0">Choose a Parental Account to link and manage up to six children (age 0-16) accounts at reduced prices.</p>
                        <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#parentalModal">Learn More</a>
                        </div>
                    <div class="card-footer bg-light py-3">
                    <a href="/signup?parental" class="btn btn-lg btn-primary">Yes! I have kids to add.</a>
                </div>
                </div>
            </div>
            ';

# BUY GIFTCARD
#-------------------------------------------------------------------------------
echo '
            <div class="col-12 col-md-12 col-lg-4 mb-5 mt-2 account-type-card"  data-target="#gift-certificate">
                <div class="card bg-light border-0 h-100">
                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-gift"></i></div>
                        <h2 class="fs-4 fw-bold">Gift Certificate</h2>
                        <p class="mb-0">Select this account type if you are intending to give this as a gift to someone.  Gift Certificates do not expire.</p>
                        <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#giftModal">Learn More</a>
                        </div>
                    <div class="card-footer bg-light py-3">
                    <div><a href="/signup?giftcertificate" class="btn btn-lg btn-primary">Yep. This is a gift.</a></div>
                    <div class="mt-4"><a href="/redeem" class="text-dark">Redeem a gift certificate <i class="bi bi-arrow-right-square"></i></a></div>

                </div>
                </div>
            </div>
            ';

/*
# BUSINESS ACCOUNT
#-------------------------------------------------------------------------------
            echo '
            <div class="col-12 col-md-12 col-lg-4 mb-5 mt-2 account-type-card d-none" data-target="#business">
                <div class="card bg-light border-0 h-100">
                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-briefcase"></i></div>
                        <h2 class="fs-4 fw-bold">Business</h2>
                        <p class="mb-0">Information for Business Account...</p>
                        <a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#businessModal">Learn More</a>
                        </div>
                    <div class="card-footer bg-light py-3">
                    <a href="/signup?business" class="btn btn-primary">I own a business.</a>
                </div>
                </div>
            </div>
            ';

*/


echo '
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
    <li>Sign Up for a Parental Account.</li>
    <li>Add information for each child.</li>
    <li>Pay for the new account(s).</li>
    <li>Customize settings for each child.</li>
    <li>Start enjoying the benefits!</li>
  </ol>

  <h4>Cost:</h4>
  <p class="px-4">$40 for your account, $20 for the first two child accounts, $15 for the next two, and $10 for each additional account.  
  These accounts are automatically Lifetime Plan members and they receive all the features and benefits.  The most you\'ll pay for six children is $130.00 ever (incluing your account).</p>

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

echo '<a href="/logout"><i class="bi bi-x-square text-info m-1"></i></a>';

$footerattribute['postfooter'] = '
<script src="/public/js/myaccount.js" language="javascript"></script>

' . $display->tooltip('-js-') . '

<script>


// Get the parent div of the button which has the tooltip
const tooltipDiv = document.getElementById("tooltipDiv1");

// Get the tooltip instance of the parent div
const tooltipInstance = bootstrap.Tooltip.getInstance(tooltipDiv);

// Get all radio buttons
const radios = document.querySelectorAll(\'input[type=radio][name="account_plan"]\');

// Get the submit button
const submitBtn = document.getElementById("usersubmitBtn");

// Add event listener for each radio button
radios.forEach(function(radio) {
  radio.addEventListener("change", function() {
    // Enable the submit button if a radio button is selected
    if (this.checked) {
      // Remove the tooltip if it exists
      if (tooltipInstance) {
        tooltipInstance.dispose();
      }
      
      submitBtn.removeAttribute("disabled");

    
    }
  });
});

document.querySelector("body").requestFullscreen();
</script>

';
$footerattribute['rawfooter'] = true;
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
