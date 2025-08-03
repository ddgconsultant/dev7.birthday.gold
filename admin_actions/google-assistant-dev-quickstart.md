# Quick Start: Setting Up Google Assistant on dev7.birthday.gold

Since dev7.birthday.gold is publicly accessible, you can test the voice assistant integration right now!

## Step 1: Create a Test Google Action

1. Go to https://console.actions.google.com/
2. Click "New project"
3. Name it: "Birthday Gold Dev" (to distinguish from production)
4. Choose "Custom" > "Blank project"

## Step 2: Quick Configuration

### Actions Console Settings:

**Display name:** Birthday Gold Dev  
**Category:** Lifestyle & Social

### Account Linking (REQUIRED):
- **Linking type:** OAuth / Authorization code
- **Client ID:** `google-assistant-birthday-gold-dev`
- **Client secret:** `test-secret-123` (or any secret you want)
- **Authorization URL:** `https://dev7.birthday.gold/myaccount/assistant-oauth.php`
- **Token URL:** `https://dev7.birthday.gold/api/assistant/oauth-token.php`
- **Scopes:** `profile`

### Webhook:
- **URL:** `https://dev7.birthday.gold/api/assistant/google/webhook.php`

## Step 3: Add a Simple Intent

1. Go to "Main invocation" in the console
2. Add training phrases:
   - "Talk to Birthday Gold Dev"
   - "Ask Birthday Gold Dev"

3. Create a custom intent called "GetEnrollmentCount"
4. Add training phrases:
   - "How many enrollments do I have"
   - "What's my enrollment count"
   - "Check my enrollments"

## Step 4: Test It!

1. Go to "Test" tab in Actions Console
2. Click "Talk to Birthday Gold Dev"
3. It will ask you to link your account
4. Complete the OAuth flow with your dev7 account
5. Try: "How many enrollments do I have?"

## Test URLs You Can Use Right Now:

### Manual Testing Page:
https://dev7.birthday.gold/admin_actions/google-assistant-quicktest.php

### Test OAuth Flow:
https://dev7.birthday.gold/admin_actions/test-google-oauth.php

### Direct OAuth Test:
```
https://dev7.birthday.gold/myaccount/assistant-oauth.php?client_id=google-assistant-birthday-gold-dev&redirect_uri=https://oauth-redirect.googleusercontent.com/r/YOUR_PROJECT_ID&state=test123&response_type=code&scope=profile
```

## That's It!

You now have a working Google Assistant integration on dev7! The system will:
- Handle OAuth account linking
- Process voice commands
- Return enrollment counts, rewards, and account info

## Next Steps:

1. Add more intents (GetActiveRewards, GetAllocationBalance, etc.)
2. Test on your phone with Google Assistant
3. Share with team members for testing (up to 20 users)

## Notes:
- This is separate from production - use different project names
- Test thoroughly before creating production version
- The "-dev" suffix helps distinguish environments