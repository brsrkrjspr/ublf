# Complete n8n Workflows for All Backend Processes

This document provides n8n workflow templates for automating all backend processes.

## Workflow Categories

### 1. Authentication Workflows
- Login Workflow
- Signup Workflow

### 2. Report Management Workflows
- Create Lost Item Report
- Create Found Item Report
- Delete Report
- Approve/Reject Reports

### 3. Profile Management Workflows
- Update Profile
- Upload Profile Photo
- Change Password

### 4. Notification Workflows
- Get Notifications
- Mark Notification as Read
- Mark All as Read

### 5. Admin Workflows
- Approve/Reject Profile Photo
- Approve/Reject Found Item
- Get Dashboard Statistics

## Workflow Structure

All workflows follow this pattern:
```
Webhook Trigger → Validate Input → Process Data → Call PHP API → Format Response → Return to UI
```

## Base Configuration

All workflows need:
- **Webhook URL**: Your n8n instance webhook URL
- **API Base URL**: `http://localhost/api/v1` (or your domain)
- **API Key**: Your API key from Config.php

## Workflow Templates

### 1. Login Workflow

**Webhook Trigger**:
- Path: `login`
- Method: POST

**Process Input** (Code Node):
```javascript
const input = $input.first().json;
return [{
  json: {
    action: 'login',
    studentNo: input.studentNo,
    password: input.password
  }
}];
```

**Call PHP API** (HTTP Request):
- Method: POST
- URL: `{{ $env.API_BASE_URL }}/webhooks`
- Headers: `X-API-Key: {{ $env.API_KEY }}`
- Body: `{{ $json }}`

**Format Response** (Code Node):
```javascript
const response = $input.first().json;
return [{
  json: {
    success: response.success,
    user: response.user,
    message: response.message
  }
}];
```

**Respond to Webhook**: Return JSON

---

### 2. Signup Workflow

**Webhook Trigger**:
- Path: `signup`
- Method: POST

**Process Input** (Code Node):
```javascript
const input = $input.first().json;
return [{
  json: {
    action: 'signup',
    studentNo: input.studentNo,
    studentName: input.studentName,
    phoneNo: input.phoneNo,
    email: input.email,
    password: input.password
  }
}];
```

**Call PHP API**: Same as Login

**Format Response**: Same as Login

---

### 3. Create Lost Item Report Workflow

**Webhook Trigger**:
- Path: `create-lost-report`
- Method: POST

**Process Input** (Code Node):
```javascript
const input = $input.first().json;

// Convert file to base64 if provided
let photoBase64 = null;
if (input.photoFile) {
  // If file is provided, convert to base64
  photoBase64 = input.photoFile;
} else if (input.photo) {
  photoBase64 = input.photo;
}

return [{
  json: {
    action: 'create_lost_report',
    studentNo: input.studentNo,
    itemName: input.itemName,
    itemClass: input.itemClass,
    description: input.description,
    dateOfLoss: input.dateOfLoss,
    lostLocation: input.lostLocation,
    photo: photoBase64
  }
}];
```

**Call PHP API**: Same structure

---

### 4. Create Found Item Report Workflow

Similar to Create Lost Item Report, but:
- Path: `create-found-report`
- Action: `create_found_report`
- Includes `adminID` instead of `studentNo`

---

### 5. Delete Report Workflow

**Webhook Trigger**:
- Path: `delete-report`
- Method: POST

**Process Input**:
```javascript
const input = $input.first().json;
return [{
  json: {
    action: 'delete_report',
    reportID: input.reportID,
    studentNo: input.studentNo
  }
}];
```

---

### 6. Approve Report Workflow

**Webhook Trigger**:
- Path: `approve-report`
- Method: POST

**Process Input**:
```javascript
const input = $input.first().json;
return [{
  json: {
    action: 'approve_report',
    reportID: input.reportID,
    adminID: input.adminID
  }
}];
```

---

### 7. Update Profile Workflow

**Webhook Trigger**:
- Path: `update-profile`
- Method: POST

**Process Input**:
```javascript
const input = $input.first().json;
return [{
  json: {
    action: 'update_profile',
    studentNo: input.studentNo,
    studentName: input.studentName,
    phoneNo: input.phoneNo,
    email: input.email,
    bio: input.bio
  }
}];
```

---

### 8. Upload Profile Photo Workflow

**Webhook Trigger**:
- Path: `upload-profile-photo`
- Method: POST

**Process Input**:
```javascript
const input = $input.first().json;

// Convert file to base64
let photoBase64 = null;
if (input.photoFile) {
  photoBase64 = input.photoFile;
} else if (input.photo) {
  photoBase64 = input.photo;
}

return [{
  json: {
    action: 'upload_profile_photo',
    studentNo: input.studentNo,
    photo: photoBase64
  }
}];
```

---

### 9. Get Notifications Workflow

**Webhook Trigger**:
- Path: `get-notifications`
- Method: POST

**Process Input**:
```javascript
const input = $input.first().json;
return [{
  json: {
    action: 'get_notifications',
    studentNo: input.studentNo,
    limit: input.limit || 50
  }
}];
```

---

### 10. Mark Notification Read Workflow

**Webhook Trigger**:
- Path: `mark-notification-read`
- Method: POST

**Process Input**:
```javascript
const input = $input.first().json;
return [{
  json: {
    action: 'mark_notification_read',
    notificationID: input.notificationID,
    studentNo: input.studentNo
  }
}];
```

---

### 11. Approve Profile Photo Workflow

**Webhook Trigger**:
- Path: `approve-profile-photo`
- Method: POST

**Process Input**:
```javascript
const input = $input.first().json;
return [{
  json: {
    action: 'approve_profile_photo',
    photoID: input.photoID,
    adminID: input.adminID
  }
}];
```

---

### 12. Get Dashboard Stats Workflow

**Webhook Trigger**:
- Path: `get-dashboard-stats`
- Method: POST

**Process Input**:
```javascript
return [{
  json: {
    action: 'get_dashboard_stats'
  }
}];
```

---

## Error Handling

Add Error Trigger node to all workflows:

**Error Handler** (Code Node):
```javascript
const error = $input.first().json.error;
return [{
  json: {
    success: false,
    error: error.message || error.error || 'Unknown error',
    message: 'An error occurred processing your request'
  }
}];
```

Connect to "Respond to Webhook" node.

---

## Testing Workflows

### Test Login
```bash
curl -X POST YOUR_N8N_WEBHOOK_URL/login \
  -H "Content-Type: application/json" \
  -d '{
    "studentNo": "TEST001",
    "password": "test123"
  }'
```

### Test Create Lost Report
```bash
curl -X POST YOUR_N8N_WEBHOOK_URL/create-lost-report \
  -H "Content-Type: application/json" \
  -d '{
    "studentNo": "TEST001",
    "itemName": "iPhone",
    "itemClass": "Electronics",
    "description": "Lost my iPhone",
    "dateOfLoss": "2024-01-15",
    "lostLocation": "Library"
  }'
```

---

## Workflow Import Files

See separate JSON files for each workflow:
- `n8n-workflow-login.json`
- `n8n-workflow-signup.json`
- `n8n-workflow-create-lost-report.json`
- etc.

---

## Next Steps

1. Import workflows into n8n
2. Configure API URLs and keys
3. Test each workflow
4. Update UI to use n8n webhooks
5. Monitor execution logs

