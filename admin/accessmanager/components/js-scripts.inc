
<!-- Include Bootstrap Multiselect JS and CSS files -->
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js"></script>  
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css">  


<script>
// This script waits for the DOM to load, then attaches click event listeners 
// to elements with the class 'type-filter-dropdown'.
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.type-filter-dropdown').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent the default anchor tag click behavior (so it doesn't navigate)
            
            // Retrieve the 'data-type' attribute from the clicked element
            var type = this.getAttribute('data-type');
            
            // Set the value of the hidden input field (typeInput) to the selected type
            document.getElementById('typeInput').value = type;
            
            // Submit the form with the ID 'createnewform', which presumably handles filtering
            document.getElementById('createnewform').submit();
            initializeTooltips();
        });
    });
});
</script>


<script>

// Function to populate the right column with content dynamically
// Takes an 'itemId' as a parameter to fetch specific data
function populateRightColumn(itemId) {
    const contentArea = document.getElementById('datapanel'); // The area where fetched content will be displayed

    // Create a FormData object to send data via AJAX
    const formData = new FormData();
    formData.append('id', itemId); // Append the ID of the selected item

    // Retrieve CSRF token to ensure security (this should be set server-side)
    const csrfToken = '<?php echo addslashes($csrf_token); ?>'; 
    formData.append('_token', csrfToken); // Append CSRF token
    formData.append('act', 'getdata'); // Action to be performed on the server (retrieve data)

    // Create an AJAX request (XMLHttpRequest) to fetch data from the server
    const xhr = new XMLHttpRequest();
    xhr.open('POST', <?php echo $amscriptendpoint; ?>, true); // Open a POST request to 'accessmanager.php'

    // When the request state changes, check for successful completion (readyState == 4, status == 200)
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            // Populate the 'datapanel' area with the returned HTML content
            contentArea.innerHTML = xhr.responseText;
            initializeTooltips();
        }
    };

    // Send the FormData object containing the item ID and CSRF token
    xhr.send(formData);
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
// Function to filter list items based on the search input value
$(document).ready(function(){
    $('#searchBar').on('keyup', function() {
        var value = $(this).val().toLowerCase(); // Get the value of the search input and convert it to lowercase

        // Iterate over each list item and check if it matches the search value
        $('.list-group-item').each(function() {
            var itemText = $(this).data('full-context').toLowerCase(); // Get the item's text content in lowercase

            // Show or hide the item based on whether it contains the search value
            if (itemText.includes(value)) {
                $(this).css('display', ''); // Show the item
            } else {
                $(this).attr('style', 'display: none !important;'); // Hide the item
            }
        });
    });
});

// Category filter functionality
$(document).ready(function(){
    // When a category filter dropdown is clicked
    $('.category-filter-dropdown').click(function(e) {
        e.preventDefault();  // Prevent default anchor behavior (so it doesn't navigate)

        var selectedCategoryValue = $(this).data('value');  // Get the selected category value

        // Iterate over each list item and show or hide based on the selected category
        $('.list-group-item').each(function() {
            var itemCategoryValue = $(this).data('category'); // Get the category value of the item

            if (selectedCategoryValue === 'all') {
                $(this).show();  // Show all items if 'All' is selected
            } else {
                // Show items that match the selected category, hide others
                if (itemCategoryValue === selectedCategoryValue) {
                    $(this).css('display', ''); // Show matching items
                } else {
                    $(this).attr('style', 'display: none !important;'); // Hide non-matching items
                }
            }
        });
    });

    // Existing searchBar functionality is already applied here
});

// Show or hide the clear icon based on input in the search bar
$(document).ready(function(){
    // When there's input in the search bar
    $('#searchBar').on('input', function() {
        if ($(this).val().length > 0) {
            $('.clear-icon').show(); // Show the clear icon if the input has content
        } else {
            $('.clear-icon').hide(); // Hide the clear icon if the input is empty
        }
    });

    // When the clear icon is clicked, clear the search bar and update the list
    $('.clear-icon').click(function() {
        $('#searchBar').val('').focus();  // Clear the input and focus on the search bar
        $(this).hide();  // Hide the clear icon
        $('#searchBar').trigger('keyup');  // Trigger the 'keyup' event to refresh the list
    });
});


function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}
</script>
