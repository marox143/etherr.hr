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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_conversations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_conversations` WRITE;
/*!40000 ALTER TABLE `etherr_ai_conversations` DISABLE KEYS */;
INSERT INTO `etherr_ai_conversations` VALUES
(27,'8479bf67-582e-4ca2-92e5-2d88f7eaaec3','active','hr','2026-04-27 10:12:57',NULL,'2026-04-27 10:12:57','2026-04-27 10:12:57'),
(28,'64522cb6-2a19-4cbf-9d48-ac2cefb6310a','ended','en','2026-04-28 06:41:54','2026-04-28 09:34:17','2026-04-28 06:41:54','2026-04-28 09:34:17'),
(29,'64522cb6-2a19-4cbf-9d48-ac2cefb6310a','active','hr','2026-04-28 09:34:17',NULL,'2026-04-28 09:34:17','2026-04-28 09:34:17'),
(30,'049a8c26-d605-4fe0-bfdb-0173d6bf7aa3','active','hr','2026-04-28 11:10:13',NULL,'2026-04-28 11:10:13','2026-04-28 11:10:13'),
(31,'a76d96df-ed9a-45ca-9253-55e5c6876466','active','hr','2026-04-28 11:21:53',NULL,'2026-04-28 11:21:53','2026-04-28 11:21:53'),
(32,'6138679a-5084-42b9-81b1-1c599794b9e2','active','hr','2026-04-28 11:22:11',NULL,'2026-04-28 11:22:11','2026-04-28 11:22:11'),
(33,'25f615f2-a082-40a1-b1ac-990352ccb2fd','active','hr','2026-04-28 11:23:08',NULL,'2026-04-28 11:23:08','2026-04-28 11:23:08'),
(34,'a0b74c44-6024-4218-915c-858b55f733d3','active','hr','2026-04-28 11:23:38',NULL,'2026-04-28 11:23:38','2026-04-28 11:23:38'),
(35,'27dbca78-3823-4e30-b6e3-4c37f25feeca','active','hr','2026-04-28 11:23:53',NULL,'2026-04-28 11:23:53','2026-04-28 11:23:53'),
(36,'8db5d3cc-0c6b-4763-8594-920748e5cedb','active','en','2026-04-28 11:23:53',NULL,'2026-04-28 11:23:53','2026-04-28 11:23:53'),
(37,'ce06df64-e114-4262-972a-c9cd0ac472f4','active','de','2026-04-28 11:23:53',NULL,'2026-04-28 11:23:53','2026-04-28 11:23:53'),
(38,'b38c3426-1878-4df9-9701-6dcc95a73dfc','active','hr','2026-04-28 11:24:29',NULL,'2026-04-28 11:24:29','2026-04-28 11:24:29'),
(39,'0406a6e0-c929-4b07-907f-6c951ac8e09b','active','en','2026-04-28 11:24:29',NULL,'2026-04-28 11:24:29','2026-04-28 11:24:29'),
(40,'e8f77c32-fd03-437a-bfc1-8fb528b2d6ce','active','de','2026-04-28 11:24:29',NULL,'2026-04-28 11:24:29','2026-04-28 11:24:29'),
(41,'0f862abc-9699-4ece-8be8-f567a0050ed0','active','hr','2026-04-28 11:27:14',NULL,'2026-04-28 11:27:14','2026-04-28 11:27:14'),
(42,'59d3bf25-e2b9-4ed7-aa37-a0caa6afe650','active','hr','2026-04-28 11:29:52',NULL,'2026-04-28 11:29:52','2026-04-28 11:29:52'),
(43,'b56bb2e6-1af8-4971-8b5f-7e639152b4d3','active','hr','2026-04-28 11:45:44',NULL,'2026-04-28 11:45:44','2026-04-28 11:45:44'),
(44,'16e882a3-96f8-417a-bb7b-99fd14efb94a','active','hr','2026-04-28 11:48:26',NULL,'2026-04-28 11:48:26','2026-04-28 11:48:26'),
(45,'3815a5e5-9043-4aa2-8da3-a1f663e00d1a','active','hr','2026-04-28 12:05:45',NULL,'2026-04-28 12:05:45','2026-04-28 12:05:45'),
(46,'5efb0c4b-e992-446e-8d66-0c881601971b','active','hr','2026-04-28 12:18:28',NULL,'2026-04-28 12:18:28','2026-04-28 12:18:28'),
(47,'04270281-a5ba-4dc2-9b1e-6f1e3a6545e4','active','hr','2026-04-28 15:30:09',NULL,'2026-04-28 15:30:09','2026-04-28 15:30:09'),
(48,'a690b6c8-433e-454e-a926-89ccf351f462','ended','hr','2026-04-28 15:35:46','2026-04-28 15:35:47','2026-04-28 15:35:46','2026-04-28 15:35:47'),
(49,'a690b6c8-433e-454e-a926-89ccf351f462','active','hr','2026-04-28 15:35:47',NULL,'2026-04-28 15:35:47','2026-04-28 15:35:47'),
(50,'4d9a5b13-95b0-4ead-a13e-70730d8ffc28','active','hr','2026-04-28 15:59:39',NULL,'2026-04-28 15:59:39','2026-04-28 15:59:39'),
(51,'1877ab5a-9028-4721-97c9-e6b19d211d67','active','hr','2026-04-28 16:47:28',NULL,'2026-04-28 16:47:28','2026-04-28 16:47:28');
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_intakes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_intakes` WRITE;
/*!40000 ALTER TABLE `etherr_ai_intakes` DISABLE KEYS */;
INSERT INTO `etherr_ai_intakes` VALUES
(13,29,'submitted','hr','{\"version\":1,\"status\":\"submitted\",\"locale\":\"hr\",\"step\":\"ready\",\"data\":{\"services_raw\":\"yyeydyd koja je cijena chtabota treba mi i web stranica za njega, sve cijela usluga, koja je to cijena\",\"services\":[{\"id\":\"websites\",\"title\":\"Web stranica\",\"category\":\"Digitalne platforme\"},{\"id\":\"custom_system\",\"title\":\"Custom sustav / web aplikacija\",\"category\":\"Digitalne platforme\"},{\"id\":\"automation_ai\",\"title\":\"Automatizacija i AI\",\"category\":\"Automatizacija i AI\"}],\"project_type\":{\"value\":\"\",\"label\":\"\"},\"timeline\":{\"value\":\"\",\"label\":\"\"},\"company\":\"KKK\",\"website\":\"kkk.com\",\"name\":\"mmm\",\"email\":\"dejogh@gmail.com\",\"phone\":\"0983883838338\",\"preferred_contact\":{\"value\":\"email\",\"label\":\"E-mail\"},\"details\":\"rr asistent pomaže vam razjasniti ideju, odabrati smjer i bolje razumjeti koje digitalno rješenje ima smisla za vaš projekt. Može objasniti usluge\",\"consent\":true}}','98cb317141c8c555','2026-04-28 13:28:59','2026-04-28 13:29:53','2026-04-28 13:29:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etherr_ai_messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `etherr_ai_messages` WRITE;
/*!40000 ALTER TABLE `etherr_ai_messages` DISABLE KEYS */;
INSERT INTO `etherr_ai_messages` VALUES
(115,28,'user','hi',1,NULL,'2026-04-28 06:41:57'),
(116,28,'assistant','Hi! What kind of digital project or business need are you working on right now?',20,'{\"response_id\":\"resp_0117703d27cd975a0069f056b62e64819f88c503869da8cd5b\",\"model\":\"gpt-5.4-mini-2026-03-17\",\"usage\":{\"input_tokens\":1769,\"input_tokens_details\":{\"cached_tokens\":0},\"output_tokens\":21,\"output_tokens_details\":{\"reasoning_tokens\":0},\"total_tokens\":1790},\"action_ids\":[],\"actions\":[]}','2026-04-28 06:41:59'),
(117,29,'user','yyeydyd',2,NULL,'2026-04-28 09:52:49'),
(118,29,'assistant','Čini se da poruka nije čitljiva. Mogu pomoći s Etherr uslugama, projektom, web stranicom, automatizacijom ili AI integracijama.\n\nŠto točno trebate za svoj projekt ili poslovanje?',45,'{\"response_id\":\"resp_0ab9a61ff36b09880069f0837243c48196a204f2b688f4f463\",\"model\":\"gpt-5.4-mini-2026-03-17\",\"usage\":{\"input_tokens\":1785,\"input_tokens_details\":{\"cached_tokens\":0},\"output_tokens\":57,\"output_tokens_details\":{\"reasoning_tokens\":0},\"total_tokens\":1842},\"action_ids\":[],\"actions\":[]}','2026-04-28 09:52:52'),
(119,46,'user','can you show me a project for webshop',10,NULL,'2026-04-28 12:30:09'),
(120,46,'assistant','For webshop projects, the most relevant example is Etherr’s ecommerce work, which shows how product structure, payments, shipping logic and a clean buying flow can come together in one system. It is a good reference if you are planning an online store with more than a few products or need payment and order handling. The project also helps illustrate how Etherr approaches webshop UX and growth-focused setup.',103,'{\"response_id\":\"resp_0e2f232f97399df80069f0a852c21081a3964371585e62d607\",\"model\":\"gpt-5.4-mini-2026-03-17\",\"usage\":{\"input_tokens\":3478,\"input_tokens_details\":{\"cached_tokens\":0},\"output_tokens\":95,\"output_tokens_details\":{\"reasoning_tokens\":0},\"total_tokens\":3573},\"action_ids\":[\"project_juvy\",\"projects\"],\"actions\":[]}','2026-04-28 12:30:12'),
(121,46,'user','and where can i find the services you offer',11,NULL,'2026-04-28 12:30:42'),
(122,46,'assistant','The services are grouped across digital platforms, marketing and growth, automation and AI, and data plus consulting. That includes websites, webshops, SEO, content support, AI integrations, reporting and IT consulting, so the offer is broader than just one type of build. The services page gives the clearest overview of what Etherr can handle and helps match the right solution to your project.',99,'{\"response_id\":\"resp_03bcc01d1d39e0780069f0a8728ab8819c85c71d3244ea9b48\",\"model\":\"gpt-5.4-mini-2026-03-17\",\"usage\":{\"input_tokens\":3575,\"input_tokens_details\":{\"cached_tokens\":3328},\"output_tokens\":89,\"output_tokens_details\":{\"reasoning_tokens\":0},\"total_tokens\":3664},\"action_ids\":[\"services\"],\"actions\":[]}','2026-04-28 12:30:45'),
(123,46,'user','give me a dashboard',5,NULL,'2026-04-28 12:31:30'),
(124,46,'assistant','A dashboard project is the right reference when you need reporting, KPI tracking, analytics or a portal that brings data from different tools into one place. Etherr’s dashboard example shows how project status, performance data and stakeholder visibility can be organized in a practical system. It is especially useful if your business needs clearer reporting instead of checking separate tools manually.',101,'{\"response_id\":\"resp_0afab9547fcc6f8d0069f0a8a3099c8191bfcea3073451edb4\",\"model\":\"gpt-5.4-mini-2026-03-17\",\"usage\":{\"input_tokens\":3666,\"input_tokens_details\":{\"cached_tokens\":3328},\"output_tokens\":73,\"output_tokens_details\":{\"reasoning_tokens\":0},\"total_tokens\":3739},\"action_ids\":[\"project_ripple\"],\"actions\":[]}','2026-04-28 12:31:32'),
(125,29,'user','koja je cijena chtabota',6,NULL,'2026-04-28 13:27:47'),
(126,29,'assistant','Osnovna implementacija AI chatbota kreće od 1.500 EUR, ali konačna cijena ovisi o složenosti razgovora, količini sadržaja, integracijama i korištenju API-ja. Za većinu web stranica mjesečni AI trošak je često oko 10 do 50 EUR, opet ovisno o prometu i upotrebi.\n\nChatbot je dobar kad želite odgovarati na česta pitanja, voditi posjetitelje kroz usluge i skupljati kontakt podatke prije ljudskog preuzimanja.\n\nZa procjenu je najvažnije znati: na kojoj web stranici bi radio i treba li ga povezati s CRM-om ili drugim alatima?',131,'{\"response_id\":\"resp_02df63a45dd7d5560069f0b5d4e02881909859c8ae3f5c8072\",\"model\":\"gpt-5.4-mini-2026-03-17\",\"usage\":{\"input_tokens\":3580,\"input_tokens_details\":{\"cached_tokens\":0},\"output_tokens\":160,\"output_tokens_details\":{\"reasoning_tokens\":0},\"total_tokens\":3740},\"action_ids\":[\"contact\"],\"actions\":[{\"id\":\"intake_start\",\"label\":\"Pošaljite upit kroz chat\",\"kind\":\"intake_start\"}]}','2026-04-28 13:27:54'),
(127,29,'user','treba mi i web stranica za njega, sve cijela usluga, koja je to cijena',18,NULL,'2026-04-28 13:28:25'),
(128,29,'assistant','Za web stranicu s chatbotom kao cjelovito rješenje okvirno treba računati na kombinaciju obje stavke: jednostavna prezentacijska web stranica kreće od 800 EUR, a AI chatbot od 1.500 EUR. Ako treba više sadržaja, posebna logika ili integracije, ukupno obično ide više.\n\nZa realniju procjenu trebam još samo dvije stvari: radi li se o klasičnoj prezentacijskoj stranici ili webu s više funkcionalnosti, i treba li chatbot skupljati upite ili se povezivati s nekim sustavom poput CRM-a?',121,'{\"response_id\":\"resp_03e73224a7187a080069f0b5fb1da881a1b1e02241c711035e\",\"model\":\"gpt-5.4-mini-2026-03-17\",\"usage\":{\"input_tokens\":3768,\"input_tokens_details\":{\"cached_tokens\":3328},\"output_tokens\":138,\"output_tokens_details\":{\"reasoning_tokens\":0},\"total_tokens\":3906},\"action_ids\":[\"project_almagea\"],\"actions\":[{\"id\":\"intake_start\",\"label\":\"Pošaljite upit kroz chat\",\"kind\":\"intake_start\"}]}','2026-04-28 13:28:28'),
(129,29,'assistant','Trebam još samo par podataka za upit.\n\nKako se zove tvrtka ili organizacija?',19,'{\"model\":\"assistant-intake\",\"actions\":[]}','2026-04-28 13:28:59'),
(130,29,'user','KKK',1,NULL,'2026-04-28 13:29:05'),
(131,29,'assistant','Imate li postojeću web stranicu? Pošaljite link ili napišite “preskoči”.',18,'{\"response_id\":\"\",\"model\":\"assistant-intake\",\"usage\":[],\"action_ids\":[],\"actions\":[]}','2026-04-28 13:29:05'),
(132,29,'user','kkk.com',2,NULL,'2026-04-28 13:29:12'),
(133,29,'assistant','Tko je kontakt osoba?',6,'{\"response_id\":\"\",\"model\":\"assistant-intake\",\"usage\":[],\"action_ids\":[],\"actions\":[]}','2026-04-28 13:29:12'),
(134,29,'user','mmm',1,NULL,'2026-04-28 13:29:18'),
(135,29,'assistant','Na koji email vam se Etherr može javiti?',10,'{\"response_id\":\"\",\"model\":\"assistant-intake\",\"usage\":[],\"action_ids\":[],\"actions\":[]}','2026-04-28 13:29:18'),
(136,29,'user','dejogh@gmail.com',4,NULL,'2026-04-28 13:29:24'),
(137,29,'assistant','Možete ostaviti i telefon ili napisati “preskoči”.',13,'{\"response_id\":\"\",\"model\":\"assistant-intake\",\"usage\":[],\"action_ids\":[],\"actions\":[]}','2026-04-28 13:29:24'),
(138,29,'user','0983883838338',4,NULL,'2026-04-28 13:29:27'),
(139,29,'assistant','Ukratko opišite što trebate, cilj projekta i sve važne napomene.',16,'{\"response_id\":\"\",\"model\":\"assistant-intake\",\"usage\":[],\"action_ids\":[],\"actions\":[]}','2026-04-28 13:29:27'),
(140,29,'user','rr asistent pomaže vam razjasniti ideju, odabrati smjer i bolje razumjeti koje digitalno rješenje ima smisla za vaš projekt. Može objasniti usluge',37,NULL,'2026-04-28 13:29:33'),
(141,29,'assistant','Za slanje upita trebamo potvrdu da Etherr smije koristiti ove podatke kako bi odgovorio na vaš upit. Odgovorite “da” ako se slažete.',33,'{\"response_id\":\"\",\"model\":\"assistant-intake\",\"usage\":[],\"action_ids\":[],\"actions\":[]}','2026-04-28 13:29:33'),
(142,29,'user','da',1,NULL,'2026-04-28 13:29:37'),
(143,29,'assistant','Pripremio sam sažetak upita. Ako je sve u redu, pošaljite ga Etherru gumbom ispod.\n\nSažetak upita:\nUsluge: Web stranica, Custom sustav / web aplikacija, Automatizacija i AI\nVrsta projekta: \nRok: \nTvrtka: KKK\nWeb stranica: kkk.com\nKontakt osoba: mmm\nEmail: dejogh@gmail.com\nTelefon: 0983883838338\nPreferirani kontakt: E-mail\n\nDetalji:\nrr asistent pomaže vam razjasniti ideju, odabrati smjer i bolje razumjeti koje digitalno rješenje ima smisla za vaš projekt. Može objasniti usluge',120,'{\"response_id\":\"\",\"model\":\"assistant-intake\",\"usage\":[],\"action_ids\":[],\"actions\":[{\"id\":\"intake_submit\",\"label\":\"Pošalji upit\",\"kind\":\"intake_submit\"}]}','2026-04-28 13:29:37'),
(144,29,'assistant','Upit je poslan Etherru. Javit ćemo vam se u najkraćem roku.',15,'{\"model\":\"assistant-intake\",\"request_id\":\"98cb317141c8c555\",\"mail_status\":\"sent\"}','2026-04-28 13:29:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(22,'8479bf67-582e-4ca2-92e5-2d88f7eaaec3',27,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','8f42af6f304383bbe22bc11b9f1c9ce77bb37478b15b0b0d93224e249f36e68e',1,'2026-04-27 10:12:57','2026-04-27 10:12:57','2026-04-27 10:12:57'),
(23,'64522cb6-2a19-4cbf-9d48-ac2cefb6310a',29,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 13:29:51','2026-04-28 06:41:54','2026-04-28 13:29:51'),
(24,'049a8c26-d605-4fe0-bfdb-0173d6bf7aa3',30,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:10:13','2026-04-28 11:10:13','2026-04-28 11:10:13'),
(25,'a76d96df-ed9a-45ca-9253-55e5c6876466',31,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:21:53','2026-04-28 11:21:53','2026-04-28 11:21:53'),
(26,'6138679a-5084-42b9-81b1-1c599794b9e2',32,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:22:34','2026-04-28 11:22:11','2026-04-28 11:22:34'),
(27,'25f615f2-a082-40a1-b1ac-990352ccb2fd',33,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:23:08','2026-04-28 11:23:08','2026-04-28 11:23:08'),
(28,'a0b74c44-6024-4218-915c-858b55f733d3',34,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 11:24:52','2026-04-28 11:23:38','2026-04-28 11:24:52'),
(29,'27dbca78-3823-4e30-b6e3-4c37f25feeca',35,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:23:53','2026-04-28 11:23:53','2026-04-28 11:23:53'),
(30,'8db5d3cc-0c6b-4763-8594-920748e5cedb',36,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:23:53','2026-04-28 11:23:53','2026-04-28 11:23:53'),
(31,'ce06df64-e114-4262-972a-c9cd0ac472f4',37,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:23:53','2026-04-28 11:23:53','2026-04-28 11:23:53'),
(32,'b38c3426-1878-4df9-9701-6dcc95a73dfc',38,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:24:29','2026-04-28 11:24:29','2026-04-28 11:24:29'),
(33,'0406a6e0-c929-4b07-907f-6c951ac8e09b',39,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:24:29','2026-04-28 11:24:29','2026-04-28 11:24:29'),
(34,'e8f77c32-fd03-437a-bfc1-8fb528b2d6ce',40,'19e36255972107d42b8cecb77ef5622e842e8a50778a6ed8dd1ce94732daca9e','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',1,'2026-04-28 11:24:29','2026-04-28 11:24:29','2026-04-28 11:24:29'),
(35,'0f862abc-9699-4ece-8be8-f567a0050ed0',41,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 11:28:12','2026-04-28 11:27:14','2026-04-28 11:28:12'),
(36,'59d3bf25-e2b9-4ed7-aa37-a0caa6afe650',42,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:29:52','2026-04-28 11:29:52','2026-04-28 11:29:52'),
(37,'b56bb2e6-1af8-4971-8b5f-7e639152b4d3',43,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:45:44','2026-04-28 11:45:44','2026-04-28 11:45:44'),
(38,'16e882a3-96f8-417a-bb7b-99fd14efb94a',44,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 11:48:26','2026-04-28 11:48:26','2026-04-28 11:48:26'),
(39,'3815a5e5-9043-4aa2-8da3-a1f663e00d1a',45,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 12:05:45','2026-04-28 12:05:45','2026-04-28 12:05:45'),
(40,'5efb0c4b-e992-446e-8d66-0c881601971b',46,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 12:31:30','2026-04-28 12:18:28','2026-04-28 12:31:30'),
(41,'04270281-a5ba-4dc2-9b1e-6f1e3a6545e4',47,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 15:30:09','2026-04-28 15:30:09','2026-04-28 15:30:09'),
(42,'a690b6c8-433e-454e-a926-89ccf351f462',49,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 15:35:47','2026-04-28 15:35:46','2026-04-28 15:35:47'),
(43,'4d9a5b13-95b0-4ead-a13e-70730d8ffc28',50,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','c378273f7787788aecfcfef0c93ead30970e2271e3055640af88a3f125e0ce2f',1,'2026-04-28 15:59:39','2026-04-28 15:59:39','2026-04-28 15:59:39'),
(44,'1877ab5a-9028-4721-97c9-e6b19d211d67',51,'eff8e7ca506627fe15dda5e0e512fcaad70b6d520f37cc76597fdb4f2d83a1a3','4cbc8039bc8d8badefe54056014551dfd22b5e4a800a9e6305203a5a073e6b48',1,'2026-04-28 16:47:28','2026-04-28 16:47:28','2026-04-28 16:47:28');
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
('actions','{\"items\":[{\"id\":\"contact\",\"enabled\":true,\"url\":\"/#contact\",\"label\":{\"hr\":\"Kontaktirajte Etherr\",\"en\":\"Contact Etherr\",\"de\":\"Etherr kontaktieren\"},\"description\":\"Use when the user asks for contact, pricing, next steps, a quote, a meeting, or when their project request is actionable.\"},{\"id\":\"projects\",\"enabled\":true,\"url\":\"/projekti.html\",\"label\":{\"hr\":\"Pogledajte projekte\",\"en\":\"View projects\",\"de\":\"Projekte ansehen\"},\"description\":\"Use when the user wants examples, references, portfolio work, or asks what Etherr has built.\"},{\"id\":\"about\",\"enabled\":true,\"url\":\"/about.html\",\"label\":{\"hr\":\"O Etherru\",\"en\":\"About Etherr\",\"de\":\"Über Etherr\"},\"description\":\"Use when the user asks who Etherr is, how Etherr works, or wants to learn about the studio.\"},{\"id\":\"services\",\"enabled\":true,\"url\":\"/#services\",\"label\":{\"hr\":\"Usluge\",\"en\":\"Services\",\"de\":\"Leistungen\"},\"description\":\"Use when the user wants to compare Etherr service categories or asks what Etherr can do.\"},{\"id\":\"project_keef\",\"enabled\":true,\"url\":\"/projekti.html#project-keef-title\",\"label\":{\"hr\":\"QR menu projekt\",\"en\":\"QR menu project\",\"de\":\"QR-Menü-Projekt\"},\"description\":\"Use for QR menus, hospitality, restaurants, bars, digital price lists, ordering flows, and mobile-first menus.\"},{\"id\":\"project_keepgoing\",\"enabled\":true,\"url\":\"/projekti.html#project-keepgoing-title\",\"label\":{\"hr\":\"AI asistent projekt\",\"en\":\"AI assistant project\",\"de\":\"KI-Assistent-Projekt\"},\"description\":\"Use for AI assistants, chatbot flows, guided intake, support journeys, and content-rich service websites.\"},{\"id\":\"project_reservation\",\"enabled\":true,\"url\":\"/projekti.html#project-reservation-title\",\"label\":{\"hr\":\"Rezervacijski sustav\",\"en\":\"Reservation system\",\"de\":\"Reservierungssystem\"},\"description\":\"Use for bookings, calendars, appointments, staff shifts, service teams, availability, and reservation workflows.\"},{\"id\":\"project_juvy\",\"enabled\":true,\"url\":\"/projekti.html#project-juvy-title\",\"label\":{\"hr\":\"Webshop projekt\",\"en\":\"Webshop project\",\"de\":\"Webshop-Projekt\"},\"description\":\"Use for webshops, ecommerce, product sales, online stores, payments, and growth-oriented shop platforms.\"},{\"id\":\"project_almagea\",\"enabled\":true,\"url\":\"/projekti.html#project-almagea-title\",\"label\":{\"hr\":\"Website projekt\",\"en\":\"Website project\",\"de\":\"Website-Projekt\"},\"description\":\"Use for company websites, educational programs, presentation sites, content structure, and brand-oriented web presence.\"},{\"id\":\"project_dfa\",\"enabled\":true,\"url\":\"/projekti.html#project-dfa-title\",\"label\":{\"hr\":\"Akademija projekt\",\"en\":\"Academy project\",\"de\":\"Akademie-Projekt\"},\"description\":\"Use for education platforms, academies, program presentation, training, and structured content websites.\"},{\"id\":\"project_ripple\",\"enabled\":true,\"url\":\"/projekti.html#project-reservation-title-copy\",\"label\":{\"hr\":\"Dashboard projekt\",\"en\":\"Dashboard project\",\"de\":\"Dashboard-Projekt\"},\"description\":\"Use for dashboards, reporting, analytics, project tracking, stakeholder systems, and data-oriented portals.\"}]}','2026-04-26 18:25:21'),
('chat','{\"assistant_display_name\":\"Etherr AI\",\"default_language\":\"hr\",\"welcome_message\":{\"hr\":\"Bok, ja sam Etherr AI asistent. Mogu vam pomoći razjasniti ideju i vidjeti što ima najviše smisla za vas. Ako još niste sigurni, vodim vas kroz to korak po korak. Imate li već nešto ili krećemo od nule?\",\"en\":\"Hi, I\'m the Etherr AI assistant. I can help you clarify your idea and see what makes the most sense for you. If you\'re not sure yet, I\'ll guide you through it step by step. Do you already have something in mind, or shall we start from scratch?\",\"de\":\"Hallo, ich bin der Etherr KI-Assistent. Ich kann Ihnen helfen, Ihre Idee zu konkretisieren und zu sehen, was für Sie am meisten Sinn ergibt. Wenn Sie noch nicht sicher sind, führe ich Sie Schritt für Schritt durch den Prozess. Haben Sie schon etwas Konkretes oder fangen wir bei null an?\"},\"input_placeholder\":{\"hr\":\"Opišite projekt ili pitanje...\",\"en\":\"Describe your project or question...\",\"de\":\"Beschreiben Sie Ihr Projekt oder Ihre Frage...\"},\"unavailable_message\":{\"hr\":\"AI asistent trenutno nije dostupan. Pošaljite upit kroz kontakt formu i javit ćemo se.\",\"en\":\"The AI assistant is currently unavailable. Please use the contact form and we will get back to you.\",\"de\":\"Der KI-Assistent ist derzeit nicht verfügbar. Bitte nutzen Sie das Kontaktformular, wir melden uns.\"},\"max_history_window\":10,\"anonymous_session_ttl\":86400}','2026-04-28 11:23:27'),
('intake','{\"enabled\":true}','2026-04-26 19:29:39'),
('model','{\"model_name\":\"gpt-5.4-mini\",\"timeout\":45,\"retry_count\":1,\"retry_backoff_ms\":700}','2026-04-24 17:31:15'),
('prompt','{\"system_prompt\":\"You are Etherr AI, a concise technical sales and consulting assistant for etherr.hr.\\n\\nGoals:\\n- Explain Etherr services clearly and practically.\\n- Help users understand what a technical solution is, how it works at a high level, and why it may matter for their business.\\n- Ask useful qualifying questions about business goals, current setup, timeline, integrations, budget sensitivity and success criteria.\\n- Recommend the most relevant Etherr service category when enough information is available.\\n- Lead actionable conversations toward contacting Etherr through the contact form.\\n\\nData gathering during conversation:\\n- As the conversation progresses, naturally note any contact or project information the user shares: company name, contact person name, email, phone, website, project type, timeline, preferred contact method, and project details/description.\\n- Do NOT ask for all this information at once. Let it come up organically through the conversation.\\n- When the user is ready to submit an inquiry (either through the in-chat form or by expressing interest in being contacted), check what information you already have from the conversation.\\n- Only ask for the specific fields that are still missing. For example, if the user already mentioned their company name and described their project, do not ask for those again.\\n- Before final submission, present a brief summary of all gathered information and ask the user to confirm it is correct.\\n- Required fields: company name, contact person name, email, and project details. Optional fields: phone, website, preferred contact method, project type, timeline.\\n\\nRules:\\n- Match the user\'s language when possible: Croatian, English or German.\\n- Be practical, calm and specific.\\n- Keep answers under 140 words unless the user asks for detail.\\n- Ask at most one or two questions per answer.\\n- Do not claim exact prices, deadlines or availability.\\n- Do not provide technical tutorials, code, setup steps, configuration instructions, deployment recipes or implementation checklists for work that Etherr provides as a service.\\n- If the user asks how to implement something in Etherr\'s service scope, give a brief conceptual explanation, mention key considerations, and suggest using the contact form so Etherr can review the situation and propose the right solution.\\n- If asked for topics unrelated to Etherr services, briefly set a boundary and return to the user\'s project or business need.\\n- NEVER use markdown formatting: no bold (**), no italic (*), no headers (#), no bullet points (- or *), no numbered lists. Write everything in plain conversational sentences and paragraphs only.\\n- Always respond entirely in the same language the user is writing in. If the user writes in Croatian, respond fully in Croatian including all service names and technical terms. Never mix languages in a single response.\",\"business_context\":\"Etherr je tehnički digitalni studio za web stranice, sustave, automatizaciju, AI/LLM integracije, marketing, analitiku i konzalting.\\n\\nGrupe usluga:\\n1. Digitalne platforme: izrada web stranica, digitalna rješenja.\\n2. Marketing i rast: digitalni marketing, sadržaj i kreativa, SEO i AI optimizacija.\\n3. Automatizacija i AI: automatizacija procesa, AI i LLM integracije.\\n4. Podaci i konzalting: podaci i izvještavanje, IT konzalting.\\n\\nKoristi ovaj kontekst za odgovaranje o Etherr uslugama, kvalificiranje projektnih potreba i usmjeravanje korisnika prema kontakt formi. Ne izmišljaj cijene, rokove, garancije, veličinu tima, privatne podatke klijenata ili nedostupne studije slučaja.\"}','2026-04-27 10:12:57');
/*!40000 ALTER TABLE `etherr_ai_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-04-28 18:59:35
