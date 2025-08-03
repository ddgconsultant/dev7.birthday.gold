# Google Assistant Setup Guide for Birthday Gold

## Prerequisites
- Google account
- Access to Google Actions Console (https://console.actions.google.com/)
- Birthday Gold admin access

## Step 1: Create a Google Action

1. Go to https://console.actions.google.com/
2. Click "New project"
3. Project name: "Birthday Gold"
4. Click "Create project"
5. Choose "Custom" > "Blank project" > "Start building"

## Step 2: Configure Action Settings

### Display Name
- Display name: "Birthday Gold"
- Pronunciation: "Birthday Gold"

### Description
- Short: "Access your Birthday Gold rewards and enrollments"
- Full: "Birthday Gold helps you manage birthday reward programs. Ask about your enrollments, rewards, and account status."

### Category
- Select: "Lifestyle & Social"

### Voice
- Select a Google Assistant voice

## Step 3: Set Up Account Linking

1. Go to "Account linking" in the left menu
2. Enable account linking toggle
3. Configure OAuth settings:

### OAuth Configuration
- **Linking type**: OAuth / Authorization code
- **Client ID**: `google-assistant-birthday-gold`
- **Client secret**: Generate a secure secret and save it
- **Authorization URL**: `https://dev7.birthday.gold/myaccount/assistant-oauth.php`
- **Token URL**: `https://dev7.birthday.gold/api/assistant/oauth-token.php`
- **Configure your client**: 
  - Scopes: `profile`
  - Google to transmit data: Query string
  
### Testing Instructions
- Use email: test@birthday.gold
- Password: (your test password)

## Step 4: Configure Webhook

1. Go to "Webhook" in the left menu
2. Enable webhook toggle
3. Webhook URL: `https://dev7.birthday.gold/api/assistant/google/webhook.php`
4. Webhook headers (optional):
   - `X-Birthday-Gold-Key`: `your-webhook-key`

## Step 5: Add Intents

### Main Intents to Create:

1. **Get Enrollment Count**
   - Training phrases:
     - "How many enrollments do I have"
     - "What's my enrollment count"
     - "How many programs am I in"

2. **Get Active Rewards**
   - Training phrases:
     - "What rewards do I have"
     - "List my active rewards"
     - "What programs am I enrolled in"

3. **Get Allocation Balance**
   - Training phrases:
     - "How many allocations do I have"
     - "What's my allocation balance"
     - "How many spots left"

4. **Get Account Status**
   - Training phrases:
     - "What's my account status"
     - "What plan am I on"
     - "Account information"

## Step 6: Test Your Action

1. Go to "Test" in the Actions Console
2. Click "Talk to Birthday Gold"
3. Test the account linking flow
4. Test voice commands

## Step 7: Submit for Production

1. Complete all required fields in "Deploy"
2. Add privacy policy: https://birthday.gold/privacy
3. Add terms of service: https://birthday.gold/terms
4. Submit for review

## Environment Variables Needed

Add to your Birthday Gold configuration:

```php
// Google Assistant Configuration
define('GOOGLE_ASSISTANT_CLIENT_ID', 'google-assistant-birthday-gold');
define('GOOGLE_ASSISTANT_CLIENT_SECRET', 'your-generated-secret-here');
define('GOOGLE_ASSISTANT_PROJECT_ID', 'your-project-id');
```

## Testing URLs

- OAuth Start: https://dev7.birthday.gold/myaccount/assistant-oauth.php?client_id=google-assistant-birthday-gold&redirect_uri=https://oauth-redirect.googleusercontent.com/r/YOUR_PROJECT_ID&state=TEST&response_type=code
- Token Exchange: POST to https://dev7.birthday.gold/api/assistant/oauth-token.php
- Webhook Test: POST to https://dev7.birthday.gold/api/assistant/google/webhook.php

## Common Issues & Solutions

1. **Account linking fails**: Check client_id matches in Google Console and code
2. **Webhook not responding**: Verify webhook URL is publicly accessible
3. **Token exchange fails**: Ensure token endpoint accepts POST requests
4. **Voice commands not recognized**: Add more training phrases