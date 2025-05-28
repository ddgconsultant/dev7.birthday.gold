<?php
// Get business hours settings
$businessHours = $app->bg_businesshours();

// Only enable chat during business hours and non-holidays
$enablechat = true;
if (!empty($forcefalseenablechat)) {
    $enablechat = false;
}

// Check if outside business hours or holiday
if (!$businessHours['isBusinessHours'] || $businessHours['isHoliday']) {
    $enablechat = false;
}

if (!isset($footeroutput)) $footeroutput = '';

if ($enablechat) {
    $chatsystem = 'rocket';

    switch ($chatsystem) {
        case 'rocket':
            $footeroutput .= "<!-- Start of Rocket.Chat Livechat Script -->
            <script>
            (function(w, d, s, u) {
                w.RocketChat = function(c) { w.RocketChat._.push(c) }; w.RocketChat._ = []; w.RocketChat.url = u;
                var h = d.getElementsByTagName(s)[0], j = d.createElement(s);
                j.async = true; 
                j.src = 'https://chat.birthdaygold.cloud/livechat/rocketchat-livechat.min.js?_=201903270000';
                j.crossOrigin = 'anonymous'; // Adding the crossorigin attribute
                h.parentNode.insertBefore(j, h);
            })(window, document, 'script', 'https://chat.birthdaygold.cloud/livechat');
            </script>
            ";
            break;
            
        case 'tawk':
            $footeroutput .=  '
            <!--Start of Tawk.to Script-->
            <script>
            var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
            (function(){
            var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
            s1.async=true;
            s1.src="https://embed.tawk.to/64d42e54cc26a871b02e57a7/1h7ed9udu";
            s1.charset="UTF-8";
            s1.setAttribute("crossorigin","*");
            s0.parentNode.insertBefore(s1,s0);
            })();
            </script>
            <!--End of Tawk.to Script-->
            ';
            break;
    }
}