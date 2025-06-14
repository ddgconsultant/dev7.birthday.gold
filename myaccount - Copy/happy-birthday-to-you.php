<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');



$till=$app->getTimeTilBirthday($current_user_data['birthdate']);
#$till = ['days' => 0]; // This would be calculated from the actual birthday

// Check if today is the user's birthday or if the notification is dismissed
$sql = 'update  bg_user_attributes set `status`="completed", modify_dt=now() where user_id=:user_id and `name` like "myaccount_redirect_happybirthday_%"';
$stmt = $database->prepare($sql);
$stmt->execute(['user_id' => $current_user_data['user_id']]);

if ($till['days'] == 0 && !($session->get('footerjs_dismiss_alert') === 'true' || isset($dismissible_user_notification))) {

    $bodycontentclass=''; // Custom class to handle full-screen layout

    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    $additionalstyles.='<style>.fs-12 {
        font-size: 12px;
    }    
    .text-light {        color: #ddd !important; /* Bootstrap light color equivalent, or adjust as needed */    }</style>';
    // Show the birthday notification and the dismiss button
    echo '

   <div class="alert alert-dismissible fade show text-center full-page" role="alert" style="position: relative; display: flex; align-items: center; justify-content: center; margin: 0; padding: 0; height: calc(100vh - 150px); width: 100vw;">
        <img src="/public/images/happy_birthday_329913658.jpg" alt="Happy Birthday" class="img-fluid" style="max-width: 100vw; max-height: calc(100vh - 150px); object-fit: contain;">
        
        <!-- Invisible button over the image -->
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" data-message-id="birthday_notification" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; background: transparent; cursor: pointer;"></button>
   
   
       <!-- Audio element for Happy Birthday music -->
<audio autoplay>
    <source src="/public/audio/happy-birthday-155461.mp3" type="audio/mpeg">
    Your browser does not support the audio element.
</audio>


             </div>
<div class="container text-center">Click image to return to your home page</div>
             <!-- Attribution for the audio -->
             <p class="fs-12 text-light ms-3">Music by <a href="https://pixabay.com/users/wavemaster-13802185/" target="_blank"  class="fs-12 text-light">Wavemaster on Pixabay</a></p>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".alert .btn-close").forEach(function(button) {
                button.addEventListener("click", function() {
                    const messageId = button.getAttribute("data-message-id");
                    
                    if (messageId) {
                        fetch("/siteactions/dismissmessage?midtag=" + encodeURIComponent(messageId), {
                            method: "GET",
                            headers: {
                                "Content-Type": "application/json"
                            }
                        })
                        .then(response => response.text())
                        .then(data => {
                            console.log("Message status updated:", data);
                            window.location.href = "/myaccount/"; // Redirect to myaccount page
                        })
                        .catch(error => console.error("Error:", error));
                    }
                });
            });
        });
    </script>
    ';

    $display_footertype = 'none';   
    include($dir['core_components'] . '/bg_footer.inc');
    $app->outputpage();

} else {
    // User already dismissed the notification or it's not their birthday
    header("Location: /myaccount/");
    exit;
}
?>
