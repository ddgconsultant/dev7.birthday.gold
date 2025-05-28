<?PHP

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#$loop_companies = $app->getFeaturedCompanies('active');


$bodycontentclass = '';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


echo '
<div class="container main-content mt-5">
<input type="text" id="searchBar" class="form-control mb-4" placeholder="Search System...">
<div class="row" id="logoGallery">
';

?>



<div class="row">


    <?PHP
    $sql = 'SELECT `link`, `icon` , `name`, `description` FROM bg_systems where `status`="A"';
    $stmt = $database->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($results as $item) {

        echo '<div class="col-4 col-md-3 col-lg-2 logo-item mb-3">
<div class="card h-100 text-center">
<div class="d-flex justify-content-center align-items-center pt-2" style="height: 150px;">
<a href="' . $item['link'] . '" class="logo-item">
<img class="img-fluid p-2" src="/public/images/system_icons/' . (str_replace('/store/icons/', '', $item['icon'])) . '" alt="" style="max-height: 75%; max-width: 75%;">
</a>
</div>
<div class="card-body">
<h5 class="card-title">' . $item['name'] . '</h5>
<div class="tagline text-muted ng-binding">' . $item['description'] . '</div>
</div>
</div>
</div>';
    }

    ?>

</div>
</div>



<!--             -------------------------------------------------------------------------------------------------- -->

</div>
</div>

<script>
    $(document).ready(function() {
        $('#searchBar').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#logoGallery .logo-item').filter(function() {
                // Changed from .title to .card-title to target the correct elements
                $(this).toggle($(this).find('.card-title').text().toLowerCase().includes(value));
            });
        });
    });
</script>



<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
