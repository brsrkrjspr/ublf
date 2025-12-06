# n8n Workflow - Quick Reference Card

## Node Configurations at a Glance

### 1. Webhook Trigger
```
Method: POST
Path: chatbot
Response Mode: Respond to Webhook
```

### 2. Process Message (Code)
```javascript
// Intent detection code (see full guide)
// Detects: lost_item, found_item, my_reports, search, help, general
```

### 3. Route Intent (IF)
```
Conditions:
- {{ $json.intent }} equals lost_item → "Lost Item"
- {{ $json.intent }} equals search → "Search"
- {{ $json.intent }} equals my_reports → "My Reports"
- {{ $json.intent }} equals found_item → "Found Item"
- Default → "General"
```

### 4. Get Lost Items (HTTP Request)
```
Method: GET
URL: http://localhost/api/v1/reports?search={{ $('Process Message').first().json.entities.itemName || $('Process Message').first().json.originalMessage }}
Header: X-API-Key: your-api-key
```

### 5. Get My Reports (HTTP Request)
```
Method: GET
URL: http://localhost/api/v1/reports?studentNo={{ $('Process Message').first().json.studentNo }}
Header: X-API-Key: your-api-key
```

### 6. Get Found Items (HTTP Request)
```
Method: GET
URL: http://localhost/api/v1/items?search={{ $('Process Message').first().json.entities.itemName || $('Process Message').first().json.originalMessage }}
Header: X-API-Key: your-api-key
```

### 7. Merge All Paths
```
Mode: Merge
Merge By: Append
```

### 8. Generate AI Response (OpenAI)
```
Resource: Chat
Model: gpt-3.5-turbo
System Message: (see full guide)
User Message: (see full guide)
```

### 9. Format Response (Code)
```javascript
// Extract reply from AI response
// Format and return JSON
```

### 10. Send Response (Respond to Webhook)
```
Respond With: JSON
Response Body: {{ $json }}
Code: 200
```

## Common Expressions

### Get Student Number
```
{{ $('Process Message').first().json.studentNo }}
```

### Get Original Message
```
{{ $('Process Message').first().json.originalMessage }}
```

### Get Detected Intent
```
{{ $('Process Message').first().json.intent }}
```

### Get Item Name
```
{{ $('Process Message').first().json.entities.itemName }}
```

### Get API Response
```
{{ $('Get Lost Items').first().json.data }}
```

## Testing URLs

Replace `YOUR_WEBHOOK_URL` with your actual webhook URL.

### Test Lost Item
```bash
curl -X POST YOUR_WEBHOOK_URL \
  -H "Content-Type: application/json" \
  -d '{"message":"I lost my phone","studentNo":"TEST001","studentName":"Test"}'
```

### Test My Reports
```bash
curl -X POST YOUR_WEBHOOK_URL \
  -H "Content-Type: application/json" \
  -d '{"message":"Show my reports","studentNo":"TEST001","studentName":"Test"}'
```

### Test Help
```bash
curl -X POST YOUR_WEBHOOK_URL \
  -H "Content-Type: application/json" \
  -d '{"message":"Help me","studentNo":"TEST001","studentName":"Test"}'
```

## Configuration Values Needed

- **Webhook URL**: From n8n webhook node
- **API Base URL**: `http://localhost/api/v1` (or your domain)
- **API Key**: From Config.php or .env
- **OpenAI API Key**: From OpenAI platform

## File Locations

- Workflow JSON: `n8n-workflow-import.json`
- Setup Guide: `N8N_WORKFLOW_STEP_BY_STEP.md`
- Complete Guide: `N8N_WORKFLOW_SETUP_COMPLETE.md`
- Original Guide: `N8N_CHATBOT_WORKFLOW_GUIDE.md`

