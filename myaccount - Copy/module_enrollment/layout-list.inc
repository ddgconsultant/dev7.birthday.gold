<?PHP

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
  echo '



<!-- ============================================================================================================================= -->
<!-- ============================================================================================================================= -->
<!-- ================================================== START LIST ITEM ========================================================== -->
<article class="card mb-3 overflow-hidden" 
 id="businesscard-' . $item_company['company_id'] . '" 
data-rewards="birthday" data-categories="' . $categorylower . ' drink" data-rating="' . $randomRating . '" 
data-types="' . ($item_company['signup_url'] == $website['apponlytag'] ? 'true' : 'false') . '" 
data-companyname="' . htmlspecialchars($item_company['company_name']) . ' 
data-more="' . (
    (($item_company['region_type'] ?? 'national') === ($current_user_data['profile_city'] ?? '') ? 'localbrands ' : '') .
    ((isset($bg_systemdata_states[$current_user_data['profile_state']]) && in_array($bg_systemdata_states[$current_user_data['profile_state']], explode(',', $item_company['region_type'] ?? 'national'))) ? 'statewidebrands ' : '') .
    ((($item_company['region_type'] ?? 'national') === 'national') ? 'nationalbrands' : '')
) . '">


    <div class="card-body p-0">
<div class="row g-0">
  <div class="col-md-4 col-lg-3">
    <div class="hoverbox h-md-100">
    <!-- <a class="text-decoration-none" href="/public/assets/video/beach.mp4" data-gallery="attachment-bg"> -->
    <img class="h-100 w-100 object-fit-cover "  loading="lazy"  src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '" alt="" />
   <!--  </a>  -->
    <div class="hoverbox-content flex-center pe-none bg-holder overlay overlay-2">
      <img class="z-1" src="/public/assets/img/icons/play.svg" width="60" alt="" /></div>
    </div>
  </div>
  <div class="col-md-8 col-lg-9 p-x1">
    <div class="row g-0 h-100">
      <div class="col-lg-8 col-xxl-9 d-flex flex-column pe-x1">


<!-- ====== ICONS --------------------------  -->
<div class="d-flex gap-2 flex-wrap mb-3">      
';

foreach ($rewardiconlist as $icon) {
  if (!empty($rewardicon[$icon])) {
  echo $rewardicon[$icon] . '
';
  }
}

echo '</div>
<!-- ====== END ICONS --------------------------  -->

    ';

  switch ($rowcnt) {
    case 1:
      $valuetag = 'Est. Reward Value';
      break;
    default:
      $valuetag = 'Est. Total Value';
      break;
  }
  
    $companylink = $item_company['info_url'];    
    $enablesignupbutton=true;
    if ($item_company['signup_url'] == $website['apponlytag'])  $companylink = $item_company['company_url'];
    $companylink='/brand-details?cid='.$qik->encodeId($item_company['company_id']);
    echo '<h3><a href="' . $companylink . '" target="_signup">' . trim($item_company['company_name']) . '</a>'.$setuplink.'</h3>';

    echo '
        <p class="fw-bold">' . $item_company['spinner_description'] . '</p>
        <p class="fs-10 mt-2 d-none d-lg-block">' . $item_company['description'] . '</p>
        <div class="flex-1 d-flex align-items-end fw-semi-bold fs-10"><span class="me-1 text-900">' . $randomRating . '</span>
          <div>
          ' . $display->starrating($randomRating) . '
          </div>
          <span class="ms-2 me-2 text-secondary">(' . rand(10, 9999) . ' reviews)</span> 
          ' . $apponlyicon . '  ' . $benefiticon . ' 
        </div>
      </div>
      <div class="col-lg-4 col-xxl-3 mt-4 mt-lg-0">
        <div class="h-100 rounded border-lg border-1 d-flex flex-lg-column justify-content-between p-lg-3  bg-light-subtle ">
          <div class="mb-lg-4 mt-auto mt-lg-0">
            <p class="mb-1 fs-10 text-success d-flex align-items-end">' . $valuetag . ': $' . number_format($totalvalue, 2) . '              
</p>
            <p class="mb-0 fs-10 text-800">' . number_format(rand(2000, 99999), 0) . ' Users Enrolled</p>
          </div>
          <div class="mt-3 d-flex flex-lg-column gap-2">
     ';
     


// HAVE button (existing account button)
echo '<button type="button" class="btn btn-md btn-falcon-default hover-danger fs-10 text-600 have-button" 
data-company-id="' . $item_company['company_id'] . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" 
title="Click if you already have an existing account with <br>' . htmlspecialchars($item_company['company_name']) . '">
<span class="bi bi-heart"></span>
<span class="text-600 d-none d-lg-inline have-label">'.$have_label_default .'</span>
</button>
';

// ENROLL button (add-to-gallery button)
if ($enablesignupbutton) {
  $signupbutton = '<button type="button" class="btn btn-md btn-primary fs-10 enroll-button" data-company-id="' . $item_company['company_id'] . '">';
} else {
  $signupbutton = '<button type="button" class="btn btn-md btn-secondary fs-10 enroll-button" disabled data-company-id="' . $item_company['company_id'] . '">';
}

echo $signupbutton . '<span class="bi bi-plus-circle"></span><span class="d-none d-lg-inline enroll-label">'.$enroll_label_default.'</span></button>';




echo '       
<input class="existingCheckbox quiz_checkbox d-none" type="checkbox" name="existinglist[]" value="' . $item_company['company_id'] . '" ' . $isExisting . '>   
  <input class="quiz_checkbox d-none" type="checkbox" name="selectionlist[]" id="' . $item_company['company_id'] . '" value="' . $item_company['company_id'] . '" ' . $isChecked . '  />

</div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</article>
<!-- ======================================================== END ================================================================ -->
';

    switch ($userplan) {
      case 'free':
        $addbutton = '  <a href="' . $item_company['info_url'] . '" target="brandwindow" class="btn btn-primary">Visit Website <i class="bi bi-box-arrow-up-right"></i></a>';
        $existingbtn = '';
        break;
      default:
        $addbutton = '  <a href="#" class="btn btn-primary add-to-gallery" data-company-id="' . $item_company['company_id'] . '"><i class="bi bi-plus"></i> Create An Account</a>';
        $existingbtn = '
<div>
<span class="existingButton btn-sm btn-secondary" data-company-id="' . $item_company['company_id'] . '">I Have An Account</span>
<input class="existingCheckbox quiz_checkbox d-none" type="checkbox" name="existinglist[]" value="' . $item_company['company_id'] . '" ' . $isExisting . '>      
</div>';
        break;
    }
    $content .= ' <input class="quiz_checkbox d-none" type="checkbox" name="selectionlist[]" id="' . $item_company['company_id'] . '" value="' . $item_company['company_id'] . '" ' . $isChecked . '  />
</div>
</div>
';
   $content .= '
<div class="card-footer tags d-flex justify-content-between">
<div>
' . $apponlyicon . '
' . $benefiticon . ' 
<a href="#" class="text-muted ' . $categorylower . ' ms-2" data-grid-control="button-filter" data-id="' . $categorylower . '" data-path=".' . $categorylower . '">' . $category . '</a>
<span class="card-text popular-count me-2">' . $item_company['usage_count'] . '</span>
<span class="card-text star-rating"><i class="bi bi-star text-primary"></i> 5.0</span>
</div>
<div>
<strong class="px-2">Enrollment:</strong>
<div class="btn-group" role="group" aria-label="Basic radio toggle button group" data-company-id="' . $item_company['company_id'] . '">
<input type="radio" class="btn-check" name="btnradio' . $item_company['company_id'] . '" id="btnradio1_' . $item_company['company_id'] . '" autocomplete="off" checked>
<label class="btn btn-outline-secondary" for="btnradio1_' . $item_company['company_id'] . '">None</label>
<input type="radio" class="btn-check" name="btnradio' . $item_company['company_id'] . '" id="btnradio2_' . $item_company['company_id'] . '" autocomplete="off">
<label class="btn btn-outline-warning" for="btnradio2_' . $item_company['company_id'] . '">Existing</label>
<input type="radio" class="btn-check" name="btnradio' . $item_company['company_id'] . '" id="btnradio3_' . $item_company['company_id'] . '" autocomplete="off">
<label class="btn btn-outline-success" for="btnradio3_' . $item_company['company_id'] . '">Add</label>
</div>
</div>
</div>
</div>
</li>
';

  }
## END BUSINESS ARTICLE LOOP


}
## END ALL BUSINESSES LOOP
