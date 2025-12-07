-- =====================================================
-- UB Lost & Found - Complete Database Schema
-- Compiled for x10hosting deployment
-- =====================================================
-- MySQL dump - Compatible with MySQL 5.5.3+ and MariaDB 10.0+
-- Server version: Compatible with older MySQL/MariaDB versions
-- =====================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- =====================================================
-- TABLE: admin
-- =====================================================
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `AdminID` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `AdminName` varchar(100) NOT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`AdminID`),
  UNIQUE KEY `Username` (`Username`),
  UNIQUE KEY `Email` (`Email`),
  KEY `idx_admin_username` (`Username`),
  KEY `idx_admin_email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `admin` WRITE;
INSERT INTO `admin` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin@example.com','Admin User','2025-07-11 16:24:36'),(2,'AAAA','$2y$10$VdwVqv6a/JIMORjlFTe4A..QufMNIbkvxI7hNBO/d7LNiY1wRg376','asadada@gmail.com','AAAA','2025-07-11 18:04:42');
UNLOCK TABLES;

-- =====================================================
-- TABLE: student
-- =====================================================
DROP TABLE IF EXISTS `student`;
CREATE TABLE `student` (
  `StudentID` int NOT NULL AUTO_INCREMENT,
  `StudentNo` varchar(20) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `StudentName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PhoneNo` varchar(20) DEFAULT NULL,
  `Course` varchar(100) DEFAULT NULL,
  `YearLevel` varchar(20) DEFAULT NULL,
  `ProfilePhoto` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Bio` text,
  `PhotoConfirmed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`StudentID`),
  UNIQUE KEY `StudentNo` (`StudentNo`),
  UNIQUE KEY `Email` (`Email`),
  KEY `idx_student_studentno` (`StudentNo`),
  KEY `idx_student_email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `student` WRITE;
INSERT INTO `student` VALUES (1,'2220009','$2y$10$iwhqLPdYvGMoTsF6UlJDBuZeizQ8iKL2vxrIWobFsc4tqTvPiNqgW','Kim Andrei','kimandrei012@gmail.com','09672564545',NULL,NULL,'assets/uploads/profile_6874e01b2e5bf0.10201398.jpg','2025-07-11 12:02:21','2025-07-14 10:46:59','',1),(2,'123456','$2y$10$fnOfd4bzNvO85RQ.2qEnx.cVH.bCYEmx6FofberDDjq7OP4sQ5waa','Kim','kimandrei@gmail.com','090909090909',NULL,NULL,NULL,'2025-07-11 13:11:06','2025-07-11 16:10:38','',0),(3,'123123','$2y$10$k4KiG4ghuWgABhOQ8PQ5weq7fhJwi5Ed25g6C6Q22ilMkprLC9cka','Kim Andrei','kimkim@gmail.com','09212795669',NULL,NULL,'assets/uploads/profile_6871461e8da378.93103426.jpg','2025-07-11 16:29:37','2025-07-11 17:13:06','',1),(4,'123451','$2y$10$XLiZI2wM9SgQV6dE9vT3jO/z470OJasg4xsoqJMj4aIh9YEOYA82.','Kim Andrei Besmar','123451@ub.edu.ph','09672564545',NULL,NULL,NULL,'2025-07-14 12:01:09','2025-07-14 12:03:17','',0),(5,'1234567','$2y$10$eM5KQN2BCqZj3GvnHvLKsu2fAasRVtaBFIFObSxbqlHu7SldvyXRu','Kim Andrei Besmar','1234567@ub.edu.ph','09672564545',NULL,NULL,NULL,'2025-11-08 11:29:33','2025-11-08 11:29:33',NULL,0);
UNLOCK TABLES;

-- =====================================================
-- TABLE: itemclass
-- =====================================================
DROP TABLE IF EXISTS `itemclass`;
CREATE TABLE `itemclass` (
  `ItemClassID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(100) NOT NULL,
  `Description` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ItemClassID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `itemclass` WRITE;
INSERT INTO `itemclass` VALUES (6,'Bags',NULL,'2025-07-14 15:21:47'),(7,'Electronics',NULL,'2025-07-14 15:23:52');
UNLOCK TABLES;

-- =====================================================
-- TABLE: itemstatus
-- =====================================================
DROP TABLE IF EXISTS `itemstatus`;
CREATE TABLE `itemstatus` (
  `StatusID` int NOT NULL AUTO_INCREMENT,
  `StatusName` varchar(50) NOT NULL,
  `Description` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`StatusID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `itemstatus` WRITE;
INSERT INTO `itemstatus` VALUES (1,'Available','Item is available for claiming','2025-07-11 16:41:34'),(2,'Claimed','Item has been claimed by owner','2025-07-11 16:41:34'),(3,'Expired','Item has been disposed of after holding period','2025-07-11 16:41:34'),(4,'Pending','Item is under review','2025-07-11 16:41:34');
UNLOCK TABLES;

-- =====================================================
-- TABLE: reportstatus
-- =====================================================
DROP TABLE IF EXISTS `reportstatus`;
CREATE TABLE `reportstatus` (
  `ReportStatusID` int NOT NULL AUTO_INCREMENT,
  `StatusName` varchar(50) NOT NULL,
  `Description` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReportStatusID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `reportstatus` WRITE;
INSERT INTO `reportstatus` VALUES (1,'Open','Report is still open and searching','2025-07-11 16:41:34'),(2,'Found','Item has been found and matched','2025-07-11 16:41:34'),(3,'Closed','Report has been closed','2025-07-11 16:41:34'),(4,'Expired','Report has expired','2025-07-11 16:41:34');
UNLOCK TABLES;

-- =====================================================
-- TABLE: status
-- =====================================================
DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `StatusID` int NOT NULL AUTO_INCREMENT,
  `StatusName` varchar(50) NOT NULL,
  PRIMARY KEY (`StatusID`),
  UNIQUE KEY `StatusName` (`StatusName`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `status` WRITE;
INSERT INTO `status` VALUES (2,'Claimed'),(5,'In Review'),(1,'Open'),(3,'Returned'),(4,'Unclaimed');
UNLOCK TABLES;

-- =====================================================
-- TABLE: item (depends on itemclass, itemstatus, admin)
-- =====================================================
DROP TABLE IF EXISTS `item`;
CREATE TABLE `item` (
  `ItemID` int NOT NULL AUTO_INCREMENT,
  `ItemName` varchar(200) NOT NULL,
  `Description` text,
  `ItemClassID` int NOT NULL,
  `StatusID` int NOT NULL,
  `PhotoURL` varchar(255) DEFAULT NULL,
  `LocationFound` varchar(200) DEFAULT NULL,
  `DateFound` date NOT NULL,
  `AdminID` int NOT NULL,
  `ContactInfo` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `StatusConfirmed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`ItemID`),
  KEY `idx_item_class` (`ItemClassID`),
  KEY `idx_item_status` (`StatusID`),
  KEY `idx_item_admin` (`AdminID`),
  CONSTRAINT `item_ibfk_1` FOREIGN KEY (`ItemClassID`) REFERENCES `itemclass` (`ItemClassID`),
  CONSTRAINT `item_ibfk_2` FOREIGN KEY (`StatusID`) REFERENCES `itemstatus` (`StatusID`),
  CONSTRAINT `item_ibfk_3` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `item` WRITE;
INSERT INTO `item` VALUES (1,'Wow','aaaa',6,1,'assets/uploads/found_6875208b91bcb_1752506507.jpg','dito','2025-07-02',1,NULL,'2025-07-14 15:21:47','2025-07-14 15:21:59',1),(2,'Wow','aaaa',6,1,'assets/uploads/found_6875209b34f62_1752506523.jpg','dito','2025-07-02',1,NULL,'2025-07-14 15:22:03','2025-07-14 15:24:01',-1),(3,'Test','Test',7,1,'assets/uploads/found_68752108c0dce_1752506632.png','Test','2025-07-11',1,NULL,'2025-07-14 15:23:52','2025-07-14 15:24:17',1);
UNLOCK TABLES;

-- =====================================================
-- TABLE: reportitem (depends on itemclass, reportstatus, student)
-- =====================================================
DROP TABLE IF EXISTS `reportitem`;
CREATE TABLE `reportitem` (
  `ReportID` int NOT NULL AUTO_INCREMENT,
  `ItemName` varchar(200) NOT NULL,
  `Description` text,
  `ItemClassID` int NOT NULL,
  `ReportStatusID` int NOT NULL,
  `PhotoURL` varchar(255) DEFAULT NULL,
  `LostLocation` varchar(200) DEFAULT NULL,
  `DateOfLoss` date NOT NULL,
  `StudentNo` varchar(20) NOT NULL,
  `ContactInfo` text,
  `Reward` decimal(10,2) DEFAULT '0.00',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `StatusConfirmed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`ReportID`),
  KEY `idx_reportitem_class` (`ItemClassID`),
  KEY `idx_reportitem_status` (`ReportStatusID`),
  KEY `idx_reportitem_studentno` (`StudentNo`),
  CONSTRAINT `reportitem_ibfk_1` FOREIGN KEY (`ItemClassID`) REFERENCES `itemclass` (`ItemClassID`),
  CONSTRAINT `reportitem_ibfk_2` FOREIGN KEY (`ReportStatusID`) REFERENCES `reportstatus` (`ReportStatusID`),
  CONSTRAINT `reportitem_ibfk_3` FOREIGN KEY (`StudentNo`) REFERENCES `student` (`StudentNo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `reportitem` WRITE;
UNLOCK TABLES;

-- =====================================================
-- TABLE: notifications (depends on student)
-- =====================================================
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `NotificationID` int NOT NULL AUTO_INCREMENT,
  `StudentNo` varchar(20) NOT NULL,
  `Type` enum('photo_approved','photo_rejected','report_approved','report_rejected','item_matched','admin_message','system_alert') NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Message` text NOT NULL,
  `RelatedID` int DEFAULT NULL,
  `IsRead` tinyint(1) DEFAULT '0',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`NotificationID`),
  KEY `idx_notifications_student` (`StudentNo`),
  KEY `idx_notifications_type` (`Type`),
  KEY `idx_notifications_read` (`IsRead`),
  KEY `idx_notifications_created` (`CreatedAt`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`StudentNo`) REFERENCES `student` (`StudentNo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `notifications` WRITE;
INSERT INTO `notifications` VALUES (1,'123123','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',3,1,'2025-07-11 17:09:38'),(2,'123123','report_approved','Lost Item Report Approved!','Your lost item report for \"f\" has been approved and is now visible to other users.',4,1,'2025-07-11 17:12:46'),(3,'123123','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',3,1,'2025-07-11 17:13:06'),(4,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-12 01:33:56'),(5,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',1,1,'2025-07-12 01:35:51'),(6,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',1,1,'2025-07-12 03:17:18'),(7,'2220009','photo_rejected','Profile Photo Rejected','Your profile photo was rejected. Please upload a different photo.',2220009,1,'2025-07-14 09:47:06'),(8,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 09:47:29'),(9,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 09:50:39'),(10,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:09:22'),(11,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:12:05'),(12,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:14:18'),(13,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:14:32'),(14,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:33:53'),(15,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:42:31'),(16,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:46:59');
UNLOCK TABLES;

-- =====================================================
-- TABLE: profile_photo_history (depends on student, admin)
-- =====================================================
DROP TABLE IF EXISTS `profile_photo_history`;
CREATE TABLE `profile_photo_history` (
  `PhotoID` int NOT NULL AUTO_INCREMENT,
  `StudentNo` varchar(20) NOT NULL,
  `PhotoURL` varchar(255) NOT NULL,
  `Status` tinyint(1) NOT NULL,
  `SubmittedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ReviewedAt` timestamp NULL DEFAULT NULL,
  `ReviewedBy` int DEFAULT NULL,
  PRIMARY KEY (`PhotoID`),
  KEY `StudentNo` (`StudentNo`),
  KEY `ReviewedBy` (`ReviewedBy`),
  CONSTRAINT `profile_photo_history_ibfk_1` FOREIGN KEY (`StudentNo`) REFERENCES `student` (`StudentNo`) ON DELETE CASCADE,
  CONSTRAINT `profile_photo_history_ibfk_2` FOREIGN KEY (`ReviewedBy`) REFERENCES `admin` (`AdminID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `profile_photo_history` WRITE;
INSERT INTO `profile_photo_history` VALUES (1,'2220009','assets/uploads/profile_6874db7d63dda5.83874671.jpg',1,'2025-07-14 10:27:09','2025-07-14 10:33:53',1),(2,'2220009','assets/uploads/profile_6874df11799875.33446588.jpg',1,'2025-07-14 10:42:25','2025-07-14 10:42:31',1),(3,'2220009','assets/uploads/profile_6874e01b2e5bf0.10201398.jpg',1,'2025-07-14 10:46:51','2025-07-14 10:46:59',1);
UNLOCK TABLES;

-- =====================================================
-- TABLE: reportitem_match (depends on reportitem, item, admin)
-- =====================================================
DROP TABLE IF EXISTS `reportitem_match`;
CREATE TABLE `reportitem_match` (
  `MatchID` int NOT NULL AUTO_INCREMENT,
  `ReportID` int NOT NULL,
  `ItemID` int NOT NULL,
  `MatchScore` decimal(5,2) DEFAULT '0.00',
  `MatchStatus` enum('Pending','Confirmed','Rejected') DEFAULT 'Pending',
  `MatchedBy` int DEFAULT NULL,
  `MatchedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Notes` text,
  PRIMARY KEY (`MatchID`),
  KEY `ReportID` (`ReportID`),
  KEY `ItemID` (`ItemID`),
  KEY `MatchedBy` (`MatchedBy`),
  CONSTRAINT `reportitem_match_ibfk_1` FOREIGN KEY (`ReportID`) REFERENCES `reportitem` (`ReportID`),
  CONSTRAINT `reportitem_match_ibfk_2` FOREIGN KEY (`ItemID`) REFERENCES `item` (`ItemID`),
  CONSTRAINT `reportitem_match_ibfk_3` FOREIGN KEY (`MatchedBy`) REFERENCES `admin` (`AdminID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `reportitem_match` WRITE;
UNLOCK TABLES;

-- =====================================================
-- Restore settings
-- =====================================================
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- =====================================================
-- Database dump completed
-- =====================================================
-- Total Tables: 11
-- 1. admin
-- 2. student
-- 3. itemclass
-- 4. itemstatus
-- 5. reportstatus
-- 6. status
-- 7. item
-- 8. reportitem
-- 9. notifications
-- 10. profile_photo_history
-- 11. reportitem_match
-- =====================================================

