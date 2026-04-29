/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.2.2-MariaDB, for osx10.21 (arm64)
--
-- Host: localhost    Database: etherr_assistant
-- ------------------------------------------------------
-- Server version	12.2.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `etherr_ai_conversations`
--

DROP TABLE IF EXISTS `etherr_ai_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `etherr_ai_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_uuid` char(36) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `locale` varchar(8) NOT NULL DEFAULT 'hr',
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session_uuid` (`session_uuid`),
  KEY `status` (`status`),
  KEY `started_at` (`started_at`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_conversations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_conversations` WRITE;
/*!40000 ALTER TABLE `etherr_ai_conversations` DISABLE KEYS */;
INSERT INTO `etherr_ai_conversations` VALUES
(67,'e8e8322d-e61a-4dd5-bb82-5efd2cc0c94e','active','hr','2026-04-29 20:01:45',NULL,'2026-04-29 20:01:45','2026-04-29 20:01:45');
/*!40000 ALTER TABLE `etherr_ai_conversations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `etherr_ai_intakes`
--

DROP TABLE IF EXISTS `etherr_ai_intakes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `etherr_ai_intakes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `locale` varchar(8) NOT NULL DEFAULT 'hr',
  `state_json` longtext NOT NULL,
  `request_id` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_id` (`conversation_id`),
  KEY `status` (`status`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_intakes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_intakes` WRITE;
/*!40000 ALTER TABLE `etherr_ai_intakes` DISABLE KEYS */;
/*!40000 ALTER TABLE `etherr_ai_intakes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `etherr_ai_logs`
--

DROP TABLE IF EXISTS `etherr_ai_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `etherr_ai_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'info',
  `payload_json` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `severity` (`severity`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_logs` WRITE;
/*!40000 ALTER TABLE `etherr_ai_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `etherr_ai_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `etherr_ai_messages`
--

DROP TABLE IF EXISTS `etherr_ai_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `etherr_ai_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `role` varchar(32) NOT NULL,
  `message_text` longtext NOT NULL,
  `token_estimate` int(11) DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `role` (`role`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=185 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_messages` WRITE;
/*!40000 ALTER TABLE `etherr_ai_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `etherr_ai_messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `etherr_ai_sessions`
--

DROP TABLE IF EXISTS `etherr_ai_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `etherr_ai_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_uuid` char(36) NOT NULL,
  `current_conversation_id` bigint(20) unsigned DEFAULT NULL,
  `client_ip_hash` char(64) DEFAULT NULL,
  `user_agent_hash` char(64) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_uuid` (`session_uuid`),
  KEY `is_active` (`is_active`),
  KEY `last_activity_at` (`last_activity_at`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_sessions` WRITE;
/*!40000 ALTER TABLE `etherr_ai_sessions` DISABLE KEYS */;
INSERT INTO `etherr_ai_sessions` VALUES
(1,'113c3312-17f5-40ec-8aec-f1ba970d82bf',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','8f42af6f304383bbe22bc11b9f1c9ce77bb37478b15b0b0d93224e249f36e68e',1,'2026-04-24 17:34:39','2026-04-24 17:31:15','2026-04-26 19:24:13'),
(2,'26b0b6a1-6e24-4b19-b010-42747394c87a',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-24 17:35:08','2026-04-24 17:32:25','2026-04-26 19:24:13'),
(3,'84557592-a1f0-4962-ab54-5cf69fff6a6d',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-24 18:05:26','2026-04-24 17:35:21','2026-04-26 19:24:13'),
(4,'af674438-88c3-4ec3-a16c-0316e570428e',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-24 18:12:12','2026-04-24 18:06:17','2026-04-26 19:24:13'),
(5,'d187dc1b-8762-4157-bf84-421f6a3f9a18',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-25 08:33:03','2026-04-25 08:10:42','2026-04-26 19:24:13'),
(6,'f061f71f-000d-4274-98e9-fbce552965c5',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-25 08:39:18','2026-04-25 08:39:18','2026-04-26 19:24:13'),
(7,'2a18705b-3ddc-461c-b3b5-fc66545f0c94',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-25 08:40:25','2026-04-25 08:40:25','2026-04-26 19:24:13'),
(8,'2e83cc69-cf44-4ec0-9163-5d6b26a4ca8f',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-25 08:48:21','2026-04-25 08:48:21','2026-04-26 19:24:13'),
(9,'c715e90d-e151-483d-a981-af1a79233c46',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-25 08:53:55','2026-04-25 08:53:55','2026-04-26 19:24:13'),
(10,'c461d7bd-cd46-43fa-9e2d-2a2b3df540cd',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-25 09:06:47','2026-04-25 08:57:05','2026-04-26 19:24:13'),
(11,'f5900f50-79dc-479e-8d1b-40754b233fe5',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-25 09:15:43','2026-04-25 09:08:59','2026-04-26 19:24:13'),
(12,'9a5bd6e2-2a47-42e8-a7d6-e20a9ddb271f',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-25 09:17:57','2026-04-25 09:16:11','2026-04-26 19:24:13'),
(13,'f2e7da28-df54-4e4b-b122-f9a1ddf65a1a',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-26 17:56:53','2026-04-26 17:56:05','2026-04-26 19:24:13'),
(14,'5b9edf3a-5db0-44ed-ba53-b777f23b1b45',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-26 18:32:28','2026-04-26 18:20:11','2026-04-26 19:24:13'),
(15,'17f51651-4f4c-4f80-8c27-1336ff387740',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-26 18:33:39','2026-04-26 18:32:39','2026-04-26 19:24:13'),
(16,'7ac51c07-2747-4c3d-9d11-9a6ce08d61b5',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-26 18:34:43','2026-04-26 18:34:00','2026-04-26 19:24:13'),
(17,'0d2b24b0-adf0-41dd-9bd3-31356301074d',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-26 19:07:58','2026-04-26 19:07:43','2026-04-26 19:24:13'),
(18,'40ff7973-d859-4c18-b3fc-d0c9bb919082',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-26 19:15:27','2026-04-26 19:09:34','2026-04-26 19:24:13'),
(19,'b2dfe20d-f65d-45e3-8543-1270c2998c68',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-26 19:19:56','2026-04-26 19:19:03','2026-04-26 19:24:13'),
(20,'76def822-94c4-4c35-ac43-a7cdeca9f74d',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-26 19:34:04','2026-04-26 19:27:15','2026-04-26 21:59:38'),
(21,'1663b48f-54f4-43a9-b9b0-013cf40e6979',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-26 19:38:31','2026-04-26 19:36:13','2026-04-26 21:59:38'),
(22,'8479bf67-582e-4ca2-92e5-2d88f7eaaec3',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','8f42af6f304383bbe22bc11b9f1c9ce77bb37478b15b0b0d93224e249f36e68e',1,'2026-04-27 10:12:57','2026-04-27 10:12:57','2026-04-29 19:59:10'),
(23,'64522cb6-2a19-4cbf-9d48-ac2cefb6310a',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 13:29:51','2026-04-28 06:41:54','2026-04-29 19:59:10'),
(24,'049a8c26-d605-4fe0-bfdb-0173d6bf7aa3',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:10:13','2026-04-28 11:10:13','2026-04-29 19:59:10'),
(25,'a76d96df-ed9a-45ca-9253-55e5c6876466',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:21:53','2026-04-28 11:21:53','2026-04-29 19:59:10'),
(26,'6138679a-5084-42b9-81b1-1c599794b9e2',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:22:34','2026-04-28 11:22:11','2026-04-29 19:59:10'),
(27,'25f615f2-a082-40a1-b1ac-990352ccb2fd',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:23:08','2026-04-28 11:23:08','2026-04-29 19:59:10'),
(28,'a0b74c44-6024-4218-915c-858b55f733d3',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 11:24:52','2026-04-28 11:23:38','2026-04-29 19:59:10'),
(29,'27dbca78-3823-4e30-b6e3-4c37f25feeca',NULL,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:23:53','2026-04-28 11:23:53','2026-04-29 19:59:10'),
(30,'8db5d3cc-0c6b-4763-8594-920748e5cedb',NULL,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:23:53','2026-04-28 11:23:53','2026-04-29 19:59:10'),
(31,'ce06df64-e114-4262-972a-c9cd0ac472f4',NULL,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:23:53','2026-04-28 11:23:53','2026-04-29 19:59:10'),
(32,'b38c3426-1878-4df9-9701-6dcc95a73dfc',NULL,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:24:29','2026-04-28 11:24:29','2026-04-29 19:59:10'),
(33,'0406a6e0-c929-4b07-907f-6c951ac8e09b',NULL,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:24:29','2026-04-28 11:24:29','2026-04-29 19:59:10'),
(34,'e8f77c32-fd03-437a-bfc1-8fb528b2d6ce',NULL,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:24:29','2026-04-28 11:24:29','2026-04-29 19:59:10'),
(35,'0f862abc-9699-4ece-8be8-f567a0050ed0',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 11:28:12','2026-04-28 11:27:14','2026-04-29 19:59:10'),
(36,'59d3bf25-e2b9-4ed7-aa37-a0caa6afe650',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:29:52','2026-04-28 11:29:52','2026-04-29 19:59:10'),
(37,'b56bb2e6-1af8-4971-8b5f-7e639152b4d3',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:45:44','2026-04-28 11:45:44','2026-04-29 19:59:10'),
(38,'16e882a3-96f8-417a-bb7b-99fd14efb94a',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:48:26','2026-04-28 11:48:26','2026-04-29 19:59:10'),
(39,'3815a5e5-9043-4aa2-8da3-a1f663e00d1a',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 12:05:45','2026-04-28 12:05:45','2026-04-29 19:59:10'),
(40,'5efb0c4b-e992-446e-8d66-0c881601971b',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 12:31:30','2026-04-28 12:18:28','2026-04-29 19:59:10'),
(41,'04270281-a5ba-4dc2-9b1e-6f1e3a6545e4',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 15:30:09','2026-04-28 15:30:09','2026-04-29 19:59:10'),
(42,'a690b6c8-433e-454e-a926-89ccf351f462',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 15:35:47','2026-04-28 15:35:46','2026-04-29 19:59:10'),
(43,'4d9a5b13-95b0-4ead-a13e-70730d8ffc28',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 15:59:39','2026-04-28 15:59:39','2026-04-29 19:59:10'),
(44,'1877ab5a-9028-4721-97c9-e6b19d211d67',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 16:47:28','2026-04-28 16:47:28','2026-04-29 19:59:10'),
(45,'039495a1-f986-4e88-94e4-a00fa19e96dc',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:03:41','2026-04-29 08:00:42','2026-04-29 19:59:10'),
(46,'19f12c68-8f04-47b2-b46d-c2d9316f38d6',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:04:22','2026-04-29 08:04:00','2026-04-29 19:59:10'),
(47,'cb6dbae7-2a00-4efd-8d08-17e987dfdd96',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:05:06','2026-04-29 08:04:41','2026-04-29 19:59:10'),
(48,'7d29ec7a-a16b-4a92-bf83-a0b85c7e612f',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:14:27','2026-04-29 08:11:30','2026-04-29 19:59:10'),
(49,'501b43b9-e39b-41a6-b1bd-d667a816afc5',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:17:51','2026-04-29 08:17:08','2026-04-29 19:59:10'),
(50,'4e122e92-f5d6-4618-b6a3-bcf5bced6153',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:20:34','2026-04-29 08:18:48','2026-04-29 19:59:10'),
(51,'aa86ac1b-8da3-4a8d-81aa-335b441a2824',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:20:54','2026-04-29 08:20:50','2026-04-29 19:59:10'),
(52,'dd4e8fc5-535e-45e1-8a27-39a5cce43b8b',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:21:49','2026-04-29 08:21:45','2026-04-29 19:59:10'),
(53,'ab4deecc-08ce-4894-9cc6-94b6fd535334',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:22:41','2026-04-29 08:22:27','2026-04-29 19:59:10'),
(54,'86bf7410-1f98-455b-8c1d-de29658894fb',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 08:24:36','2026-04-29 08:23:20','2026-04-29 19:59:10'),
(55,'7b9a6678-698d-4fab-964e-65dee8a0eb61',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 18:33:08','2026-04-29 18:21:11','2026-04-29 19:59:10'),
(56,'8aff9012-0d23-4a62-86fd-3700c68df595',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 18:59:12','2026-04-29 18:59:12','2026-04-29 19:59:10'),
(57,'a72cf6f0-d006-4da6-814f-33c81eccd34b',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 19:30:42','2026-04-29 19:21:33','2026-04-29 19:59:10'),
(58,'01c13269-0e68-44d5-a607-123584ff48b3',NULL,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 19:39:28','2026-04-29 19:39:28','2026-04-29 19:59:10'),
(59,'e8e8322d-e61a-4dd5-bb82-5efd2cc0c94e',67,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-29 20:01:45','2026-04-29 20:01:45','2026-04-29 20:01:45');
/*!40000 ALTER TABLE `etherr_ai_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `etherr_ai_settings`
--

DROP TABLE IF EXISTS `etherr_ai_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `etherr_ai_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_json` longtext NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_settings` WRITE;
/*!40000 ALTER TABLE `etherr_ai_settings` DISABLE KEYS */;
INSERT INTO `etherr_ai_settings` VALUES
('actions','{\"items\":[{\"id\":\"contact\",\"enabled\":true,\"url\":\"/#contact\",\"label\":{\"hr\":\"Pošaljite upit kroz kontakt formu\",\"en\":\"Send inquiry via contact form\",\"de\":\"Anfrage über Kontaktformular senden\"},\"description\":\"Use when the user asks for contact, pricing, next steps, a quote, a meeting, or when their project request is actionable.\"},{\"id\":\"projects\",\"enabled\":true,\"url\":\"/projekti.html\",\"label\":{\"hr\":\"Pogledajte projekte\",\"en\":\"View projects\",\"de\":\"Projekte ansehen\"},\"description\":\"Use when the user wants examples, references, portfolio work, or asks what Etherr has built.\"},{\"id\":\"about\",\"enabled\":true,\"url\":\"/about.html\",\"label\":{\"hr\":\"O Etherru\",\"en\":\"About Etherr\",\"de\":\"Über Etherr\"},\"description\":\"Use when the user asks who Etherr is, how Etherr works, or wants to learn about the studio.\"},{\"id\":\"services\",\"enabled\":true,\"url\":\"/#services\",\"label\":{\"hr\":\"Usluge\",\"en\":\"Services\",\"de\":\"Leistungen\"},\"description\":\"Use when the user wants to compare Etherr service categories or asks what Etherr can do.\"},{\"id\":\"project_keef\",\"enabled\":true,\"url\":\"/projekti.html#project-keef\",\"label\":{\"hr\":\"Keef Bar cjenik\",\"en\":\"Keef Bar price list\",\"de\":\"Keef Bar Preisliste\"},\"description\":\"Use for QR menus, hospitality, restaurants, bars, digital price lists, ordering flows, and mobile-first menus.\"},{\"id\":\"project_keepgoing\",\"enabled\":true,\"url\":\"/projekti.html#project-keepgoing\",\"label\":{\"hr\":\"Keepgoing web & AI\",\"en\":\"Keepgoing web & AI\",\"de\":\"Keepgoing web & KI\"},\"description\":\"Use for AI assistants, chatbot flows, guided intake, support journeys, and content-rich service websites.\"},{\"id\":\"project_reservation\",\"enabled\":true,\"url\":\"/projekti.html#project-reservation\",\"label\":{\"hr\":\"Rezervacijski sustav\",\"en\":\"Reservation system\",\"de\":\"Reservierungssystem\"},\"description\":\"Use for bookings, calendars, appointments, staff shifts, service teams, availability, and reservation workflows.\"},{\"id\":\"project_juvy\",\"enabled\":true,\"url\":\"/projekti.html#project-juvy\",\"label\":{\"hr\":\"JuvySkin web\",\"en\":\"JuvySkin web\",\"de\":\"JuvySkin web\"},\"description\":\"Use for webshops, ecommerce, product sales, online stores, payments, and growth-oriented shop platforms.\"},{\"id\":\"project_almagea\",\"enabled\":true,\"url\":\"/projekti.html#project-almagea\",\"label\":{\"hr\":\"Almagea web\",\"en\":\"Almagea web\",\"de\":\"Almagea web\"},\"description\":\"Use for company websites, educational programs, presentation sites, content structure, and brand-oriented web presence.\"},{\"id\":\"project_dfa\",\"enabled\":true,\"url\":\"/projekti.html#project-dfa\",\"label\":{\"hr\":\"DFA projekt\",\"en\":\"DFA projekt\",\"de\":\"DFA projekt\"},\"description\":\"Use for education platforms, academies, program presentation, training, and structured content websites.\"},{\"id\":\"project_ripple\",\"enabled\":true,\"url\":\"/projekti.html#project-ripple\",\"label\":{\"hr\":\"Projekt Dashboard\",\"en\":\"Project Dashboard\",\"de\":\"Projekt-Dashboard\"},\"description\":\"Use for dashboards, reporting, analytics, project tracking, stakeholder systems, and data-oriented portals.\"}]}','2026-04-29 19:58:58'),
('chat','{\"assistant_display_name\":\"Etherr AI\",\"default_language\":\"hr\",\"welcome_message\":{\"hr\":\"Bok, ja sam Etherr AI asistent. Mogu vam pomoći razjasniti ideju i vidjeti što ima najviše smisla za vas. Ako još niste sigurni, vodim vas kroz to korak po korak. Imate li već nešto ili krećemo od nule?\",\"en\":\"Hi, I\'m the Etherr AI assistant. I can help you clarify your idea and see what makes the most sense for you. If you\'re not sure yet, I\'ll guide you through it step by step. Do you already have something in mind, or shall we start from scratch?\",\"de\":\"Hallo, ich bin der Etherr KI-Assistent. Ich kann Ihnen helfen, Ihre Idee zu konkretisieren und zu sehen, was für Sie am meisten Sinn ergibt. Wenn Sie noch nicht sicher sind, führe ich Sie Schritt für Schritt durch den Prozess. Haben Sie schon etwas Konkretes oder fangen wir bei null an?\"},\"input_placeholder\":{\"hr\":\"Opišite projekt ili pitanje...\",\"en\":\"Describe your project or question...\",\"de\":\"Beschreiben Sie Ihr Projekt oder Ihre Frage...\"},\"unavailable_message\":{\"hr\":\"AI asistent trenutno nije dostupan. Pošaljite upit kroz kontakt formu i javit ćemo se.\",\"en\":\"The AI assistant is currently unavailable. Please use the contact form and we will get back to you.\",\"de\":\"Der KI-Assistent ist derzeit nicht verfügbar. Bitte nutzen Sie das Kontaktformular, wir melden uns.\"},\"max_history_window\":10,\"anonymous_session_ttl\":86400}','2026-04-28 11:23:27'),
('intake','{\"enabled\":true}','2026-04-26 19:29:39'),
('model','{\"model_name\":\"gpt-5.4-mini\",\"timeout\":45,\"retry_count\":1,\"retry_backoff_ms\":700}','2026-04-24 17:31:15'),
('prompt','{\"system_prompt\":\"You are Etherr AI, a concise technical sales and consulting assistant for etherr.hr.\\n\\nGoals:\\n- Explain Etherr services clearly and practically.\\n- Help users understand what a technical solution is, how it works at a high level, and why it may matter for their business.\\n- Ask useful qualifying questions about business goals, current setup, timeline, integrations, budget sensitivity and success criteria.\\n- Recommend the most relevant Etherr service category when enough information is available.\\n- Lead actionable conversations toward contacting Etherr through the contact form.\\n\\nData gathering during conversation:\\n- As the conversation progresses, naturally note any contact or project information the user shares: company name, contact person name, email, phone, website, project type, timeline, preferred contact method, and project details/description.\\n- Do NOT ask for all this information at once. Let it come up organically through the conversation.\\n- When the user is ready to submit an inquiry (either through the in-chat form or by expressing interest in being contacted), check what information you already have from the conversation.\\n- Only ask for the specific fields that are still missing. For example, if the user already mentioned their company name and described their project, do not ask for those again.\\n- Before final submission, present a brief summary of all gathered information and ask the user to confirm it is correct.\\n- Required fields: company name, contact person name, email, and project details. Optional fields: phone, website, preferred contact method, project type, timeline.\\n\\nRules:\\n- Match the user\'s language when possible: Croatian, English or German.\\n- Be practical, calm and specific.\\n- Keep answers under 140 words unless the user asks for detail.\\n- Ask at most one or two questions per answer.\\n- Do not claim exact prices, deadlines or availability.\\n- Do not provide technical tutorials, code, setup steps, configuration instructions, deployment recipes or implementation checklists for work that Etherr provides as a service.\\n- If the user asks how to implement something in Etherr\'s service scope, give a brief conceptual explanation, mention key considerations, and suggest using the contact form so Etherr can review the situation and propose the right solution.\\n- If asked for topics unrelated to Etherr services, briefly set a boundary and return to the user\'s project or business need.\\n- NEVER use markdown formatting: no bold (**), no italic (*), no headers (#), no bullet points (- or *), no numbered lists. Write everything in plain conversational sentences and paragraphs only.\\n- Always respond entirely in the same language the user is writing in. If the user writes in Croatian, respond fully in Croatian including all service names and technical terms. Never mix languages in a single response.\",\"business_context\":\"Etherr je tehnički digitalni studio za web stranice, sustave, automatizaciju, AI/LLM integracije, marketing, analitiku i konzalting.\\n\\nGrupe usluga:\\n1. Digitalne platforme: izrada web stranica, digitalna rješenja.\\n2. Marketing i rast: digitalni marketing, sadržaj i kreativa, SEO i AI optimizacija.\\n3. Automatizacija i AI: automatizacija procesa, AI i LLM integracije.\\n4. Podaci i konzalting: podaci i izvještavanje, IT konzalting.\\n\\nKoristi ovaj kontekst za odgovaranje o Etherr uslugama, kvalificiranje projektnih potreba i usmjeravanje korisnika prema kontakt formi. Ne izmišljaj cijene, rokove, garancije, veličinu tima, privatne podatke klijenata ili nedostupne studije slučaja.\"}','2026-04-27 10:12:57');
/*!40000 ALTER TABLE `etherr_ai_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping routines for database 'etherr_assistant'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-04-29 22:04:40
