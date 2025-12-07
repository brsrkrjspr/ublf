# Running the Application Locally

## Quick Start

### Option 1: Use Batch File (Easiest)
1. Double-click `start-server.bat` or `RUN-SERVER.bat`
2. Server will start on **http://localhost:8000**
3. Keep the window open while using the server
4. Press `Ctrl+C` to stop

### Option 2: Manual Command
```bash
cd Lost&found/htdocs/public
php -S localhost:8000
```

### Option 3: PowerShell
```powershell
cd Lost&found
.\start-server.ps1
```

## Access the Application

Once server is running:

1. **Open Browser**: Go to **http://localhost:8000**

2. **Login Page**: You'll see the login/signup page

3. **Test Accounts** (if database is set up):
   - Student: `TEST001` / `test123`
   - Admin: `admin` / `admin123`

4. **Without Database**: The app will work with mock data for testing UI

## What You'll See

### Main Pages
- **Login/Signup** (`index.php`) - Entry point
- **Dashboard** (`dashboard.php`) - Student dashboard with chatbot
- **My Reports** (`my_reports.php`) - Student's lost item reports
- **All Lost Items** (`all_lost.php`) - Browse all approved lost items
- **Found Items** (`found_items.php`) - Browse all approved found items
- **Profile** (`profile.php`) - Student profile management
- **Notifications** (`notifications.php`) - Student notifications
- **Contact Admin** (`contact_admin.php`) - Contact form

### Admin Pages
- **Admin Login** (`admin_login.php`) - Admin entry
- **Admin Dashboard** (`admin_dashboard.php`) - Admin management panel

## Features to Test

### 1. Chatbot
- Click the floating chatbot icon (bottom right)
- Send messages like "I lost my phone" or "Show my reports"
- Note: Requires n8n webhook configured (will show error if not set)

### 2. Report Lost Item
- Click "Report Lost Item" button
- Fill out the form
- Upload photo (optional)
- Submit

### 3. Browse Items
- Click "Browse Lost Items" or "Browse Found Items"
- Use filters (keyword, date, class)
- View item details

### 4. Profile Management
- Click "My Profile"
- Update information
- Upload profile photo

## Database Setup (Optional)

If you want full functionality:

1. **Install MySQL** (or use XAMPP)
2. **Create Database**: `ub_lost_found`
3. **Import SQL Files**: From `db/` directory
4. **Update Database.php**: If credentials differ

**Without Database**: App will use mock data for UI testing

## Troubleshooting

### Server Won't Start
- **PHP Not Found**: Install XAMPP or add PHP to PATH
- **Port 8000 Busy**: Change port in batch file to 8001 or 8080
- **Permission Error**: Run as Administrator

### Pages Show Errors
- **Database Error**: Normal if database not set up - app has fallback
- **404 Errors**: Make sure you're in `htdocs/public` directory
- **CSS Not Loading**: Check `css.php` file exists

### Chatbot Not Working
- **Expected**: Will show error if n8n not configured
- **To Fix**: Set `N8N_WEBHOOK_URL` in `.env` file

## Server Commands

### Start Server
```bash
start-server.bat
```

### Stop Server
- Press `Ctrl+C` in the server window
- Or close the terminal window

### Change Port
Edit `start-server.bat`:
```batch
php -S localhost:8001  # Change 8000 to 8001
```

## Next Steps

1. ✅ Server should be running on http://localhost:8000
2. ✅ Open browser and navigate to the URL
3. ✅ Test the UI and features
4. ✅ Set up database if you want full functionality
5. ✅ Configure n8n if you want chatbot to work

---

**Server should now be running!** Open http://localhost:8000 in your browser.

