
<!-- Include Bootstrap Multiselect JS and CSS files -->
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js"></script>  
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css">  


<script>
// This script waits for the DOM to load, then attaches click event listeners 
// to elements with the class 'type-filter-dropdown'.
document.addEventListener('DOMContentLoaded', function() {
    // Mobile create new buttons
    document.querySelectorAll('.type-filter-dropdown').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var type = this.getAttribute('data-type');
            document.getElementById('typeInput').value = type;
            document.getElementById('createnewform').submit();
            initializeTooltips();
        });
    });
    
    // Desktop create new buttons
    document.querySelectorAll('.type-filter-dropdown-desktop').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var type = this.getAttribute('data-type');
            document.getElementById('typeInput2').value = type;
            document.getElementById('createnewform2').submit();
            initializeTooltips();
        });
    });
});
</script>


<script>

// Function to populate the right column with content dynamically
// Takes an 'itemId' as a parameter to fetch specific data
function populateRightColumn(itemId) {
    // Check if we're on mobile (< 992px)
    const isMobile = window.innerWidth < 992;
    
    // Get the name of the clicked item from the active list item
    const activeItem = document.querySelector('.list-group-item.active');
    const itemName = activeItem ? activeItem.querySelector('.item-name').textContent : 'Item Details';
    
    if (isMobile) {
        // Show modal on mobile
        const modal = new bootstrap.Modal(document.getElementById('credentialModal'));
        const modalTitle = document.getElementById('credentialModalLabel');
        const modalBody = document.getElementById('modalCredentialContent');
        
        // Debug - log what we're loading
        console.log('Loading credential:', itemId, 'Name:', itemName);
        
        // Update modal title
        modalTitle.textContent = itemName;
        
        // Show loading spinner
        modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Continue with AJAX call
        const formData = new FormData();
        formData.append('id', itemId);
        const csrfToken = '<?php echo addslashes($csrf_token); ?>'; 
        formData.append('_token', csrfToken);
        formData.append('act', 'getdata');
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', <?php echo $amscriptendpoint; ?>, true);
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Update modal body content
                modalBody.innerHTML = xhr.responseText;
                
                // Extract and show action buttons in modal header
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = xhr.responseText;
                const buttonData = tempDiv.querySelector('[data-action-buttons]');
                if (buttonData) {
                    const buttons = buttonData.getAttribute('data-action-buttons');
                    const modalHeader = document.querySelector('#credentialModal .modal-header');
                    
                    // Remove existing buttons if any
                    const existingButtons = modalHeader.querySelector('.action-buttons');
                    if (existingButtons) {
                        existingButtons.remove();
                    }
                    
                    // Add new buttons
                    const buttonContainer = document.createElement('div');
                    buttonContainer.className = 'action-buttons ms-auto me-2';
                    const textarea = document.createElement('textarea');
                    textarea.innerHTML = buttons;
                    buttonContainer.innerHTML = textarea.value;
                    modalHeader.insertBefore(buttonContainer, modalHeader.querySelector('.btn-close'));
                }
                
                // Update modal title with the actual item name from the active element
                const newActiveItem = document.querySelector('.list-group-item.active');
                if (newActiveItem) {
                    const actualItemName = newActiveItem.querySelector('.item-name').textContent;
                    modalTitle.textContent = actualItemName;
                }
                
                initializeTooltips();
            }
        };
        
        xhr.send(formData);
        
    } else {
        // Desktop behavior
        const card = document.getElementById('datapanel');
        const cardHeader = card.querySelector('.card-header');
        const cardBody = card.querySelector('.card-body');
        
        // Update the header with the item name and action buttons
        if (cardHeader) {
            cardHeader.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">${itemName}</h2>
                    <div class="action-buttons" id="headerActionButtons">
                        <!-- Buttons will be populated from AJAX response -->
                    </div>
                </div>
            `;
        }

        // Create a FormData object to send data via AJAX
        const formData = new FormData();
        formData.append('id', itemId);

        // Retrieve CSRF token to ensure security
        const csrfToken = '<?php echo addslashes($csrf_token); ?>'; 
        formData.append('_token', csrfToken);
        formData.append('act', 'getdata');

        // Create an AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', <?php echo $amscriptendpoint; ?>, true);

        // When the request completes
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Update the card body content
                cardBody.innerHTML = xhr.responseText;
                cardBody.classList.remove('d-flex', 'align-items-center', 'justify-content-center');
                
                // Extract action buttons from the response and place them in header
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = xhr.responseText;
                const buttonData = tempDiv.querySelector('[data-action-buttons]');
                if (buttonData) {
                    const buttons = buttonData.getAttribute('data-action-buttons');
                    const headerButtons = document.getElementById('headerActionButtons');
                    if (headerButtons && buttons) {
                        // Decode HTML entities
                        const textarea = document.createElement('textarea');
                        textarea.innerHTML = buttons;
                        headerButtons.innerHTML = textarea.value;
                    }
                }
                
                initializeTooltips();
            }
        };

        // Send the request
        xhr.send(formData);
    }
}



// Function to copy a password to the clipboard and log the action for security
// Takes rowData (an object containing the relevant data) as a parameter
function copyToClipboardAndLog(rowData) {
    // Get the password element from the DOM (it should be a password input field)
    const passwordElement = document.getElementById('password');
    
    // Temporarily change the input type to 'text' to allow copying
    passwordElement.type = 'text';
    
    // Select the text content within the password input field
    passwordElement.select();
    
    // Copy the selected text to the clipboard
    document.execCommand('copy');
    
    // Revert the input type back to 'password' for security
    passwordElement.type = 'password';

    // Log the copy action by calling the recordAccess function with the appropriate action
    recordAccess(rowData.id, 'clipboardcopy');
}

// Function to log user access to specific data
// Takes an itemId (ID of the data being accessed) and an optional action (default is 'show')
function recordAccess(itemId, action = 'show') {
    const csrfToken = '<?php echo addslashes($csrf_token); ?>'; // Ensure CSRF token is available

    // Create an AJAX request to log access
    const xhr = new XMLHttpRequest();
    xhr.open('POST',  <?php echo $amscriptendpoint; ?>, true); // POST request to the logging script

    // Set the request header for form submission
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    // Monitor the response to ensure the request completed successfully
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            // Log the successful access in the console
            console.log(action, 'Access recorded');
        }
    };

    // Prepare the data to send (ID, action, and CSRF token)
    const data = 'id=' + encodeURIComponent(itemId) + '&act=' + encodeURIComponent(action) + '&_token=' + encodeURIComponent(csrfToken);
    xhr.send(data); // Send the data
}

// Function to toggle the visibility of a password field
// Takes an itemId as a parameter to log the action (show/hide password)
function togglePasswordVisibility(itemId) {  
    let passwordInput = document.getElementById('password'); // Password input field
    let toggleIcon = document.getElementById('toggleIcon'); // Icon that indicates the password visibility state

    if (passwordInput.type === 'password') {
        // If password is currently hidden, show it and change the icon
        passwordInput.type = 'text';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
        // Log the action as 'showpassword'
        recordAccess(itemId, 'showpassword');  
    } else {
        // If password is currently visible, hide it and change the icon back
        passwordInput.type = 'password';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
        // Log the action as 'hidepassword'
        recordAccess(itemId, 'hidepassword');  
    }
}

</script>

<?php
/*
<script>
// Initialize Bootstrap tooltips for elements that have the attribute 'data-bs-toggle="tooltip"'
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl); // Initialize Bootstrap tooltips for each element
});
</script>
*/
?>


<script>
// Active state management for list items
$(document).ready(function(){
    // Add active state to clicked items
    $(document).on('click', '.list-group-item', function() {
        $('.list-group-item').removeClass('active');
        $(this).addClass('active');
    });
    
    // Clean up modal when hidden
    $('#credentialModal').on('hidden.bs.modal', function () {
        // Remove any action buttons
        const existingButtons = this.querySelector('.action-buttons');
        if (existingButtons) {
            existingButtons.remove();
        }
        // Reset title
        document.getElementById('credentialModalLabel').textContent = 'Credential Details';
        // Clear body
        document.getElementById('modalCredentialContent').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    });
    
    // Mobile category filter dropdown
    $('.mobile-category-filter').click(function(e) {
        e.preventDefault();
        
        var selectedValue = $(this).data('value');
        var selectedText = $(this).html();
        
        // Update dropdown button text
        $('#mobileFilterDropdown').html('<i class="bi bi-funnel me-1"></i> ' + selectedText.replace(/<i[^>]*><\/i>/g, '').trim() + ' <span class="pill-count">(' + $('.list-group-item:visible').length + ')</span>');
        
        // Apply the same filtering logic as desktop
        var visibleCount = 0;
        $('.list-group-item').each(function() {
            var itemCategoryValue = $(this).data('category');
            var searchValue = $('#searchBar').val().toLowerCase();
            var itemText = $(this).data('full-context').toLowerCase();
            
            var matchesCategory = (selectedValue === 'all' || itemCategoryValue === selectedValue);
            var matchesSearch = (searchValue === '' || itemText.includes(searchValue));
            
            if (matchesCategory && matchesSearch) {
                $(this).css('display', '');
                visibleCount++;
            } else {
                $(this).attr('style', 'display: none !important;');
            }
        });
        
        // Update count
        $('#mobileFilterDropdown .pill-count').text('(' + visibleCount + ')');
    });
});

// Category filter functionality with pills
$(document).ready(function(){
    // When a category pill is clicked
    $('.category-pill').click(function(e) {
        e.preventDefault();  // Prevent default anchor behavior

        // Update active state
        $('.category-pill').removeClass('active');
        $(this).addClass('active');

        var selectedCategoryValue = $(this).data('value');  // Get the selected category value
        var visibleCount = 0;

        // Iterate over each list item and show or hide based on the selected category
        $('.list-group-item').each(function() {
            var itemCategoryValue = $(this).data('category'); // Get the category value of the item
            var searchValue = $('#searchBar').val().toLowerCase();
            var itemText = $(this).data('full-context').toLowerCase();

            // Check both category and search criteria
            var matchesCategory = (selectedCategoryValue === 'all' || itemCategoryValue === selectedCategoryValue);
            var matchesSearch = (searchValue === '' || itemText.includes(searchValue));

            if (matchesCategory && matchesSearch) {
                $(this).css('display', ''); // Show matching items
                visibleCount++;
            } else {
                $(this).attr('style', 'display: none !important;'); // Hide non-matching items
            }
        });

        // Update count in All pill
        $('.category-pill[data-value="all"] .pill-count').text('(' + visibleCount + ')');
    });

    // Update search to work with categories
    $('#searchBar').on('keyup input', function() {
        var value = $(this).val().toLowerCase();
        var activeCategoryValue = $('.category-pill.active').data('value') || 'all';
        var visibleCount = 0;

        $('.list-group-item').each(function() {
            var itemText = $(this).data('full-context').toLowerCase();
            var itemCategoryValue = $(this).data('category');

            // Check both search and category criteria
            var matchesSearch = (value === '' || itemText.includes(value));
            var matchesCategory = (activeCategoryValue === 'all' || itemCategoryValue === activeCategoryValue);

            if (matchesSearch && matchesCategory) {
                $(this).css('display', '');
                visibleCount++;
            } else {
                $(this).attr('style', 'display: none !important;');
            }
        });

        // Update count in All pill
        $('.category-pill[data-value="all"] .pill-count').text('(' + visibleCount + ')');
    });

    // Initialize count on page load
    $('.category-pill[data-value="all"] .pill-count').text('(' + $('.list-group-item:visible').length + ')');
});

// Loading spinner for data panel
$(document).ready(function(){
    // Show loading spinner when fetching data
    var originalPopulateRightColumn = window.populateRightColumn;
    window.populateRightColumn = function(itemId) {
        const card = document.getElementById('datapanel');
        const cardBody = card.querySelector('.card-body');
        
        // Show loading spinner in the card body
        cardBody.classList.add('d-flex', 'align-items-center', 'justify-content-center');
        cardBody.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        
        originalPopulateRightColumn(itemId);
    };
});


function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}
</script>
