<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">';
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
?>

<div class="container-xl px-4 mt-4">
    <!-- Account page navigation-->

<?PHP  include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); 
$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
$astrosign=$app->getastrosign($current_user_data['birthdate']);
$astroicon=$app->getZodiacInfo($astrosign);
$alive=$app->calculateage($current_user_data['birthdate']);

    echo '
    <hr class="mt-0 mb-4">
    
    <div class="container">
    <div class="row">

        <div class="col-xl-4">
            <!-- Profile picture card-->
            <div class="card mb-4 mb-xl-0">
                <div class="card-header">'.$current_user_data['first_name'].' '.$current_user_data['last_name'].'</div>
                <div class="card-body text-center">
                    <!-- Profile picture image-->
                    <img class="img-account-profile rounded-circle mb-2" src="/public/images/defaultavatar.png" alt="">
                    <!-- Profile picture help block-->
                    <div class="small font-italic text-muted mb-4">JPG or PNG no larger than 5 MB</div>
                    <!-- Profile picture upload button-->
                    <button class="btn btn-primary" type="button">Upload new image</button>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
        <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Billing card 2-->
            <div class="card h-100 border-start-lg border-start-secondary">
                <div class="card-body">
                    <div class="small text-muted">Days until your birthday</div>
                    <div class="h3">'.$till['days'].' days</div>
                    <a class="text-arrow-icon small text-secondary" href="/myaccount/events">
                        Exciting Events
                        <i class="bi bi-arrow-right-square"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <!-- Billing card 3-->
            <div class="card h-100 border-start-lg border-start-success">
                <div class="card-body">
                    <div class="small text-muted">Your Sign</div>
                    <div class="h3 d-flex align-items-center">'.$astroicon['symbol'].' '.ucfirst($astrosign).'</div>
                    <a class="text-arrow-icon small text-success" href="/myaccount/horoscope">
                        Horoscope
                        <i class="bi bi-arrow-right-square"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <!-- Billing card 2-->
            <div class="card h-100 border-start-lg border-start-secondary">
                <div class="card-body">
                    <div class="small text-muted">You have been alive for</div>
                    <div class="h3">'.number_format($alive['days']).' days</div>
                    <a class="text-arrow-icon small text-secondary" href="/myaccount/stats">
                        Interesting stats
                        <i class="bi bi-arrow-right-square"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <!-- Billing card 3-->
            <div class="card h-100 border-start-lg border-start-success">
                <div class="card-body">
                    <div class="small text-muted">Your Sign</div>
                    <div class="h3 d-flex align-items-center">Blah Blah</div>
                    <a class="text-arrow-icon small text-success" href="#!">
                        Read
                        <i class="bi bi-arrow-right-square"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
        <!-- Billing card 3-->
        <div class="card h-100 border-start-lg border-start-success">
            <div class="card-body">
                <div class="small text-muted">Your Sign</div>
                <div class="h3 d-flex align-items-center">Yada Yada</div>
                <a class="text-arrow-icon small text-success" href="#!">
                    More
                    <i class="bi bi-arrow-right-square"></i>
                </a>
            </div>
        </div>
        </div>
        <div class="col-lg-4 mb-4">
        <!-- Billing card 3-->
        <div class="card h-100 border-start-lg border-start-success">
            <div class="card-body">
                <div class="small text-muted">Your Sign</div>
                <div class="h3 d-flex align-items-center">Hella Cool</div>
                <a class="text-arrow-icon small text-success" href="#!">
                    Rad
                    <i class="bi bi-arrow-right-square"></i>
                </a>
            </div>
        </div>
    </div>

        </div>
    </div>
    </div>
</div>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
