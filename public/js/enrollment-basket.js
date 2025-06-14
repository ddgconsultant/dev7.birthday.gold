/**
 * Enrollment Basket functionality
 * Manages the selection basket for multiple enrollments
 */

// Global basket state
let selectionBasket = [];

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
    
    // Update UI
    updateBasketUI();
    
    // Update the card to show it's selected
    const card = document.querySelector(`[data-company-id="${companyId}"]`);
    if (card) {
        const btn = card.querySelector('.action-btn');
        btn.className = 'action-btn selected';
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Selected';
        btn.onclick = () => removeFromBasket(companyId);
    }
}

// Remove from basket
function removeFromBasket(companyId) {
    selectionBasket = selectionBasket.filter(item => item.id !== companyId);
    updateBasketUI();
    
    // Update the card button back to Select
    const card = document.querySelector(`[data-company-id="${companyId}"]`);
    if (card && !card.classList.contains('enrolled')) {
        const btn = card.querySelector('.action-btn');
        btn.className = 'action-btn enroll';
        btn.innerHTML = '<i class="bi bi-plus-circle"></i> Select';
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
    if (confirm('Clear all selections?')) {
        const companyIds = [...selectionBasket.map(item => item.id)];
        selectionBasket = [];
        updateBasketUI();
        
        // Reset all buttons
        companyIds.forEach(id => removeFromBasket(id));
    }
}

// Update basket UI
function updateBasketUI() {
    const counter = document.getElementById('selectionCounter');
    const basketCount = document.getElementById('basketCount');
    const modalBasketCount = document.getElementById('modalBasketCount');
    const basketItems = document.getElementById('basketItems');
    
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
    
    if (!confirm(`Enroll in ${selectionBasket.length} companies? This will use ${selectionBasket.length} of your ${window.userData.availableAllocations} available allocations.`)) {
        return;
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
        updateBasketUI();
        
        // Show results
        if (successCount > 0 && errors.length === 0) {
            showSuccess(`Successfully enrolled in ${successCount} companies!`);
        } else if (successCount > 0 && errors.length > 0) {
            showError(`Enrolled in ${successCount} companies. Errors: ${errors.join(', ')}`);
        } else {
            showError(`Failed to enroll. Errors: ${errors.join(', ')}`);
        }
        
        // Update balance
        if (successCount > 0) {
            // Reload page to refresh balance and UI
            setTimeout(() => location.reload(), 2000);
        }
        
    } catch (error) {
        console.error('Batch enrollment error:', error);
        showError('Failed to process enrollments');
    } finally {
        showLoading(false);
    }
}

// Confirm enrollments with better UX
async function confirmEnrollments() {
    if (selectionBasket.length === 0) return;
    
    if (!confirm(`Enroll in ${selectionBasket.length} companies? This will use ${selectionBasket.length} of your ${window.userData.availableAllocations} available allocations.`)) {
        return;
    }
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('basketModal'));
    if (modal) {
        modal.hide();
    }
    
    // Show loading
    showLoading(true);
    
    try {
        let successCount = 0;
        let errors = [];
        
        for (const company of selectionBasket) {
            try {
                // Call the enrollment API
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
                    
                    // Update balance from server response
                    if (result.new_balance) {
                        window.userData.availableAllocations = result.new_balance.available_allocations;
                    }
                } else {
                    errors.push(`${company.name}: ${result.error || 'Failed'}`);
                }
            } catch (error) {
                console.error('Enrollment error for', company.name, error);
                errors.push(`${company.name}: Network error`);
            }
        }
        
        // Clear basket
        selectionBasket = [];
        updateBasketUI();
        
        // Show results
        if (successCount > 0 && errors.length === 0) {
            showSuccess(`Successfully enrolled in ${successCount} companies!`);
            // Update balance display
            document.querySelector('.balance-number').textContent = window.userData.availableAllocations;
        } else if (successCount > 0 && errors.length > 0) {
            showError(`Enrolled in ${successCount} companies. Errors: ${errors.join(', ')}`);
        } else {
            showError(`Failed to enroll. Errors: ${errors.join(', ')}`);
        }
        
        // Reload page after a delay to refresh everything
        if (successCount > 0) {
            setTimeout(() => location.reload(), 2000);
        }
        
    } catch (error) {
        console.error('Batch enrollment error:', error);
        showError('Failed to process enrollments');
    } finally {
        showLoading(false);
    }
}