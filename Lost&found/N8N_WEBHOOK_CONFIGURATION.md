# n8n Webhook Configuration

## Approval Notifications Webhook

Your approval notifications webhook has been configured!

**Webhook URL**: `https://besmar.app.n8n.cloud/webhook/approval-action`

## What Was Updated

### 1. Config.php
- Added `N8N_APPROVAL_WEBHOOK_URL` configuration key
- Default value set to your webhook URL

### 2. admin_action.php
- Added webhook triggers after all approval/rejection actions:
  - Profile photo approvals/rejections
  - Lost item report approvals/rejections
  - Found item approvals/rejections

## How It Works

When an admin approves or rejects any item, the system will:

1. **Process the action** (update database, create notification)
2. **Trigger n8n webhook** (send data to your n8n workflow)
3. **n8n workflow** receives the data and can:
   - Send email notifications
   - Send SMS notifications
   - Create additional notifications
   - Log the action
   - Any other automation you've set up

## Webhook Data Format

The webhook sends the following data structure:

### Profile Photo Approval/Rejection
```json
{
  "action": "approve" | "reject",
  "type": "profile_photo",
  "photoID": 123,
  "adminID": 1,
  "studentNo": "2220009",
  "studentName": "Kim Andrei",
  "studentEmail": "student@ub.edu.ph",
  "photoURL": "assets/uploads/profile_photo.jpg"
}
```

### Lost Item Report Approval/Rejection
```json
{
  "action": "approve" | "reject",
  "type": "report",
  "reportID": 456,
  "adminID": 1,
  "studentNo": "2220009",
  "studentName": "Kim Andrei",
  "itemName": "Lost Phone"
}
```

### Found Item Approval/Rejection
```json
{
  "action": "approve" | "reject",
  "type": "found_item",
  "itemID": 789,
  "adminID": 1,
  "itemName": "Found Wallet",
  "description": "Black leather wallet"
}
```

## Configuration

### Option 1: Environment Variable (Recommended)
Set in your hosting environment or `.env` file:
```env
N8N_APPROVAL_WEBHOOK_URL=https://besmar.app.n8n.cloud/webhook/approval-action
```

### Option 2: Config.php Default
The default is already set in `Config.php`, so it will work out of the box!

## Testing

### Test the Webhook
1. Log in as admin
2. Approve or reject any pending item (photo, report, or found item)
3. Check your n8n workflow execution logs
4. You should see the webhook trigger with the approval data

### Manual Test (cURL)
```bash
curl -X POST https://besmar.app.n8n.cloud/webhook/approval-action \
  -H "Content-Type: application/json" \
  -d '{
    "action": "approve",
    "type": "profile_photo",
    "photoID": 1,
    "adminID": 1,
    "studentNo": "2220009",
    "studentName": "Test Student",
    "studentEmail": "test@ub.edu.ph"
  }'
```

## n8n Workflow Setup

Your n8n workflow should:

1. **Webhook Trigger** - Receives POST requests
2. **Extract Data** - Parse the JSON payload
3. **Conditional Logic** - Check `action` (approve/reject) and `type`
4. **Send Notifications** - Email, SMS, or in-app notifications
5. **Respond** - Return success response

## Troubleshooting

### Webhook Not Triggering
- Check that `N8N_APPROVAL_WEBHOOK_URL` is set correctly
- Verify the webhook URL is accessible
- Check PHP error logs for curl errors
- Ensure the webhook is active in n8n

### Webhook Timing Out
- The webhook call has a 5-second timeout
- It runs asynchronously (doesn't block the admin action)
- If it fails, the admin action still completes successfully

### Missing Data
- Verify the database queries are returning data
- Check that student/item data exists before approval
- Review the webhook payload in n8n execution logs

## Next Steps

1. ✅ Webhook URL configured
2. ✅ Webhook triggers added to admin actions
3. ⏭️ Test the webhook by approving/rejecting items
4. ⏭️ Set up email/SMS notifications in n8n workflow
5. ⏭️ Monitor n8n execution logs

---

**Your approval notifications webhook is now active!** 🎉

Every time an admin approves or rejects an item, your n8n workflow will be triggered automatically.

