-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2025 at 06:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `constituency_db`
--
CREATE DATABASE IF NOT EXISTS `constituency_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `constituency_db`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `status`, `last_login`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$30feo3yC/r1PMwm4yCdsB.kmaM5F4cUOUyFdcY8xaTgTCIQQ1sA9u', 'System', 'Administrator', 'super_admin', 'active', '2025-04-24 03:43:24', NULL, '2025-04-23 11:36:27', '2025-04-24 03:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `admins_activity_log`
--

DROP TABLE IF EXISTS `admins_activity_log`;
CREATE TABLE `admins_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins_activity_log`
--

INSERT INTO `admins_activity_log` (`id`, `admin_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(8, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-23 11:47:21'),
(10, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-24 03:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `blog_comments`
--

DROP TABLE IF EXISTS `blog_comments`;
CREATE TABLE `blog_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `excerpt` text NOT NULL,
  `content` longtext NOT NULL,
  `image_url` text DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `author_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carousel_items`
--

DROP TABLE IF EXISTS `carousel_items`;
CREATE TABLE `carousel_items` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_items`
--

INSERT INTO `carousel_items` (`id`, `title`, `image_url`, `link`, `position`, `created_at`, `updated_at`) VALUES
(1, 'Visit to the Roman Catholic Church - Thank You Tour', '/uploads/carousel/1745497758_Screenshot 2025-04-24 122817.jpg', '#', 1, '2025-04-20 22:53:54', '2025-04-24 14:29:18'),
(2, 'Hon. Kofi Afful with Indigens on his Thank You tour', '/uploads/carousel/1745497375_photo_2025-04-24_12-08-11.jpg', '#', 2, '2025-04-20 22:53:54', '2025-04-24 14:22:55'),
(5, 'Thank You Visit - Hon. Benteh Afful', '/uploads/carousel/1745498125_photo_3_2025-04-24_12-33-28.jpg', 'https://www.swma.rf.gd', 3, '2025-04-24 14:35:25', '2025-04-24 14:35:25'),
(6, 'Happy Easter ', '/uploads/carousel/1745498280_photo_1_2025-04-24_12-33-28.jpg', 'https://www.swma.rf.gd', 4, '2025-04-24 14:38:00', '2025-04-24 14:38:00');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Gilbert Elikplim Kukah', 'kwamegilbert1114@gmail.com', '0541436414', 'This is a test compaint', 'Hello there, this is used to test the complaint', 'new', '2025-04-24 20:34:02', NULL),
(2, 'Gilbert Elikplim Kukah', 'kwamegilbert1114@gmail.com', '0541436414', 'This is a test compaint', 'sdzgxhjgh ,hmgchxgfncgnvmccngb', 'new', '2025-04-24 20:44:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `electoral_areas`
--

DROP TABLE IF EXISTS `electoral_areas`;
CREATE TABLE `electoral_areas` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `constituency` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `electoral_areas`
--

INSERT INTO `electoral_areas` (`id`, `name`, `constituency`, `region`, `created_at`) VALUES
(1, 'Anhwiam', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(2, 'Dwinase', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(3, 'Aboduam', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(4, 'Asawinso', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(5, 'Boako', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(6, 'Asempaneye', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(7, 'Nsawora', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(8, 'Kojina', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(9, 'Sefwi Wiawso Central', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30'),
(10, 'Yawkrom', 'Sefwi Wiawso', 'Western North', '2025-04-27 14:26:30');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `slug`, `description`, `start_date`, `end_date`, `event_time`, `location`, `image_url`, `created_at`) VALUES
(1, 'Town Hall Meeting', 'town-hall-meeting', 'Open forum with the MP to discuss constituency priorities.', '2025-05-10', '2025-05-10', '05:26:20', 'Sefwi Wiawso Municipal Hall', 'https://via.placeholder.com/800x400?text=Town+Hall+Meeting', '2025-04-21 01:05:47'),
(2, 'Community Clean‑Up Exercise', 'community-clean-up-exercise', 'Join hands to clean and beautify our neighborhoods.', '2025-06-15', '2025-06-15', '09:13:38', 'Downtown Sefwi Wiawso', 'https://via.placeholder.com/800x400?text=Clean-Up+Exercise', '2025-04-21 01:05:47'),
(3, 'Health Outreach Fair', 'health-outreach-fair', 'Free medical screenings and health education for all ages.', '2025-07-01', '2025-07-01', '04:16:35', 'Central Clinic Grounds', 'https://via.placeholder.com/800x400?text=Health+Fair', '2025-04-21 01:05:47'),
(4, 'Scholarship Application Workshop', 'scholarship-application-workshop', 'Guidance on applying to national and international scholarships.', '2025-08-05', '2025-08-05', '19:27:17', 'Municipal Education Office', 'https://via.placeholder.com/800x400?text=Scholarship+Workshop', '2025-04-21 01:05:47'),
(5, 'ICT Skills Training', 'ict-skills-training', 'Hands‑on sessions in basic computing and coding for youth.', '2025-06-20', '2025-06-22', '20:27:32', 'Tech Lab, Sefwi Wiawso Polytechnic', 'https://via.placeholder.com/800x400?text=ICT+Training', '2025-04-21 01:05:47'),
(6, 'Farmers Capacity Building', 'farmers-capacity-building', 'Workshops on modern farming techniques and agri‑business.', '2025-07-15', '2025-07-16', '09:27:47', 'Agro‑Extension Center', 'https://via.placeholder.com/800x400?text=Farmers+Training', '2025-04-21 01:05:47'),
(7, 'Youth Sports Day', 'youth-sports-day', 'Athletics and games promoting health and teamwork among youths.', '2025-08-20', '2025-08-20', '18:27:54', 'Municipal Sports Complex', 'https://via.placeholder.com/800x400?text=Sports+Day', '2025-04-21 01:05:47'),
(8, 'Road Safety Campaign', 'road-safety-campaign', 'Awareness drive on road rules and safe driving practices.', '2025-09-10', '2025-09-10', '00:15:01', 'Main Junction, Sefwi Wiawso', 'https://via.placeholder.com/800x400?text=Road+Safety', '2025-04-21 01:05:47'),
(9, 'Cultural Heritage Festival', 'cultural-heritage-festival', '', '2024-01-10', '2024-12-27', NULL, 'Open Grounds, Wiawso', 'https://via.placeholder.com/800x400?text=Cultural+Festival', '2025-04-21 01:05:47');

-- --------------------------------------------------------

--
-- Table structure for table `field_officers`
--

DROP TABLE IF EXISTS `field_officers`;
CREATE TABLE `field_officers` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `field_officers`
--

INSERT INTO `field_officers` (`id`, `name`, `email`, `password`, `phone`, `office_location`, `electoral_area_id`, `profile_pic`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Kwame Mensah', 'kwame.mensah@swma.gov.gh', '$2y$10$1XiTMmdXVgtzRDfkVDPAauARGCab.Ntrsci8Py37H.pfiCoqMZloK', '+233242560140', 'Wiawso Central Office', NULL, NULL, 'active', '2025-04-28 11:39:13', '2025-04-27 10:50:40', '2025-04-28 11:39:13'),
(2, 'Ama Serwaa', 'ama.serwaa@swma.gov.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+233548531963', 'Dwinase Office', NULL, NULL, 'active', NULL, '2025-04-27 10:50:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

DROP TABLE IF EXISTS `issues`;
CREATE TABLE `issues` (
  `id` int(11) NOT NULL,
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
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `title`, `description`, `location`, `electoral_area_id`, `severity`, `status`, `officer_id`, `supervisor_id`, `people_affected`, `budget_estimate`, `resolution_notes`, `additional_notes`, `created_at`, `updated_at`, `resolved_at`) VALUES
(6, 'Broken Bridge', 'The wooden bridge connecting Ahokwa to the main road has collapsed after heavy rainfall.', 'Ahokwa Junction', 9, 'low', 'pending', 1, NULL, 5000, NULL, NULL, '0', '2025-04-25 12:05:21', '2025-04-27 17:41:24', NULL),
(7, 'Water Pollution', 'The community water source shows signs of contamination. Residents reporting stomach issues.', 'Wiawso Central', 1, 'high', 'under_review', 1, NULL, 1200, NULL, NULL, NULL, '2025-04-22 12:05:21', NULL, NULL),
(8, 'School Roof Damage', 'Roof of the primary school classroom block damaged by recent storm.', 'Dwinase Basic School', 2, 'medium', 'in_progress', 1, NULL, 350, NULL, NULL, NULL, '2025-04-17 12:05:21', NULL, NULL),
(9, 'Road Potholes', 'Multiple deep potholes on the main market road causing accidents.', 'Market Road', 3, 'critical', 'pending', 1, NULL, 2000, NULL, NULL, '0', '2025-04-24 12:05:21', '2025-04-27 16:53:22', NULL),
(11, 'sgdcgds', 'sdsdas', 'sdsdfsd', 1, 'medium', 'pending', 1, NULL, 322, NULL, NULL, '0', '2025-04-29 07:27:43', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `issue_comments`
--

DROP TABLE IF EXISTS `issue_comments`;
CREATE TABLE `issue_comments` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `officer_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issue_comments`
--

INSERT INTO `issue_comments` (`id`, `issue_id`, `officer_id`, `comment`, `created_at`) VALUES
(1, 9, 1, 'gdgsgrtjwdnbwwrgrwh', '2025-04-29 16:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `issue_photos`
--

DROP TABLE IF EXISTS `issue_photos`;
CREATE TABLE `issue_photos` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `photo_url` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issue_photos`
--

INSERT INTO `issue_photos` (`id`, `issue_id`, `photo_url`, `caption`, `uploaded_at`) VALUES
(1, 11, '/uploads/issues/11/1745911663_ChatGPT Image Apr 25, 2025, 06_36_30 AM.png', NULL, '2025-04-29 07:27:43'),
(2, 11, '/uploads/issues/11/1745911664_491934620_122157533246506946_8161682865869586109_n.jpg', NULL, '2025-04-29 07:27:44'),
(3, 11, '/uploads/issues/11/1745911664_photo_2025-04-24_13-19-04.jpg', NULL, '2025-04-29 07:27:44'),
(4, 11, '/uploads/issues/11/1745911666_Screenshot 2025-04-24 122817.jpg', NULL, '2025-04-29 07:27:46'),
(5, 11, '/uploads/issues/11/1745911667_photo_2025-03-31_15-46-35.jpg', NULL, '2025-04-29 07:27:48'),
(6, 11, '/uploads/issues/11/1745911671_image1.jpg', NULL, '2025-04-29 07:27:51');

-- --------------------------------------------------------

--
-- Table structure for table `issue_updates`
--

DROP TABLE IF EXISTS `issue_updates`;
CREATE TABLE `issue_updates` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `officer_id` int(11) DEFAULT NULL,
  `update_text` text NOT NULL,
  `status_change` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `officers`
--

DROP TABLE IF EXISTS `officers`;
CREATE TABLE `officers` (
  `id` int(11) NOT NULL,
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
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officers`
--

INSERT INTO `officers` (`id`, `name`, `email`, `password`, `phone`, `office_location`, `electoral_areas`, `role`, `profile_pic`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Gilbert', 'kwamegilbert1114@gmail.com', '$2y$10$RAdY0cLPMOmVl0dcXUytEOOU.VDFj7Pw2WvpcfC3ipulhfAqgur7y', '0541436414', 'Kumasi', NULL, 'field_officer', NULL, 'active', '2025-04-26 20:18:25', '2025-04-26 22:16:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personal_assistants`
--

DROP TABLE IF EXISTS `personal_assistants`;
CREATE TABLE `personal_assistants` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_assistants`
--

INSERT INTO `personal_assistants` (`id`, `name`, `email`, `password`, `phone`, `office_location`, `department`, `profile_pic`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Grace Addo', 'grace.addo@swma.gov.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+233277889900', 'Municipal Assembly', 'MP Office', NULL, 'active', NULL, '2025-04-27 10:50:40', NULL),
(2, 'Daniel Kofi', 'daniel.kofi@swma.gov.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+233244789012', 'Municipal Assembly', 'MCE Office', NULL, 'active', NULL, '2025-04-27 10:50:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `sector` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planned','ongoing','completed') NOT NULL DEFAULT 'planned',
  `description` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `sector`, `location`, `start_date`, `end_date`, `status`, `description`, `images`, `featured`, `created_at`) VALUES
(1, 'Road Expansion Project', 'Infrastructure', 'Sefwi Wiawso', '2024-10-01', '2025-04-30', 'ongoing', 'Widening and resurfacing of the main municipal highway.', '[\"data:image/jpeg;base64,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\", \"data:image/jpeg;base64,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\", \"data:image/jpeg;base64,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\"]', 1, '2025-04-21 12:47:48');
INSERT INTO `projects` (`id`, `title`, `sector`, `location`, `start_date`, `end_date`, `status`, `description`, `images`, `featured`, `created_at`) VALUES
(2, 'Community Health Center', 'Health', 'Wiawso Central', '2024-06-15', NULL, 'ongoing', 'Construction of a new primary healthcare facility.', '[\"data:image/jpeg;base64,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\", \"data:image/jpeg;base64,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\"]', 1, '2025-04-21 12:47:48'),
(3, 'Market Rehabilitation', 'Commerce', 'Abodom Market', '2023-11-01', '2024-08-31', 'completed', 'Renovation of vendor stalls and upgrading sanitation facilities.', '[\"data:image/jpeg;base64,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\", \"data:image/jpeg;base64,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\"]', 1, '2025-04-21 12:47:48');

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

DROP TABLE IF EXISTS `supervisors`;
CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisors`
--

INSERT INTO `supervisors` (`id`, `name`, `email`, `password`, `phone`, `position`, `office_location`, `profile_pic`, `term_start`, `term_end`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Hon. John Doe', 'mp@swma.gov.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+233302000001', 'mp', 'Parliament House', NULL, NULL, NULL, 'active', NULL, '2025-04-27 10:50:40', NULL),
(2, 'Mrs. Jane Smith', 'mce@swma.gov.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+233302000002', 'mce', 'Municipal Assembly', NULL, NULL, NULL, 'active', NULL, '2025-04-27 10:50:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `profile_image`, `electoral_area`, `department`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'Yolanda Abena Juliet Donkoh', 'iamyola@gmail.com', '$2y$10$Ercofs31rnBmptU8fC6Md.FgVotK1jZhmfZ3NjqyvSyV4IL8kzKYG', 'field_officer', '0541436414', NULL, NULL, NULL, 'active', '2025-04-27 10:42:28', '2025-04-27 10:42:28', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admins_activity_log`
--
ALTER TABLE `admins_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `carousel_items`
--
ALTER TABLE `carousel_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `electoral_areas`
--
ALTER TABLE `electoral_areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `field_officers`
--
ALTER TABLE `field_officers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `electoral_area_id` (`electoral_area_id`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `officer_id` (`officer_id`),
  ADD KEY `electoral_area_id` (`electoral_area_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `issue_comments`
--
ALTER TABLE `issue_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `officer_id` (`officer_id`);

--
-- Indexes for table `issue_photos`
--
ALTER TABLE `issue_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`);

--
-- Indexes for table `issue_updates`
--
ALTER TABLE `issue_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `officer_id` (`officer_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `officers`
--
ALTER TABLE `officers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `personal_assistants`
--
ALTER TABLE `personal_assistants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admins_activity_log`
--
ALTER TABLE `admins_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `blog_comments`
--
ALTER TABLE `blog_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carousel_items`
--
ALTER TABLE `carousel_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `electoral_areas`
--
ALTER TABLE `electoral_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `field_officers`
--
ALTER TABLE `field_officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `issue_comments`
--
ALTER TABLE `issue_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `issue_photos`
--
ALTER TABLE `issue_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `issue_updates`
--
ALTER TABLE `issue_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `officers`
--
ALTER TABLE `officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `personal_assistants`
--
ALTER TABLE `personal_assistants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins_activity_log`
--
ALTER TABLE `admins_activity_log`
  ADD CONSTRAINT `admins_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD CONSTRAINT `blog_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `admins` (`id`);

--
-- Constraints for table `field_officers`
--
ALTER TABLE `field_officers`
  ADD CONSTRAINT `fo_electoral_area_fk` FOREIGN KEY (`electoral_area_id`) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`officer_id`) REFERENCES `field_officers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issues_ibfk_2` FOREIGN KEY (`electoral_area_id`) REFERENCES `electoral_areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `issues_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `field_officers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `issue_comments`
--
ALTER TABLE `issue_comments`
  ADD CONSTRAINT `issue_comments_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_comments_ibfk_2` FOREIGN KEY (`officer_id`) REFERENCES `field_officers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `issue_photos`
--
ALTER TABLE `issue_photos`
  ADD CONSTRAINT `issue_photos_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `issue_updates`
--
ALTER TABLE `issue_updates`
  ADD CONSTRAINT `issue_updates_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_updates_ibfk_2` FOREIGN KEY (`officer_id`) REFERENCES `field_officers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
