<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');




#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
$adminenabledpage=true;

  // Define the categories in the order they should appear
  $categories = [
    'About Birthday.Gold',
    'Birthday.Gold Accounts',
    'Explore and Claim Your Rewards'
  ];



  
#-------------------------------------------------------------------------------
# PROCESS POST ATTEMPT
#-------------------------------------------------------------------------------
  // Handle form submission for updating FAQ
  if ($account->isadmin() && $app->formposted() && isset($_POST['faq_id'])) {
    $faq_id = $_POST['faq_id'];
    $display_name = $_POST['display_name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    

    $sql = "UPDATE bg_content SET display_name = :display_name, description = :description, `grouping` = :category, modify_dt = now() WHERE id = :id";    $stmt = $database->prepare($sql);
    $stmt->bindParam(':display_name', $display_name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':id', $faq_id);
    $stmt->execute();
  }




#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
  // Get all FAQs grouped by category
  $sql = "SELECT * FROM bg_content WHERE category = 'faq' ORDER BY `grouping`, `rank`";
  $stmt = $database->prepare($sql);
  $stmt->execute();
  $all_faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Organize FAQs by category
  $faqs_by_category = [];
  foreach ($all_faqs as $faq) {
    $category = $faq['grouping'] ?: 'Uncategorized';
    if (!isset($faqs_by_category[$category])) {
      $faqs_by_category[$category] = [];
    }
    $faqs_by_category[$category][] = $faq;
  }


  include($dir['core_components'] . '/bg_pagestart.inc');
  include($dir['core_components'] . '/bg_header.inc');


  echo '
  
  <div class="container content my-5 pt-3">
  
    <div class="mb-4">
  
      <!--/.bg-holder-->
  
      <div class="">
        <div class="row">
          <div class="col-lg-8">
            <h2>Frequently Asked Questions</h2>
            <p class="mb-0">Below you\'ll find answers to the questions we get asked the most at birthday.gold</p>
          </div>
        </div>
      </div>
    </div>
  
';




  // Display each category with its FAQs
  foreach ($categories as $category) {
    if (isset($faqs_by_category[$category]) && !empty($faqs_by_category[$category])) {
      echo '<h3 class="mt-5 mb-4 fw-bold">' . htmlspecialchars($category) . '</h3>';
      echo '<div class="accordion border rounded overflow-hidden mb-5" id="accordionFaq' . preg_replace('/[^a-zA-Z0-9]/', '', $category) . '">';
      
      $faqs = $faqs_by_category[$category];
      foreach ($faqs as $index => $faq) {
        $headingId = "faqHeading" . $faq['id'];
        $collapseId = "faqCollapse" . $faq['id'];
        $question = htmlspecialchars($app->tagreplace($faq['display_name']), ENT_QUOTES, 'UTF-8');
        $answer = $app->tagreplace($faq['description']);
        $faq_id = $faq['id'];

        echo '<div class="card shadow-none border-0">';
        echo '<div class="accordion-item rounded-bottom-0">';
        echo '<div class="card-header p-0" id="' . $headingId . '">';
        echo '<button class="accordion-button btn btn-link text-decoration-none d-block w-100 py-4 px-3 collapsed border-0 text-start rounded-0 shadow-none" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" aria-expanded="false" aria-controls="' . $collapseId . '">';
        echo '<span class="bi bi-caret-right-fill accordion-icon me-3"></span><span class="fw-medium font-sans-serif text-900 fw-bold">' . $question . '</span>';
        echo '</button>';
        echo '</div>'; // End of card-header
        echo '<div class="accordion-collapse collapse" id="' . $collapseId . '" aria-labelledby="' . $headingId . '" data-parent="#accordionFaq' . preg_replace('/[^a-zA-Z0-9]/', '', $category) . '">';
        echo '<div class="accordion-body p-0 pb-5">';
        echo '<div class="card-body pt-2">';
        echo '<div class="ps-3 mb-0">' . $answer . '</div>';

        // If the user is an admin, show the edit gear icon
        if ($account->isadmin()) {
          echo '<div class="d-flex justify-content-end mt-3">';
          echo '<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editFaqModal' . $faq_id . '"><span class="bi bi-gear-fill"></span> Edit</button>';
          echo '</div>';
        }

        echo '</div></div></div></div></div>';

        // Modal for editing the FAQ
        if ($account->isadmin()) {
          echo '
          <div class="modal modal-lg fade" id="editFaqModal' . $faq_id . '" tabindex="-1" aria-labelledby="editFaqModalLabel' . $faq_id . '" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="editFaqModalLabel' . $faq_id . '">Edit FAQ</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="min-height: 400px;">
                  <form method="POST" action="/faq">
                    ' . $display->inputcsrf_token() . '
                    <input type="hidden" name="faq_id" value="' . $faq_id . '">
                    
                    <!-- Hidden fields to store previous values for tracking -->
                    <input type="hidden" name="prev_display_name" value="' . htmlspecialchars($faq['display_name'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="prev_description" value="' . htmlspecialchars($faq['description'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="prev_category" value="' . htmlspecialchars($faq['grouping'], ENT_QUOTES, 'UTF-8') . '">
                    
                    <div class="mb-3">
                      <label for="display_name" class="form-label">Question</label>
                      <input type="text" class="form-control" id="display_name" name="display_name" value="' . htmlspecialchars($faq['display_name'], ENT_QUOTES, 'UTF-8') . '">
                    </div>
                        <div class="mb-3">
                      <label for="category" class="form-label">Category</label>
                      <select class="form-select" id="category" name="category">
                        <option value="">Uncategorized</option>';
          
          // Add options for each category
          foreach ($categories as $cat_option) {
            $selected = ($cat_option == $faq['grouping']) ? 'selected' : '';
            echo '<option value="' . htmlspecialchars($cat_option, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($cat_option, ENT_QUOTES, 'UTF-8') . '</option>';
          }
          
          echo '</select>
                    </div>
                    <div class="mb-3">
                      <label for="description" class="form-label">Answer</label>
                      <textarea class="form-control" id="description" name="description" rows="6" style="min-height: 150px;">' . htmlspecialchars($faq['description'], ENT_QUOTES, 'UTF-8') . '</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                  </form>
                </div>
              </div>
            </div>
          </div>';
        }
      }
      
      echo '</div>'; // End of accordion
    }
  }

  // Display any uncategorized FAQs at the end
  if (isset($faqs_by_category['Uncategorized']) && !empty($faqs_by_category['Uncategorized'])) {
    echo '<h1 class="mt-5 mb-4">Other Questions</h1>';
    echo '<div class="accordion border rounded overflow-hidden mb-5" id="accordionFaqUncategorized">';
    
    // Render uncategorized FAQs
    // (Same rendering code as above for categorized FAQs)
    
    echo '</div>';
  }
  ?>

</div>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();