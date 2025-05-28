<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# SIGN UP MODE
#-------------------------------------------------------------------------------
$signupmode = '';
$buttonsize = '';
$signupexit = '';
$columnsize = 'col-lg-6';

$seniormode = false;
$signupmode = $session->get('signupmode', isset($_GET['signupmode']) ? $_GET['signupmode'] : '');

switch ($signupmode) {

  case 'upgrade':
    $kioskmode = false;
    $signup = false;
    break;

  case 'tabletkiosk':
    $kioskmode = true;
    $signup = true;
    break;

  default:
    $kioskmode = false;
    $signup = true;
    break;
}



if ($signupmode != '') {
  $headerattribute['rawheader'] = true;
  $buttonsize = 'btn-lg';
  $signupexit = '<a href="/logout"><i class="bi bi-x-square text-info m-1"></i></a>';
  $footerattribute['rawfooter'] = true;
  $session->set('signupmode', $signupmode);
  if ($session->get('referral_userid', '') == '') $session->set('referral_userid', $current_user_data['user_id']);


  if ($account->iscconsultant('', 'senior')) {
    $seniormode = true;
  }
}



include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
#include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/topnav.inc'); 


$additionalstyles .= '<link rel="stylesheet" href="/public/css/myaccount.css">
<style>
.feature {
width: 75px;  /* Set width */
height: 75px;  /* Set height */
display: flex;
align-items: center;
justify-content: center;
}

.feature i {
font-size: 32px;  /* Increase icon size */
}
</style>

<style>
/* Default size */
.responsive-icon img {
    width: 128px;
    height: 128px;
}

/* Reduce size on MD and smaller screens */
@media (max-width: 992px) {
    .responsive-icon img {
        width: 96px;
        height: 96px;
    }
}

@media (max-width: 768px) {
    .responsive-icon img {
        width: 72px;
        height: 72px;
    }
}

.card-footer {
    min-height: 100px; /* Adjust this value as needed */
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}


.account-type-card .card {
    transition: background-color 0.3s ease;
}

.account-type-card .card:hover {
    background-color: #f8f9fa !important; /* Subtle gray color on hover */
}
</style>
';


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
if (empty($transferpage['message'])) {
  $transferpage['message'] = $session->get('force_error_message', '');
}
$session->unset('force_error_message');


#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
echo '
<!-- Welcome Start -->
<div class="container-fluid container-xl pt-0 pb-5 my-0 px-xl-5 text-center main-content ">
<div class="row my-0 justify-content-center text-center">
';
if ($transferpage['message']!='') {
    echo '
<div class="col-12 px-sm-0 mx-sm-0 px-lg-0 mb-3" id="signup_error_message_holder">
'. $display->formaterrormessage($transferpage['message']) . '
</div>
';
}

$signupOffers = [
  ["title" => "Join Today!", "description" => "Unlock full lifetime access for a one-time payment of only $40."],
  ["title" => "Become a Member!", "description" => "Enjoy lifetime benefits for a single payment of $40."],
  ["title" => "Start Now!", "description" => "Get lifetime access to all features for a one-time fee of $40."],
  ["title" => "Claim Your Spot!", "description" => "Secure lifetime access to all premium features for just $40."],
  ["title" => "Get Started!", "description" => "Experience our complete Gold Plan for a one-off payment of $40."],
  ["title" => "Take Advantage Now!", "description" => "Gain full, lifetime access with a one-time investment of only $40."],
  ["title" => "Subscribe for Life!", "description" => "One payment of $40 for lifelong access to all our features."],
  ["title" => "Embark on Your Journey!", "description" => "All-inclusive lifetime access available now for a single payment of $40."],
  ["title" => "Get It All!", "description" => "Full lifetime access to all perks for a one-time cost of just $40."]
];

$randomOffer = $signupOffers[rand(0, count($signupOffers) - 1)];





///=======================================================================================================================
///=======================================================================================================================
/// TOP OF SCREEN GOLD PLAN ACTION ITEM (gold plan)
    $optionProductData = $app->getProduct('gold', 'user', '*', 1);
if ((isset($optionProductData['display_grouping_status'])) && ($optionProductData['display_grouping_status'] == 'active')) {
    $options = [
        'message1' => [
            'text' => 'Get all the features in our ' . $optionProductData['account_name'] . ' Plan. Only ' . $qik->convertamount($optionProductData['price']) . ' one-time payment.',
            'pillWidth' => 'col-lg-11'
        ],
        'message2' => [
            'text' => 'Get lifetime access to our ' . $optionProductData['account_name'] . ' Plan for a one-time payment of ' . $qik->convertamount($optionProductData['price']) . '.',
            'pillWidth' => 'col-lg-9'
        ],
        'message3' => [
            'text' => 'Lifetime ' . $optionProductData['account_name'] . ' Plan access for a single ' . $qik->convertamount($optionProductData['price']) . ' payment.',
            'pillWidth' => 'col-lg-8'
        ],
        'message4' => [
            'text' => $optionProductData['account_name'] . ' Plan: ' . $qik->convertamount($optionProductData['price']) . ' one-time for lifetime access.',
            'pillWidth' => 'col-lg-7'
        ],
        'message5' => [
            'text' => 'Pay ' . $qik->convertamount($optionProductData['price']) . ' once, enjoy the ' . $optionProductData['account_name'] . ' Plan forever.',
            'pillWidth' => 'col-lg-7'
        ],
        'message6' => [
            'text' => $optionProductData['account_name'] . ' Plan: One-time ' . $qik->convertamount($optionProductData['price']) . ' for life.',
            'pillWidth' => 'col-lg-6'
        ]
    ];
    
    // Set the selected message key
    $selectedMessageKey = 'message'.rand(1,6); // Change this to select the desired message
    $session->set('selectedMessageKey', $selectedMessageKey);
// Extract the message text and pill width for the selected key
$selectedMessage = $options[$selectedMessageKey]['text'];
$selectedPillWidth = $options[$selectedMessageKey]['pillWidth'];    
echo '
<div class="card ' . $selectedPillWidth . ' col-md-12 border-3 rounded-pill h-100 border-success my-0" style="background-color: #f6faf8">
<div class="row py-3 align-items-center">
<div class="col-2 align-middle">
<picture class="responsive-icon">
    <source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.webp" type="image/webp">
    <img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f973/512.gif" alt="🥳" width="128" height="128">
</picture>
</div>
<div class="col-10">
<h1>Sign Up!</h1>
<p class="h5 fw-bold my-3">' . $selectedMessage . '</p>
';
#echo '<p class="h5 fw-bold my-3">Get all the features in our ' . $optionProductData['account_name'] . ' Plan.  Only ' . $qik->convertamount($optionProductData['price']) . ' one-time payment.</p>';
#echo '<p class="h5 fw-bold my-3">Get lifetime access to our ' . $optionProductData['account_name'] . ' Plan for a one-time payment of ' . $qik->convertamount($optionProductData['price']) . '.</p>';
#echo '<p class="h5 fw-bold my-3">Get lifetime access to our ' . $optionProductData['account_name'] . ' Plan for a one-time payment of ' . $qik->convertamount($optionProductData['price']) . '.</p>';
#echo '<p class="h5 fw-bold my-3">Lifetime ' . $optionProductData['account_name'] . ' Plan access for a single ' . $qik->convertamount($optionProductData['price']) . ' payment.</p>';
#echo '<p class="h5 fw-bold my-3">' . $optionProductData['account_name'] . ' Plan: ' . $qik->convertamount($optionProductData['price']) . ' one-time for lifetime access.</p>';
#echo '<p class="h5 fw-bold my-3">Pay ' . $qik->convertamount($optionProductData['price']) . ' once, enjoy the ' . $optionProductData['account_name'] . ' Plan forever.</p>';
#echo '<p class="h5 fw-bold my-3">' . $optionProductData['account_name'] . ' Plan: One-time ' . $qik->convertamount($optionProductData['price']) . ' for life.</p>';

/*
echo '<p class="h5 fw-bold">Get all the features in our ' . $optionProductData['account_name'] . ' Plan. <img class="ms-4" width="64" src="/public/images/icon/sale.gif"></p>
<p><span class="text-danger me-3">Save 75%!!</span>  Only ' . $qik->convertamount($optionProductData['price']) . ' one-time payment<br>On sale through the end of the year! </p>';
*/

echo '
<div>
<form name="accounttype" method="POST" id="singleoption" action="/signup">
' . $display->inputcsrf_token() . '
<input type="hidden" name="account_plan" value="'.$qik->encodeId($optionProductData['id']).'" >
<input type="hidden" name="account_type" value="individual">
<input type="hidden" name="selector" value="topoption">
<button type="submit" class="btn ' . $buttonsize . ' button px-5 btn-lg btn-primary fw-bold" data-cy="btn-bannerplan">Yes! Sign Me Up!</button>
</form>
</div>
</div>
</div>
</div>
';
}




///=======================================================================================================================
///=======================================================================================================================
/// LIST ALL AVAILABLE PLANS BOXES
echo '
<h4 class="mt-4 pt-5 pb-lg-2">Or, choose from other birthday.gold plans:</h4>
<section class="col-12 text-center px-lg-0">
<div class="row text-center">
';

# INDIVIDUAL ACCOUNT
#-------------------------------------------------------------------------------
echo '
<div class="col-12 col-md-12 '.$columnsize.' mb-4 account-type-card" data-target="#individual">
<div class="card bg-light border-1 h-100 d-flex flex-column">
<div class="card-body p-3 flex-grow-1">
<div class="d-flex align-items-center mb-3">
    <div class="flex-shrink-0">
        <div class="feature bg-dark bg-gradient text-white rounded-3"><i class="bi bi-person"></i></div>
    </div>
    <div class="flex-grow-1 d-flex justify-content-center">
        <h2 class="fs-2 fw-bold mb-0">Individual</h2>
    </div>
</div>
<p class="mb-1 text-center px-5">Choose this if you want all the freebies for yourself.</p>
<a href="#" class="secondary d-block text-center" data-bs-toggle="modal" data-bs-target="#individualModal">Learn More</a>
<form name="accounttype" method="POST" id="multioption" action="/signup" class="mt-3">
' . $display->inputcsrf_token() . '
<div class="text-center mt-4 fw-bold h5">Pick A Plan <a href="/plans">
<i class="bi bi-question-square-fill" ' . $display->tooltip('Click to view Plans') . '></i></a></div>
<div class="mx-n4 mb-3 d-flex justify-content-center">
';
$show = false;
$buttonspacing='3 px-5';
if ($kioskmode) $show = true;
if (empty($current_user_data['account_plan'])) $show = true;


/// FREE PLAN OPTION
if ($show || (!empty($current_user_data['account_plan']) && ($current_user_data['account_plan'] != 'free'))) {
$optionProductData = $app->getProduct('free', 'user', '*', 1);
if ((isset($optionProductData['display_grouping_status'])) && ($optionProductData['display_grouping_status'] == 'active')) {
echo '
<input type="radio" class="btn-check ' . $buttonsize . ' " name="account_plan" value="'.$qik->encodeId($optionProductData['id']).'" id="btnradio1"  data-product-name="' . $optionProductData['account_plan'] . '" data-cy="btn-free">
<label class="btn ' . $buttonsize . ' btn-outline-success mx-'.$buttonspacing.'" for="btnradio1">' . $optionProductData['account_name'] . '</label>
';
}
}


/// GOLD PLAN OPTION
if ($show || (!empty($current_user_data['account_plan']) && ($current_user_data['account_plan'] != 'gold'))) {
    $optionProductData = $app->getProduct('gold', 'user', '*', 1);
if ((isset($optionProductData['display_grouping_status'])) && ($optionProductData['display_grouping_status'] == 'active')) {
echo '
<input type="radio" class="btn-check ' . $buttonsize . ' " name="account_plan" value="'.$qik->encodeId($optionProductData['id']).'" id="btnradio2"  data-product-name="' . $optionProductData['account_plan'] . '" data-cy="btn-gold">
<label class="btn ' . $buttonsize . ' btn-outline-success mx-'.$buttonspacing.'" for="btnradio2">' . $optionProductData['account_name'] . '</label>
';
}
}


/// LIFE PLAN OPTION
if ($show || (!empty($current_user_data['account_plan']) && ($current_user_data['account_plan'] != 'life'))) {
    $optionProductData = $app->getProduct('life', 'user', '*', 1);
if ((isset($optionProductData['display_grouping_status'])) && ($optionProductData['display_grouping_status'] == 'active')) {
echo '
<input type="radio" class="btn-check ' . $buttonsize . ' " name="account_plan" value="'.$qik->encodeId($optionProductData['id']).'" id="btnradio3"  data-product-name="' . $optionProductData['account_plan'] . '" data-cy="btn-life">
<label class="btn ' . $buttonsize . ' btn-outline-success mx-'.$buttonspacing.'" for="btnradio3">' . $optionProductData['account_name'] . '</label>
';
}
}



echo '
</div>

</div>
<div class="card-footer bg-light">
<input type="hidden" name="account_type" value="individual">
<!-- Wrapper div with tooltip -->
<div id="tooltipDiv1" ' . $display->tooltip("Select a plan first 👆") . '>
<button type="submit" id="usersubmitBtn" class="btn ' . $buttonsize . ' button btn-primary d-block mx-auto px-4" disabled data-cy="btn-planconfirm">I want this one!</button>
</form>
</div>
</div>
</div>
</div>
';





# PARENTAL ACCOUNT
#-------------------------------------------------------------------------------
/*
$optionProductData = $app->getProduct('gold', 'parental', '*', 1, 'parental');

echo '
<div class="col-12 col-md-12 '.$columnsize.' mb-4 account-type-card" data-target="#parental">
<div class="card bg-light border-1 h-100 d-flex flex-column">
<div class="card-body p-2">
<div class="d-flex align-items-center mb-3">
    <div class="flex-shrink-0">
        <div class="feature bg-dark bg-gradient text-white rounded-3"><i class="bi bi-people"></i></div>
    </div>
    <div class="flex-grow-1 d-flex justify-content-center">
        <h2 class="fs-2 fw-bold mb-0">Parental</h2>
    </div>
</div>
<p class="text-center mb-1 px-5">Choose a Parental Account to link and manage up to six children (age 0-16) accounts at reduced prices.</p>
<a href="#" class="secondary d-block text-center" data-bs-toggle="modal" data-bs-target="#parentalModal">Learn More</a>
</div>
<div class="card-footer bg-light">
<form name="accounttype" method="POST" id="multioptionparent" action="/signup" class="mt-3">
' . $display->inputcsrf_token() . '
<input type="hidden" name="account_plan" value="'.$qik->encodeId($optionProductData['id']).'"   data-product-name="' . $optionProductData['account_plan'] . '">
<button type="submit" id="usersubmitBtn" class="btn ' . $buttonsize . ' button btn-primary d-block text-center px-4" data-cy="btn-parental">Yes! I have kids to add.</a>
</form>
</div>
</div>
</div>
';
*/





# BUY GIFTCARD
#-------------------------------------------------------------------------------
$optionProductData = $app->getProduct('gold', 'giftcertificate', '*', 1 ,'giftcertificate');
echo '
<div class="col-12 col-md-12 '.$columnsize.' mb-4 account-type-card" data-target="#gift-certificate">
<div class="card bg-light border-1 h-100 d-flex flex-column">
<div class="card-body p-2">
<div class="d-flex align-items-center mb-3">
    <div class="flex-shrink-0">
        <div class="feature bg-dark bg-gradient text-white rounded-3"><i class="bi bi-gift"></i></div>
    </div>
    <div class="flex-grow-1 d-flex justify-content-center">
        <h2 class="fs-2 fw-bold mb-0">Gift Certificate</h2>
    </div>
</div>
<p class="text-center mb-1 px-5">Select this account type if you are intending to give this as a gift to someone. Gift Certificates do not expire.</p>
<a href="#" class="secondary d-block text-center" data-bs-toggle="modal" data-bs-target="#giftModal">Learn More</a>
</div>
<div class="card-footer bg-light">
    <div class="mb-2">
   
        <form name="accounttype" method="POST" id="multioptiongift" action="/signup" class="mt-3">
' . $display->inputcsrf_token() . '
<input type="hidden" name="account_plan" value="'.$qik->encodeId($optionProductData['id']).'"   data-product-name="' . $optionProductData['account_plan'] . '">
<button type="submit" id="usersubmitBtn" class="btn ' . $buttonsize . ' button btn-primary d-block text-center px-4" data-cy="btn-gift">Yep. This is a gift</a>
</form>
    </div>
    <div class="text-center">
        <a href="/redeem" class="text-dark">Redeem a gift certificate <i class="bi bi-arrow-right-square"></i></a>
    </div>
</div>
</div>
</div>
';






if ($seniormode) {
  # FAMILY ACCOUNT
  #-------------------------------------------------------------------------------
  echo '
<div class="col-12 col-md-12 '.$columnsize.' mb-5 mt-2 account-type-card" data-target="#family">
<div class="card bg-light border-1 h-100">
<div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
<div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-person-hearts"></i></div>
<h2 class="fs-4 fw-bold">Family</h2>
<p class="mb-0">Information for Family Account...</p>
<a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#familyModal">Learn More</a>
</div>
<div class="card-footer bg-light py-3">
<a href="/signup?family" class="btn btn-primary">I have a Family.</a>
</div>
</div>
</div>

</div>
';
}
if ($seniormode) {
  # CORPORATE ACCOUNT
  #-------------------------------------------------------------------------------
  echo '
<div class="col-12 col-md-12 '.$columnsize.' mb-5 mt-2 account-type-card" data-target="#corporate">
<div class="card bg-light border-1 h-100">
<div class="card-body text-center p-4 p-lg-5 pt-0 pt-lg-0">
<div class="feature bg-dark bg-gradient text-white rounded-3 mb-4 mt-n4"><i class="bi bi-buildings"></i></div>
<h2 class="fs-4 fw-bold">Corporate</h2>
<p class="mb-0">Information for Corporate Account...</p>
<a href="#" class="secondary" data-bs-toggle="modal" data-bs-target="#corporateModal">Learn More</a>
</div>
<div class="card-footer bg-light py-3">
<a href="/signup?corporate" class="btn btn-primary">I need a Corporate Account.</a>
</div>
</div>
</div>

</div>
';
}
if ($seniormode) {
  # BUSINESS ACCOUNT
  #-------------------------------------------------------------------------------
  echo '
<div class="col-12 col-md-12 '.$columnsize.' mb-5 mt-2 account-type-card" data-target="#business">
<div class="card bg-light border-1 h-100">
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

</div>
';
}


echo '
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
<p class="px-4">Since you are a Gold Plan member -- there is no additional costs/fees</p>
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
These accounts are automatically Gold Plan members and they receive all the features and benefits.  The most you\'ll pay for six children is $130.00 ever (incluing your account).</p>

<p>If you have any more questions about the Parental Account, feel free to <a href="/contact">contact us</a>.</p>

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
<li><strong>Instant Gifting:</strong> This account will be converted to a gift certificate instantly.  You will receive a downloadable Gift Certificate that you can give to your special someone.</li>
<li><strong>No Expiry:</strong> Our gift certificates have no expiration date.</li>
<li><strong>Personalized Message:</strong> Add a personalized message to make your gift even more special.</li>
</ul>

<h4>Cost:</h4>
<ul>
<li>There is no additional cost to you or the recipient.</li>
</ul>

<p>If you have any more questions about the Gift Certificate Account, feel free to <a href="/contact">contact us</a>.</p>

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
<p>Since you are a Gold Plan member -- there is no additional costs/fees.</p>

</div>
<div class="modal-footer">
<button type="button" class="btn  btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>


</div>
</div>               
';

echo $signupexit;

$footerattribute['postfooter'] = '
<script src="/public/js/myaccount.js"></script>

';


$footerattribute['bottomfooter'] = ' 
<script>
// Get the parent div of the button which has the tooltip
const tooltipDiv = document.getElementById("tooltipDiv1");

// Initialize the tooltip
const tooltipInstance = new bootstrap.Tooltip(tooltipDiv);

const radios = document.querySelectorAll(\'input[type=radio][name="account_plan"]\');
const submitBtn = document.getElementById("usersubmitBtn");

// Add event listener for each radio button
radios.forEach(function(radio) {
    radio.addEventListener("change", function() {
        if (this.checked) {
            submitBtn.removeAttribute("disabled");
            console.log("plan selected");
            
            // Dispose of the tooltip and remove the tooltip attribute
            if (tooltipInstance) {
                try {
                    tooltipInstance.dispose(); // Dispose of the tooltip instance
                    tooltipDiv.removeAttribute("title"); // Remove the tooltip title attribute
                    tooltipDiv.removeAttribute("data-bs-toggle"); // Remove the Bootstrap toggle attribute
                    tooltipDiv.classList.remove("tooltip"); // Optionally remove any tooltip-related classes
                } catch (error) {
                    console.error("Error disposing tooltip:", error);
                }
            }
        }
    });
});
</script>';




include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
