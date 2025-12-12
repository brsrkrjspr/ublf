# 📍 Database Location & Configuration Guide

## Database Configuration File

**Location**: `Lost&found/htdocs/includes/Database.php`

### Current Database Settings

```php
private $host = 'localhost';
private $db_name = 'gcrajoqq_ublf';
private $username = 'gcrajoqq_ublf';
private $password = 'ublf12345';
```

**⚠️ Note**: These appear to be production/hosting credentials. For local development or Render deployment, you'll need to update these.

---

## Database Schema Files (SQL)

**Location**: `Lost&found/db/`

### Complete Database File (Recommended)
- **`ub_lost_found_COMPLETE.sql`** - Complete database with all tables and sample data

### Individual Table Files
- `ub_lost_found_admin.sql` - Admin users table
- `ub_lost_found_student.sql` - Student accounts table
- `ub_lost_found_item.sql` - Found items table
- `ub_lost_found_reportitem.sql` - Lost item reports table
- `ub_lost_found_itemclass.sql` - Item categories/classes
- `ub_lost_found_itemstatus.sql` - Item status types
- `ub_lost_found_reportstatus.sql` - Report status types
- `ub_lost_found_status.sql` - General status table
- `ub_lost_found_notifications.sql` - Notifications table
- `ub_lost_found_profile_photo_history.sql` - Profile photo history
- `ub_lost_found_reportitem_match.sql` - Matches between lost and found items

---

## Database Structure

The database contains **11 main tables**:

1. **`admin`** - Administrator accounts
2. **`student`** - Student user accounts
3. **`item`** - Found items
4. **`reportitem`** - Lost item reports
5. **`itemclass`** - Item categories (Bags, Electronics, etc.)
6. **`itemstatus`** - Item status types
7. **`reportstatus`** - Report status types
8. **`status`** - General status lookup
9. **`notifications`** - User notifications
10. **`profile_photo_history`** - Profile photo change history
11. **`reportitem_match`** - Matches between lost and found items

---

## How to Update Database Credentials

### Option 1: Edit Database.php Directly

Edit `Lost&found/htdocs/includes/Database.php`:

```php
private $host = 'your-database-host';        // e.g., 'localhost' or 'db.example.com'
private $db_name = 'your-database-name';      // e.g., 'ub_lost_found'
private $username = 'your-database-user';     // e.g., 'root' or 'myuser'
private $password = 'your-database-password'; // Your database password
```

### Option 2: Use Environment Variables (Recommended for Production)

The `Config.php` class supports `.env` files, but `Database.php` doesn't use it yet. You can modify `Database.php` to read from environment variables:

```php
private $host = getenv('DB_HOST') ?: 'localhost';
private $db_name = getenv('DB_NAME') ?: 'ub_lost_found';
private $username = getenv('DB_USER') ?: 'root';
private $password = getenv('DB_PASS') ?: '';
```

Then set environment variables in Render Dashboard or create a `.env` file.

---

## Setting Up Database for Render Deployment

### Step 1: Create Database

Render doesn't provide MySQL by default. You have options:

1. **External MySQL Service** (Recommended):
   - Use **PlanetScale** (free tier available)
   - Use **AWS RDS**
   - Use **DigitalOcean Managed Database**
   - Use **Railway** MySQL

2. **Render PostgreSQL** (requires code changes):
   - Would need to modify Database.php to use PostgreSQL

### Step 2: Import Database Schema

1. Create your database on your chosen MySQL provider
2. Import `Lost&found/db/ub_lost_found_COMPLETE.sql` using:
   - phpMyAdmin (if available)
   - MySQL command line
   - Database management tool

### Step 3: Update Database Credentials

**For Render Deployment:**

1. **Get your database connection details** from your MySQL provider:
   - Host (e.g., `us-east.connect.psdb.cloud`)
   - Database name
   - Username
   - Password
   - Port (usually 3306)

2. **Update `Database.php`** with your credentials

3. **OR** set as Environment Variables in Render Dashboard:
   - `DB_HOST` = your database host
   - `DB_NAME` = your database name
   - `DB_USER` = your database username
   - `DB_PASS` = your database password

---

## Local Development Setup

### Using XAMPP (Windows)

1. **Start MySQL** in XAMPP Control Panel
2. **Open phpMyAdmin**: http://localhost/phpmyadmin
3. **Create Database**: `ub_lost_found`
4. **Import SQL**: Select database → Import → Choose `ub_lost_found_COMPLETE.sql`
5. **Update Database.php**:
   ```php
   private $host = 'localhost';
   private $db_name = 'ub_lost_found';
   private $username = 'root';
   private $password = '';  // XAMPP default is empty
   ```

### Using Docker MySQL

```bash
docker run --name mysql-ublf -e MYSQL_ROOT_PASSWORD=rootpassword -e MYSQL_DATABASE=ub_lost_found -p 3306:3306 -d mysql:8.0
```

Then import the SQL file.

---

## Database Connection Usage

The database is used throughout the application:

- **Classes**: `Student.php`, `Admin.php`, `Item.php`, `ReportItem.php`, `Notification.php`
- **Public Pages**: All pages in `Lost&found/htdocs/public/`
- **API**: `Lost&found/htdocs/api/v1/`

All classes instantiate `Database` class:
```php
require_once __DIR__ . '/../includes/Database.php';
$db = new Database();
$conn = $db->getConnection();
```

---

## Default Test Accounts

After importing the database, you can use:

**Admin:**
- Username: `admin`
- Password: Check the `PasswordHash` in the database (default might be `password`)

**Student:**
- StudentNo: `TEST001`
- Password: `test123` (if test data is included)

---

## Troubleshooting

### "Database connection unavailable"
- Check database credentials in `Database.php`
- Verify database server is running
- Check if database exists
- Verify user has proper permissions

### "Access denied for user"
- Check username and password
- Verify user has access to the database
- Check if host allows connections from your IP

### "Unknown database"
- Create the database first
- Import the SQL schema
- Verify database name matches in `Database.php`

---

## Security Notes

⚠️ **Important**: 
- Never commit database passwords to Git
- Use environment variables for production
- Change default passwords
- Use strong passwords for production databases
- Consider using connection pooling for high traffic

---

## Quick Reference

| Item | Location |
|------|----------|
| **Database Config** | `Lost&found/htdocs/includes/Database.php` |
| **Complete SQL** | `Lost&found/db/ub_lost_found_COMPLETE.sql` |
| **Individual SQL Files** | `Lost&found/db/*.sql` |
| **Config Class** | `Lost&found/htdocs/includes/Config.php` |

---

**Current Database**: `gcrajoqq_ublf` on `localhost`  
**To change**: Edit `Lost&found/htdocs/includes/Database.php`

