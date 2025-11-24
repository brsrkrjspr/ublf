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
-- Table structure for table `item`
--

DROP TABLE IF EXISTS `item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item`
--

LOCK TABLES `item` WRITE;
/*!40000 ALTER TABLE `item` DISABLE KEYS */;
INSERT INTO `item` VALUES (1,'Wow','aaaa',6,1,'assets/uploads/found_6875208b91bcb_1752506507.jpg','dito','2025-07-02',1,NULL,'2025-07-14 15:21:47','2025-07-14 15:21:59',1),(2,'Wow','aaaa',6,1,'assets/uploads/found_6875209b34f62_1752506523.jpg','dito','2025-07-02',1,NULL,'2025-07-14 15:22:03','2025-07-14 15:24:01',-1),(3,'Test','Test',7,1,'assets/uploads/found_68752108c0dce_1752506632.png','Test','2025-07-11',1,NULL,'2025-07-14 15:23:52','2025-07-14 15:24:17',1);
/*!40000 ALTER TABLE `item` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-11 22:38:14
