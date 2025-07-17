# Google Address Autocomplete Implementation

This directory contains the archived Google Address Autocomplete implementation that was removed from the Birthday Gold application on 2025-07-15.

## Implementation Details

The feature was implemented in commit `0b5c761` (Fix address autocomplete functionality) and provided Google Places API integration for automatic address completion on the following pages:
- `/myaccount/settings.php` - User settings page
- `/myaccount/profile.php` - User profile page

### Files Modified

1. **myaccount/settings.php**
   - Added ID attribute to state select field: `id="inputState"`
   - Added Google Maps API script loading in footer
   - Added address-autocomplete.js script inclusion

2. **myaccount/profile.php**
   - Added ID attribute to state select field: `id="inputprofile_State"`
   - Added Google Maps API script loading in footer
   - Added address-autocomplete.js script inclusion

3. **public/js/address-autocomplete.js** (NEW FILE)
   - Complete implementation of the AddressAutocomplete class
   - Handles Google Places API integration
   - Auto-populates city, state, and zip code fields
   - Includes floating label support
   - Has profile and settings page specific configurations

### How It Worked

1. When users typed in the address field, Google Places API would show autocomplete suggestions
2. Upon selecting an address, the script would:
   - Parse the address components
   - Populate the street address field with just the street number and name
   - Auto-fill the city field
   - Select the appropriate state from the dropdown
   - Fill in the zip code

### Configuration

The implementation used the Google API key from `$sitesettings['GOOGLEAPI']['mainkey']` and required the Places library.

### Issues Encountered

The implementation was not working as needed, which led to its removal. The specific issues were not documented in the commit history.

## Restoration Instructions

To restore this functionality:

1. Copy `address-autocomplete.js` back to `/public/js/`
2. Add the Google Maps API script and address-autocomplete.js script to the pages where needed
3. Ensure the form fields have the correct ID attributes
4. Configure the Google API key in site settings

## Code Changes to Revert

The changes made in commit `0b5c761` need to be reverted from:
- `myaccount/settings.php`
- `myaccount/profile.php`

And the file to be removed:
- `public/js/address-autocomplete.js`