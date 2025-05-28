<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$loop_companies = $app->getFeaturedCompanies('active');


echo '
<div class="container main-content mt-5">
<input type="text" id="searchBar" class="form-control mb-4" placeholder="Search company...">
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
<?PHP

include($dir['core_components'] . '/bg_footer.inc');
      $app->outputpage();
