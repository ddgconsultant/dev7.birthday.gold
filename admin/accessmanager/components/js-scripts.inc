
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
    
    // Debug logging
    console.log('populateRightColumn called with itemId:', itemId);
    console.log('Active item:', activeItem);
    console.log('Item name:', itemName);
    
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
        xhr.open('POST', '<?php echo str_replace("'", "\\'", $amscriptendpoint); ?>', true);
        
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
                    buttonContainer.className = 'action-buttons ms-auto me-3';
                    const textarea = document.createElement('textarea');
                    textarea.innerHTML = buttons;
                    buttonContainer.innerHTML = textarea.value;
                    modalHeader.insertBefore(buttonContainer, modalHeader.querySelector('.btn-close'));
                    
                    // Re-initialize tooltips for the new buttons
                    const newTooltips = buttonContainer.querySelectorAll('[data-bs-toggle="tooltip"]');
                    newTooltips.forEach(el => new bootstrap.Tooltip(el));
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
        xhr.open('POST', '<?php echo str_replace("'", "\\'", $amscriptendpoint); ?>', true);

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
                
                // Update header title with the actual item name from the active element
                const newActiveItem = document.querySelector('.list-group-item.active');
                if (newActiveItem && cardHeader) {
                    const actualItemName = newActiveItem.querySelector('.item-name').textContent;
                    const headerTitle = cardHeader.querySelector('h2');
                    if (headerTitle) {
                        headerTitle.textContent = actualItemName;
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
    xhr.open('POST', '<?php echo str_replace("'", "\\'", $amscriptendpoint); ?>', true); // POST request to the logging script

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
// Function to handle item selection
function selectItem(element, itemId) {
    // Set active state
    $('.list-group-item').removeClass('active');
    $(element).addClass('active');
    
    // Then populate the right column
    populateRightColumn(itemId);
}

// Active state management for list items
$(document).ready(function(){
    
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

// Function to open edit modal for a credential
function openEditModal(itemId) {
    // Create modal HTML dynamically
    const modalHtml = `
        <div class="modal fade" id="editCredentialModal" tabindex="-1" aria-labelledby="editCredentialModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCredentialModalLabel">Edit Credential</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveCredentialChanges(${itemId})">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove any existing edit modal
    const existingModal = document.getElementById('editCredentialModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show the modal
    const editModal = new bootstrap.Modal(document.getElementById('editCredentialModal'));
    editModal.show();
    
    // Load the edit form
    loadEditForm(itemId);
}

// Function to load edit form data
function loadEditForm(itemId) {
    const formData = new FormData();
    formData.append('id', itemId);
    formData.append('act', 'geteditdata');
    const csrfToken = '<?php echo addslashes($csrf_token); ?>'; 
    formData.append('_token', csrfToken);
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo str_replace("'", "\\'", $amscriptendpoint); ?>', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            const modalBody = document.querySelector('#editCredentialModal .modal-body');
            if (modalBody) {
                // For now, create a simple edit form
                // In production, this would come from the server response
                modalBody.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Edit functionality is currently being implemented. This feature will allow you to:
                        <ul class="mt-2 mb-0">
                            <li>Update usernames and passwords</li>
                            <li>Modify URLs and descriptions</li>
                            <li>Change categories and notes</li>
                            <li>Update security settings</li>
                        </ul>
                    </div>
                    <p class="text-muted">For security reasons, credential editing requires additional verification and will be available in the next update.</p>
                `;
            }
        }
    };
    
    xhr.send(formData);
}

// Function to save credential changes
function saveCredentialChanges(itemId) {
    // For now, show a message
    alert('Changes will be saved once the edit functionality is fully implemented.');
    
    // Close the modal
    const editModal = bootstrap.Modal.getInstance(document.getElementById('editCredentialModal'));
    if (editModal) {
        editModal.hide();
    }
}

// Function to show access link
function showAccessLink(encodedId, protocol, host) {
    // Create modal HTML for access link
    const modalHtml = `
        <div class="modal fade" id="accessLinkModal" tabindex="-1" aria-labelledby="accessLinkModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="accessLinkModalLabel">Get Access Link</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>API Access Link</strong>
                        </div>
                        <p>To generate an access link for this credential, you need a valid API key.</p>
                        <div class="mb-3">
                            <label for="apiKeyInput" class="form-label">Enter API Key:</label>
                            <input type="text" class="form-control" id="apiKeyInput" placeholder="Your API key">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Access Link Preview:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="accessLinkPreview" readonly value="${protocol}://${host}/admin/accessmanager/accessmanager_get?id=${encodedId}&apikey=">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyAccessLink()">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <small class="text-muted">The link will be complete once you enter your API key above.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="generateAccessLink()">Generate Link</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove any existing modal
    const existingModal = document.getElementById('accessLinkModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show the modal
    const accessModal = new bootstrap.Modal(document.getElementById('accessLinkModal'));
    accessModal.show();
    
    // Update preview when API key is entered
    document.getElementById('apiKeyInput').addEventListener('input', function() {
        const apiKey = this.value;
        const baseUrl = `${protocol}://${host}/admin/accessmanager/accessmanager_get?id=${encodedId}&apikey=`;
        document.getElementById('accessLinkPreview').value = baseUrl + apiKey;
    });
}

// Function to copy access link
function copyAccessLink() {
    const linkInput = document.getElementById('accessLinkPreview');
    linkInput.select();
    document.execCommand('copy');
    
    // Show success message
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i>';
    setTimeout(() => {
        btn.innerHTML = originalHtml;
    }, 2000);
}

// Function to generate and test access link
function generateAccessLink() {
    const apiKey = document.getElementById('apiKeyInput').value;
    if (!apiKey) {
        alert('Please enter an API key');
        return;
    }
    
    const linkInput = document.getElementById('accessLinkPreview');
    const fullLink = linkInput.value;
    
    // Copy to clipboard
    linkInput.select();
    document.execCommand('copy');
    
    // Close modal
    const accessModal = bootstrap.Modal.getInstance(document.getElementById('accessLinkModal'));
    if (accessModal) {
        accessModal.hide();
    }
    
    alert('Access link has been copied to your clipboard!');
}
</script>
