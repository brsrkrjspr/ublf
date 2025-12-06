# n8n Priority Workflows - Complete Guide

This guide covers the 4 priority workflows that provide the most value for your Lost & Found system.

## Workflows Created

### 1. ✅ Automated Match Detection
**File**: `n8n-workflow-match-detection.json`

**Purpose**: Automatically finds matches when a found item is approved

**Trigger**: Webhook when found item is approved
**Path**: `/webhook/found-item-approved`

**Flow**:
1. Receives found item data
2. Searches for matching lost items
3. Calculates match scores
4. Creates notifications for students
5. Sends email notifications

**How to Trigger**:
After admin approves a found item, call this webhook:
```javascript
// In your PHP code after Item::approve()
$n8nWebhookUrl = Config::get('N8N_MATCH_DETECTION_WEBHOOK_URL');
curl_post($n8nWebhookUrl, [
    'itemID' => $itemID,
    'itemName' => $itemName,
    'itemClass' => $itemClass,
    'description' => $description
]);
```

---

### 2. ✅ Approval Notifications
**File**: `n8n-workflow-approval-notifications.json`

**Purpose**: Sends email notifications when items are approved/rejected

**Trigger**: Webhook when admin approves/rejects
**Path**: `/webhook/approval-action`

**Flow**:
1. Receives approval action data
2. Formats notification message
3. Creates in-app notification
4. Sends email to student

**How to Trigger**:
After admin action, call this webhook:
```javascript
// After ReportItem::approve() or Item::approve()
$n8nWebhookUrl = Config::get('N8N_APPROVAL_NOTIFICATION_WEBHOOK_URL');
curl_post($n8nWebhookUrl, [
    'action' => 'approve', // or 'reject'
    'type' => 'report', // or 'found_item', 'profile_photo'
    'itemID' => $itemID,
    'adminID' => $adminID,
    'studentNo' => $studentNo,
    'itemName' => $itemName,
    'studentName' => $studentName,
    'studentEmail' => $studentEmail
]);
```

---

### 3. ✅ Scheduled Cleanup
**File**: `n8n-workflow-scheduled-cleanup.json`

**Purpose**: Daily cleanup of old notifications

**Trigger**: Cron (daily at 2 AM)
**Schedule**: `0 2 * * *` (2:00 AM daily)

**Flow**:
1. Cron triggers daily
2. Calls cleanup API
3. Deletes notifications older than 30 days
4. Sends report email to admin (optional)

**No manual trigger needed** - runs automatically

---

### 4. ✅ Daily Report
**File**: `n8n-workflow-daily-report.json`

**Purpose**: Generates and emails daily statistics report

**Trigger**: Cron (daily at 8 AM)
**Schedule**: `0 8 * * *` (8:00 AM daily)

**Flow**:
1. Cron triggers daily
2. Gets dashboard statistics
3. Gets recent reports and items
4. Formats HTML report
5. Emails to admin

**No manual trigger needed** - runs automatically

---

## Setup Instructions

### Step 1: Import Workflows

1. Open n8n
2. Go to Workflows → Import from File
3. Import each JSON file:
   - `n8n-workflow-match-detection.json`
   - `n8n-workflow-approval-notifications.json`
   - `n8n-workflow-scheduled-cleanup.json`
   - `n8n-workflow-daily-report.json`

### Step 2: Configure Each Workflow

#### Match Detection Workflow
1. Update "Call PHP API" node:
   - URL: `http://localhost/api/v1/webhooks` (or your domain)
   - API Key: Your API key
2. Update "Search Matching Lost Items" node:
   - URL: `http://localhost/api/v1/reports?search=...`
   - API Key: Your API key
3. Configure "Send Email Notification" node:
   - Add email credentials (Gmail, SendGrid, etc.)
   - Update from email: `lostfound@ub.edu.ph`
4. Copy webhook URL for triggering from PHP

#### Approval Notifications Workflow
1. Update "Call PHP API" node (same as above)
2. Configure "Send Email" node:
   - Add email credentials
   - Update from/to emails
3. Copy webhook URL for triggering from PHP

#### Scheduled Cleanup Workflow
1. Update "Call Cleanup API" node:
   - URL: `http://localhost/api/v1/webhooks`
   - API Key: Your API key
2. Configure "Send Report Email" node (optional):
   - Add email credentials
   - Update admin email
3. Verify cron schedule: `0 2 * * *` (2 AM daily)

#### Daily Report Workflow
1. Update all API nodes:
   - URLs: `http://localhost/api/v1/webhooks`, `/reports`, `/items`
   - API Keys: Your API key
2. Configure "Send Email Report" node:
   - Add email credentials
   - Update admin email: `admin@ub.edu.ph`
3. Verify cron schedule: `0 8 * * *` (8 AM daily)

### Step 3: Activate Workflows

1. Toggle "Active" switch ON for each workflow
2. Scheduled workflows (cleanup, daily report) will run automatically
3. Webhook workflows wait for triggers

### Step 4: Integrate with PHP

#### Update Item::approve() to trigger match detection

Add to `htdocs/classes/Item.php` after approval:

```php
// After Item::approve() succeeds
$n8nWebhookUrl = Config::get('N8N_MATCH_DETECTION_WEBHOOK_URL');
if ($n8nWebhookUrl) {
    // Trigger match detection
    $ch = curl_init($n8nWebhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'itemID' => $itemID,
        'itemName' => $itemName,
        'itemClass' => $itemClass,
        'description' => $description
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_exec($ch);
    curl_close($ch);
}
```

#### Update approval actions to trigger notifications

Add to `htdocs/public/admin_action.php` after approval:

```php
// After ReportItem::approve() or Item::approve()
$n8nWebhookUrl = Config::get('N8N_APPROVAL_NOTIFICATION_WEBHOOK_URL');
if ($n8nWebhookUrl) {
    $ch = curl_init($n8nWebhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'action' => 'approve',
        'type' => 'report',
        'itemID' => $reportID,
        'adminID' => $adminID,
        'studentNo' => $studentNo,
        'itemName' => $itemName,
        'studentName' => $studentName,
        'studentEmail' => $studentEmail
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_exec($ch);
    curl_close($ch);
}
```

### Step 5: Update Config

Add to `.env` file:
```
N8N_MATCH_DETECTION_WEBHOOK_URL=https://your-n8n.com/webhook/found-item-approved
N8N_APPROVAL_NOTIFICATION_WEBHOOK_URL=https://your-n8n.com/webhook/approval-action
```

---

## Testing

### Test Match Detection
```bash
curl -X POST YOUR_N8N_WEBHOOK_URL/found-item-approved \
  -H "Content-Type: application/json" \
  -d '{
    "itemID": 1,
    "itemName": "iPhone",
    "itemClass": "Electronics",
    "description": "Black iPhone found"
  }'
```

### Test Approval Notification
```bash
curl -X POST YOUR_N8N_WEBHOOK_URL/approval-action \
  -H "Content-Type: application/json" \
  -d '{
    "action": "approve",
    "type": "report",
    "itemID": 1,
    "adminID": 1,
    "studentNo": "TEST001",
    "itemName": "iPhone",
    "studentName": "Test User",
    "studentEmail": "TEST001@ub.edu.ph"
  }'
```

### Test Scheduled Workflows
- Wait for scheduled time, or
- Manually trigger in n8n (click "Execute Workflow")

---

## Workflow Benefits

### Match Detection
- ✅ Automatic matching when items approved
- ✅ Email notifications to students
- ✅ Match scoring algorithm
- ✅ No manual intervention needed

### Approval Notifications
- ✅ Instant email notifications
- ✅ In-app notifications created
- ✅ Works for all approval types
- ✅ Professional email formatting

### Scheduled Cleanup
- ✅ Automatic database maintenance
- ✅ Prevents notification table bloat
- ✅ Optional admin reports
- ✅ Configurable retention period

### Daily Report
- ✅ Automated statistics
- ✅ Recent activity summary
- ✅ Professional HTML emails
- ✅ Admin stays informed

---

## Monitoring

### Check Execution Logs
1. Go to n8n → Executions
2. View execution history
3. Check for errors
4. Review execution times

### Check Email Delivery
- Verify emails are being sent
- Check spam folders
- Test with different email providers

### Check Match Detection
- Verify matches are found
- Check match scores
- Ensure notifications are created

---

## Troubleshooting

### Match Detection Not Working
- Check webhook URL is correct
- Verify API endpoints are accessible
- Check n8n execution logs
- Ensure found items are being approved

### Emails Not Sending
- Verify email credentials in n8n
- Check email provider limits
- Test email node separately
- Check spam folders

### Scheduled Workflows Not Running
- Verify workflows are activated
- Check cron expressions
- Verify n8n instance is running
- Check execution logs

### API Calls Failing
- Verify API URLs are correct
- Check API keys match
- Test API endpoints directly
- Check network connectivity

---

## Next Steps

1. ✅ Import all 4 workflows
2. ✅ Configure API URLs and keys
3. ✅ Set up email credentials
4. ✅ Activate workflows
5. ✅ Integrate with PHP code
6. ✅ Test each workflow
7. ✅ Monitor execution logs
8. ✅ Adjust schedules if needed

---

All 4 priority workflows are ready to use! These will automate the most valuable processes in your system.

