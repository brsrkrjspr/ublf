-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: ub_lost_found
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'123123','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',3,1,'2025-07-11 17:09:38'),(2,'123123','report_approved','Lost Item Report Approved!','Your lost item report for \"f\" has been approved and is now visible to other users.',4,1,'2025-07-11 17:12:46'),(3,'123123','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',3,1,'2025-07-11 17:13:06'),(4,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-12 01:33:56'),(5,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',1,1,'2025-07-12 01:35:51'),(6,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',1,1,'2025-07-12 03:17:18'),(7,'2220009','photo_rejected','Profile Photo Rejected','Your profile photo was rejected. Please upload a different photo.',2220009,1,'2025-07-14 09:47:06'),(8,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 09:47:29'),(9,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 09:50:39'),(10,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:09:22'),(11,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:12:05'),(12,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:14:18'),(13,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:14:32'),(14,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:33:53'),(15,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:42:31'),(16,'2220009','photo_approved','Profile Photo Approved!','Your profile photo has been approved and is now visible to other users.',2220009,1,'2025-07-14 10:46:59');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-11 22:38:13
