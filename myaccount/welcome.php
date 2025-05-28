<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 


$additionalstyles.='<link rel="stylesheet" href="/public/css/myaccount.css">
<style>
.feature {
width: 100px;  /* Set width */
height: 100px;  /* Set height */
display: flex;
align-items: center;
justify-content: center;
}

.feature i {
font-size: 48px;  /* Increase icon size */
}
</style>
';

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
      <div class="container text-center">
          <div class="row justify-content-center">
              <div class="col-12">
              <picture>
              <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.webp" type="image/webp">
              <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.gif" alt="🥳" width="128" height="128">
            </picture>
                  <h1 class="display-1">'.$titletag.'</h1>
                  <h3 class="mb-4">'.$current_user_data['first_name'].', we are excited to have you be a part of birthday.gold.</h3>
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
                    <h6 class="mb-5">You are a now one of our great Gold Plan members.</h6>
                    <section class="pt-4">                
                    <div class="container px-lg-5">
                            <!-- Single Account Card -->
                        <div class="row gx-lg-5  justify-content-center"">
                            <div class="col-lg-8  mb-5 account-type-card" data-target="#individual">
                                <div class="card bg-light border-0 h-100">
                                    <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                                        <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-person"></i></div>
                                        <h2 class="fs-4 fw-bold">Individual Gold Account</h2>
                                        <p class="mb-0">We will now walk you through the easy three step enrollment process.</p>
                                        <div class="d-none">
                                        --------------------------------------------------
                                        <p class="mb-0">
                                       Here\'s what you need to know about upgrading to Lifetime Plan in the future:
                                      </p>
                                      <ul class="mt-3" style="text-align: left;">
                                        <li><strong>Upgrading is Possible:</strong> You can upgrade to the Lifetime Plan later, but there are some things to consider.</li>
                                        <li><strong>Email Accounts:</strong> 
                                          <ul>
                                            <li>If you proceed with the Gold Plan, you\'ll use your personal email for enrollments.</li>
                                            <li>Upgrading to the Lifetime Plan offers a special birthday.gold email address.</li>
                                          </ul>
                                        </li>
                                        <li><strong>Email Management:</strong> With the Lifetime Plan, marketing emails from businesses are organized automatically on a user-friendly page.</li>
                                        <li><strong>Limitation:</strong> This email management feature is only available for new enrollments under the Lifetime Plan. It won\'t apply to any enrollments made under the Gold Plan.</li>
                                      </ul>
                                      </div>
                                         </div>
                                    <div class="card-footer bg-light pb-3">
                                    <a class="btn btn-primary py-3 px-5 mt-5 fw-bold" href="/myaccount/enrollment">Sweet. Get me started!</a>
                                </div>
                                </div>
                            </div>
                                           '.$birthdayprioritytag.'
                   
                  
              </div>
          </div>
      </div>
      </div>           </div>
      </div>
      <!-- Welcome End -->
  ';
break;
default:
echo '
<section class="pt-4">                
<div class="container px-lg-5">
        <!-- Single Account Card -->
    <div class="row gx-lg-5  justify-content-center"">
        <div class="col-lg-8  mb-5 account-type-card" data-target="#individual">
            <div class="card bg-light border-0 h-100">
                <div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
                    <div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-person"></i></div>
                    <h2 class="fs-4 fw-bold">Free Plan Account</h2>
                   
                     </div>
                <div class="card-footer bg-light pb-3">
                <a class="btn btn-primary py-3 px-5 mt-5 fw-bold" href="/myaccount/">Sweet. Get me started!</a>
            </div>
            </div>
        </div>
                       '.$birthdayprioritytag.'


</div>
</div>';
echo ' </div>  </div> </div></div> </div>';
break;
}


$footerattribute['postfooter'] = '
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="/public/js/myaccount.js" language="javascript"></script>
';



include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();