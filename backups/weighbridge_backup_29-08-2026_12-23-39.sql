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
INSERT INTO `company` VALUES (1,'TEST HI WP Y9EFYHPQE3FRASF','TEST CHENNAI-600001','-','-','-');
/*!40000 ALTER TABLE `company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `print_field_config`
--

DROP TABLE IF EXISTS `print_field_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `print_field_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_key` varchar(100) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `custom_label` varchar(255) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_key` (`field_key`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `print_field_config`
--

LOCK TABLES `print_field_config` WRITE;
/*!40000 ALTER TABLE `print_field_config` DISABLE KEYS */;
INSERT INTO `print_field_config` VALUES (1,'slip_no',1,'Slip No','2026-08-28 15:25:16'),(2,'vehicle_no',1,'Vehicle No','2026-08-28 15:25:16'),(3,'material_name',1,'Material Name','2026-08-28 15:26:23'),(4,'party_name',1,'Party Name','2026-08-28 15:25:16'),(5,'vessel_name',0,'Vessel Name','2026-08-28 15:25:16'),(6,'vt_no',0,'Vt No','2026-08-28 15:25:16'),(7,'movement_type',1,'Movement Type','2026-08-28 15:25:16'),(8,'driver_name',1,'Driver Name','2026-08-28 15:25:16'),(9,'driver_no',1,'DRIVER NO','2026-08-28 15:25:16'),(10,'sap_trans',0,'Sap Trans','2026-08-28 15:25:16'),(11,'cctv_images',1,'CCTV Photos','2026-08-28 16:23:55'),(12,'signature_block',1,'Operator\'s Signature','2026-08-28 15:25:16');
/*!40000 ALTER TABLE `print_field_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `print_format_settings`
--

DROP TABLE IF EXISTS `print_format_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `print_format_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL DEFAULT 'ALL',
  `format_key` varchar(100) NOT NULL DEFAULT 'format_standard_a4_portrait',
  `name` varchar(255) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `template_code` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_name` (`company_name`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `print_format_settings`
--

LOCK TABLES `print_format_settings` WRITE;
/*!40000 ALTER TABLE `print_format_settings` DISABLE KEYS */;
INSERT INTO `print_format_settings` VALUES (1,'ALL','format_dual_landscape_cctv','Triplicate Copy Landscape A4 Slip','2026-08-29 12:20:54','<!-- ============================================================\r\n     LAYOUT 4: TRIPLICATE LANDSCAPE 3-IN-1 VIEW\r\n     ============================================================ -->\r\n<style>\r\n@page { size: A4 landscape; margin: 0; }\r\n* { margin: 0; padding: 0; box-sizing: border-box; }\r\nbody { font-family: \'Courier New\', monospace; background: #fff; color: #000; padding: 4mm 5mm; }\r\n.triplicate-wrapper { display: flex; justify-content: space-between; width: 100%; height: 196mm; gap: 4mm; }\r\n.slip-third { width: 32.2%; height: 196mm; border: 1.5px solid #000; padding: 6px 8px; background: #fff; display: flex; flex-direction: column; justify-content: space-between; }\r\n.copy-badge { text-align: center; font-size: 10px; font-weight: bold; background: #000; color: #fff; padding: 2px 0; margin-bottom: 4px; letter-spacing: 1px; }\r\n.company-header { text-align: center; min-height: 45px; position: relative; }\r\n.company-logo { position: absolute; left: 0; top: 0; width: 35px; height: 35px; object-fit: contain; }\r\n.company-header h1 { font-size: 13.5px; font-weight: bold; letter-spacing: 1px; margin-left: 30px; }\r\n.company-header h2 { font-size: 11px; font-weight: bold; letter-spacing: 2px; margin-left: 30px; }\r\n.company-header .sub { font-size: 8px; margin-top: 1px; margin-left: 30px; }\r\n.info-grid { font-size: 9.5px; font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; margin: 3px 0; }\r\n.info-grid .item { display: flex; justify-content: space-between; padding: 1.5px 0; }\r\n.weights-table { width: 100%; border-collapse: collapse; margin: 3px 0; font-size: 9px; }\r\n.weights-table th, .weights-table td { border: 1px solid #000; padding: 3px 1px; text-align: center; }\r\n.weights-table th { background: #f1f5f9; font-weight: bold; }\r\n.certify-row { display: flex; justify-content: space-between; font-size: 9.5px; font-weight: bold; padding-top: 4px; border-top: 1px solid #000; }\r\n.signature-section { text-align: right; font-size: 9.5px; font-weight: bold; margin-top: 8px; }\r\n</style>\r\n\r\n<?php $copyTitles = [\'CUSTOMER COPY\', \'TRANSPORTER COPY\', \'GATE / SECURITY COPY\']; ?>\r\n<div class=\"triplicate-wrapper\">\r\n    <?php for ($c = 0; $c < 3; $c++): ?>\r\n    <div class=\"slip-third\">\r\n        <div class=\"copy-badge\"><?= $copyTitles[$c] ?></div>\r\n        <div class=\"company-header\">\r\n            <?php if (!empty($logoPath)): ?><img src=\"<?= $logoPath ?>?v=<?= time() ?>\" class=\"company-logo\" alt=\"Logo\"><?php endif; ?>\r\n            <h1><?= strtoupper(htmlspecialchars($company[\'company_name\'] ?? \'\')) ?></h1>\r\n            <h2>WEIGHMENT SLIP</h2>\r\n            <div class=\"sub\"><?= strtoupper(htmlspecialchars($company[\'company_address\'] ?? \'\')) ?></div>\r\n        </div>\r\n\r\n        <div class=\"info-grid\">\r\n            <?php if (showPrintField(\'slip_no\')): ?><div class=\"item\"><span>Slip No:</span><span><?= $row[\'slip_no\'] ?></span></div><?php endif; ?>\r\n            <?php if (showPrintField(\'vehicle_no\')): ?><div class=\"item\"><span>Vehicle:</span><span><?= htmlspecialchars($row[\'vehicle_no\']) ?></span></div><?php endif; ?>\r\n            <?php if (showPrintField(\'material_name\')): ?><div class=\"item\"><span>Material:</span><span><?= htmlspecialchars($material_name) ?></span></div><?php endif; ?>\r\n            <?php if (showPrintField(\'party_name\')): ?><div class=\"item\"><span>Party:</span><span><?= htmlspecialchars($party_name) ?></span></div><?php endif; ?>\r\n        </div>\r\n\r\n        <?php if ($isFinal): ?>\r\n        <table class=\"weights-table\">\r\n            <thead><tr><th>Gross</th><th>Tare</th><th>Net</th></tr></thead>\r\n            <tbody><tr><td><?= number_format($disp_gross, 2) ?></td><td><?= number_format($disp_tare, 2) ?></td><td><b><?= number_format($disp_net, 2) ?></b></td></tr></tbody>\r\n        </table>\r\n        <div style=\"font-size:8.5px;margin:2px 0;\">Date: <?= $row[\'gross_date\'] ?? \'\' ?> <?= substr($row[\'gross_time\'] ?? \'\', 0, 5) ?></div>\r\n        <?php else: ?>\r\n        <table class=\"weights-table\">\r\n            <thead><tr><th><?= ($row[\'gt_type\']==\'G\')?\'Gross\':\'Tare\' ?> Wt</th><th>Date & Time</th></tr></thead>\r\n            <tbody><tr><td><?= number_format($disp_first, 2) ?></td><td><?= formatDateTime($row[\'first_date\']??\'\', $row[\'first_time\']??\'\') ?></td></tr></tbody>\r\n        </table>\r\n        <?php endif; ?>\r\n\r\n        <?php if (showPrintField(\'signature_block\')): ?>\r\n        <div class=\"certify-row\"><div>Authorized Signatory</div><div>Driver Sign</div></div>\r\n        <?php endif; ?>\r\n    </div>\r\n    <?php endfor; ?>\r\n</div>'),(46,'ACTIVE_CHOICE','format_dual_landscape_cctv','','2026-08-29 12:01:28',NULL);
/*!40000 ALTER TABLE `print_format_settings` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sweighment`
--

LOCK TABLES `sweighment` WRITE;
/*!40000 ALTER TABLE `sweighment` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weighment_field_values`
--

LOCK TABLES `weighment_field_values` WRITE;
/*!40000 ALTER TABLE `weighment_field_values` DISABLE KEYS */;
INSERT INTO `weighment_field_values` VALUES (1,1,1,'EXPORT'),(2,1,2,'APPLE 7777'),(3,1,3,'FANTA JUICE'),(4,1,4,'KG');
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
INSERT INTO `weighment_fields` VALUES (1,1,'MOVEMENT TYPE','MOVEMENT TYPE','dropdown','export , import','report',0,0,1),(2,1,'MATERIAL NAME','CARGO','text','','report',0,1,1),(3,1,'PARTY NAME','CLIENT NAME','text','','report',0,2,1),(4,1,'WEIGHT UNIT','WEIGHT UNIT','dropdown','KG, TON','report',0,3,1),(5,1,'VESSEL NAME','','text','','report',0,4,0),(6,1,'VT NO','','text','','report',0,5,0);
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
INSERT INTO `weighments` VALUES (1,1,'TN01TT0101',0.00,'','00:00:00','G',1,1,NULL);
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

-- Dump completed on 2026-08-29 12:23:39
