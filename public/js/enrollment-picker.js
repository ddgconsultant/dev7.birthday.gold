/**
 * Enrollment Picker JavaScript
 * Handles enrollment actions and UI interactions
 */

// Global state
let isProcessing = false;

// Clear search
function clearSearch() {
    const searchInput = document.querySelector('.search-input');
    searchInput.value = '';
    searchInput.form.submit();
}

// Toggle favorite
async function toggleFavorite(companyId) {
    if (isProcessing) return;
    
    const btn = document.querySelector(`[data-company-id="${companyId}"] .favorite-btn`);
    const isActive = btn.classList.contains('active');
    
    try {
        const response = await fetch('/api/toggle-favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                company_id: companyId,
                action: isActive ? 'remove' : 'add'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            btn.classList.toggle('active');
            const icon = btn.querySelector('i');
            icon.className = isActive ? 'bi bi-heart' : 'bi bi-heart-fill';
        }
    } catch (error) {
        console.error('Failed to toggle favorite:', error);
    }
}

// Enroll in company
async function enrollInCompany(companyId) {
    if (isProcessing || window.userData.availableAllocations <= 0) return;
    
    isProcessing = true;
    showLoading(true);
    
    try {
        const response = await fetch('/api/enroll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                company_id: companyId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update UI
            updateCompanyCard(companyId, true);
            updateBalance(result.new_balance);
            
            // Show success message
            showSuccess(result.message || 'Successfully enrolled!');
            
            // Track analytics
            if (window.gtag) {
                gtag('event', 'enrollment_complete', {
                    company_id: companyId,
                    allocation_type: result.allocation_used?.allocation_type
                });
            }
        } else {
            showError(result.error || 'Enrollment failed');
        }
    } catch (error) {
        console.error('Enrollment error:', error);
        showError('Network error. Please try again.');
    } finally {
        isProcessing = false;
        showLoading(false);
    }
}

// Update company card after enrollment
function updateCompanyCard(companyId, enrolled) {
    const card = document.querySelector(`[data-company-id="${companyId}"]`);
    if (!card) return;
    
    if (enrolled) {
        card.classList.add('enrolled');
        const btn = card.querySelector('.action-btn');
        btn.className = 'action-btn enrolled';
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Enrolled';
    }
}

// Update balance display
function updateBalance(newBalance) {
    window.userData.availableAllocations = newBalance.available_allocations;
    
    // Update balance number
    const balanceNumber = document.querySelector('.balance-number');
    if (balanceNumber) {
        balanceNumber.textContent = newBalance.available_allocations;
    }
    
    // Update expiring warning
    const expiringWarning = document.querySelector('.expiring-warning');
    if (expiringWarning) {
        if (newBalance.expiring_soon_count > 0) {
            expiringWarning.style.display = 'flex';
            expiringWarning.innerHTML = `<i class="bi bi-clock-history"></i> ${newBalance.expiring_soon_count} expiring soon`;
        } else {
            expiringWarning.style.display = 'none';
        }
    }
    
    // Update all enrollment buttons if no allocations left
    if (newBalance.available_allocations <= 0) {
        document.querySelectorAll('.action-btn.enroll').forEach(btn => {
            btn.className = 'action-btn disabled';
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-lock"></i> No Enrollments';
        });
        
        // Show warning
        showWarning('You have no enrollments left. <a href="/myaccount/earn-enrollments">Earn more</a>');
    }
}

// Show loading overlay
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

// Show success message
function showSuccess(message) {
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    document.getElementById('successMessage').textContent = message;
    modal.show();
}

// Show error message
function showError(message) {
    // You can implement a toast notification here
    alert(message); // Simple fallback
}

// Show warning message
function showWarning(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'allocation-alert alert-warning';
    alertDiv.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${message}`;
    
    const header = document.querySelector('.enrollment-header');
    header.parentNode.insertBefore(alertDiv, header.nextSibling);
}

// Infinite scroll for large lists
let isLoadingMore = false;
let currentPage = 1;

function loadMoreCompanies() {
    if (isLoadingMore || !hasMoreCompanies) return;
    
    isLoadingMore = true;
    currentPage++;
    
    fetch(`/api/load-more.php?page=${currentPage}&category=${selectedCategory}&search=${searchQuery}`)
        .then(response => response.json())
        .then(data => {
            if (data.companies && data.companies.length > 0) {
                appendCompanies(data.companies);
                hasMoreCompanies = data.has_more;
            } else {
                hasMoreCompanies = false;
            }
        })
        .finally(() => {
            isLoadingMore = false;
        });
}

// Detect when near bottom of page
window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 1000) {
        loadMoreCompanies();
    }
});

// Search debouncing
let searchTimeout;
const searchInput = document.querySelector('.search-input');

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (e.target.value.length >= 2 || e.target.value.length === 0) {
                e.target.form.submit();
            }
        }, 500);
    });
}

// Pull to refresh on mobile
let touchStartY = 0;
let touchEndY = 0;

document.addEventListener('touchstart', (e) => {
    touchStartY = e.changedTouches[0].screenY;
});

document.addEventListener('touchend', (e) => {
    touchEndY = e.changedTouches[0].screenY;
    if (touchEndY - touchStartY > 100 && window.scrollY === 0) {
        location.reload();
    }
});

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    // Add visual feedback for touch
    document.querySelectorAll('.company-card').forEach(card => {
        card.addEventListener('touchstart', () => {
            card.style.transform = 'scale(0.98)';
        });
        
        card.addEventListener('touchend', () => {
            setTimeout(() => {
                card.style.transform = '';
            }, 100);
        });
    });
    
    // Smooth scroll for category pills
    const categoryScroll = document.querySelector('.category-scroll');
    if (categoryScroll) {
        const activePill = categoryScroll.querySelector('.category-pill.active');
        if (activePill) {
            activePill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    }
});