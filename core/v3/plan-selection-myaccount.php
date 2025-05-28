<?php
$liststyle='list-unstyled';
$promolink='';
if (isset($promodata['data']['code'])) $promolink='&promocode='.$promodata['data']['code'];

$links['registerfirst'] = [
'free' => '/register?plan=free',
'gold' => '/register?plan=gold'.$promolink,
'life' => '/register?plan=life'
];

$links['selectplan'] = [
'free' => '/applyplan_handler?_token=' . $display->inputcsrf_token('tokenonly') . '&redirect_status=free',
'gold' => '/applyplan?plan=gold',
'life' => '/applyplan?plan=life'
];


if (!empty($userregistrationdata[':email'])) {
$link['use'] = $links['registerfirst'];
} else {
$link['use'] = $links['selectplan'];
}
$link['use'] = $links['selectplan'];



$iconF='bi bi-emoji-neutral-fill me-2';
$iconG='bi bi-emoji-smile-fill text-dark me-2';
$iconL='bi bi-emoji-laughing-fill text-success me-2';
?>



<!-- Plans Start -->
<div class="container py-5 flex-grow-1">
<div class="text-center mx-auto mb-5 <?= $animatetag; ?>" data-wow-delay="0.1s">
<h6 class="text-primary text-uppercase mb-2">Our Plans</h6>
<h1 class="display-6">Birthday Gold Package Plans</h1>
</div>

<div class="row team-items">
<!-- PLAN ITEM  ------------------------------------------------------------------------------------  Start -->
<div class="col-lg-4 col-md-12 col-sm-12  <?= $animatetag; ?>pricing-plan" data-wow-delay="0.1s">
<div class="team-itemx position-relative">
<div class="position-relative text-center  pricing-plan-content ">
<div class="pricename price d-inline-block bg-primary text-white  px-4 mb-4 text-center">FREE</div>
<h5 class="mb-3">Always Free</h5>
<p class="mb-5 fst-italic ">DIY - Enroll Yourself<br>Make Your Own Celebration Plan</p>

<?PHP
echo '
<ul class="fa-ul mb-0 text-start '.$liststyle.'">
<li class="mb-4"><span class="fa-li"><i class="'.$iconF.'" data-bs-toggle="tooltip" data-bs-placement="right" title="Register your brands easily with our automated brand registration system.">
</i></span>View the '.$website['numberofbiz'].'+ participating brands</span></li>
<li class="mb-3"><span class="fa-li"><i class="'.$iconF.'"></i></span>You visit their websites & self-enroll</li>
<li class="mb-3"><span class="fa-li"><i class="'.$iconF.'"></i></span>Self-tracking of all the benefits & freebies</li>
<li class="mb-3"><span class="fa-li"><i class="'.$iconF.'"></i></span>Enjoy things on your schedule</li>
</ul>
';
?>
</div>
<div class="text-white plan-button mt-5 d-none">
<a class="btn btn-outline-primary border-2 mb-5 plan-button-button" href="<?= $link['use']['free']; ?>">SELECT THIS PLAN</a>
</div>
</div>
</div>
<!-- PLAN ITEM  ------------------------------------------------------------------------------------  Start -->
<?PHP
echo '
<div class="col-lg-4 col-md-12 col-sm-12  ' . $animatetag . 'pricing-plan" data-wow-delay="0.1s">
<div class="team-itemx position-relative">
<div class="position-relative text-center pricing-plan-content ">
<div class="pricename price d-inline-block bg-primary text-white  px-4 mb-4 text-center">

';
if ($gotvalidpromo) echo '<span class="oldprice">$10 / Year</span>';
else
echo '$10 / Year';


echo '</div>
<h5 class="mb-3">Auto Renews / Cancel Anytime</h5>
<p class="mb-5 fst-italic ">We Do The Work, You reap the rewards!</p>

<ul class="fa-ul mb-0 text-start '.$liststyle.'">
<li class="mb-3"><span class="fa-li"><i class="'.$iconG.'"></i></span>Auto-Enroll you up to 20 brands</li>
<li class="mb-3"><span class="fa-li"><i class="'.$iconG.'"></i></span>Advanced celebration planning tools</li>
<li class="mb-3"><span class="fa-li"><i class="'.$iconG.'"></i></span>Reminders of upcoming benefits</li>
<li class="mb-3"><span class="fa-li"><i class="'.$iconG.'"></i></span>Priority email support</li>
<li class="mb-3"><span class="fa-li"><i class="'.$iconG.'"></i></span>Mobile app access</li>
</ul>


';
echo '<div class="d-none">';
if (!$gotvalidpromo) {
echo '<div class="mt-5">';
if ($invalidvalidpromo) {

echo $promofailedmessage;
}
echo '
<form action="/plans" method="post">';
echo $display->inputcsrf_token();
echo '
<div class="input-group">
<input type="text" class="form-control" name="promocode" id="promocode" value="" maxlength="10" placeholder="Promo Code">
<button class="btn btn-dark" type="submit">Apply Code</button>
</div>
</form>
';
} else {
## display promo code discount

echo '<div class="mt-3">' . $promosuccessmessage . '';
}
echo '</div>

</div>
</div>
';
?>
<div class="text-white plan-button mt-5 d-none">
<a class="btn btn-outline-primary border-2 mb-5 plan-button-button" href="<?= $link['use']['gold']; ?>">SELECT THIS PLAN</a>

</div>
</div>
</div>
<!-- PLAN ITEM  ------------------------------------------------------------------------------------  Start -->
<div class="col-lg-4 col-md-12 col-sm-12  <?= $animatetag; ?>pricing-plan" data-wow-delay="0.1s">
<div class="team-itemx position-relative">
<div class="position-relative text-center pricing-plan-content ">
<div class="pricename price d-inline-block bg-primary text-white  px-4 mb-4 text-center">$40 / Lifetime</div>
<h5 class="mb-3">One Time Payment for Life</h5>
<p class="mb-5 fst-italic ">We got you covered for the rest of your life.<br>You get all the features!</p>
<?PHP
echo '
<ul class="fa-ul mb-0 text-start '.$liststyle.'">
<li class="mb-4"><span class="fa-li"><i class="'.$iconL.'"></i></span>Auto-Enroll you up to 40 brands per year</li>
<li class="mb-4"><span class="fa-li"><i class="'.$iconL.'"></i></span>Exclusive lifetime deals</li>
<li class="mb-4 d-none"><span class="fa-li"><i class="'.$iconL.'"></i></span>Enrolled in our upcoming Gold Pot Giveaways</li>
<li class="mb-4"><span class="fa-li"><i class="'.$iconL.'"></i></span>Lifetime access to new particpating brands</li>
<li class="mb-4"><span class="fa-li"><i class="'.$iconL.'"></i></span>Create Special Accounts (Parental & Gift Certificates)</li>
<li class="mb-4"><span class="fa-li"><i class="'.$iconL.'"></i></span>Get a Birthday Gold Email to streamline enrollments</li>
</ul>
';

?>
</div>
<div class="text-white plan-button mt-5 d-none">
<a class="btn btn-outline-primary border-2 mb-5 plan-button-button" href="<?= $link['use']['life']; ?>">SELECT THIS PLAN</a>
</div>
</div>


</div>
</div>
<div class="text-center<?= $animatetag; ?>" data-wow-delay="0.39s">
<a class="btn fw-bold  text-white border-2 mt-5 bg-dark px-5" href="plans?learn=more">LEARN MORE ABOUT THESE PLANS</a>
</div>

</div>
</div>