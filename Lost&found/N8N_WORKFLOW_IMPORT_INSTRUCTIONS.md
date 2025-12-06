# How to Import n8n Workflow

## Method 1: Import JSON File (Easiest)

1. **Open n8n**: Go to https://besmar.app.n8n.cloud
2. **Go to Workflows**: Click "Workflows" in left sidebar
3. **Import**: Click "..." menu (three dots) → Select "Import from File"
4. **Select File**: Choose `n8n-workflow-import.json` from this project
5. **Import**: Click "Import" button
6. **Update Configuration** (see below)
7. **Activate**: Toggle "Active" switch to ON

---

## Method 2: Manual Setup

Follow the step-by-step guide in `N8N_WORKFLOW_STEP_BY_STEP.md`

---

## After Import: Required Updates

### 1. Update API URLs

The workflow uses `http://localhost/api/v1/` - you need to update these:

**In n8n, edit these nodes:**
- **Get Lost Items** → URL field
- **Get My Reports** → URL field  
- **Get Found Items** → URL field

**Replace**: `http://localhost/api/v1/`
**With**: Your actual API URL:
- Local: `http://localhost/api/v1/`
- Production: `https://yourdomain.com/api/v1/`

### 2. Update API Keys

**In all HTTP Request nodes**, update the API key:

1. Click on **"Get Lost Items"** node
2. Find **"Authentication"** section
3. Click **"Header Auth"**
4. In **"Value"** field, replace `your-secret-api-key-change-this` with your actual API key
5. Repeat for **"Get My Reports"** and **"Get Found Items"** nodes

**Your API key is in:**
- `htdocs/includes/Config.php` (line 19)
- Or `.env` file: `API_KEY=your-key`

### 3. Add OpenAI Credentials

1. In n8n, click **"Generate AI Response"** node
2. Click **"Credential to connect with"** dropdown
3. Click **"Create New Credential"**
4. Select **"OpenAI"**
5. Enter your OpenAI API key (get from https://platform.openai.com/api-keys)
6. Click **"Save"**
7. Select this credential in the node

### 4. Update Webhook URL in PHP

1. After importing, copy the webhook URL from n8n
2. Update your `.env` file:
   ```
   N8N_WEBHOOK_URL=https://besmar.app.n8n.cloud/webhook/chatbot
   ```
   (Use your actual webhook URL)

---

## Quick Configuration Checklist

After importing, update:

- [ ] API URLs in HTTP Request nodes (3 nodes)
- [ ] API keys in HTTP Request nodes (3 nodes)
- [ ] OpenAI API key in credentials
- [ ] Webhook URL in `.env` file
- [ ] Activate workflow (toggle ON)

---

## Testing After Import

1. **Test in n8n:**
   - Click "Webhook Trigger" node
   - Click "Test step"
   - Send test data
   - Check execution

2. **Test from your app:**
   - Open dashboard
   - Click chatbot
   - Send message
   - Verify response

---

## Troubleshooting Import

**If import fails:**
- Check n8n version compatibility
- Try manual setup instead (see step-by-step guide)
- Check JSON file is valid

**If nodes don't connect:**
- Manually connect nodes by dragging
- Check node names match exactly

**If credentials error:**
- Re-add OpenAI credentials
- Verify API keys are correct

---

The workflow JSON is ready to import. Just update the URLs and keys, and you're done!

