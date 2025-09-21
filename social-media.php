<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$additionalstyles .= '
<style>
    .social-media-box {
        padding: 10px;
        max-height: 250px;
        text-align: center;
        width: 100%;
    }
    .social-media-icon {
        font-size: 4rem;
    }
    .social-media-name {
        font-size: 1.5rem;
        font-weight: bold;
    }
    .social-media-handle {
        font-size: 1rem;
        color: #6c757d;
    }
</style>
';
?>


<!-- Header -->
<section class="container main-content py-3">
    <div class="container">
        <h1 class="text-center fw-bold">Follow Birthday.Gold on Social Media</h1>
        <p class="text-center fs-4">
        Join us on our social media platforms to stay
        connected with the latest news, exciting events, and all things Birthday Gold. Whether you're looking for
     updates on our latest offerings, or just some fun and engaging
        content, our social media channels have got you covered.
    </p>
    </div>


    <?php
echo '
<!-- Social Media Section -->
<div class="album py-5 bg-light">
    <div class="container">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 justify-content-center">
            <div class="col">
                <div class="card shadow-sm social-media-box">
                    <a href="' . $display->socialapplink('twitter', 'https://twitter.com/birthday_gold', 'url') . '"  target="smwindow" class="text-decoration-none">
                        <div class="card-body">
                            <i class="bi bi-twitter social-media-icon"></i>
                            <p class="social-media-name">Twitter</p>
                            <p class="social-media-handle">@birthday_gold</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm social-media-box">
                    <a href="' . $display->socialapplink('facebook', 'https://www.facebook.com/birthdaygold/', 'url') . '"  target="smwindow" class="text-decoration-none">
                        <div class="card-body">
                            <i class="bi bi-facebook social-media-icon"></i>
                            <p class="social-media-name">Facebook</p>
                            <p class="social-media-handle">/birthdaygold</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm social-media-box">
                    <a href="' . $display->socialapplink('instagram', 'https://www.instagram.com/birthday_gold/', 'url') . '"  target="smwindow" class="text-decoration-none">
                        <div class="card-body">
                            <i class="bi bi-instagram social-media-icon"></i>
                            <p class="social-media-name">Instagram</p>
                            <p class="social-media-handle">@birthday_gold</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm social-media-box">
                    <a href="' . $display->socialapplink('linkedin', 'https://www.linkedin.com/company/birthdaygold', 'url') . '"  target="smwindow" class="text-decoration-none">
                        <div class="card-body">
                            <i class="bi bi-linkedin social-media-icon"></i>
                            <p class="social-media-name">LinkedIn</p>
                            <p class="social-media-handle">/company/birthdaygold</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm social-media-box">
                    <a href="' . $display->socialapplink('tiktok', 'https://www.tiktok.com/@birthday.gold', 'url') . '"  target="smwindow" class="text-decoration-none">
                        <div class="card-body">
                            <i class="bi bi-tiktok social-media-icon"></i>
                            <p class="social-media-name">TikTok</p>
                            <p class="social-media-handle">@birthday.gold</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm social-media-box">
                    <a href="' . $display->socialapplink('youtube', 'https://www.youtube.com/@birthdaygold', 'url') . '"  target="smwindow" class="text-decoration-none">
                        <div class="card-body">
                            <i class="bi bi-youtube social-media-icon"></i>
                            <p class="social-media-name">YouTube</p>
                            <p class="social-media-handle">@birthdaygold</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm social-media-box">
                    <a href="' . $display->socialapplink('pinterest', 'https://www.pinterest.com/birthdaygold/', 'url') . '"  target="smwindow" class="text-decoration-none">
                        <div class="card-body">
                            <i class="bi bi-pinterest social-media-icon"></i>
                            <p class="social-media-name">Pinterest</p>
                            <p class="social-media-handle">/birthdaygold</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>';


?>


</section>
<!-- Footer -->
<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>
