# n8n Workflow - Complete Package

All files and documentation needed to set up your n8n chatbot workflow.

## Files Created

### 1. Workflow Import File
**File**: `n8n-workflow-import.json`
- Ready-to-import n8n workflow
- All nodes pre-configured
- Just update URLs and keys after import

### 2. Step-by-Step Guide
**File**: `N8N_WORKFLOW_STEP_BY_STEP.md`
- Detailed instructions for manual setup
- Screenshot descriptions
- Exact configurations for each node

### 3. Complete Setup Guide
**File**: `N8N_WORKFLOW_SETUP_COMPLETE.md`
- Complete reference guide
- All node configurations
- Testing procedures

### 4. Import Instructions
**File**: `N8N_WORKFLOW_IMPORT_INSTRUCTIONS.md`
- How to import the JSON file
- Post-import configuration steps
- Quick checklist

### 5. Quick Reference
**File**: `N8N_QUICK_REFERENCE.md`
- Quick lookup for node settings
- Common expressions
- Testing commands

---

## Quick Start (3 Steps)

### Step 1: Import Workflow
1. Open n8n: https://besmar.app.n8n.cloud
2. Workflows → Import from File
3. Select: `n8n-workflow-import.json`

### Step 2: Update Configuration
1. Update API URLs (replace `localhost` with your domain)
2. Update API keys in HTTP Request nodes
3. Add OpenAI API key credential

### Step 3: Activate
1. Toggle "Active" switch ON
2. Copy webhook URL
3. Update `.env` file with webhook URL

---

## Workflow Structure

```
Webhook Trigger
    ↓
Process Message (detects intent)
    ↓
Route Intent (IF node - 5 branches)
    ├─→ Lost Item/Search → Get Lost Items (HTTP)
    ├─→ My Reports → Get My Reports (HTTP)
    ├─→ Found Item → Get Found Items (HTTP)
    └─→ General/Help → (skip database)
    ↓
Merge All Paths
    ↓
Generate AI Response (OpenAI)
    ↓
Format Response
    ↓
Send Response (Respond to Webhook)
```

---

## Configuration Values

### From Your Project
- **API Base URL**: `http://localhost/api/v1` (or your domain)
- **API Key**: From `Config.php` or `.env` file
- **Webhook URL**: From n8n (after creating workflow)

### From External Services
- **OpenAI API Key**: Get from https://platform.openai.com/api-keys

---

## Node Count Summary

- **Total Nodes**: 11
- **Webhook**: 1
- **Code Nodes**: 3 (Process Message, Format Response, Error Response)
- **IF Node**: 1 (Route Intent)
- **HTTP Request**: 3 (Get Lost Items, Get My Reports, Get Found Items)
- **Merge**: 1
- **OpenAI**: 1
- **Respond to Webhook**: 1
- **Error Trigger**: 1

---

## What Each Node Does

1. **Webhook Trigger**: Receives POST from PHP chat_handler.php
2. **Process Message**: Analyzes message, detects intent, extracts entities
3. **Route Intent**: Routes to appropriate database query based on intent
4. **Get Lost Items**: Queries `/api/v1/reports?search=...`
5. **Get My Reports**: Queries `/api/v1/reports?studentNo=...`
6. **Get Found Items**: Queries `/api/v1/items?search=...`
7. **Merge All Paths**: Combines all paths before AI processing
8. **Generate AI Response**: Uses OpenAI to generate contextual response
9. **Format Response**: Formats AI response for frontend
10. **Send Response**: Returns JSON to PHP endpoint
11. **Error Handler**: Handles errors gracefully

---

## Testing

### Test 1: Basic
```json
{
  "message": "Hello",
  "studentNo": "TEST001",
  "studentName": "Test User"
}
```

### Test 2: Lost Item
```json
{
  "message": "I lost my iPhone",
  "studentNo": "TEST001",
  "studentName": "Test User"
}
```

### Test 3: My Reports
```json
{
  "message": "Show my reports",
  "studentNo": "TEST001",
  "studentName": "Test User"
}
```

---

## Support Documents

- **New to n8n?** → Start with `N8N_WORKFLOW_STEP_BY_STEP.md`
- **Want to import?** → Use `N8N_WORKFLOW_IMPORT_INSTRUCTIONS.md`
- **Need quick reference?** → Check `N8N_QUICK_REFERENCE.md`
- **Full details?** → Read `N8N_WORKFLOW_SETUP_COMPLETE.md`

---

## Next Steps

1. ✅ Import workflow or follow step-by-step guide
2. ✅ Update API URLs and keys
3. ✅ Add OpenAI credentials
4. ✅ Activate workflow
5. ✅ Test from chatbot UI
6. ✅ Monitor execution logs
7. ✅ Refine prompts if needed

---

Everything you need is ready! Choose your preferred method:
- **Fast**: Import JSON file
- **Learning**: Follow step-by-step guide
- **Custom**: Build manually with reference guides

