/**
 * Simple Token Field Management
 * Shared between test-token-field.html and newsletter-edit.php
 */

// Global token array
let tokens = [];

// Show validation message using Bootstrap modal if available, otherwise fallback to alert
function showMessage(title, message) {
    // Check if we have Bootstrap modal available
    if (typeof bootstrap !== 'undefined') {
        // Use existing modal or create one
        let modal = document.getElementById('validationModal');
        if (!modal) {
            // Create modal dynamically
            const modalHtml = `
                <div class="modal fade" id="validationModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="validationModalTitle"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="validationModalBody"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            modal = document.getElementById('validationModal');
        }
        
        // Update modal content
        document.getElementById('validationModalTitle').textContent = title;
        document.getElementById('validationModalBody').textContent = message;
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        // Fallback to alert
        alert(message);
    }
}

// Define expandable token types and their options
const expandableTokens = {
    birthday_month: {
        label: 'Birthday Month',
        icon: 'fas fa-birthday-cake',
        options: [
            { value: '1', label: 'January' },
            { value: '2', label: 'February' },
            { value: '3', label: 'March' },
            { value: '4', label: 'April' },
            { value: '5', label: 'May' },
            { value: '6', label: 'June' },
            { value: '7', label: 'July' },
            { value: '8', label: 'August' },
            { value: '9', label: 'September' },
            { value: '10', label: 'October' },
            { value: '11', label: 'November' },
            { value: '12', label: 'December' }
        ]
    },
    plan: {
        label: 'Account Plan',
        icon: 'fas fa-crown',
        options: [
            { value: 'free', label: 'Free Plan' },
            { value: 'plus', label: 'Plus Plan' },
            { value: 'premium', label: 'Premium Plan' },
            { value: 'family', label: 'Family Plan' },
            { value: 'business', label: 'Business Plan' }
        ]
    },
    account_type: {
        label: 'Account Type',
        icon: 'fas fa-user-check',
        options: [
            { value: 'real', label: 'Real Accounts' },
            { value: 'test', label: 'Test Accounts' },
            { value: 'staff', label: 'Staff Accounts' },
            { value: 'demo', label: 'Demo Accounts' }
        ]
    },
    gender: {
        label: 'Gender',
        icon: 'fas fa-venus-mars',
        options: [
            { value: 'male', label: 'Male' },
            { value: 'female', label: 'Female' },
            { value: 'other', label: 'Other' },
            { value: 'prefer_not', label: 'Prefer Not to Say' },
            { value: 'not_specified', label: 'Not Specified' }
        ]
    },
    age_range: {
        label: 'Age Range',
        icon: 'fas fa-user-clock',
        options: [
            { value: '13-17', label: '13-17 (Teen)' },
            { value: '18-24', label: '18-24 (Young Adult)' },
            { value: '25-34', label: '25-34' },
            { value: '35-44', label: '35-44' },
            { value: '45-54', label: '45-54' },
            { value: '55-64', label: '55-64' },
            { value: '65-74', label: '65-74' },
            { value: '75+', label: '75+' }
        ]
    },
    profile_completeness: {
        label: 'Profile Completeness',
        icon: 'fas fa-percentage',
        options: [
            { value: '0-25', label: '0-25% (Minimal)' },
            { value: '26-50', label: '26-50% (Basic)' },
            { value: '51-75', label: '51-75% (Good)' },
            { value: '76-99', label: '76-99% (Nearly Complete)' },
            { value: '100', label: '100% (Complete)' }
        ]
    },
    enrollment_count: {
        label: 'Enrollment Count',
        icon: 'fas fa-list-ol',
        options: [
            { value: '0', label: 'No Enrollments' },
            { value: '1-5', label: '1-5 Enrollments' },
            { value: '6-10', label: '6-10 Enrollments' },
            { value: '11-20', label: '11-20 Enrollments' },
            { value: '21-50', label: '21-50 Enrollments' },
            { value: '51-100', label: '51-100 Enrollments' },
            { value: '101-200', label: '101-200 Enrollments' },
            { value: '200+', label: '200+ Enrollments' }
        ]
    },
    business_category: {
        label: 'Business Category',
        icon: 'fas fa-building',
        options: [
            { value: 'restaurant', label: 'Restaurant' },
            { value: 'fast_food', label: 'Fast Food' },
            { value: 'coffee_shop', label: 'Coffee Shop' },
            { value: 'retail_clothing', label: 'Clothing & Apparel' },
            { value: 'retail_electronics', label: 'Electronics' },
            { value: 'retail_home', label: 'Home & Garden' },
            { value: 'retail_beauty', label: 'Beauty & Cosmetics' },
            { value: 'retail_sporting', label: 'Sporting Goods' },
            { value: 'grocery', label: 'Grocery Store' },
            { value: 'pharmacy', label: 'Pharmacy' },
            { value: 'entertainment', label: 'Entertainment' },
            { value: 'movie_theater', label: 'Movie Theater' },
            { value: 'theme_park', label: 'Theme Park' },
            { value: 'hotel', label: 'Hotel & Lodging' },
            { value: 'gas_station', label: 'Gas Station' },
            { value: 'automotive', label: 'Automotive' },
            { value: 'fitness', label: 'Fitness & Gym' },
            { value: 'spa_salon', label: 'Spa & Salon' },
            { value: 'pet_store', label: 'Pet Store' },
            { value: 'bookstore', label: 'Bookstore' },
            { value: 'toy_store', label: 'Toy Store' }
        ]
    },
    state: {
        label: 'State',
        icon: 'fas fa-map-marker-alt',
        options: [
            { value: 'AL', label: 'Alabama' },
            { value: 'AK', label: 'Alaska' },
            { value: 'AZ', label: 'Arizona' },
            { value: 'AR', label: 'Arkansas' },
            { value: 'CA', label: 'California' },
            { value: 'CO', label: 'Colorado' },
            { value: 'CT', label: 'Connecticut' },
            { value: 'DE', label: 'Delaware' },
            { value: 'DC', label: 'District of Columbia' },
            { value: 'FL', label: 'Florida' },
            { value: 'GA', label: 'Georgia' },
            { value: 'HI', label: 'Hawaii' },
            { value: 'ID', label: 'Idaho' },
            { value: 'IL', label: 'Illinois' },
            { value: 'IN', label: 'Indiana' },
            { value: 'IA', label: 'Iowa' },
            { value: 'KS', label: 'Kansas' },
            { value: 'KY', label: 'Kentucky' },
            { value: 'LA', label: 'Louisiana' },
            { value: 'ME', label: 'Maine' },
            { value: 'MD', label: 'Maryland' },
            { value: 'MA', label: 'Massachusetts' },
            { value: 'MI', label: 'Michigan' },
            { value: 'MN', label: 'Minnesota' },
            { value: 'MS', label: 'Mississippi' },
            { value: 'MO', label: 'Missouri' },
            { value: 'MT', label: 'Montana' },
            { value: 'NE', label: 'Nebraska' },
            { value: 'NV', label: 'Nevada' },
            { value: 'NH', label: 'New Hampshire' },
            { value: 'NJ', label: 'New Jersey' },
            { value: 'NM', label: 'New Mexico' },
            { value: 'NY', label: 'New York' },
            { value: 'NC', label: 'North Carolina' },
            { value: 'ND', label: 'North Dakota' },
            { value: 'OH', label: 'Ohio' },
            { value: 'OK', label: 'Oklahoma' },
            { value: 'OR', label: 'Oregon' },
            { value: 'PA', label: 'Pennsylvania' },
            { value: 'RI', label: 'Rhode Island' },
            { value: 'SC', label: 'South Carolina' },
            { value: 'SD', label: 'South Dakota' },
            { value: 'TN', label: 'Tennessee' },
            { value: 'TX', label: 'Texas' },
            { value: 'UT', label: 'Utah' },
            { value: 'VT', label: 'Vermont' },
            { value: 'VA', label: 'Virginia' },
            { value: 'WA', label: 'Washington' },
            { value: 'WV', label: 'West Virginia' },
            { value: 'WI', label: 'Wisconsin' },
            { value: 'WY', label: 'Wyoming' }
        ]
    }
};

function addToken(type, label, value) {
    // Check for duplicates
    const exists = tokens.some(t => 
        t.type === type && 
        t.label === label && 
        t.value === (value || type)
    );
    
    if (exists) {
        // Flash existing token
        flashToken(label);
        return;
    }
    
    // Special handling for "all" type - clear other tokens
    if (type === 'all') {
        tokens = [];
    } else if (tokens.length > 0 && tokens[0].type === 'all') {
        // Remove "all" if adding specific segments
        tokens = [];
    }
    
    // Add the token
    tokens.push({ 
        type: type, 
        label: label, 
        value: value || type 
    });
    
    renderTokens();
    updateRecipientCount();
}

function showTokenOptions(type) {
    // Check if this is an expandable token type
    if (!expandableTokens[type]) {
        // Not expandable, just add it directly
        addToken(type, type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
        return;
    }
    
    const config = expandableTokens[type];
    
    // Create dropdown menu
    const dropdown = $(`
        <div class="token-dropdown-menu" id="tokenDropdown">
            <div class="dropdown-header">
                <i class="${config.icon} me-2"></i>${config.label}
                <button class="btn-close btn-close-sm float-end" onclick="closeTokenDropdown()"></button>
            </div>
            <div class="dropdown-search">
                <input type="text" class="form-control form-control-sm" placeholder="Search..." 
                       id="tokenDropdownSearch" onkeyup="filterTokenOptions()">
            </div>
            <div class="dropdown-options" id="tokenDropdownOptions">
                ${config.options.map(opt => `
                    <div class="dropdown-option" onclick="selectTokenOption('${type}', '${opt.label}', '${opt.value}')">
                        ${opt.label}
                    </div>
                `).join('')}
            </div>
            <div class="dropdown-footer">
                <button class="btn btn-sm btn-primary" onclick="selectAllTokenOptions('${type}')">
                    Select All
                </button>
            </div>
        </div>
    `);
    
    // Position dropdown near the button that was clicked
    const button = event.target.closest('button');
    const rect = button.getBoundingClientRect();
    
    dropdown.css({
        position: 'fixed',
        top: rect.bottom + 5,
        left: rect.left,
        zIndex: 9999
    });
    
    // Remove any existing dropdown
    $('#tokenDropdown').remove();
    
    // Add to body
    $('body').append(dropdown);
    
    // Focus search field
    $('#tokenDropdownSearch').focus();
    
    // Close on outside click
    $(document).on('click.tokenDropdown', function(e) {
        if (!$(e.target).closest('#tokenDropdown').length && !$(e.target).closest('.segment-btn').length) {
            closeTokenDropdown();
        }
    });
}

function closeTokenDropdown() {
    $('#tokenDropdown').remove();
    $(document).off('click.tokenDropdown');
}

function selectTokenOption(type, label, value) {
    const config = expandableTokens[type];
    const fullLabel = `${config.label}: ${label}`;
    addToken(type, fullLabel, value);
    closeTokenDropdown();
}

function selectAllTokenOptions(type) {
    const config = expandableTokens[type];
    
    // Add all options as separate tokens
    config.options.forEach(opt => {
        const fullLabel = `${config.label}: ${opt.label}`;
        // Check for duplicates before adding
        const exists = tokens.some(t => 
            t.type === type && 
            t.label === fullLabel && 
            t.value === opt.value
        );
        
        if (!exists) {
            tokens.push({ 
                type: type, 
                label: fullLabel, 
                value: opt.value 
            });
        }
    });
    
    renderTokens();
    updateRecipientCount();
    closeTokenDropdown();
}

function filterTokenOptions() {
    const search = $('#tokenDropdownSearch').val().toLowerCase();
    $('#tokenDropdownOptions .dropdown-option').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(search));
    });
}

function addOperatorToken(op) {
    // Don't add operator at start
    if (tokens.length === 0) {
        showMessage('Invalid Operation', 'Add a segment before adding an operator');
        return;
    }
    
    const lastToken = tokens[tokens.length - 1];
    
    // Allow operator after closing parenthesis
    if (lastToken.type === 'operator' && lastToken.value !== ')') {
        showMessage('Invalid Operation', 'Cannot add operator after another operator');
        return;
    }
    
    // Don't add operator right after opening parenthesis
    if (lastToken.type === 'parenthesis' && lastToken.value === '(') {
        showMessage('Invalid Operation', 'Add a segment after opening parenthesis');
        return;
    }
    
    tokens.push({ 
        type: 'operator', 
        label: op,
        value: op 
    });
    
    renderTokens();
}

function addParenthesis(paren) {
    if (paren === '(') {
        // Opening parenthesis can go at start, after an operator, or after another opening parenthesis
        if (tokens.length > 0) {
            const lastToken = tokens[tokens.length - 1];
            // Allow after operator or another opening parenthesis
            if (lastToken.type !== 'operator' && lastToken.value !== '(') {
                showMessage('Invalid Placement', 'Opening parenthesis must be placed at the beginning, after an operator (AND/OR/NOT), or after another opening parenthesis');
                return;
            }
        }
        
        tokens.push({ 
            type: 'parenthesis', 
            label: '(',
            value: '(' 
        });
    } else if (paren === ')') {
        // Closing parenthesis needs a matching opening one
        let openCount = 0;
        let closeCount = 0;
        
        tokens.forEach(token => {
            if (token.type === 'parenthesis') {
                if (token.value === '(') openCount++;
                else if (token.value === ')') closeCount++;
            }
        });
        
        if (openCount <= closeCount) {
            showMessage('Unmatched Parenthesis', 'No matching opening parenthesis');
            return;
        }
        
        // Don't close right after opening or operator
        const lastToken = tokens[tokens.length - 1];
        if (lastToken.type === 'operator' || 
            (lastToken.type === 'parenthesis' && lastToken.value === '(')) {
            showMessage('Invalid Placement', 'Cannot close parenthesis here - add a segment first');
            return;
        }
        
        tokens.push({ 
            type: 'parenthesis', 
            label: ')',
            value: ')' 
        });
    }
    
    renderTokens();
    updateRecipientCount();
}

function removeToken(index) {
    tokens.splice(index, 1);
    
    // Clean up invalid operators
    cleanupOperators();
    
    renderTokens();
    updateRecipientCount();
}

function clearAllTokens() {
    if (tokens.length > 0) {
        tokens = [];
        renderTokens();
        updateRecipientCount();
    }
}

function cleanupOperators() {
    // Remove leading operators
    while (tokens.length > 0 && tokens[0].type === 'operator') {
        tokens.shift();
    }
    
    // Remove trailing operators
    while (tokens.length > 0 && tokens[tokens.length - 1].type === 'operator') {
        tokens.pop();
    }
    
    // Remove consecutive operators
    for (let i = tokens.length - 1; i > 0; i--) {
        if (tokens[i].type === 'operator' && tokens[i-1].type === 'operator') {
            tokens.splice(i, 1);
        }
    }
}

function flashToken(label) {
    const tokenEls = document.querySelectorAll('.recipient-token');
    tokenEls.forEach(el => {
        if (el.textContent.includes(label)) {
            el.style.animation = 'none';
            setTimeout(() => {
                el.style.animation = 'tokenFlash 0.3s ease';
            }, 10);
        }
    });
}

function renderTokens() {
    const container = document.getElementById('recipientTokens');
    
    if (!container) return;
    
    if (tokens.length === 0) {
        container.innerHTML = '<span class="text-muted">Click buttons to add recipient segments</span>';
    } else {
        container.innerHTML = tokens.map((token, i) => {
            let className = 'recipient-token';
            let content = '';
            
            if (token.type === 'operator') {
                className = 'recipient-token operator';
            } else if (token.type === 'parenthesis') {
                className = 'recipient-token parenthesis';
            }
            
            const icon = getTokenIcon(token.type);
            
            // Parentheses don't need remove button, operators and segments do
            if (token.type === 'parenthesis') {
                content = `<span class="${className}">
                    ${token.label}
                    <span class="remove-token" onclick="removeToken(${i})">×</span>
                </span>`;
            } else {
                content = `<span class="${className}">
                    ${icon ? `<i class="${icon} me-1"></i>` : ''}
                    ${token.label}
                    <span class="remove-token" onclick="removeToken(${i})">×</span>
                </span>`;
            }
            
            return content;
        }).join('');
    }
    
    // Update debug display if it exists
    const debugEl = document.getElementById('tokenDebug');
    if (debugEl) {
        debugEl.textContent = JSON.stringify(tokens, null, 2);
    }
    
    // Update count if it exists
    const countEl = document.getElementById('tokenCount');
    if (countEl) {
        const segmentCount = tokens.filter(t => t.type !== 'operator').length;
        countEl.textContent = segmentCount;
    }
}

function getTokenIcon(type) {
    switch(type) {
        case 'all': return 'fas fa-users';
        case 'birthday_month': return 'fas fa-birthday-cake';
        case 'plan': return 'fas fa-crown';
        case 'account_type': return 'fas fa-user-check';
        case 'test': return 'fas fa-vial';
        case 'state': return 'fas fa-map-marker-alt';
        case 'country': return 'fas fa-globe';
        case 'gender': return 'fas fa-venus-mars';
        case 'age_range': return 'fas fa-user-clock';
        case 'profile_completeness': return 'fas fa-percentage';
        case 'enrollment_count': return 'fas fa-list-ol';
        case 'business_category': return 'fas fa-building';
        case 'operator': return '';
        default: return 'fas fa-tag';
    }
}

function updateRecipientCount() {
    // Skip if no recipient count element (test page)
    const recipientCountEl = document.getElementById('recipientCount');
    if (!recipientCountEl) return;
    
    // Skip if no tokens
    if (tokens.length === 0) {
        recipientCountEl.innerHTML = '<span class="text-muted">No recipients selected</span>';
        return;
    }
    
    // Build query from tokens
    const query = buildQueryFromTokens();
    
    // Show loading state
    recipientCountEl.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Calculating...</span>';
    
    // Make AJAX call
    $.ajax({
        url: '/staff/ajax/newsletter-recipients-count.php',
        type: 'POST',
        data: { 
            query: query,
            tokens: JSON.stringify(tokens)
        },
        success: function(response) {
            if (response.success) {
                recipientCountEl.innerHTML = `<strong>${response.count.toLocaleString()}</strong> recipients`;
            } else {
                // Silently fail - just show calculating message
                recipientCountEl.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Calculating...</span>';
            }
        },
        error: function() {
            // Silently fail - just show calculating message instead of error
            recipientCountEl.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Calculating...</span>';
        }
    });
}

function buildQueryFromTokens() {
    // Convert tokens to a query object
    const segments = [];
    let currentGroup = [];
    let currentOperator = 'OR';
    
    tokens.forEach(token => {
        if (token.type === 'operator') {
            if (currentGroup.length > 0) {
                segments.push({
                    operator: currentOperator,
                    conditions: currentGroup
                });
                currentGroup = [];
            }
            currentOperator = token.value;
        } else {
            currentGroup.push({
                type: token.type,
                value: token.value
            });
        }
    });
    
    // Add remaining group
    if (currentGroup.length > 0) {
        segments.push({
            operator: currentOperator,
            conditions: currentGroup
        });
    }
    
    return segments;
}

// Helper functions for newsletter-edit.php compatibility
function addSegment(type, label, value) {
    addToken(type, label, value);
}

function addOperator(op) {
    addOperatorToken(op);
}

function clearTokens() {
    if (tokens.length > 0) {
        tokens = [];
        renderTokens();
        updateRecipientCount();
    }
}

// Initialize on document ready
$(document).ready(function() {
    // Make the recipient builder focusable if it exists
    const builderEl = document.getElementById('recipientBuilder');
    if (builderEl) {
        builderEl.setAttribute('tabindex', '0');
        
        // Keyboard support
        document.addEventListener('keydown', (e) => {
            if (document.activeElement === builderEl || builderEl.contains(document.activeElement)) {
                if (e.key === 'Backspace' && tokens.length > 0) {
                    e.preventDefault();
                    removeToken(tokens.length - 1);
                }
            }
        });
    }
    
    // Initial render
    renderTokens();
    
    // For newsletter-edit, initialize with "All Active Users"
    if (window.location.pathname.includes('newsletter-edit')) {
        if (tokens.length === 0) {
            addToken('all', 'All Active Users');
        }
    }
});