# Debug Logging Setup Instructions

## Overview

A custom logging system has been added to help you debug the photo upload issue. All debug messages will be written to a file that you can view in your web browser.

## Files Created

1. **`htdocs/includes/Logger.php`** - Custom logging class
2. **`htdocs/public/view_logs.php`** - Web-based log viewer
3. **`htdocs/debug.log`** - Log file (created automatically)

## Step-by-Step Setup

### Step 1: Upload Files to Server

Upload these files to your server:

1. **`htdocs/includes/Logger.php`**
   - Upload to: `htdocs/includes/Logger.php` (or `public_html/includes/Logger.php`)

2. **`htdocs/public/view_logs.php`**
   - Upload to: `htdocs/public/view_logs.php` (or `public_html/public/view_logs.php`)

3. **Updated Files** (already modified):
   - `htdocs/api/v1/webhooks.php` - Now uses Logger
   - `htdocs/public/dashboard.php` - Now uses Logger

### Step 2: Set File Permissions

Set permissions for the log file directory:

**Via cPanel File Manager:**
1. Navigate to `htdocs/` (or `public_html/`)
2. Right-click on the directory
3. Select "Change Permissions"
4. Set to `755` (or `777` if 755 doesn't work)

**Via FTP/SSH:**
```bash
chmod 755 htdocs/
chmod 666 htdocs/debug.log  # After first log entry is created
```

### Step 3: Access the Log Viewer

After uploading, access the log viewer at:

```
https://ublf.x10.mx/public/view_logs.php
```

Or if your public directory is different:

```
https://ublf.x10.mx/view_logs.php
```

### Step 4: Test the Logging

1. **Submit a report with a photo** through your dashboard
2. **Open the log viewer** in a new tab
3. **Check the logs** - You should see entries like:
   ```
   [2025-01-15 10:30:45] === SENDING TO N8N ===
   [2025-01-15 10:30:45] Photo base64 present: YES (length: 123456)
   [2025-01-15 10:30:45] === CREATE LOST REPORT DEBUG ===
   [2025-01-15 10:30:45] Photo data received: YES (length: 123456)
   ```

## What to Look For

When debugging the photo upload issue, check these log entries:

### 1. Photo Upload (Dashboard)
- ✅ **"Photo base64 present: YES"** - Photo was converted to base64
- ❌ **"Photo base64 present: NO"** - Photo wasn't uploaded or converted

### 2. n8n Webhook
- ✅ **"n8n webhook response - HTTP Code: 200"** - n8n received the data
- ❌ **"n8n webhook returned HTTP 400/500"** - n8n had an error

### 3. Photo Data Reception (Webhook Handler)
- ✅ **"Photo data received: YES (length: 123456)"** - Photo reached the API
- ❌ **"Photo data received: NO/NULL"** - Photo didn't reach the API (issue in n8n)

### 4. Base64 Upload
- ✅ **"Photo uploaded successfully: assets/uploads/lost_xxx.jpg"** - Photo was saved
- ❌ **"Photo upload FAILED: [error message]"** - Photo upload failed

### 5. Database Save
- ✅ **"Final photoURL before database: assets/uploads/lost_xxx.jpg"** - Photo URL is set
- ❌ **"Final photoURL before database: NULL"** - Photo URL is missing

## Log Viewer Features

The log viewer (`view_logs.php`) has these features:

- **View last N lines**: Choose 50, 100, 200, or 500 lines
- **Auto-refresh**: Page refreshes every 30 seconds
- **Clear logs**: Button to clear all logs (use with caution)
- **File info**: Shows log file location and size

## Troubleshooting

### Log file not created?

1. **Check permissions**: Ensure `htdocs/` directory is writable (755 or 777)
2. **Check path**: Verify the log file path in `Logger.php`
3. **Check PHP errors**: Look for PHP errors in your hosting control panel

### Can't access view_logs.php?

1. **Check file location**: Ensure file is in `htdocs/public/` or `public_html/public/`
2. **Check URL**: Try different URL patterns based on your hosting setup
3. **Check permissions**: File should be readable (644)

### No logs appearing?

1. **Submit a report**: Logs only appear when actions are performed
2. **Check file permissions**: Log file directory must be writable
3. **Check PHP errors**: There might be an error preventing logging

## Security Note

⚠️ **Important**: The log viewer is currently accessible to anyone. For production:

1. **Add password protection** to `view_logs.php`:
   ```php
   // Uncomment this in view_logs.php:
   if (!isset($_SESSION['admin']) && !isset($_SESSION['student'])) {
       die('Access denied. Please log in first.');
   }
   ```

2. **Or use .htaccess** to protect the file:
   ```apache
   <Files "view_logs.php">
       AuthType Basic
       AuthName "Restricted Access"
       AuthUserFile /path/to/.htpasswd
       Require valid-user
   </Files>
   ```

3. **Or delete** `view_logs.php` after debugging and access logs via FTP/File Manager

## Next Steps

1. ✅ Upload the files
2. ✅ Set permissions
3. ✅ Test by submitting a report with a photo
4. ✅ Check the logs to see where the photo data is lost
5. ✅ Share the log output if you need help interpreting it

## Example Log Output

Here's what a successful flow looks like:

```
[2025-01-15 10:30:45] === SENDING TO N8N ===
[2025-01-15 10:30:45] Photo base64 present: YES (length: 245678)
[2025-01-15 10:30:45] Photo URL from upload: assets/uploads/lost_abc123_1234567890.jpg
[2025-01-15 10:30:45] Payload size: 245890 bytes
[2025-01-15 10:30:46] n8n webhook response - HTTP Code: 200
[2025-01-15 10:30:46] n8n response parsed: YES
[2025-01-15 10:30:46] n8n response success: true
[2025-01-15 10:30:46] === CREATE LOST REPORT DEBUG ===
[2025-01-15 10:30:46] Photo data received: YES (length: 245678)
[2025-01-15 10:30:46] Photo data preview: /9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQ...
[2025-01-15 10:30:46] All data keys: action, studentNo, itemName, itemClass, description, dateOfLoss, lostLocation, photo
[2025-01-15 10:30:46] Attempting to upload base64 image...
[2025-01-15 10:30:46] Photo uploaded successfully: assets/uploads/lost_xyz789_1234567891.jpg
[2025-01-15 10:30:46] Final photoURL before database: assets/uploads/lost_xyz789_1234567891.jpg
[2025-01-15 10:30:46] Report creation result: SUCCESS
[2025-01-15 10:30:46] Report ID: 42
[2025-01-15 10:30:46] === END DEBUG ===
```

## Support

If you see errors in the logs or need help interpreting them, share:
1. The relevant log entries
2. What action you were performing
3. What you expected vs. what happened

