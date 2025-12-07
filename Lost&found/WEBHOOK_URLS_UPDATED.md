# Webhook URLs Updated ✅

## Updated Files

### 1. ✅ Config.php
**File**: `Lost&found/htdocs/includes/Config.php`

**Updated**:
- `N8N_WEBHOOK_URL`: `https://besmar.app.n8n.cloud/webhook/chatbot` ✅
- `N8N_APPROVAL_WEBHOOK_URL`: `https://besmar.app.n8n.cloud/webhook/approval-action` ✅ (already set)

### 2. ✅ admin_action.php
**File**: `Lost&found/htdocs/public/admin_action.php`

**Fixed**: Updated the webhook check to use the correct URL pattern

## Current Webhook Configuration

### PHP → n8n (Outgoing Webhooks)
These are configured in `Config.php` and used by PHP to call n8n:

1. **Chatbot Webhook**: `https://besmar.app.n8n.cloud/webhook/chatbot`
   - Used by: `chat_handler.php`
   - Purpose: Sends user messages to n8n for AI processing

2. **Approval Notifications Webhook**: `https://besmar.app.n8n.cloud/webhook/approval-action`
   - Used by: `admin_action.php`
   - Purpose: Sends approval/rejection actions to n8n for email notifications

### n8n → PHP (Incoming API Calls)
These are in the n8n workflow JSON files and need to be updated when you deploy:

**Files that need domain update**:
- `n8n-workflow-import.json` (chatbot workflow)
- `n8n-workflow-approval-notifications.json`
- `n8n-workflow-match-detection.json`
- `n8n-workflow-scheduled-cleanup.json`
- `n8n-workflow-daily-report.json`
- `n8n-workflow-login.json`
- `n8n-workflow-create-lost-report.json`

**Current URLs** (in workflows): `http://localhost/api/v1/...`

**Update to**: `https://yourdomain.com/api/v1/...` (when deploying)

## What's Working Now

✅ **Chatbot**: PHP will send messages to `https://besmar.app.n8n.cloud/webhook/chatbot`
✅ **Approval Notifications**: PHP will send approval actions to `https://besmar.app.n8n.cloud/webhook/approval-action`

## What You Still Need to Do

### 1. Update n8n Workflow API URLs (When Deploying)

When you deploy to x10hosting, update the API URLs in your n8n workflows:

**Find and Replace** in n8n workflow nodes:
- `http://localhost/api/v1/` → `https://yourdomain.com/api/v1/`

**Or** update in n8n UI after importing:
1. Open each workflow
2. Find HTTP Request nodes
3. Update URL from `http://localhost/api/v1/...` to `https://yourdomain.com/api/v1/...`

### 2. Configure n8n Workflows

1. **Import chatbot workflow**: `n8n-workflow-import.json`
2. **Configure OpenAI node**: Resource = "Text", Operation = "Message a Model"
3. **Add OpenAI API key** in n8n credentials
4. **Activate workflows**

### 3. Test the Connection

1. **Test Chatbot**:
   - Log in to dashboard
   - Click chatbot icon
   - Send a message
   - Should receive AI response from n8n

2. **Test Approval Notifications**:
   - Log in as admin
   - Approve/reject an item
   - Check n8n execution logs
   - Should see webhook triggered

## Summary

✅ **PHP → n8n webhooks**: Updated and ready
⏳ **n8n → PHP API URLs**: Need to update when deploying (replace localhost with your domain)

The chatbot and approval notifications are now configured to use your n8n instance!

