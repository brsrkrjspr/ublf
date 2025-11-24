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
-- Table structure for table `profile_photo_history`
--

DROP TABLE IF EXISTS `profile_photo_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profile_photo_history`
--

LOCK TABLES `profile_photo_history` WRITE;
/*!40000 ALTER TABLE `profile_photo_history` DISABLE KEYS */;
INSERT INTO `profile_photo_history` VALUES (1,'2220009','assets/uploads/profile_6874db7d63dda5.83874671.jpg',1,'2025-07-14 10:27:09','2025-07-14 10:33:53',1),(2,'2220009','assets/uploads/profile_6874df11799875.33446588.jpg',1,'2025-07-14 10:42:25','2025-07-14 10:42:31',1),(3,'2220009','assets/uploads/profile_6874e01b2e5bf0.10201398.jpg',1,'2025-07-14 10:46:51','2025-07-14 10:46:59',1);
/*!40000 ALTER TABLE `profile_photo_history` ENABLE KEYS */;
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
