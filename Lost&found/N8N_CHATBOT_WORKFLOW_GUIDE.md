# n8n Chatbot Workflow Setup Guide

This guide provides step-by-step instructions for setting up the n8n workflow that powers the chatbot integration.

## Prerequisites

- n8n instance (cloud or self-hosted)
- OpenAI API key (for AI-powered responses)
- Access to your PHP API endpoints
- API key for authentication

## Workflow Overview

```
Webhook Trigger → Process Message → Intent Detection → Database Queries (if needed) → OpenAI → Format Response → Respond
```

## Step-by-Step Setup

### Step 1: Create Webhook Trigger

1. In n8n, create a new workflow
2. Add **Webhook** node
3. Configure:
   - **HTTP Method**: POST
   - **Path**: `/webhook/chatbot`
   - **Response Mode**: "Respond to Webhook"
   - **Authentication**: None (or API Key if you want extra security)

4. **Copy the Webhook URL** - You'll need this for `chat_handler.php`
   - Example: `https://your-n8n-instance.com/webhook/chatbot`

5. **Update `chat_handler.php`**:
   - Set `$n8nWebhookUrl` to your webhook URL
   - Or set environment variable `N8N_WEBHOOK_URL`

### Step 2: Message Processing Node

Add a **Code** node after the Webhook:

**Node Name**: "Process Message"

**JavaScript Code**:
```javascript
// Get input from webhook
const input = $input.first().json;
const message = input.message.toLowerCase().trim();
const studentNo = input.studentNo;
const studentName = input.studentName;
const studentEmail = input.studentEmail || '';

// Detect intent
let intent = 'general';
let entities = {};

// Check for keywords
if (message.includes('lost') || message.includes('report lost') || message.includes('i lost')) {
  intent = 'lost_item';
} else if (message.includes('found') || message.includes('report found')) {
  intent = 'found_item';
} else if (message.includes('my report') || message.includes('my item') || message.includes('my lost')) {
  intent = 'my_reports';
} else if (message.includes('search') || message.includes('find') || message.includes('looking for')) {
  intent = 'search';
} else if (message.includes('help') || message.includes('how') || message.includes('what can')) {
  intent = 'help';
} else if (message.includes('status') || message.includes('check') || message.includes('pending')) {
  intent = 'status';
}

// Extract item name if mentioned
const itemNameMatch = message.match(/(?:lost|found|looking for|searching for|find)\s+([a-z0-9\s]+?)(?:\s|$|\.|,)/i);
if (itemNameMatch) {
  entities.itemName = itemNameMatch[1].trim();
}

// Extract location if mentioned
const locationMatch = message.match(/(?:at|in|near|around)\s+([a-z0-9\s]+?)(?:\s|$|\.|,)/i);
if (locationMatch) {
  entities.location = locationMatch[1].trim();
}

return [{
  json: {
    intent,
    entities,
    originalMessage: input.message,
    studentNo,
    studentName,
    studentEmail,
    context: input
  }
}];
```

### Step 3: Intent Routing

Add an **IF** node after "Process Message":

**Node Name**: "Route Intent"

**Conditions**:
- `{{ $json.intent }}` equals `lost_item`
- `{{ $json.intent }}` equals `found_item`
- `{{ $json.intent }}` equals `my_reports`
- `{{ $json.intent }}` equals `search`
- Default: `general`

### Step 4: Database Query Nodes

#### For Lost Items / Search Intent

Add **HTTP Request** node:

**Node Name**: "Get Lost Items"

**Configuration**:
- **Method**: GET
- **URL**: `http://your-domain.com/api/v1/reports?search={{ $('Process Message').first().json.entities.itemName || $('Process Message').first().json.originalMessage }}`
- **Authentication**: Header Auth
- **Header Name**: `X-API-Key`
- **Header Value**: `your-api-key-here`
- **Response Format**: JSON

#### For My Reports Intent

Add **HTTP Request** node:

**Node Name**: "Get My Reports"

**Configuration**:
- **Method**: GET
- **URL**: `http://your-domain.com/api/v1/reports?studentNo={{ $('Process Message').first().json.studentNo }}`
- **Authentication**: Header Auth
- **Header Name**: `X-API-Key`
- **Header Value**: `your-api-key-here`
- **Response Format**: JSON

#### For Found Items Intent

Add **HTTP Request** node:

**Node Name**: "Get Found Items"

**Configuration**:
- **Method**: GET
- **URL**: `http://your-domain.com/api/v1/items?search={{ $('Process Message').first().json.entities.itemName || $('Process Message').first().json.originalMessage }}`
- **Authentication**: Header Auth
- **Header Name**: `X-API-Key`
- **Header Value**: `your-api-key-here`
- **Response Format**: JSON

### Step 5: OpenAI Integration

Add **OpenAI** node (or compatible LLM node):

**Node Name**: "Generate AI Response"

**Configuration**:
- **Resource**: Chat
- **Operation**: Create Message
- **Model**: `gpt-3.5-turbo` or `gpt-4`
- **System Message**:
```
You are a helpful assistant for a University Lost & Found system. Help students:
- Report lost items
- Search for lost or found items
- Check their report status
- Answer questions about the system

Be friendly, concise, and helpful. Use the provided context to give accurate information. 
If database results are provided, format them clearly in your response.
```

**User Message**:
```javascript
{{ $('Process Message').first().json.originalMessage }}

Context:
Student: {{ $('Process Message').first().json.studentName }} ({{ $('Process Message').first().json.studentNo }})
Intent: {{ $('Process Message').first().json.intent }}

{{ $('Get Lost Items').first() ? 'Lost Items Found:\n' + JSON.stringify($('Get Lost Items').first().json, null, 2) : '' }}
{{ $('Get My Reports').first() ? 'User Reports:\n' + JSON.stringify($('Get My Reports').first().json, null, 2) : '' }}
{{ $('Get Found Items').first() ? 'Found Items:\n' + JSON.stringify($('Get Found Items').first().json, null, 2) : '' }}
```

**API Key**: Your OpenAI API key

### Step 6: Format Response

Add **Code** node after OpenAI:

**Node Name**: "Format Response"

**JavaScript Code**:
```javascript
const intent = $('Process Message').first().json.intent;
const studentName = $('Process Message').first().json.studentName;
const aiResponse = $('Generate AI Response').first().json;

// Get AI reply
let reply = aiResponse.choices?.[0]?.message?.content || 
            aiResponse.message?.content || 
            aiResponse.reply || 
            'I apologize, but I couldn\'t generate a response. Please try again.';

// Get database results if available
let dbResults = null;
if ($('Get Lost Items').first()) {
  dbResults = $('Get Lost Items').first().json;
} else if ($('Get My Reports').first()) {
  dbResults = $('Get My Reports').first().json;
} else if ($('Get Found Items').first()) {
  dbResults = $('Get Found Items').first().json;
}

// Format response with database results if available
if (dbResults && dbResults.data && dbResults.data.length > 0) {
  // AI response already includes context, but we can enhance it
  reply = reply.trim();
}

return [{
  json: {
    reply: reply,
    intent: intent,
    hasResults: dbResults && dbResults.data && dbResults.data.length > 0,
    resultCount: dbResults?.count || 0,
    timestamp: new Date().toISOString()
  }
}];
```

### Step 7: Respond to Webhook

Add **Respond to Webhook** node:

**Node Name**: "Send Response"

**Configuration**:
- **Respond With**: JSON
- **Response Body**:
```json
{{ $json }}
```

**Response Code**: 200

### Step 8: Error Handling

Add **Error Trigger** node:

**Node Name**: "Error Handler"

**Configuration**:
- Connect to all nodes that might fail
- Add **Code** node for error response:

```javascript
const error = $input.first().json.error;
return [{
  json: {
    reply: 'I encountered an error processing your request. Please try again or contact admin for assistance.',
    error: error.message || 'Unknown error',
    timestamp: new Date().toISOString()
  }
}];
```

Connect error handler to "Send Response" node.

## Workflow Connections

```
Webhook
  ↓
Process Message
  ↓
Route Intent
  ├─→ [lost_item/search] → Get Lost Items → Generate AI Response
  ├─→ [my_reports] → Get My Reports → Generate AI Response
  ├─→ [found_item] → Get Found Items → Generate AI Response
  └─→ [general/help] → Generate AI Response
         ↓
    Format Response
         ↓
    Send Response
```

## Environment Variables

Set these in n8n (Settings → Environment Variables):

- `API_BASE_URL`: `http://your-domain.com/api/v1`
- `API_KEY`: Your API authentication key
- `OPENAI_API_KEY`: Your OpenAI API key

## Testing the Workflow

1. **Test Webhook**:
```bash
curl -X POST https://your-n8n.com/webhook/chatbot \
  -H "Content-Type: application/json" \
  -d '{
    "message": "I lost my phone",
    "studentNo": "TEST001",
    "studentName": "Test User",
    "studentEmail": "test@ub.edu.ph"
  }'
```

2. **Check Execution Logs** in n8n to see each step

3. **Test Different Intents**:
   - "I lost my phone" → should trigger lost_item intent
   - "Show my reports" → should trigger my_reports intent
   - "Help me" → should trigger help intent

## Troubleshooting

### Webhook Not Receiving Requests
- Check webhook URL in `chat_handler.php`
- Verify n8n workflow is active
- Check n8n logs for errors

### API Requests Failing
- Verify API key is correct
- Check API endpoint URLs
- Ensure API endpoints are accessible from n8n

### OpenAI Not Responding
- Verify OpenAI API key
- Check API quota/limits
- Review OpenAI node configuration

### Responses Not Formatting Correctly
- Check Code node syntax
- Verify JSON structure
- Review execution logs

## Advanced Features (Optional)

### Add Conversation Memory
- Store conversation history in database
- Pass previous messages to OpenAI for context

### Add Quick Actions
- Return buttons/links in response
- Format as: `{"reply": "...", "actions": [...]}`

### Add Rate Limiting
- Track requests per student
- Limit requests to prevent abuse

## Next Steps

1. Activate the workflow in n8n
2. Update `chat_handler.php` with webhook URL
3. Test from the chatbot UI
4. Monitor execution logs
5. Refine prompts and responses based on usage

