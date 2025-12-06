# Backend Processes - UB Lost & Found System

This document lists all backend processes involved in the Lost & Found system, organized by functional area.

---

## 📋 Table of Contents

1. [Authentication & Authorization](#authentication--authorization)
2. [Student Management](#student-management)
3. [Admin Management](#admin-management)
4. [Lost Item Reports](#lost-item-reports)
5. [Found Item Management](#found-item-management)
6. [File Upload & Image Processing](#file-upload--image-processing)
7. [Notification System](#notification-system)
8. [Search & Matching](#search--matching)
9. [Approval Workflows](#approval-workflows)
10. [Analytics & Reporting](#analytics--reporting)
11. [Communication](#communication)
12. [Database Operations](#database-operations)

---

## 🔐 Authentication & Authorization

### Student Authentication
- **Process**: `Student::login($studentNo, $password)`
- **Location**: `classes/Student.php`
- **Steps**:
  1. Validate database connection
  2. Query student by StudentNo
  3. Verify password hash using `password_verify()`
  4. Return user data on success
  5. Handle mock login for testing (TEST001/test123)

### Student Registration
- **Process**: `Student::register($studentNo, $studentName, $phoneNo, $email, $password)`
- **Location**: `classes/Student.php`
- **Steps**:
  1. Check if student number or email already exists
  2. Hash password using `password_hash()` with BCRYPT
  3. Insert new student record
  4. Return success/failure status

### Admin Authentication
- **Process**: `Admin::login($username, $password)`
- **Location**: `classes/Admin.php`
- **Steps**:
  1. Query admin by username
  2. Verify password hash
  3. Return admin data on success
  4. Handle mock login for testing (admin/admin123)

### Session Management
- **Process**: Session creation and validation
- **Location**: All public PHP files
- **Steps**:
  1. `session_start()` on page load
  2. Check `$_SESSION['student']` or `$_SESSION['admin']`
  3. Redirect to login if not authenticated
  4. Store user data in session after login

---

## 👤 Student Management

### Get Student Data
- **Process**: `Student::getByStudentNo($studentNo)`
- **Location**: `classes/Student.php`
- **Steps**:
  1. Query student table by StudentNo
  2. Return student record with all fields
  3. Handle database unavailability with mock data

### Update Student Profile
- **Process**: `Student::updateProfile($studentNo, $studentName, $phoneNo, $email)`
- **Location**: `classes/Student.php`
- **Steps**:
  1. Validate input data
  2. Update student record in database
  3. Return success/failure status

### Update Profile Photo
- **Process**: `Student::updateProfilePhoto($studentNo, $photoURL)`
- **Location**: `classes/Student.php`
- **Steps**:
  1. Update ProfilePhoto field
  2. Set PhotoConfirmed = 0 (pending approval)
  3. Return success message with approval notice

### Get Pending Photo Approvals
- **Process**: `Student::getPendingPhotoApprovals()`
- **Location**: `classes/Student.php`
- **Steps**:
  1. Query students with ProfilePhoto IS NOT NULL AND PhotoConfirmed = 0
  2. Return list of pending approvals
  3. Order by StudentNo

### Get Completed Photo Approvals
- **Process**: `Student::getCompletedPhotoApprovals()`
- **Location**: `classes/Student.php`
- **Steps**:
  1. Query students with PhotoConfirmed IN (1, 2)
  2. Return approved/rejected photos
  3. Include UpdatedAt timestamp

---

## 👨‍💼 Admin Management

### Get All Admins
- **Process**: `Admin::getAllAdmins()`
- **Location**: `classes/Admin.php`
- **Steps**:
  1. Query all admin records
  2. Exclude password hash from results
  3. Order by CreatedAt DESC

### Add Admin
- **Process**: `Admin::addAdmin($username, $email, $password)`
- **Location**: `classes/Admin.php`
- **Steps**:
  1. Check if username or email already exists
  2. Hash password
  3. Insert new admin record
  4. Return success/failure status

### Remove Admin
- **Process**: `Admin::removeAdmin($adminID, $currentAdminID)`
- **Location**: `classes/Admin.php`
- **Steps**:
  1. Prevent self-removal
  2. Delete admin record
  3. Return success/failure status

### Change Admin Password
- **Process**: `Admin::changePassword($adminID, $currentPassword, $newPassword)`
- **Location**: `classes/Admin.php`
- **Steps**:
  1. Verify current password
  2. Hash new password
  3. Update password hash in database
  4. Return success/failure status

### Get Dashboard Statistics
- **Process**: `Admin::getDashboardStats()`
- **Location**: `classes/Admin.php`
- **Steps**:
  1. Count total students
  2. Count pending photo approvals
  3. Count pending lost item approvals
  4. Count pending found item approvals
  5. Count total approved lost items
  6. Count total approved found items
  7. Return statistics array

### Get Pending Approvals
- **Process**: `Admin::getPendingApprovals()`
- **Location**: `classes/Admin.php`
- **Steps**:
  1. Query pending photo approvals
  2. Query pending lost item approvals (with student and class info)
  3. Query pending found item approvals (with class info)
  4. Return structured array

### Get Completed Approvals
- **Process**: `Admin::getCompletedApprovals()`
- **Location**: `classes/Admin.php`
- **Steps**:
  1. Query completed photo approvals (StatusConfirmed IN (1, -1))
  2. Query completed lost item approvals
  3. Query completed found item approvals
  4. Include UpdatedAt timestamps
  5. Return structured array

---

## 📝 Lost Item Reports

### Create Lost Item Report
- **Process**: `ReportItem::create($studentNo, $itemName, $itemClass, $description, $dateOfLoss, $lostLocation, $photoURL)`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Get or create ItemClassID
  2. Insert report record with StatusConfirmed = 0 (pending)
  3. Set ReportStatusID = 1 (Open)
  4. Return success message with approval notice

### Get All Approved Reports
- **Process**: `ReportItem::getAllApproved($limit, $offset)`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Join with student and itemclass tables
  2. Filter by StatusConfirmed = 1
  3. Apply pagination if limit provided
  4. Order by ReportID DESC
  5. Return array of reports

### Get Reports by Student
- **Process**: `ReportItem::getByStudent($studentNo)`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Query reports for specific student
  2. Join with itemclass table
  3. Order by ReportID DESC
  4. Return all reports (approved and pending)

### Get Report by ID
- **Process**: `ReportItem::getById($reportItemID)`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Join with student and itemclass tables
  2. Return single report with all details

### Approve Lost Item Report
- **Process**: `ReportItem::approve($reportItemID, $adminID)`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Update StatusConfirmed = 1
  2. Get student information
  3. Create notification (report_approved)
  4. Return success status

### Reject Lost Item Report
- **Process**: `ReportItem::reject($reportItemID, $adminID)`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Update StatusConfirmed = 2
  2. Get student information
  3. Create notification (report_rejected)
  4. Return success status

### Delete Lost Item Report
- **Process**: `ReportItem::delete($reportItemID, $studentNo)`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Verify student owns the report
  2. Delete report record
  3. Return success status

### Search Lost Items
- **Process**: `ReportItem::search($searchTerm, $itemClass)`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Search in ItemName, Description, LostLocation
  2. Filter by item class if provided
  3. Only return approved reports (StatusConfirmed = 1)
  4. Use LIKE with wildcards
  5. Order by ReportID DESC

### Get Item Classes
- **Process**: `ReportItem::getItemClasses()`
- **Location**: `classes/ReportItem.php`
- **Steps**:
  1. Query distinct class names from itemclass table
  2. Join with reportitem to only show classes with approved reports
  3. Order alphabetically
  4. Return array of class names

---

## 🔍 Found Item Management

### Create Found Item
- **Process**: `Item::create($adminID, $itemName, $itemClass, $description, $dateFound, $locationFound, $photoURL)`
- **Location**: `classes/Item.php`
- **Steps**:
  1. Get or create ItemClassID
  2. Insert item record with StatusConfirmed = 0 (pending)
  3. Set StatusID = 1
  4. Return success message with approval notice

### Get All Approved Items
- **Process**: `Item::getAllApproved($limit, $offset)`
- **Location**: `classes/Item.php`
- **Steps**:
  1. Join with ItemClass table
  2. Filter by StatusConfirmed = 1
  3. Apply pagination if limit provided
  4. Order by ItemID DESC
  5. Return array of items

### Get Item by ID
- **Process**: `Item::getById($itemID)`
- **Location**: `classes/Item.php`
- **Steps**:
  1. Join with ItemClass table
  2. Return single item with all details

### Approve Found Item
- **Process**: `Item::approve($itemID, $adminID)`
- **Location**: `classes/Item.php`
- **Steps**:
  1. Update StatusConfirmed = 1
  2. Return success status

### Reject Found Item
- **Process**: `Item::reject($itemID, $adminID)`
- **Location**: `classes/Item.php`
- **Steps**:
  1. Update StatusConfirmed = 2
  2. Return success status

### Delete Found Item
- **Process**: `Item::delete($itemID, $adminID)`
- **Location**: `classes/Item.php`
- **Steps**:
  1. Verify admin owns the item
  2. Delete item record
  3. Return success status

### Search Found Items
- **Process**: `Item::search($searchTerm, $itemClass)`
- **Location**: `classes/Item.php`
- **Steps**:
  1. Search in ItemName, Description, LocationFound
  2. Filter by item class if provided
  3. Only return approved items (StatusConfirmed = 1)
  4. Use LIKE with wildcards
  5. Order by ItemID DESC

### Find Matches
- **Process**: `Item::findMatches($lostItemName, $lostItemClass)`
- **Location**: `classes/Item.php`
- **Steps**:
  1. Search found items matching lost item name
  2. Filter by item class if provided
  3. Only search approved items
  4. Limit to 5 results
  5. Return potential matches

---

## 📤 File Upload & Image Processing

### Upload Photo
- **Process**: `FileUpload::uploadPhoto($file, $prefix)`
- **Location**: `classes/FileUpload.php`
- **Steps**:
  1. Validate file (size, type, errors)
  2. Generate unique filename with prefix, uniqid(), and timestamp
  3. Ensure upload directory exists
  4. Move uploaded file to target directory
  5. Return relative path for database storage

### Validate File
- **Process**: `FileUpload::validateFile($file)`
- **Location**: `classes/FileUpload.php`
- **Steps**:
  1. Check upload error codes
  2. Verify file size (max 5MB default)
  3. Check MIME type using finfo
  4. Validate against allowed types (JPEG, PNG, GIF, WebP)
  5. Return validation result

### Resize Image
- **Process**: `FileUpload::resizeImage($sourcePath, $targetPath, $maxWidth, $maxHeight, $quality)`
- **Location**: `classes/FileUpload.php`
- **Steps**:
  1. Get image dimensions and type
  2. Calculate new dimensions maintaining aspect ratio
  3. Create new image resource
  4. Load source image based on type
  5. Resample image
  6. Preserve transparency for PNG
  7. Save resized image
  8. Clean up resources

### Delete File
- **Process**: `FileUpload::deleteFile($filePath)`
- **Location**: `classes/FileUpload.php`
- **Steps**:
  1. Construct full file path
  2. Check if file exists
  3. Delete file using unlink()
  4. Return success status

---

## 🔔 Notification System

### Create Notification
- **Process**: `Notification::create($studentNo, $type, $title, $message, $relatedID)`
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Insert notification record
  2. Set IsRead = 0 (unread)
  3. Store type, title, message, and relatedID
  4. Handle database errors gracefully

### Get Unread Notifications
- **Process**: `Notification::getUnread($studentNo, $limit)`
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Query notifications where IsRead = 0
  2. Filter by StudentNo
  3. Order by CreatedAt DESC
  4. Apply limit
  5. Return array of notifications

### Get All Notifications
- **Process**: `Notification::getAll($studentNo, $limit)`
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Query all notifications for student
  2. Order by CreatedAt DESC
  3. Apply limit
  4. Return array (read and unread)

### Mark as Read
- **Process**: `Notification::markAsRead($notificationID, $studentNo)`
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Update IsRead = 1
  2. Verify student owns notification
  3. Return success status

### Mark All as Read
- **Process**: `Notification::markAllAsRead($studentNo)`
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Update all unread notifications for student
  2. Set IsRead = 1
  3. Return success status

### Get Unread Count
- **Process**: `Notification::getUnreadCount($studentNo)`
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Count notifications where IsRead = 0
  2. Filter by StudentNo
  3. Return count integer

### Cleanup Old Notifications
- **Process**: `Notification::cleanupOldNotifications()`
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Delete notifications older than 30 days
  2. Use DATE_SUB() SQL function
  3. Return success status

### Get Notification Icon
- **Process**: `Notification::getIcon($type)` (static)
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Map notification types to Bootstrap icons
  2. Return icon class string

### Get Notification Color
- **Process**: `Notification::getColor($type)` (static)
- **Location**: `classes/Notification.php`
- **Steps**:
  1. Map notification types to Bootstrap colors
  2. Return color class string

---

## 🔍 Search & Matching

### Search Lost Items
- **Process**: `ReportItem::search($searchTerm, $itemClass)`
- **Location**: `classes/ReportItem.php`
- **Details**: See [Lost Item Reports](#lost-item-reports) section

### Search Found Items
- **Process**: `Item::search($searchTerm, $itemClass)`
- **Location**: `classes/Item.php`
- **Details**: See [Found Item Management](#found-item-management) section

### Find Matches Between Lost and Found
- **Process**: `Item::findMatches($lostItemName, $lostItemClass)`
- **Location**: `classes/Item.php`
- **Details**: See [Found Item Management](#found-item-management) section

---

## ✅ Approval Workflows

### Profile Photo Approval Workflow
- **Process**: Admin approves/rejects profile photos
- **Location**: `public/admin_action.php`
- **Steps**:
  1. Admin selects photo from pending list
  2. Admin clicks approve/reject
  3. Update profile_photo_history table
  4. Update student table (ProfilePhoto, PhotoConfirmed)
  5. Create notification (photo_approved/photo_rejected)
  6. Redirect to admin dashboard

### Lost Item Report Approval Workflow
- **Process**: Admin approves/rejects lost item reports
- **Location**: `public/admin_action.php` and `classes/ReportItem.php`
- **Steps**:
  1. Student submits lost item report (StatusConfirmed = 0)
  2. Admin reviews report
  3. Admin approves/rejects
  4. Update StatusConfirmed (1 = approved, -1 = rejected)
  5. Create notification (report_approved/report_rejected)
  6. If approved, report becomes visible to all users

### Found Item Approval Workflow
- **Process**: Admin approves/rejects found items
- **Location**: `public/admin_action.php` and `classes/Item.php`
- **Steps**:
  1. Admin submits found item (StatusConfirmed = 0)
  2. Another admin reviews
  3. Admin approves/rejects
  4. Update StatusConfirmed (1 = approved, -1 = rejected)
  5. If approved, item becomes visible to all users

### Item Class Management
- **Process**: Automatic creation of item classes
- **Location**: `classes/ReportItem.php` and `classes/Item.php`
- **Steps**:
  1. Check if item class exists
  2. If not, create new ItemClass record
  3. Return ItemClassID
  4. Use for report/item creation

---

## 📊 Analytics & Reporting

### Dashboard Statistics
- **Process**: `Admin::getDashboardStats()`
- **Location**: `classes/Admin.php`
- **Metrics**:
  - Total students
  - Pending photo approvals
  - Pending lost item approvals
  - Pending found item approvals
  - Total approved lost items
  - Total approved found items

### Pending Approvals Summary
- **Process**: `Admin::getPendingApprovals()`
- **Location**: `classes/Admin.php`
- **Returns**: Structured array with photos, lost items, and found items

### Completed Approvals History
- **Process**: `Admin::getCompletedApprovals()`
- **Location**: `classes/Admin.php`
- **Returns**: Historical approval data with timestamps

---

## 📧 Communication

### Contact Admin
- **Process**: Send email to admin
- **Location**: `public/contact_admin_send.php`
- **Steps**:
  1. Get student information from session
  2. Validate subject and message
  3. Format email body with student details
  4. Set email headers (From, Reply-To, Content-Type)
  5. Send email using PHP mail() function
  6. Store success/failure message in session
  7. Redirect to contact page

---

## 🗄️ Database Operations

### Database Connection
- **Process**: `Database::getConnection()`
- **Location**: `includes/Database.php`
- **Steps**:
  1. Create PDO connection to MySQL
  2. Set error mode to EXCEPTION
  3. Set charset to utf8mb4
  4. Handle connection errors gracefully
  5. Return connection object or null

### Prepared Statements
- **Process**: Used throughout all classes
- **Security**: All queries use prepared statements to prevent SQL injection
- **Pattern**:
  1. Prepare query with placeholders
  2. Execute with parameter array
  3. Fetch results

### Transaction Management
- **Note**: Currently not implemented, but could be added for complex operations

### Error Handling
- **Pattern**: Try-catch blocks in classes
- **Fallback**: Mock data when database unavailable
- **Logging**: Error logging can be enabled for development

---

## 🔄 Automated Processes (Potential for n8n)

### Notification Creation Triggers
- **When**: Profile photo approved/rejected
- **When**: Lost item report approved/rejected
- **Location**: Various classes automatically create notifications

### Match Detection
- **Current**: Manual search by users
- **Potential**: Automated matching when new found items are approved
- **Could trigger**: Email/SMS notifications to students with matching lost items

### Cleanup Tasks
- **Process**: `Notification::cleanupOldNotifications()`
- **Frequency**: Could be scheduled (cron job or n8n workflow)
- **Action**: Delete notifications older than 30 days

### Daily Reports
- **Current**: Not implemented
- **Potential**: Generate daily summary reports
- **Could include**: New reports, matches found, pending approvals

---

## 📝 Process Flow Examples

### Complete Lost Item Report Flow
1. Student logs in
2. Student fills out lost item form
3. Student uploads photo (FileUpload::uploadPhoto)
4. Report created (ReportItem::create) with StatusConfirmed = 0
5. Admin views pending reports (Admin::getPendingApprovals)
6. Admin approves report (ReportItem::approve)
7. Notification created (Notification::create)
8. Report becomes visible (ReportItem::getAllApproved)
9. Other users can search and view report

### Complete Found Item Flow
1. Admin logs in
2. Admin fills out found item form
3. Admin uploads photo (FileUpload::uploadPhoto)
4. Item created (Item::create) with StatusConfirmed = 0
5. Another admin reviews (Admin::getPendingApprovals)
6. Admin approves item (Item::approve)
7. Item becomes visible (Item::getAllApproved)
8. System could automatically search for matches (Item::findMatches)
9. Notifications sent to students with matching lost items (potential)

---

## 🔐 Security Processes

### Password Hashing
- **Algorithm**: BCRYPT
- **Function**: `password_hash($password, PASSWORD_BCRYPT)`
- **Verification**: `password_verify($password, $hash)`
- **Location**: Student and Admin classes

### Input Validation
- **File Uploads**: MIME type checking, size limits
- **Database**: Prepared statements prevent SQL injection
- **Email Format**: UB email format validation (@ub.edu.ph)

### Session Security
- **Session Start**: On every page
- **Authentication Check**: Redirect if not logged in
- **Session Data**: Stores user/admin information

---

## 📈 Performance Considerations

### Database Queries
- **Indexing**: Should be on StudentNo, Email, Username, ReportID, ItemID
- **Joins**: Used for related data (student names, class names)
- **Pagination**: Implemented for large result sets

### File Storage
- **Directory**: `assets/uploads/`
- **Naming**: Unique filenames prevent conflicts
- **Cleanup**: Old files not automatically deleted (manual process)

### Caching
- **Current**: No caching implemented
- **Potential**: Cache dashboard stats, item classes

---

## 🚀 Integration Points for n8n

### Webhook Triggers (To Create)
1. **New Lost Item Report** → Trigger match search
2. **New Found Item Approved** → Trigger match search and notifications
3. **Match Found** → Send email/SMS to student
4. **Report Approved** → Post to social media (optional)

### API Endpoints (To Create)
1. **GET /api/v1/reports** → Get all approved reports
2. **GET /api/v1/items** → Get all approved found items
3. **GET /api/v1/matches** → Get potential matches
4. **POST /api/v1/webhooks** → Receive webhooks from n8n

### Scheduled Tasks (n8n Cron)
1. **Daily Report Generation** → Email summary to admin
2. **Match Detection** → Run every hour to find new matches
3. **Notification Cleanup** → Delete old notifications
4. **Statistics Update** → Refresh dashboard stats

---

**Last Updated**: Based on current codebase analysis
**Total Backend Processes**: 50+ distinct processes across 6 main classes

