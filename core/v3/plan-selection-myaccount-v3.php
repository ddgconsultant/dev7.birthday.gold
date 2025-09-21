<?php
$additionalstyles .= '
<style >
/* Popular Plan Badge */
.pricing-option_popular { position: absolute;  top: -10px; /* Move it slightly above the container */
    left: -10px; /* Align it slightly to the left */ }
.pricing-option_popular::before { content: "Popular"; position: absolute; top: 0; left: 0; background-color: #ed3e49; color: white; padding: 5px; font-size: 12px; font-weight: bold; border-radius: 0 0 5px 0; }
.learn-more-btn { background-color: #212529; color: white; text-align: center; padding: 10px 30px; margin-top: 20px; display: inline-block; border-radius: 0.25rem; }
.learn-more-btn:hover { background-color: #000; text-decoration: none; }
/* Additional hover styles */
.highlight-column td, .highlight-column th {
    background-color: #f8f9fa !important;
}

/* Media query for smaller screens (e.g., mobile) */
@media (max-width: 768px) {
    .pricing-option_popular {
        font-size: 10px; /* Adjust the font size for smaller screens */
        top: -35px; /* Adjust the top position */
        left: -10px; /* Adjust the left position */
        padding: 3px; /* Smaller padding */
    }
</style>

';


echo '

<div class="container my-5 main-content">
    <div class="col-12">

      <div class="container text-center my-5">
      <h1 class="fw-bold mt-0 pt-0 mb-3">Birthday Deals Online</h1>
  <h2 class="fw-bold">Choose the Perfect Birthday Plan</h2>
  <p class="fs-5 text-muted">
     At Birthday Gold, select the <strong>Free Plan</strong> to get started or upgrade to the <strong>Gold Plan</strong> for automatic enrollments and premium support. Pick the plan that fits your celebration needs!
  </p>
</div>


    <h1>Our Plans</h1>  

        <div class="table-responsive border  p-3 rounded bg-dark-subtle">
            <table class="table table-bordered align-middle bg-white">
                <thead class="border-2">
                    <tr class="table-row">
                        <th></th>
                        <th class="table-header">
                            <div class="text-center ">
                                <h1 class="fw-bold">FREE</h1>
                                <p class="mb-0">Free Forever Plan</p>
                                <small>No Credit Card Required</small>
                            </div>
                        </th>
';
$optionProductData_free = $app->getProduct('free', 'user', '*', 1);
$optionProductData_gold = $app->getProduct('gold', 'user', '*', 1);
$plandatafeatures_free=$app->plandetail('details_id', $optionProductData_free['id']);
$plandatafeatures_gold=$app->plandetail('details_id', $optionProductData_gold['id']);

echo '                        <th class="table-header">
                            <div class="text-center position-relative">
                                <h1 class="fw-bold">GOLD</h1>
                                <p class="mb-0">Paid Plan</p>
                                <small>'.$qik->convertamount($plandatafeatures_gold['price'], '$',  'Free', 0).' (One-time Payment)</small>
                                <div class="pricing-option_popular"></div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-row">
                        <td class="fw-bold">View the '.$website['numberofbiz'].'+ participating brands</td>
                        <td class="text-center xhover"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center xhover"><i class="bi bi-check-circle-fill text-success"></i></td>
                    </tr>
                    <tr class="table-row">
                        <td class="fw-bold">Self-enroll & track benefits</td>
                        <td class="text-center xhover"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center xhover"><i class="bi bi-check-circle-fill text-success"></i></td>
                    </tr>
                    <tr class="table-row">
                        <td class="fw-bold">Auto-enroll Brand Rewards</td>
                        <td class="text-center xhover fw-bold">'.$plandatafeatures_free['max_business_select'].'</td>
                        <td class="text-center xhover fw-bold">'.$plandatafeatures_gold['max_business_select'].' Every Year!</td>
                    </tr>
                    <tr class="table-row">
                        <td class="fw-bold">Exclusive lifetime deals</td>
                        <td class="text-center xhover"><i class="bi bi-dash text-muted"></i></td>
                        <td class="text-center xhover"><i class="bi bi-check-circle-fill text-success"></i></td>
                    </tr>
                    <tr class="table-row">
                        <td class="fw-bold">Create Special Accounts</td>
                        <td class="text-center xhover"><i class="bi bi-dash text-muted"></i></td>
                        <td class="text-center xhover"><i class="bi bi-check-circle-fill text-success"></i></td>
                    </tr>
                    <tr class="table-row">
                        <td class="fw-bold">Birthday Gold Managed Email Account</td>
                        <td class="text-center xhover"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center xhover"><i class="bi bi-check-circle-fill text-success"></i></td>
                    </tr>
                    <tr class="table-row">
                        <td></td>
                        <td class="text-center xhover"><a href="/signup?plan=free" class="btn btn-primary px-5">Get Free</a></td>
                        <td class="text-center xhover"><a href="/signup?plan=gold" class="btn btn-primary px-5">Get Gold</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        ';
/*
        echo '
 <div class="text-center mt-4">
            <a class="btn btn-lg fw-bold text-white border-2 mt-5 bg-dark px-5" href="plans?learn=more">LEARN MORE ABOUT THESE PLANS</a>
        </div>
';
*/

echo '    </div>
</div>';


?>

<script>
$(document).ready(function() {
		$('.table-row').hover(function() {             
			$(this).addClass('current-row');
		}, function() {
			$(this).removeClass('current-row');
		});
	   
		$("th").hover(function() {
			var index = $(this).index();
			$("th.table-header, td").filter(":nth-child(" + (index+1) + ")").addClass("current-col");
			$("th.table-header").filter(":nth-child(" + (index+1) + ")").css("background-color","#999")
		}, function() {
			var index = $(this).index();
			$("th.table-header, td").removeClass("current-col");
			$("th.table-header").filter(":nth-child(" + (index+1) + ")").css("background-color","#F5F5F5")
		});
	}); 
</script>
