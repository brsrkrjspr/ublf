# n8n Priority Workflows - Quick Setup Guide

## ✅ All 4 Priority Workflows Created!

### Workflow Files
1. `n8n-workflow-match-detection.json` - Automated match detection
2. `n8n-workflow-approval-notifications.json` - Approval email notifications
3. `n8n-workflow-scheduled-cleanup.json` - Daily cleanup (2 AM)
4. `n8n-workflow-daily-report.json` - Daily statistics report (8 AM)

## Quick Setup (5 Steps)

### Step 1: Import Workflows
1. Open n8n
2. Workflows → Import from File
3. Import all 4 JSON files

### Step 2: Update API URLs
In each workflow, update "Call PHP API" nodes:
- Replace `http://localhost/api/v1` with your actual API URL
- Update API key: `your-secret-api-key-change-this`

### Step 3: Configure Email
In workflows with email nodes:
- Add email credentials (Gmail, SendGrid, etc.)
- Update from/to email addresses

### Step 4: Activate Workflows
- Toggle "Active" switch ON for all workflows
- Copy webhook URLs for match detection and approval notifications

### Step 5: Update PHP Code
Add webhook triggers to:
- `Item::approve()` - trigger match detection
- `admin_action.php` - trigger approval notifications

## Webhook URLs to Add to Config

Add to `.env`:
```
N8N_MATCH_DETECTION_WEBHOOK_URL=https://your-n8n.com/webhook/found-item-approved
N8N_APPROVAL_NOTIFICATION_WEBHOOK_URL=https://your-n8n.com/webhook/approval-action
```

## What Each Workflow Does

### 1. Match Detection
- **When**: Found item approved
- **Does**: Searches for matching lost items → Creates notifications → Sends emails
- **Benefit**: Automatic matching, no manual work

### 2. Approval Notifications
- **When**: Admin approves/rejects anything
- **Does**: Creates notification → Sends email to student
- **Benefit**: Instant student notifications

### 3. Scheduled Cleanup
- **When**: Daily at 2 AM
- **Does**: Deletes notifications older than 30 days
- **Benefit**: Keeps database clean automatically

### 4. Daily Report
- **When**: Daily at 8 AM
- **Does**: Gets stats → Formats report → Emails admin
- **Benefit**: Admin stays informed automatically

## Testing

### Test Match Detection
```bash
curl -X POST YOUR_N8N_WEBHOOK/found-item-approved \
  -H "Content-Type: application/json" \
  -d '{"itemID":1,"itemName":"iPhone","itemClass":"Electronics"}'
```

### Test Approval Notification
```bash
curl -X POST YOUR_N8N_WEBHOOK/approval-action \
  -H "Content-Type: application/json" \
  -d '{"action":"approve","type":"report","itemID":1,"adminID":1,"studentNo":"TEST001","itemName":"iPhone","studentName":"Test","studentEmail":"test@ub.edu.ph"}'
```

## Next Steps

1. ✅ Import workflows
2. ✅ Configure URLs and keys
3. ✅ Set up email
4. ✅ Activate workflows
5. ✅ Integrate with PHP
6. ✅ Test everything
7. ✅ Monitor logs

---

**All workflows are ready!** See `N8N_PRIORITY_WORKFLOWS_GUIDE.md` for detailed instructions.

