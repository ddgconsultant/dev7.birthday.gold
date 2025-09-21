class ImprovedSignupFlow {
    constructor() {
        this.currentStep = 1;
        this.selectedAccountType = 'individual';
        this.selectedPlan = null;
        this.selectedPlanData = null;
        this.validPromoCode = null;
        this.validReferralCode = null;
        
        // Updated plan data with clearer context messages
        this.planData = {
            individual: {
                free: {
                    id: 'free_individual',
                    name: 'Free Plan',
                    price: '$0',
                    priceNote: 'Forever',
                    icon: 'bi-person',
                    features: [
                        'Basic birthday tracking',
                        'Limited offers',
                        'Email support'
                    ]
                },
                gold: {
                    id: 'gold_individual',
                    name: 'Gold Plan',
                    price: '$40',
                    priceNote: 'One-time payment',
                    icon: 'bi-star-fill',
                    features: [
                        'All birthday freebies',
                        'VIP experiences',
                        'Priority support',
                        'Year-round deals'
                    ],
                    recommended: true
                }
            },
            family: {
                gold: {
                    id: 'gold_family',
                    name: 'Family Plan',
                    price: '$40',
                    priceNote: 'Parent + discounted children',
                    icon: 'bi-people-fill',
                    features: [
                        'All Gold Plan features',
                        'Add up to 6 children',
                        'Discounted child rates ($5 each)',
                        'Manage from one account',
                        'Family dashboard',
                        'Bulk enrollment'
                    ],
                    recommended: true
                }
            },
            giftcertificate: {
                gold: {
                    id: 'gold_gift',
                    name: 'Gold Gift Certificate',
                    price: '$40',
                    priceNote: 'One-time payment',
                    icon: 'bi-gift',
                    features: [
                        'All birthday freebies',
                        'VIP experiences',
                        'Priority support',
                        'Year-round deals',
                        'Beautiful gift presentation',
                        'Personalized gift message'
                    ],
                    giftOnly: true
                }
            },
            business: {
                enterprise: {
                    id: 'enterprise_business',
                    name: 'Business Plan',
                    price: 'Custom',
                    priceNote: 'Contact for pricing',
                    icon: 'bi-building',
                    features: [
                        'Employee birthday management',
                        'Bulk enrollment tools',
                        'Admin dashboard',
                        'Custom branding',
                        'Priority support',
                        'Analytics & reporting'
                    ],
                    recommended: true
                }
            },
            enterprise: {
                enterprise: {
                    id: 'enterprise_large',
                    name: 'Enterprise Plan',
                    price: 'Custom',
                    priceNote: 'Contact for pricing',
                    icon: 'bi-buildings',
                    features: [
                        'All Business Plan features',
                        'Unlimited employees',
                        'Dedicated account manager',
                        'Custom integrations',
                        'SLA guarantees',
                        'Advanced analytics'
                    ],
                    recommended: true
                }
            },
            nonprofit: {
                gold: {
                    id: 'gold_nonprofit',
                    name: 'Non-Profit Plan',
                    price: '$20',
                    priceNote: 'Special non-profit pricing',
                    icon: 'bi-heart',
                    features: [
                        'All Gold Plan features',
                        '50% discount for verified non-profits',
                        'Volunteer management tools',
                        'Fundraising support',
                        'Community features',
                        'Priority support'
                    ],
                    recommended: true
                }
            }
        };
        
        // Updated labels for the UI
        this.accountTypeLabels = {
            individual: 'Just me',
            family: 'My family',
            giftcertificate: 'As a gift',
            business: 'Business',
            enterprise: 'Enterprise',
            nonprofit: 'Non-Profit'
        };
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateContextInfo();
        this.updateButtons();
    }

    bindEvents() {
        // Main account type buttons
        document.querySelectorAll('.account-type-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Check if this is the "Other" button
                if (btn.dataset.modalTrigger) {
                    // Open the modal instead of selecting account type
                    const modal = new bootstrap.Modal(document.getElementById(btn.dataset.modalTrigger));
                    modal.show();
                } else {
                    this.selectAccountType(btn.dataset.accountType);
                }
            });
        });

        // Other account type modal options
        document.querySelectorAll('.list-group-item[data-account-type]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const accountType = item.dataset.accountType;
                
                // Update visual selection in modal
                document.querySelectorAll('.list-group-item .selection-check').forEach(check => {
                    check.classList.add('d-none');
                });
                item.querySelector('.selection-check').classList.remove('d-none');
                
                // Enable the select button
                document.getElementById('modalSelectBtn').disabled = false;
                document.getElementById('modalSelectBtn').dataset.accountType = accountType;
            });
        });

        // Modal select button
        document.getElementById('modalSelectBtn').addEventListener('click', () => {
            const accountType = document.getElementById('modalSelectBtn').dataset.accountType;
            if (accountType) {
                this.selectOtherAccountType(accountType);
            }
        });

        // Navigation buttons
        document.getElementById('continueToReview').addEventListener('click', () => this.goToStep(2));
        document.getElementById('backToStep1').addEventListener('click', () => this.goToStep(1));

        // Promo toggle
        document.querySelector('.promo-toggle').addEventListener('click', (e) => {
            e.preventDefault();
            this.togglePromoSection();
        });

        // Plan selection
        document.addEventListener('click', (e) => {
            const planCard = e.target.closest('.plan-card');
            if (planCard && !planCard.classList.contains('disabled')) {
                this.selectPlan(planCard);
            }
        });
    }

    selectAccountType(accountType) {
        this.selectedAccountType = accountType;
        this.selectedPlan = null;
        this.selectedPlanData = null;
        
        // Update button states
        document.querySelectorAll('.account-type-btn').forEach(btn => {
            if (!btn.dataset.modalTrigger) {
                btn.classList.toggle('active', btn.dataset.accountType === accountType);
            }
        });
        
        // Update context and buttons
        this.updateContextInfo();
        this.updateButtons();
    }

    selectOtherAccountType(accountType) {
        // Remove selection from main buttons
        document.querySelectorAll('.account-type-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        this.selectedAccountType = accountType;
        this.selectedPlan = null;
        this.selectedPlanData = null;
        
        // Update context
        this.updateContextInfo();
        this.updateButtons();

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('otherAccountsModal'));
        if (modal) {
            modal.hide();
        }
    }

    updateContextInfo() {
        const contextText = document.getElementById('contextText');
        
        const contextMessages = {
            individual: 'Choose the plan that works best for your personal birthday rewards',
            family: 'Family plans include Gold features plus discounted rates for children',
            giftcertificate: 'Gift certificates automatically include our premium Gold Plan features',
            business: 'Business plans offer employee management and bulk enrollment tools',
            enterprise: 'Enterprise plans provide unlimited scalability with dedicated support',
            nonprofit: 'Special pricing and features designed for non-profit organizations'
        };
        
        contextText.textContent = contextMessages[this.selectedAccountType];
        
        // Update the info modal content based on current selection
        this.updateInfoModalContent();
    }

    updateInfoModalContent() {
        const content = document.getElementById('accountTypeInfoContent');
        const accountType = this.selectedAccountType;
        
        let html = '<div class="account-type-details">';
        
        if (accountType === 'individual') {
            html += `
                <h6>Individual Account (Just me)</h6>
                <p>Perfect for individuals who want to enjoy birthday rewards and member benefits for themselves.</p>
                <ul>
                    <li>Personal birthday tracking and reminders</li>
                    <li>Access to hundreds of birthday freebies</li>
                    <li>Year-round member rewards</li>
                    <li>Mobile app access</li>
                </ul>
            `;
        } else if (accountType === 'family') {
            html += `
                <h6>Family Account (My family)</h6>
                <p>Manage birthday rewards for your entire family from one account.</p>
                <ul>
                    <li>Add up to 6 children to your account</li>
                    <li>Discounted pricing for additional family members</li>
                    <li>Family dashboard to track all birthdays</li>
                    <li>Parental controls for children's accounts</li>
                    <li>Bulk reward enrollment for the whole family</li>
                </ul>
            `;
        } else if (accountType === 'giftcertificate') {
            html += `
                <h6>Gift Certificate (As a gift)</h6>
                <p>Give the gift of birthday rewards! Perfect for friends and loved ones.</p>
                <ul>
                    <li>Beautiful digital gift presentation</li>
                    <li>Personalized message option</li>
                    <li>Instant delivery or scheduled sending</li>
                    <li>Includes full Gold Plan benefits</li>
                    <li>One year of premium birthday rewards</li>
                </ul>
            `;
        } else {
            // Show all account types
            html += `
                <h6>All Account Types</h6>
                <div class="mt-3">
                    <strong>Individual (Just me):</strong>
                    <p>For personal use - enjoy birthday rewards and member benefits.</p>
                </div>
                <div class="mt-3">
                    <strong>Family (My family):</strong>
                    <p>Manage rewards for your entire family with discounted child accounts.</p>
                </div>
                <div class="mt-3">
                    <strong>Gift Certificate (As a gift):</strong>
                    <p>Perfect gift option with full Gold Plan benefits.</p>
                </div>
                <div class="mt-3">
                    <strong>Business:</strong>
                    <p>Employee birthday management and bulk enrollment tools.</p>
                </div>
                <div class="mt-3">
                    <strong>Enterprise:</strong>
                    <p>Large-scale organization management with dedicated support.</p>
                </div>
                <div class="mt-3">
                    <strong>Non-Profit:</strong>
                    <p>Special 50% discount for qualifying non-profit organizations.</p>
                </div>
            `;
        }
        
        html += '</div>';
        content.innerHTML = html;
    }

    selectPlan(card) {
        // Remove previous selections
        document.querySelectorAll('.plan-card').forEach(c => {
            c.classList.remove('selected');
        });
        
        // Add selection to clicked card
        card.classList.add('selected');
        
        // Store selection
        this.selectedPlan = card.dataset.plan;
        const plans = this.planData[this.selectedAccountType];
        if (plans && plans[this.selectedPlan]) {
            this.selectedPlanData = plans[this.selectedPlan];
        }
        
        this.updateButtons();
    }

    updateButtons() {
        const continueBtn = document.getElementById('continueToReview');
        continueBtn.disabled = !this.selectedPlan;
    }

    goToStep(step) {
        // Update progress indicator
        document.querySelectorAll('.progress-step').forEach((stepEl, index) => {
            stepEl.classList.remove('active', 'completed');
            if (index + 1 === step) {
                stepEl.classList.add('active');
            } else if (index + 1 < step) {
                stepEl.classList.add('completed');
            }
        });

        // Show/hide steps
        document.querySelectorAll('.step').forEach(stepEl => {
            stepEl.classList.remove('active');
        });
        document.getElementById(`step${step}`).classList.add('active');
        
        this.currentStep = step;

        if (step === 2) {
            this.updateSummary();
        }
    }

    updateSummary() {
        if (!this.selectedPlanData) return;
        
        // Use the friendly labels
        const accountTypeLabel = this.accountTypeLabels[this.selectedAccountType] || this.selectedAccountType;
        
        document.getElementById('finalPlanName').textContent = this.selectedPlanData.name;
        document.getElementById('finalAccountType').textContent = accountTypeLabel;
        document.getElementById('finalPrice').textContent = this.selectedPlanData.price;
        document.getElementById('finalPriceNote').textContent = this.selectedPlanData.priceNote;

        // Update hidden form fields
        const planId = document.querySelector(`.plan-card[data-plan="${this.selectedPlan}"]`).dataset.planId;
        document.getElementById('hiddenPlan').value = planId || this.selectedPlanData.id;
        document.getElementById('hiddenAccountType').value = this.selectedAccountType;
        document.getElementById('hiddenPromoCode').value = this.validPromoCode || '';
        document.getElementById('hiddenReferralCode').value = this.validReferralCode || '';
    }

    togglePromoSection() {
        const promoFields = document.getElementById('promoFields');
        const toggle = document.querySelector('.promo-toggle');
        const icon = toggle.querySelector('i');
        
        if (promoFields.classList.contains('show')) {
            promoFields.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
            icon.className = 'bi bi-plus-circle me-2';
        } else {
            promoFields.classList.add('show');
            toggle.setAttribute('aria-expanded', 'true');
            icon.className = 'bi bi-dash-circle me-2';
        }
    }
}

// Validation functions
function validatePromoCode() {
    const input = document.getElementById('promoCode');
    const feedback = document.getElementById('promoCodeFeedback');
    const code = input.value.trim().toUpperCase();
    
    // Demo validation
    const validCodes = ['SAVE10', 'WELCOME', 'BIRTHDAY', 'GOLD50'];
    
    if (validCodes.includes(code)) {
        feedback.textContent = '✓ Valid promo code applied!';
        feedback.className = 'form-text text-success';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        window.signupFlow.validPromoCode = code;
    } else if (code) {
        feedback.textContent = '✗ Invalid promo code';
        feedback.className = 'form-text text-danger';
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        window.signupFlow.validPromoCode = null;
    } else {
        feedback.textContent = '';
        input.classList.remove('is-valid', 'is-invalid');
        window.signupFlow.validPromoCode = null;
    }
}

function validateReferralCode() {
    const input = document.getElementById('referralCode');
    const feedback = document.getElementById('referralCodeFeedback');
    const code = input.value.trim();
    
    // Demo validation - check if it looks like a referral code
    if (code.length >= 6 && /^[A-Za-z0-9]+$/.test(code)) {
        feedback.textContent = '✓ Valid referral code applied!';
        feedback.className = 'form-text text-success';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        window.signupFlow.validReferralCode = code;
    } else if (code) {
        feedback.textContent = '✗ Invalid referral code format';
        feedback.className = 'form-text text-danger';
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        window.signupFlow.validReferralCode = null;
    } else {
        feedback.textContent = '';
        input.classList.remove('is-valid', 'is-invalid');
        window.signupFlow.validReferralCode = null;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.signupFlow = new ImprovedSignupFlow();
});