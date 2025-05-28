<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$showmore=false;
$listlimit=64;
if (isset($_GET['more'])) {
    $showmore=true;
$listlimit=128;
}
$enablesearch=false;

$loop_companies = $app->getFeaturedCompanies($listlimit, '!!alphabetical!!');



#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
echo '
<div class="container main-content">
    <div class="container">
        <div class="text-center mx-auto mb-5">
            <h3 class="text-primary text-uppercase mb-2">Discover Over '.$website['numberofbiz'].'+ '.ucfirst($website['biznames']).'</h3>
            <h1 class="display-4 mb-4">Take a Look at Some of Our Favorites!</h1>
    ';

    // Conditionally display signup button only if user is not logged in
    if (!$account->isactive()) {
        echo '<a href="/signup" class="btn btn-gold btn-lg">Sign Up to See Them All!</a>';
    }

echo '
        </div>
';


if ($enablesearch)
{        echo '
        <div class="row">
            <input type="text" id="searchBar" class="form-control mb-4" placeholder="Search '.ucfirst($website['biznames']).'...">
        </div>
        ';
}
    echo     '
        <div class="row" id="logoGallery">
';


foreach ($loop_companies as $item_company) {
    echo '<div class="col-6 col-md-4 col-lg-3 logo-item mb-3">
<div class="card h-100 ">
<img class="img-fluid" src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '" alt="">
<div class="card-body">
<h5 class="card-title">' . $item_company['company_name'] . '</h5>
</div>
</div>
</div>';
}
?>


</div>
</div>

</div>
</div>
<?php
$output= $display->backtotop();
$additionalstyles.=$output['style'];
echo $output['content'];

if (!$showmore) 
echo '       <div class="container">
        <div class="text-center mx-auto mb-5">
           <a href="'.$_SERVER['PHP_SELF'].'?more" class="btn btn-primary btn-lg">Load More!</a>
           </div>
</div>
  ';
if ($enablesearch)
{        echo "

<script>
    $(document).ready(function() {
        $('#searchBar').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#logoGallery .logo-item').filter(function() {
                $(this).toggle($(this).find('.card-title').text().toLowerCase().includes(value));
            });
        });
    });
</script>
";
}

include($dir['core_components'] . '/bg_footer.inc');
      $app->outputpage();
