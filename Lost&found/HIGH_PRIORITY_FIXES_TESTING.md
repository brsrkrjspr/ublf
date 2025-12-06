# High Priority Fixes - Testing Guide

This guide provides testing procedures for the three high-priority fixes implemented.

## Fix 1: Configuration File Testing

### Test 1.1: Config Class Loading

**Test**: Verify Config class loads correctly

**Steps**:
1. Create a test file `test_config.php`:
```php
<?php
require_once __DIR__ . '/htdocs/includes/Config.php';

echo "Config loaded successfully\n";
echo "N8N_WEBHOOK_URL: " . Config::get('N8N_WEBHOOK_URL') . "\n";
echo "API_KEY: " . (Config::has('API_KEY') ? 'Set' : 'Not set') . "\n";
echo "Environment: " . Config::environment() . "\n";
```

2. Run: `php test_config.php`

**Expected Result**:
- No errors
- Config values displayed
- Fallback values used if .env not present

### Test 1.2: .env File Loading

**Test**: Verify .env file is loaded correctly

**Steps**:
1. Create `.env` file in `htdocs/` directory:
```
N8N_WEBHOOK_URL=https://test-n8n.com/webhook
API_KEY=test-api-key-123
ENVIRONMENT=development
```

2. Run test_config.php again

**Expected Result**:
- Values from .env file are used
- Overrides default values

### Test 1.3: Environment Variable Priority

**Test**: Verify environment variables take priority over .env

**Steps**:
1. Set environment variable: `export API_KEY=env-api-key`
2. Create .env with different value
3. Run test_config.php

**Expected Result**:
- Environment variable value is used (higher priority)

### Test 1.4: chat_handler.php Configuration

**Test**: Verify chat_handler.php uses Config class

**Steps**:
1. Check that chat_handler.php includes Config.php
2. Verify no `getenv()` calls remain (except in Config class)
3. Test that Config::get() is used

**Expected Result**:
- Config.php included
- Uses Config::get() for n8n settings

### Test 1.5: API base.php Configuration

**Test**: Verify API base.php uses Config class

**Steps**:
1. Check that base.php includes Config.php
2. Verify API key comes from Config::get()

**Expected Result**:
- Config.php included
- Uses Config::get('API_KEY')

## Fix 2: StudentNo Field Testing

### Test 2.1: API Response Includes StudentNo

**Test**: Verify getByStudent() returns StudentNo field

**Test Command**:
```bash
curl -X GET "http://localhost/api/v1/reports?studentNo=TEST001" \
  -H "X-API-Key: your-api-key"
```

**Expected Response**:
```json
{
  "success": true,
  "data": [
    {
      "ReportID": 1,
      "StudentNo": "TEST001",
      "ItemName": "iPhone",
      "Description": "...",
      "DateOfLoss": "2024-01-15",
      "LostLocation": "Library",
      "PhotoURL": "...",
      "StatusConfirmed": 1,
      "ClassName": "Electronics"
    }
  ],
  "count": 1
}
```

**Key Check**: Verify `StudentNo` field is present in response

### Test 2.2: Multiple Reports Include StudentNo

**Test**: Verify all reports in response include StudentNo

**Steps**:
1. Ensure test student has multiple reports
2. Query API endpoint
3. Verify all items in array have StudentNo field

**Expected Result**:
- All report objects include StudentNo field
- No missing StudentNo fields

### Test 2.3: Database Query Verification

**Test**: Verify SQL query includes StudentNo

**Steps**:
1. Check ReportItem.php line 76
2. Verify SELECT statement includes `ri.StudentNo`

**Expected Result**:
- Query includes: `SELECT ri.ReportID, ri.StudentNo, ri.ItemName, ...`

## Fix 3: API Routing Testing

### Test 3.1: .htaccess File Exists

**Test**: Verify .htaccess files are created

**Check Files**:
- `Lost&found/htdocs/api/.htaccess` - exists
- `Lost&found/htdocs/api/v1/.htaccess` - exists

**Expected Result**:
- Both files exist
- Files contain rewrite rules

### Test 3.2: API Endpoint Accessibility

**Test**: Verify API endpoints are accessible

**Test Commands**:
```bash
# Test reports endpoint
curl -X GET "http://localhost/api/v1/reports" \
  -H "X-API-Key: your-api-key" \
  -v

# Test items endpoint
curl -X GET "http://localhost/api/v1/items" \
  -H "X-API-Key: your-api-key" \
  -v
```

**Expected Result**:
- HTTP 200 OK
- JSON response returned
- No 404 errors

### Test 3.3: CORS Preflight Request

**Test**: Verify CORS OPTIONS request works

**Test Command**:
```bash
curl -X OPTIONS "http://localhost/api/v1/reports" \
  -H "Origin: https://example.com" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: X-API-Key" \
  -v
```

**Expected Headers**:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, X-API-Key`
- HTTP 200 status

### Test 3.4: Security Headers

**Test**: Verify security headers are set

**Test Command**:
```bash
curl -X GET "http://localhost/api/v1/reports" \
  -H "X-API-Key: your-api-key" \
  -I
```

**Expected Headers**:
- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`

### Test 3.5: Directory Listing Prevention

**Test**: Verify directory listing is disabled

**Test Command**:
```bash
curl -X GET "http://localhost/api/v1/" \
  -v
```

**Expected Result**:
- HTTP 403 Forbidden or 404 Not Found
- No directory listing displayed

### Test 3.6: Sensitive File Protection

**Test**: Verify .env and other sensitive files are protected

**Test Command**:
```bash
curl -X GET "http://localhost/api/.env" \
  -v
```

**Expected Result**:
- HTTP 403 Forbidden
- File content not accessible

## Integration Testing

### Test 4.1: Complete Flow with Config

**Test**: Test chatbot with Config class

**Steps**:
1. Set up .env file with n8n webhook URL
2. Send message via chatbot UI
3. Verify request reaches n8n

**Expected Result**:
- Chatbot works correctly
- Uses values from Config class
- No hardcoded values needed

### Test 4.2: API with StudentNo for n8n

**Test**: Test n8n can query student reports with StudentNo

**Steps**:
1. n8n workflow queries: `/api/v1/reports?studentNo=TEST001`
2. Verify response includes StudentNo
3. Verify n8n can identify report ownership

**Expected Result**:
- API returns StudentNo field
- n8n can process student-specific data
- Reports correctly associated with students

### Test 4.3: API Routing with n8n

**Test**: Verify n8n can access API endpoints

**Steps**:
1. Configure n8n HTTP Request node with API URL
2. Test API call from n8n
3. Verify response received

**Expected Result**:
- n8n can successfully call API
- CORS headers allow n8n requests
- Security headers present

## Quick Test Script

Create `test_all_fixes.php`:

```php
<?php
require_once __DIR__ . '/htdocs/includes/Config.php';

echo "=== Testing Configuration ===\n";
echo "N8N_WEBHOOK_URL: " . Config::get('N8N_WEBHOOK_URL') . "\n";
echo "API_KEY: " . (Config::has('API_KEY') ? 'Set' : 'Not set') . "\n";
echo "Environment: " . Config::environment() . "\n\n";

echo "=== Testing ReportItem Class ===\n";
require_once __DIR__ . '/htdocs/classes/ReportItem.php';
$reportItem = new ReportItem();
if ($reportItem->conn) {
    $reports = $reportItem->getByStudent('TEST001');
    if (!empty($reports)) {
        $firstReport = $reports[0];
        echo "StudentNo in response: " . (isset($firstReport['StudentNo']) ? 'YES' : 'NO') . "\n";
        if (isset($firstReport['StudentNo'])) {
            echo "StudentNo value: " . $firstReport['StudentNo'] . "\n";
        }
    } else {
        echo "No reports found for test student\n";
    }
} else {
    echo "Database not connected - skipping test\n";
}

echo "\n=== All Tests Complete ===\n";
```

## Success Criteria

- [ ] Config class loads without errors
- [ ] .env file is loaded correctly
- [ ] Environment variables take priority
- [ ] chat_handler.php uses Config class
- [ ] API base.php uses Config class
- [ ] getByStudent() returns StudentNo field
- [ ] API responses include StudentNo
- [ ] .htaccess files exist and work
- [ ] API endpoints accessible
- [ ] CORS headers present
- [ ] Security headers present
- [ ] Directory listing disabled
- [ ] Sensitive files protected

## Troubleshooting

### Config Not Loading
- Check file path: `htdocs/includes/Config.php`
- Verify PHP can read .env file
- Check file permissions

### StudentNo Still Missing
- Verify ReportItem.php was updated
- Clear any PHP opcode cache
- Check database connection

### API Not Accessible
- Verify mod_rewrite is enabled in Apache
- Check .htaccess file syntax
- Verify file permissions (644)
- Check Apache error logs

### CORS Issues
- Verify CORS headers in .htaccess
- Check base.php also sets CORS headers
- Test with actual browser request

