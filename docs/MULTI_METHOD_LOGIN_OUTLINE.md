# Multi-Method Login System Outline

## Overview
Birthday Gold now supports multiple authentication methods, allowing users to log in and reset passwords using either email or phone number. The system remembers user preferences for a seamless experience.

## Features

### 1. Login Methods
- **Email Login**: Traditional email/username + password authentication
- **Phone Login**: Phone number + password authentication

### 2. User Preference System
- Automatically saves user's preferred login method
- Cookie-based storage (1 year duration)
- Cross-subdomain support (works across all *.birthday.gold domains)
- Secure implementation (HttpOnly, Secure flags)

### 3. UI/UX Features
- Tab-based method selection on login and forgot password pages
- Automatic phone number formatting: (555) 123-4567
- Dynamic form field switching
- Preserved form state during method switches
- Appropriate input validation for each method

## Technical Implementation

### Account Class Methods

#### `setLoginMethodPreference($method)`
- **Purpose**: Saves user's preferred login method
- **Parameters**: `$method` - 'email' or 'phone'
- **Storage**: Cookie named `bdgold_login_method`
- **Duration**: 1 year
- **Security**: HttpOnly, Secure, SameSite=Lax

#### `getLoginMethodPreference()`
- **Purpose**: Retrieves user's saved preference
- **Returns**: 'email' or 'phone' (defaults to 'email')
- **Fallback**: Returns 'email' if no preference or invalid value

### Database Schema
- Phone numbers stored in `bg_user_attributes` table
- Attribute name: `profile_phone_number`
- Type: `profile`
- Status: `active`

### Authentication Flow

#### Phone Login Process
1. User selects phone tab
2. Enters phone number (auto-formatted)
3. System queries `bg_user_attributes` for matching phone
4. Joins with `bg_users` to get full user record
5. Validates password against user record
6. Sets login method preference on success

#### Email Login Process
1. User selects email tab (default)
2. Standard email/username authentication
3. Sets login method preference on success

### Password Reset Flow
- Same tab interface as login
- Phone reset sends SMS with reset link
- Email reset sends standard email
- Preference saved on successful request

## User Experience Flow

### First-Time User
1. Sees email tab by default
2. Can switch to phone if preferred
3. Choice saved on successful action

### Returning User
1. Sees their last used method
2. Form pre-configured for that method
3. Can still switch if desired

### Cross-Page Consistency
- Login page preference
- Forgot password preference
- Future: Registration preference

## Security Considerations

### Cookie Security
- HttpOnly: Prevents JavaScript access
- Secure: HTTPS only transmission
- SameSite=Lax: CSRF protection
- Domain: `.birthday.gold` for subdomain access

### Input Validation
- Phone: Numeric only, stripped of formatting
- Email: Standard email validation
- Both methods require active user status

### Rate Limiting
- Existing login throttling applies to both methods
- Same lockout rules for failed attempts

## Future Enhancements

### Potential Additions
1. Two-factor authentication for phone users
2. SMS verification for new phone numbers
3. Multiple phone numbers per account
4. International phone number support
5. Registration with phone number
6. Account recovery via phone

### Integration Points
- User profile phone management
- Admin interface for phone lookup
- Analytics for method usage
- Customer support tools

## Implementation Files

### Core Files Modified
- `/core/classes/class.account.php` - Preference methods and phone authentication
- `/login.php` - Tab interface and preference handling
- `/forgot.php` - Tab interface and preference handling

### Key Functions
- `Account::login()` - Added 'phone' case
- `Account::getUserByPhone()` - Existing phone lookup
- `Account::setLoginMethodPreference()` - New
- `Account::getLoginMethodPreference()` - New

## Testing Checklist

### Functional Tests
- [ ] Email login works
- [ ] Phone login works
- [ ] Preference saves correctly
- [ ] Preference loads correctly
- [ ] Tab switching works
- [ ] Form validation works
- [ ] Password reset for both methods

### Edge Cases
- [ ] Invalid phone numbers
- [ ] Non-existent phone numbers
- [ ] Cookie disabled scenarios
- [ ] Method switching mid-form
- [ ] Cross-subdomain cookie access

## Support Documentation

### User FAQ
- **Q**: Can I use both email and phone?
- **A**: Yes, the system remembers your last choice but you can switch anytime

- **Q**: What if I forgot which method I used?
- **A**: Try both - the system will tell you if the account isn't found

- **Q**: Is my phone number secure?
- **A**: Yes, stored encrypted and transmitted securely

### Admin Notes
- Phone numbers in `bg_user_attributes` table
- Preference cookies don't affect security
- Both methods use same password validation
- Rate limiting applies equally