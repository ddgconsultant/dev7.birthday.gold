/**
 * Address Autocomplete Functionality
 * Provides Google Places Autocomplete for address fields with automatic city/state/zip population
 * Used on /myaccount/profile and /myaccount/settings pages
 */

class AddressAutocomplete {
    constructor(options = {}) {
        this.addressFieldId = options.addressFieldId || 'inputprofile_mailing_address';
        this.cityFieldId = options.cityFieldId || 'inputprofile_City';
        this.stateFieldId = options.stateFieldId || 'inputprofile_State';
        this.zipFieldId = options.zipFieldId || 'inputprofile_zip_code';
        this.countryRestriction = options.countryRestriction || 'us';
        this.autocomplete = null;
        this.isInitialized = false;
        
        // State abbreviation to full name mapping
        this.stateMap = {
            'AL': 'Alabama', 'AK': 'Alaska', 'AZ': 'Arizona', 'AR': 'Arkansas',
            'CA': 'California', 'CO': 'Colorado', 'CT': 'Connecticut', 'DE': 'Delaware',
            'FL': 'Florida', 'GA': 'Georgia', 'HI': 'Hawaii', 'ID': 'Idaho',
            'IL': 'Illinois', 'IN': 'Indiana', 'IA': 'Iowa', 'KS': 'Kansas',
            'KY': 'Kentucky', 'LA': 'Louisiana', 'ME': 'Maine', 'MD': 'Maryland',
            'MA': 'Massachusetts', 'MI': 'Michigan', 'MN': 'Minnesota', 'MS': 'Mississippi',
            'MO': 'Missouri', 'MT': 'Montana', 'NE': 'Nebraska', 'NV': 'Nevada',
            'NH': 'New Hampshire', 'NJ': 'New Jersey', 'NM': 'New Mexico', 'NY': 'New York',
            'NC': 'North Carolina', 'ND': 'North Dakota', 'OH': 'Ohio', 'OK': 'Oklahoma',
            'OR': 'Oregon', 'PA': 'Pennsylvania', 'RI': 'Rhode Island', 'SC': 'South Carolina',
            'SD': 'South Dakota', 'TN': 'Tennessee', 'TX': 'Texas', 'UT': 'Utah',
            'VT': 'Vermont', 'VA': 'Virginia', 'WA': 'Washington', 'WV': 'West Virginia',
            'WI': 'Wisconsin', 'WY': 'Wyoming', 'DC': 'District of Columbia'
        };
    }

    /**
     * Initialize the autocomplete functionality
     */
    init() {
        // Check if Google Maps API is loaded
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            console.error('Google Maps Places API is not loaded');
            return false;
        }

        const addressField = document.getElementById(this.addressFieldId);
        if (!addressField) {
            console.error(`Address field with ID "${this.addressFieldId}" not found`);
            return false;
        }

        // Check if PlaceAutocompleteElement is available (new API)
        if (google.maps.places.PlaceAutocompleteElement) {
            return this.initPlaceAutocompleteElement(addressField);
        } else {
            // Fallback to legacy Autocomplete
            return this.initLegacyAutocomplete(addressField);
        }
    }

    /**
     * Initialize using the new PlaceAutocompleteElement API
     */
    initPlaceAutocompleteElement(addressField) {
        try {
            // Create a wrapper div for the new element
            const wrapper = document.createElement('div');
            wrapper.className = 'place-autocomplete-wrapper';
            addressField.parentNode.insertBefore(wrapper, addressField);
            wrapper.appendChild(addressField);

            // Create the PlaceAutocompleteElement
            this.autocompleteElement = new google.maps.places.PlaceAutocompleteElement({
                input: addressField,
                types: ['address'],
                componentRestrictions: { country: this.countryRestriction },
                fields: ['addressComponents', 'location', 'formattedAddress']
            });

            // Listen for place selection
            this.autocompleteElement.addEventListener('gmp-placeselect', (event) => {
                this.isProcessingSelection = true;
                this.handlePlaceSelection(event.place);
                setTimeout(() => {
                    this.isProcessingSelection = false;
                }, 500);
            });

            // Store the last selected address
            this.lastSelectedAddress = '';
            this.isProcessingSelection = false;

            // Prevent form submission on Enter
            this.addEnterKeyPrevention(addressField);
            
            // Monitor and prevent Google from overwriting the field
            this.addFieldMonitoring(addressField);

            // Add CSS fixes
            this.addAutocompleteFix();
            this.isInitialized = true;
            return true;
        } catch (error) {
            console.error('Failed to initialize PlaceAutocompleteElement:', error);
            // Fallback to legacy
            return this.initLegacyAutocomplete(addressField);
        }
    }

    /**
     * Initialize using the legacy Autocomplete API
     */
    initLegacyAutocomplete(addressField) {
        // Create autocomplete instance
        this.autocomplete = new google.maps.places.Autocomplete(addressField, {
            types: ['address'],
            componentRestrictions: { country: this.countryRestriction },
            fields: ['address_components', 'geometry'] // Only request needed fields
        });

        // Store the last selected address to prevent Google from overwriting it
        this.lastSelectedAddress = '';
        this.isProcessingSelection = false;
        
        // Add place changed listener
        this.autocomplete.addListener('place_changed', () => {
            this.isProcessingSelection = true;
            this.fillInAddress();
            // Reset flag after a delay
            setTimeout(() => {
                this.isProcessingSelection = false;
            }, 500);
        });
        
        // Prevent form submission on Enter
        this.addEnterKeyPrevention(addressField);
        
        // Monitor and prevent Google from overwriting the field
        this.addFieldMonitoring(addressField);

        // Add CSS fix for z-index in modals/overlays
        this.addAutocompleteFix();

        this.isInitialized = true;
        return true;
    }

    /**
     * Add Enter key prevention for autocomplete
     */
    addEnterKeyPrevention(addressField) {
        addressField.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const pacContainer = document.querySelector('.pac-container');
                if (pacContainer && pacContainer.style.display !== 'none') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        });
    }

    /**
     * Add field monitoring to prevent Google from overwriting with full address
     */
    addFieldMonitoring(addressField) {
        // Monitor for Google overwriting the field value
        let previousValue = '';
        addressField.addEventListener('input', (e) => {
            // Skip if we're currently processing a selection
            if (this.isProcessingSelection) {
                previousValue = addressField.value;
                return;
            }
            
            const currentValue = addressField.value;
            
            // If the value suddenly includes a comma (full address format) but our stored value doesn't
            if (currentValue.includes(',') && this.lastSelectedAddress && !this.lastSelectedAddress.includes(',')) {
                // Revert to our stored address
                addressField.value = this.lastSelectedAddress;
                this.triggerFloatingLabelUpdate(addressField);
            }
            
            previousValue = addressField.value;
        });
        
        // Additional protection on blur
        addressField.addEventListener('blur', () => {
            setTimeout(() => {
                if (this.lastSelectedAddress && addressField.value !== this.lastSelectedAddress) {
                    // Check if it's the full address format
                    if (addressField.value.includes(',') && !this.lastSelectedAddress.includes(',')) {
                        addressField.value = this.lastSelectedAddress;
                        this.triggerFloatingLabelUpdate(addressField);
                    }
                }
            }, 100);
        });
    }

    /**
     * Handle place selection from PlaceAutocompleteElement
     */
    handlePlaceSelection(place) {
        if (!place || !place.location) {
            console.warn('No details available for the selected place');
            return;
        }

        // Debug logging
        console.log('=== ADDRESS SELECTION DEBUG (PlaceAutocompleteElement) ===');
        console.log('Place selected:', place);
        console.log('Address components:', place.addressComponents);
        console.log('Formatted address:', place.formattedAddress);

        // Store current scroll position
        const scrollX = window.scrollX;
        const scrollY = window.scrollY;

        // Clear only city, state, and zip - NOT the address field
        this.clearAddressFields();

        // Extract components from the new API format
        let streetNumber = '';
        let route = '';
        let city = '';
        let state = '';
        let zipCode = '';

        if (place.addressComponents) {
            place.addressComponents.forEach(component => {
                const types = component.types;
                if (types.includes('street_number')) {
                    streetNumber = component.shortText || component.longText;
                } else if (types.includes('route')) {
                    route = component.longText || component.shortText;
                } else if (types.includes('locality')) {
                    city = component.longText || component.shortText;
                } else if (types.includes('administrative_area_level_1')) {
                    state = component.shortText || component.longText;
                } else if (types.includes('postal_code')) {
                    zipCode = component.shortText || component.longText;
                }
            });
        }

        // Populate the fields
        this.populateFields({ streetNumber, route, city, state, zipCode });

        // Restore scroll position
        window.scrollTo(scrollX, scrollY);
    }

    /**
     * Fill in the address fields when a place is selected (legacy)
     */
    fillInAddress() {
        const place = this.autocomplete.getPlace();

        if (!place.geometry) {
            console.warn('No details available for the selected place');
            return;
        }

        // Debug logging
        console.log('=== ADDRESS SELECTION DEBUG (Legacy) ===');
        console.log('Place selected:', place);
        console.log('Current address field value:', document.getElementById(this.addressFieldId)?.value);
        console.log('Last selected address:', this.lastSelectedAddress);

        // Store current scroll position
        const scrollX = window.scrollX;
        const scrollY = window.scrollY;

        // Prevent page jump by focusing on the address field
        const addressFieldElement = document.getElementById(this.addressFieldId);
        if (addressFieldElement) {
            addressFieldElement.focus();
        }

        // Clear only city, state, and zip - NOT the address field
        this.clearAddressFields();

        // Force hide the autocomplete dropdown
        setTimeout(() => {
            const pacContainer = document.querySelector('.pac-container');
            if (pacContainer) {
                pacContainer.style.display = 'none';
            }
        }, 100);

        // Extract address components
        let streetNumber = '';
        let route = '';
        let city = '';
        let state = '';
        let zipCode = '';

        for (const component of place.address_components) {
            const addressType = component.types[0];

            if (addressType === 'street_number') {
                streetNumber = component.short_name;
            } else if (addressType === 'route') {
                route = component.long_name;
            } else if (addressType === 'locality') {
                city = component.long_name;
            } else if (addressType === 'administrative_area_level_1') {
                state = component.short_name;
            } else if (addressType === 'postal_code') {
                zipCode = component.short_name;
            }
        }

        // Populate the fields
        this.populateFields({ streetNumber, route, city, state, zipCode });

        // Restore scroll position
        window.scrollTo(scrollX, scrollY);
    }

    /**
     * Populate form fields with extracted address data
     */
    populateFields({ streetNumber, route, city, state, zipCode }) {
        console.log('Extracted values:', { streetNumber, route, city, state, zipCode });

        // Populate fields - try ID first, then name
        const addressField = document.getElementById(this.addressFieldId) || 
                           document.querySelector(`input[name="${this.addressFieldId}"]`);
        const cityField = document.getElementById(this.cityFieldId) || 
                         document.querySelector(`input[name="${this.cityFieldId}"]`);
        // For state field, check for select by name first (since IDs are missing on select elements)
        let stateField = document.querySelector(`select[name="${this.stateFieldId}"]`) ||
                        document.getElementById(this.stateFieldId) || 
                        document.querySelector(`input[name="${this.stateFieldId}"]`);
        const zipField = document.getElementById(this.zipFieldId) || 
                        document.querySelector(`input[name="${this.zipFieldId}"]`);

        console.log('Found fields:', {
            addressField: addressField ? 'YES' : 'NO',
            cityField: cityField ? 'YES' : 'NO',
            stateField: stateField ? 'YES' : 'NO',
            zipField: zipField ? 'YES' : 'NO'
        });

        // Set address (street number + route)
        if (addressField) {
            const fullStreetAddress = streetNumber ? `${streetNumber} ${route}` : route;
            // Only update if we have a valid street address
            if (fullStreetAddress && fullStreetAddress.trim()) {
                // Store the address we want to keep
                this.lastSelectedAddress = fullStreetAddress;
                addressField.value = fullStreetAddress;
                this.triggerFloatingLabelUpdate(addressField);
                
                // Multiple checks to prevent Google from overwriting
                const enforceAddress = () => {
                    if (addressField.value !== fullStreetAddress) {
                        addressField.value = fullStreetAddress;
                        this.triggerFloatingLabelUpdate(addressField);
                    }
                };
                
                // Check multiple times over a short period
                setTimeout(enforceAddress, 50);
                setTimeout(enforceAddress, 100);
                setTimeout(enforceAddress, 200);
                setTimeout(enforceAddress, 300);
            } else if (!streetNumber && route) {
                // If there's no street number but there is a route, just use the route
                // Don't lose the previous street number if the new address doesn't have one
                if (this.lastSelectedAddress && this.lastSelectedAddress.includes(' ')) {
                    // Extract the street number from the last selected address
                    const lastParts = this.lastSelectedAddress.split(' ');
                    const lastStreetNumber = lastParts[0];
                    // Check if the first part is actually a number
                    if (/^\d+/.test(lastStreetNumber)) {
                        // Keep the old street number with the new route
                        const newAddress = `${lastStreetNumber} ${route}`;
                        this.lastSelectedAddress = newAddress;
                        addressField.value = newAddress;
                        this.triggerFloatingLabelUpdate(addressField);
                    } else {
                        // No previous street number, just use the route
                        this.lastSelectedAddress = route;
                        addressField.value = route;
                        this.triggerFloatingLabelUpdate(addressField);
                    }
                } else {
                    // No previous address or street number, just use the route
                    this.lastSelectedAddress = route;
                    addressField.value = route;
                    this.triggerFloatingLabelUpdate(addressField);
                }
            }
        }

        // Set city
        if (city) {
            if (cityField) {
                console.log('Setting city field to:', city);
                cityField.value = city;
                this.triggerFloatingLabelUpdate(cityField);
            } else {
                console.error('City field not found!');
            }
        }

        // Set state (handle both input and select fields)
        if (state) {
            if (stateField) {
                console.log('Setting state field to:', state, 'Field type:', stateField.tagName);
                if (stateField.tagName === 'SELECT') {
                    // Convert state abbreviation to full name
                    const stateName = this.stateMap[state] || state;
                    console.log('Looking for state:', stateName, '(from abbreviation:', state + ')');
                    
                    // For select fields, find the matching option
                    let found = false;
                    for (let option of stateField.options) {
                        if (option.value === stateName || option.text === stateName || option.value === state) {
                            stateField.value = option.value;
                            found = true;
                            console.log('Found matching state option:', option.value);
                            break;
                        }
                    }
                    if (!found) {
                        console.error('State option not found for:', stateName, 'or', state);
                    }
                } else {
                    stateField.value = state;
                }
                this.triggerFloatingLabelUpdate(stateField);
            } else {
                console.error('State field not found!');
            }
        }

        // Set zip code
        if (zipCode) {
            if (zipField) {
                console.log('Setting zip field to:', zipCode);
                zipField.value = zipCode;
                this.triggerFloatingLabelUpdate(zipField);
            } else {
                console.error('Zip field not found!');
            }
        }

        // Trigger change events for any form validation
        this.triggerChangeEvents();

        // Trigger change events for any form validation
        this.triggerChangeEvents();
    }

    /**
     * Clear all address fields
     */
    clearAddressFields() {
        // DO NOT clear the address field itself - only clear city, state, and zip
        // This prevents the truncation issue when selecting different addresses
        const fields = [
            { id: this.cityFieldId, selector: `input[name="${this.cityFieldId}"]` },
            { id: this.stateFieldId, selector: `select[name="${this.stateFieldId}"], input[name="${this.stateFieldId}"]` },
            { id: this.zipFieldId, selector: `input[name="${this.zipFieldId}"]` }
        ];

        fields.forEach(fieldInfo => {
            const field = document.getElementById(fieldInfo.id) || document.querySelector(fieldInfo.selector);
            if (field) {
                // For select fields, reset to first option
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else {
                    field.value = '';
                }
                this.triggerFloatingLabelUpdate(field);
            }
        });
    }

    /**
     * Trigger floating label update for a field
     */
    triggerFloatingLabelUpdate(field) {
        if (!field) return;

        // Add/remove 'has-value' class for floating labels
        const wrapper = field.closest('.floating-label-group');
        if (wrapper) {
            if (field.value) {
                field.classList.add('has-value');
            } else {
                field.classList.remove('has-value');
            }
        }

        // Trigger input event for floating label animation
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    /**
     * Trigger change events on all fields
     */
    triggerChangeEvents() {
        const fields = [
            this.addressFieldId,
            this.cityFieldId,
            this.stateFieldId,
            this.zipFieldId
        ];

        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    /**
     * Add CSS fix for autocomplete dropdown z-index
     */
    addAutocompleteFix() {
        if (document.getElementById('autocomplete-fix-style')) {
            return; // Already added
        }

        const style = document.createElement('style');
        style.id = 'autocomplete-fix-style';
        style.textContent = `
            /* Fix Google autocomplete z-index for modals and overlays */
            .pac-container {
                z-index: 10000 !important;
            }
            
            /* Style autocomplete dropdown */
            .pac-container {
                font-family: inherit;
                border-radius: 0.375rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                border: 1px solid #dee2e6;
                margin-top: 2px;
            }
            
            /* PlaceAutocompleteElement styles */
            .place-autocomplete-wrapper {
                position: relative;
            }
            
            gmp-place-autocomplete {
                width: 100%;
            }
            
            .pac-item {
                padding: 0.5rem 1rem;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            
            .pac-item:hover {
                background-color: #f8f9fa;
            }
            
            .pac-item-selected {
                background-color: #e9ecef;
            }
            
            .pac-matched {
                font-weight: 600;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Destroy the autocomplete instance
     */
    destroy() {
        if (this.autocomplete) {
            google.maps.event.clearInstanceListeners(this.autocomplete);
            this.autocomplete = null;
        }
        if (this.autocompleteElement) {
            // Remove event listeners from PlaceAutocompleteElement
            this.autocompleteElement = null;
        }
        this.isInitialized = false;
    }
}

/**
 * Initialize autocomplete for profile page
 */
function initProfileAddressAutocomplete() {
    const profileAutocomplete = new AddressAutocomplete({
        addressFieldId: 'inputprofile_mailing_address',
        cityFieldId: 'inputprofile_City',
        stateFieldId: 'inputprofile_State',
        zipFieldId: 'inputprofile_zip_code'
    });

    profileAutocomplete.init();
    return profileAutocomplete;
}

/**
 * Initialize autocomplete for settings page
 */
function initSettingsAddressAutocomplete() {
    const settingsAutocomplete = new AddressAutocomplete({
        addressFieldId: 'inputmailing_address',
        cityFieldId: 'inputCity',
        stateFieldId: 'inputState',  // This will be found by name since there's no ID
        zipFieldId: 'inputzip_code'
    });

    settingsAutocomplete.init();
    return settingsAutocomplete;
}

/**
 * Global callback for Google Maps API
 */
window.initAddressAutocomplete = function() {
    // Check which page we're on based on field IDs
    if (document.getElementById('inputprofile_mailing_address')) {
        window.addressAutocomplete = initProfileAddressAutocomplete();
    } else if (document.getElementById('inputmailing_address')) {
        window.addressAutocomplete = initSettingsAddressAutocomplete();
    }
};

// Auto-initialize based on page if Google Maps is already loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only auto-initialize if Google Maps is already loaded
    // Otherwise, wait for the callback
    if (typeof google !== 'undefined' && google.maps && google.maps.places) {
        window.initAddressAutocomplete();
    }
});

// Debug helper function
window.testAddressFields = function() {
    console.log('Testing field detection...');
    const currentPage = document.getElementById('inputprofile_mailing_address') ? 'profile' : 'settings';
    console.log('Current page:', currentPage);
    
    let tests;
    if (currentPage === 'profile') {
        tests = [
            { id: 'inputprofile_mailing_address', selector: 'input[name="inputprofile_mailing_address"]' },
            { id: 'inputprofile_City', selector: 'input[name="inputprofile_City"]' },
            { id: 'inputprofile_State', selector: 'select[name="inputprofile_State"]' },
            { id: 'inputprofile_zip_code', selector: 'input[name="inputprofile_zip_code"]' }
        ];
    } else {
        tests = [
            { id: 'inputmailing_address', selector: 'input[name="inputmailing_address"]' },
            { id: 'inputCity', selector: 'input[name="inputCity"]' },
            { id: 'inputState', selector: 'select[name="inputState"]' },
            { id: 'inputzip_code', selector: 'input[name="inputzip_code"]' }
        ];
    }
    
    tests.forEach(test => {
        const byId = document.getElementById(test.id);
        const bySelector = document.querySelector(test.selector);
        console.log(`${test.id}: ID=${byId ? 'FOUND' : 'NOT FOUND'}, Selector=${bySelector ? 'FOUND' : 'NOT FOUND'}`);
        if (bySelector) {
            console.log(`  -> Tag: ${bySelector.tagName}, Name: ${bySelector.name}, Value: "${bySelector.value}"`);
        }
    });
};