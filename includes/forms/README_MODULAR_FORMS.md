# Birthday Gold Modular Form System

This directory contains reusable form modules for the createaccount.php page. The modular system allows easy mixing and matching of form sections based on account type.

## Directory Structure

```
/includes/forms/
├── name_birthday.inc.php          # Common name and birthday fields
├── business_info.inc.php          # Business-specific fields
├── family_members.inc.php         # Family member fields
├── organization_info.inc.php      # Organization fields (example)
├── account_info.inc.php           # Common account fields
├── process_name_birthday.inc.php  # Name/birthday validation
├── process_business_info.inc.php  # Business data processing
├── process_family_members.inc.php # Family data processing
├── process_organization_info.inc.php # Organization processing
└── process_account_info.inc.php   # Account data processing
```

## How It Works

### Form Display Modules

Each display module (e.g., `name_birthday.inc.php`) contains:
- Self-contained HTML form section
- PHP logic to pre-populate fields from `$_POST`
- Consistent styling and structure
- Access to shared variables like `$errors` and `$account_type`

### Form Processing Modules

Each processor module (e.g., `process_name_birthday.inc.php`) contains:
- Validation logic for its specific fields
- Data sanitization and formatting
- Error message generation
- Returns processed data array

## Usage in createaccount.php

### Display Forms Based on Account Type

```php
// Always include name and birthday
include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/name_birthday.inc.php');

// Include type-specific fields
if ($account_type == 'business') {
    include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/business_info.inc.php');
} elseif ($account_type == 'family') {
    include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/family_members.inc.php');
} elseif ($account_type == 'organization') {
    include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/organization_info.inc.php');
}

// Always include account info
include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/account_info.inc.php');
```

### Process Form Submission

```php
if ($app->formposted()) {
    $errors = [];
    $processed_data = [];
    
    // Process common fields
    $name_birthday_data = include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/process_name_birthday.inc.php');
    if (is_array($name_birthday_data)) {
        $processed_data = array_merge($processed_data, $name_birthday_data);
    }
    
    // Process type-specific fields
    if ($account_type == 'business') {
        $business_data = include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/process_business_info.inc.php');
        if (is_array($business_data)) {
            $processed_data = array_merge($processed_data, $business_data);
        }
    }
    
    // Process account fields
    $account_data = include($_SERVER['DOCUMENT_ROOT'].'/includes/forms/process_account_info.inc.php');
    if (is_array($account_data)) {
        $processed_data = array_merge($processed_data, $account_data);
    }
    
    // Create account if no errors
    if (empty($errors)) {
        // Use $processed_data to create account
    }
}
```

## Adding New Account Types

To add a new account type:

1. **Create Display Module** (`new_type_info.inc.php`):
   ```php
   <div class="form-section">
       <h5 class="section-title">New Type Information</h5>
       <!-- Add your fields here -->
   </div>
   ```

2. **Create Processor Module** (`process_new_type_info.inc.php`):
   ```php
   <?php
   if (!isset($processed_data)) {
       $processed_data = [];
   }
   
   // Add validation logic
   if (empty($_POST['required_field'])) {
       $errors[] = 'Required field is required';
   } else {
       $processed_data['required_field'] = trim($_POST['required_field']);
   }
   
   return $processed_data;
   ```

3. **Update createaccount.php** to include the new modules based on account type.

## Available Variables

All modules have access to these variables:
- `$_POST` - Form submission data
- `$errors` - Array to store validation errors
- `$account_type` - Current account type ('user', 'family', 'business', etc.)
- `$session` - Session object
- `$processed_data` - Array for processed form data (in processors)

## Best Practices

1. **Keep modules self-contained** - Each module should work independently
2. **Use consistent naming** - Follow the existing naming patterns
3. **Validate server-side** - Never rely on client-side validation
4. **Sanitize input** - Always clean and validate user input
5. **Return data properly** - Processors must return the `$processed_data` array
6. **Document fields** - Add comments explaining non-obvious fields
7. **Maintain styling** - Use existing CSS classes and structure

## Account Type Stack Examples

### Individual Account
- name_birthday.inc.php
- account_info.inc.php

### Family Account
- name_birthday.inc.php (parent info)
- family_members.inc.php
- account_info.inc.php

### Business Account
- name_birthday.inc.php (contact person)
- business_info.inc.php
- account_info.inc.php

### Organization Account (example)
- name_birthday.inc.php (admin info)
- organization_info.inc.php
- account_info.inc.php

## Testing New Modules

1. Create test account type in signup flow
2. Add conditional logic in createaccount.php
3. Test all validation scenarios
4. Verify data is properly saved to database
5. Check error handling and display