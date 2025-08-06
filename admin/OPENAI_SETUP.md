# OpenAI API Setup Instructions

## Creating and Configuring Your OpenAI API Key

1. **Create a New API Key**
   - Go to https://platform.openai.com/api-keys
   - Click "Create new secret key"
   - Name it something like "Birthday Gold Production"
   - Copy the key immediately (you won't be able to see it again)

2. **Add to Configuration File**
   - Open your `config-ai.inc` file (located outside the web root)
   - Add the following configuration:
   ```php
   $sitesettings_ai['openai_api_key'] = 'sk-YOUR-NEW-API-KEY-HERE';
   ```

3. **Set Usage Limits** (Recommended)
   - In OpenAI dashboard, set monthly usage limits to prevent unexpected charges
   - Start with a reasonable limit like $50-100/month

4. **Security Best Practices**
   - Never commit API keys to Git
   - Ensure config-ai.inc is in your .gitignore
   - Rotate keys periodically
   - Monitor usage regularly

## Testing the Integration

After configuring your API key, test the ChatGPT integration:
1. Navigate to `/admin/chatgpt.php`
2. The script should now use your configured API key
3. It will rewrite company descriptions using GPT-3.5-turbo

## Troubleshooting

If you get an API key error:
- Verify the key is correctly set in config-ai.inc
- Check that config-ai.inc is being loaded by site-controller.php
- Ensure the key has the necessary permissions for chat completions