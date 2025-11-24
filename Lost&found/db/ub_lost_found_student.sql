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
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student`
--

LOCK TABLES `student` WRITE;
/*!40000 ALTER TABLE `student` DISABLE KEYS */;
INSERT INTO `student` VALUES (1,'2220009','$2y$10$iwhqLPdYvGMoTsF6UlJDBuZeizQ8iKL2vxrIWobFsc4tqTvPiNqgW','Kim Andrei','kimandrei012@gmail.com','09672564545',NULL,NULL,'assets/uploads/profile_6874e01b2e5bf0.10201398.jpg','2025-07-11 12:02:21','2025-07-14 10:46:59','',1),(2,'123456','$2y$10$fnOfd4bzNvO85RQ.2qEnx.cVH.bCYEmx6FofberDDjq7OP4sQ5waa','Kim','kimandrei@gmail.com','090909090909',NULL,NULL,NULL,'2025-07-11 13:11:06','2025-07-11 16:10:38','',0),(3,'123123','$2y$10$k4KiG4ghuWgABhOQ8PQ5weq7fhJwi5Ed25g6C6Q22ilMkprLC9cka','Kim Andrei','kimkim@gmail.com','09212795669',NULL,NULL,'assets/uploads/profile_6871461e8da378.93103426.jpg','2025-07-11 16:29:37','2025-07-11 17:13:06','',1),(4,'123451','$2y$10$XLiZI2wM9SgQV6dE9vT3jO/z470OJasg4xsoqJMj4aIh9YEOYA82.','Kim Andrei Besmar','123451@ub.edu.ph','09672564545',NULL,NULL,NULL,'2025-07-14 12:01:09','2025-07-14 12:03:17','',0),(5,'1234567','$2y$10$eM5KQN2BCqZj3GvnHvLKsu2fAasRVtaBFIFObSxbqlHu7SldvyXRu','Kim Andrei Besmar','1234567@ub.edu.ph','09672564545',NULL,NULL,NULL,'2025-11-08 11:29:33','2025-11-08 11:29:33',NULL,0);
/*!40000 ALTER TABLE `student` ENABLE KEYS */;
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
