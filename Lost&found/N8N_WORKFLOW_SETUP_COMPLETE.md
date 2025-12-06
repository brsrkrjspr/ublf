# n8n Workflow Setup - Complete Guide

This guide provides everything you need to set up the chatbot workflow in n8n.

## Quick Start: Import Workflow

1. **Open n8n**
2. **Go to Workflows** → Click **"Import from File"**
3. **Select**: `n8n-workflow-import.json`
4. **Update Configuration** (see below)
5. **Activate Workflow**

---

## Manual Setup (If Import Doesn't Work)

### Step 1: Create Webhook Trigger

1. Create new workflow in n8n
2. Add **Webhook** node
3. Configure:
   - **HTTP Method**: POST
   - **Path**: `chatbot`
   - **Response Mode**: "Respond to Webhook"
4. **Save** and copy the webhook URL
5. Update your `.env` file:
   ```
   N8N_WEBHOOK_URL=your-webhook-url-here
   ```

### Step 2: Process Message Node

Add **Code** node with this JavaScript:

```javascript
// Get input from webhook
const input = $input.first().json;
const message = input.message.toLowerCase().trim();
const studentNo = input.studentNo || '';
const studentName = input.studentName || '';
const studentEmail = input.studentEmail || '';

// Detect intent
let intent = 'general';
let entities = {};

if (message.includes('lost') || message.includes('report lost') || message.includes('i lost')) {
  intent = 'lost_item';
} else if (message.includes('found') || message.includes('report found')) {
  intent = 'found_item';
} else if (message.includes('my report') || message.includes('my item') || message.includes('what did i report')) {
  intent = 'my_reports';
} else if (message.includes('search') || message.includes('find') || message.includes('looking for')) {
  intent = 'search';
} else if (message.includes('help') || message.includes('how') || message.includes('what can')) {
  intent = 'help';
}

// Extract item name
const itemMatch = message.match(/(?:lost|found|looking for|find)\s+(?:my|a|an|the)?\s*([a-z0-9\s]+?)(?:\s|$|\.|,|at|in)/i);
if (itemMatch && itemMatch[1]) {
  entities.itemName = itemMatch[1].trim();
}

return [{
  json: {
    intent,
    entities,
    originalMessage: input.message,
    studentNo,
    studentName,
    studentEmail,
    timestamp: input.timestamp || new Date().toISOString()
  }
}];
```

### Step 3: Route Intent (IF Node)

Add **IF** node with these conditions:

1. `{{ $json.intent }}` equals `lost_item` → Output: "Lost Item"
2. `{{ $json.intent }}` equals `search` → Output: "Search"  
3. `{{ $json.intent }}` equals `my_reports` → Output: "My Reports"
4. `{{ $json.intent }}` equals `found_item` → Output: "Found Item"
5. Default → Output: "General"

### Step 4: Database Query Nodes

#### Get Lost Items (HTTP Request)
- **Method**: GET
- **URL**: `http://localhost/api/v1/reports?search={{ $('Process Message').first().json.entities.itemName || $('Process Message').first().json.originalMessage }}`
- **Headers**: 
  - `X-API-Key`: `your-api-key-here`
- Connect from: "Lost Item" and "Search" outputs

#### Get My Reports (HTTP Request)
- **Method**: GET
- **URL**: `http://localhost/api/v1/reports?studentNo={{ $('Process Message').first().json.studentNo }}`
- **Headers**: 
  - `X-API-Key`: `your-api-key-here`
- Connect from: "My Reports" output

#### Get Found Items (HTTP Request)
- **Method**: GET
- **URL**: `http://localhost/api/v1/items?search={{ $('Process Message').first().json.entities.itemName || $('Process Message').first().json.originalMessage }}`
- **Headers**: 
  - `X-API-Key`: `your-api-key-here`
- Connect from: "Found Item" output

### Step 5: Merge Node

Add **Merge** node:
- **Mode**: Merge
- **Merge By**: Append
- Connect all database nodes + "General" output to it

### Step 6: OpenAI Node

Add **OpenAI** node:
- **Resource**: Chat
- **Operation**: Create Message
- **Model**: `gpt-3.5-turbo`
- **System Message**:
```
You are a helpful assistant for a University Lost & Found system. Help students report lost items, search for items, check their reports, and answer questions. Be friendly, concise, and helpful. Use the provided context to give accurate information. Keep responses under 200 words.
```
- **User Message**:
```
User Question: {{ $('Process Message').first().json.originalMessage }}

Student: {{ $('Process Message').first().json.studentName }} ({{ $('Process Message').first().json.studentNo }})
Intent: {{ $('Process Message').first().json.intent }}

Database Results:
{{ $('Get Lost Items').first() ? 'Lost Items:\n' + JSON.stringify($('Get Lost Items').first().json, null, 2) : ($('Get My Reports').first() ? 'User Reports:\n' + JSON.stringify($('Get My Reports').first().json, null, 2) : ($('Get Found Items').first() ? 'Found Items:\n' + JSON.stringify($('Get Found Items').first().json, null, 2) : 'No database results')) }}

Provide a helpful response.
```

### Step 7: Format Response (Code Node)

```javascript
const aiResponse = $('Generate AI Response').first().json;

let reply = '';
if (aiResponse.choices?.[0]?.message?.content) {
  reply = aiResponse.choices[0].message.content;
} else if (aiResponse.message?.content) {
  reply = aiResponse.message.content;
} else if (aiResponse.content) {
  reply = aiResponse.content;
} else {
  reply = 'I apologize, but I couldn\'t generate a response. Please try again.';
}

const intent = $('Process Message').first().json.intent;

return [{
  json: {
    reply: reply.trim(),
    intent: intent,
    timestamp: new Date().toISOString()
  }
}];
```

### Step 8: Respond to Webhook

- **Respond With**: JSON
- **Response Body**: `{{ $json }}`
- **Response Code**: 200

### Step 9: Error Handling

Add **Error Trigger** node and connect error outputs from:
- Get Lost Items
- Get My Reports  
- Get Found Items
- Generate AI Response

Add Code node for error response:
```javascript
return [{
  json: {
    reply: 'I encountered an error. Please try again or contact admin.',
    timestamp: new Date().toISOString()
  }
}];
```

Connect error code node → Send Response

---

## Configuration Required

### 1. Update API URLs

In the imported workflow, update these URLs in HTTP Request nodes:

**Replace**: `http://localhost/api/v1/`
**With**: Your actual API URL (e.g., `https://yourdomain.com/api/v1/`)

### 2. Update API Key

In HTTP Request nodes, update:
- **Header Value**: `X-API-Key`
- **Value**: Your actual API key (from Config.php or .env)

### 3. Add OpenAI Credentials

1. Go to n8n **Settings** → **Credentials**
2. Click **"+ Add Credential"**
3. Select **"OpenAI"**
4. Enter your OpenAI API key
5. Save as "OpenAI API"
6. Assign to "Generate AI Response" node

### 4. Update Webhook URL in PHP

Update `.env` file or `chat_handler.php`:
```
N8N_WEBHOOK_URL=your-n8n-webhook-url
```

---

## Workflow Node Summary

| Node | Type | Purpose |
|------|------|---------|
| Webhook Trigger | Webhook | Receives POST from PHP |
| Process Message | Code | Detects intent and extracts entities |
| Route Intent | IF | Routes to appropriate database query |
| Get Lost Items | HTTP Request | Queries lost items API |
| Get My Reports | HTTP Request | Queries student's reports |
| Get Found Items | HTTP Request | Queries found items API |
| Merge All Paths | Merge | Combines all paths before AI |
| Generate AI Response | OpenAI | Generates AI reply with context |
| Format Response | Code | Formats final response |
| Send Response | Respond to Webhook | Returns JSON to PHP |
| Format Error Response | Code | Handles errors gracefully |

---

## Testing the Workflow

### Test 1: Basic Test
```bash
curl -X POST YOUR_WEBHOOK_URL \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Hello",
    "studentNo": "TEST001",
    "studentName": "Test User"
  }'
```

### Test 2: Lost Item Intent
```bash
curl -X POST YOUR_WEBHOOK_URL \
  -H "Content-Type: application/json" \
  -d '{
    "message": "I lost my phone",
    "studentNo": "TEST001",
    "studentName": "Test User"
  }'
```

### Test 3: My Reports Intent
```bash
curl -X POST YOUR_WEBHOOK_URL \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Show my reports",
    "studentNo": "TEST001",
    "studentName": "Test User"
  }'
```

---

## Troubleshooting

### Workflow Not Receiving Requests
- Check webhook URL in chat_handler.php
- Verify workflow is activated (green toggle)
- Check n8n execution logs

### API Requests Failing
- Verify API URL is correct (replace localhost if needed)
- Check API key matches Config.php
- Test API endpoint directly with curl
- Check if API is accessible from n8n's location

### OpenAI Not Working
- Verify OpenAI API key is correct
- Check API quota/limits
- Try gpt-3.5-turbo instead of gpt-4
- Review OpenAI node configuration

### Intent Not Detected
- Check Process Message node output
- Review intent detection logic
- Add more keywords if needed
- Test with different message variations

---

## Next Steps

1. **Import workflow** from `n8n-workflow-import.json`
2. **Update API URLs** (replace localhost)
3. **Update API keys** in HTTP Request nodes
4. **Add OpenAI credentials**
5. **Activate workflow**
6. **Test from chatbot UI**
7. **Monitor execution logs**

---

The workflow JSON file is ready to import. Just update the API URLs and keys, and you're good to go!

