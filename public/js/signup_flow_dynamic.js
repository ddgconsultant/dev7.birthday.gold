/**
 * Dynamic Signup Flow JavaScript
 * Handles account type switching, plan selection, promo validation, and form submission
 */

document.addEventListener('DOMContentLoaded', function() {
    // State management
    const state = {
        selectedAccountType: null,
        selectedPlan: null,
        selectedPlanData: null,
        promoCode: null,
        promoValid: false,
        referralCode: null
    };

    // Elements
    const elements = {
        accountTypeBtns: document.querySelectorAll('.account-type-btn'),
        planGrid: document.getElementById('planGrid'),
        continueBtn: document.getElementById('continueBtn'),
        contextText: document.getElementById('contextText'),
        form: document.getElementById('signupForm'),
        modalSelectBtn: document.getElementById('modalSelectBtn'),
        otherAccountsModal: document.getElementById('otherAccountsModal')
    };

    // Initialize
    init();

    function init() {
        // Set initial account type
        const activeBtn = document.querySelector('.account-type-btn.active');
        if (activeBtn) {
            state.selectedAccountType = activeBtn.getAttribute('data-account-type');
            // Also set the hidden field
            document.getElementById('hiddenAccountType').value = state.selectedAccountType;
        }

        // Bind events
        bindEvents();
        
        // Update UI
        updateContinueButton();
    }

    function bindEvents() {
        // Account type selection
        elements.accountTypeBtns.forEach(btn => {
            btn.addEventListener('click', handleAccountTypeClick);
        });

        // Plan selection (delegated)
        elements.planGrid.addEventListener('click', function(e) {
            const planCard = e.target.closest('.plan-card');
            if (planCard) {
                handlePlanSelection(planCard);
            }
        });



        // Modal account type selection
        if (elements.otherAccountsModal) {
            const modalBtns = elements.otherAccountsModal.querySelectorAll('[data-account-type]');
            modalBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Update selection UI
                    modalBtns.forEach(b => {
                        b.classList.remove('active');
                        b.querySelector('.selection-check').classList.add('d-none');
                    });
                    this.classList.add('active');
                    this.querySelector('.selection-check').classList.remove('d-none');
                    
                    // Enable select button
                    elements.modalSelectBtn.disabled = false;
                    elements.modalSelectBtn.setAttribute('data-selected-type', this.getAttribute('data-account-type'));
                });
            });

            elements.modalSelectBtn.addEventListener('click', function() {
                const selectedType = this.getAttribute('data-selected-type');
                if (selectedType) {
                    // Reload page with new account type
                    window.location.href = `?account_type=${selectedType}`;
                }
            });
        }

        // Form submission
        elements.form.addEventListener('submit', handleFormSubmit);
    }

    function handleAccountTypeClick(e) {
        const btn = e.currentTarget;
        
        // Check if it's a modal trigger
        if (btn.hasAttribute('data-modal-trigger')) {
            const modal = new bootstrap.Modal(document.getElementById(btn.getAttribute('data-modal-trigger')));
            modal.show();
            return;
        }

        const accountType = btn.getAttribute('data-account-type');
        
        // If different account type, reload page to get new plans
        if (accountType !== state.selectedAccountType) {
            window.location.href = `?account_type=${accountType}`;
        }
    }

    async function loadPlansForAccountType(accountType) {
        try {
            // Show loading state
            elements.planGrid.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            // Fetch plans via AJAX
            const response = await fetch(`${pageData.ajaxUrl}?ajax_action=get_plans&account_type=${accountType}&version=${pageData.selectedVersion}`);
            const data = await response.json();

            if (data.success) {
                renderPlans(data.plans);
            } else {
                throw new Error('Failed to load plans');
            }
        } catch (error) {
            console.error('Error loading plans:', error);
            elements.planGrid.innerHTML = '<div class="alert alert-danger">Error loading plans. Please refresh the page.</div>';
        }
    }

    function renderPlans(plans) {
        let html = '';
        
        plans.forEach(plan => {
            const isRecommended = plan.is_recommended;
            
            html += `
                <div class="plan-card${isRecommended ? ' recommended' : ''}" 
                     data-plan="${plan.plan_code}" 
                     data-plan-id="${plan.id}"
                     data-price="${plan.price}">
                    ${isRecommended ? '<div class="recommended-badge">POPULAR</div>' : ''}
                    <div class="plan-header">
                        <div class="plan-icon">
                            <i class="bi ${getPlanIcon(plan.plan_code)}"></i>
                        </div>
                        <h3 class="plan-title">${plan.name}</h3>
                    </div>
                    <div class="plan-price">${plan.price_formatted}</div>
                    <div class="plan-price-note">${getPriceNote(plan)}</div>
                    <ul class="plan-features">
                        ${plan.features.map(feature => `<li>${feature}</li>`).join('')}
                    </ul>
                </div>
            `;
        });
        
        elements.planGrid.innerHTML = html;
    }

    function getPlanIcon(planCode) {
        if (planCode.includes('free')) return 'bi-person';
        if (planCode.includes('gold')) return 'bi-star-fill';
        if (planCode.includes('life')) return 'bi-infinity';
        if (planCode.includes('business')) return 'bi-building';
        if (planCode.includes('family')) return 'bi-people';
        return 'bi-award';
    }

    function getPriceNote(plan) {
        if (plan.price === 0) return 'Forever free';
        if (plan.plan_code.includes('life')) return 'Lifetime access';
        return 'One-time payment';
    }

    function handlePlanSelection(planCard) {
        // Update UI
        document.querySelectorAll('.plan-card').forEach(card => {
            card.classList.remove('selected');
        });
        planCard.classList.add('selected');

        // Update state
        state.selectedPlan = planCard.getAttribute('data-plan-id');
        state.selectedPlanData = {
            name: planCard.querySelector('.plan-title').textContent,
            price: parseInt(planCard.getAttribute('data-price')),
            priceFormatted: planCard.querySelector('.plan-price').textContent,
            priceNote: planCard.querySelector('.plan-price-note').textContent
        };

        // Update hidden form fields
        document.getElementById('hiddenPlan').value = state.selectedPlan;
        document.getElementById('hiddenAccountType').value = state.selectedAccountType;

        // Update continue button
        updateContinueButton();
    }

    function updateContinueButton() {
        elements.continueBtn.disabled = !state.selectedPlan;
        
        // Make button more prominent when enabled
        if (state.selectedPlan) {
            elements.continueBtn.classList.add('btn-lg', 'pulse-animation');
            elements.continueBtn.innerHTML = 'Continue to Account Details <i class="bi bi-arrow-right ms-2"></i>';
        } else {
            elements.continueBtn.classList.remove('btn-lg', 'pulse-animation');
            elements.continueBtn.innerHTML = 'Select a Plan to Continue';
        }
    }



    function handleFormSubmit(e) {
        // Validate before submission
        if (!state.selectedPlan) {
            e.preventDefault();
            alert('Please select a plan');
            return;
        }

        // Form will submit normally
    }
});