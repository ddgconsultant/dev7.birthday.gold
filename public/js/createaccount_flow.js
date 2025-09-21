/**
 * Register Details Flow JavaScript
 * Handles form validation, promo codes, and real-time checks
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const form = document.getElementById('detailsForm');
    const phoneInput = document.getElementById('phone');
    const emailInput = document.getElementById('email');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const promoCodeInput = document.getElementById('promo_code');
    const referralCodeInput = document.getElementById('referral_code');
    const applyPromoBtn = document.getElementById('applyPromo');
    const verifyReferralBtn = document.getElementById('verifyReferral');
    const promoMessage = document.getElementById('promoMessage');
    const referralMessage = document.getElementById('referralMessage');
    const displayPrice = document.getElementById('displayPrice');
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    
    // Social login removed - providers don't provide birthday data
    
    // Contact method toggle
    const phoneRadio = document.getElementById('usePhone');
    const emailRadio = document.getElementById('useEmail');
    const phoneField = document.getElementById('phoneField');
    const emailField = document.getElementById('emailField');
    
    // Promo/Referral toggle
    const togglePromoReferral = document.getElementById('togglePromoReferral');
    const togglePromoReferralMobile = document.getElementById('togglePromoReferralMobile');
    const promoReferralSection = document.getElementById('promoReferralSection');
    const promoReferralChevron = document.getElementById('promoReferralChevron');
    
    // Get Bootstrap collapse instance for promo/referral section
    let promoReferralCollapse = null;
    
    // Function to initialize collapse
    function initializeCollapse() {
        if (promoReferralSection && typeof bootstrap !== 'undefined') {
            // Check if section should be shown initially
            const shouldShow = promoReferralSection.classList.contains('show');
            promoReferralCollapse = new bootstrap.Collapse(promoReferralSection, {
                toggle: false
            });
            // If it should be shown, ensure it's properly initialized as shown
            if (shouldShow) {
                promoReferralCollapse.show();
            }
            return true;
        }
        return false;
    }
    
    // Try to initialize immediately
    if (!initializeCollapse()) {
        // If Bootstrap isn't loaded yet, wait for window load
        window.addEventListener('load', function() {
            initializeCollapse();
            // Re-bind events if needed
            if (promoReferralCollapse) {
                bindPromoToggleEvents();
            }
        });
    }
    
    // Initialize
    init();
    
    function init() {
        // Check for missing elements and log warnings
        if (!displayPrice) {
            console.warn('[PROMO] displayPrice element not found - price updates will not be shown');
        }
        
        // Debug Bootstrap availability
        if (typeof bootstrap === 'undefined') {
            console.error('[PROMO] Bootstrap is not loaded! Collapse functionality will not work.');
        } else {
            console.log('[PROMO] Bootstrap loaded successfully');
        }
        
        bindEvents();
        
        // Check if we should auto-show promo/referral section
        const referralCodeInput = document.getElementById('referral_code');
        if ((promoCodeInput && promoCodeInput.value) || (referralCodeInput && referralCodeInput.value)) {
            if (promoReferralCollapse) {
                promoReferralCollapse.show();
            }
        }
        
        // Check if promo code was pre-filled and auto-apply
        if (promoCodeInput && promoCodeInput.value) {
            applyPromoCode();
        }
    }
    
    function bindPromoToggleEvents() {
        // Promo/Referral section toggle
        if (promoReferralCollapse) {
            // Desktop toggle
            if (togglePromoReferral) {
                togglePromoReferral.addEventListener('click', function(e) {
                    e.preventDefault();
                    promoReferralCollapse.toggle();
                });
            }
            
            // Mobile toggle
            if (togglePromoReferralMobile) {
                console.log('[PROMO] Mobile toggle element found');
                togglePromoReferralMobile.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('[PROMO] Mobile toggle clicked');
                    promoReferralCollapse.toggle();
                });
            }
            
            // Listen for show/hide events to update icons
            promoReferralSection.addEventListener('shown.bs.collapse', function() {
                // Update desktop chevron
                if (promoReferralChevron) {
                    promoReferralChevron.classList.remove('bi-chevron-down');
                    promoReferralChevron.classList.add('bi-chevron-up');
                }
                
                // Update mobile header
                if (togglePromoReferralMobile) {
                    const mobileIcon = togglePromoReferralMobile.querySelector('.bi');
                    const mobileText = togglePromoReferralMobile.querySelector('span');
                    if (mobileIcon) {
                        mobileIcon.classList.remove('bi-plus-circle');
                        mobileIcon.classList.add('bi-dash-circle');
                    }
                    if (mobileText) {
                        mobileText.classList.remove('text-muted');
                        mobileText.classList.add('text-black');
                    }
                }
                
                // Focus on promo code if empty, otherwise referral code
                if (promoCodeInput && !promoCodeInput.value) {
                    promoCodeInput.focus();
                } else if (referralCodeInput && !referralCodeInput.value) {
                    referralCodeInput.focus();
                }
            });
            
            promoReferralSection.addEventListener('hidden.bs.collapse', function() {
                // Update desktop chevron
                if (promoReferralChevron) {
                    promoReferralChevron.classList.remove('bi-chevron-up');
                    promoReferralChevron.classList.add('bi-chevron-down');
                }
                
                // Update mobile header
                if (togglePromoReferralMobile) {
                    const mobileIcon = togglePromoReferralMobile.querySelector('.bi');
                    const mobileText = togglePromoReferralMobile.querySelector('span');
                    if (mobileIcon) {
                        mobileIcon.classList.remove('bi-dash-circle');
                        mobileIcon.classList.add('bi-plus-circle');
                    }
                    if (mobileText) {
                        mobileText.classList.remove('text-black');
                        mobileText.classList.add('text-muted');
                    }
                }
            });
        }
    }
    
    function bindEvents() {
        // Phone formatting only
        if (phoneInput) {
            phoneInput.addEventListener('input', formatPhoneNumber);
        }
        
        // Username input cleanup
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                // Remove spaces and special characters
                this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
            });
        }
        
        
        // Password strength indicator only
        if (passwordInput) {
            passwordInput.addEventListener('input', checkPasswordStrength);
        }
        
        // Contact method toggle
        if (phoneRadio && emailRadio) {
            phoneRadio.addEventListener('change', handleContactMethodChange);
            emailRadio.addEventListener('change', handleContactMethodChange);
        }
        
        // Bind promo toggle events
        bindPromoToggleEvents();
        
        // Toggle password visibility
        togglePasswordBtns.forEach(btn => {
            btn.addEventListener('click', togglePasswordVisibility);
        });
        
        // Promo code
        if (applyPromoBtn) {
            applyPromoBtn.addEventListener('click', applyPromoCode);
            
            // Apply on Enter key and handle button state
            if (promoCodeInput) {
                // Initial state - disable if empty
                applyPromoBtn.disabled = !promoCodeInput.value.trim();
                
                // Update button state on input
                promoCodeInput.addEventListener('input', function() {
                    applyPromoBtn.disabled = !this.value.trim();
                });
                
                promoCodeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyPromoCode();
                    }
                });
            }
        }
        
        // Referral code
        if (verifyReferralBtn) {
            verifyReferralBtn.addEventListener('click', verifyReferralCode);
            
            // Verify on Enter key and handle button state
            if (referralCodeInput) {
                // Initial state - disable if empty
                verifyReferralBtn.disabled = !referralCodeInput.value.trim();
                
                // Update button state on input
                referralCodeInput.addEventListener('input', function() {
                    verifyReferralBtn.disabled = !this.value.trim();
                });
                
                referralCodeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        verifyReferralCode();
                    }
                });
            }
        }
        
        // Birthday dropdowns handling
        const birthMonth = document.getElementById('birth_month');
        const birthDay = document.getElementById('birth_day');
        const birthYear = document.getElementById('birth_year');
        const birthdayHidden = document.getElementById('birthday');
        
        function updateBirthdayField() {
            if (birthMonth && birthDay && birthYear && birthdayHidden) {
                if (birthMonth.value && birthDay.value && birthYear.value) {
                    // Combine into YYYY-MM-DD format
                    birthdayHidden.value = `${birthYear.value}-${birthMonth.value}-${birthDay.value}`;
                } else {
                    birthdayHidden.value = '';
                }
            }
        }
        
        // Update hidden field when any dropdown changes
        if (birthMonth) birthMonth.addEventListener('change', updateBirthdayField);
        if (birthDay) birthDay.addEventListener('change', updateBirthdayField);
        if (birthYear) birthYear.addEventListener('change', updateBirthdayField);
        
        // Dynamic day adjustment based on month
        if (birthMonth && birthDay) {
            birthMonth.addEventListener('change', function() {
                const month = parseInt(this.value);
                const year = birthYear ? parseInt(birthYear.value) : new Date().getFullYear();
                const currentDay = parseInt(birthDay.value) || 1;
                
                // Days in each month
                const daysInMonth = {
                    1: 31, 2: 28, 3: 31, 4: 30, 5: 31, 6: 30,
                    7: 31, 8: 31, 9: 30, 10: 31, 11: 30, 12: 31
                };
                
                // Check for leap year
                if (month === 2 && year && ((year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0))) {
                    daysInMonth[2] = 29;
                }
                
                // Update day options
                const maxDays = month ? daysInMonth[month] : 31;
                
                // Clear current options
                birthDay.innerHTML = '<option value="">Day</option>';
                
                // Add new options
                for (let i = 1; i <= maxDays; i++) {
                    const day = i.toString().padStart(2, '0');
                    const option = document.createElement('option');
                    option.value = day;
                    option.textContent = i;
                    if (i === currentDay && currentDay <= maxDays) {
                        option.selected = true;
                    }
                    birthDay.appendChild(option);
                }
                
                updateBirthdayField();
            });
        }
        
        // Form submission
        form.addEventListener('submit', handleFormSubmit);
    }
    
    function formatPhoneNumber(e) {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = '';
        
        if (value.length > 0) {
            if (value.length <= 3) {
                formattedValue = value;
            } else if (value.length <= 6) {
                formattedValue = `${value.slice(0, 3)}-${value.slice(3)}`;
            } else {
                formattedValue = `${value.slice(0, 3)}-${value.slice(3, 6)}-${value.slice(6, 10)}`;
            }
        }
        
        e.target.value = formattedValue;
    }
    
    function validatePhone() {
        if (!phoneInput || !phoneInput.value.trim()) {
            // Check if phone is required (when phone contact method is selected)
            if (phoneRadio && phoneRadio.checked) {
                setFieldError(phoneInput, 'Phone number is required');
                return false;
            }
            return true; // Phone is optional when email is selected
        }
        
        const phone = phoneInput.value.replace(/\D/g, '');
        
        if (phone.length !== 10) {
            setFieldError(phoneInput, 'Please enter a valid 10-digit phone number');
            return false;
        }
        
        clearFieldError(phoneInput);
        return true;
    }
    
    function handleContactMethodChange(e) {
        if (phoneRadio.checked) {
            // Show phone field, hide email field
            phoneField.classList.remove('d-none');
            emailField.classList.add('d-none');
            
            // Make phone required, email optional
            if (phoneInput) {
                phoneInput.setAttribute('required', 'required');
                phoneInput.removeAttribute('tabindex');
            }
            if (emailInput) {
                emailInput.removeAttribute('required');
                emailInput.setAttribute('tabindex', '-1');
                // Clear any autofill styling
                emailInput.blur();
            }
            
            // Update alt contact field
            const altContact = document.getElementById('altContact');
            if (altContact && emailInput) altContact.value = emailInput.value;
            
            // Force reflow to ensure proper rendering
            phoneField.offsetHeight;
        } else {
            // Show email field, hide phone field
            emailField.classList.remove('d-none');
            phoneField.classList.add('d-none');
            
            // Make email required, phone optional
            if (emailInput) {
                emailInput.setAttribute('required', 'required');
                emailInput.removeAttribute('tabindex');
            }
            if (phoneInput) {
                phoneInput.removeAttribute('required');
                phoneInput.setAttribute('tabindex', '-1');
                // Clear any autofill styling
                phoneInput.blur();
            }
            
            // Update alt contact field
            const altContact = document.getElementById('altContact');
            if (altContact && phoneInput) altContact.value = phoneInput.value;
        }
    }
    
    // Social login functionality removed - providers don't provide birthday data

    async function validateEmail() {
        if (!emailInput || !emailInput.value.trim()) {
            // Check if email is required (when email contact method is selected)
            if (emailRadio && emailRadio.checked && emailInput) {
                setFieldError(emailInput, 'Email is required');
                return false;
            }
            return true; // Email is optional when phone is selected
        }
        
        const email = emailInput.value.trim();
        
        if (!isValidEmail(email)) {
            setFieldError(emailInput, 'Please enter a valid email address');
            return false;
        }
        
        // Check availability
        try {
            const response = await fetch(`${pageData.ajaxUrl}?ajax_action=check_email&email=${encodeURIComponent(email)}`);
            const data = await response.json();
            
            if (data.available === false) {
                setFieldError(emailInput, 'This email is already registered');
                return false;
            } else {
                clearFieldError(emailInput);
                return true;
            }
        } catch (error) {
            console.error('Error checking email:', error);
            return true;
        }
    }
    
    async function validateUsername() {
        // Username is auto-generated, so this validation is no longer needed
        return true;
        
        if (username.length < 3) {
            setFieldError(usernameInput, 'Username must be at least 3 characters');
            return false;
        }
        
        // Check availability
        try {
            const response = await fetch(`${pageData.ajaxUrl}?ajax_action=check_username&username=${encodeURIComponent(username)}`);
            const data = await response.json();
            
            if (data.available === false) {
                setFieldError(usernameInput, 'This username is already taken');
                return false;
            } else {
                clearFieldError(usernameInput);
                return true;
            }
        } catch (error) {
            console.error('Error checking username:', error);
            return true;
        }
    }
    
    function validatePassword() {
        const password = passwordInput.value;
        
        if (password.length < 10) {
            setFieldError(passwordInput, 'Password must be at least 10 characters');
            return false;
        } else {
            clearFieldError(passwordInput);
            return true;
        }
    }
    
    function checkPasswordStrength() {
        const password = passwordInput.value;
        const strengthBar = document.querySelector('.strength-bar');
        
        if (!strengthBar) return;
        
        let strength = 0;
        
        // Length check
        if (password.length >= 10) strength++;
        if (password.length >= 14) strength++;
        
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
        } else if (strength <= 3) {
            strengthBar.classList.add('medium');
        } else {
            strengthBar.classList.add('strong');
        }
    }
    
    function checkPasswordMatch() {
        // Password confirmation field was removed
        return true;
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
        // Check if we're removing an applied promo
        if (applyPromoBtn.getAttribute('data-applied') === 'true') {
            removePromoCode();
            return;
        }
        
        const promoCode = promoCodeInput.value.trim();
        
        if (!promoCode) {
            showPromoMessage('Please enter a promo code', 'danger');
            return;
        }
        
        // Show loading state
        applyPromoBtn.disabled = true;
        applyPromoBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Applying...';
        
        // Use embedded validation if available
        if (window.promoValidation && typeof window.promoValidation.validate === 'function') {
            console.log('[PROMO] Using embedded validation');
            
            // Simulate async behavior for UI consistency
            setTimeout(() => {
                const validation = window.promoValidation.validate(promoCode);
                console.log('[PROMO] Validation result:', validation);
                
                if (validation.valid) {
                    showPromoMessage(validation.message, 'success');
                    
                    // Update displayed price if provided
                    if (validation.new_price && displayPrice) {
                        displayPrice.innerHTML = validation.new_price + ' <del class="text-muted ms-2">' + displayPrice.textContent + '</del>';
                        displayPrice.classList.add('text-success');
                    }
                    
                    // Save to session via embedded function
                    window.promoValidation.saveToSession(promoCode, validation);
                    
                    // Also update the form with a hidden field
                    let hiddenInput = document.getElementById('validated_promo_code');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.id = 'validated_promo_code';
                        hiddenInput.name = 'validated_promo_code';
                        form.appendChild(hiddenInput);
                    }
                    hiddenInput.value = promoCode;
                    
                    // Update button to show "Remove" option
                    promoCodeInput.disabled = true;
                    applyPromoBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Remove';
                    applyPromoBtn.classList.remove('btn-success');
                    applyPromoBtn.classList.add('btn-danger');
                    applyPromoBtn.setAttribute('data-applied', 'true');
                    applyPromoBtn.disabled = false; // Make sure it's enabled
                    
                    // Hide the caption
                    const promoCaption = promoCodeInput.closest('.col-md-6').querySelector('small.text-muted');
                    if (promoCaption) {
                        promoCaption.style.display = 'none';
                    }
                } else {
                    showPromoMessage(validation.message || 'Invalid promo code', 'danger');
                }
                
                // Reset button if not already applied
                if (applyPromoBtn.getAttribute('data-applied') !== 'true') {
                    applyPromoBtn.disabled = false;
                    applyPromoBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Apply';
                }
            }, 300); // Small delay to show loading state
            
            return;
        }
        
        // Fallback: If embedded validation not available, show error
        console.error('[PROMO] Embedded validation not available');
        showPromoMessage('Unable to validate promo code. Please continue and it will be applied at checkout.', 'warning');
        
        // Still save the promo code for checkout
        let hiddenInput = document.getElementById('validated_promo_code');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = 'validated_promo_code';
            hiddenInput.name = 'validated_promo_code';
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = promoCode;
        
        // Reset button
        applyPromoBtn.disabled = false;
        applyPromoBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Apply';
    }
    
    function removePromoCode() {
        console.log('[PROMO] Removing promo code');
        
        // Re-enable input
        promoCodeInput.disabled = false;
        promoCodeInput.value = '';
        
        // Reset button
        applyPromoBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Apply';
        applyPromoBtn.classList.remove('btn-danger');
        applyPromoBtn.classList.add('btn-success');
        applyPromoBtn.removeAttribute('data-applied');
        
        // Reset price display
        if (displayPrice) {
            const originalPrice = '$' + (pageData.originalPrice / 100).toFixed(2);
            displayPrice.innerHTML = originalPrice;
            displayPrice.classList.remove('text-success');
        }
        
        // Clear message
        showPromoMessage('Promo code removed', 'info');
        
        // Show the caption again
        const promoCaption = promoCodeInput.closest('.col-md-6').querySelector('small.text-muted');
        if (promoCaption) {
            promoCaption.style.display = '';
        }
        
        // Remove from localStorage
        localStorage.removeItem('promo_code');
        localStorage.removeItem('promo_validation');
        
        // Remove hidden input
        const hiddenInput = document.getElementById('validated_promo_code');
        if (hiddenInput) {
            hiddenInput.remove();
        }
        
        // Clear from form if it exists
        if (promoCodeInput.form) {
            const promoField = promoCodeInput.form.querySelector('input[name="promo_code"]');
            if (promoField && promoField !== promoCodeInput) {
                promoField.value = '';
            }
        }
        
        setTimeout(() => {
            showPromoMessage('', '');
        }, 3000);
    }
    
    function showPromoMessage(message, type) {
        if (!promoMessage) return;
        
        promoMessage.textContent = message;
        promoMessage.className = `mt-2 mb-3 text-${type}`;
        
        // Auto-hide error messages after 5 seconds
        if (type === 'danger') {
            setTimeout(() => {
                promoMessage.textContent = '';
                promoMessage.className = 'mt-2';
            }, 5000);
        }
    }
    
    function verifyReferralCode() {
        // Check if we're removing a verified referral
        if (verifyReferralBtn.getAttribute('data-verified') === 'true') {
            removeReferralCode();
            return;
        }
        
        const referralCode = referralCodeInput.value.trim();
        
        if (!referralCode) {
            showReferralMessage('Please enter a referral code', 'danger');
            return;
        }
        
        // Show loading state
        verifyReferralBtn.disabled = true;
        verifyReferralBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
        
        // Simulate verification (in real implementation, this would be an API call)
        setTimeout(() => {
            // For demo purposes, check if code is at least 4 characters
            if (referralCode.length >= 4) {
                showReferralMessage('Valid referral code! You and your friend will receive rewards.', 'success');
                
                // Update button to show "Remove" option
                referralCodeInput.disabled = true;
                verifyReferralBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Remove';
                verifyReferralBtn.classList.remove('btn-success');
                verifyReferralBtn.classList.add('btn-danger');
                verifyReferralBtn.setAttribute('data-verified', 'true');
                verifyReferralBtn.disabled = false;
                
                // Store verified referral code
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'verified_referral_code';
                hiddenInput.value = referralCode;
                form.appendChild(hiddenInput);
                
                // Hide the caption
                const referralCaption = referralCodeInput.closest('.col-md-6').querySelector('small.text-muted');
                if (referralCaption) {
                    referralCaption.style.display = 'none';
                }
            } else {
                showReferralMessage('Invalid referral code. Please check and try again.', 'danger');
                // Reset button with icon
                verifyReferralBtn.disabled = false;
                verifyReferralBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Verify';
            }
        }, 500);
    }
    
    function removeReferralCode() {
        console.log('[REFERRAL] Removing referral code');
        
        // Re-enable input
        referralCodeInput.disabled = false;
        referralCodeInput.value = '';
        
        // Reset button
        verifyReferralBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Verify';
        verifyReferralBtn.classList.remove('btn-danger');
        verifyReferralBtn.classList.add('btn-success');
        verifyReferralBtn.removeAttribute('data-verified');
        
        // Clear message
        showReferralMessage('Referral code removed', 'info');
        
        // Show the caption again
        const referralCaption = referralCodeInput.closest('.col-md-6').querySelector('small.text-muted');
        if (referralCaption) {
            referralCaption.style.display = '';
        }
        
        // Remove hidden input
        const hiddenInput = form.querySelector('input[name="verified_referral_code"]');
        if (hiddenInput) {
            hiddenInput.remove();
        }
        
        // Clear message after a delay
        setTimeout(() => {
            showReferralMessage('', '');
        }, 3000);
    }
    
    function showReferralMessage(message, type) {
        if (!referralMessage) return;
        
        referralMessage.textContent = message;
        referralMessage.className = `mt-1 mb-3 text-${type}`;
        
        // Auto-hide error messages after 5 seconds
        if (type === 'danger') {
            setTimeout(() => {
                referralMessage.textContent = '';
                referralMessage.className = 'mt-1';
            }, 5000);
        }
    }
    
    // Removed validation functions - all validation is server-side
    
    function handleFormSubmit(e) {
        console.log('[FORM] Form submit handler triggered');
        
        // Basic client-side validation
        const birthMonth = document.getElementById('birth_month');
        const birthDay = document.getElementById('birth_day');
        const birthYear = document.getElementById('birth_year');
        
        console.log('[FORM] Birth fields:', {
            month: birthMonth?.value,
            day: birthDay?.value,
            year: birthYear?.value
        });
        
        // Remove any existing error alerts first
        const existingAlerts = document.querySelectorAll('.alert-validation');
        existingAlerts.forEach(alert => alert.remove());
        
        // Check if birthday fields are selected
        if (birthMonth && birthDay && birthYear) {
            if (!birthMonth.value || !birthDay.value || !birthYear.value) {
                e.preventDefault();
                console.log('[FORM] Validation failed: incomplete birth date');
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger alert-dismissible fade show alert-validation mb-3';
                errorDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Please select your complete birth date
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Find the main card and insert error inside it at the top
                const mainCard = document.querySelector('.card.p-3.p-lg-5');
                if (mainCard) {
                    mainCard.insertBefore(errorDiv, mainCard.firstChild);
                } else {
                    // Fallback: insert at top of form
                    form.insertBefore(errorDiv, form.firstChild);
                }
                
                // Scroll to error
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Re-enable submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.remove('btn-loading');
                    submitBtn.disabled = false;
                }
                
                return false;
            }
            
            // Check for future date
            const selectedDate = new Date(birthYear.value, birthMonth.value - 1, birthDay.value);
            const today = new Date();
            today.setHours(23, 59, 59, 999); // Set to end of today to be more lenient
            
            if (selectedDate > today) {
                e.preventDefault();
                console.log('[FORM] Validation failed: future birth date');
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger alert-dismissible fade show alert-validation mb-3';
                errorDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Birth date cannot be in the future
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Find the main card and insert error inside it at the top
                const mainCard = document.querySelector('.card.p-3.p-lg-5');
                if (mainCard) {
                    mainCard.insertBefore(errorDiv, mainCard.firstChild);
                } else {
                    // Fallback: insert at top of form
                    form.insertBefore(errorDiv, form.firstChild);
                }
                
                // Scroll to error
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Re-enable submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.remove('btn-loading');
                    submitBtn.disabled = false;
                }
                
                return false;
            }
        }
        
        console.log('[FORM] All client-side validation passed, submitting form');
        
        // Show loading state on submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        }
        
        // Let the form submit naturally
        return true;
    }
});