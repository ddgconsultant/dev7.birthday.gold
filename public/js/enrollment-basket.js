/**
 * Enrollment Basket functionality
 * Manages the selection basket for multiple enrollments
 */

// Global basket state - load from sessionStorage if available
let selectionBasket = JSON.parse(sessionStorage.getItem('enrollmentBasket') || '[]');

// Initialize basket on page load
document.addEventListener('DOMContentLoaded', function() {
    // Restore basket UI state
    if (selectionBasket.length > 0) {
        updateBasketUI();
        
        // Update card states for items in basket
        selectionBasket.forEach(item => {
            const card = document.querySelector(`[data-company-id="${item.id}"]`);
            if (card && !card.classList.contains('enrolled')) {
                const btn = card.querySelector('.action-btn');
                btn.className = 'action-btn selected';
                btn.innerHTML = '<i class="bi bi-check-circle"></i> ' + window.userData.labels.tokened;
                btn.onclick = () => removeFromBasket(item.id);
            }
        });
    }
});

// Show error message using Bootstrap alert
function showError(message) {
    // Create a Bootstrap alert at the top of the page
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert after the enrollment header
    const header = document.querySelector('.enrollment-header');
    if (header) {
        const alertDiv = document.createElement('div');
        alertDiv.innerHTML = alertHtml;
        header.parentNode.insertBefore(alertDiv.firstElementChild, header.nextSibling);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = header.nextElementSibling;
            if (alert && alert.classList.contains('alert')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
}

// Show success message
function showSuccess(message, count = 0) {
    const modal = document.getElementById('successModal');
    if (modal) {
        // Update the modal title dynamically
        const modalTitle = modal.querySelector('h4');
        if (modalTitle && count > 0) {
            const pickWord = count === 1 ? window.userData.labels.token : window.userData.labels.token + 's';
            modalTitle.textContent = `${count} ${pickWord} Submitted!`;
        }
        
        const modalInstance = new bootstrap.Modal(modal);
        document.getElementById('successMessage').textContent = message;
        modalInstance.show();
    }
}

// Show loading overlay
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

// Update company card to show enrolled state
function updateCompanyCard(companyId, enrolled) {
    const card = document.querySelector(`[data-company-id="${companyId}"]`);
    if (card) {
        if (enrolled) {
            card.classList.add('enrolled');
            const btn = card.querySelector('.action-btn');
            btn.className = 'action-btn enrolled';
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + window.userData.labels.tokened;
            btn.disabled = true;
        }
    }
}

// Add company to basket
function addToBasket(companyId, companyName, companyLogo) {
    // Check if already in basket
    if (selectionBasket.find(item => item.id === companyId)) {
        showError('This company is already in your selection basket');
        return;
    }
    
    // Check if we have enough allocations
    if (selectionBasket.length >= window.userData.availableAllocations) {
        showError('You don\'t have enough allocations for more selections');
        return;
    }
    
    // Add to basket
    selectionBasket.push({
        id: companyId,
        name: companyName,
        logo: companyLogo
    });
    
    // Save to sessionStorage
    sessionStorage.setItem('enrollmentBasket', JSON.stringify(selectionBasket));
    
    // Update UI
    updateBasketUI();
    
    // Update the card to show it's selected
    const card = document.querySelector(`[data-company-id="${companyId}"]`);
    if (card) {
        const btn = card.querySelector('.action-btn');
        btn.className = 'action-btn selected';
        btn.innerHTML = '<i class="bi bi-check-circle"></i> ' + window.userData.labels.tokened;
        btn.onclick = () => removeFromBasket(companyId);
    }
}

// Remove from basket
function removeFromBasket(companyId) {
    selectionBasket = selectionBasket.filter(item => item.id !== companyId);
    
    // Save to sessionStorage
    sessionStorage.setItem('enrollmentBasket', JSON.stringify(selectionBasket));
    
    updateBasketUI();
    
    // Update the card button back to Select
    const card = document.querySelector(`[data-company-id="${companyId}"]`);
    if (card && !card.classList.contains('enrolled')) {
        const btn = card.querySelector('.action-btn');
        btn.className = 'action-btn enroll';
        btn.innerHTML = '<i class="bi bi-plus-circle"></i> ' + window.userData.labels.token;
        btn.onclick = () => {
            const company = selectionBasket.find(item => item.id === companyId);
            if (!company) {
                // Re-fetch company data from card
                const name = card.querySelector('.company-name').textContent;
                const imgEl = card.querySelector('.company-image img');
                const logo = imgEl ? imgEl.src : '';
                addToBasket(companyId, name, logo);
            }
        };
    }
}

// Clear entire basket
function clearBasket() {
    const companyIds = [...selectionBasket.map(item => item.id)];
    selectionBasket = [];
    
    // Clear from sessionStorage
    sessionStorage.removeItem('enrollmentBasket');
    
    updateBasketUI();
    
    // Reset all buttons
    companyIds.forEach(id => {
        const card = document.querySelector(`[data-company-id="${id}"]`);
        if (card && !card.classList.contains('enrolled')) {
            const btn = card.querySelector('.action-btn');
            btn.className = 'action-btn enroll';
            btn.innerHTML = '<i class="bi bi-plus-circle"></i> ' + window.userData.labels.token;
            btn.onclick = () => {
                const name = card.querySelector('.company-name').textContent;
                const imgEl = card.querySelector('.company-image img');
                const logo = imgEl ? imgEl.src : '';
                addToBasket(id, name, logo);
            };
        }
    });
    
    // Close the basket modal
    const basketModal = bootstrap.Modal.getInstance(document.getElementById('basketModal'));
    if (basketModal) {
        basketModal.hide();
    }
}

// Update basket UI
function updateBasketUI() {
    const counter = document.getElementById('selectionCounter');
    const basketCount = document.getElementById('basketCount');
    const modalBasketCount = document.getElementById('modalBasketCount');
    const basketItems = document.getElementById('basketItems');
    const selectedInfo = document.getElementById('selectedInfo');
    const selectedCount = document.getElementById('selectedCount');
    const confirmButtonCount = document.getElementById('confirmButtonCount');
    const confirmButton = document.getElementById('confirmButton');
    
    // Update the purple header selected count
    if (selectedInfo && selectedCount) {
        if (selectionBasket.length > 0) {
            selectedInfo.style.display = 'flex';
            selectedCount.textContent = selectionBasket.length;
        } else {
            selectedInfo.style.display = 'none';
        }
    }
    
    // Update confirm button count
    if (confirmButtonCount) {
        confirmButtonCount.textContent = selectionBasket.length;
    }
    
    // Update confirm button text based on count
    if (confirmButton && window.userData && window.userData.labels) {
        const btnHtml = `<i class="bi bi-check-circle"></i> Confirm ${selectionBasket.length} ${selectionBasket.length === 1 ? window.userData.labels.token : window.userData.labels.token + 's'}`;
        confirmButton.innerHTML = btnHtml;
    }
    
    if (selectionBasket.length > 0) {
        counter.style.display = 'flex';
        basketCount.textContent = selectionBasket.length;
        modalBasketCount.textContent = selectionBasket.length;
        
        // Build items HTML with more details
        basketItems.innerHTML = selectionBasket.map(item => `
            <div class="basket-item">
                ${item.logo ? `<img src="${item.logo}" alt="${item.name}">` : '<div style="width:50px;height:50px;background:#f0f0f0;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;"><i class="bi bi-building text-muted"></i></div>'}
                <div class="basket-item-info">
                    <div class="basket-item-name">${item.name}</div>
                    <div class="basket-item-category">Birthday Reward</div>
                </div>
                <button class="basket-item-remove" onclick="removeFromBasket(${item.id})">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `).join('');
    } else {
        counter.style.display = 'none';
        // Close modal if open
        const modal = bootstrap.Modal.getInstance(document.getElementById('basketModal'));
        if (modal) {
            modal.hide();
        }
    }
}

// Toggle basket details modal
function toggleBasketDetails() {
    const modalElement = document.getElementById('basketModal');
    let modal = bootstrap.Modal.getInstance(modalElement);
    
    // If modal doesn't exist, create it with proper options
    if (!modal) {
        modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
    }
    
    modal.show();
    
    // Ensure modal backdrop has proper z-index
    setTimeout(() => {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.style.zIndex = '1040';
        }
        // Ensure modal itself is on top
        modalElement.style.zIndex = '1050';
    }, 100);
}


// Confirm enrollments
async function confirmEnrollments() {
    if (selectionBasket.length === 0) return;
    
    // Close basket modal
    const basketModal = bootstrap.Modal.getInstance(document.getElementById('basketModal'));
    if (basketModal) {
        basketModal.hide();
    }
    
    // Show loading
    showLoading(true);
    
    try {
        // For now, we'll process each enrollment individually
        // In the future, this could be a batch API call
        let successCount = 0;
        let errors = [];
        
        for (const company of selectionBasket) {
            try {
                // Use existing enrollment logic
                const response = await fetch('/api/enroll.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        company_id: company.id
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    successCount++;
                    updateCompanyCard(company.id, true);
                } else {
                    errors.push(`${company.name}: ${result.error || 'Failed'}`);
                }
            } catch (error) {
                errors.push(`${company.name}: Network error`);
            }
        }
        
        // Clear basket
        selectionBasket = [];
        sessionStorage.removeItem('enrollmentBasket');
        updateBasketUI();
        
        // Show results
        if (successCount > 0 && errors.length === 0) {
            const pickWord = successCount === 1 ? window.userData.labels.token : window.userData.labels.token + 's';
            showSuccess(`Your ${successCount} ${pickWord} ${successCount === 1 ? 'has' : 'have'} been successfully submitted for enrollment processing.`, successCount);
        } else if (successCount > 0 && errors.length > 0) {
            const pickWord = successCount === 1 ? window.userData.labels.token : window.userData.labels.token + 's';
            showError(`${successCount} ${pickWord} submitted. Errors: ${errors.join(', ')}`);
        } else {
            showError(`Failed to submit. Errors: ${errors.join(', ')}`);
        }
        
        // Update balance display
        if (successCount > 0) {
            document.querySelector('.balance-number').textContent = window.userData.availableAllocations;
        }
        
    } catch (error) {
        console.error('Batch enrollment error:', error);
        showError('Failed to process enrollments');
    } finally {
        showLoading(false);
    }
}

// Redirect to my account page
function redirectToMyAccount() {
    window.location.href = '/myaccount';
}

