# AI-Enhanced Match Detection Workflow Guide

## Overview

This workflow uses **AI (OpenAI)** to intelligently compare found items with lost items, providing more accurate matching than simple string similarity algorithms.

## Important: How AI Accesses Data

**The AI does NOT have direct database access.** Here's how it works:

1. **Workflow fetches data**: The workflow makes API calls to get lost items and found items from your database
2. **Data is formatted**: The workflow formats this data into text
3. **Data is sent to AI**: The formatted data is sent to OpenAI in the API request
4. **AI analyzes**: The AI analyzes ONLY the data provided in that specific request
5. **AI responds**: The AI returns match results based on the provided data

**Key Points:**
- ✅ AI sees all the data you send it (lost items, found items, descriptions, etc.)
- ❌ AI does NOT have persistent memory between requests
- ❌ AI does NOT directly query your database
- ❌ AI does NOT remember previous matches

Each time the workflow runs, it fetches fresh data from your database and sends it to the AI for analysis.

## Features

✅ **AI-Powered Matching**: Uses GPT-4o-mini to analyze and compare items  
✅ **Intelligent Comparison**: Considers item names, classes, descriptions, locations, and dates  
✅ **Confidence Scoring**: Each match includes a confidence percentage (60%+ threshold)  
✅ **Reasoning Provided**: AI explains why items match  
✅ **Email Notifications**: Automatically notifies students when matches are found  
✅ **In-App Notifications**: Creates notifications in the system  

## Workflow Files

**Option 1 - Webhook Trigger (Immediate)**: `n8n-workflow-match-detection-ai.json`
- Triggers when a found item is approved
- Efficient: Only runs when needed
- Immediate matching

**Option 2 - Scheduled Trigger (Periodic)**: `n8n-workflow-match-detection-ai-scheduled.json`
- Runs on a schedule (e.g., daily at 6 AM)
- Catches matches that might have been missed
- Checks all found items against all lost items

**Recommendation**: Use BOTH workflows for best coverage:
- Webhook version for immediate matching when items are approved
- Scheduled version for periodic comprehensive checks

## How It Works

### Webhook Version (Immediate)

1. **Webhook Trigger**: Receives found item data when a found item is approved
2. **Extract Found Item**: Extracts details from the webhook payload
3. **Get All Lost Items**: Fetches all lost items from the API
4. **Prepare AI Comparison**: Formats data for AI analysis
5. **AI Match Analysis**: Uses OpenAI to compare found item with all lost items
6. **Process AI Matches**: Parses AI response and filters matches (confidence >= 60%)
7. **Check Has Matches**: Routes to notification/email if matches found
8. **Create Notification**: Creates in-app notification for each match
9. **Send Email Notification**: Sends email to student with match details
10. **Format Response**: Returns summary of matches found

### Scheduled Version (Periodic)

1. **Schedule Trigger**: Runs daily at 6 AM (configurable)
2. **Get All Found Items**: Fetches all approved found items from the API
3. **Get All Lost Items**: Fetches all approved lost items from the API
4. **Prepare Items**: Filters and formats items for comparison
5. **Prepare AI Comparison**: Formats data for AI analysis (for each found item)
6. **AI Match Analysis**: Uses OpenAI to compare each found item with all lost items
7. **Process AI Matches**: Parses AI response and filters matches (confidence >= 60%)
8. **Check Has Matches**: Routes to notification/email if matches found
9. **Create Notification**: Creates in-app notification for each match
10. **Send Email Notification**: Sends email to student with match details
11. **Format Response**: Returns summary of all matches found

## Setup Instructions

### Step 1: Import Workflow(s)

**For Immediate Matching (Recommended):**
1. Open n8n
2. Go to **Workflows** → **Import from File**
3. Select `n8n-workflow-match-detection-ai.json`
4. Click **Import**

**For Scheduled Matching (Optional but Recommended):**
1. Import `n8n-workflow-match-detection-ai-scheduled.json`
2. This runs periodically to catch missed matches

### Step 2: Configure OpenAI Credential

1. In the workflow, open the **"AI Match Analysis"** node
2. Click **"Credential to connect with"** dropdown
3. Click **"Create New Credential"**
4. Select **"OpenAI"**
5. Enter your OpenAI API key (get from https://platform.openai.com/api-keys)
6. Click **"Save"**
7. Select this credential in the node

### Step 3: Configure SMTP Email

1. Open the **"Send Email Notification"** node
2. In **"Credential to connect with"**, select your SMTP account
3. Verify the email fields:
   - **From Email**: `foundlost004@gmail.com`
   - **To Email**: `={{ $json.studentEmail }}` (expression)
   - **Subject**: Expression with item name
   - **HTML**: Expression with match details

### Step 4: Verify API Configuration

Check that the API URLs are correct:
- **Get All Lost Items** node: `https://ublf.x10.mx/api/v1/reports`
- **Create Notification** node: `https://ublf.x10.mx/api/v1/webhooks`

Both should have the API key header: `ublf-x10mx-2024-secure-api-key-7a9b3c2d1e4f6g8h`

### Step 5: Activate Workflow(s)

**For Webhook Version:**
1. Toggle **"Active"** switch to ON
2. Copy the webhook URL (shown in "Webhook Trigger" node)

**For Scheduled Version:**
1. Toggle **"Active"** switch to ON
2. Verify schedule time (default: 6 AM daily)
3. Adjust schedule if needed in "Schedule Trigger" node

### Step 6: Update PHP Code

Add webhook trigger in your PHP code when a found item is approved:

```php
// In your Item::approve() method or admin_action.php
$n8nWebhookUrl = Config::get('N8N_MATCH_DETECTION_WEBHOOK_URL');
// Or use: 'https://besmar.app.n8n.cloud/webhook/found-item-approved'

if (!empty($n8nWebhookUrl)) {
    $ch = curl_init($n8nWebhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'itemID' => $itemID,
            'itemName' => $itemName,
            'itemClass' => $itemClass,
            'description' => $description,
            'location' => $location,
            'dateFound' => $dateFound
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
```

## AI Matching Logic

The AI analyzes:
- **Item Names**: Handles variations, abbreviations, similar-sounding names
- **Item Classes**: Category matching (Electronics, Books, etc.)
- **Descriptions**: Semantic similarity in descriptions
- **Locations**: Geographic proximity or same location
- **Dates**: Temporal relevance (recent losses more relevant)

### Confidence Threshold

Only matches with **60% or higher confidence** are considered and notifications are sent.

### AI Response Format

The AI returns JSON like:
```json
[
  {
    "index": 0,
    "isMatch": true,
    "confidence": 85,
    "reasoning": "Item names are very similar, same class, and descriptions match"
  },
  {
    "index": 1,
    "isMatch": false,
    "confidence": 0,
    "reasoning": "Different item classes and names don't match"
  }
]
```

## Email Template

The email includes:
- Lost item details (name, class, description)
- Found item details (name, class, description)
- Match confidence percentage
- AI reasoning for the match
- Link to dashboard

## Performance Considerations

- **Limits to 100 recent lost items** to avoid excessive API calls
- Uses **GPT-4o-mini** (faster and cheaper than GPT-4)
- **Temperature: 0.3** for consistent results
- **Max tokens: 2000** for response

## Testing

### Test the Workflow

1. **Manual Test**:
   - In n8n, click **"Execute Workflow"** on the webhook trigger
   - Or use the "Test" button in the webhook node

2. **Test with cURL**:
   ```bash
   curl -X POST https://besmar.app.n8n.cloud/webhook/found-item-approved \
     -H "Content-Type: application/json" \
     -d '{
       "itemID": "123",
       "itemName": "iPhone 13",
       "itemClass": "Electronics",
       "description": "Black iPhone 13 with case",
       "location": "Library",
       "dateFound": "2024-12-07"
     }'
   ```

3. **Check Results**:
   - View execution logs in n8n
   - Check if emails were sent
   - Verify notifications were created

## Troubleshooting

### "Could not get parameter" error
- Ensure SMTP credential is configured
- Check that email fields use expressions (fx icon enabled)

### "No matches found"
- AI might not find matches above 60% confidence
- Check that lost items exist in the database
- Verify API is returning lost items correctly

### "OpenAI API error"
- Verify OpenAI credential is set up correctly
- Check API key is valid and has credits
- Ensure model name is correct (gpt-4o-mini)

### "Authentication failed" for API
- Verify API key in headers is correct
- Check API URL is accessible
- Ensure API endpoint exists

## Comparison: AI vs Original

| Feature | Original | AI-Enhanced |
|---------|----------|-------------|
| Matching Method | String similarity (Levenshtein) | AI semantic analysis |
| Handles Variations | Limited | Excellent |
| Context Understanding | None | High |
| Confidence Scoring | Basic (0-100) | AI-based with reasoning |
| False Positives | Higher | Lower |
| Processing Time | Fast | Slower (AI call) |
| Cost | Free | OpenAI API costs |

## Cost Estimate

Using GPT-4o-mini:
- **Input**: ~500-1000 tokens per comparison
- **Output**: ~100-200 tokens per match
- **Cost**: ~$0.001-0.002 per found item analyzed
- **100 items/month**: ~$0.10-0.20

## Next Steps

1. Import and configure the workflow
2. Test with sample data
3. Monitor AI match quality
4. Adjust confidence threshold if needed (in "Process AI Matches" node)
5. Consider adding more context (photos, timestamps) if available

