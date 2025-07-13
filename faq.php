<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');




#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------
// Only show admin header styling if an admin is logged in
if ($account->isadmin()) {
    $adminenabledpage = true;
}

  // Define the categories in the order they should appear
  $categories = [
    'About Birthday.Gold',
    'Birthday.Gold Accounts',
    'Explore and Claim Your Rewards'
  ];

// Modern FAQ Page Styles
$additionalstyles = '
<style>

/* Search Box */
.faq-search {
    max-width: 600px;
    margin: -2rem auto 3rem;
    position: relative;
    z-index: 10;
}

.search-input {
    width: 100%;
    padding: 1rem 3rem 1rem 1.5rem;
    font-size: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.search-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

/* Category Sections */
.faq-category {
    margin-bottom: 3rem;
}

.category-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid #667eea;
    display: inline-block;
}

/* Accordion Styling */
.faq-accordion {
    border: none;
    background: none;
}

.faq-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    margin-bottom: 1rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.faq-button {
    width: 100%;
    padding: 1.25rem 1.5rem;
    background: none;
    border: none;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.faq-button:hover {
    background: #f8f9fa;
}

.faq-button::after {
    content: "+";
    font-size: 1.5rem;
    font-weight: 300;
    color: #667eea;
    transition: all 0.3s ease;
    line-height: 1;
}

.faq-button[aria-expanded="true"]::after {
    content: "−";
}

.faq-button:not(.collapsed)::after {
    content: "−";
}

.faq-question {
    font-size: 1.125rem;
    font-weight: 600;
    color: #212529;
    margin: 0;
    padding-right: 2rem;
    line-height: 1.5;
}

.faq-collapse {
    border-top: 1px solid #e9ecef;
}

.faq-answer {
    padding: 1.5rem;
    color: #495057;
    line-height: 1.7;
}

.faq-answer p {
    margin-bottom: 1rem;
}

.faq-answer p:last-child {
    margin-bottom: 0;
}

.faq-answer ul, .faq-answer ol {
    margin: 1rem 0;
    padding-left: 2rem;
}

.faq-answer li {
    margin-bottom: 0.5rem;
}

/* Admin Edit Button */
.edit-faq-btn {
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

/* No Results Message */
.no-results {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.no-results i {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
    color: #dee2e6;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .category-title {
        font-size: 1.5rem;
    }
    
    .faq-question {
        font-size: 1rem;
    }
    
    .faq-button {
        padding: 1rem 1.25rem;
    }
    
    .faq-answer {
        padding: 1.25rem;
    }
}

/* Smooth Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.faq-category {
    animation: fadeIn 0.6s ease-out;
}

.faq-item {
    animation: fadeIn 0.4s ease-out;
    animation-fill-mode: both;
}

.faq-item:nth-child(1) { animation-delay: 0.1s; }
.faq-item:nth-child(2) { animation-delay: 0.2s; }
.faq-item:nth-child(3) { animation-delay: 0.3s; }
.faq-item:nth-child(4) { animation-delay: 0.4s; }
.faq-item:nth-child(5) { animation-delay: 0.5s; }

/* Better collapse animation */
.accordion-collapse {
    transition: all 0.3s ease;
}

.collapsing {
    transition: height 0.3s ease;
}

/* Expand/Collapse All Buttons */
.faq-controls {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-bottom: 2rem;
}

.faq-controls button {
    padding: 0.5rem 1rem;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 25px;
    font-size: 0.875rem;
    color: #495057;
    cursor: pointer;
    transition: all 0.2s ease;
}

.faq-controls button:hover {
    background: #f8f9fa;
    border-color: #667eea;
    color: #667eea;
}

.faq-controls button i {
    margin-right: 0.5rem;
}
</style>
';



  
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
  $sql = "SELECT * FROM bg_content WHERE category = 'faq' AND display_name != '' AND description != '' ORDER BY `grouping`, `rank`";
  $stmt = $database->prepare($sql);
  $stmt->execute();
  $all_faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Organize FAQs by category
  $faqs_by_category = [];
  foreach ($all_faqs as $faq) {
    // Skip empty FAQs
    if (empty(trim($faq['display_name'])) || empty(trim($faq['description']))) {
      continue;
    }
    $category = $faq['grouping'] ?: 'Uncategorized';
    if (!isset($faqs_by_category[$category])) {
      $faqs_by_category[$category] = [];
    }
    $faqs_by_category[$category][] = $faq;
  }


  include($dir['core_components'] . '/bg_pagestart.inc');
  include($dir['core_components'] . '/bg_header.inc');


  echo '
  <!-- FAQ Hero Section -->
  <div class="content-header-dark no-rounded-corners">
    <div class="container">
      <h1>Frequently Asked Questions</h1>
      <p class="lead">Find answers to common questions about Birthday Gold</p>
    </div>
  </div>
  
  <div class="container">
    <!-- Search Box -->
    <div class="faq-search">
      <div class="position-relative">
        <input 
          type="text" 
          class="search-input" 
          placeholder="Search for answers..."
          id="faqSearch"
        >
        <i class="bi bi-search search-icon"></i>
      </div>
    </div>
    
    <!-- Expand/Collapse Controls -->
    <div class="faq-controls">
      <button onclick="expandAllFAQs()" title="Expand all questions">
        <i class="bi bi-arrows-expand"></i>Expand All
      </button>
      <button onclick="collapseAllFAQs()" title="Collapse all questions">
        <i class="bi bi-arrows-collapse"></i>Collapse All
      </button>
    </div>
    
    <div id="faqContent">
';




  // Display each category with its FAQs
  foreach ($categories as $category) {
    if (isset($faqs_by_category[$category]) && !empty($faqs_by_category[$category])) {
      echo '<div class="faq-category" data-category="' . htmlspecialchars($category) . '">';
      echo '<h2 class="category-title">' . htmlspecialchars($category) . '</h2>';
      echo '<div class="faq-accordion" id="accordionFaq' . preg_replace('/[^a-zA-Z0-9]/', '', $category) . '">';
      
      $faqs = $faqs_by_category[$category];
      foreach ($faqs as $index => $faq) {
        $headingId = "faqHeading" . $faq['id'];
        $collapseId = "faqCollapse" . $faq['id'];
        $question = htmlspecialchars($app->tagreplace($faq['display_name']), ENT_QUOTES, 'UTF-8');
        $answer = $app->tagreplace($faq['description']);
        $faq_id = $faq['id'];

        echo '<div class="faq-item" data-question="' . htmlspecialchars($question, ENT_QUOTES, 'UTF-8') . '">';
        echo '<h3 id="' . $headingId . '" class="mb-0">';
        echo '<button class="faq-button collapsed" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" aria-expanded="false" aria-controls="' . $collapseId . '">';
        echo '<span class="faq-question">' . $question . '</span>';
        echo '</button>';
        echo '</h3>';
        echo '<div class="accordion-collapse collapse faq-collapse" id="' . $collapseId . '" aria-labelledby="' . $headingId . '" data-bs-parent="#accordionFaq' . preg_replace('/[^a-zA-Z0-9]/', '', $category) . '">';
        echo '<div class="faq-answer">';
        echo $answer;

        // If the user is an admin, show the edit gear icon
        if ($account->isadmin()) {
          echo '<div class="d-flex justify-content-end">';
          echo '<button class="btn btn-sm btn-outline-secondary edit-faq-btn" data-bs-toggle="modal" data-bs-target="#editFaqModal' . $faq_id . '"><i class="bi bi-gear-fill"></i> Edit</button>';
          echo '</div>';
        }

        echo '</div></div></div>';

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
      echo '</div>'; // End of faq-category
    }
  }

  // Display any uncategorized FAQs at the end
  if (isset($faqs_by_category['Uncategorized']) && !empty($faqs_by_category['Uncategorized'])) {
    echo '<div class="faq-category" data-category="Uncategorized">';
    echo '<h2 class="category-title">Other Questions</h2>';
    echo '<div class="faq-accordion" id="accordionFaqUncategorized">';
    
    $faqs = $faqs_by_category['Uncategorized'];
    foreach ($faqs as $index => $faq) {
      $headingId = "faqHeadingUncat" . $faq['id'];
      $collapseId = "faqCollapseUncat" . $faq['id'];
      $question = htmlspecialchars($app->tagreplace($faq['display_name']), ENT_QUOTES, 'UTF-8');
      $answer = $app->tagreplace($faq['description']);
      $faq_id = $faq['id'];

      echo '<div class="faq-item" data-question="' . htmlspecialchars($question, ENT_QUOTES, 'UTF-8') . '">';
      echo '<h3 id="' . $headingId . '" class="mb-0">';
      echo '<button class="faq-button collapsed" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" aria-expanded="false" aria-controls="' . $collapseId . '">';
      echo '<span class="faq-question">' . $question . '</span>';
      echo '</button>';
      echo '</h3>';
      echo '<div class="accordion-collapse collapse faq-collapse" id="' . $collapseId . '" aria-labelledby="' . $headingId . '" data-bs-parent="#accordionFaqUncategorized">';
      echo '<div class="faq-answer">';
      echo $answer;

      // If the user is an admin, show the edit gear icon
      if ($account->isadmin()) {
        echo '<div class="d-flex justify-content-end">';
        echo '<button class="btn btn-sm btn-outline-secondary edit-faq-btn" data-bs-toggle="modal" data-bs-target="#editFaqModalUncat' . $faq_id . '"><i class="bi bi-gear-fill"></i> Edit</button>';
        echo '</div>';
      }

      echo '</div></div></div>';

      // Modal for editing the FAQ
      if ($account->isadmin()) {
        echo '
        <div class="modal modal-lg fade" id="editFaqModalUncat' . $faq_id . '" tabindex="-1" aria-labelledby="editFaqModalLabelUncat' . $faq_id . '" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editFaqModalLabelUncat' . $faq_id . '">Edit FAQ</h5>
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
    echo '</div>'; // End of faq-category
  }
  ?>

    </div><!-- End faqContent -->
    
    <!-- No Results Message -->
    <div class="no-results" id="noResults" style="display: none;">
      <i class="bi bi-search"></i>
      <h3>No matching questions found</h3>
      <p>Try searching with different keywords</p>
    </div>
  </div><!-- End container -->

<?php
// Add JavaScript for search functionality
$footerattribute['postfooter'] = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("faqSearch");
    const faqCategories = document.querySelectorAll(".faq-category");
    const faqItems = document.querySelectorAll(".faq-item");
    const noResults = document.getElementById("noResults");
    const faqContent = document.getElementById("faqContent");
    
    if (searchInput) {
        searchInput.addEventListener("input", function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            let hasResults = false;
            
            if (searchTerm === "") {
                // Show all items and categories
                faqCategories.forEach(category => {
                    category.style.display = "";
                });
                faqItems.forEach(item => {
                    item.style.display = "";
                });
                faqContent.style.display = "";
                noResults.style.display = "none";
                return;
            }
            
            // Hide all categories initially
            faqCategories.forEach(category => {
                category.style.display = "none";
            });
            
            // Search through FAQ items
            faqItems.forEach(item => {
                const question = item.getAttribute("data-question").toLowerCase();
                const answer = item.querySelector(".faq-answer").textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = "";
                    // Show the parent category
                    const parentCategory = item.closest(".faq-category");
                    if (parentCategory) {
                        parentCategory.style.display = "";
                    }
                    hasResults = true;
                } else {
                    item.style.display = "none";
                }
            });
            
            // Show/hide no results message
            if (hasResults) {
                faqContent.style.display = "";
                noResults.style.display = "none";
            } else {
                faqContent.style.display = "none";
                noResults.style.display = "block";
            }
        });
        
        // Focus search on page load
        searchInput.focus();
    }
    
    // Smooth scroll to FAQ when clicking from another page
    if (window.location.hash) {
        const element = document.querySelector(window.location.hash);
        if (element) {
            // Open the accordion if it exists
            const collapse = element.querySelector(".accordion-collapse");
            if (collapse) {
                const bsCollapse = new bootstrap.Collapse(collapse, {
                    show: true
                });
            }
            // Smooth scroll to element
            setTimeout(() => {
                element.scrollIntoView({ behavior: "smooth", block: "center" });
            }, 300);
        }
    }
});

// Expand all FAQs
function expandAllFAQs() {
    const collapses = document.querySelectorAll(".faq-collapse:not(.show)");
    collapses.forEach(collapse => {
        const bsCollapse = new bootstrap.Collapse(collapse, {
            show: true
        });
    });
}

// Collapse all FAQs
function collapseAllFAQs() {
    const collapses = document.querySelectorAll(".faq-collapse.show");
    collapses.forEach(collapse => {
        const bsCollapse = new bootstrap.Collapse(collapse, {
            hide: true
        });
    });
}
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();