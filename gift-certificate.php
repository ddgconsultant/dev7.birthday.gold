<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$pagedata['pagetitle']='Gold Plan Gift Certificate Online - Birthday Gold';
$pagedata['metakeywords']='Gift Certificate, Gold Plan Gift Certificate, Gold Plan Gift Certificate Online';
$pagedata['metadescriptions']='Get a Gold Plan Gift Certificate Online! The perfect gift for any occasion. Give the gift of celebration with our Gold Plan Gift Certificate — perfect for making birthdays unforgettable.';



include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');



echo '
<!-- Main Content -->
<div class="container main-content">
    <div class="row">
    <div class="col-12 text-center my-5">
    <h1>Gold Plan Gift Certificate Online</h1>
    <h2 class="fw-bold">Buy a Birthday.Gold Gift Certificate</h2>
    <p class="lead">Give the gift of celebration with our Gold Plan Gift Certificate — perfect for making birthdays unforgettable.</p>
    </div>
        <div class="col-6">
            <img src="/public/images/sample-gc.jpg" class="img-fluid rounded" alt="Gift Certificate Image">
        </div>
        <div class="col-6 mt-5">
            <h2 >Gold Plan Gift Certificate</h2>
            <p>Make their birthday special with exclusive benefits, premium services, and a personalized experience.</p>
            <ul class="list-unstyled mb-5">
                <li><i class="bi bi-check-circle-fill text-success"></i> Exclusive Benefits</li>
                <li><i class="bi bi-check-circle-fill text-success"></i> Premium Services</li>
                <li><i class="bi bi-check-circle-fill text-success"></i> Personalized Experience</li>
            </ul>
            <a href="/signup?account_plan='.$qik->encodeId(301).'" class="btn btn-primary btn-lg mt-3">Buy Now</a>
        </div>
    </div>
</div>
';


include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
