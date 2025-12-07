# Fix: Process Message Node Error

## Error
```
Cannot read properties of undefined (reading 'toLowerCase') [line 3]
```

## Cause
The "Process Message" node is trying to access `input.message` but it's undefined. This happens when the webhook receives data in a different format than expected.

## Solution

### Option 1: Update the Code (Recommended)

Replace the code in your "Process Message" node with this more defensive version:

```javascript
// Get input from webhook - handle different input formats
const input = $input.first().json || $input.first() || {};
const rawInput = input.body || input.json || input.query || input;

// Extract message from various possible locations
const rawMessage = rawInput.message || input.message || '';
const message = (rawMessage || '').toString().toLowerCase().trim();

// Extract other fields with defaults
const studentNo = rawInput.studentNo || input.studentNo || '';
const studentName = rawInput.studentName || input.studentName || '';
const studentEmail = rawInput.studentEmail || input.studentEmail || '';

// Detect intent
let intent = 'general';
let entities = {};

// Only process if we have a message
if (message) {
  // Intent detection
  if (message.includes('lost') || message.includes('report lost') || message.includes('i lost')) {
    intent = 'lost_item';
  } else if (message.includes('found') || message.includes('report found')) {
    intent = 'found_item';
  } else if (message.includes('my report') || message.includes('my item') || message.includes('my lost') || message.includes('what did i report')) {
    intent = 'my_reports';
  } else if (message.includes('search') || message.includes('find') || message.includes('looking for')) {
    intent = 'search';
  } else if (message.includes('help') || message.includes('how') || message.includes('what can') || message.includes('assist')) {
    intent = 'help';
  } else if (message.includes('status') || message.includes('check') || message.includes('pending')) {
    intent = 'status';
  }

  // Extract item name if mentioned
  const itemPatterns = [
    /(?:lost|found|looking for|searching for|find|looking|search)\s+(?:my|a|an|the)?\s*([a-z0-9\s]+?)(?:\s|$|\.|,|at|in)/i,
    /(?:i|i'm|im)\s+(?:looking for|searching for|finding)\s+([a-z0-9\s]+?)(?:\s|$|\.|,)/i
  ];

  for (const pattern of itemPatterns) {
    const match = message.match(pattern);
    if (match && match[1]) {
      entities.itemName = match[1].trim();
      break;
    }
  }

  // Extract location if mentioned
  const locationMatch = message.match(/(?:at|in|near|around|from)\s+([a-z0-9\s]+?)(?:\s|$|\.|,)/i);
  if (locationMatch && locationMatch[1]) {
    entities.location = locationMatch[1].trim();
  }
}

return [{
  json: {
    intent: intent,
    entities: entities,
    originalMessage: rawMessage || message,
    studentNo: studentNo,
    studentName: studentName,
    studentEmail: studentEmail,
    timestamp: rawInput.timestamp || input.timestamp || new Date().toISOString(),
    sessionId: rawInput.sessionId || input.sessionId || ''
  }
}];
```

### Option 2: Check Webhook Configuration

1. **Click on "Webhook Trigger" node**
2. **Check "Response Mode"**: Should be "Respond to Webhook"
3. **Check "Path"**: Should be `chatbot`
4. **In n8n, test the webhook**:
   - Click "Webhook Trigger" node
   - Click "Test step"
   - Send this test data:
   ```json
   {
     "message": "hello",
     "studentNo": "TEST001",
     "studentName": "Test User",
     "studentEmail": "test@ub.edu.ph"
   }
   ```
5. **Check what the "Process Message" node receives**:
   - After testing, click on "Process Message" node
   - Check the "Input" tab to see what data it received
   - This will show you the actual data structure

### Option 3: Add Debug Logging

Temporarily add this at the start of the "Process Message" code to see what's being received:

```javascript
// Debug: Log the input
console.log('Input received:', JSON.stringify($input.first(), null, 2));
console.log('Input JSON:', JSON.stringify($input.first().json, null, 2));

// Then continue with the rest of the code...
```

## Most Common Issue

The webhook might be receiving data in `input.body` instead of `input.message`. The updated code above handles both cases.

## Quick Fix Steps

1. Open your n8n workflow
2. Click "Process Message" node
3. Replace the code with the updated version above
4. Save the workflow
5. Test again

The updated code will handle:
- ✅ Data in `input.message`
- ✅ Data in `input.body.message`
- ✅ Data in `input.json.message`
- ✅ Missing/undefined values
- ✅ Different data structures

