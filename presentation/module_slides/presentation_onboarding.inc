<?PHP
include_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

##  ========================================================================================================================================================
##  must conform to this format
##  You can provide:  $sectionClass, $sectionTag, $content, $speechScript, $section_suffix
##  ========================================================================================================================================================
/*
<!-- Slide #' .   $counter . ' - ID:' . $slide['id'] . ' ====================================================================== -->' . $section_prefix . '
<section class="' . $sectionClass . '"  id="' . $sectionTag . '"
data-speech="' . str_replace(array("\r", "\n"), '', $speechScript) . '">
' . $content . '
</section>
' . $section_suffix;
*/

#$current_user_data = $session->get('curent_user_data');
if (empty($current_user_data)) {
// Redirect to login page - authentication required
header('Location: /login');
 exit;
}


// grab other onboarding data
$sql="SELECT name, description FROM bg_user_attributes WHERE user_id = :userid and type = 'employee_onboarding' AND status = 'active'";
$stmt = $database->prepare($sql);
    $params = [
        ':userid' => $current_user_data['user_id']
    ];
// Execute the query
$stmt->execute($params );
// Initialize the array
$onboardingdata = [];

// Fetch the results and populate the array
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $onboardingdata[$row['name']] = $row['description'];
}

// add $onboardingdata to the session form_data (dont' overwrite existing data)
$session->set('form_data', array_merge($session->get('form_data'), $onboardingdata));
#breakpoint($session->get('form_data'));






// SLIDE 2 for Employee Onboarding
if ((!empty($_REQUEST['code']) && $_REQUEST['code']=='123')) {

    $speechScript='testing slide 2 active';

    
    $content = '
    <script>
    // Ensure DOM is fully loaded before adding event listeners
    document.addEventListener("DOMContentLoaded", function() {
      console.log("DOM fully loaded and parsed");

      // Listen for WebSlides slide change event
      document.addEventListener("ws:slide-change", function() {
        console.log("Slide change event triggered");

        const currentSection = document.querySelector(".current");
        console.log("Current Section:", currentSection);

        if (currentSection) {
          const sectionId = currentSection.getAttribute("id");
          console.log("Current Section ID:", sectionId);

          // Check if the current section is section-2
          if (sectionId === "section-2") {
            console.log("Slide #2 is active, redirecting...");
            window.location.href = "/presentation/employeeonboarding#slide=3";
          } else {
            console.log("Not slide #2, no action taken");
          }
        } else {
          console.log("No current section found");
        }
      });
    });
    </script>';


}