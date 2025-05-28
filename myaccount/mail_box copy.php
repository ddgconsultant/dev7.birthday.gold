<?php
$addClasses[] = 'Mail';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$errormessage = '';
#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------





$transferpagedata['message'] = $errormessage;
$transferpagedata = $system->startpostpage($transferpagedata);
#breakpoint($transferpagedata);
$additionalstyles .= '<link rel="stylesheet" href="/public/css/login.css">
<!-- Bootstrap CSS -->

<link href="/public/mailassets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
<link href="/public/mailassets/css/app.css" rel="stylesheet">
<link href="/public/mailassets/css/icons.css" rel="stylesheet">

<style>
.footerxcontent {
display: none;
}
.hidden {
display: block !important;
}


.email-wrapper {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 485px) !important; /* Subtract header (65px) and footer (120px) */
    height: calc(100vh - 485px) !important; /* Full height of the viewport minus header and footer */
}


.email-content {
    display: flex;
    flex-direction: column;
    flex-grow: 1; /* Takes the remaining space inside .email-wrapper */
    min-height: 0; /* Ensures that .email-content can shrink */
   overflow-y: auto !important; /* Makes the list scrollable when necessary */
  
}

/* Thin scrollbar styling */
.container {
  scrollbar-width: thin; /* Firefox */
  scrollbar-color: #ccc transparent; /* Firefox */
}

.container::-webkit-scrollbar {
  width: 6px;
}

.container::-webkit-scrollbar-track {
  background: transparent;
}

.container::-webkit-scrollbar-thumb {
  background-color: #ccc; /* Scrollbar thumb color */
  border-radius: 4px;
}

.container::-webkit-scrollbar-thumb:hover {
  background-color: #aaa; /* Scrollbar hover color */
}

</style>';
$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');


?>



<div class="container main-content">
 <div class="row ">
  <div class="container">
   <!--start email wrapper-->
   <div class="email-wrapper mt-5 ">


    <?PHP

    $hasmessages = false;
    $messages = $mail->getmessagelist($current_user_data['user_id'], 'user');


    if (!empty($messages)) {
     # $mailcount=$mail->mailcount($current_user_data['user_id']);
     $countmessages = $mailcount = count($messages);
     $hasmessages = true;
     #$countmessages=count($messages);
     #$countmessages=$mailcount['total'];
    }

    include($_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/nav-mailmenuside.php');

    #include ($_SERVER['DOCUMENT_ROOT'].'/core/'.$website['ui_version'].'/nav-mailmenutop.php'); 
    ?>



    <div class="email-content ">
     <div class="">
      <div class="email-listx ">
       <?PHP


       # SHOW MESSAGES
       #-------------------------------------------------------------------------------
	   if ($hasmessages) {
		echo '
		<div class="container" style="overflow-y: auto;">
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h4 class="mb-0">
					Messages
					<span class="badge bg-primary rounded-pill ms-2">'
					. number_format(count($messages)) . ' / ' . number_format($featuremailcount['unread']) .
					'</span>
				</h4>
				<button class="btn btn-outline-secondary btn-sm" id="filterButton">
					<i class="bi bi-filter"></i> Filter/Search
				</button>
			</div>
			<!-- Column Headers -->
			<div class="row align-items-center px-3 py-2 border-bottom bg-light fw-bold" style="margin: 0;">
				<div class="col-auto d-flex" style="width: 50px;">
					<input class="form-check-input" type="checkbox" id="selectAllCheckbox" data-bs-toggle="tooltip" title="Select All">
				</div>
				<div class="col-auto" style="width: 200px;">
					<span>Company</span>
					<i class="bi bi-arrow-down-up sortable"></i>
				</div>
				<div class="col" style="width: 350px;">
					<span>Subject</span>
					<i class="bi bi-arrow-down-up sortable"></i>
				</div>
				<div class="col-auto d-flex justify-content-end" style="width: 80px;">
					<span>Date</span>
					<i class="bi bi-arrow-down-up sortable"></i>
				</div>
			</div>';
	
		// Loop through messages
		foreach ($messages as $message) {
			$date = new DateTime($message['create_dt']);
			$today = new DateTime();
			$dateformat = ($date->format('Y-m-d') === $today->format('Y-m-d')) ? 'h:i a' : 'M j';
			$message['create_dt_formatted'] = $display->formatdate($message['create_dt'], $dateformat);
	
			$item_company = !empty($message['company_id']) && $message['company_id'] != 0
				? $app->getcompany($message['company_id'])
				: [];
	
			$item_company['company_display_name'] = $item_company['company_display_name'] ?? 'Freebie Company';
			$item_company['logo_url'] = !empty($item_company['company_logo'])
				? $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo'])
				: '';
	
			$messagereadflag = ($message['processstatus'] === 'read') ? '' : 'fw-bold';
	
			echo '
			<div class="row align-items-center email-message px-3 py-2 border-bottom">
				<div class="col-auto d-flex align-items-center email-actions" style="width: 50px;">
					<div class="form-check m-0">
						<input class="form-check-input" type="checkbox" id="emailCheckbox' . $message['message_id'] . '">
						<label class="form-check-label" for="emailCheckbox' . $message['message_id'] . '"></label>
					</div>
					<i class="bi bi-star ms-1 email-star"></i>
				</div>
				<a href="/myaccount/mail_read?m=' . $qik->encodeID($message['message_id']) . '" class="text-decoration-none text-dark">
					<div class="col-auto d-flex align-items-center email-sender text-truncate ' . $messagereadflag . '" style="width: 200px;">
						' . (!empty($item_company['logo_url']) ? '<img src="' . htmlspecialchars($item_company['logo_url']) . '" width="24" height="24" class="me-2">' : '') . '
						<span class="text-truncate">' . $item_company['company_display_name'] . '</span>
					</div>
					<div class="col d-flex align-items-center email-subject text-truncate ' . $messagereadflag . '" style="width: 350px;">
						' . $message['subject'] . '
					</div>
					<div class="col-auto email-time text-end text-muted ' . $messagereadflag . '" style="width: 80px;">
						' . $message['create_dt_formatted'] . '
					</div>
				</a>
			</div>';
		}
	
		echo '
		</div>
		<script>
			document.addEventListener("DOMContentLoaded", function () {
				const selectAllCheckbox = document.getElementById("selectAllCheckbox");
				if (selectAllCheckbox) {
					selectAllCheckbox.addEventListener("change", function () {
						const checkboxes = document.querySelectorAll(".email-message .form-check-input:not(#selectAllCheckbox)");
						checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
					});
				}
			});
		</script>';
	} else {
		echo '
		<div class="row align-items-center">
			<div class="col-auto">
				<picture>
					<source srcset="https://fonts.gstatic.com/s/e/notoemoji/latest/1f636_200d_1f32b_fe0f/512.webp" type="image/webp">
					<img src="https://fonts.gstatic.com/s/e/notoemoji/latest/1f636_200d_1f32b_fe0f/512.gif" alt="😶" width="128" height="128">
				</picture>
			</div>
			<div class="col-auto">
				<h2 class="m-0 p-0">Your inbox is empty.</h2>
			</div>
		</div>
		<script>
			setTimeout(function() {
				location.reload();
			}, 90000);
		</script>';
	}
	?>


      </div>
     </div>

     <!--start email overlay-->
     <div class="overlay email-toggle-btn-mobile"></div>
     <!--end email overlay-->
    </div>
    <!--end email wrapper-->
   </div>
  </div>



 </div>
</div>

<!--plugins-->

<script src="/public/mailassets/js/jquery.min.js"></script>
<script src="/public/mailassets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script>
 new PerfectScrollbar('.email-navigation');
 new PerfectScrollbar('.email-list');
</script>
<!--app JS-->
<script src="/public/mailassets/js/app.js"></script>



<?PHP
$display_footertype = 'min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
