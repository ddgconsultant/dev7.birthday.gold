// Birthday Gold Feedback Form JavaScript
document.addEventListener("DOMContentLoaded", function() {
    // Initialize the form page navigation
    let currentPage = 1;
    const maxPages = 3;
    
    // Navigation buttons
    const nextBtn = document.querySelector(".btn-next");
    const prevBtn = document.querySelector(".btn-prev");
    const submitBtn = document.querySelector(".btn-submit");
    
    // Get all pages
    const page1 = document.querySelector(".page-1");
    const page2 = document.querySelector(".page-2");
    const page3 = document.querySelector(".page-3");
    
    // Ensure pages exist
    if (!page1 || !page2 || !page3) {
        console.error("Missing page elements");
        return;
    }
    
    // Initialize page visibility
    function showPage(pageNum) {
        // Hide all pages first
        page1.classList.remove("active");
        page1.style.display = "none";
        page2.classList.remove("active");
        page2.style.display = "none";
        page3.classList.remove("active");
        page3.style.display = "none";
        
        // Show the requested page
        switch(pageNum) {
            case 1:
                page1.classList.add("active");
                page1.style.display = "block";
                break;
            case 2:
                page2.classList.add("active");
                page2.style.display = "block";
                break;
            case 3:
                page3.classList.add("active");
                page3.style.display = "block";
                break;
        }
        
        currentPage = pageNum;
    }
    
    // Show first page on load
    showPage(1);
    
    // Initialize navigation immediately
    updateNavigation();
    updateProgressIndicator();
    
    if (nextBtn) {
        nextBtn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (currentPage < maxPages) {
                showPage(currentPage + 1);
                
                // Update hidden field
                const currentPageInput = document.getElementById("current_page");
                if (currentPageInput) {
                    currentPageInput.value = currentPage;
                }
                
                // Scroll to top of form
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                updateNavigation();
            }
            return false;
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (currentPage > 1) {
                showPage(currentPage - 1);
                
                // Update hidden field
                const currentPageInput = document.getElementById("current_page");
                if (currentPageInput) {
                    currentPageInput.value = currentPage;
                }
                
                // Scroll to top of form
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                updateNavigation();
            }
            return false;
        });
    }
    
    function updateNavigation() {
        // Update the display of navigation buttons
        console.log("Updating navigation for page", currentPage);
        
        if (prevBtn) {
            // Show back button on pages 2 and 3, hide on page 1
            if (currentPage === 1) {
                prevBtn.style.display = "none";
                prevBtn.style.visibility = "hidden";
            } else {
                prevBtn.style.display = "inline-flex";
                prevBtn.style.visibility = "visible";
            }
        }
        
        if (nextBtn && submitBtn) {
            // On last page, hide next and show submit
            if (currentPage === maxPages) {
                nextBtn.style.display = "none";
                nextBtn.style.visibility = "hidden";
                submitBtn.style.display = "inline-flex";
                submitBtn.style.visibility = "visible";
            } else {
                // On other pages, show next and hide submit
                nextBtn.style.display = "inline-flex";
                nextBtn.style.visibility = "visible";
                submitBtn.style.display = "none";
                submitBtn.style.visibility = "hidden";
            }
        }
        
        // Update progress indicator
        updateProgressIndicator();
    }
    
    function updateProgressIndicator() {
        const progressSlices = document.querySelectorAll(".cake-slice");
        progressSlices.forEach((slice, index) => {
            // Remove all classes first
            slice.classList.remove("bg-warning", "bg-warning-subtle", "bg-light", "border-warning");
            
            if (index + 1 < currentPage) {
                // Completed pages
                slice.classList.add("bg-warning");
                slice.innerHTML = '<i class="fas fa-check text-white" style="font-size: 12px;"></i>';
            } else if (index + 1 === currentPage) {
                // Current page
                slice.classList.add("bg-warning-subtle", "border", "border-warning");
                slice.innerHTML = "";
            } else {
                // Future pages
                slice.classList.add("bg-light", "border");
                slice.innerHTML = "";
            }
        });
        
        const indicator = document.querySelector(".progress-page-indicator");
        if (indicator) {
            indicator.textContent = "Page " + currentPage + " of " + maxPages;
        }
    }
    
    // Star rating functionality with dynamic feedback
    const starContainers = document.querySelectorAll(".star-rating");
    
    // Rating feedback messages
    const ratingMessages = {
        overall_rating: [
            "We're sorry to hear that! We'll work to improve.",
            "Thanks for the honest feedback.",
            "Glad you had a decent experience!",
            "That's great to hear! Thank you!",
            "Wow! We're thrilled you had an amazing birthday experience! 🎉"
        ],
        value_rating: ["Not valuable", "Somewhat valuable", "Moderately valuable", "Quite valuable", "Extremely valuable"],
        ease_rating: ["Very difficult", "Somewhat difficult", "Neutral", "Fairly easy", "Very easy"],
        timeliness_rating: ["Very late", "Somewhat late", "On time", "Good timing", "Perfect timing"]
    };
    
    starContainers.forEach(container => {
        const stars = container.querySelectorAll(".star");
        const fieldName = container.dataset.field || 'overall_rating';
        const ratingInput = document.getElementById(fieldName) || container.nextElementSibling;
        
        // Create feedback display if it doesn't exist
        let feedbackDiv = container.parentElement.querySelector('.rating-feedback');
        if (!feedbackDiv && ratingMessages[fieldName]) {
            feedbackDiv = document.createElement('div');
            feedbackDiv.className = 'rating-feedback text-center mt-2';
            feedbackDiv.style.color = '#d4af37';
            feedbackDiv.style.fontWeight = '500';
            feedbackDiv.style.minHeight = '24px';
            container.parentElement.appendChild(feedbackDiv);
        }
        
        stars.forEach((star, index) => {
            // Set initial data-rating if not present
            if (!star.dataset.rating) {
                star.dataset.rating = index + 1;
            }
            
            star.addEventListener("click", function(e) {
                e.preventDefault();
                const rating = parseInt(star.dataset.rating);
                
                if (ratingInput) {
                    ratingInput.value = rating;
                }
                
                // Update visual state of all stars
                stars.forEach((s, i) => {
                    s.classList.remove("active");
                    if (i < rating) {
                        s.classList.add("active");
                        s.innerHTML = "&#9733;"; // Filled star
                        s.style.color = "#ffc107";
                    } else {
                        s.innerHTML = "&#9734;"; // Empty star
                        s.style.color = "#ddd";
                    }
                });
                
                // Show feedback message
                if (feedbackDiv && ratingMessages[fieldName]) {
                    feedbackDiv.textContent = ratingMessages[fieldName][rating - 1];
                    feedbackDiv.style.display = 'block';
                }
            });
            
            // Hover effect
            star.addEventListener("mouseenter", function() {
                const rating = parseInt(star.dataset.rating);
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.style.color = "#ffc107";
                        s.style.transform = "scale(1.1)";
                    } else {
                        s.style.color = "#ddd";
                        s.style.transform = "scale(1)";
                    }
                });
            });
            
            star.addEventListener("mouseleave", function() {
                stars.forEach((s) => {
                    s.style.transform = "scale(1)";
                });
            });
        });
        
        // Reset hover effect when leaving the container
        container.addEventListener("mouseleave", function() {
            stars.forEach(s => {
                const currentRating = parseInt(ratingInput ? ratingInput.value : 0);
                const starRating = parseInt(s.dataset.rating);
                s.style.color = starRating <= currentRating ? "#ffc107" : "#ddd";
                s.style.transform = "scale(1)";
            });
        });
    });
    
    // Radio button selection with visual feedback
    const radioContainers = document.querySelectorAll('.toggle-switches');
    radioContainers.forEach(container => {
        const radioOptions = container.querySelectorAll('.form-check');
        
        radioOptions.forEach(option => {
            const radioInput = option.querySelector('input[type="radio"]');
            
            // Make entire card clickable
            option.style.cursor = 'pointer';
            
            option.addEventListener('click', function(e) {
                // Don't double-trigger if clicking the input itself
                if (e.target.tagName !== 'INPUT') {
                    radioInput.checked = true;
                    radioInput.dispatchEvent(new Event('change'));
                }
            });
            
            if (radioInput) {
                radioInput.addEventListener('change', function() {
                    // Reset all options in this group
                    radioOptions.forEach(opt => {
                        opt.style.backgroundColor = 'white';
                        opt.classList.remove('border-primary', 'border-2');
                    });
                    
                    // Highlight selected option
                    if (this.checked) {
                        option.style.backgroundColor = '#f0f7ff';
                        option.classList.add('border-primary', 'border-2');
                    }
                });
            }
        });
    });
    
    // Business toggle functionality
    const businessToggles = document.querySelectorAll(".business-toggle");
    businessToggles.forEach(toggle => {
        toggle.addEventListener("change", function() {
            // Update the card styling
            const card = this.closest(".business-card");
            if (card) {
                if (this.checked) {
                    card.classList.add("border-success", "border-2");
                    card.style.backgroundColor = "#f0fff4";
                } else {
                    card.classList.remove("border-success", "border-2");
                    card.style.backgroundColor = "white";
                }
            }
        });
    });
    
    // NPS slider functionality
    const npsSlider = document.getElementById("nps_rating_slider");
    if (npsSlider) {
        const npsValueDisplay = document.getElementById("nps_value_display");
        
        npsSlider.addEventListener("input", function() {
            const value = this.value;
            if (npsValueDisplay) {
                npsValueDisplay.textContent = value;
            }
        });
    }
    
    // Character counter for textareas
    const textareas = document.querySelectorAll("textarea[maxlength]");
    textareas.forEach(textarea => {
        // Create character counter display
        const counterDiv = document.createElement("div");
        counterDiv.className = "text-muted small mt-1";
        counterDiv.innerHTML = '<span class="char-count">0/' + textarea.maxLength + '</span>';
        textarea.parentNode.insertBefore(counterDiv, textarea.nextSibling);
        
        const countDisplay = counterDiv.querySelector(".char-count");
        
        textarea.addEventListener("input", function() {
            if (countDisplay) {
                countDisplay.textContent = this.value.length + "/" + this.maxLength;
            }
        });
    });
});