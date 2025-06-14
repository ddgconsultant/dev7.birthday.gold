/**
 * Register Details Flow JavaScript V3
 * Handles common functionality for all form types
 * Form-specific JS is injected by the PHP form handler
 */

document.addEventListener('DOMContentLoaded', function() {
    // Common elements
    const form = document.getElementById('detailsForm');
    const promoCodeInput = document.getElementById('promo_code');
    const referralCodeInput = document.getElementById('referral_code');
    const applyPromoBtn = document.getElementById('applyPromo');
    const promoMessage = document.getElementById('promoMessage');
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    
    // Promo/Referral toggle
    const togglePromoReferral = document.getElementById('togglePromoReferral');
    const promoReferralSection = document.getElementById('promoReferralSection');
    const promoReferralChevron = document.getElementById('promoReferralChevron');
    
    // Bootstrap collapse instance
    let promoReferralCollapse = null;
    if (promoReferralSection) {
        promoReferralCollapse = new bootstrap.Collapse(promoReferralSection, {
            toggle: false
        });
        
        // If we have promo/referral codes from URL, ensure section is shown
        if (pageData.showPromoSection) {
            promoReferralCollapse.show();
        }
    }
    
    // Initialize
    init();
    
    function init() {
        bindCommonEvents();
        
        // Check if promo code was pre-filled and auto-apply
        if (promoCodeInput && promoCodeInput.value) {
            applyPromoCode();
        }
    }
    
    function bindCommonEvents() {
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', checkPasswordStrength);
        }
        
        // Promo/Referral section toggle
        if (togglePromoReferral && promoReferralCollapse) {
            togglePromoReferral.addEventListener('click', function(e) {
                e.preventDefault();
                promoReferralCollapse.toggle();
            });
            
            // Listen for show/hide events to update chevron
            promoReferralSection.addEventListener('shown.bs.collapse', function() {
                promoReferralChevron.classList.remove('bi-chevron-down');
                promoReferralChevron.classList.add('bi-chevron-up');
                
                // Focus on promo code if empty, otherwise referral code
                if (promoCodeInput && !promoCodeInput.value) {
                    promoCodeInput.focus();
                } else if (referralCodeInput && !referralCodeInput.value) {
                    referralCodeInput.focus();
                }
            });
            
            promoReferralSection.addEventListener('hidden.bs.collapse', function() {
                promoReferralChevron.classList.remove('bi-chevron-up');
                promoReferralChevron.classList.add('bi-chevron-down');
            });
        }
        
        // Toggle password visibility
        togglePasswordBtns.forEach(btn => {
            btn.addEventListener('click', togglePasswordVisibility);
        });
        
        // Promo code
        if (applyPromoBtn) {
            applyPromoBtn.addEventListener('click', applyPromoCode);
            
            // Apply on Enter key
            promoCodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyPromoCode();
                }
            });
        }
        
        // Form submission
        form.addEventListener('submit', handleFormSubmit);
        
        // Email availability check
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('blur', checkEmailAvailability);
        }
    }
    
    function checkPasswordStrength() {
        const password = this.value;
        const strengthBar = document.querySelector('.strength-bar');
        
        if (!strengthBar) return;
        
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Complexity checks
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        // Update strength bar
        strengthBar.className = 'strength-bar';
        if (password.length === 0) {
            strengthBar.style.width = '0';
        } else if (strength <= 2) {
            strengthBar.classList.add('weak');
            strengthBar.style.width = '33%';
        } else if (strength <= 3) {
            strengthBar.classList.add('medium');
            strengthBar.style.width = '66%';
        } else {
            strengthBar.classList.add('strong');
            strengthBar.style.width = '100%';
        }
    }
    
    function togglePasswordVisibility(e) {
        const btn = e.currentTarget;
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = btn.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
    
    async function applyPromoCode() {
        const promoCode = promoCodeInput.value.trim();
        
        if (!promoCode) {
            showPromoMessage('Please enter a promo code', 'danger');
            return;
        }
        
        // Show loading state
        applyPromoBtn.disabled = true;
        applyPromoBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Applying...';
        
        try {
            const response = await fetch(`${pageData.ajaxUrl}?ajax_action=validate_promo&promo_code=${encodeURIComponent(promoCode)}`);
            const data = await response.json();
            
            if (data.valid) {
                showPromoMessage(data.message, 'success');
                
                // Update displayed price if provided
                const displayPrice = document.getElementById('displayPrice');
                if (displayPrice && data.new_price) {
                    displayPrice.innerHTML = data.new_price + ' <del class="text-muted ms-2">' + displayPrice.textContent + '</del>';
                    displayPrice.classList.add('text-success');
                }
                
                // Disable promo input after successful application
                promoCodeInput.disabled = true;
                applyPromoBtn.style.display = 'none';
                
                // Show a small "Remove" link
                const removeLink = document.createElement('a');
                removeLink.href = '#';
                removeLink.className = 'small text-danger ms-2';
                removeLink.textContent = 'Remove';
                removeLink.onclick = function(e) {
                    e.preventDefault();
                    removePromoCode();
                };
                applyPromoBtn.parentNode.appendChild(removeLink);
            } else {
                showPromoMessage(data.message || 'Invalid promo code', 'danger');
                promoCodeInput.focus();
            }
        } catch (error) {
            console.error('Error applying promo code:', error);
            showPromoMessage('Error applying promo code. Please try again.', 'danger');
        } finally {
            // Reset button if not successful
            if (!promoCodeInput.disabled) {
                applyPromoBtn.disabled = false;
                applyPromoBtn.innerHTML = 'Apply';
            }
        }
    }
    
    function removePromoCode() {
        promoCodeInput.disabled = false;
        promoCodeInput.value = '';
        applyPromoBtn.style.display = '';
        promoMessage.textContent = '';
        
        // Remove the "Remove" link
        const removeLink = applyPromoBtn.parentNode.querySelector('a.text-danger');
        if (removeLink) {
            removeLink.remove();
        }
        
        // Reset price display
        const displayPrice = document.getElementById('displayPrice');
        if (displayPrice) {
            displayPrice.innerHTML = pageData.originalPrice;
            displayPrice.classList.remove('text-success');
        }
    }
    
    function showPromoMessage(message, type) {
        if (!promoMessage) return;
        
        promoMessage.textContent = message;
        promoMessage.className = `mt-1 small text-${type}`;
        
        // Auto-hide error messages after 5 seconds
        if (type === 'danger') {
            setTimeout(() => {
                promoMessage.textContent = '';
                promoMessage.className = 'mt-1';
            }, 5000);
        }
    }
    
    async function checkEmailAvailability() {
        const email = this.value.trim();
        if (!email || !isValidEmail(email)) return;
        
        try {
            const response = await fetch(`${pageData.ajaxUrl}?ajax_action=check_email&email=${encodeURIComponent(email)}`);
            const data = await response.json();
            
            if (data.available === false) {
                setFieldError(this, 'This email is already registered');
            } else {
                clearFieldError(this);
            }
        } catch (error) {
            console.error('Error checking email:', error);
        }
    }
    
    function setFieldError(field, message) {
        field.classList.add('is-invalid');
        
        // Find or create feedback element
        let feedback = field.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentElement.appendChild(feedback);
        }
        feedback.textContent = message;
    }
    
    function clearFieldError(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = '';
        }
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    async function handleFormSubmit(e) {
        e.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        
        // Basic client-side validation
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]:not([disabled])');
        
        requiredFields.forEach(field => {
            // Skip hidden fields
            const container = field.closest('.d-none');
            if (container) return;
            
            if (!field.value.trim()) {
                setFieldError(field, 'This field is required');
                isValid = false;
            }
        });
        
        if (isValid) {
            // Show loading state on submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account...';
            
            // Submit form
            form.submit();
        } else {
            // Scroll to first error
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    }
});