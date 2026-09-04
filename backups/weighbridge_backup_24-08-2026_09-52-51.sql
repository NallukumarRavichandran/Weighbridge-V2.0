-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: weighbridge_bltransport_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `apacs_token`
--

DROP TABLE IF EXISTS `apacs_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apacs_token` (
  `id` int(11) NOT NULL,
  `token` text NOT NULL,
  `expiry` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apacs_token`
--

LOCK TABLES `apacs_token` WRITE;
/*!40000 ALTER TABLE `apacs_token` DISABLE KEYS */;
INSERT INTO `apacs_token` VALUES (1,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6NSwibG9naW5JZCI6IjQxMjYzMjUzIiwid2VpZ2hCcmlkZ2VOYW1lIjoiTS9zLkIuTC4gVHJhbnNwb3J0IFB2dC5MdGQiLCJpYXQiOjE3ODcxMzY2NDgsImV4cCI6MTc4NzIyMzA0OH0.QiwUiaH4yTMFxbtvDfkJo8b5Mazmsv6Z4afQdgrvFw8','2026-08-20 12:50:48','2026-08-19 16:18:09');
/*!40000 ALTER TABLE `apacs_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `apacs_upload_log`
--

DROP TABLE IF EXISTS `apacs_upload_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apacs_upload_log` (
  `slip_no` varchar(30) NOT NULL,
  `status` enum('PENDING','SUCCESS','FAILED') NOT NULL DEFAULT 'PENDING',
  `request_data` longtext DEFAULT NULL,
  `response_data` longtext DEFAULT NULL,
  `uploaded_on` datetime DEFAULT NULL,
  `apacs_id` int(11) DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`slip_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apacs_upload_log`
--

LOCK TABLES `apacs_upload_log` WRITE;
/*!40000 ALTER TABLE `apacs_upload_log` DISABLE KEYS */;
INSERT INTO `apacs_upload_log` VALUES ('1','FAILED','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"1\",\"weighDate\":\"2026-08-18\",\"weighTime\":\"12:14:00\",\"vehicleNumber\":\"TN01TT7775\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"ORANGE COMPANY\",\"grossWeight\":20,\"tareWeight\":7,\"netWeight\":13,\"weightUnit\":\"kg\"}','{\"success\":false,\"message\":\"Weighbridge record with serial number \'1\' already exists\",\"data\":{\"id\":26,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"1\",\"weighDate\":\"2026-08-10\",\"weighTime\":\"18:02:00\",\"vehicleNumber\":\"DL49AB4532\",\"movementType\":\"import\",\"cargo\":\"CARGO1\",\"clientName\":\"RPP\",\"grossWeight\":\"20334.00\",\"tareWeight\":\"2334.00\",\"netWeight\":\"18000.00\",\"weightUnit\":\"kg\",\"operatorId\":5,\"createdAt\":\"2026-08-14T09:35:46.556Z\",\"updatedAt\":\"2026-08-14T09:35:46.556Z\"}}','2026-08-18 12:15:07',NULL,1),('2','SUCCESS','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"2\",\"weighDate\":\"2026-08-18\",\"weighTime\":\"13:08:00\",\"vehicleNumber\":\"TN01TT8888\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"ORANGE COMPANY\",\"grossWeight\":14,\"tareWeight\":1,\"netWeight\":13,\"weightUnit\":\"kg\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":33,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"2\",\"weighDate\":\"2026-08-18\",\"weighTime\":\"13:08:00\",\"vehicleNumber\":\"TN01TT8888\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"ORANGE COMPANY\",\"grossWeight\":\"14\",\"tareWeight\":\"1\",\"netWeight\":\"13\",\"weightUnit\":\"kg\",\"operatorId\":5,\"updatedAt\":\"2026-08-18T07:41:47.230Z\",\"createdAt\":\"2026-08-18T07:41:47.230Z\"}}','2026-08-18 13:09:07',33,0),('3','SUCCESS','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"3\",\"weighDate\":\"2026-08-18\",\"weighTime\":\"13:31:00\",\"vehicleNumber\":\"TN01TT6666\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"ORANGE COMPANY\",\"grossWeight\":8,\"tareWeight\":1,\"netWeight\":7,\"weightUnit\":\"kg\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":34,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"3\",\"weighDate\":\"2026-08-18\",\"weighTime\":\"13:31:00\",\"vehicleNumber\":\"TN01TT6666\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"ORANGE COMPANY\",\"grossWeight\":\"8\",\"tareWeight\":\"1\",\"netWeight\":\"7\",\"weightUnit\":\"kg\",\"operatorId\":5,\"updatedAt\":\"2026-08-18T08:04:32.659Z\",\"createdAt\":\"2026-08-18T08:04:32.659Z\"}}','2026-08-18 13:31:53',34,0),('4','FAILED','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"4\",\"weighDate\":\"2026-08-19\",\"weighTime\":\"16:18:00\",\"vehicleNumber\":\"TN99Z9999\",\"movementType\":\"import\",\"cargo\":\"\",\"clientName\":\"\",\"grossWeight\":16,\"tareWeight\":10,\"netWeight\":6,\"weightUnit\":\"kg\"}','{\"success\":false,\"message\":\"Missing required fields: cargo, clientName\"}','2026-08-19 16:18:09',NULL,1),('5','FAILED','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"5\",\"weighDate\":\"2026-08-19\",\"weighTime\":\"16:19:00\",\"vehicleNumber\":\"TN99Z3674\",\"movementType\":\"import\",\"cargo\":\"\",\"clientName\":\"\",\"grossWeight\":14,\"tareWeight\":10,\"netWeight\":4,\"weightUnit\":\"kg\"}','{\"success\":false,\"message\":\"Missing required fields: cargo, clientName\"}','2026-08-19 16:20:07',NULL,1),('6','SUCCESS','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"6\",\"weighDate\":\"2026-08-19\",\"weighTime\":\"16:26:00\",\"vehicleNumber\":\"TN01A1111\",\"movementType\":\"export\",\"cargo\":\"TWEST MARTYT\",\"clientName\":\"TESTPARTY N AMGHE NAEM\",\"grossWeight\":16,\"tareWeight\":10.1,\"netWeight\":5.9,\"weightUnit\":\"kg\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":35,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"6\",\"weighDate\":\"2026-08-19\",\"weighTime\":\"16:26:00\",\"vehicleNumber\":\"TN01A1111\",\"movementType\":\"export\",\"cargo\":\"TWEST MARTYT\",\"clientName\":\"TESTPARTY N AMGHE NAEM\",\"grossWeight\":\"16\",\"tareWeight\":\"10.1\",\"netWeight\":\"5.9\",\"weightUnit\":\"kg\",\"operatorId\":5,\"updatedAt\":\"2026-08-19T10:59:19.564Z\",\"createdAt\":\"2026-08-19T10:59:19.564Z\"}}','2026-08-19 16:26:40',35,0),('7','FAILED','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"7\",\"weighDate\":\"2026-08-20\",\"weighTime\":\"15:23:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"FANTA JUICE\",\"grossWeight\":1212,\"tareWeight\":1000,\"netWeight\":212,\"weightUnit\":\"kg\"}','{\"success\":false,\"message\":\"Weighbridge record with serial number \'7\' already exists\",\"data\":{\"id\":28,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"7\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"16:41:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"RED APPEL\",\"clientName\":\"FANTA JUICE\",\"grossWeight\":\"10000\",\"tareWeight\":\"1000\",\"netWeight\":\"9000\",\"weightUnit\":\"ton\",\"operatorId\":5,\"createdAt\":\"2026-08-14T11:14:43.109Z\",\"updatedAt\":\"2026-08-14T11:14:43.109Z\"}}','2026-08-20 15:23:59',NULL,1),('8','FAILED','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"8\",\"weighDate\":\"2026-08-20\",\"weighTime\":\"16:08:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"FANTA JUICE\",\"grossWeight\":1000,\"tareWeight\":100,\"netWeight\":900,\"weightUnit\":\"kg\"}','{\"success\":false,\"message\":\"Weighbridge record with serial number \'8\' already exists\",\"data\":{\"id\":29,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"8\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"17:07:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"RED APPEL\",\"clientName\":\"FANTA JUICE\",\"grossWeight\":\"100001\",\"tareWeight\":\"10000\",\"netWeight\":\"90001\",\"weightUnit\":\"ton\",\"operatorId\":5,\"createdAt\":\"2026-08-14T11:43:51.020Z\",\"updatedAt\":\"2026-08-14T11:43:51.020Z\"}}','2026-08-20 16:08:57',NULL,1);
/*!40000 ALTER TABLE `apacs_upload_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `company_address` text DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;
/*!40000 ALTER TABLE `company` DISABLE KEYS */;
INSERT INTO `company` VALUES (1,'B.L. TRANSPORT PVT.LTD','46/90, MOORE STREET, CHENNAI-600001','-','-','-');
/*!40000 ALTER TABLE `company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sweighment`
--

DROP TABLE IF EXISTS `sweighment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sweighment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slip_no` int(11) NOT NULL,
  `vehicle_no` varchar(30) DEFAULT NULL,
  `gross_weight` decimal(10,2) DEFAULT NULL,
  `gross_date` varchar(10) DEFAULT NULL,
  `gross_time` varchar(8) DEFAULT NULL,
  `tare_weight` decimal(10,2) DEFAULT NULL,
  `tare_date` varchar(10) DEFAULT NULL,
  `tare_time` varchar(8) DEFAULT NULL,
  `net_weight` decimal(10,2) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_image_path` varchar(255) DEFAULT NULL,
  `second_image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slip_no` (`slip_no`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sweighment`
--

LOCK TABLES `sweighment` WRITE;
/*!40000 ALTER TABLE `sweighment` DISABLE KEYS */;
INSERT INTO `sweighment` VALUES (1,1,'TN01TT7775',20.00,'18/08/2026','12:14:00',7.00,'18/08/2026','12:14:00',13.00,1,1,NULL,NULL),(2,2,'TN01TT8888',14.00,'18/08/2026','13:08:00',1.00,'18/08/2026','13:09:00',13.00,1,1,NULL,NULL),(3,3,'TN01TT6666',8.00,'18/08/2026','13:31:00',1.00,'18/08/2026','13:31:00',7.00,1,1,NULL,NULL),(4,4,'TN99Z9999',16.00,'19/08/2026','16:18:00',10.00,'19/08/2026','16:18:00',6.00,1,1,NULL,NULL),(5,5,'TN99Z3674',14.00,'19/08/2026','16:19:00',10.00,'19/08/2026','16:20:00',4.00,1,1,NULL,NULL),(6,6,'TN01A1111',16.00,'19/08/2026','16:26:00',10.10,'19/08/2026','16:26:00',5.90,1,1,NULL,NULL),(7,7,'TN01TT0101',1212.00,'20/08/2026','15:23:00',1000.00,'20/08/2026','15:23:00',212.00,1,1,NULL,NULL),(8,8,'TN01TT0101',1000.00,'20/08/2026','16:08:00',100.00,'20/08/2026','16:08:00',900.00,1,1,NULL,NULL);
/*!40000 ALTER TABLE `sweighment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'operator',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$6Z7Cs4fd66pw/JbV7sOYvOpMPm3JcEmJkcufqIgzlfMFIpaXmyPai','admin'),(2,'op1','$2y$10$JyT53ImrUUaQ79llXkXVWeetjD5mW9Oe9wD7FI8yct4qNdss2SSQW','user');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weighment_field_values`
--

DROP TABLE IF EXISTS `weighment_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weighment_field_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weighment_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighment_field_values`
--

LOCK TABLES `weighment_field_values` WRITE;
/*!40000 ALTER TABLE `weighment_field_values` DISABLE KEYS */;
INSERT INTO `weighment_field_values` VALUES (1,1,1,'EXPORT'),(2,1,2,'APPLE 7777'),(3,1,3,'ORANGE COMPANY'),(4,1,4,'KG'),(5,2,1,'EXPORT'),(6,2,2,'APPLE 7777'),(7,2,3,'ORANGE COMPANY'),(8,2,4,'KG'),(9,3,1,'EXPORT'),(10,3,2,'APPLE 7777'),(11,3,3,'ORANGE COMPANY'),(12,3,4,'KG'),(13,4,1,'IMPORT'),(14,4,2,'TWEST'),(15,4,3,'TESTPARTY'),(16,4,4,'KG'),(17,4,5,'TESTPARTY'),(18,4,6,'TESTPARTY'),(19,5,1,'IMPORT'),(20,5,2,'TWEST MART'),(21,5,3,'TESTPARTY N AME'),(22,5,4,'KG'),(23,5,5,'TES VESSLE'),(24,5,6,'VT TEST TES VT'),(25,6,1,'EXPORT'),(26,6,2,'TWEST MARTYT'),(27,6,3,'TESTPARTY N AMGHE NAEM'),(28,6,4,'KG'),(29,6,5,'TES VESSLE GYU'),(30,6,6,'VT TEST TES VTTRT'),(31,7,1,'EXPORT'),(32,7,2,'APPLE 7777'),(33,7,3,'FANTA JUICE'),(34,7,4,'KG'),(35,8,1,'EXPORT'),(36,8,2,'APPLE 7777'),(37,8,3,'FANTA JUICE'),(38,8,4,'KG'),(39,9,4,'KG');
/*!40000 ALTER TABLE `weighment_field_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weighment_fields`
--

DROP TABLE IF EXISTS `weighment_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weighment_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `field_label` varchar(100) NOT NULL,
  `field_type` enum('text','number','date','dropdown') NOT NULL,
  `field_values` varchar(255) DEFAULT NULL,
  `field_options` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `field_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighment_fields`
--

LOCK TABLES `weighment_fields` WRITE;
/*!40000 ALTER TABLE `weighment_fields` DISABLE KEYS */;
INSERT INTO `weighment_fields` VALUES (1,1,'MOVEMENT TYPE','MOVEMENT TYPE','dropdown','export , import','report',0,0,1),(2,1,'MATERIAL NAME','CARGO','text','','report',0,1,1),(3,1,'PARTY NAME','CLIENT NAME','text','','report',0,2,1),(4,1,'WEIGHT UNIT','WEIGHT UNIT','dropdown','KG, TON','report',0,3,1),(5,1,'VESSEL NAME','','text','','report',0,4,1),(6,1,'VT NO','','text','','report',0,5,1);
/*!40000 ALTER TABLE `weighment_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weighments`
--

DROP TABLE IF EXISTS `weighments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weighments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slip_no` int(11) NOT NULL,
  `vehicle_no` varchar(100) DEFAULT NULL,
  `first_weight` decimal(10,2) DEFAULT NULL,
  `first_date` varchar(30) DEFAULT NULL,
  `first_time` time DEFAULT NULL,
  `gt_type` enum('G','T') DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slip_no` (`slip_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighments`
--

LOCK TABLES `weighments` WRITE;
/*!40000 ALTER TABLE `weighments` DISABLE KEYS */;
INSERT INTO `weighments` VALUES (2,9,'44444444444444444444',0.00,'','00:00:00','G',1,1,NULL);
/*!40000 ALTER TABLE `weighments` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-24 13:22:51
