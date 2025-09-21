<?php



$data = array(
    'Rewards' => $rewardCategoriesData['Category'],
    'Categories' => array(
      array('label' => 'Retail', 'filter' => 'retail', 'state' => true),
      array('label' => 'Food', 'filter' => 'food', 'state' => true),
      array('label' => 'Drink', 'filter' => 'drink', 'state' => false),
      array('label' => 'Points', 'filter' => 'points', 'state' => false),
      array('label' => 'Discount', 'filter' => 'discount', 'state' => false),
      array('label' => 'Miscellaneous', 'filter' => 'miscellaneous', 'state' => false)
    ),
    'Rating' => array(
      array('label' => '4.5 & Up', 'filter' => '4.5', 'state' => false),
      array('label' => '4.0 & Up', 'filter' => '4.0', 'state' => false),
      array('label' => '3.5 & Up', 'filter' => '3.5', 'state' => false),
      array('label' => '3.0 & Up', 'filter' => '3.0', 'state' => false)
    ),
    'Types' => array(
      array('label' => 'App Only', 'filter' => 'apponly', 'state' => false),
      array('label' => 'Popular', 'filter' => 'popular', 'state' => false),
      array('label' => 'Sponsored', 'filter' => 'sponsored', 'state' => false)
    ),
    'More' => array(
      array('label' => 'Show Enrolled', 'filter' => 'show', 'state' => false),
      array('label' => 'Local Brands', 'filter' => 'LOC-local', 'state' => false),
      array('label' => 'Statewide Brands', 'filter' => 'LOC-state', 'state' => false),
      array('label' => 'National Brands', 'filter' => 'LOC-national', 'state' => false)
    )
  );
  



  
# FILTER PANEL COLUMN
#-------------------------------------------------------------------------------
$filterpanel_content= '
<!-- FILTER PANEL COLUMN -->
<div class="col-xxl-2 col-xl-3">
<aside class="scrollbar-overlay font-sans-serif p-4 p-xl-3 ps-xl-0 offcanvas offcanvas-start offcanvas-filter-sidebar" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
<div class="d-flex flex-between-center pb-3 border-bottom">
<div class="d-flex gap-2 flex-xl-grow-1 align-items-center justify-content-xl-between">
<h5 class="mb-0 text-700 d-flex align-items-center" id="filterOffcanvasLabel"><span class="bi bi-funnel fs-10 me-2"></span><span>Filter</span></h5>
<button id="resetFiltersButton" class="btn btn-sm btn-outline-secondary">Clear</button>

</div>
<button class="btn-close text-reset d-xl-none shadow-none" type="button" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>
<ul class="list-unstyled">';

$search = array(' ', '&', '.', '’');
foreach ($data as $section => $options) {
  $sectiontag = strtolower(str_replace($search, '', $section));
  $hasTrue = in_array(true, array_column($options, 'state'));
  $filterpanel_content .= '  <li><a class="nav-link collapse-indicator-plus fs-11 fw-medium text-600 py-3" data-bs-toggle="collapse" href="#' . $sectiontag . '-collapse" aria-controls="' . $sectiontag . '-collapse" aria-expanded="false">' . $section . '</a>';
  $filterpanel_content .= "<div class='collapse" . ($hasTrue ? " show" : "") . "' id='$sectiontag-collapse'>";
  $filterpanel_content .= "<ul class='list-unstyled'>";
  foreach ($options as $option) {
    $valuetag = '';
    $label = $option['label'];
    $filter = $option['filter'];
    $state = $option['state'];
    $labeltag = strtolower(str_replace($search, '', $label));
    $checkedAttribute = $state ? "checked='checked'" : "";
    if ($sectiontag == 'rating')  $valuetag = 'value="' . $filter . '"';
    $filterpanel_content .= '
<li>
<div class="form-check d-flex ps-0">
<label class="form-check-label fs-10 flex-1 text-truncate" for="filter-' . $sectiontag . '-' . $labeltag . '">
<span class="bi bi-file-text fs-10 me-3"></span>' . $label . '
</label>
<input class="form-check-input filter-checkbox" type="checkbox" ' . $checkedAttribute . ' name="' . $sectiontag . '-' . $labeltag . '" id="filter-' . $sectiontag . '-' . $labeltag . '" data-section="' . $sectiontag . '" data-label="' . $labeltag . '" ' . $valuetag . ' />
</div>
</li>
';
  }
  $filterpanel_content .= '</ul>
</div>
</li>';
}
$filterpanel_content .= '
</ul>
</aside>
</div>
';
