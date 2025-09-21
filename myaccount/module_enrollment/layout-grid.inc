<?php
// File: /myaccount/module_enrollment/layout-grid.inc


foreach ($categories as $category) {
if ($counter['total'] > $resultsize) continue;
$categorylower = strtolower($category);

$records = $app->getSelectionCompanies(500, $category);

$uniqueRecords = array_values(array_unique(array_map(function ($record) {
return serialize($record);
}, $records)));

$uniqueRecords = array_map(function ($record) {
return unserialize($record);
}, $uniqueRecords);

$categorycompanycount = count($uniqueRecords);
$counter['total'] += $categorycompanycount;

foreach ($uniqueRecords as $item_company) {
$counter['record']++;
flush();

if (($item_company['minage'] > $alive['years']) || ($item_company['maxage'] < $alive['years'])) continue;
$counter['display']++;
if ($counter['display'] > $resultsize) continue;

$isChecked = in_array($item_company['company_id'], $selectionList) ? 'checked' : '';
$isExisting = in_array($item_company['company_id'], $existingList) ? 'checked' : '';

if ($account->isadmin())
$setuplink = '<a href="/admin/brands?cid=' . $item_company['company_id'] . '" target="companysetup">
<i class="bi bi-gear text-secondary ms-2 pb-3 fs-5"></i></a>';
else $setuplink = '';

$apponlyicon = '';
if ($item_company['signup_url'] == $website['apponlytag'])  $apponlyicon = '<i class="bi bi-phone-fill text-danger apponly me-2"  data-bs-toggle="tooltip" data-bs-placement="top" title="You can only enroll by installing their app"></i>';

$randomBenefit = $benefits[array_rand($benefits)];
$benefitClass = str_replace(' ', '_', strtolower($randomBenefit));
switch ($randomBenefit) {
case 'On Enrollment':
$benefiticon = '<i class="bi bi-calendar-check ' . $benefitClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="You get a reward on enrollment"></i>';
break;
case 'All Month':
$benefiticon = '<i class="bi bi-calendar3 ' . $benefitClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="You get a reward during the full month of your birthday"></i>';
break;
case 'Week Of':
$benefiticon = '<i class="bi bi-calendar-week ' . $benefitClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="You get a reward on the week of your birthday"></i>';
break;
case 'On Birthday Only':
$benefiticon = '<i class="bi bi-calendar-event ' . $benefitClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Reward given only on your actual birthday"></i>';
break;
}

$query = "SELECT * FROM bg_company_rewards WHERE company_id= ? and `status`='active'";
$stmt = $database->prepare($query);
$stmt->execute([$item_company['company_id']]);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
$rowcnt = count($rewards);
$totalvalue = 0;

foreach ($rewardiconlist as $icon) {
$rewardicon[$icon] = '';
}

if ($rewards) {
foreach ($rewards as $reward) {
$counter['rewards']++;
$totalvalue = $totalvalue + $reward["cash_value"];
switch ($reward["category"]) {
case 'enrollment':
$rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-primary p-2 pe-3"><i class="bi bi-pen me-1 fs-10"></i><span>Enrollment</span></span>';
break;
case 'birthday':
$rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-warning p-2 pe-3"><i class="bi bi-cake-fill me-1 fs-10" ></i><span>Birthday</span></span>';
break;
case 'enrollment_anniversary':
$rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-success p-2 pe-3"><i class="bi bi-calendar-heart me-1 fs-10" ></i><span>Annual Member</span></span>';
break;
case 'wedding_anniversary':
$rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-info p-2 pe-3"><i class="bi bi-gem me-1 fs-10" ></i><span>Wedding</span></span>';
break;
case 'honor':
$rewardicon[$reward["category"]] = '<span class="badge rounded-pill bg-secondary p-2 pe-3"><i class="bi bi-award-fill me-1 fs-10"></i><span>Honor</span></span>';
break;
}
}
}

$randomRating = mt_rand(32, 49) / 10;


$companylink = $item_company['info_url'];
if ($item_company['signup_url'] == $website['apponlytag'])  $companylink = $item_company['company_url'];
$companylink='/brand-details?cid='.$qik->encodeId($item_company['company_id']);


switch ($rowcnt) {
case 1:
$valuetag = 'Est. Reward Value';
break;
default:
$valuetag = 'Est. Total Value';
break;
}


echo '


<!-- ============================================================================================================================= -->
<!-- ============================================================================================================================= -->
<!-- ================================================== START GRID ITEM ========================================================== -->
<article class="col-md-6 col-xxl-4 mb-3"  id="businesscard-' . $item_company['company_id'] . '" 
data-rewards="birthday" data-categories="' . $categorylower . ' drink" data-rating="' . $randomRating . '" 
data-types="' . ($item_company['signup_url'] == $website['apponlytag'] ? 'true' : 'false') . '" 
data-companyname="' . htmlspecialchars($item_company['company_name']) . '" 
data-more="' . (
(($item_company['region_type'] ?? 'national') === ($current_user_data['profile_city'] ?? '') ? 'localbrands ' : '') .
((isset($bg_systemdata_states[$current_user_data['profile_state']]) && in_array($bg_systemdata_states[$current_user_data['profile_state']], explode(',', $item_company['region_type'] ?? 'national'))) ? 'statewidebrands ' : '') .
((($item_company['region_type'] ?? 'national') === 'national') ? 'nationalbrands' : '')
) . '">

<div class="card h-100 overflow-hidden">
<div class="card-body p-0 d-flex flex-column justify-content-between">
<div>
<div class="hoverbox text-center"><a class="text-decoration-none" href="' . $companylink . '" target="_signup">
<img class="w-100 h-100 object-fit-cover" src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '"  alt="" /></a>
<div class="hoverbox-content flex-center pe-none bg-holder overlay overlay-2"><img class="z-1"  src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '" width="60" alt="" /></div>
</div>
<div class="p-3">
<h5 class="mb-2"><a href="' . $companylink . '" target="_signup">' . trim($item_company['company_name']) . '</a>' . $setuplink . '</h5>
<h5 class="fs-9">

<!-- ====== ICONS --------------------------  -->
<div class="d-flex gap-2 flex-wrap mb-3">';      

foreach ($rewardiconlist as $icon) {
    if (!empty($rewardicon[$icon])) {
        echo $rewardicon[$icon] . '
';
    }
}

echo '</div>
<!-- ====== END ICONS --------------------------  -->

</h5>
</div>
</div>
<div class="row g-0 mb-3 align-items-end">
<div class="col ps-3">
<p class="fw-bold">' . $item_company['spinner_description'] . '</p>

<p class="mb-0 fs-10 text-800">' . $display->starrating($randomRating, 'tooltip|' . rand(10, 9999)) . '
' . $apponlyicon . '  ' . $benefiticon . ' </p>
</div>
<div class="col-auto pe-3">';

// HAVE button (existing account button)
echo '<button type="button" class="btn btn-md btn-falcon-default hover-danger fs-10 text-600 have-button" 
data-company-id="' . $item_company['company_id'] . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" 
title="Click if you already have an existing account with <br>' . htmlspecialchars($item_company['company_name']) . '">
<span class="bi bi-heart">'.$have_label_default.'</span>
</button>
';

// ENROLL button (add-to-gallery button)
if ($enablesignupbutton) {
    $signupbutton = '<button type="button" class="btn btn-md btn-primary fs-10 enroll-button" data-company-id="' . $item_company['company_id'] . '">';
} else {
    $signupbutton = '<button type="button" class="btn btn-md btn-secondary fs-10 enroll-button" disabled data-company-id="' . $item_company['company_id'] . '">';
}

echo $signupbutton . '<span class="bi bi-plus-circle"></span><span class="d-none d-lg-inline enroll-label">'.$enroll_label_default.'</span></button>';

echo '</div>
</div>
</div>
</div>

<input class="existingCheckbox quiz_checkbox d-none" type="checkbox" name="existinglist[]" value="' . $item_company['company_id'] . '" ' . $isExisting . '>   
<input class="quiz_checkbox d-none" type="checkbox" name="selectionlist[]" id="' . $item_company['company_id'] . '" value="' . $item_company['company_id'] . '" ' . $isChecked . '  />
</article>
<!-- ======================================================== END ================================================================ -->
';





}
## END BUSINESS ARTICLE LOOP


}
## END ALL BUSINESSES LOOP