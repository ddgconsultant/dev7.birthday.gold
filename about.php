<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$pagedata['pagetitle']='Birthday Freebies Service Online - Birthday Gold';
$pagedata['metakeywords']='Birthday Freebies, Birthday Freebies Online, Birthday Freebies Near Me, Birthday Freebies Service, Freebies on Birthday';
$pagedata['metadescriptions']='Get the best Birthday Freebies Online & Near Me! Enjoy exclusive Freebies on Birthday with our top Birthday Freebies Service. Sign up now!';


include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>
<!-- Navbar End -->




    <!-- About Start -->
    <div class="container-xxl py-6 flex-grow-1">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6">
                    <div class="position-relative overflow-hidden pt-5 h-100" style="min-height: 400px;">
                       <img class="position-absolute w-100 h-100" src="/public/images/IMG_6318.jpg" alt="" style="object-fit: cover;">
                       <!--    <img class="position-absolute top-0 start-0 bg-white pe-3 pb-3" src="/public/images/IMG_6318.jpg" alt="" style="width: 200px; height: 200px;"> -->
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="h-100">
                        <h6 class="text-primary text-uppercase mb-2">About Us</h6>

                        <?PHP


if (!empty($enableadminpageeditor)) {    $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
echo '
<h1>Birthday Freebies Service Online</h1>
<h2 class="mb-4">Celebrate Your Birthday with Us!</h2>
<p>At Birthday.Gold, our mission is simple: we want everyone to enjoy their birthdays to the fullest. It all started when we saw someone joyfully celebrating with birthday freebies, but they didn’t share how they got them. We realized that not everyone knows the ins and outs of signing up for these perks or navigating the process to claim them. It’s more than just walking into a business and asking for your birthday treat—it can be a bit complicated.</p>
<p class="mb-4">That’s why we’re here to make it easy for you. With Birthday.Gold, all you have to do is sign up for our service, select your favorite businesses, and we’ll handle the rest. You’ll receive notifications, a handy map of where to go, and all that’s left for you to do is celebrate and enjoy!
</p>
<div class="row g-2 mb-4 pb-2">
    <div class="col-sm-6">
    <i class="bi bi-check2-square text-success me-2"></i>Sign up for our service.
    </div>
    <div class="col-sm-6">
    <i class="bi bi-check2-square text-success me-2"></i>Select '.$website['biznames'].' rewards.
    </div>
    <div class="col-sm-6">
    <i class="bi bi-check2-square text-success me-2"></i>Get notified and get a map.
    </div>
    <div class="col-sm-6">
    <i class="bi bi-check2-square text-success me-2"></i>Celebrate and enjoy.
    </div>
</div>
<div class="row g-4">
    <div class="col-sm-6">
        <a class="btn btn-primary py-3 px-5" href="/how">Learn More</a>
    </div>
';
### ADMIN PAGE EDITOR: END-body-1 ###
?>

                        <div class="col-sm-6">
                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- About End -->





<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
