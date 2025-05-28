<?PHP

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
$addcredjs = false;


$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$current_user_data = $account->getuserdata($current_user_data['user_id'], 'user_id');
$transferpagedata = [];



#-------------------------------------------------------------------------------
# DISPLAY THE PAGE
#-------------------------------------------------------------------------------
$pageoutput = '';

$transferpagedata = $system->startpostpage($transferpagedata);
$pageoutput .= '' . $display->formaterrormessage($transferpagedata['message']);


$till = $app->getTimeTilBirthday($current_user_data['birthdate']);
$astrosigndetails = $app->getastrosign($current_user_data['birthdate'], 'all');
$birthdates = $account->getBirthdates($current_user_data['birthdate']);
#$astroicon=$app->getZodiacInfo($astrosign);
$planname = $app->plandetail();
#$daysalive=$app->calculateDaysAlive($current_user_data['birthdate']);
$alive = $app->calculateage($current_user_data['birthdate']);
$avatar = '/public/images/defaultavatar.png';
$avatarbuttontag = 'Upload';
if (!empty($current_user_data['avatar'])) {
  $avatar = $current_user_data['avatar'];
  $avatarbuttontag = 'Change';
}



if ($current_user_data['account_type'] == 'minor') $minorbg = 'bg-info-subtle';
else $minorbg = '';

?>




<?PHP


$born_birthdate = new DateTime($current_user_data['birthdate']);;

$lastlogindetails = $account->getLastLogin($current_user_data['user_id']);





#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------

include($dir['core_components'] . '/bg_user_profileheader.inc');

?>

  <div class="container my-5 main-content" >
<?PHP
echo '<div class="mb-3">
<h2 class="text-primary">Your Fun Facts</h2>
</div>
';
?>

    <div class="row g-0 mt-4 mb-5 mx-0 px-0 ">
      <div class="col-lg-12 mx-0 px-0">
        <div class="card mb-3 mx-0 px-0 bg-light">
          <div class="card-header bg-info-subtle d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Fun Facts</h5>

          </div>


            <?php
            // Define classes for each card based on its position
            $visibilityClasses = [
              0 => 'd-block',              // Always show the first card
              1 => 'd-none d-md-block',    // Show the second card on medium screens and up
              2 => 'd-none d-lg-block'     // Show the third card on large screens and up
            ];
echo '     <div class="row p-3 pb-0 funfacts mx-0 px-0">';
            $index = 0;
            // Loop through the selected files and include them
            foreach ($bg_funfacts as  $file) {    // listed in site_arrays

              if ($file == 'random_facts.inc') {
                echo '</div>';
                $choices_array = ['topsong', 'historic_event', 'slang_words', 'inventions', 'generations'];
                foreach ($choices_array as $choice) {
                  $choices = [$choice];
                  echo '<div class="col-lg-12 mb-4" style="padding-left:12px; padding-right:12px">';
                  include($_SERVER['DOCUMENT_ROOT'] .  '/myaccount/module_funfacts/' . $file);
                  echo '</div>';
                }
              } else {

            #    echo '<div class="col-sm-6 col-lg-4 mb-3 ' . $visibilityClasses[$index] . '">';
                echo '<div class="col-sm-6 col-lg-4 mb-3 ">';
                $index++;
                if ($index > 2)  $index = 2;
                include($_SERVER['DOCUMENT_ROOT'] .  '/myaccount/module_funfacts/' . $file);
                echo $funfactrecord;
                 echo '</div>';
              }
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>


<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();