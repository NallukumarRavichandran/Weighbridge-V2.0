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
INSERT INTO `apacs_token` VALUES (1,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6NSwibG9naW5JZCI6IjQxMjYzMjUzIiwid2VpZ2hCcmlkZ2VOYW1lIjoiTS9zLkIuTC4gVHJhbnNwb3J0IFB2dC5MdGQiLCJpYXQiOjE3ODcwMzUzMzAsImV4cCI6MTc4NzEyMTczMH0.w6m1xAH5UoreZb_X7YLUEQs1D303QyXAuwWaLfoNdrA','2026-08-19 08:42:10','2026-08-18 12:09:30');
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
INSERT INTO `apacs_upload_log` VALUES ('1','SUCCESS','{\"weighBridgeName\":\"SEA GREEN\",\"serialNo\":\"1\",\"weighDate\":\"2026-08-10\",\"weighTime\":\"18:02:00\",\"vehicleNumber\":\"DL49AB4532\",\"movementType\":\"\",\"cargo\":\"\",\"clientName\":\"\",\"grossWeight\":20334,\"tareWeight\":2334,\"netWeight\":18000,\"weightUnit\":\"\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":26,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"1\",\"weighDate\":\"2026-08-10\",\"weighTime\":\"18:02:00\",\"vehicleNumber\":\"DL49AB4532\",\"movementType\":\"import\",\"cargo\":\"CARGO1\",\"clientName\":\"RPP\",\"grossWeight\":\"20334.00\",\"tareWeight\":\"2334.00\",\"netWeight\":\"18000.00\",\"weightUnit\":\"kg\",\"operatorId\":5,\"updatedAt\":\"2026-08-14T09:35:46.556Z\",\"createdAt\":\"2026-08-14T09:35:46.556Z\"}}','2026-08-14 15:03:00',26,1),('10','SUCCESS','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"10\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"18:19:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"APPLE 10\",\"clientName\":\"VIJAY KHAN 2\",\"grossWeight\":11000,\"tareWeight\":1000,\"netWeight\":10000,\"weightUnit\":\"kg\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":31,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"10\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"18:19:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"APPLE 10\",\"clientName\":\"VIJAY KHAN 2\",\"grossWeight\":\"11000\",\"tareWeight\":\"1000\",\"netWeight\":\"10000\",\"weightUnit\":\"kg\",\"operatorId\":5,\"updatedAt\":\"2026-08-14T12:53:14.878Z\",\"createdAt\":\"2026-08-14T12:53:14.878Z\"}}','2026-08-14 18:20:29',31,0),('11','SUCCESS','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"11\",\"weighDate\":\"2026-08-18\",\"weighTime\":\"12:08:00\",\"vehicleNumber\":\"TN01TT7777\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"ORANGE COMPANY\",\"grossWeight\":26,\"tareWeight\":7,\"netWeight\":19,\"weightUnit\":\"kg\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":32,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"11\",\"weighDate\":\"2026-08-18\",\"weighTime\":\"12:08:00\",\"vehicleNumber\":\"TN01TT7777\",\"movementType\":\"export\",\"cargo\":\"APPLE 7777\",\"clientName\":\"ORANGE COMPANY\",\"grossWeight\":\"26\",\"tareWeight\":\"7\",\"netWeight\":\"19\",\"weightUnit\":\"kg\",\"operatorId\":5,\"updatedAt\":\"2026-08-18T06:42:10.858Z\",\"createdAt\":\"2026-08-18T06:42:10.858Z\"}}','2026-08-18 12:09:30',32,0),('7','SUCCESS','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"7\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"16:41:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"RED APPEL\",\"clientName\":\"FANTA JUICE\",\"grossWeight\":10000,\"tareWeight\":1000,\"netWeight\":9000,\"weightUnit\":\"ton\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":28,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"7\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"16:41:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"RED APPEL\",\"clientName\":\"FANTA JUICE\",\"grossWeight\":\"10000\",\"tareWeight\":\"1000\",\"netWeight\":\"9000\",\"weightUnit\":\"ton\",\"operatorId\":5,\"updatedAt\":\"2026-08-14T11:14:43.109Z\",\"createdAt\":\"2026-08-14T11:14:43.109Z\"}}','2026-08-14 16:41:57',28,0),('8','SUCCESS','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"8\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"17:07:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"RED APPEL\",\"clientName\":\"FANTA JUICE\",\"grossWeight\":100001,\"tareWeight\":10000,\"netWeight\":90001,\"weightUnit\":\"ton\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":29,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"8\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"17:07:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"RED APPEL\",\"clientName\":\"FANTA JUICE\",\"grossWeight\":\"100001\",\"tareWeight\":\"10000\",\"netWeight\":\"90001\",\"weightUnit\":\"ton\",\"operatorId\":5,\"updatedAt\":\"2026-08-14T11:43:51.020Z\",\"createdAt\":\"2026-08-14T11:43:51.020Z\"}}','2026-08-14 17:11:05',29,0),('9','SUCCESS','{\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"9\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"18:18:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"APPLE 9\",\"clientName\":\"VIJAY KHAN\",\"grossWeight\":1000,\"tareWeight\":100,\"netWeight\":900,\"weightUnit\":\"kg\"}','{\"success\":true,\"message\":\"Weighbridge record saved successfully\",\"data\":{\"id\":30,\"weighBridgeName\":\"B.L. TRANSPORT PVT.LTD\",\"serialNo\":\"9\",\"weighDate\":\"2026-08-14\",\"weighTime\":\"18:18:00\",\"vehicleNumber\":\"TN01TT0101\",\"movementType\":\"export\",\"cargo\":\"APPLE 9\",\"clientName\":\"VIJAY KHAN\",\"grossWeight\":\"1000\",\"tareWeight\":\"100\",\"netWeight\":\"900\",\"weightUnit\":\"kg\",\"operatorId\":5,\"updatedAt\":\"2026-08-14T12:51:54.237Z\",\"createdAt\":\"2026-08-14T12:51:54.237Z\"}}','2026-08-14 18:19:08',30,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sweighment`
--

LOCK TABLES `sweighment` WRITE;
/*!40000 ALTER TABLE `sweighment` DISABLE KEYS */;
INSERT INTO `sweighment` VALUES (1,1,'DL49AB4532',20334.00,'10/08/2026','18:02:00',2334.00,'10/08/2026','18:01:00',18000.00,1,1,NULL,NULL),(2,3,'TN01TT0101',5000.00,'','',2000.00,'','',3000.00,1,1,NULL,NULL),(3,4,'TN01TT0101',5000.00,'14/08/2026','16:00:00',4000.00,'14/08/2026','16:00:00',1000.00,1,1,NULL,NULL),(4,5,'TN01TT0101',10000.00,'14/08/2026','16:37:00',1000.00,'14/08/2026','16:37:00',9000.00,1,1,NULL,NULL),(5,6,'TN01TT0101',10000.00,'','',1000.00,'','',9000.00,1,1,NULL,NULL),(6,7,'TN01TT0101',10000.00,'14/08/2026','16:41:00',1000.00,'14/08/2026','16:41:00',9000.00,1,1,NULL,NULL),(7,8,'TN01TT0101',100001.00,'14/08/2026','17:07:00',10000.00,'14/08/2026','17:08:00',90001.00,1,1,NULL,NULL),(8,9,'TN01TT0101',1000.00,'14/08/2026','18:18:00',100.00,'','',900.00,1,1,NULL,NULL),(9,10,'TN01TT0101',11000.00,'14/08/2026','18:19:00',1000.00,'14/08/2026','18:20:00',10000.00,1,1,NULL,NULL),(10,11,'TN01TT7777',26.00,'18/08/2026','12:08:00',7.00,'18/08/2026','12:09:00',19.00,1,1,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighment_field_values`
--

LOCK TABLES `weighment_field_values` WRITE;
/*!40000 ALTER TABLE `weighment_field_values` DISABLE KEYS */;
INSERT INTO `weighment_field_values` VALUES (1,1,1,'IMPORT'),(2,1,2,'CARGO1'),(3,1,3,'RPP'),(4,1,4,'KG'),(5,2,1,'EXPORT'),(6,2,2,'APPLE'),(7,2,3,'YAHOO'),(8,2,4,'TON'),(9,3,1,'EXPORT'),(10,3,2,'APPLE'),(11,3,3,'YAHOO'),(12,3,4,'TON'),(13,4,1,'IMPORT'),(14,4,2,'APPLE'),(15,4,3,'YAHOO'),(16,4,4,'TON'),(17,5,2,'RED APPEL'),(18,5,3,'FANTA JUICE'),(19,5,4,'TON'),(20,6,1,'EXPORT'),(21,6,2,'RED APPEL'),(22,6,3,'FANTA JUICE'),(23,6,4,'TON'),(24,7,1,'EXPORT'),(25,7,2,'RED APPEL'),(26,7,3,'FANTA JUICE'),(27,7,4,'TON'),(28,8,1,'EXPORT'),(29,8,2,'RED APPEL'),(30,8,3,'FANTA JUICE'),(31,8,4,'TON'),(32,9,1,'EXPORT'),(33,9,2,'APPLE 9'),(34,9,3,'VIJAY KHAN'),(35,9,4,'KG'),(36,10,1,'EXPORT'),(37,10,2,'APPLE 10'),(38,10,3,'VIJAY KHAN 2'),(39,10,4,'KG'),(40,11,1,'EXPORT'),(41,11,2,'APPLE 7777'),(42,11,3,'ORANGE COMPANY'),(43,11,4,'KG');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighment_fields`
--

LOCK TABLES `weighment_fields` WRITE;
/*!40000 ALTER TABLE `weighment_fields` DISABLE KEYS */;
INSERT INTO `weighment_fields` VALUES (1,1,'MOVEMENT TYPE','MOVEMENT TYPE','dropdown','export , import','report',0,0,1),(2,1,'CARGO','CARGO','text','','report',0,1,1),(3,1,'CLIENT NAME','CLIENT NAME','text','','report',0,2,1),(4,1,'WEIGHT UNIT','WEIGHT UNIT','dropdown','KG, TON','report',0,3,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighments`
--

LOCK TABLES `weighments` WRITE;
/*!40000 ALTER TABLE `weighments` DISABLE KEYS */;
INSERT INTO `weighments` VALUES (1,2,'TN01AB1235',0.00,'','00:00:00','G',1,1,NULL);
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

-- Dump completed on 2026-08-18 12:12:39
