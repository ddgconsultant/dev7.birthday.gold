/**
 * RecipientTokenField - Professional token field for recipient selection
 * Used in newsletter system for building complex recipient queries
 * 
 * @author Claude
 * @version 1.0
 */

// Professional Token Field Implementation with Query Logic
class RecipientTokenField {
    constructor(container) {
        this.container = document.getElementById(container);
        this.tokensContainer = document.getElementById('recipientTokens');
        this.tokens = [];
        this.selectedIndex = -1;
        this.draggedToken = null;
        this.queryMode = 'simple'; // 'simple' or 'advanced'
        
        // Initialize with default tokens if empty
        if (this.tokens.length === 0) {
            this.addToken({type: 'all', label: 'All Active Users', value: 'all'});
        }
        
        this.init();
    }
    
    init() {
        // Create hidden input for form submission
        this.hiddenInput = document.createElement('input');
        this.hiddenInput.type = 'hidden';
        this.hiddenInput.id = 'recipient_criteria';
        this.hiddenInput.name = 'recipient_criteria';
        this.container.appendChild(this.hiddenInput);
        
        // Set up event listeners
        this.setupEventListeners();
        
        // Initial render
        this.render();
        this.updateCount();
    }
    
    setupEventListeners() {
        // Click on container to focus
        this.container.addEventListener('click', (e) => {
            if (e.target === this.container || e.target === this.tokensContainer) {
                this.container.classList.add('focused');
            }
        });
        
        // Click outside to unfocus
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.container.classList.remove('focused');
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.container.classList.contains('focused')) return;
            
            switch(e.key) {
                case 'Backspace':
                    if (this.tokens.length > 0 && this.selectedIndex === -1) {
                        e.preventDefault();
                        this.removeToken(this.tokens.length - 1);
                    }
                    break;
                case 'Delete':
                    if (this.selectedIndex >= 0) {
                        e.preventDefault();
                        this.removeToken(this.selectedIndex);
                    }
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    this.selectPrevious();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    this.selectNext();
                    break;
            }
        });
        
        // Set up drag and drop (disabled in simple mode)
        if (this.queryMode === 'advanced') {
            this.setupDragAndDrop();
        }
    }
    
    setupDragAndDrop() {
        this.tokensContainer.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('recipient-token')) {
                this.draggedToken = e.target;
                e.target.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            }
        });
        
        this.tokensContainer.addEventListener('dragend', (e) => {
            if (e.target.classList.contains('recipient-token')) {
                e.target.classList.remove('dragging');
                this.draggedToken = null;
            }
        });
        
        this.tokensContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            const afterElement = this.getDragAfterElement(this.tokensContainer, e.clientX);
            if (afterElement == null) {
                this.tokensContainer.appendChild(this.draggedToken);
            } else {
                this.tokensContainer.insertBefore(this.draggedToken, afterElement);
            }
        });
    }
    
    getDragAfterElement(container, x) {
        const draggableElements = [...container.querySelectorAll('.recipient-token:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = x - box.left - box.width / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    validateTokenAddition(tokenData) {
        // Rule 1: "All Users" cannot be combined with other segments
        const hasAllUsers = this.tokens.some(t => t.type === 'all');
        const isAddingAllUsers = tokenData.type === 'all';
        
        if (hasAllUsers && tokenData.type !== 'operator' && !isAddingAllUsers) {
            this.showWarning('Cannot combine "All Users" with other segments. Remove "All Users" first.');
            return false;
        }
        
        if (isAddingAllUsers && this.tokens.length > 0 && 
            this.tokens.some(t => t.type !== 'operator' && t.type !== 'all')) {
            this.showWarning('Cannot add "All Users" when other segments exist. Clear selection first.');
            return false;
        }
        
        // Rule 2: Cannot start with an operator
        if (this.tokens.length === 0 && tokenData.type === 'operator') {
            this.showWarning('Cannot start with an operator. Add a segment first.');
            return false;
        }
        
        // Rule 3: Cannot have two operators in a row
        const lastToken = this.tokens[this.tokens.length - 1];
        if (lastToken && lastToken.type === 'operator' && tokenData.type === 'operator') {
            this.showWarning('Cannot have two operators in a row.');
            return false;
        }
        
        // Rule 4: Cannot end with an operator (when adding non-operator)
        if (tokenData.type !== 'operator' && lastToken && lastToken.type === 'operator') {
            // This is OK - adding segment after operator
            return true;
        }
        
        // Rule 5: Need operator between segments
        if (tokenData.type !== 'operator' && lastToken && lastToken.type !== 'operator') {
            // Auto-insert OR operator
            this.tokens.push({type: 'operator', label: 'OR', value: 'OR'});
        }
        
        return true;
    }
    
    showWarning(message) {
        // Create a temporary warning message
        const warning = document.createElement('div');
        warning.className = 'alert alert-warning alert-sm position-absolute';
        warning.style.cssText = 'top: -50px; left: 0; right: 0; z-index: 1000; font-size: 13px; padding: 5px 10px;';
        warning.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
        
        this.container.style.position = 'relative';
        this.container.appendChild(warning);
        
        setTimeout(() => {
            warning.remove();
        }, 3000);
    }
    
    addToken(tokenData) {
        // Validate the token addition
        if (!this.validateTokenAddition(tokenData)) {
            return;
        }
        
        // Check for duplicates (except operators)
        if (tokenData.type !== 'operator') {
            const exists = this.tokens.some(t => 
                t.type === tokenData.type && 
                t.value === tokenData.value
            );
            if (exists) {
                // Flash the existing token
                this.flashToken(tokenData);
                return;
            }
        }
        
        // Special handling for "All Users"
        if (tokenData.type === 'all') {
            this.tokens = [tokenData]; // Replace everything with just "All Users"
        } else if (this.tokens.length === 1 && this.tokens[0].type === 'all') {
            // Replacing "All Users" with a specific segment
            this.tokens = [tokenData];
        } else {
            this.tokens.push(tokenData);
        }
        
        this.render();
        this.updateCount();
    }
    
    removeToken(index) {
        const removed = this.tokens.splice(index, 1)[0];
        
        // Clean up any orphaned operators
        this.cleanupOperators();
        
        // Animate removal
        const tokenElements = this.tokensContainer.querySelectorAll('.recipient-token');
        if (tokenElements[index]) {
            tokenElements[index].style.animation = 'tokenFadeIn 0.2s ease reverse';
            setTimeout(() => {
                this.render();
                this.updateCount();
            }, 200);
        } else {
            this.render();
            this.updateCount();
        }
    }
    
    cleanupOperators() {
        // Remove leading operators
        while (this.tokens.length > 0 && this.tokens[0].type === 'operator') {
            this.tokens.shift();
        }
        
        // Remove trailing operators
        while (this.tokens.length > 0 && this.tokens[this.tokens.length - 1].type === 'operator') {
            this.tokens.pop();
        }
        
        // Remove consecutive operators
        for (let i = 0; i < this.tokens.length - 1; i++) {
            if (this.tokens[i].type === 'operator' && this.tokens[i + 1].type === 'operator') {
                this.tokens.splice(i + 1, 1);
                i--; // Check this position again
            }
        }
    }
    
    flashToken(tokenData) {
        const tokens = this.tokensContainer.querySelectorAll('.recipient-token');
        tokens.forEach(token => {
            const type = token.dataset.type;
            const value = token.dataset.value;
            if (type === tokenData.type && value === tokenData.value) {
                token.style.animation = 'none';
                setTimeout(() => {
                    token.style.animation = 'tokenFlash 0.3s ease';
                }, 10);
            }
        });
    }
    
    selectPrevious() {
        if (this.tokens.length === 0) return;
        
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
        } else {
            this.selectedIndex = this.tokens.length - 1;
        }
        this.highlightSelected();
    }
    
    selectNext() {
        if (this.tokens.length === 0) return;
        
        if (this.selectedIndex < this.tokens.length - 1) {
            this.selectedIndex++;
        } else {
            this.selectedIndex = 0;
        }
        this.highlightSelected();
    }
    
    highlightSelected() {
        const tokens = this.tokensContainer.querySelectorAll('.recipient-token');
        tokens.forEach((token, index) => {
            token.classList.toggle('selected', index === this.selectedIndex);
        });
    }
    
    render() {
        let html = '';
        this.tokens.forEach((token, index) => {
            const className = token.type === 'operator' ? 'recipient-token operator' : 'recipient-token';
            const draggable = this.queryMode === 'advanced' ? 'true' : 'false';
            html += `
                <span class="${className}" 
                      data-type="${token.type}" 
                      data-value="${token.value}" 
                      data-index="${index}"
                      draggable="${draggable}">
                    ${token.label}
                    <span class="remove-token" onclick="recipientField.removeToken(${index})">×</span>
                </span>
            `;
        });
        
        if (this.tokens.length === 0) {
            html = '<span class="text-muted">Click buttons below to add recipient segments</span>';
        }
        
        this.tokensContainer.innerHTML = html;
        
        // Update hidden input
        this.hiddenInput.value = JSON.stringify(this.tokens);
        
        // Update button states
        this.updateButtonStates();
    }
    
    updateButtonStates() {
        // Get operator buttons
        const operatorButtons = document.querySelectorAll('button[onclick*="addOperator"]');
        const lastToken = this.tokens[this.tokens.length - 1];
        const hasAllUsers = this.tokens.some(t => t.type === 'all');
        
        // Disable/enable operator buttons based on rules
        operatorButtons.forEach(btn => {
            if (this.tokens.length === 0 || (lastToken && lastToken.type === 'operator')) {
                btn.disabled = true;
                btn.classList.add('disabled');
                btn.title = 'Add a segment first';
            } else {
                btn.disabled = false;
                btn.classList.remove('disabled');
                btn.title = '';
            }
        });
        
        // Disable segment buttons if "All Users" is selected
        const segmentButtons = document.querySelectorAll('.segment-btn:not([onclick*="addSegment(\'all\'"])');
        segmentButtons.forEach(btn => {
            if (hasAllUsers) {
                btn.disabled = true;
                btn.classList.add('disabled');
                btn.title = 'Remove "All Users" to add other segments';
            } else {
                btn.disabled = false;
                btn.classList.remove('disabled');
                btn.title = '';
            }
        });
        
        // Update "All Users" button
        const allUsersBtn = document.querySelector('button[onclick*="addSegment(\'all\'"]');
        if (allUsersBtn) {
            if (this.tokens.length > 0 && !hasAllUsers) {
                allUsersBtn.disabled = true;
                allUsersBtn.classList.add('disabled');
                allUsersBtn.title = 'Clear selection to use "All Users"';
            } else if (hasAllUsers) {
                allUsersBtn.disabled = true;
                allUsersBtn.classList.add('disabled');
                allUsersBtn.title = 'Already selected';
            } else {
                allUsersBtn.disabled = false;
                allUsersBtn.classList.remove('disabled');
                allUsersBtn.title = '';
            }
        }
    }
    
    updateCount() {
        // Clean up operators first
        this.cleanupOperators();
        
        if (this.tokens.length === 0) {
            this.updateDisplay(0, 'No recipients selected');
            return;
        }
        
        // Generate human-readable description
        const description = this.getQueryDescription();
        
        // Check if we can calculate count (simple queries only for now)
        const hasOperators = this.tokens.some(t => t.type === 'operator');
        
        if (!hasOperators && this.tokens.length === 1) {
            const token = this.tokens[0];
            const criteria = this.buildCriteria(token);
            
            fetch('/staff/ajax/newsletter-recipient-count.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'criteria=' + encodeURIComponent(JSON.stringify(criteria))
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateDisplay(data.count, description);
                } else {
                    this.updateDisplay('?', 'Could not calculate');
                }
            })
            .catch(error => {
                console.error('Error fetching count:', error);
                this.updateDisplay('?', 'Error loading count');
            });
        } else if (hasOperators) {
            // For complex queries, show the description
            this.updateDisplay('Calculating...', description);
            // TODO: Implement complex query calculation
            setTimeout(() => {
                this.updateDisplay('~', description + '<br><small class="text-warning">Complex query - exact count pending</small>');
            }, 500);
        }
    }
    
    getQueryDescription() {
        if (this.tokens.length === 0) {
            return 'No recipients selected';
        }
        
        let parts = [];
        let currentGroup = [];
        
        this.tokens.forEach((token, i) => {
            if (token.type === 'operator') {
                if (token.value === 'NOT') {
                    parts.push('<span class="text-danger">EXCLUDING</span>');
                } else {
                    parts.push('<span class="text-primary font-weight-bold">' + token.value + '</span>');
                }
            } else {
                parts.push(token.label);
            }
        });
        
        return parts.join(' ');
    }
    
    buildCriteria(token) {
        const criteria = {
            type: token.type,
            value: token.value
        };
        
        if (token.type === 'birthday_month') {
            criteria.month = token.value;
        } else if (token.type === 'plan') {
            criteria.plans = [token.value];
        } else if (token.type === 'enrollment') {
            criteria.range = token.value;
        }
        
        return criteria;
    }
    
    updateDisplay(count, details) {
        const countEl = document.getElementById('recipient_count');
        const detailsEl = document.getElementById('recipient_details');
        
        if (countEl) {
            countEl.textContent = typeof count === 'number' ? count.toLocaleString() : count;
        }
        if (detailsEl) {
            detailsEl.innerHTML = details || '';
        }
    }
    
    clear() {
        this.tokens = [];
        this.render();
        this.updateCount();
    }
}

// Global instance
let recipientField;

// Helper functions for buttons
function addSegment(type, label, value) {
    if (!recipientField) {
        console.error('RecipientTokenField not initialized yet');
        return;
    }
    recipientField.addToken({
        type: type,
        label: label,
        value: value || type
    });
}

function addOperator(op) {
    if (!recipientField) {
        console.error('RecipientTokenField not initialized yet');
        return;
    }
    recipientField.addToken({
        type: 'operator',
        label: op,
        value: op
    });
}

function showMonthSelector() {
    const selector = document.getElementById('monthSelector');
    if (selector) {
        selector.style.display = 'block';
        selector.focus();
        const planSelector = document.getElementById('planSelector');
        const enrollmentSelector = document.getElementById('enrollmentSelector');
        if (planSelector) planSelector.style.display = 'none';
        if (enrollmentSelector) enrollmentSelector.style.display = 'none';
    }
}

function showPlanSelector() {
    const selector = document.getElementById('planSelector');
    if (selector) {
        selector.style.display = 'block';
        selector.focus();
        const monthSelector = document.getElementById('monthSelector');
        const enrollmentSelector = document.getElementById('enrollmentSelector');
        if (monthSelector) monthSelector.style.display = 'none';
        if (enrollmentSelector) enrollmentSelector.style.display = 'none';
    }
}

function showEnrollmentSelector() {
    const selector = document.getElementById('enrollmentSelector');
    if (selector) {
        selector.style.display = 'block';
        selector.focus();
        const monthSelector = document.getElementById('monthSelector');
        const planSelector = document.getElementById('planSelector');
        if (monthSelector) monthSelector.style.display = 'none';
        if (planSelector) planSelector.style.display = 'none';
    }
}

function clearTokens() {
    if (!recipientField) {
        console.error('RecipientTokenField not initialized yet');
        return;
    }
    if (confirm('Are you sure you want to clear all recipient selections?')) {
        recipientField.clear();
    }
}

// Initialize on document ready if jQuery is available
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Check if the recipient builder exists on this page
        if (document.getElementById('recipientBuilder')) {
            // Initialize the token field
            recipientField = new RecipientTokenField('recipientBuilder');
            
            // Handle month selector
            $('#monthSelector').on('change', function() {
                const val = $(this).val();
                if (val) {
                    const monthNames = ["", "January", "February", "March", "April", "May", "June", 
                                     "July", "August", "September", "October", "November", "December"];
                    recipientField.addToken({
                        type: 'birthday_month',
                        label: monthNames[parseInt(val)] + ' Birthdays',
                        value: val
                    });
                    $(this).val('').hide();
                }
            });
            
            // Handle plan selector
            $('#planSelector').on('change', function() {
                const val = $(this).val();
                if (val) {
                    recipientField.addToken({
                        type: 'plan',
                        label: val.charAt(0).toUpperCase() + val.slice(1) + ' Plan',
                        value: val
                    });
                    $(this).val('').hide();
                }
            });
            
            // Handle enrollment selector
            $('#enrollmentSelector').on('change', function() {
                const val = $(this).val();
                if (val) {
                    const label = $(this).find('option:selected').text();
                    recipientField.addToken({
                        type: 'enrollment',
                        label: label,
                        value: val
                    });
                    $(this).val('').hide();
                }
            });
            
            // Hide dropdowns when clicking elsewhere
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.segment-buttons').length) {
                    $('#monthSelector, #planSelector, #enrollmentSelector').hide();
                }
            });
        }
    });
} else {
    // Vanilla JavaScript initialization
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('recipientBuilder')) {
            recipientField = new RecipientTokenField('recipientBuilder');
        }
    });
}