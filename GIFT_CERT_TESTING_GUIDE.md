# Gift Certificate Testing Guide

## Steps to Test Gift Certificate Functionality

### 1. Check if Gift Certificate Products Exist
Visit: https://dev7.birthday.gold/test_gift_cert.php

This will show you:
- If any gift certificate products exist in the database
- All available account types
- Instructions if you need to create gift certificate products

### 2. Create Gift Certificate Product (if needed)
If no gift certificate products exist:
1. Go to https://dev7.birthday.gold/admin/plan_editor.php
2. Click "Create New Product"
3. Fill in:
   - Account Type: `giftcertificate`
   - Account Name: "Gift Certificate - 1 Year" (or similar)
   - Price: Your desired price (e.g., 4999 for $49.99)
   - Billing Cycle: `yearly` or `one-time`
   - Status: `active`
   - Grouping Status: `active`
4. Add features as needed
5. Save the product

### 3. Test Gift Certificate Section Directly
Visit: https://dev7.birthday.gold/test_gift_section.php

This will:
- Show the gift certificate form section in isolation
- Display any errors if the section isn't loading
- Show current session data

### 4. Test Full Gift Certificate Flow
1. Start at: https://dev7.birthday.gold/signup.php
2. Look for "Gift Certificate" or "As a gift" in the account types
3. Select a gift certificate plan
4. Click continue to go to createaccount.php
5. You should see:
   - Standard account fields (buyer's info)
   - Recipient Information section
   - Delivery Options section

### 5. Check Error Logs
If something isn't working, check the error logs:
```bash
tail -f /var/log/apache2/error.log
# or wherever your PHP error log is located
```

Look for entries containing:
- [CREATENEWACCOUNT] Account type: giftcertificate
- [CREATENEWACCOUNT] Config sections:
- Gift certificate creation failed:

## What Has Been Implemented

### Files Created/Modified:
1. `/core/forms/signup/section_gift_certificate.inc` - Form section for recipient info
2. `/core/forms/signup/handler_giftcertificate_basic.inc` - Processes gift data after payment
3. `/core/classes/class.giftcertificate.php` - Helper class for gift certificate operations
4. `/createaccount.php` - Added gift certificate configuration to section_configs

### Database Storage:
Gift certificates are stored in `bg_user_attributes` table with:
- `type`: 'gift_certificate' for main records
- `name`: 'gift_cert_[timestamp]_[random]' as unique identifier
- `string_value`: Gift certificate code
- `description`: JSON with all gift details (recipient, delivery, etc.)

### Gift Certificate Data Structure:
```json
{
  "code": "ABC123DEF456",
  "status": "active",
  "recipient": {
    "firstname": "John",
    "lastname": "Doe",
    "birthdate": "1990-01-01",
    "email": "john@example.com",
    "phone": "1234567890"
  },
  "delivery": {
    "methods": ["email", "print"],
    "date": "2025-12-25",
    "message": "Happy Birthday!"
  },
  "plan_id": "123",
  "plan_name": "Gift Certificate - 1 Year",
  "amount": 4999,
  "created_at": "2025-06-19 10:00:00",
  "expires_at": "2026-06-19 10:00:00"
}
```

## Troubleshooting Common Issues

### "Gift Certificate" not showing in account types
- Check that gift certificate products exist with `display_grouping_status = 'active'`
- Verify products have `version = 6` (or whatever version your site uses)

### Gift certificate section not loading
- Check browser console for JavaScript errors
- Verify file exists: `/core/forms/signup/section_gift_certificate.inc`
- Check PHP error logs for include errors

### Form validation errors
- Recipient fields are required
- Birthdate must be for someone 13+ years old
- Email/phone required based on delivery method selected

### After successful purchase
- Gift certificate data stored in bg_user_attributes
- Buyer gets the account and owns the gift certificates
- Scheduled deliveries stored with type='gift_delivery_schedule'

## Clean Up Test Files
After testing, remove the test files:
```bash
rm /mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/test_gift_cert.php
rm /mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/test_gift_section.php
rm /mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/GIFT_CERT_TESTING_GUIDE.md
```