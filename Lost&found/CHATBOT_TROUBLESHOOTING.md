# Chatbot Troubleshooting Guide

## Error: "I received an unexpected response"

This error occurs when the n8n workflow returns a response that isn't valid JSON or doesn't match the expected format.

### Common Causes & Solutions

### 1. **OpenAI Credential Not Configured** (Most Common)

**Symptom**: Error message appears immediately when sending any message

**Solution**:
1. Open n8n workflow: "UB Lost & Found Chatbot"
2. Click on **"Generate AI Response"** node
3. Click **"Credential to connect with"** dropdown
4. Click **"Create New Credential"**
5. Select **"OpenAI"**
6. Enter your OpenAI API key from https://platform.openai.com/api-keys
7. Click **"Save"**
8. The credential should now be selected automatically
9. **Activate the workflow** (toggle ON)

### 2. **Workflow Not Activated**

**Symptom**: Error appears, webhook might return 404 or HTML error page

**Solution**:
1. In n8n, go to the workflow
2. Toggle the **"Active"** switch to **ON** (top right)
3. The workflow must be active to receive webhooks

### 3. **Wrong Webhook URL**

**Symptom**: Connection errors or 404 errors

**Solution**:
1. In n8n, click on **"Webhook Trigger"** node
2. Copy the webhook URL (should be like `https://besmar.app.n8n.cloud/webhook/chatbot`)
3. Update your `.env` file or `Config.php`:
   ```
   N8N_WEBHOOK_URL=https://besmar.app.n8n.cloud/webhook/chatbot
   ```
4. Make sure the URL matches exactly

### 4. **API Endpoints Not Working**

**Symptom**: Workflow runs but returns errors from API calls

**Solution**:
1. Check that your API is accessible: `https://ublf.x10.mx/api/v1/reports`
2. Verify API key is correct in n8n workflow HTTP Request nodes
3. Test API endpoints directly with curl or Postman

### 5. **Check Server Error Logs**

To see the actual error:

1. **Via cPanel**:
   - Go to cPanel → Error Log
   - Look for entries with "Chatbot" in the message

2. **Via FTP**:
   - Check `/error_log` or `/logs/error_log` file
   - Look for lines starting with "Chatbot"

3. **What to look for**:
   - `Chatbot n8n invalid JSON response` - Shows what n8n actually returned
   - `Chatbot n8n HTTP error` - Shows HTTP status code
   - `Chatbot n8n connection error` - Shows connection issues

### Testing Steps

1. **Test n8n Webhook Directly**:
   ```bash
   curl -X POST https://besmar.app.n8n.cloud/webhook/chatbot \
     -H "Content-Type: application/json" \
     -d '{"message":"hello","studentNo":"TEST001","studentName":"Test User","studentEmail":"test@ub.edu.ph"}'
   ```
   
   Should return JSON with `reply` field.

2. **Test from Browser Console**:
   ```javascript
   fetch('php/chat_handler.php', {
     method: 'POST',
     headers: {'Content-Type': 'application/json'},
     body: JSON.stringify({message: 'hello'})
   })
   .then(r => r.json())
   .then(console.log)
   ```

3. **Check n8n Execution Logs**:
   - In n8n, go to "Executions" tab
   - Find the failed execution
   - Click to see which node failed and why

### Expected Response Format

The n8n workflow should return:
```json
{
  "reply": "Hello! How can I help you?",
  "intent": "general",
  "hasResults": false,
  "resultCount": 0,
  "timestamp": "2024-01-01T00:00:00.000Z"
}
```

If you see a different format, the workflow needs to be fixed.

### Quick Fix Checklist

- [ ] OpenAI credential is configured in n8n
- [ ] Workflow is activated (toggle ON)
- [ ] Webhook URL in Config.php matches n8n webhook URL
- [ ] API URLs in workflow are correct (`https://ublf.x10.mx/api/v1/...`)
- [ ] API keys in workflow are correct
- [ ] Test webhook directly in n8n (click "Test step" on Webhook Trigger)

### Still Not Working?

1. Check n8n execution logs for specific error
2. Check server error logs for detailed error message
3. Verify all URLs and keys are correct
4. Test each component separately (webhook, API, OpenAI)

