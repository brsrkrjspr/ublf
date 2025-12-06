# n8n Complete Backend Automation - Implementation Guide

This guide explains how to implement n8n workflows for all backend processes.

## Overview

We've created:
1. **Webhook Receiver API** (`/api/v1/webhooks.php`) - Handles all n8n webhook calls
2. **Base64 Image Support** - Added to `FileUpload` class for n8n image handling
3. **Workflow Templates** - JSON files ready to import
4. **Documentation** - Complete guides and examples

## Architecture

```
UI Form → n8n Webhook → n8n Workflow → PHP Webhook API → PHP Classes → Database
```

## Step 1: Import Workflows

### Available Workflow Files

1. **Authentication**:
   - `n8n-workflow-login.json` - Student login
   - `n8n-workflow-signup.json` - Student registration (create similar to login)

2. **Reports**:
   - `n8n-workflow-create-lost-report.json` - Create lost item report
   - Create similar for found reports, delete, approve/reject

3. **Profile**:
   - Create workflows for update profile, upload photo, change password

4. **Notifications**:
   - Create workflows for get, mark read, mark all read

5. **Admin**:
   - Create workflows for approve/reject actions, dashboard stats

### Import Steps

1. Open n8n
2. Go to Workflows → Import from File
3. Select workflow JSON file
4. Update configuration (see below)
5. Activate workflow

## Step 2: Configure Workflows

### Update API URLs

In each workflow, update the "Call PHP API" node:

**Current**: `http://localhost/api/v1/webhooks`
**Update to**: Your actual API URL (e.g., `https://yourdomain.com/api/v1/webhooks`)

### Update API Keys

In "Call PHP API" node headers:
- **Name**: `X-API-Key`
- **Value**: Your API key from Config.php or .env

### Get Webhook URLs

After importing and activating:
1. Click on "Webhook Trigger" node
2. Copy the webhook URL
3. Use this in your UI forms

## Step 3: Update UI to Use n8n

### Option A: Direct n8n Webhooks (Recommended)

Update forms to POST directly to n8n webhooks:

**Example - Login Form**:
```javascript
// Instead of POST to login.php
fetch('https://your-n8n.com/webhook/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    studentNo: document.getElementById('studentNo').value,
    password: document.getElementById('password').value
  })
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    // Handle success - create session, redirect
    window.location.href = 'dashboard.php';
  } else {
    // Show error
    alert(data.message);
  }
});
```

**Example - Create Lost Report**:
```javascript
// Convert file to base64
const fileInput = document.getElementById('lostPhoto');
let photoBase64 = null;

if (fileInput.files[0]) {
  const reader = new FileReader();
  reader.onload = function(e) {
    photoBase64 = e.target.result; // data:image/jpeg;base64,...
    
    // Send to n8n
    fetch('https://your-n8n.com/webhook/create-lost-report', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        studentNo: '<?php echo $_SESSION["student"]["StudentNo"]; ?>',
        itemName: document.getElementById('lostItemName').value,
        itemClass: document.getElementById('lostItemClass').value,
        description: document.getElementById('lostDescription').value,
        dateOfLoss: document.getElementById('lostDate').value,
        lostLocation: document.getElementById('lostLocation').value,
        photo: photoBase64
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    });
  };
  reader.readAsDataURL(fileInput.files[0]);
} else {
  // No photo, send without photo
  // ... same fetch call without photo field
}
```

### Option B: Hybrid Approach (Fallback)

Keep PHP processing as fallback, use n8n when available:

```php
<?php
// In dashboard.php
$useN8n = getenv('USE_N8N') === 'true';
$n8nWebhookUrl = getenv('N8N_WEBHOOK_URL');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_lost'])) {
    if ($useN8n && $n8nWebhookUrl) {
        // Use n8n workflow
        // Redirect to n8n processing endpoint
    } else {
        // Fallback to direct PHP processing
        $result = handleLostItemReport($reportItem, $fileUpload);
    }
}
?>
```

## Step 4: Workflow Actions Reference

### Available Actions in webhooks.php

| Action | Description | Required Fields |
|--------|-------------|----------------|
| `login` | Student login | studentNo, password |
| `signup` | Student registration | studentNo, studentName, phoneNo, email, password |
| `create_lost_report` | Create lost item report | studentNo, itemName, itemClass, description, dateOfLoss, lostLocation, photo (optional) |
| `create_found_report` | Create found item report | adminID, itemName, itemClass, description, dateFound, locationFound, photo (optional) |
| `delete_report` | Delete report | reportID, studentNo |
| `approve_report` | Approve lost item report | reportID, adminID |
| `reject_report` | Reject lost item report | reportID, adminID |
| `update_profile` | Update student profile | studentNo, studentName, phoneNo, email, bio (optional) |
| `upload_profile_photo` | Upload profile photo | studentNo, photo (base64) |
| `change_password` | Change password | studentNo, currentPassword, newPassword |
| `get_notifications` | Get notifications | studentNo, limit (optional) |
| `mark_notification_read` | Mark notification as read | notificationID, studentNo |
| `mark_all_notifications_read` | Mark all as read | studentNo |
| `approve_profile_photo` | Approve profile photo | photoID, adminID |
| `reject_profile_photo` | Reject profile photo | photoID, adminID |
| `approve_found_item` | Approve found item | itemID, adminID |
| `reject_found_item` | Reject found item | itemID, adminID |
| `get_dashboard_stats` | Get admin dashboard stats | (none) |

## Step 5: Testing

### Test Webhook API Directly

```bash
# Test Login
curl -X POST http://localhost/api/v1/webhooks \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "action": "login",
    "studentNo": "TEST001",
    "password": "test123"
  }'

# Test Create Lost Report
curl -X POST http://localhost/api/v1/webhooks \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "action": "create_lost_report",
    "studentNo": "TEST001",
    "itemName": "iPhone",
    "itemClass": "Electronics",
    "description": "Lost my iPhone",
    "dateOfLoss": "2024-01-15",
    "lostLocation": "Library"
  }'
```

### Test n8n Workflow

```bash
# Test Login Workflow
curl -X POST https://your-n8n.com/webhook/login \
  -H "Content-Type: application/json" \
  -d '{
    "studentNo": "TEST001",
    "password": "test123"
  }'
```

## Step 6: Monitoring

### n8n Execution Logs

1. Go to n8n → Executions
2. View execution history
3. Click on execution to see step-by-step details
4. Check for errors in any node

### PHP Error Logs

Check PHP error logs for webhook API issues:
- Location: Usually in `htdocs/` or server error log
- Look for: "Webhook Error" messages

## Benefits of n8n Automation

1. **Visual Workflow**: See all processes visually
2. **Easy Debugging**: Step-by-step execution logs
3. **Extensibility**: Easy to add:
   - Email notifications
   - SMS alerts
   - Social media posts
   - External API integrations
   - Scheduled tasks
4. **Centralized Logic**: All business logic in one place
5. **Version Control**: Export workflows as JSON

## Next Steps

1. ✅ Import workflow templates
2. ✅ Configure API URLs and keys
3. ✅ Test each workflow
4. ✅ Update UI forms to use n8n
5. ✅ Monitor execution logs
6. ✅ Add additional automation (email, SMS, etc.)

## Troubleshooting

### Workflow Not Receiving Requests
- Check webhook URL is correct
- Verify workflow is activated
- Check n8n execution logs

### API Calls Failing
- Verify API URL is accessible from n8n
- Check API key is correct
- Test API endpoint directly with curl

### Image Upload Issues
- Verify base64 encoding is correct
- Check file size limits
- Ensure FileUpload class has uploadBase64Image method

### Session Issues
- n8n workflows don't maintain PHP sessions
- Return user data, let PHP create session
- Or use JWT tokens for authentication

---

All workflows are ready to import and configure!

