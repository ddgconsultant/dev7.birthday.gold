<?PHP
if (!isset($initialcount)) $initialcount=0;
if (!isset($initialcount)) $planlimit=20;
$include_local_output='
<!-- ===============================================-->
<!--    JavaScripts-->
<!-- ===============================================-->
<script src="/public/assets/vendors/popper/popper.min.js"></script>
<script src="/public/assets/vendors/bootstrap/bootstrap.min.js"></script>
<script src="/public/assets/vendors/anchorjs/anchor.min.js"></script>
<script src="/public/assets/vendors/is/is.min.js"></script>
<script src="/public/assets/vendors/swiper/swiper-bundle.min.js"></script>
<script src="/public/assets/vendors/glightbox/glightbox.min.js"></script>
<!-- <script src="/public/assets/vendors/fontawesome/all.min.js"></script> -->
<script src="/public/assets/vendors/lodash/lodash.min.js"></script>
<!-- <script src="https://polyfill.io/v3/polyfill.min.js?features=window.scroll"></script>
<script src="/public/assets/vendors/list.js/list.min.js"></script> -->
<script src="/public/assets/js/theme.js"></script>


';

echo '
<script>
// PROGRESS BAR ----------------------------------------------------------------------
$(document).ready(function() {
  const progressBar = $(".progress-bar");
  const checkboxes = $(".quiz_checkbox");
  let initialCount = ' . $initialcount . ';
  const alertDiv = $("#planlimitalert");
  const countDisplay = $("#count-display");
  const countDisplay2 = $("#count-display2");


// -- submit buttons - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
  const submitButtons = $("button[name=\'submit_button_confirmed\']"); // Select all buttons with name="submit_button_confirmed"

 const updateButtonState = () => {
    let checkedCount = checkboxes.filter(":checked").length;

    // Enable/disable both submit buttons based on checkbox selections
    if (checkedCount > 0) {
      submitButtons.prop("disabled", false);
      submitButtons.css("opacity", "1");
    } else {
      submitButtons.prop("disabled", true);
      submitButtons.css("opacity", "0.5");
    }
  };
  // Call updateButtonState to set the initial state
  updateButtonState();

  // Update the button state when checkboxes change
  checkboxes.on("change", updateButtonState);



// -- progess bar - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
  const updateProgress = () => {
    let checkedCount = checkboxes.filter(":checked").length;
    let totalSelections = checkedCount + initialCount;
    const percentage = Math.min(100 * totalSelections / ' . $planlimit . ', 100);

    if (progressBar.length) {
      progressBar.css("width", percentage + "%");
      progressBar.text(Math.round(percentage) + "%"); // Update the percentage text
    } else {
      console.error("Element with class \'progress-bar\' not found");
    }

    if (countDisplay.length) {
      countDisplay.text(totalSelections);
    }
    if (countDisplay2.length) {
      countDisplay2.text(totalSelections);
    }

    if (alertDiv.length) {
      if (totalSelections >= ' . $planlimit . ') {
        checkboxes.not(":checked").prop("disabled", true);
        alertDiv.show();
      } else {
        checkboxes.prop("disabled", false);
        alertDiv.hide();
      }
    } else {
      console.error("Element with ID \'planlimitalert\' not found");
    }
  };

  // Call updateProgress to set the initial state
  updateProgress();

 // Update progress and button states when checkboxes are changed
  checkboxes.on("change", function() {
    updateProgress();
    updateButtonState();  // Ensure the button state is also updated when checkboxes change
  });


  // ENROLL button functionality ----------------------------------------------------------------------
  $(".enroll-button").on("click", function(event) {
    const enrollButton = $(this);
    const companyId = enrollButton.data("company-id");
    const checkbox = $("#" + companyId);
    const haveButton = $(".have-button[data-company-id=\'" + companyId + "\']");

    let checkedCount = checkboxes.filter(":checked").length;
    let totalSelections = checkedCount + initialCount;

    // Toggle checkbox and button states
    if (totalSelections < ' . $planlimit . ' || checkbox.is(":checked")) {
        checkbox.prop("checked", !checkbox.is(":checked"));
    }

    if (checkbox.is(":checked")) {
      enrollButton.html("<i class=\'bi bi-dash-circle\'></i> '.$enroll_label_active.'") // Change icon to dash-circle for Remove state
                  .removeClass("btn-primary btn-disabled btn-dark")
                  .addClass("btn-warning") // Gold color for Remove
                  .blur(); // Remove focus after click

      if (haveButton.length) {
        haveButton.prop("disabled", true)
                  .removeClass("btn-success")
                  .addClass("btn-disabled");
      }
    } else {
      enrollButton.html("<i class=\'bi bi-plus-circle\'></i> '.$enroll_label_default.'") // Revert icon to plus-circle for Enroll Me state
                  .removeClass("btn-warning btn-disabled")
                  .addClass("btn-primary") // Light blue Enroll Me
                  .blur(); // Remove focus after click

      if (haveButton.length) {
        haveButton.prop("disabled", false)
                  .removeClass("btn-disabled");
      }
    }

    event.preventDefault();
    updateProgress();
    updateButtonState();
  });

  
  // HAVE button functionality ----------------------------------------------------------------------
  $(".have-button").on("click", function() {
    const haveButton = $(this);
    const companyId = haveButton.data("company-id");
    const haveCheckbox = $("input[name=\'existinglist[]\'][value=\'" + companyId + "\']");
    const enrollButton = $(".enroll-button[data-company-id=\'" + companyId + "\']");

    haveCheckbox.prop("checked", !haveCheckbox.is(":checked"));

    if (haveCheckbox.is(":checked")) {
      haveButton.html("<i class=\'bi bi-heart-fill\' style=\'color: red\'></i> '.$have_label_active.'") // Change icon to heart-fill and red color for selected state
                .removeClass("btn-secondary deselected")
                .addClass("btn-success")
                .blur(); // Remove focus after click

      if (enrollButton.length) {
        enrollButton.prop("disabled", true)
                    .addClass("btn-disabled");
      }
    } else {
      haveButton.html("<i class=\'bi bi-heart\'></i> '.$have_label_default.'") // Revert icon to heart for deselected state
                .addClass("deselected")
                .removeClass("btn-success")
                .addClass("btn-secondary")
                .blur(); // Remove focus after click

      if (enrollButton.length) {
        enrollButton.prop("disabled", false)
                    .removeClass("btn-disabled");
      }
    }
      updateButtonState();
  });
});
</script>
';


