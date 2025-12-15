-- =====================================================
-- SAFE DELETION QUERIES FOR NOTIFICATIONS
-- =====================================================
-- This script safely deletes all notification records
-- while preserving reference data (student accounts)
--
-- IMPORTANT: This will delete ALL notification records!
-- Run this entire script in MySQL Workbench
--
-- DELETION SAFETY:
--   - notifications.StudentNo → student.StudentNo (student table preserved)
--   - RelatedID field has NO foreign key constraint (safe to delete)
--   - No other tables reference notifications table
-- =====================================================

-- Check current record count (before deletion)
SELECT 'BEFORE DELETION - Current Notification Count' AS Info;
SELECT COUNT(*) AS notification_count FROM notifications;
SELECT COUNT(*) AS unread_count FROM notifications WHERE IsRead = 0;
SELECT COUNT(*) AS read_count FROM notifications WHERE IsRead = 1;

-- Start transaction for safety (allows rollback if needed)
START TRANSACTION;

-- Delete all notifications
-- Safe because:
--   1. StudentNo references student table (which is preserved)
--   2. RelatedID has no foreign key constraint
--   3. No other tables depend on notifications
DELETE FROM notifications;

-- Verify deletion (check that count is now 0)
SELECT 'AFTER DELETION - Notification Count (should be 0)' AS Info;
SELECT COUNT(*) AS notification_count FROM notifications;

-- If everything looks good (count is 0), commit the changes:
COMMIT;

-- If something went wrong or you want to undo, uncomment the line below instead:
-- ROLLBACK;

-- =====================================================
-- NOTES:
-- =====================================================
-- This script preserves (NOT deleted):
--    - student (student accounts - notifications reference this)
--    - All other tables remain untouched
--
-- This script deletes:
--    - notifications (all notification records)
--
-- Foreign Key Relationships Verified:
--    - notifications.StudentNo → student.StudentNo (ON DELETE CASCADE)
--      Since student table is preserved, deletion is safe
--
-- RelatedID Field:
--    - The RelatedID field may reference ReportID or ItemID, but there is
--      NO foreign key constraint, so deleting reportitem or item won't
--      cause issues. RelatedID values will simply become orphaned references.
-- =====================================================

