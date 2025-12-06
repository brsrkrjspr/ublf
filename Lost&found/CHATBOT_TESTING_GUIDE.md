# Chatbot Testing Guide

This guide provides testing procedures for the chatbot integration with n8n.

## Prerequisites

- PHP server running
- Database connected and populated with test data
- n8n workflow created and active
- OpenAI API key configured (if using AI)
- API endpoints accessible

## Test Scenarios

### 1. Basic Connection Test

**Objective**: Verify PHP endpoint can connect to n8n

**Steps**:
1. Open browser developer console
2. Navigate to dashboard
3. Open chatbot modal
4. Send message: "Hello"

**Expected Result**:
- Message appears in chat window
- "Typing..." indicator shows
- Response from n8n appears
- No errors in console

**Test Command** (cURL):
```bash
curl -X POST http://localhost/php/chat_handler.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{"message": "Hello"}'
```

### 2. Session Validation Test

**Objective**: Verify unauthorized users are blocked

**Steps**:
1. Log out of the system
2. Try to access chatbot endpoint directly

**Test Command**:
```bash
curl -X POST http://localhost/php/chat_handler.php \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello"}'
```

**Expected Result**:
- HTTP 401 Unauthorized
- JSON response: `{"error": "Unauthorized - Please log in..."}`

### 3. Intent Detection Tests

#### Test 3.1: Lost Item Intent

**Message**: "I lost my phone"

**Expected**:
- Intent: `lost_item`
- Triggers database query for lost items
- AI response includes search results if found

#### Test 3.2: My Reports Intent

**Message**: "Show my reports" or "What items did I report?"

**Expected**:
- Intent: `my_reports`
- Queries database for student's reports
- Returns list of user's lost item reports

#### Test 3.3: Search Intent

**Message**: "Search for iPhone" or "Find lost wallet"

**Expected**:
- Intent: `search`
- Searches both lost and found items
- Returns relevant matches

#### Test 3.4: Help Intent

**Message**: "Help me" or "How do I report a lost item?"

**Expected**:
- Intent: `help`
- Returns general help information
- No database query needed

### 4. Database Integration Tests

#### Test 4.1: API Endpoint - Get Reports

**Test Command**:
```bash
curl -X GET "http://localhost/api/v1/reports?search=phone" \
  -H "X-API-Key: your-api-key"
```

**Expected Result**:
```json
{
  "success": true,
  "data": [...],
  "count": 2
}
```

#### Test 4.2: API Endpoint - Get Student Reports

**Test Command**:
```bash
curl -X GET "http://localhost/api/v1/reports?studentNo=TEST001" \
  -H "X-API-Key: your-api-key"
```

**Expected Result**:
- Returns only reports for specified student
- Includes all report details

#### Test 4.3: API Endpoint - Get Found Items

**Test Command**:
```bash
curl -X GET "http://localhost/api/v1/items?search=phone" \
  -H "X-API-Key: your-api-key"
```

**Expected Result**:
- Returns found items matching search
- Includes item details

### 5. API Authentication Tests

#### Test 5.1: Missing API Key

**Test Command**:
```bash
curl -X GET "http://localhost/api/v1/reports"
```

**Expected Result**:
- HTTP 401 Unauthorized
- Error message about missing API key

#### Test 5.2: Invalid API Key

**Test Command**:
```bash
curl -X GET "http://localhost/api/v1/reports" \
  -H "X-API-Key: wrong-key"
```

**Expected Result**:
- HTTP 401 Unauthorized
- Error message about invalid API key

### 6. Error Handling Tests

#### Test 6.1: n8n Unavailable

**Scenario**: n8n webhook is down or unreachable

**Steps**:
1. Stop n8n workflow or use invalid webhook URL
2. Send message via chatbot

**Expected Result**:
- Retry logic attempts 2 times
- Fallback response provided
- User-friendly error message
- No crash or exception

#### Test 6.2: Network Timeout

**Scenario**: n8n takes too long to respond

**Steps**:
1. Configure n8n to delay response (>30 seconds)
2. Send message via chatbot

**Expected Result**:
- Request times out after 30 seconds
- Fallback response provided
- Error logged

#### Test 6.3: Invalid JSON Response

**Scenario**: n8n returns invalid JSON

**Steps**:
1. Configure n8n to return non-JSON response
2. Send message via chatbot

**Expected Result**:
- Error detected
- Fallback response provided
- Error logged

#### Test 6.4: Database Connection Failure

**Scenario**: Database is unavailable

**Steps**:
1. Stop database server
2. Try to access API endpoints

**Expected Result**:
- HTTP 503 Service Unavailable
- Error message: "Database connection unavailable"

### 7. End-to-End Flow Tests

#### Test 7.1: Complete Lost Item Query

**Steps**:
1. User sends: "I lost my iPhone yesterday"
2. System processes intent: `lost_item`
3. Searches database for "iPhone"
4. AI generates response with results
5. Response displayed in chat

**Expected Flow**:
```
UI → chat_handler.php → n8n webhook → 
Process Message → Route Intent → Get Lost Items → 
OpenAI → Format Response → Respond to Webhook → 
chat_handler.php → UI
```

#### Test 7.2: Complete My Reports Query

**Steps**:
1. User sends: "What items have I reported?"
2. System processes intent: `my_reports`
3. Queries database for student's reports
4. AI formats response
5. Response displayed

### 8. Performance Tests

#### Test 8.1: Response Time

**Objective**: Measure response time

**Steps**:
1. Send message via chatbot
2. Measure time from send to response

**Expected**:
- Response time < 5 seconds (including AI processing)
- Database queries < 1 second

#### Test 8.2: Concurrent Requests

**Objective**: Test multiple simultaneous requests

**Steps**:
1. Open multiple browser tabs
2. Send messages simultaneously from each

**Expected**:
- All requests processed
- No errors or timeouts
- Responses returned correctly

### 9. UI/UX Tests

#### Test 9.1: Message Display

**Steps**:
1. Send multiple messages
2. Verify messages appear correctly
3. Check scrolling behavior

**Expected**:
- Messages appear in correct order
- User messages on right (blue)
- AI messages on left (gray)
- Auto-scroll to latest message

#### Test 9.2: Typing Indicator

**Steps**:
1. Send message
2. Observe typing indicator

**Expected**:
- "Typing..." appears immediately
- Removed when response arrives
- Smooth transition

#### Test 9.3: Error Messages

**Steps**:
1. Trigger error scenario
2. Check error message display

**Expected**:
- User-friendly error message
- No technical details exposed
- Suggestion to try again

### 10. Security Tests

#### Test 10.1: XSS Prevention

**Steps**:
1. Send message with HTML/JavaScript: `<script>alert('xss')</script>`
2. Check response handling

**Expected**:
- Script tags escaped
- No script execution
- Safe display

#### Test 10.2: SQL Injection Prevention

**Steps**:
1. Send message with SQL: "'; DROP TABLE--"
2. Check database queries

**Expected**:
- Prepared statements used
- No SQL injection possible
- Safe query execution

#### Test 10.3: Session Hijacking Prevention

**Steps**:
1. Try to use another user's session
2. Verify session validation

**Expected**:
- Session validated correctly
- Unauthorized access blocked

## Test Checklist

- [ ] Basic connection works
- [ ] Session validation works
- [ ] All intents detected correctly
- [ ] Database queries return correct data
- [ ] API authentication works
- [ ] Error handling works for all scenarios
- [ ] End-to-end flow works
- [ ] Performance is acceptable
- [ ] UI displays correctly
- [ ] Security measures work

## Debugging Tips

### Check PHP Logs
```bash
tail -f /var/log/php_errors.log
# or
tail -f /path/to/your/error.log
```

### Check n8n Execution Logs
1. Open n8n workflow
2. Click on "Executions" tab
3. Review failed executions
4. Check error messages

### Test API Endpoints Directly
```bash
# Test reports endpoint
curl -X GET "http://localhost/api/v1/reports?search=test" \
  -H "X-API-Key: your-key" \
  -v

# Test items endpoint
curl -X GET "http://localhost/api/v1/items?search=test" \
  -H "X-API-Key: your-key" \
  -v
```

### Check Network Requests
1. Open browser DevTools (F12)
2. Go to Network tab
3. Send message via chatbot
4. Check request/response details

## Common Issues and Solutions

### Issue: "Error contacting server"
**Solution**: 
- Check n8n webhook URL in `chat_handler.php`
- Verify n8n workflow is active
- Check network connectivity

### Issue: "Unauthorized" error
**Solution**:
- Verify user is logged in
- Check session is valid
- Clear cookies and re-login

### Issue: No response from AI
**Solution**:
- Check OpenAI API key in n8n
- Verify OpenAI node configuration
- Check API quota/limits

### Issue: Database queries fail
**Solution**:
- Verify database connection
- Check API key authentication
- Review API endpoint logs

## Test Data Setup

Create test data for comprehensive testing:

```sql
-- Test student
INSERT INTO student (StudentNo, StudentName, Email, PhoneNo, PasswordHash) 
VALUES ('TEST001', 'Test User', 'TEST001@ub.edu.ph', '09123456789', '$2y$10$...');

-- Test lost items
INSERT INTO reportitem (StudentNo, ItemName, ItemClassID, Description, DateOfLoss, LostLocation, StatusConfirmed)
VALUES ('TEST001', 'iPhone 13', 1, 'Black iPhone with case', '2024-01-15', 'Library', 1);

-- Test found items
INSERT INTO item (AdminID, ItemName, ItemClassID, Description, DateFound, LocationFound, StatusConfirmed)
VALUES (1, 'iPhone 13', 1, 'Black iPhone found', '2024-01-16', 'Library', 1);
```

## Success Criteria

All tests should pass with:
- ✅ No errors in logs
- ✅ Responses within 5 seconds
- ✅ Correct intent detection
- ✅ Accurate database queries
- ✅ User-friendly error messages
- ✅ Secure handling of all inputs

