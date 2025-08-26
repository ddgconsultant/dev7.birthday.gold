/**
 * Enrollment Basket functionality
 * Manages the selection basket for multiple enrollments
 */

// Global basket state - load from sessionStorage if available
let selectionBasket = JSON.parse(sessionStorage.getItem('enrollmentBasket') || '[]');

// Initialize basket on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load tracked basket
    const trackedBasket = JSON.parse(sessionStorage.getItem('trackedBasket') || '[]');
    
    // Restore basket UI state if we have any items
    if (selectionBasket.length > 0 || trackedBasket.length > 0) {
        updateBasketUI();
        
        // Update card states for picked items
        selectionBasket.forEach(item => {
            const card = document.querySelector(`[data-company-id="${item.id}"]`);
            if (card && !card.classList.contains('enrolled')) {
                const actionDiv = card.querySelector('.company-action');
                if (actionDiv) {
                    // Replace button group with single selected button
                    actionDiv.innerHTML = `
                        <button class="action-btn selected" onclick="removeFromBasket(${item.id})">
                            <i class="bi bi-check-circle"></i> ${window.userData.labels.tokened}
                        </button>
                    `;
                }
            }
        });
        
        // Update card states for tracked items
        trackedBasket.forEach(item => {
            const card = document.querySelector(`[data-company-id="${item.id}"]`);
            if (card && !card.classList.contains('enrolled')) {
                const actionDiv = card.querySelector('.company-action');
                if (actionDiv) {
                    // Replace button group with single tracked button
                    actionDiv.innerHTML = `
                        <button class="action-btn tracked" onclick="removeFromTrackedBasket(${item.id})">
                            <i class="bi bi-bookmark-check-fill"></i> Tracked
                        </button>
                    `;
                }
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
        const actionDiv = card.querySelector('.company-action');
        if (actionDiv) {
            // Replace the entire button group with a single selected button (no split)
            actionDiv.innerHTML = `
                <button class="action-btn selected" onclick="removeFromBasket(${companyId})">
                    <i class="bi bi-check-circle"></i> ${window.userData.labels.tokened}
                </button>
            `;
        }
    }
}

// Remove from basket
function removeFromBasket(companyId) {
    selectionBasket = selectionBasket.filter(item => item.id !== companyId);
    
    // Save to sessionStorage
    sessionStorage.setItem('enrollmentBasket', JSON.stringify(selectionBasket));
    
    updateBasketUI();
    
    // Update the card button back to Select with split button
    const card = document.querySelector(`[data-company-id="${companyId}"]`);
    if (card && !card.classList.contains('enrolled')) {
        const actionDiv = card.querySelector('.company-action');
        if (actionDiv) {
            // Re-fetch company data from card
            const name = card.querySelector('.company-name').textContent;
            const imgEl = card.querySelector('.company-image img');
            const logo = imgEl ? imgEl.src : '';
            
            // Restore the split button group with proper styling
            actionDiv.innerHTML = `
                <div class="btn-group w-100" role="group">
                    <button class="action-btn enroll split-main" 
                            style="border-top-right-radius: 0; border-bottom-right-radius: 0; flex: 1; padding-left: 1.5rem;"
                            onclick="addToBasket(${companyId}, '${name.replace(/'/g, "\\'")}', '${logo}')">
                        <span class="pick-text">(+) ${window.userData.labels.token}</span>
                    </button>
                    <button type="button" class="action-btn enroll split-dropdown dropdown-toggle dropdown-toggle-split" 
                            style="border-top-left-radius: 0; border-bottom-left-radius: 0; width: auto; border-left: 1px solid rgba(255,255,255,0.2);"
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="trackAsOwned(${companyId}, '${name.replace(/'/g, "\\'")}', this); return false;">
                            <i class="bi bi-bookmark-check me-2"></i> I Already Have This
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-item-text text-muted small"><em>Track without using a ${window.userData.labels.token.toLowerCase()}</em></span></li>
                    </ul>
                </div>
            `;
            
            // Re-initialize Bootstrap dropdowns for this new element
            const dropdownToggle = actionDiv.querySelector('[data-bs-toggle="dropdown"]');
            if (dropdownToggle) {
                new bootstrap.Dropdown(dropdownToggle);
            }
        }
    }
}

// Remove tracked item from basket (now calls removeFromTrackedBasket)
function removeTrackedItem(companyId) {
    // This function now just calls the unified removeFromTrackedBasket
    if (typeof removeFromTrackedBasket === 'function') {
        removeFromTrackedBasket(companyId);
    } else {
        // Fallback if removeFromTrackedBasket is not available
        let trackedBasket = JSON.parse(sessionStorage.getItem('trackedBasket') || '[]');
        trackedBasket = trackedBasket.filter(item => item.id !== companyId);
        sessionStorage.setItem('trackedBasket', JSON.stringify(trackedBasket));
        updateBasketUI();
    }
}

// Clear entire basket (both picked and tracked)
function clearBasket() {
    // Get all company IDs from both baskets
    const pickedIds = [...selectionBasket.map(item => item.id)];
    const trackedBasket = JSON.parse(sessionStorage.getItem('trackedBasket') || '[]');
    const trackedIds = [...trackedBasket.map(item => item.id)];
    
    // Clear both baskets
    selectionBasket = [];
    sessionStorage.removeItem('enrollmentBasket');
    sessionStorage.removeItem('trackedBasket');
    
    updateBasketUI();
    
    // Reset all picked buttons
    pickedIds.forEach(id => {
        const card = document.querySelector(`[data-company-id="${id}"]`);
        if (card && !card.classList.contains('enrolled')) {
            const actionDiv = card.querySelector('.company-action');
            if (actionDiv) {
                const name = card.querySelector('.company-name').textContent;
                const imgEl = card.querySelector('.company-image img');
                const logo = imgEl ? imgEl.src : '';
                
                // Restore the split button group with proper styling
                actionDiv.innerHTML = `
                    <div class="btn-group w-100" role="group">
                        <button class="action-btn enroll split-main" 
                                style="border-top-right-radius: 0; border-bottom-right-radius: 0; flex: 1; padding-left: 1.5rem;"
                                onclick="addToBasket(${id}, '${name.replace(/'/g, "\\'")}', '${logo}')">
                            <span class="pick-text">(+) ${window.userData.labels.token}</span>
                        </button>
                        <button type="button" class="action-btn enroll split-dropdown dropdown-toggle dropdown-toggle-split" 
                                style="border-top-left-radius: 0; border-bottom-left-radius: 0; width: auto; border-left: 1px solid rgba(255,255,255,0.2);"
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="trackAsOwned(${id}, '${name.replace(/'/g, "\\'")}', this); return false;">
                                <i class="bi bi-bookmark-check me-2"></i> I Already Have This
                            </a></li>
                        </ul>
                    </div>
                `;
                
                // Re-initialize Bootstrap dropdowns
                const dropdownToggle = actionDiv.querySelector('[data-bs-toggle="dropdown"]');
                if (dropdownToggle) {
                    new bootstrap.Dropdown(dropdownToggle);
                }
            }
        }
    });
    
    // Reset all tracked buttons
    trackedIds.forEach(id => {
        const card = document.querySelector(`[data-company-id="${id}"]`);
        if (card && !card.classList.contains('enrolled')) {
            const actionDiv = card.querySelector('.company-action');
            if (actionDiv) {
                const name = card.querySelector('.company-name').textContent;
                const imgEl = card.querySelector('.company-image img');
                const logo = imgEl ? imgEl.src : '';
                
                // Restore the split button group with proper styling
                actionDiv.innerHTML = `
                    <div class="btn-group w-100" role="group">
                        <button class="action-btn enroll split-main" 
                                style="border-top-right-radius: 0; border-bottom-right-radius: 0; flex: 1; padding-left: 1.5rem;"
                                onclick="addToBasket(${id}, '${name.replace(/'/g, "\\'")}', '${logo}')">
                            <span class="pick-text">(+) ${window.userData.labels.token}</span>
                        </button>
                        <button type="button" class="action-btn enroll split-dropdown dropdown-toggle dropdown-toggle-split" 
                                style="border-top-left-radius: 0; border-bottom-left-radius: 0; width: auto; border-left: 1px solid rgba(255,255,255,0.2);"
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="trackAsOwned(${id}, '${name.replace(/'/g, "\\'")}', this); return false;">
                                <i class="bi bi-bookmark-check me-2"></i> I Already Have This
                            </a></li>
                        </ul>
                    </div>
                `;
                
                // Re-initialize Bootstrap dropdowns
                const dropdownToggle = actionDiv.querySelector('[data-bs-toggle="dropdown"]');
                if (dropdownToggle) {
                    new bootstrap.Dropdown(dropdownToggle);
                }
            }
        }
    });
    
    // Close the basket modal
    const basketModal = bootstrap.Modal.getInstance(document.getElementById('basketModal'));
    if (basketModal) {
        basketModal.hide();
    }
}

// Track active popover instance globally
let activePopover = null;

// Update basket UI
function updateBasketUI() {
    // Call the new updateBalanceDisplay function if it exists
    if (typeof updateBalanceDisplay === 'function') {
        updateBalanceDisplay();
    }
    const counter = document.getElementById('selectionCounter');
    const basketCount = document.getElementById('basketCount');
    const modalBasketCount = document.getElementById('modalBasketCount');
    const trackedBasket = JSON.parse(sessionStorage.getItem('trackedBasket') || '[]');
    const trackedCount = trackedBasket.length;
    const pickedCount = selectionBasket.length;
    const totalCount = pickedCount + trackedCount;
    const basketItems = document.getElementById('basketItems');
    const selectedInfo = document.getElementById('selectedInfo');
    const selectedCount = document.getElementById('selectedCount');
    
    // Console logging for both baskets
    console.log('UpdateBasketUI - Picked basket has ' + pickedCount + ' items');
    console.log('UpdateBasketUI - Tracked basket has ' + trackedCount + ' items');
    
    // Update the header with combined picked and tracked count
    if (selectedInfo && selectedCount) {
        if (totalCount > 0) {
            selectedInfo.style.display = 'flex';
            
            const selectedLabel = document.getElementById('selectedLabel');
            
            // Show breakdown if we have both picked and tracked
            if (pickedCount > 0 && trackedCount > 0) {
                // Format: "2 Picked / 1 Tracked"
                selectedCount.textContent = pickedCount + ' / ' + trackedCount;
                if (selectedLabel) {
                    selectedLabel.textContent = `${window.userData.labels.tokened} / Tracked`;
                }
            } else if (pickedCount > 0) {
                // Only picked items
                selectedCount.textContent = pickedCount;
                if (selectedLabel) {
                    selectedLabel.textContent = window.userData.labels.tokened;
                }
            } else if (trackedCount > 0) {
                // Only tracked items
                selectedCount.textContent = trackedCount;
                if (selectedLabel) {
                    selectedLabel.textContent = 'Tracked';
                }
            }
        } else {
            selectedInfo.style.display = 'none';
        }
    }
    
    // Update confirm button count (removed - no longer needed)
    // Button now just says "Confirm" without count
    
    if (totalCount > 0) {
        counter.style.display = 'flex';
        basketCount.textContent = totalCount;
        modalBasketCount.textContent = totalCount;
        
        // Show first-time picker help popover
        // Shows for new users (no enrollments) or when forced via URL parameter
        if (selectionBasket.length === 1) {
            const shouldShow = window.userData.forceShowHelp || 
                              (!window.userData.hasEnrollments && !sessionStorage.getItem('pickerHelpShown'));
            if (shouldShow) {
                showFirstPickerHelp();
            }
        }
        
        // Get tracked items from trackedBasket (not trackedItems)
        const trackedBasket = JSON.parse(sessionStorage.getItem('trackedBasket') || '[]');
        
        // Build HTML for picked items
        let itemsHTML = '';
        
        // Add picked items with green badge
        if (selectionBasket.length > 0) {
            itemsHTML += '<div class="mb-3"><h6 class="text-success"><i class="bi bi-check-circle"></i> Using Picks</h6>';
            itemsHTML += selectionBasket.map(item => `
                <div class="basket-item">
                    ${item.logo ? `<img src="${item.logo}" alt="${item.name}">` : '<div style="width:50px;height:50px;background:#f0f0f0;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;"><i class="bi bi-building text-muted"></i></div>'}
                    <div class="basket-item-info">
                        <div class="basket-item-name">${item.name}</div>
                        <div class="basket-item-category"><span class="badge bg-success">Pick</span></div>
                    </div>
                    <button class="basket-item-remove" onclick="removeFromBasket(${item.id})">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            `).join('');
            itemsHTML += '</div>';
        }
        
        // Add tracked items with gold badge
        if (trackedBasket.length > 0) {
            itemsHTML += '<div class="mb-3"><h6 class="text-warning"><i class="bi bi-bookmark-check-fill"></i> Already Have (Tracking Only)</h6>';
            itemsHTML += trackedBasket.map(item => `
                <div class="basket-item">
                    ${item.logo ? `<img src="${item.logo}" alt="${item.name}">` : '<div style="width:50px;height:50px;background:#f0f0f0;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;"><i class="bi bi-building text-muted"></i></div>'}
                    <div class="basket-item-info">
                        <div class="basket-item-name">${item.name}</div>
                        <div class="basket-item-category"><span class="badge bg-warning text-dark">Tracked</span></div>
                    </div>
                    <button class="basket-item-remove" onclick="removeTrackedItem(${item.id})">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            `).join('');
            itemsHTML += '</div>';
        }
        
        basketItems.innerHTML = itemsHTML;
    } else {
        counter.style.display = 'none';
        // Close modal if open
        const modal = bootstrap.Modal.getInstance(document.getElementById('basketModal'));
        if (modal) {
            modal.hide();
        }
        
        // Hide active popover if basket is empty
        if (activePopover) {
            activePopover.hide();
            setTimeout(() => {
                if (activePopover) {
                    activePopover.dispose();
                    activePopover = null;
                }
            }, 200);
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


// Confirm enrollments - handles both picked and tracked items
async function confirmEnrollments() {
    // Sync global variable with sessionStorage first
    selectionBasket = JSON.parse(sessionStorage.getItem('enrollmentBasket') || '[]');
    const trackedBasket = JSON.parse(sessionStorage.getItem('trackedBasket') || '[]');
    
    console.log('Picked basket (synced):', selectionBasket);
    console.log('Tracked basket:', trackedBasket);
    console.log('Picked length:', selectionBasket.length);
    console.log('Tracked length:', trackedBasket.length);
    
    if (selectionBasket.length === 0 && trackedBasket.length === 0) {
        console.log('Both baskets are empty');
        showError('No items to submit');
        return;
    }
    
    // Close basket modal
    const basketModal = bootstrap.Modal.getInstance(document.getElementById('basketModal'));
    if (basketModal) {
        basketModal.hide();
    }
    
    // Show loading
    showLoading(true);
    
    try {
        // Prepare batch data for submission - ensure IDs are numbers
        const batchData = {
            picked: selectionBasket.map(item => parseInt(item.id, 10)),
            tracked: trackedBasket.map(item => parseInt(item.id, 10))
        };
        
        console.log('Submitting batch data:', batchData);
        console.log('Picked IDs:', batchData.picked);
        console.log('Tracked IDs:', batchData.tracked);
        
        // Submit both picked and tracked in batch
        const response = await fetch('/myaccount/ajax/batch-process-enrollments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(batchData)
        });
        
        if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Server response:', result);
        
        if (!result) {
            throw new Error('No response from server');
        }
        
        // Check if the server reported success
        if (result.success === false && result.message) {
            // Log debug error if available
            if (result.debug_error) {
                console.error('Debug error from server:', result.debug_error);
            }
            throw new Error(result.message);
        }
        
        let successCount = 0;
        let trackedSuccessCount = 0;
        let errors = [];
        
        // Handle results for picked items
        if (result.picked && Array.isArray(result.picked)) {
            result.picked.forEach(item => {
                if (item.success) {
                    successCount++;
                    updateCompanyCard(item.company_id, true);
                } else {
                    errors.push(`${item.company_name}: ${item.error || 'Failed'}`);
                }
            });
        }
        
        // Handle results for tracked items
        if (result.tracked && Array.isArray(result.tracked)) {
            result.tracked.forEach(item => {
                if (item.success) {
                    trackedSuccessCount++;
                    // Note: No need to update cards since tracked companies are filtered from the list
                } else {
                    errors.push(`${item.company_name}: ${item.error || 'Failed to track'}`);
                }
            });
        }
        
        // If we got a successful response but no items were processed, 
        // assume everything went through successfully based on what we sent
        if (result.success === true && successCount === 0 && trackedSuccessCount === 0 && errors.length === 0) {
            // Count what we sent as successful
            successCount = selectionBasket.length;
            trackedSuccessCount = trackedBasket.length;
        }
        
        // Clear both baskets
        selectionBasket = [];  // Clear global variable
        sessionStorage.removeItem('enrollmentBasket');
        sessionStorage.removeItem('trackedBasket');
        updateBasketUI();
        
        // Show results
        const totalSuccess = successCount + trackedSuccessCount;
        
        if (totalSuccess > 0 && errors.length === 0) {
            let message = '';
            if (successCount > 0 && trackedSuccessCount > 0) {
                const pickWord = successCount === 1 ? window.userData.labels.token : window.userData.labels.token + 's';
                message = `Successfully submitted ${successCount} ${pickWord} for enrollment and tracked ${trackedSuccessCount} reward${trackedSuccessCount === 1 ? '' : 's'} you already have.`;
            } else if (successCount > 0) {
                const pickWord = successCount === 1 ? window.userData.labels.token : window.userData.labels.token + 's';
                message = `Your ${successCount} ${pickWord} ${successCount === 1 ? 'has' : 'have'} been successfully submitted for enrollment processing.`;
            } else if (trackedSuccessCount > 0) {
                message = `Successfully tracked ${trackedSuccessCount} reward${trackedSuccessCount === 1 ? '' : 's'} you already have.`;
            }
            showSuccess(message, totalSuccess);
            
            // Reload page after showing success message to remove tracked items from display
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else if (totalSuccess > 0 && errors.length > 0) {
            showError(`Partially successful. ${totalSuccess} processed, but errors: ${errors.join(', ')}`);
            
            // Reload page after partial success
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        } else if (errors.length > 0) {
            showError(`Failed to submit. Errors: ${errors.join(', ')}`);
        } else {
            // This shouldn't happen if we have a successful response
            // But if it does, still reload to clear tracked items
            showError('No items to submit');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
        
        // Update balance display
        if (successCount > 0) {
            document.querySelector('.balance-number').textContent = window.userData.availableAllocations;
        }
        
    } catch (error) {
        console.error('Batch enrollment error:', error);
        
        // Show more specific error message
        let errorMsg = 'Failed to process enrollments';
        if (error.message) {
            errorMsg += ': ' + error.message;
        }
        showError(errorMsg);
    } finally {
        showLoading(false);
    }
}

// Redirect to my account page
function redirectToMyAccount() {
    window.location.href = '/myaccount';
}

// Show first-time picker help popover
function showFirstPickerHelp() {
    const counter = document.getElementById('selectionCounter');
    if (!counter) return;
    
    // Mark as shown in session storage (unless forced via URL)
    if (!window.userData.forceShowHelp) {
        sessionStorage.setItem('pickerHelpShown', 'true');
    }
    
    // Small delay to ensure cart animation completes and is visible
    setTimeout(() => {
        // Initialize Bootstrap popover and store globally
        activePopover = new bootstrap.Popover(counter, {
            content: 'Great choice! Click here to review and confirm your selections.',
            placement: 'left',
            trigger: 'manual',
            customClass: 'first-picker-popover',
            html: true,
            animation: true
        });
        
        // Show the popover
        activePopover.show();
        
        // Auto-hide after 10 seconds
        const autoHideTimer = setTimeout(() => {
            if (activePopover) {
                activePopover.hide();
                // Clean up after hiding
                setTimeout(() => {
                    if (activePopover) {
                        activePopover.dispose();
                        activePopover = null;
                    }
                }, 500);
            }
        }, 10000);
        
        // Also hide when user clicks the cart
        counter.addEventListener('click', function hidePopoverOnClick() {
            clearTimeout(autoHideTimer); // Clear auto-hide timer
            if (activePopover) {
                activePopover.hide();
                setTimeout(() => {
                    if (activePopover) {
                        activePopover.dispose();
                        activePopover = null;
                    }
                }, 500);
            }
            counter.removeEventListener('click', hidePopoverOnClick);
        }, { once: true });
    }, 300); // 300ms delay for cart animation
}

