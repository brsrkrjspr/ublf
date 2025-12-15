-- =====================================================
-- SAFE DELETION QUERIES FOR LOST AND FOUND ITEMS
-- =====================================================
-- This script safely deletes all reported lost and found items
-- while preserving reference data (itemclass, itemstatus, reportstatus, student, admin)
--
-- IMPORTANT: This will delete ALL lost and found item records!
-- Run this entire script in MySQL Workbench
--
-- DELETION ORDER (respects foreign key constraints):
--   1. reportitem_match (child table - references both reportitem and item)
--   2. reportitem (lost items reported by students)
--   3. item (found items reported by admins)
-- =====================================================

-- Check current record counts (before deletion)
SELECT 'BEFORE DELETION - Current Record Counts' AS Info;
SELECT COUNT(*) AS reportitem_match_count FROM reportitem_match;
SELECT COUNT(*) AS reportitem_count FROM reportitem;
SELECT COUNT(*) AS item_count FROM item;

-- Start transaction for safety (allows rollback if needed)
START TRANSACTION;

-- Step 1: Delete matches first (child table)
-- This table links lost items (reportitem) to found items (item)
-- Foreign keys: ReportID → reportitem, ItemID → item, MatchedBy → admin
-- Must delete this first because it references both reportitem and item
DELETE FROM reportitem_match;

-- Step 2: Delete lost item reports (reportitem)
-- This deletes all lost item reports submitted by students
-- Foreign keys: ItemClassID → itemclass, ReportStatusID → reportstatus, StudentNo → student
-- These reference tables are preserved, so deletion is safe
DELETE FROM reportitem;

-- Step 3: Delete found items (item)
-- This deletes all found items reported by admins
-- Foreign keys: ItemClassID → itemclass, StatusID → itemstatus, AdminID → admin
-- These reference tables are preserved, so deletion is safe
DELETE FROM item;

-- Verify deletion (check that counts are now 0)
SELECT 'AFTER DELETION - Record Counts (should all be 0)' AS Info;
SELECT COUNT(*) AS reportitem_match_count FROM reportitem_match;
SELECT COUNT(*) AS reportitem_count FROM reportitem;
SELECT COUNT(*) AS item_count FROM item;

-- If everything looks good (all counts are 0), commit the changes:
COMMIT;

-- If something went wrong or you want to undo, uncomment the line below instead:
-- ROLLBACK;

-- =====================================================
-- NOTES:
-- =====================================================
-- This script preserves (NOT deleted):
--    - itemclass (item categories)
--    - itemstatus (item statuses)
--    - reportstatus (report statuses)
--    - student (student accounts)
--    - admin (admin accounts)
--    - profile_photo_history (profile photos)
--    - notifications (notification records - RelatedID may become orphaned but no FK constraint)
--
-- This script deletes:
--    - reportitem_match (matches between lost and found items)
--    - reportitem (lost item reports - all records from reportitem table)
--    - item (found items - all records from item table)
--
-- Foreign Key Relationships Verified:
--    - reportitem_match.ReportID → reportitem.ReportID
--    - reportitem_match.ItemID → item.ItemID
--    - reportitem_match.MatchedBy → admin.AdminID (preserved)
--    - reportitem.ItemClassID → itemclass.ItemClassID (preserved)
--    - reportitem.ReportStatusID → reportstatus.ReportStatusID (preserved)
--    - reportitem.StudentNo → student.StudentNo (preserved)
--    - item.ItemClassID → itemclass.ItemClassID (preserved)
--    - item.StatusID → itemstatus.StatusID (preserved)
--    - item.AdminID → admin.AdminID (preserved)
-- =====================================================
