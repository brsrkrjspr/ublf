# API URLs Updated ✅

## All n8n Workflow Files Updated

All workflow JSON files have been updated to use your production domain: **`https://ublf.x10.mx/api/v1/`**

### Updated Files:

1. ✅ `n8n-workflow-import.json` (Chatbot)
   - Updated 3 API URLs:
     - `/api/v1/reports?search=...`
     - `/api/v1/reports?studentNo=...`
     - `/api/v1/items?search=...`

2. ✅ `n8n-workflow-approval-notifications.json`
   - Updated: `/api/v1/webhooks`

3. ✅ `n8n-workflow-match-detection.json`
   - Updated 2 API URLs:
     - `/api/v1/reports?search=...`
     - `/api/v1/webhooks`

4. ✅ `n8n-workflow-scheduled-cleanup.json`
   - Updated: `/api/v1/webhooks`

5. ✅ `n8n-workflow-daily-report.json`
   - Updated 3 API URLs:
     - `/api/v1/webhooks`
     - `/api/v1/reports?limit=10`
     - `/api/v1/items?limit=10`

6. ✅ `n8n-workflow-login.json`
   - Updated: `/api/v1/webhooks`

7. ✅ `n8n-workflow-create-lost-report.json`
   - Updated: `/api/v1/webhooks`

## API Base URL

**Production URL**: `https://ublf.x10.mx/api/v1/`

All workflows now use this base URL for all API calls.

## What This Means

✅ **n8n workflows can now call your PHP API** at `https://ublf.x10.mx/api/v1/`
✅ **All workflows are ready to import** - no manual URL updates needed
✅ **Workflows will work immediately** after importing to n8n

## Next Steps

1. **Import workflows** to n8n (all URLs are already configured)
2. **Configure OpenAI** node in chatbot workflow
3. **Add API key** in workflow HTTP Request nodes (if different from default)
4. **Activate workflows**
5. **Test** - workflows should connect to your API automatically

## API Endpoints Available

Your API is accessible at:
- `https://ublf.x10.mx/api/v1/webhooks` - Webhook receiver
- `https://ublf.x10.mx/api/v1/reports` - Lost item reports
- `https://ublf.x10.mx/api/v1/items` - Found items

All workflows are now configured to use these endpoints!

