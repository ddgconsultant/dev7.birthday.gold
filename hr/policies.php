<?php

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


include($_SERVER['DOCUMENT_ROOT'] . '/hr/policies_text.inc');




#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------

$headerattribute['additionalcss']='<link rel="stylesheet" href="/public/css/myaccount.css">';

include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/header.php'); 
echo '<section>';
$search=['</p>-<br />'];
$replace=["</p>\n"];

foreach ($policies as $policy_name =>$policy_text) {
echo '
<div class="container mt-4 my-5">
<hr>
    <h1 class="text-center mb-4">'.$policy_name.'</h1>
    
    <div class="accordion" id="policyAccordion">
        <!-- Social Media Conduct -->
        <div class="accordion-item">

            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#policyAccordion">
                <div class="accordion-body p-5 fs-4">                  
           '.str_replace($search, $replace, nl2br($policy_text)).'
                </div>
            </div>
        </div>

    </div>
</div>
';
}
echo '</section>
</div>';

include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footer.inc');
include($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/footerjs.inc');
?>

</body>

</html>