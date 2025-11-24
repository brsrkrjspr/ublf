# UB Lost & Found - OOP Refactor Documentation

## Overview

This document outlines the comprehensive Object-Oriented Programming (OOP) refactor implemented for the University of Batangas Lost & Found web application. The refactor improves code maintainability, separation of concerns, and overall architecture.

## 🏗️ Architecture Changes

### Before (Procedural)
- All database operations scattered across PHP files
- Inline CSS and JavaScript mixed with HTML
- Business logic embedded in presentation layer
- No clear separation of concerns

### After (Object-Oriented)
- Clean class-based architecture
- Separated CSS and JavaScript files
- Business logic encapsulated in classes
- Clear separation of concerns

## 📁 New File Structure

```
htdocs/
├── classes/                    # PHP Classes (OOP Core)
│   ├── Student.php            # Student management
│   ├── Admin.php              # Admin management
│   ├── ReportItem.php         # Lost item reports
│   ├── Item.php               # Found items
│   ├── Notification.php       # Notification system
│   └── FileUpload.php         # File upload utilities
├── assets/                     # Frontend Assets
│   ├── ub.css                 # Main UB branding styles
│   ├── dashboard.css          # Dashboard-specific styles
│   ├── admin.css              # Admin dashboard styles
│   ├── profile.css            # Profile page styles
│   ├── notifications.css      # Notification styles
│   ├── notifications.js       # Notification functionality
│   ├── forms.js               # Form handling & validation
│   └── uploads/               # Uploaded files
├── includes/                   # Core includes
│   └── Database.php           # Database connection
├── public/                     # Public pages (refactored)
│   ├── dashboard.php          # User dashboard (OOP)
│   ├── admin_dashboard.php    # Admin dashboard (OOP)
│   └── ... (other pages)
└── templates/                  # Reusable templates
    └── header.php             # Common header
```

## 🎯 Core Classes

### 1. Student Class (`classes/Student.php`)
**Responsibilities:**
- User registration and authentication
- Profile management
- Photo approval workflow
- Student data retrieval

**Key Methods:**
```php
public function register($studentNo, $studentName, $phoneNo, $email, $password)
public function login($studentNo, $password)
public function updateProfile($studentNo, $studentName, $phoneNo, $email)
public function updateProfilePhoto($studentNo, $photoURL)
public function approvePhoto($studentNo)
public function rejectPhoto($studentNo)
```

### 2. Admin Class (`classes/Admin.php`)
**Responsibilities:**
- Admin authentication
- Dashboard statistics
- Approval management
- Admin user management

**Key Methods:**
```php
public function login($username, $password)
public function getDashboardStats()
public function getPendingApprovals()
public function getCompletedApprovals()
public function addAdmin($username, $email, $password)
public function removeAdmin($adminID, $currentAdminID)
```

### 3. ReportItem Class (`classes/ReportItem.php`)
**Responsibilities:**
- Lost item report creation
- Report approval/rejection
- Search and filtering
- Notification integration

**Key Methods:**
```php
public function create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL)
public function getAllApproved($limit = null, $offset = 0)
public function approve($reportItemID, $adminID)
public function reject($reportItemID, $adminID)
public function search($searchTerm, $itemClass = null)
```

### 4. Item Class (`classes/Item.php`)
**Responsibilities:**
- Found item management
- Item approval workflow
- Search functionality
- Match finding

**Key Methods:**
```php
public function create($adminID, $itemName, $itemClass, $description, $dateFound, $locationFound, $photoURL)
public function getAllApproved($limit = null, $offset = 0)
public function approve($itemID, $adminID)
public function reject($itemID, $adminID)
public function findMatches($lostItemName, $lostItemClass = null)
```

### 5. Notification Class (`classes/Notification.php`)
**Responsibilities:**
- Notification creation and management
- Read/unread status tracking
- Notification formatting
- Cleanup operations

**Key Methods:**
```php
public function create($studentNo, $type, $title, $message, $relatedID = null)
public function getUnread($studentNo, $limit = 10)
public function markAsRead($notificationID, $studentNo)
public function getUnreadCount($studentNo)
```

### 6. FileUpload Class (`classes/FileUpload.php`)
**Responsibilities:**
- File validation and upload
- Image processing
- Security checks
- Error handling

**Key Methods:**
```php
public function uploadPhoto($file, $prefix = 'photo')
public function validateFile($file)
public function resizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 600)
public function deleteFile($filePath)
```

## 🎨 Frontend Separation

### CSS Files
- **`ub.css`**: Main UB branding and global styles
- **`dashboard.css`**: Dashboard-specific styles (hero section, cards, modals)
- **`admin.css`**: Admin dashboard styles (stats cards, approval tables)
- **`profile.css`**: Profile page styles (Facebook-like layout)
- **`notifications.css`**: Notification system styles (dropdown, badges)

### JavaScript Files
- **`notifications.js`**: Notification management (AJAX, real-time updates)
- **`forms.js`**: Form validation, file uploads, user feedback

## 🔄 Migration Guide

### For Developers

1. **Database Operations**: Use class methods instead of direct SQL
   ```php
   // Old way
   $stmt = $conn->prepare("SELECT * FROM student WHERE StudentNo = ?");
   
   // New way
   $student = new Student();
   $studentData = $student->getByStudentNo($studentNo);
   ```

2. **File Uploads**: Use FileUpload class
   ```php
   // Old way
   move_uploaded_file($_FILES['photo']['tmp_name'], $target);
   
   // New way
   $fileUpload = new FileUpload();
   $result = $fileUpload->uploadPhoto($_FILES['photo'], 'profile');
   ```

3. **Notifications**: Automatic integration
   ```php
   // Notifications are automatically created when:
   // - Photos are approved/rejected
   // - Reports are approved/rejected
   // - Admin actions are performed
   ```

### For Styling

1. **Page-specific styles**: Add to appropriate CSS file
2. **Global styles**: Add to `ub.css`
3. **Component styles**: Create new CSS file if needed

### For JavaScript

1. **Form handling**: Use `forms.js` classes
2. **Notifications**: Use `notifications.js` classes
3. **Custom functionality**: Create new JS file following the same pattern

## 🚀 Benefits Achieved

### 1. Maintainability
- **Single Responsibility**: Each class has one clear purpose
- **DRY Principle**: No code duplication
- **Easy Testing**: Classes can be unit tested independently

### 2. Scalability
- **Modular Design**: Easy to add new features
- **Extensible**: New classes can be added without affecting existing code
- **Performance**: Optimized database queries

### 3. Security
- **Input Validation**: Centralized in classes
- **SQL Injection Prevention**: Prepared statements in all classes
- **File Upload Security**: Comprehensive validation

### 4. User Experience
- **Real-time Notifications**: AJAX-powered updates
- **Form Validation**: Client and server-side validation
- **Responsive Design**: Mobile-friendly interfaces

## 🔧 Configuration

### Database
- Ensure database schema is up to date
- Run any pending migration scripts
- Verify all tables exist with correct structure

### File Permissions
- `assets/uploads/` directory must be writable
- Ensure proper file permissions for uploaded images

### PHP Requirements
- PHP 7.4+ recommended
- PDO extension enabled
- GD extension for image processing

## 📝 Usage Examples

### Creating a Lost Item Report
```php
$reportItem = new ReportItem();
$fileUpload = new FileUpload();

// Handle file upload
$photoURL = null;
if (isset($_FILES['photo'])) {
    $uploadResult = $fileUpload->uploadPhoto($_FILES['photo'], 'lost');
    if ($uploadResult['success']) {
        $photoURL = $uploadResult['path'];
    }
}

// Create report
$result = $reportItem->create(
    $studentNo,
    $itemName,
    $itemClass,
    $description,
    $dateOfLoss,
    $lostLocation,
    $photoURL
);
```

### Admin Approving a Report
```php
$reportItem = new ReportItem();
$result = $reportItem->approve($reportItemID, $adminID);
// Notification is automatically created
```

### Getting Dashboard Statistics
```php
$admin = new Admin();
$stats = $admin->getDashboardStats();
// Returns: totalStudents, pendingPhotoApprovals, pendingLostApprovals, etc.
```

## 🎯 Future Enhancements

1. **API Layer**: RESTful API for mobile app integration
2. **Caching**: Redis/Memcached for performance optimization
3. **Logging**: Comprehensive logging system
4. **Testing**: Unit and integration tests
5. **Documentation**: API documentation with Swagger

## 📞 Support

For questions or issues with the OOP refactor:
1. Check this documentation first
2. Review the class methods and their parameters
3. Ensure database schema is correct
4. Verify file permissions are set properly

---

**Note**: This refactor maintains backward compatibility while providing a solid foundation for future development. All existing functionality has been preserved and enhanced through the OOP structure. 