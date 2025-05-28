<?PHP
$footerattribute['postfooter'] .= '
<script>
// SEARCH BAR ----------------------------------------------------------------------
$(document).ready(function(){
  $(".search-input").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      var status = $(this).data("status");
      
      $("#section-" + status + " .list-group-item").each(function() {
          var itemText = $(this).data("full-context").toLowerCase();
          if (itemText.includes(value)) {
              $(this).show();
              $(this).css("display", "");
          } else {
              $(this).hide();                
              $(this).attr("style", "display: none !important;");
          }
      });
  });

  $(".clear-icon").on("click", function() {
      var status = $(this).data("status");
      $("#searchBar-" + status).val("").trigger("keyup");
  });
});
</script>
';


/*
$footerattribute['postfooter'] .= '
<script>
document.addEventListener("DOMContentLoaded", function() {
  const cards = document.querySelectorAll("article.card");

  cards.forEach(card => {
    const existingButton = card.querySelector(".existingButton");
    const enrollButton = card.querySelector(".add-to-gallery");
    const existingCheckbox = card.querySelector(".existingCheckbox");
    const selectionCheckbox = card.querySelector("input[name=\'selectionlist[]\']");

    function toggleButtons() {
      if (existingButton.classList.contains("border-success") || enrollButton.classList.contains("btn-success")) {
        existingButton.disabled = existingButton.classList.contains("border-success") ? false : true;
        enrollButton.disabled = enrollButton.classList.contains("btn-success") ? false : true;
      } else {
        existingButton.disabled = false;
        enrollButton.disabled = false;
      }
    }

    // I HAVE THIS buttons ----------------------------------------
    existingButton.addEventListener("click", function() {
      const icon = this.querySelector(".bi");
      const label = this.querySelector(".have-label");
      if (this.classList.contains("border-success")) {
        this.classList.remove("border-success");
        icon.className = "bi bi-heart text-danger";
     '.($pagetype=='list'?'label.textContent = "I Have This";':'').'
        if (existingCheckbox) existingCheckbox.checked = false;
      } else {
        this.classList.add("border-success");
        icon.className = "bi bi-heart-fill text-danger";
        '.($pagetype=='list'?'label.textContent = "Have This";':'').'
        if (existingCheckbox) existingCheckbox.checked = true;

        // Disable the other button
        enrollButton.classList.remove("btn-success", "border-success");
        const enrollIcon = enrollButton.querySelector(".bi");
        const enrollLabel = enrollButton.querySelector(".enroll-label");
        enrollIcon.className = "bi bi-plus-circle";
         '.($pagetype=='list'?'enrollLabel.textContent = "Enroll Me";':'').'
        if (selectionCheckbox) selectionCheckbox.checked = false;
      }
      toggleButtons();
    });

    // ADD TO ENROLLMENT buttons ----------------------------------------
    enrollButton.addEventListener("click", function() {
      const icon = this.querySelector(".bi");
      const label = this.querySelector(".enroll-label");
      if (this.classList.contains("btn-success")) {
        this.classList.remove("btn-success");
        this.classList.remove("border-success");
        icon.className = "bi bi-plus-circle";
         '.($pagetype=='list'?'label.textContent = "Enroll Me";':'').'
        if (selectionCheckbox) selectionCheckbox.checked = false;
      } else {
        this.classList.add("btn-success");
        this.classList.add("border-success");
        icon.className = "bi bi-check-circle-fill";
        '.($pagetype=='list'?'label.textContent = "Selected";':'').'
        if (selectionCheckbox) selectionCheckbox.checked = true;

        // Disable the other button
        existingButton.classList.remove("border-success");
        const existingIcon = existingButton.querySelector(".bi");
        const existingLabel = existingButton.querySelector(".have-label");
        existingIcon.className = "bi bi-heart text-danger";
         '.($pagetype=='list'?'existingLabel.textContent = "I Have This";':'').'
        if (existingCheckbox) existingCheckbox.checked = false;
      }
      toggleButtons();
    });
  });
});
</script>
';

*/
$footerattribute['postfooter'] .= '
<script>
// FILTER BAR ----------------------------------------------------------------------
$(document).ready(function(){
  const filterCheckboxes = document.querySelectorAll(".filter-checkbox");
  const ratingFilters = document.querySelectorAll(".filter-checkbox[data-section=\'rating\']");
  const articles = document.querySelectorAll("article");
  const resetButton = document.getElementById("resetFiltersButton");

  function applyFilters() {
    const activeFilters = {};

    // Collect active filters
    filterCheckboxes.forEach(checkbox => {
      const section = checkbox.getAttribute("data-section");
      const label = checkbox.getAttribute("data-label");

      if (!activeFilters[section]) {
        activeFilters[section] = [];
      }

      if (checkbox.checked) {
        activeFilters[section].push(label);
      }
    });

   //   console.log("Active Filters:", activeFilters);

    // Collect selected rating filters
    const selectedRatings = Array.from(ratingFilters)
      .filter(filter => filter.checked)
      .map(filter => parseFloat(filter.value));

   //   console.log("Selected Ratings:", selectedRatings);

    // Apply filters to articles
    articles.forEach(article => {
      let visible = true;

      // Apply attribute filters
      for (const section in activeFilters) {
        if (activeFilters[section].length > 0 && section !== "rating") {
          const articleAttribute = article.getAttribute("data-" + section);

          if (articleAttribute) {
            const tokens = articleAttribute.split(" ");
           //   console.log(`Article ID: ${article.id}, Section: ${section}, Tokens:`, tokens);

            if (!tokens.some(token => activeFilters[section].includes(token))) {
              visible = false;
             //   console.log(`Article ID: ${article.id} is not visible due to section: ${section}`);
              break;
            }
          } else {
            visible = false;
           //   console.log(`Article ID: ${article.id} has no data-${section} attribute`);
            break;
          }
        }
      }

      // Apply rating filter
      if (visible && selectedRatings.length > 0) {
        const rating = parseFloat(article.getAttribute("data-rating"));
        visible = selectedRatings.some(selectedRating => rating >= selectedRating);
       //   console.log(`Article ID: ${article.id}, Rating: ${rating}, Visibility after rating filter:`, visible);
      }

      article.style.display = visible ? "block" : "none";
     //   console.log(`Article ID: ${article.id} visibility:`, visible);
    });
  }


  filterCheckboxes.forEach(checkbox => {
    checkbox.addEventListener("change", applyFilters);
  });



  // RESET FILTER BUTTON
      function resetFilters() {
    filterCheckboxes.forEach(checkbox => {
      checkbox.checked = false;
    });
    applyFilters();
  }
  resetButton.addEventListener("click", resetFilters);


  // Initial application of filters
  applyFilters();
});


</script>
';


if (empty($suppress_jslibrary))
$footerattribute['postfooter'] .= '
<script src="/public/js/jquery-3.6.0.min.js"></script>
'.$website["bootstrap_js"].'
';


$footerattribute['postfooter'] .= '
<script>
$(document).ready(function(){
    $("#searchBar").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        console.log("Search value:", value);

        $("article.card").filter(function() {
            var company = $(this).data("companyname") ? $(this).data("companyname").toLowerCase() : "";
            var rewards = $(this).data("rewards") ? $(this).data("rewards").toLowerCase() : "";
            var categories = $(this).data("categories") ? $(this).data("categories").toLowerCase() : "";
            var rating = $(this).data("rating") ? $(this).data("rating").toString().toLowerCase() : "";
            var types = $(this).data("types") ? $(this).data("types").toString().toLowerCase() : "";

            console.log("Company:", company);
            console.log("Rewards:", rewards);
            console.log("Categories:", categories);
            console.log("Rating:", rating);
            console.log("Types:", types);

            var match = company.includes(value) || 
                        rewards.includes(value) || 
                        categories.includes(value) || 
                        rating.includes(value) || 
                        types.includes(value);

            console.log("Match:", match);

            $(this).toggle(match);
        });
    });
});
</script>
';