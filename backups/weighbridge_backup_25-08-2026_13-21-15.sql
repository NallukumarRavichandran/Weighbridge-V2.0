-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: weighbridge_db
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
-- Table structure for table `camera_settings`
--

DROP TABLE IF EXISTS `camera_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `camera_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deviceid` varchar(50) DEFAULT NULL,
  `cam_num` int(11) DEFAULT NULL,
  `cam_name` varchar(100) DEFAULT NULL,
  `cam_url` varchar(255) DEFAULT NULL,
  `cam_user` varchar(100) DEFAULT NULL,
  `cam_pass` varchar(100) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `camera_settings`
--

LOCK TABLES `camera_settings` WRITE;
/*!40000 ALTER TABLE `camera_settings` DISABLE KEYS */;
INSERT INTO `camera_settings` VALUES (1,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(2,'wb1234',2,'CAMERA 2','','','',0),(3,'wb1234',3,'CAMERA 3','','','',0),(4,'wb1234',4,'CAMERA 4','','','',0),(5,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(6,'wb1234',2,'CAMERA 2','','','',0),(7,'wb1234',3,'CAMERA 3','','','',0),(8,'wb1234',4,'CAMERA 4','','','',0),(9,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(10,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(11,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(12,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(13,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(14,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(15,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(16,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(17,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(18,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(19,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(20,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(21,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(22,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(23,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(24,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(25,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(26,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(27,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(28,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(29,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(30,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(31,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(32,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(33,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(34,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(35,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(36,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(37,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(38,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(39,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(40,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(41,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(42,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(43,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(44,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(45,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(46,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(47,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(48,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(49,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(50,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(51,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(52,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(53,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(54,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(55,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(56,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(57,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(58,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(59,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(60,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(61,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(62,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(63,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(64,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(65,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(66,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(67,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(68,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(69,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(70,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(71,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(72,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(73,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(74,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(75,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(76,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(77,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(78,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(79,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(80,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(81,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(82,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(83,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(84,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(85,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(86,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(87,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(88,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(89,'wb1234',1,'CAMERA 1','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1),(90,'wb1234',2,'CAMERA 2','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(91,'wb1234',3,'CAMERA 3','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',0),(92,'wb1234',4,'CAMERA 4','http://192.168.1.11/cgi-bin/mjpg/video.cgi?channel=1&subtype=1','admin','GiRi_1973',1);
/*!40000 ALTER TABLE `camera_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company` (
  `deviceid` varchar(50) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_address` text DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`deviceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;
/*!40000 ALTER TABLE `company` DISABLE KEYS */;
INSERT INTO `company` VALUES ('wb1234','Test Weighbridge Hiasdfasdf','test address 12345asdfasdfasdf',' 1234567890',' 1234567890',' 1234testwb@gmail.com');
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
  `vehicle_no` varchar(20) DEFAULT NULL,
  `gross_weight` int(11) DEFAULT NULL,
  `gross_date` varchar(20) DEFAULT NULL,
  `gross_time` varchar(20) DEFAULT NULL,
  `tare_weight` int(11) DEFAULT NULL,
  `tare_date` varchar(20) DEFAULT NULL,
  `tare_time` varchar(20) DEFAULT NULL,
  `net_weight` int(11) DEFAULT NULL,
  `deviceid` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_image_path` varchar(255) DEFAULT NULL,
  `second_image_path` varchar(255) DEFAULT NULL,
  `company_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_sweigh_device` (`deviceid`),
  CONSTRAINT `fk_sweigh_device` FOREIGN KEY (`deviceid`) REFERENCES `company` (`deviceid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sweighment`
--

LOCK TABLES `sweighment` WRITE;
/*!40000 ALTER TABLE `sweighment` DISABLE KEYS */;
INSERT INTO `sweighment` VALUES (1,1001,'TN-01-AB-1234',25000,'2026-08-11','18:40:08',10000,'2026-08-11','18:40:08',15000,'wb1234',0,NULL,NULL,1),(2,1005,'TN-01-AB-1005',0,'2026-08-12','16',0,'2026-08-12','12:30:25',0,'wb1234',0,'uploads/cam_1005_first_1786530625.jpg','uploads/cam_1005_second_1786532725.jpg',1);
/*!40000 ALTER TABLE `sweighment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_log`
--

DROP TABLE IF EXISTS `sync_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) DEFAULT NULL,
  `deviceid` varchar(50) NOT NULL,
  `sync_status` tinyint(4) DEFAULT 0,
  `synced_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sync_device` (`deviceid`),
  CONSTRAINT `fk_sync_device` FOREIGN KEY (`deviceid`) REFERENCES `company` (`deviceid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_log`
--

LOCK TABLES `sync_log` WRITE;
/*!40000 ALTER TABLE `sync_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sync_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `deviceid` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_device` (`username`,`deviceid`),
  KEY `fk_user_device` (`deviceid`),
  CONSTRAINT `fk_user_device` FOREIGN KEY (`deviceid`) REFERENCES `company` (`deviceid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'testadmin','1234567890','ADMIN','wb1234'),(2,'testop','$2y$10$p9cQHtILsbrguqBx/YcAnebyoU7JGpNAnWjfP4q/V6vF8zegznFgO','user','wb1234'),(3,'testwbadmin','1234567890','admin','wb1234'),(4,'testop2','$2y$10$aYn41mzbyW3B7hwurDjJFO.tF79fKUTqA8V0jthszlVTpJ2eW6WPS','user','wb1234'),(5,'testop3','$2y$10$c1P6XmNI.yuGNMuoTfl/eeJWGObki1vMIBJpI16/n2chDG1XbJPOq','user','wb1234'),(6,'testop4','$2y$10$/DNggALcOCLoYOPVtEKdgemDUS5doVUgYxA/9r6h.1HElIHElwv/6','user','wb1234'),(7,'testopera1','$2y$10$cSO5oW5cp/41QdUxU1ae4O6UHBCujyWCrCkIV0axrEZ5cpDX3CLfO','user','wb1234'),(8,'testopera2','$2y$10$R6UpkaHyH8e4Ot4dczXfgeNtEwRb/moQ3WfsVA7f18jdI5mF54Uem','user','wb1234');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wb_dynamic_field_values`
--

DROP TABLE IF EXISTS `wb_dynamic_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_dynamic_field_values` (
  `value_id` int(11) NOT NULL AUTO_INCREMENT,
  `slip_no` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL,
  PRIMARY KEY (`value_id`),
  KEY `field_id` (`field_id`),
  CONSTRAINT `wb_dynamic_field_values_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `wb_dynamic_fields` (`field_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wb_dynamic_field_values`
--

LOCK TABLES `wb_dynamic_field_values` WRITE;
/*!40000 ALTER TABLE `wb_dynamic_field_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `wb_dynamic_field_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wb_dynamic_fields`
--

DROP TABLE IF EXISTS `wb_dynamic_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_dynamic_fields` (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `deviceid` varchar(50) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `field_label` varchar(100) NOT NULL,
  `field_type` enum('text','number','date','dropdown') DEFAULT 'text',
  `field_options` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `field_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`field_id`),
  KEY `deviceid` (`deviceid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wb_dynamic_fields`
--

LOCK TABLES `wb_dynamic_fields` WRITE;
/*!40000 ALTER TABLE `wb_dynamic_fields` DISABLE KEYS */;
INSERT INTO `wb_dynamic_fields` VALUES (1,'wb1234','','movementType','dropdown','EXPORT,IMPORT',0,5,1,'2026-08-11 05:57:20'),(2,'wb1234','','cargo','text','report',0,6,1,'2026-08-11 05:57:20'),(3,'wb1234','','client / party name','text','report',0,7,1,'2026-08-11 05:57:20'),(4,'wb1234','','weightUnit','dropdown','KG,TON',0,8,1,'2026-08-11 05:57:20');
/*!40000 ALTER TABLE `wb_dynamic_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wb_master`
--

DROP TABLE IF EXISTS `wb_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` varchar(50) NOT NULL,
  `company_id` varchar(50) DEFAULT '',
  `company` varchar(255) DEFAULT '',
  `site_name` varchar(255) DEFAULT '',
  `admin_user` varchar(100) DEFAULT '',
  `admin_password` varchar(255) DEFAULT '',
  `head1` varchar(255) DEFAULT '',
  `head2` varchar(255) DEFAULT '',
  `head3` varchar(255) DEFAULT '',
  `head4` varchar(255) DEFAULT '',
  `head5` varchar(255) DEFAULT '',
  `head6` varchar(255) DEFAULT '',
  `tail1` varchar(255) DEFAULT '',
  `tail2` varchar(255) DEFAULT '',
  `tail3` varchar(255) DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wb_master`
--

LOCK TABLES `wb_master` WRITE;
/*!40000 ALTER TABLE `wb_master` DISABLE KEYS */;
INSERT INTO `wb_master` VALUES (1,'wb1234','','Test Weighbridge Hiasdfasdf','','','','Test Weighbridge Hiasdfasdf','test address 12345asdfasdfasdf',' 1234567890',' 1234567890',' 1234testwb@gmail.com','','','','','2026-08-19 04:37:43');
/*!40000 ALTER TABLE `wb_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weighment_field_values`
--

DROP TABLE IF EXISTS `weighment_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weighment_field_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weighment_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `field_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighment_field_values`
--

LOCK TABLES `weighment_field_values` WRITE;
/*!40000 ALTER TABLE `weighment_field_values` DISABLE KEYS */;
INSERT INTO `weighment_field_values` VALUES (1,1002,1,'EXPORT'),(2,1002,4,'KG'),(3,1003,1,'EXPORT'),(4,1003,4,'KG'),(5,1004,1,'EXPORT'),(6,1004,4,'KG'),(7,1005,1,'EXPORT'),(8,1005,4,'TON'),(9,1005,1,'IMPORT'),(10,1005,4,'TON'),(11,1006,1,'IMPORT'),(12,1006,4,'KG'),(13,1007,1,'IMPORT'),(14,1007,4,'TON');
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
  `company_id` int(11) DEFAULT NULL,
  `deviceid` varchar(50) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `field_label` varchar(100) DEFAULT NULL,
  `field_type` varchar(20) DEFAULT NULL,
  `field_options` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `field_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_fields_dev` (`deviceid`),
  CONSTRAINT `fk_fields_dev` FOREIGN KEY (`deviceid`) REFERENCES `company` (`deviceid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighment_fields`
--

LOCK TABLES `weighment_fields` WRITE;
/*!40000 ALTER TABLE `weighment_fields` DISABLE KEYS */;
INSERT INTO `weighment_fields` VALUES (1,1,'wb1234','LOADING PLACE','LOADING PLACE','text','report',0,0,1),(2,1,'wb1234','AGENT NAME','AGENT NAME','text','report',0,1,1),(3,1,'wb1234','MATERIAL NAME','MATERIAL NAME','text','report',0,2,1),(4,1,'wb1234','BILL NO','BILL NO','text','',0,3,1),(5,1,'wb1234','CHARGES','CHARGES','number','report,total',0,4,1),(6,1,'wb1234','movementType','movementType','dropdown','export,import',0,5,1),(7,1,'wb1234','cargo','cargo','text','report',0,6,1),(8,1,'wb1234','clientName','client / party name','text','report',0,7,1),(9,1,'wb1234','weightUnit','weightUnit','dropdown','kg,ton',0,8,1);
/*!40000 ALTER TABLE `weighment_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weighments`
--

DROP TABLE IF EXISTS `weighments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weighments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slip_no` int(11) NOT NULL,
  `vehicle_no` varchar(20) DEFAULT NULL,
  `first_weight` int(11) DEFAULT NULL,
  `first_date` varchar(20) DEFAULT NULL,
  `first_time` varchar(20) DEFAULT NULL,
  `gt_type` char(1) DEFAULT NULL,
  `deviceid` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_image_path` varchar(255) DEFAULT '',
  `company_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_weigh_device` (`deviceid`),
  CONSTRAINT `fk_weigh_device` FOREIGN KEY (`deviceid`) REFERENCES `company` (`deviceid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighments`
--

LOCK TABLES `weighments` WRITE;
/*!40000 ALTER TABLE `weighments` DISABLE KEYS */;
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

-- Dump completed on 2026-08-25 16:51:15
