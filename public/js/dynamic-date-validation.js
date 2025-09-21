/**
 * Dynamic Date Validation for Birthday Dropdowns
 * Provides intelligent date selection with month/leap year awareness
 * Can be toggled on/off via various methods
 */

(function() {
    'use strict';
    
    // Configuration and feature detection
    const DynamicDateValidation = {
        // Debug flag - set to true to enable console logging
        debug: false,
        // Check if feature should be enabled
        isEnabled: function() {
            // Check URL parameter override (only if present)
            const urlParams = new URLSearchParams(window.location.search);
            const urlOverride = urlParams.get('dynamic_dates');
            if (urlOverride === 'off' || urlOverride === 'false') return false;
            if (urlOverride === 'on' || urlOverride === 'true') return true;
            
            // Check if explicitly set in page config (this is the production setting)
            if (typeof window.DYNAMIC_DATE_VALIDATION !== 'undefined') {
                return window.DYNAMIC_DATE_VALIDATION;
            }
            
            // Check browser compatibility (fallback for old browsers)
            if (!this.checkBrowserSupport()) {
                if (this.debug) console.log('Dynamic date validation disabled: Browser not supported');
                return false;
            }
            
            // Default: enabled (production default)
            return true;
        },
        
        // Check browser support
        checkBrowserSupport: function() {
            // Check for required features
            return (
                'querySelector' in document &&
                'addEventListener' in window &&
                'forEach' in Array.prototype
            );
        },
        
        // Days in each month
        daysInMonth: {
            '01': 31, '02': 28, '03': 31, '04': 30,
            '05': 31, '06': 30, '07': 31, '08': 31,
            '09': 30, '10': 31, '11': 30, '12': 31
        },
        
        // Check if year is leap year
        isLeapYear: function(year) {
            year = parseInt(year);
            return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
        },
        
        // Get maximum days for month/year combination
        getMaxDays: function(month, year) {
            if (!month) return 31; // Default to max if no month selected
            
            let maxDays = this.daysInMonth[month] || 31;
            
            // Handle February - optimistic approach
            if (month === '02') {
                if (this.debug) {
                    console.log('February detected. Year value:', year, 'Type:', typeof year, 'Empty?:', (!year || year === ''));
                }
                
                if (!year || year === '') {
                    // No year selected yet - be optimistic and show 29
                    if (this.debug) console.log('No year selected - returning 29 days');
                    maxDays = 29;
                } else if (this.isLeapYear(year)) {
                    // Leap year - show 29
                    if (this.debug) console.log('Leap year detected - returning 29 days');
                    maxDays = 29;
                } else {
                    // Non-leap year - restrict to 28
                    if (this.debug) console.log('Non-leap year - returning 28 days');
                    maxDays = 28;
                }
            }
            
            if (this.debug) {
                console.log('getMaxDays returning:', maxDays, 'for month:', month, 'year:', year);
            }
            return maxDays;
        },
        
        // Initialize the dynamic validation
        init: function() {
            // Check for debug mode via URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('debug_dates') === 'true' || urlParams.get('debug') === 'true') {
                this.debug = true;
                console.log('Dynamic Date Validation: Debug mode enabled');
            }
            
            // Check if toggle should be shown (only if dynamic_dates is in URL)
            this.showToggle = urlParams.has('dynamic_dates');
            
            // Exit if not enabled
            if (!this.isEnabled()) {
                // Only show toggle if URL param exists
                if (this.showToggle) {
                    this.addToggleLink();
                }
                return;
            }
            
            // Get dropdown elements
            this.monthSelect = document.getElementById('birth_month');
            this.daySelect = document.getElementById('birth_day');
            this.yearSelect = document.getElementById('birth_year');
            
            // Exit if elements not found
            if (!this.monthSelect || !this.daySelect || !this.yearSelect) {
                return;
            }
            
            // Store original day options for restoration
            this.originalDayOptions = Array.from(this.daySelect.options).map(option => ({
                value: option.value,
                text: option.text,
                selected: option.selected
            }));
            
            // Add event listeners
            this.monthSelect.addEventListener('change', this.handleMonthChange.bind(this));
            this.yearSelect.addEventListener('change', this.handleYearChange.bind(this));
            
            // Only add toggle link if URL param exists
            if (this.showToggle) {
                this.addToggleLink();
            }
            
            // Initial adjustment if month is already selected
            if (this.monthSelect.value) {
                this.updateDayOptions();
            }
            
            // Add accessibility announcement
            this.addAriaAnnouncement();
        },
        
        // Handle month change
        handleMonthChange: function() {
            this.updateDayOptions();
        },
        
        // Handle year change (for leap year detection)
        handleYearChange: function() {
            // Only update if February is selected
            if (this.monthSelect.value === '02') {
                // Check if we need to adjust for non-leap year
                const currentDay = this.daySelect.value;
                if (currentDay === '29' && !this.isLeapYear(this.yearSelect.value)) {
                    // User had Feb 29 selected but chose a non-leap year
                    this.updateDayOptionsWithMessage();
                } else {
                    this.updateDayOptions();
                }
            }
        },
        
        // Update day options with special message for Feb 29 adjustment
        updateDayOptionsWithMessage: function() {
            // Update the days
            this.updateDayOptions();
            
            // Show a friendly message about the adjustment
            this.announceChange('Date adjusted to February 28 (not a leap year)');
            
            // Optionally, show a temporary visual indicator
            this.showTemporaryMessage('Adjusted to Feb 28 - not a leap year');
        },
        
        // Show temporary message near the date fields
        showTemporaryMessage: function(message) {
            // Check if message element exists
            let messageEl = document.getElementById('date-adjustment-message');
            if (!messageEl) {
                messageEl = document.createElement('div');
                messageEl.id = 'date-adjustment-message';
                messageEl.className = 'alert alert-info alert-sm mt-2 fade show';
                messageEl.style.fontSize = '0.875rem';
                messageEl.style.padding = '0.5rem';
                
                // Insert after the date row
                const dateRow = document.querySelector('.date-row');
                if (dateRow && dateRow.parentNode) {
                    dateRow.parentNode.insertBefore(messageEl, dateRow.nextSibling);
                }
            }
            
            // Set message and show
            messageEl.innerHTML = `<i class="bi bi-info-circle me-1"></i> ${message}`;
            messageEl.style.display = 'block';
            
            // Fade out after 3 seconds
            setTimeout(() => {
                messageEl.classList.remove('show');
                setTimeout(() => {
                    messageEl.style.display = 'none';
                    messageEl.classList.add('show'); // Reset for next time
                }, 150);
            }, 3000);
        },
        
        // Update day dropdown options
        updateDayOptions: function() {
            const selectedMonth = this.monthSelect.value;
            const selectedYear = this.yearSelect.value;
            const currentDay = this.daySelect.value;
            
            // Get max days for selected month/year
            const maxDays = this.getMaxDays(selectedMonth, selectedYear);
            
            // Store current selection
            let dayToSelect = currentDay;
            
            // If current day exceeds max, adjust to last valid day
            if (currentDay && parseInt(currentDay) > maxDays) {
                dayToSelect = String(maxDays).padStart(2, '0');
                // Only announce for non-February months (February gets special handling)
                if (selectedMonth !== '02') {
                    this.announceChange(`Day adjusted to ${maxDays} for selected month`);
                }
            }
            
            // Clear current options (except placeholder)
            while (this.daySelect.options.length > 1) {
                this.daySelect.remove(1);
            }
            
            // Add days up to maximum for selected month
            for (let i = 1; i <= maxDays; i++) {
                const option = document.createElement('option');
                option.value = String(i).padStart(2, '0');
                option.text = String(i);
                
                // Restore selection if it matches
                if (option.value === dayToSelect) {
                    option.selected = true;
                }
                
                this.daySelect.add(option);
            }
            
            // Verify the options were added (for debugging)
            if (this.debug && selectedMonth === '02') {
                console.log(`February days: Added ${this.daySelect.options.length - 1} days (should be ${maxDays})`);
                // Check what the last option actually is
                const lastOption = this.daySelect.options[this.daySelect.options.length - 1];
                console.log('Last day option value:', lastOption ? lastOption.value : 'none', 'text:', lastOption ? lastOption.text : 'none');
                
                // Check again after a brief moment to see if something else is changing it
                const self = this;
                setTimeout(function() {
                    console.log('AFTER DELAY - Days in dropdown:', self.daySelect.options.length - 1);
                    const lastOptionAfter = self.daySelect.options[self.daySelect.options.length - 1];
                    console.log('AFTER DELAY - Last option:', lastOptionAfter ? lastOptionAfter.value : 'none');
                }, 100);
            }
            
            // If we adjusted the day, update hidden field
            if (dayToSelect !== currentDay && dayToSelect) {
                this.daySelect.value = dayToSelect;
                this.triggerBirthdayUpdate();
            }
            
            // Add smooth transition effect
            this.addTransitionEffect();
        },
        
        // Restore original day options (for toggle off)
        restoreOriginalDays: function() {
            // Clear current options
            while (this.daySelect.options.length > 0) {
                this.daySelect.remove(0);
            }
            
            // Restore original options
            this.originalDayOptions.forEach(optionData => {
                const option = document.createElement('option');
                option.value = optionData.value;
                option.text = optionData.text;
                if (optionData.selected) {
                    option.selected = true;
                }
                this.daySelect.add(option);
            });
        },
        
        // Add toggle link for users
        addToggleLink: function() {
            const dateRow = document.querySelector('.date-row');
            if (!dateRow) return;
            
            // Check if toggle already exists
            if (document.getElementById('date-validation-toggle')) return;
            
            const toggleContainer = document.createElement('div');
            toggleContainer.className = 'col-12 mt-2';
            toggleContainer.innerHTML = `
                <small class="text-muted">
                    <a href="#" id="date-validation-toggle" class="text-decoration-none">
                        <i class="bi bi-toggle-${this.isEnabled() ? 'on' : 'off'}"></i>
                        ${this.isEnabled() ? 'Using smart date selection' : 'Using simple date selection'}
                    </a>
                </small>
            `;
            
            // Insert after date row
            dateRow.parentNode.insertBefore(toggleContainer, dateRow.nextSibling);
            
            // Add click handler
            document.getElementById('date-validation-toggle').addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleMode();
            });
        },
        
        // Toggle between dynamic and static mode
        toggleMode: function() {
            const currentMode = this.isEnabled();
            
            // Update URL to opposite mode
            const url = new URL(window.location);
            url.searchParams.set('dynamic_dates', currentMode ? 'off' : 'on');
            window.history.replaceState({}, '', url);
            
            // Reload to apply changes (simplest approach)
            window.location.reload();
        },
        
        // Trigger birthday field update
        triggerBirthdayUpdate: function() {
            // Trigger change event to update hidden birthday field
            const event = new Event('change', { bubbles: true });
            this.daySelect.dispatchEvent(event);
        },
        
        // Add smooth transition effect
        addTransitionEffect: function() {
            this.daySelect.style.transition = 'none';
            this.daySelect.style.opacity = '0.7';
            
            setTimeout(() => {
                this.daySelect.style.transition = 'opacity 0.2s ease';
                this.daySelect.style.opacity = '1';
            }, 50);
        },
        
        // Add ARIA announcement for accessibility
        addAriaAnnouncement: function() {
            // Create announcement region if it doesn't exist
            let announcer = document.getElementById('date-validation-announcer');
            if (!announcer) {
                announcer = document.createElement('div');
                announcer.id = 'date-validation-announcer';
                announcer.setAttribute('role', 'status');
                announcer.setAttribute('aria-live', 'polite');
                announcer.setAttribute('aria-atomic', 'true');
                announcer.className = 'visually-hidden';
                document.body.appendChild(announcer);
            }
        },
        
        // Announce changes for screen readers
        announceChange: function(message) {
            const announcer = document.getElementById('date-validation-announcer');
            if (announcer) {
                announcer.textContent = message;
                // Clear after announcement
                setTimeout(() => {
                    announcer.textContent = '';
                }, 1000);
            }
        }
    };
    
    // Initialize when DOM is ready with a slight delay to ensure we run after other scripts
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Add a small delay to ensure we initialize after other scripts
            setTimeout(function() {
                DynamicDateValidation.init();
            }, 250);
        });
    } else {
        // DOM already loaded - still add delay
        setTimeout(function() {
            DynamicDateValidation.init();
        }, 250);
    }
    
    // Expose to global scope for manual control
    window.DynamicDateValidation = DynamicDateValidation;
})();