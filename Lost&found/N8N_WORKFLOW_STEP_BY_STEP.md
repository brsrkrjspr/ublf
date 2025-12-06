# n8n Workflow - Step-by-Step Construction Guide

Follow these exact steps to build your chatbot workflow in n8n.

## Prerequisites

- n8n account (you have: besmar.app.n8n.cloud)
- OpenAI API key
- Your API URL and API key ready

---

## STEP 1: Create New Workflow

1. Open n8n: https://besmar.app.n8n.cloud
2. Click **"Workflows"** in left sidebar
3. Click **"+ Add workflow"** button (top right)
4. Click on "Untitled" at top, rename to: **"UB Lost & Found Chatbot"**

---

## STEP 2: Add Webhook Trigger

1. Click **"+ Add node"** button
2. Type "webhook" in search box
3. Click on **"Webhook"** node

4. **Configure Webhook Node:**
   - **HTTP Method**: Select **"POST"** from dropdown
   - **Path**: Type `chatbot` (without quotes)
   - **Response Mode**: Select **"Respond to Webhook"** from dropdown
   - **Authentication**: Leave as **"None"**

5. Click **"Save"** button (top right of screen)

6. **Copy Webhook URL:**
   - Look at the top of the Webhook node
   - You'll see a URL like: `https://besmar.app.n8n.cloud/webhook/chatbot`
   - **Copy this entire URL**

7. **Update Your Config:**
   - Open `.env` file in `htdocs/` directory
   - Add line: `N8N_WEBHOOK_URL=your-copied-url-here`
   - Or update `chat_handler.php` line 28

---

## STEP 3: Add Process Message Node

1. Click **"+ Add node"** (appears after Webhook node)
2. Type "code" in search
3. Click **"Code"** node
4. **Rename node**: Double-click "Code" → Type "Process Message"

5. **Connect Nodes:**
   - Hover over Webhook node → See small dot on right
   - Click and drag to Process Message node
   - Connection line appears

6. **Configure Code Node:**
   - **Mode**: Select **"Run Once for All Items"** from dropdown
   - **JavaScript Code**: Delete existing code, paste this:

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

// Intent detection
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
    intent: intent,
    entities: entities,
    originalMessage: input.message,
    studentNo: studentNo,
    studentName: studentName,
    studentEmail: studentEmail,
    timestamp: input.timestamp || new Date().toISOString()
  }
}];
```

7. Click **"Execute Node"** button to test (optional)

---

## STEP 4: Add Route Intent Node (IF)

1. Click **"+ Add node"** after Process Message
2. Type "if" in search
3. Click **"IF"** node
4. **Rename**: "Route Intent"

5. **Connect**: Process Message → Route Intent

6. **Configure IF Node:**
   - **Mode**: Select **"Rules"** from dropdown
   - Click **"+ Add Condition"** button

   **Condition 1:**
   - **Value 1**: Click field → Type: `{{ $json.intent }}`
   - **Operation**: Select **"equals"**
   - **Value 2**: Type: `lost_item`
   - **Rename Output**: Click "true" label → Type "Lost Item"

   **Condition 2:** (Click "+ Add Condition" again)
   - **Value 1**: `{{ $json.intent }}`
   - **Operation**: **equals**
   - **Value 2**: `search`
   - **Rename Output**: "Search"

   **Condition 3:** (+ Add Condition)
   - **Value 1**: `{{ $json.intent }}`
   - **Operation**: **equals**
   - **Value 2**: `my_reports`
   - **Rename Output**: "My Reports"

   **Condition 4:** (+ Add Condition)
   - **Value 1**: `{{ $json.intent }}`
   - **Operation**: **equals**
   - **Value 2**: `found_item`
   - **Rename Output**: "Found Item"

   **Default Output:**
   - Click "false" label → Rename to "General"

7. You should now have 5 outputs from IF node

---

## STEP 5: Add Get Lost Items Node

1. Click **"+ Add node"**
2. Type "http" in search
3. Click **"HTTP Request"** node
4. **Rename**: "Get Lost Items"

5. **Connect TWO outputs to this node:**
   - Drag from "Lost Item" output → Get Lost Items
   - Drag from "Search" output → Get Lost Items (same node)

6. **Configure HTTP Request:**
   - **Method**: Select **"GET"**
   - **URL**: Click field → Type:
     ```
     http://localhost/api/v1/reports?search={{ $('Process Message').first().json.entities.itemName || $('Process Message').first().json.originalMessage }}
     ```
     (Replace `localhost` with your domain if needed)

   - **Authentication**: Select **"Header Auth"** from dropdown
   - **Name**: Type `X-API-Key`
   - **Value**: Type your API key (from Config.php)

   - **Options** (click to expand):
     - **Response Format**: Select **"JSON"**

---

## STEP 6: Add Get My Reports Node

1. Add another **"HTTP Request"** node
2. **Rename**: "Get My Reports"

3. **Connect**: "My Reports" output → Get My Reports

4. **Configure:**
   - **Method**: **GET**
   - **URL**: 
     ```
     http://localhost/api/v1/reports?studentNo={{ $('Process Message').first().json.studentNo }}
     ```
   - **Authentication**: **Header Auth**
   - **Name**: `X-API-Key`
   - **Value**: Your API key
   - **Response Format**: **JSON**

---

## STEP 7: Add Get Found Items Node

1. Add **"HTTP Request"** node
2. **Rename**: "Get Found Items"

3. **Connect**: "Found Item" output → Get Found Items

4. **Configure:**
   - **Method**: **GET**
   - **URL**: 
     ```
     http://localhost/api/v1/items?search={{ $('Process Message').first().json.entities.itemName || $('Process Message').first().json.originalMessage }}
     ```
   - **Authentication**: **Header Auth** (same as above)
   - **Response Format**: **JSON**

---

## STEP 8: Add Merge Node

1. Add **"Merge"** node (search "merge")
2. **Rename**: "Merge All Paths"

3. **Connect ALL paths to Merge:**
   - Get Lost Items → Merge All Paths
   - Get My Reports → Merge All Paths
   - Get Found Items → Merge All Paths
   - Route Intent "General" output → Merge All Paths (skip database)

4. **Configure:**
   - **Mode**: **Merge**
   - **Merge By**: **Append**

---

## STEP 9: Add OpenAI Node

1. Add **"OpenAI"** node (search "openai")
2. **Rename**: "Generate AI Response"

3. **Connect**: Merge All Paths → Generate AI Response

4. **Add OpenAI Credential:**
   - Click **"Credential to connect with"** dropdown
   - Click **"Create New Credential"**
   - Select **"OpenAI"**
   - Enter your OpenAI API key
   - Click **"Save"**

5. **Configure OpenAI Node:**
   - **Resource**: Select **"Chat"**
   - **Operation**: Select **"Create Message"**
   - **Model**: Select **"gpt-3.5-turbo"**

   - **System Message** (click to expand, then paste):
     ```
     You are a helpful assistant for a University Lost & Found system. Help students report lost items, search for items, check their reports, and answer questions. Be friendly, concise, and helpful. Use the provided context to give accurate information. Keep responses under 200 words.
     ```

   - **User Message** (paste this):
     ```
     User Question: {{ $('Process Message').first().json.originalMessage }}

     Student: {{ $('Process Message').first().json.studentName }} ({{ $('Process Message').first().json.studentNo }})
     Intent: {{ $('Process Message').first().json.intent }}

     Database Results:
     {{ $('Get Lost Items').first() ? 'Lost Items:\n' + JSON.stringify($('Get Lost Items').first().json, null, 2) : ($('Get My Reports').first() ? 'User Reports:\n' + JSON.stringify($('Get My Reports').first().json, null, 2) : ($('Get Found Items').first() ? 'Found Items:\n' + JSON.stringify($('Get Found Items').first().json, null, 2) : 'No database results')) }}

     Provide a helpful response.
     ```

   - **Options** (expand):
     - **Temperature**: `0.7`
     - **Max Tokens**: `500`

---

## STEP 10: Add Format Response Node

1. Add **"Code"** node
2. **Rename**: "Format Response"

3. **Connect**: Generate AI Response → Format Response

4. **Configure:**
   - **Mode**: **Run Once for All Items**
   - **JavaScript Code**:
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

---

## STEP 11: Add Respond to Webhook Node

1. Add **"Respond to Webhook"** node (search "respond")
2. **Rename**: "Send Response"

3. **Connect**: Format Response → Send Response

4. **Configure:**
   - **Respond With**: Select **"JSON"**
   - **Response Body**: Type `{{ $json }}`
   - **Response Code**: `200`

---

## STEP 12: Add Error Handling

1. Add **"Error Trigger"** node (search "error trigger")
2. **Rename**: "Error Handler"

3. **Add Error Connections:**
   - Right-click on **"Get Lost Items"** node
   - Select **"Add Error Connection"**
   - Connect to Error Handler
   - Repeat for: Get My Reports, Get Found Items, Generate AI Response

4. **Add Error Response Code Node:**
   - Add **"Code"** node after Error Handler
   - **Rename**: "Format Error Response"
   - **Code**:
   ```javascript
   return [{
     json: {
       reply: 'I encountered an error. Please try again or contact admin.',
       timestamp: new Date().toISOString()
     }
   }];
   ```

5. **Connect**: Format Error Response → Send Response

---

## STEP 13: Activate Workflow

1. Look at top right of n8n screen
2. Find **"Active"** toggle switch
3. Click to turn it **ON** (should turn green)
4. Workflow is now live!

---

## STEP 14: Test the Workflow

### Test from n8n:
1. Click on **"Webhook Trigger"** node
2. Click **"Test step"** button
3. In test panel, paste:
   ```json
   {
     "message": "I lost my phone",
     "studentNo": "TEST001",
     "studentName": "Test User",
     "studentEmail": "TEST001@ub.edu.ph"
   }
   ```
4. Click **"Execute Node"**
5. Check if response appears

### Test from Your App:
1. Open your dashboard
2. Click chatbot icon
3. Send message: "I lost my phone"
4. Check if AI response appears

---

## Final Checklist

Before going live, verify:

- [ ] Webhook URL copied to `.env` or `chat_handler.php`
- [ ] All API URLs updated (replace localhost if needed)
- [ ] API keys set in all HTTP Request nodes
- [ ] OpenAI API key added to credentials
- [ ] All nodes connected correctly
- [ ] Workflow is activated (green toggle)
- [ ] Tested with sample message

---

## Workflow Visual Layout

Your final workflow should look like this:

```
[Webhook] → [Process Message] → [Route Intent]
                                      ├─→ [Get Lost Items] ──┐
                                      ├─→ [Get My Reports] ──┤
                                      ├─→ [Get Found Items] ─┤
                                      └─→ [General] ──────────┤
                                                               │
                                                               ▼
                                                    [Merge All Paths]
                                                               │
                                                               ▼
                                                    [Generate AI Response]
                                                               │
                                                               ▼
                                                    [Format Response]
                                                               │
                                                               ▼
                                                    [Send Response]
```

---

## Troubleshooting Quick Fixes

**"Cannot read property" error:**
- Check node names match exactly
- Use `$('Node Name').first()` to access other nodes

**API requests fail:**
- Verify API URL is accessible from n8n
- Check API key is correct
- Test API endpoint directly with curl

**OpenAI not responding:**
- Verify API key is correct
- Check quota/limits
- Try gpt-3.5-turbo model

**Webhook not receiving:**
- Check workflow is activated
- Verify webhook URL in chat_handler.php
- Check n8n execution logs

---

## You're Done!

Your n8n workflow is now ready. The chatbot should work end-to-end!

For more details, see `N8N_WORKFLOW_SETUP_COMPLETE.md` or import `n8n-workflow-import.json` directly.

