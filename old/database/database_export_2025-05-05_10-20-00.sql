DROP TABLE IF EXISTS `admins`;

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `role` enum('super_admin','admin','editor') NOT NULL DEFAULT 'admin',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` VALUES("1","admin","admin@example.com","$2y$10$30feo3yC/r1PMwm4yCdsB.kmaM5F4cUOUyFdcY8xaTgTCIQQ1sA9u","System","Administrator","super_admin","active","2025-04-24 03:43:24","","2025-04-23 11:36:27","2025-04-24 03:43:24");



DROP TABLE IF EXISTS `admins_activity_log`;

CREATE TABLE `admins_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admins_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins_activity_log` VALUES("8","1","login","Admin logged in successfully","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36","2025-04-23 11:47:21");
INSERT INTO `admins_activity_log` VALUES("10","1","login","Admin logged in successfully","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36","2025-04-24 03:43:25");



DROP TABLE IF EXISTS `blog_comments`;

CREATE TABLE `blog_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `blog_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `blog_posts`;

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `excerpt` text NOT NULL,
  `content` longtext NOT NULL,
  `image_url` text DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `author_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `carousel_items`;

CREATE TABLE `carousel_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `carousel_items` VALUES("1","Visit to the Roman Catholic Church - Thank You Tour","/uploads/carousel/1745497758_Screenshot 2025-04-24 122817.jpg","#","1","2025-04-20 22:53:54","2025-04-24 14:29:18");
INSERT INTO `carousel_items` VALUES("2","Hon. Kofi Afful with Indigens on his Thank You tour","/uploads/carousel/1745497375_photo_2025-04-24_12-08-11.jpg","#","2","2025-04-20 22:53:54","2025-04-24 14:22:55");
INSERT INTO `carousel_items` VALUES("5","Thank You Visit - Hon. Benteh Afful","/uploads/carousel/1745498125_photo_3_2025-04-24_12-33-28.jpg","https://www.swma.rf.gd","3","2025-04-24 14:35:25","2025-04-24 14:35:25");
INSERT INTO `carousel_items` VALUES("6","Happy Easter ","/uploads/carousel/1745498280_photo_1_2025-04-24_12-33-28.jpg","https://www.swma.rf.gd","4","2025-04-24 14:38:00","2025-04-24 14:38:00");



DROP TABLE IF EXISTS `contact_messages`;

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `contact_messages` VALUES("1","Gilbert Elikplim Kukah","kwamegilbert1114@gmail.com","0541436414","This is a test compaint","Hello there, this is used to test the complaint","new","2025-04-24 20:34:02","");
INSERT INTO `contact_messages` VALUES("2","Gilbert Elikplim Kukah","kwamegilbert1114@gmail.com","0541436414","This is a test compaint","sdzgxhjgh ,hmgchxgfncgnvmccngb","new","2025-04-24 20:44:55","");



DROP TABLE IF EXISTS `electoral_areas`;

CREATE TABLE `electoral_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `constituency` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `electoral_areas` VALUES("1","Anhwiam","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("2","Dwinase","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("3","Aboduam","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("4","Asawinso","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("5","Boako","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("6","Asempaneye","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("7","Nsawora","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("8","Kojina","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("9","Sefwi Wiawso Central","Sefwi Wiawso","Western North","2025-04-27 14:26:30");
INSERT INTO `electoral_areas` VALUES("10","Yawkrom","Sefwi Wiawso","Western North","2025-04-27 14:26:30");



DROP TABLE IF EXISTS `employment_opportunities`;

CREATE TABLE `employment_opportunities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `address` text NOT NULL,
  `electoral_area_id` int(11) DEFAULT NULL,
  `job_title` varchar(255) NOT NULL,
  `industry` varchar(100) NOT NULL,
  `work_location` varchar(255) NOT NULL,
  `date_employed` date NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `pa_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `electoral_area_id` (`electoral_area_id`),
  KEY `pa_id` (`pa_id`),
  KEY `supervisor_id` (`supervisor_id`)) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE) REFERENCES `supervisors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `entities`;

CREATE TABLE `entities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('company','individual','organization','government') NOT NULL DEFAULT 'company',
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `events`;

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `events` VALUES("1","Town Hall Meeting","town-hall-meeting","Open forum with the MP to discuss constituency priorities.","2025-05-10","2025-05-10","05:26:20","Sefwi Wiawso Municipal Hall","https://via.placeholder.com/800x400?text=Town+Hall+Meeting","2025-04-21 01:05:47");
INSERT INTO `events` VALUES("2","Community Clean‑Up Exercise","community-clean-up-exercise","Join hands to clean and beautify our neighborhoods.","2025-06-15","2025-06-15","09:13:38","Downtown Sefwi Wiawso","https://via.placeholder.com/800x400?text=Clean-Up+Exercise","2025-04-21 01:05:47");
INSERT INTO `events` VALUES("3","Health Outreach Fair","health-outreach-fair","Free medical screenings and health education for all ages.","2025-07-01","2025-07-01","04:16:35","Central Clinic Grounds","https://via.placeholder.com/800x400?text=Health+Fair","2025-04-21 01:05:47");
INSERT INTO `events` VALUES("4","Scholarship Application Workshop","scholarship-application-workshop","Guidance on applying to national and international scholarships.","2025-08-05","2025-08-05","19:27:17","Municipal Education Office","https://via.placeholder.com/800x400?text=Scholarship+Workshop","2025-04-21 01:05:47");
INSERT INTO `events` VALUES("5","ICT Skills Training","ict-skills-training","Hands‑on sessions in basic computing and coding for youth.","2025-06-20","2025-06-22","20:27:32","Tech Lab, Sefwi Wiawso Polytechnic","https://via.placeholder.com/800x400?text=ICT+Training","2025-04-21 01:05:47");
INSERT INTO `events` VALUES("6","Farmers Capacity Building","farmers-capacity-building","Workshops on modern farming techniques and agri‑business.","2025-07-15","2025-07-16","09:27:47","Agro‑Extension Center","https://via.placeholder.com/800x400?text=Farmers+Training","2025-04-21 01:05:47");
INSERT INTO `events` VALUES("7","Youth Sports Day","youth-sports-day","Athletics and games promoting health and teamwork among youths.","2025-08-20","2025-08-20","18:27:54","Municipal Sports Complex","https://via.placeholder.com/800x400?text=Sports+Day","2025-04-21 01:05:47");
INSERT INTO `events` VALUES("8","Road Safety Campaign","road-safety-campaign","Awareness drive on road rules and safe driving practices.","2025-09-10","2025-09-10","00:15:01","Main Junction, Sefwi Wiawso","https://via.placeholder.com/800x400?text=Road+Safety","2025-04-21 01:05:47");
INSERT INTO `events` VALUES("9","Cultural Heritage Festival","cultural-heritage-festival","","2024-01-10","2024-12-27","","Open Grounds, Wiawso","https://via.placeholder.com/800x400?text=Cultural+Festival","2025-04-21 01:05:47");



DROP TABLE IF EXISTS `field_officers`;

CREATE TABLE `field_officers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `electoral_area_id` int(11) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `electoral_area_id` (`electoral_area_id`),
  CONSTRAINT `fo_electoral_area_fk` FOREIGN KEY (`electoral_area_id`) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `field_officers` VALUES("1","Kwame Mensah","kwamegilbert1114@gmail.com","$2y$10$FY6DCv4aixL0tvvk1fZEQ.0kuG8EVwkdDA8FsKMlSc/NCd48uSYUG","+233242560140","Wiawso Central Office","","","active","2025-05-01 20:03:30","2025-04-27 10:50:40","2025-05-01 20:03:30");
INSERT INTO `field_officers` VALUES("2","Ama Serwaa","ama.serwaa@swma.gov.gh","$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi","+233548531963","Dwinase Office","","","active","","2025-04-27 10:50:40","");



DROP TABLE IF EXISTS `issue_comments`;

CREATE TABLE `issue_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `officer_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  KEY `officer_id` (`officer_id`)) REFERENCES `issues` (`id`) ON DELETE CASCADE) REFERENCES `field_officers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `issue_comments` VALUES("1","9","1","gdgsgrtjwdnbwwrgrwh","2025-04-29 16:20:02");
INSERT INTO `issue_comments` VALUES("2","11","1","This issue already exists, kindly delete it","2025-04-30 19:53:51");
INSERT INTO `issue_comments` VALUES("3","7","1","Hello there how are you doing","2025-04-30 19:59:18");
INSERT INTO `issue_comments` VALUES("4","8","1","Thanks","2025-04-30 20:17:37");
INSERT INTO `issue_comments` VALUES("5","11","1","Alright noted","2025-04-30 20:19:55");
INSERT INTO `issue_comments` VALUES("6","9","1","Thank you, I\'m waiting for furthur information","2025-05-01 11:13:14");



DROP TABLE IF EXISTS `issue_entity_assignments`;

CREATE TABLE `issue_entity_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `role` varchar(100) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_entity` (`issue_id`,`entity_id`),
  KEY `entity_id` (`entity_id`),
  KEY `assigned_by` (`assigned_by`)) REFERENCES `issues` (`id`) ON DELETE CASCADE) REFERENCES `entities` (`id`) ON DELETE CASCADE) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `issue_photos`;

CREATE TABLE `issue_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `photo_url` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  CONSTRAINT `issue_photos_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `issue_photos` VALUES("1","11","/uploads/issues/11/1745911663_ChatGPT Image Apr 25, 2025, 06_36_30 AM.png","","2025-04-29 07:27:43");
INSERT INTO `issue_photos` VALUES("2","11","/uploads/issues/11/1745911664_491934620_122157533246506946_8161682865869586109_n.jpg","","2025-04-29 07:27:44");
INSERT INTO `issue_photos` VALUES("3","11","/uploads/issues/11/1745911664_photo_2025-04-24_13-19-04.jpg","","2025-04-29 07:27:44");
INSERT INTO `issue_photos` VALUES("4","11","/uploads/issues/11/1745911666_Screenshot 2025-04-24 122817.jpg","","2025-04-29 07:27:46");
INSERT INTO `issue_photos` VALUES("5","11","/uploads/issues/11/1745911667_photo_2025-03-31_15-46-35.jpg","","2025-04-29 07:27:48");
INSERT INTO `issue_photos` VALUES("6","11","/uploads/issues/11/1745911671_image1.jpg","","2025-04-29 07:27:51");
INSERT INTO `issue_photos` VALUES("12","9","/uploads/issues/9/1745981657_dfdf1MS_5475@0,33x~2.jpg","","2025-04-30 02:54:17");
INSERT INTO `issue_photos` VALUES("13","9","/uploads/issues/9/1745981657_IMG_2229.jpg","","2025-04-30 02:54:17");
INSERT INTO `issue_photos` VALUES("14","9","/uploads/issues/9/1745981657_IMG-20250221-WA0121.jpg","","2025-04-30 02:54:18");
INSERT INTO `issue_photos` VALUES("15","9","/uploads/issues/9/1745983835_dfdf1MS_5475@0,33x~2.jpg","","2025-04-30 03:30:35");
INSERT INTO `issue_photos` VALUES("16","9","/uploads/issues/9/1745983835_IMG_2229.jpg","","2025-04-30 03:30:35");
INSERT INTO `issue_photos` VALUES("17","9","/uploads/issues/9/1745983836_IMG-20250221-WA0121.jpg","","2025-04-30 03:30:36");



DROP TABLE IF EXISTS `issue_updates`;

CREATE TABLE `issue_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `officer_id` int(11) DEFAULT NULL,
  `update_text` text NOT NULL,
  `status_change` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  KEY `officer_id` (`officer_id`)) REFERENCES `issues` (`id`) ON DELETE CASCADE) REFERENCES `field_officers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `issue_updates` VALUES("1","8","1","Issue status changed from In Progress to Resolved.","resolved","2025-04-30 19:45:00");
INSERT INTO `issue_updates` VALUES("2","6","1","Issue status changed from Pending to Under Review.","under_review","2025-04-30 19:45:52");
INSERT INTO `issue_updates` VALUES("3","6","1","Issue status changed from Under Review to In Progress.","in_progress","2025-04-30 19:46:17");
INSERT INTO `issue_updates` VALUES("4","6","1","Issue status changed from In Progress to Resolved.","resolved","2025-04-30 19:46:31");
INSERT INTO `issue_updates` VALUES("5","9","1","Issue status changed from Pending to Under Review.","under_review","2025-04-30 19:46:58");
INSERT INTO `issue_updates` VALUES("6","11","1","A new comment was added to this issue.","","2025-04-30 19:53:51");
INSERT INTO `issue_updates` VALUES("7","7","1","A new comment was added to this issue.","","2025-04-30 19:59:18");
INSERT INTO `issue_updates` VALUES("8","9","1","Issue status changed from Under Review to In Progress.","in_progress","2025-05-01 11:02:12");
INSERT INTO `issue_updates` VALUES("9","9","1","This is a custom update, we are currently working on it","","2025-05-01 11:02:39");
INSERT INTO `issue_updates` VALUES("10","9","1","Sure","","2025-05-01 11:24:36");
INSERT INTO `issue_updates` VALUES("11","9","1","Issue status changed from In Progress to Resolved.","resolved","2025-05-01 17:24:00");
INSERT INTO `issue_updates` VALUES("12","7","1","Issue status changed from Under Review to In Progress.","in_progress","2025-05-01 17:24:22");
INSERT INTO `issue_updates` VALUES("13","7","1","Issue status changed from In Progress to Resolved.","resolved","2025-05-01 17:24:26");
INSERT INTO `issue_updates` VALUES("14","11","1","Issue status changed from Pending to Under Review.","under_review","2025-05-01 17:24:53");
INSERT INTO `issue_updates` VALUES("15","11","1","Issue status changed from Under Review to In Progress.","in_progress","2025-05-01 17:24:58");
INSERT INTO `issue_updates` VALUES("16","11","1","Issue status changed from In Progress to Resolved.","resolved","2025-05-01 17:25:02");
INSERT INTO `issue_updates` VALUES("17","6","1","Issue is now under review.","under_review","2025-05-01 18:04:30");
INSERT INTO `issue_updates` VALUES("18","6","1","Issue is now in progress.","in_progress","2025-05-01 18:04:40");
INSERT INTO `issue_updates` VALUES("19","6","1","Issue resolved: This has been resolved completely and done, thanks for your prompt acion","resolved","2025-05-01 18:05:16");
INSERT INTO `issue_updates` VALUES("20","7","1","Sorry, this issue has already been reported, thank you for your prompt action","rejected","2025-05-01 18:06:15");



DROP TABLE IF EXISTS `issues`;

CREATE TABLE `issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `electoral_area_id` int(11) DEFAULT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('pending','under_review','in_progress','resolved','rejected') NOT NULL DEFAULT 'pending',
  `officer_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `people_affected` int(11) DEFAULT NULL,
  `budget_estimate` decimal(10,2) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `officer_id` (`officer_id`),
  KEY `electoral_area_id` (`electoral_area_id`),
  KEY `supervisor_id` (`supervisor_id`)) REFERENCES `field_officers` (`id`) ON DELETE CASCADE) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL) REFERENCES `field_officers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `issues` VALUES("6","Broken Bridge","The wooden bridge connecting Ahokwa to the main road has collapsed after heavy rainfall.","Ahokwa Junction","9","low","resolved","1","","5000","","This has been resolved completely and done, thanks for your prompt acion","0","2025-02-25 12:05:21","2025-05-02 08:26:22","2025-05-01 18:05:17");
INSERT INTO `issues` VALUES("7","Water Pollution","The community water source shows signs of contamination. Residents reporting stomach issues.","Wiawso Central","1","high","rejected","1","","1200","","","","2025-03-22 12:05:21","2025-05-02 08:26:30","2025-05-01 17:24:26");
INSERT INTO `issues` VALUES("8","School Roof Damage","Roof of the primary school classroom block damaged by recent storm.","Dwinase Basic School","2","medium","resolved","1","","350","","","","2025-05-17 12:05:21","2025-05-02 08:26:48","2025-04-30 19:45:00");
INSERT INTO `issues` VALUES("9","Road Potholes","Multiple deep potholes on the main market road causing accidents.","Market Road","3","critical","resolved","1","","2000","","","0","2025-04-24 12:05:21","2025-01-01 17:24:00","2025-05-01 17:24:00");
INSERT INTO `issues` VALUES("11","sgdcgds","sdsdas","sdsdfsd","1","medium","resolved","1","","322","","","0","2025-04-29 07:27:43","2025-05-01 17:25:02","2025-05-01 17:25:02");



DROP TABLE IF EXISTS `newsletter_subscribers`;

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `officers`;

CREATE TABLE `officers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `office_location` varchar(255) NOT NULL,
  `electoral_areas` text DEFAULT NULL,
  `role` enum('field_officer','supervisor','administrator','mp') NOT NULL DEFAULT 'field_officer',
  `profile_pic` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `officers` VALUES("1","Gilbert","kwamegilbert1114@gmail.com","$2y$10$RAdY0cLPMOmVl0dcXUytEOOU.VDFj7Pw2WvpcfC3ipulhfAqgur7y","0541436414","Kumasi","","field_officer","","active","2025-04-26 20:18:25","2025-04-26 22:16:41","");



DROP TABLE IF EXISTS `pa_supervisor_relationships`;

CREATE TABLE `pa_supervisor_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pa_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `relationship_type` enum('mp','mce','other') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pa_supervisor` (`pa_id`,`supervisor_id`),
  KEY `supervisor_id` (`supervisor_id`)) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE) REFERENCES `supervisors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `personal_assistants`;

CREATE TABLE `personal_assistants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `personal_assistants` VALUES("1","Kwame Gilbert","kwamegilbert1114@gmail.com","$2y$10$mCCndpdX2q2cAl0DP36JYOxogjHFc6FsevgwGbx7wtRW2NZ4J8rDS","+233277889900","Municipal Assembly","MP Office","","active","2025-05-03 17:57:10","2025-04-27 10:50:40","2025-05-03 17:57:10");
INSERT INTO `personal_assistants` VALUES("2","Daniel Kofi","daniel.kofi@swma.gov.gh","$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi","+233244789012","Municipal Assembly","MCE Office","","active","","2025-04-27 10:50:40","");



DROP TABLE IF EXISTS `project_comments`;

CREATE TABLE `project_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `pa_id` int(11) DEFAULT NULL,
  `officer_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `pa_id` (`pa_id`),
  KEY `officer_id` (`officer_id`)) REFERENCES `projects` (`id`) ON DELETE CASCADE) REFERENCES `personal_assistants` (`id`) ON DELETE SET NULL) REFERENCES `field_officers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `project_entity_assignments`;

CREATE TABLE `project_entity_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `role` varchar(100) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_entity` (`project_id`,`entity_id`),
  KEY `entity_id` (`entity_id`),
  KEY `assigned_by` (`assigned_by`)) REFERENCES `projects` (`id`) ON DELETE CASCADE) REFERENCES `entities` (`id`) ON DELETE CASCADE) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `project_photos`;

CREATE TABLE `project_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `photo_url` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `project_photos_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `project_updates`;

CREATE TABLE `project_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `pa_id` int(11) NOT NULL,
  `update_text` text NOT NULL,
  `status_change` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `pa_id` (`pa_id`)) REFERENCES `projects` (`id`) ON DELETE CASCADE) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `projects`;

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `electoral_area_id` int(11) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `sector` varchar(100) NOT NULL DEFAULT 'General',
  `people_benefitted` int(11) DEFAULT NULL,
  `budget_allocation` decimal(15,2) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `status` enum('planned','ongoing','completed','cancelled') NOT NULL DEFAULT 'planned',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `pa_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `views` int(11) NOT NULL DEFAULT 0,
  `progress` int(11) NOT NULL DEFAULT 0 COMMENT 'Project completion percentage',
  `slug` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `electoral_area_id` (`electoral_area_id`),
  KEY `pa_id` (`pa_id`),
  KEY `supervisor_id` (`supervisor_id`),
  KEY `idx_project_sector` (`sector`),
  KEY `idx_project_status` (`status`),
  KEY `idx_project_featured` (`featured`)) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE) REFERENCES `supervisors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS `supervisors`;

CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` enum('mp','mce','dce','other') NOT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `term_start` date DEFAULT NULL,
  `term_end` date DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `supervisors` VALUES("1","Hon. John Doe","mp@swma.gov.gh","$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi","+233302000001","mp","Parliament House","","","","active","","2025-04-27 10:50:40","");
INSERT INTO `supervisors` VALUES("2","Mrs. Jane Smith","mce@swma.gov.gh","$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi","+233302000002","mce","Municipal Assembly","","","","active","","2025-04-27 10:50:40","");



DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('field_officer','pa','mce') NOT NULL DEFAULT 'field_officer',
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `electoral_area` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES("1","Yolanda Abena Juliet Donkoh","iamyola@gmail.com","$2y$10$Ercofs31rnBmptU8fC6Md.FgVotK1jZhmfZ3NjqyvSyV4IL8kzKYG","field_officer","0541436414","","","","active","2025-04-27 10:42:28","2025-04-27 10:42:28","");





-- Constraints
CONSTRAINT `employment_opportunities_ibfk_1` FOREIGN KEY (`electoral_area_id`) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL;
CONSTRAINT `employment_opportunities_ibfk_2` FOREIGN KEY (`pa_id`) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE;
CONSTRAINT `issue_comments_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE;
CONSTRAINT `issue_entity_assignments_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE;
CONSTRAINT `issue_entity_assignments_ibfk_2` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE;
CONSTRAINT `issue_updates_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE;
CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`officer_id`) REFERENCES `field_officers` (`id`) ON DELETE CASCADE;
CONSTRAINT `issues_ibfk_2` FOREIGN KEY (`electoral_area_id`) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL;
CONSTRAINT `pa_supervisor_relationships_ibfk_1` FOREIGN KEY (`pa_id`) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE;
CONSTRAINT `project_comments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
CONSTRAINT `project_comments_ibfk_2` FOREIGN KEY (`pa_id`) REFERENCES `personal_assistants` (`id`) ON DELETE SET NULL;
CONSTRAINT `project_entity_assignments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
CONSTRAINT `project_entity_assignments_ibfk_2` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE;
CONSTRAINT `project_updates_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`electoral_area_id`) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL;
CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`pa_id`) REFERENCES `personal_assistants` (`id`) ON DELETE CASCADE;
