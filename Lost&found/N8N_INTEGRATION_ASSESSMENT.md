# n8n Integration Assessment for UB Lost & Found System

## 📊 Current Tech Stack Analysis

### Backend
- **Language**: PHP 7.4+
- **Database**: MySQL (ub_lost_found)
- **Architecture**: Traditional server-side PHP application
- **Database Access**: PDO with prepared statements
- **Server**: Apache/XAMPP (based on htdocs structure)

### Frontend
- **Framework**: Bootstrap 5.3.0
- **JavaScript**: Vanilla JS (ES6+)
- **Charts**: Chart.js (for admin analytics)
- **UI Pattern**: Server-rendered HTML with form submissions

### Current API Status
- ❌ **No REST API endpoints** currently exist
- ❌ **No webhook support** implemented
- ✅ **Form-based POST/GET** endpoints available
- ✅ **OOP architecture** makes API creation easier

---

## ✅ n8n Compatibility: **YES, with modifications**

Your tech stack **CAN** integrate with n8n, but you'll need to add an API layer.

---

## 🔌 Integration Options

### Option 1: Create REST API Endpoints (Recommended)

**What you need to do:**
1. Create a new `api/` directory in `htdocs/`
2. Build RESTful endpoints that return JSON
3. Use n8n's HTTP Request node to interact with these endpoints

**Example Structure:**
```
htdocs/
├── api/
│   ├── v1/
│   │   ├── reports.php          # GET/POST /api/v1/reports
│   │   ├── items.php             # GET/POST /api/v1/items
│   │   ├── students.php          # GET /api/v1/students
│   │   ├── notifications.php     # GET /api/v1/notifications
│   │   └── webhooks.php          # POST /api/v1/webhooks (for n8n to trigger)
```

**Benefits:**
- ✅ Clean separation of concerns
- ✅ Can be used by mobile apps, n8n, and other services
- ✅ Standard REST conventions
- ✅ Easy to secure with API keys/tokens

**n8n Workflow Example:**
```
Webhook Trigger → HTTP Request (GET /api/v1/reports) → Process Data → Send Email
```

---

### Option 2: Add Webhook Support to Existing Endpoints

**What you need to do:**
1. Modify existing PHP files to accept webhook calls from n8n
2. Add webhook endpoints that n8n can POST to
3. Use n8n's Webhook node to trigger actions

**Example:**
```php
// htdocs/public/webhook_handler.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Process webhook data from n8n
    // Trigger actions in your system
}
```

**Benefits:**
- ✅ Quick to implement
- ✅ n8n can trigger actions in your system
- ✅ Good for event-driven workflows

**n8n Workflow Example:**
```
Cron Trigger → Check Database → HTTP Request (POST to webhook_handler.php) → Update System
```

---

### Option 3: Direct Database Access (Not Recommended)

**What it involves:**
- n8n can connect directly to MySQL database
- Use n8n's MySQL node to read/write data

**⚠️ Security Concerns:**
- ❌ Exposes database directly
- ❌ Bypasses business logic in PHP classes
- ❌ No input validation
- ❌ Harder to maintain

**Only use if:**
- You need read-only access for reporting
- You have proper network security
- You understand the risks

---

## 🎯 Recommended Integration Scenarios

### Scenario 1: Automated Notifications
**Use Case**: Send email/SMS when a lost item matches a found item

**n8n Workflow:**
```
Webhook (from your system) → Check Matches → Send Email/SMS → Update Database
```

**What you need:**
- Webhook endpoint that n8n can call
- Or: n8n polls your API for new matches

### Scenario 2: Data Synchronization
**Use Case**: Sync lost/found items with external systems

**n8n Workflow:**
```
Cron (every hour) → HTTP Request (GET /api/v1/reports) → Transform Data → Send to External System
```

**What you need:**
- REST API endpoint to fetch reports/items

### Scenario 3: Automated Reporting
**Use Case**: Generate daily/weekly reports

**n8n Workflow:**
```
Cron (daily) → HTTP Request (GET /api/v1/analytics) → Generate Report → Email to Admin
```

**What you need:**
- Analytics API endpoint (you already have this planned per CHANGELOG.md)

### Scenario 4: External Integrations
**Use Case**: Post to social media when items are found

**n8n Workflow:**
```
Webhook (new found item) → Format Message → Post to Facebook/Twitter
```

**What you need:**
- Webhook endpoint that triggers when items are created

---

## 🛠️ Implementation Steps

### Step 1: Create API Base Structure

Create `htdocs/api/v1/base.php`:
```php
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../classes/ReportItem.php';
// ... other includes

// API Authentication (add API key validation)
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!validateApiKey($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
```

### Step 2: Create REST Endpoints

Example: `htdocs/api/v1/reports.php`:
```php
<?php
require_once __DIR__ . '/base.php';

$method = $_SERVER['REQUEST_METHOD'];
$reportItem = new ReportItem();

switch ($method) {
    case 'GET':
        $reports = $reportItem->getAllApproved();
        echo json_encode(['success' => true, 'data' => $reports]);
        break;
    
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $reportItem->create(...);
        echo json_encode($result);
        break;
}
```

### Step 3: Add Webhook Endpoints

Example: `htdocs/api/v1/webhooks.php`:
```php
<?php
require_once __DIR__ . '/base.php';

$data = json_decode(file_get_contents('php://input'), true);
$event = $data['event'] ?? '';

switch ($event) {
    case 'item_found':
        // Process found item webhook from n8n
        break;
    case 'match_detected':
        // Process match notification
        break;
}
```

### Step 4: Configure n8n

1. **Install n8n** (self-hosted or cloud)
2. **Create workflow** with HTTP Request nodes
3. **Point to your API endpoints**
4. **Add authentication** (API keys)

---

## 🔒 Security Considerations

### API Authentication
- ✅ Use API keys or JWT tokens
- ✅ Validate on every request
- ✅ Rate limiting
- ✅ HTTPS only

### Webhook Security
- ✅ Verify webhook signatures
- ✅ Use secret tokens
- ✅ Validate source IPs (if possible)

### Database Security
- ✅ Never expose database directly
- ✅ Use prepared statements (you already do this ✅)
- ✅ Validate all inputs

---

## 📋 Quick Start Checklist

- [ ] Create `htdocs/api/` directory structure
- [ ] Create base API file with authentication
- [ ] Create REST endpoints for:
  - [ ] Reports (GET, POST)
  - [ ] Items (GET, POST)
  - [ ] Students (GET)
  - [ ] Notifications (GET, POST)
  - [ ] Analytics (GET)
- [ ] Create webhook endpoints
- [ ] Add API key management
- [ ] Test endpoints with Postman/curl
- [ ] Install and configure n8n
- [ ] Create n8n workflows
- [ ] Test integration end-to-end

---

## 🎨 n8n UI Integration

**Can you use your current UI with n8n?**

**Direct Embedding**: ❌ No
- n8n doesn't provide a way to embed your PHP UI directly
- n8n has its own workflow editor UI

**Indirect Integration**: ✅ Yes
- Your UI can trigger n8n workflows via webhooks
- n8n workflows can update your UI via API calls
- You can build a custom admin panel that shows n8n workflow status

**Best Approach:**
1. Keep your existing PHP UI for users
2. Add API endpoints for n8n to interact with
3. Create admin dashboard section showing n8n workflow status
4. Use n8n for backend automation, not UI replacement

---

## 💡 Example Use Cases

### 1. Automated Match Notifications
```
New Found Item → Webhook to n8n → Search for Matches → Send Email/SMS → Create Notification
```

### 2. Daily Summary Reports
```
Cron (9 AM daily) → Fetch Reports → Generate PDF → Email to Admin
```

### 3. Social Media Integration
```
Item Approved → Webhook → Format Post → Post to Facebook/Twitter
```

### 4. SMS Notifications
```
Match Found → Webhook → Format SMS → Send via Twilio → Update Status
```

---

## 🚀 Next Steps

1. **Decide on integration approach** (REST API recommended)
2. **Create API endpoints** (start with reports and items)
3. **Add authentication** (API keys)
4. **Install n8n** (self-hosted or cloud)
5. **Create test workflow** (simple GET request to your API)
6. **Expand gradually** (add more endpoints as needed)

---

## 📚 Resources

- **n8n Documentation**: https://docs.n8n.io
- **n8n HTTP Request Node**: https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.httprequest/
- **n8n Webhook Node**: https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.webhook/
- **REST API Best Practices**: https://restfulapi.net/

---

## ✅ Conclusion

**Your tech stack IS compatible with n8n**, but you need to:

1. ✅ Add REST API layer (recommended)
2. ✅ Implement webhook support
3. ✅ Add API authentication
4. ✅ Test integration

**Estimated Effort:**
- API Creation: 2-3 days
- n8n Setup: 1 day
- Integration Testing: 1 day
- **Total: ~1 week**

Your OOP architecture makes this easier since you can reuse your existing classes (Student, ReportItem, Item, etc.) in the API endpoints!

