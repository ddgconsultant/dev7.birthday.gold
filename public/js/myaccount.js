/*
$(document).ready(function() {
    $('.accordion-button').each(function() {
        if($(this).attr('aria-expanded') === 'true') {
            $(this).find('.indicator').text(' Less');
        }
    });

    // existing accordion state change handlers...
    $('#infoAccordion').on('show.bs.collapse', function(e) {
        $(e.target).prev('.accordion-header').find('.indicator').text('Less');
    });
    $('#infoAccordion').on('hide.bs.collapse', function(e) {
        $(e.target).prev('.accordion-header').find('.indicator').text('More');
    });
});
*/




  // //////////////////////////////////////////////////// TIP ALERT COOKIE ---------------------------------------------------
  const closeButton = document.querySelector(".btn-close");
  closeButton.addEventListener("click", function() {
      const now = new Date();
      now.setTime(now.getTime() + (3 * 24 * 60 * 60 * 1000)); // Set the expiration to 3 days from now
      document.cookie = "hideAlert=true; expires=" + now.toUTCString() + "; path=/";
  });

  // Check if the hideAlert cookie is set
  const cookies = document.cookie.split(";").map(cookie => cookie.trim());
  if (cookies.includes("hideAlert=true")) {
      const alert = document.querySelector(".alert");
      if (alert) {
          alert.style.display = "none";
      }
  }