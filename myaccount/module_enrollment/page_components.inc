<?PHP


$title_content = '
<div class="row">
<div class="col-sm-12">
<div class="text-center">
<div id="commentary">
' . $plan_pagetitle . '
' . $plan_pagetip . '
</div>
</div>
</div>
</div>';

$search_content = '
<!-- text filter control -->
<div class="d-md-none col-12 mb-3">
<input type="text" value="" class="form-control mr-3" placeholder="Search by Name" data-grid-control="text-filter" data-path=".product-name" />
</div>
<div class="d-none d-md-block col-8 mb-3">
<input type="text" value="" class="form-control mr-3" placeholder="Search by Business Name" data-grid-control="text-filter" data-path=".product-name" />
</div>
';

$sort_content = '
<!-- sort control -->
<div class="col-4  d-none d-md-block mb-3">
<select data-grid-control="sort" class="form-control">
<option data-path=".company-name" data-direction="asc" data-type="text" selected>Sort A-Z</option>
<option data-path=".company-name" data-direction="desc" data-type="text">Sort Z-A</option>
<option data-path=".popular-count" data-direction="desc" data-type="number">Sort most popular</option>
<option data-path=".popular-count" data-direction="asc" data-type="number">Sort least popular</option>
<option data-path=".star-rating" data-direction="desc" data-type="number">Sort highest ratings</option>
<option data-path=".star-rating" data-direction="asc" data-type="number">Sort lowest ratings</option>
</select>
</div>
';

$pagination_content = '
<!-- pagination start -->
<div class="col-12 d-none d-md-flex align-items-start align-items-md-center justify-content-between flex-wrap text-dark">
<!-- pages number label -->
<div data-grid-control="label" data-type="pagination-pages" class="pagination-label text-dark mb-3"></div>
<!-- pagination control -->
<nav aria-label="pagination" data-grid-control="pagination" class="text-dark mb-3"></nav>
<!-- number of items per page -->
<select class="form-control page-size-control mb-3" data-grid-control="page-size">
<option value="24">24 items per page</option>
<option value="48">48 items per page</option>
<option value="96">96 items per page</option>
<option value="Infinity">all</option>
</select>
<!-- reset all controls -->
<button type="button" data-grid-control="reset-button" class="btn btn-secondary btn-sm d-none d-lg-block mb-3 mx-3 px-3">Reset</button>
</div>
<!-- pagination end -->
';





# BUILD THE PROGRESS BAR
#-------------------------------------------------------------------------------
if ($initialcount >= $planlimit) {
    $barvalue = 100;
    $enablesignupbutton = false;
  } else {
    $barvalue = (100 - ceil($initialcount / $planlimit));
    $enablesignupbutton = true;
  }
  $barvaluestring = $barvalue . '%';
  if ($barvalue < 10) $barvaluestring = '';
  $progressbar_content = '
        <div class="progress mb-3" style="height:15px" role="progressbar" aria-valuenow="' . $initialcount . '" aria-valuemin="0" aria-valuemax="' . $planlimit . '">
        <div class="progress-bar" style="width: ' . $barvalue . '%">' . $barvaluestring . '</div>
      </div>
      <div class="alert alert-danger" role="alert" id="planlimitalert"  style="display: none;">
      You cannot select any more items. You are at your plan limit.
      </div>
        ';
  $titleplantag = 'Plan: <small class="fw-normal">( <span id="count-display2">' . $initialcount . '</span>/' . $planlimit . ' limit)';
  $progressbar_contentx = '
       <div class="row ' . $plan_objectdisplaytag . '">
       <div class="quiz_backBtn_progressBar mt-4 mb-3">
       <div class="row">
       <div class="col-9 mb-3">
       <h3 class="d-none d-md-block">Plan Limit: <small class="fw-normal">( <span id="count-display">' . $initialcount . '</span> of ' . $planlimit . ' enrollments this year )</small></h3>
       <h5 class="d-md-none">Plan: <small class="fw-normal">( <span id="count-display2">' . $initialcount . '</span>/' . $planlimit . ' limit)</small></h5>
       </div>
       <div class="col-3 mb-2 pb-0 d-flex justify-content-end">
       <input type="submit" value="Save List"  id="submit-button"  name="submit_top_desktop" class="btn btn-success py-2 px-5 d-none d-lg-block">
       <input type="submit" value="Save List"  id="submit-button" name="submit_top_mobile" class="btn btn-success py-2 px-3 d-lg-none">
       </div>
       </div>
       <div class="row  mb-0 pb-0">
       <div class=" mb-0 pb-0">
       <div class="progress mb-3" style="height:15px" role="progressbar" aria-valuenow="' . $initialcount . '" aria-valuemin="0" aria-valuemax="' . $planlimit . '">
       <div class="progress-bar" style="width: ' . $barvalue . '%">' . $barvaluestring . '</div>
     </div>
       </div>
       </div>
       </div>
       <div class="alert alert-danger" role="alert" id="planlimitalert"  style="display: none;">
       You cannot select any more items. You are at your plan limit.
       </div>
       </div>
       ';
  

# BUILD THE SEARCH BAR
#-------------------------------------------------------------------------------
$searchbar_content = '
  <form class="position-relative" data-bs-toggle="search" data-bs-display="static" method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="searchbusiness" id="searchbusinessform" >
  ' . $display->inputcsrf_token() . '
    <input class="form-control search-input fuzzy-search" type="text" name="search" id="search placeholder="Search..." aria-label="Search" />
    <span class="fas fa-search search-box-icon"></span>
  </form>
  <div class="btn-close-falcon-container position-absolute end-0 top-50 translate-middle shadow-none" data-bs-dismiss="search">
    <button class="btn btn-link btn-close-falcon p-0" aria-label="Close"></button>
  </div>
  </div>
';