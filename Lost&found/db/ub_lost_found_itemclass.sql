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
-- Table structure for table `itemclass`
--

DROP TABLE IF EXISTS `itemclass`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itemclass` (
  `ItemClassID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(100) NOT NULL,
  `Description` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ItemClassID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itemclass`
--

LOCK TABLES `itemclass` WRITE;
/*!40000 ALTER TABLE `itemclass` DISABLE KEYS */;
-- Insert comprehensive school-appropriate item class categories
INSERT INTO `itemclass` (`ClassName`, `Description`) VALUES
('Electronics', 'Electronic devices like phones, laptops, tablets, chargers, headphones, etc.'),
('Bags', 'Backpacks, purses, wallets, handbags, tote bags, etc.'),
('Books & Notebooks', 'Textbooks, notebooks, binders, planners, study materials'),
('Clothing & Accessories', 'Jackets, sweaters, hats, scarves, gloves, belts, etc.'),
('ID Cards & Documents', 'Student IDs, driver licenses, certificates, important papers'),
('Keys & Keychains', 'House keys, car keys, keychains, lanyards'),
('Stationery & School Supplies', 'Pens, pencils, calculators, rulers, erasers, highlighters'),
('Jewelry & Watches', 'Rings, necklaces, bracelets, watches, earrings'),
('Sports Equipment', 'Balls, rackets, gym bags, sports gear'),
('Umbrellas', 'Umbrellas and rain gear'),
('Water Bottles & Containers', 'Water bottles, lunch boxes, containers, thermos'),
('Eyewear', 'Glasses, sunglasses, contact lens cases'),
('Others', 'Items that do not fit into other categories');
/*!40000 ALTER TABLE `itemclass` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-11 22:38:15
