# x10hosting Deployment Guide

## Database Setup on x10hosting

### Step 1: Access phpMyAdmin

1. Log in to your **x10hosting control panel**
2. Navigate to **phpMyAdmin** (usually in the "Databases" section)
3. Click on **phpMyAdmin** to open it

### Step 2: Create Database

1. In phpMyAdmin, click on **"Databases"** tab
2. Enter database name: `ub_lost_found` (or your preferred name)
3. Select **Collation**: `utf8mb4_unicode_ci` (or `utf8mb4_general_ci`)
4. Click **"Create"**

### Step 3: Import SQL File

1. Select your newly created database from the left sidebar
2. Click on the **"Import"** tab at the top
3. Click **"Choose File"** button
4. Select the file: `db/ub_lost_found_COMPLETE.sql`
5. Under **"Format"**, make sure **"SQL"** is selected
6. Click **"Go"** or **"Import"** button
7. Wait for the import to complete (should show success message)

### Step 4: Verify Import

1. Check that all **11 tables** are created:
   - `admin`
   - `student`
   - `itemclass`
   - `itemstatus`
   - `reportstatus`
   - `status`
   - `item`
   - `reportitem`
   - `notifications`
   - `profile_photo_history`
   - `reportitem_match`

2. Verify data was imported:
   - Check `admin` table has at least 1 admin user
   - Check `student` table has test data (optional - you can delete this)
   - Check `itemclass` has categories (Bags, Electronics)

## File Upload to x10hosting

### Step 1: Upload Files via FTP/File Manager

1. **Access File Manager** in x10hosting control panel
2. Navigate to **`public_html`** (or `htdocs` depending on your hosting)
3. Upload all files from `Lost&found/htdocs/` to your web root

**Recommended Structure:**
```
public_html/
├── assets/
│   ├── uploads/ (make sure this folder is writable - chmod 755)
│   ├── admin_dashboard.css
│   ├── admin.css
│   ├── dash.css
│   ├── forms.js
│   ├── notifications.css
│   ├── notifications.js
│   ├── profile.css
│   ├── ub_bg.jpg
│   ├── ub_logo.png
│   └── UB.css
├── classes/
│   ├── Admin.php
│   ├── FileUpload.php
│   ├── Item.php
│   ├── Notification.php
│   ├── ReportItem.php
│   └── Student.php
├── includes/
│   ├── Config.php
│   └── Database.php
├── public/
│   ├── (all PHP files)
│   └── php/
│       └── chat_handler.php
├── api/
│   ├── .htaccess
│   └── v1/
│       ├── .htaccess
│       ├── base.php
│       ├── items.php
│       ├── reports.php
│       └── webhooks.php
└── templates/
    └── header.php
```

### Step 2: Set Permissions

**Important:** Set correct file permissions:

1. **Folders**: `chmod 755` (or `0755`)
   - `assets/uploads/` - **MUST be writable** (755 or 777)
   - `classes/`
   - `includes/`
   - `public/`
   - `api/`

2. **Files**: `chmod 644` (or `0644`)
   - All `.php` files
   - All `.css`, `.js`, `.jpg`, `.png` files

**Via File Manager:**
- Right-click folder → **Change Permissions** → Set to `755`
- Right-click file → **Change Permissions** → Set to `644`

**Via FTP/SSH:**
```bash
chmod 755 assets/uploads
chmod 644 *.php
```

## Configuration

### Step 1: Update Database Connection

Edit `includes/Database.php`:

```php
private $host = 'localhost'; // Usually 'localhost' on x10hosting
private $db_name = 'your_database_name'; // Your x10hosting database name
private $username = 'your_database_username'; // Your x10hosting database user
private $password = 'your_database_password'; // Your x10hosting database password
```

**OR** better yet, use the `Config.php` class:

### Step 2: Create .env File (Recommended)

Create a file `.env` in the `htdocs/` directory (same level as `includes/`):

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_username
DB_PASS=your_database_password

# API Configuration
API_KEY=your-secret-api-key-change-this-to-something-random

# n8n Configuration (if using)
N8N_WEBHOOK_URL=https://your-n8n-instance.com/webhook/chatbot
N8N_API_KEY=your-n8n-api-key-if-needed

# CORS (for API)
CORS_ALLOW_ORIGIN=*
```

**Note:** On x10hosting, you might need to set these as environment variables in the control panel instead of using `.env` file.

### Step 3: Update Config.php (if needed)

If `.env` files don't work on x10hosting, edit `includes/Config.php` directly:

```php
private function loadDefaults() {
    $this->config = [
        'DB_HOST' => 'localhost',
        'DB_NAME' => 'your_database_name',
        'DB_USER' => 'your_database_username',
        'DB_PASS' => 'your_database_password',
        'API_KEY' => 'your-secret-api-key',
        'N8N_WEBHOOK_URL' => 'https://your-n8n-instance.com/webhook/chatbot',
        'N8N_API_KEY' => '',
        'CORS_ALLOW_ORIGIN' => '*',
    ];
}
```

## Testing

### Step 1: Test Database Connection

1. Visit: `https://yourdomain.com/public/index.php`
2. Try to **sign up** a new account
3. If successful, database connection is working!

### Step 2: Test File Uploads

1. Log in to your account
2. Go to **Profile** → Upload a profile photo
3. Check if file appears in `assets/uploads/` folder
4. If error, check folder permissions (should be 755 or 777)

### Step 3: Test Admin Login

**Default Admin Credentials:**
- Username: `admin`
- Password: `password` (or check the hash in database)

**To reset admin password:**
1. Go to phpMyAdmin
2. Select `admin` table
3. Edit the admin user
4. Generate new password hash using PHP:
   ```php
   echo password_hash('your_new_password', PASSWORD_DEFAULT);
   ```
5. Update `PasswordHash` field

## Common Issues

### Issue 1: "Database connection failed"

**Solution:**
- Verify database credentials in `Database.php` or `.env`
- Check database name, username, password in x10hosting control panel
- Ensure database user has proper permissions

### Issue 2: "Permission denied" for file uploads

**Solution:**
- Set `assets/uploads/` folder to `chmod 755` or `777`
- Check folder ownership (should be your web server user)

### Issue 3: "404 Not Found" errors

**Solution:**
- Check `.htaccess` files are uploaded
- Verify Apache mod_rewrite is enabled (contact x10hosting support)
- Check file paths are correct

### Issue 4: "Internal Server Error"

**Solution:**
- Check PHP error logs in x10hosting control panel
- Verify PHP version (should be 7.4 or higher)
- Check file permissions
- Verify all required PHP extensions are enabled (PDO, MySQL, cURL)

## Security Checklist

- [ ] Change default admin password
- [ ] Set strong `API_KEY` in Config.php
- [ ] Remove test data from database (optional)
- [ ] Set proper file permissions (644 for files, 755 for folders)
- [ ] Enable HTTPS (SSL certificate)
- [ ] Update `CORS_ALLOW_ORIGIN` if using API from specific domains
- [ ] Keep `.env` file secure (not publicly accessible)

## Next Steps

1. ✅ Database imported successfully
2. ✅ Files uploaded to server
3. ✅ Database credentials configured
4. ✅ File permissions set
5. ✅ Test the application
6. ✅ Configure n8n webhooks (if using)
7. ✅ Set up SSL certificate (recommended)

## Support

If you encounter issues:
1. Check x10hosting error logs
2. Verify PHP version compatibility
3. Contact x10hosting support for server-specific issues
4. Review PHP error logs in control panel

---

**Your application should now be live on x10hosting!** 🎉

