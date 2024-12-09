-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2024 at 10:04 PM
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
-- Database: `hospital_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `created_at`, `last_login`, `status`) VALUES
(4, 'alfonso', '$2y$10$mowKuQue8RT5aRECP.vKWurHsRVy4joS3ZTjh96QfYPsuYEI3o85S', '', '2024-12-04 20:39:48', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `appointment_time` time NOT NULL DEFAULT current_timestamp(),
  `status` enum('Scheduled','Completed','Cancelled','pending','confirmed','') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text NOT NULL,
  `diagnosis` text NOT NULL,
  `prescription` text NOT NULL,
  `added_by_type` enum('doctor','admin','patient','') NOT NULL DEFAULT 'patient',
  `reason` text NOT NULL,
  `unique_appointment` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `created_at`, `updated_at`, `notes`, `diagnosis`, `prescription`, `added_by_type`, `reason`, `unique_appointment`) VALUES
(31, 45, 4, '2024-12-14 13:16:00', '01:16:39', 'Scheduled', '2024-12-03 17:16:39', '2024-12-03 17:16:39', '', '', '', 'admin', '', NULL),
(32, 46, 9, '2024-12-05 12:00:00', '22:14:38', 'Scheduled', '2024-12-04 17:39:00', '2024-12-04 17:39:00', '', '', '', 'patient', 'tite', NULL),
(33, 46, 10, '2024-12-13 15:30:00', '02:27:58', 'Scheduled', '2024-12-04 18:37:35', '2024-12-04 18:27:58', 'asdasd', '', '', 'patient', 'asdasd', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_history`
--

CREATE TABLE `appointment_history` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `old_status` varchar(20) NOT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `complaint_text` text NOT NULL,
  `created_at` int(11) NOT NULL,
  `status` enum('active','resolved','','') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `patient_id`, `complaint_text`, `created_at`, `status`) VALUES
(2, 46, 'asdasdas', 2147483647, 'active'),
(3, 45, 'adasda', 2147483647, 'active'),
(4, 45, 'asdasda', 2147483647, 'active'),
(5, 45, 'asdasdasa - aadsbaasfaasd', 2147483647, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `specializations_id` int(11) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `statuses` enum('active','busy','off','leave','on break') DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_statuses` enum('pending','active','') DEFAULT 'pending',
  `specializations` varchar(100) NOT NULL DEFAULT 'Not Specified',
  `license_number` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `first_name`, `last_name`, `password`, `specializations_id`, `phone`, `email`, `created_at`, `statuses`, `updated_at`, `account_statuses`, `specializations`, `license_number`) VALUES
(4, 'Alfonso', 'Malig', '$2y$10$xTDadHkeK47qb.dRx4.Yt.J2Px1UOxfpu3X/n995VI9AF8Leoonla', NULL, '123456789', 'Alfonso.malig3156@gmail.com', '2024-12-03 16:39:11', 'active', '2024-11-28 06:12:03', 'active', 'neurologist', ''),
(8, 'ad', 'lebron', '$2y$10$bnwqzEmi4CYeThv3j3HPdeuewG429E2SCneYfO33/n/bhzHL.yMlC', NULL, '123123', 'adlebron@gmail.com', '2024-12-02 16:02:59', 'busy', '2024-12-02 15:21:32', 'active', 'Urology', ''),
(9, 'alfonsk', 'malik', '$2y$10$hRndLHPmJ2OhLcZmHXX/Eu8vVYZHkjrfEFdAFAfeAiwFXghxpVjRi', NULL, '234451132', 'alfonskmalik@gmail.com', '2024-12-03 17:35:55', 'active', '2024-12-03 16:43:23', 'active', 'Psychiatry', ''),
(10, 'Daniel', 'Dela Cruz', '$2y$10$ZzyZrv6082vYm.wLFJlAZOI0JyRaxExqIthSxI4cGOQ54bgtAlq2W', NULL, '09602020493', 'asdasdads@gmail.com', '2024-12-04 17:53:02', 'active', '2024-12-04 16:33:38', 'active', 'Gastroenterology', '');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `max_appointments` int(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_specializations`
--

CREATE TABLE `doctor_specializations` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_time_off`
--

CREATE TABLE `doctor_time_off` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `illness_symptoms`
--

CREATE TABLE `illness_symptoms` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `illness` text NOT NULL,
  `symptoms` text NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `illness_symptoms`
--

INSERT INTO `illness_symptoms` (`id`, `patient_id`, `illness`, `symptoms`, `date_recorded`) VALUES
(3, 45, 'adasda', 'aaa', '2024-12-04 19:42:37'),
(4, 45, 'adasda', 'aqwef', '2024-12-04 19:49:16'),
(5, 45, 'adasda', 'asdasdada', '2024-12-04 19:50:13'),
(6, 45, 'adasda', 'wewerwerwerwer', '2024-12-04 19:52:18'),
(7, 45, 'adasda', 'adsadas', '2024-12-04 19:55:21'),
(8, 45, 'adasda', 'qwtqwtgqwe', '2024-12-04 19:55:31'),
(9, 45, 'adasda', 'asdasdasdas', '2024-12-04 19:58:00'),
(10, 45, 'asdasdasa', 'asdasasasdsdasadsdaasd', '2024-12-04 19:58:12'),
(11, 45, 'asdadsas', 'adsdsda', '2024-12-04 19:59:29');

-- --------------------------------------------------------

--
-- Table structure for table `medical_certificates`
--

CREATE TABLE `medical_certificates` (
  `ID` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `recommendation` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','approved','rejected','') NOT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text NOT NULL,
  `rejection_reason` text NOT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `specializations` int(11) DEFAULT NULL,
  `doctor_remarks` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `symptoms` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_certificates`
--

INSERT INTO `medical_certificates` (`ID`, `patient_id`, `doctor_id`, `diagnosis`, `recommendation`, `start_date`, `end_date`, `status`, `approval_date`, `created_at`, `reason`, `rejection_reason`, `request_date`, `approved_by`, `appointment_id`, `specializations`, `doctor_remarks`, `approved_at`, `symptoms`) VALUES
(22, 45, 4, NULL, '', '2024-12-19', '2024-12-11', 'approved', NULL, '2024-12-02 16:33:15', 'nasdasdasda', '', '2024-12-03 00:33:15', 4, NULL, NULL, 'yes sir', '2024-12-02 16:33:35', NULL),
(23, 46, 4, NULL, '', '2024-12-05', '2024-12-12', 'pending', NULL, '2024-12-04 14:14:27', 'tite', '', '2024-12-04 22:14:27', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 46, 10, NULL, '', '2024-12-20', '2024-12-09', 'pending', NULL, '2024-12-04 18:31:43', 'asdasd', '', '2024-12-05 02:31:43', NULL, NULL, 28, NULL, NULL, NULL),
(25, 46, 10, NULL, '', '2024-12-05', '2024-12-06', 'approved', NULL, '2024-12-04 18:32:55', 'asdasd', '', '2024-12-05 02:32:55', 10, NULL, 28, 'asasd', '2024-12-04 18:33:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medical_status`
--

CREATE TABLE `medical_status` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `status` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_status`
--

INSERT INTO `medical_status` (`id`, `patient_id`, `status`, `created_at`) VALUES
(2, 46, 'asdasd', '2024-12-04 17:12:57'),
(3, 46, 'asdasdas', '2024-12-04 17:28:32'),
(4, 45, 'asdasd', '2024-12-04 17:28:35');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `civil_status` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `doctors_id` int(11) NOT NULL,
  `patients_login_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive','','') NOT NULL DEFAULT 'active',
  `age` int(11) NOT NULL,
  `descriptions` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `phone`, `address`, `create_at`, `civil_status`, `email`, `doctors_id`, `patients_login_id`, `created_at`, `status`, `age`, `descriptions`) VALUES
(45, 'ham', 'burger', '2003-12-05', 'Male', '123123', '', '2024-12-04 19:47:34', 'Single', 'hamburger@gmail.com', 0, 28, '2024-12-02 05:56:47', 'active', 0, ''),
(46, 'Daniel', 'Dela Cruz', '1942-01-12', 'Male', '09771209740', '', '2024-12-04 19:47:00', 'Single', '1234567x2x23@gmail.com', 0, 29, '2024-12-04 14:13:51', 'active', 0, ''),
(47, 'alfonso', 'malig', '2004-09-04', 'Male', '', '', '2024-12-04 20:13:03', '', 'alfonzo@gmail.com', 0, 30, '2024-12-04 20:13:03', 'active', 20, ''),
(48, 'asdlfonso', 'malig', '2003-11-04', 'Male', '13123213112', '', '2024-12-04 20:20:40', '', 'alfons023@gmail.com', 0, 31, '2024-12-04 20:20:40', 'active', 21, ''),
(49, 'lafonso', 'malig', '2015-03-20', 'Male', '13123213234', '', '2024-12-04 20:33:05', '', 'adalfonso@gmail.com', 0, 32, '2024-12-04 20:33:05', 'active', 9, '');

-- --------------------------------------------------------

--
-- Table structure for table `patients_login`
--

CREATE TABLE `patients_login` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `reset_token` varchar(64) NOT NULL,
  `reset_token_expiry` datetime NOT NULL DEFAULT current_timestamp(),
  `current_illness` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fk_patient_login` int(11) NOT NULL,
  `reset_attempts` int(11) NOT NULL DEFAULT 0,
  `date_of_birth` date NOT NULL,
  `age` int(11) NOT NULL,
  `contact` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients_login`
--

INSERT INTO `patients_login` (`id`, `first_name`, `last_name`, `password`, `email`, `address`, `reset_token`, `reset_token_expiry`, `current_illness`, `created_at`, `updated_at`, `fk_patient_login`, `reset_attempts`, `date_of_birth`, `age`, `contact`) VALUES
(28, 'ham', 'burger', '$2y$10$V9D5.zkFuictpvBLi7byXu3bXzsWexbUe3pvNRbwRYZh9xshlr3Sa', 'hamburger@gmail.com', '', '', '0000-00-00 00:00:00', '', '2024-12-02 05:56:47', '2024-12-02 05:56:47', 0, 0, '0000-00-00', 0, ''),
(29, 'Daniel', 'Dela Cruz', '$2y$10$rfSMqPFGPGjahjxGCVAAEO0acYy5iPIBBrk0hMd8Gb.bgZpLUZDAe', '1234567x2x23@gmail.com', '', '', '2024-12-04 22:13:51', '', '2024-12-04 14:13:51', '2024-12-04 14:13:51', 0, 0, '0000-00-00', 0, ''),
(30, 'alfonso', 'malig', '$2y$10$rQPOVB4MHXr33FexM1FXmeMBgVhI4GamWvvUkMF9J.hBbjZ2ZjjNm', 'alfonzo@gmail.com', '', '', '2024-12-05 04:13:03', '', '2024-12-04 20:13:03', '2024-12-04 20:13:03', 0, 0, '2004-09-04', 20, ''),
(31, 'asdlfonso', 'malig', '$2y$10$zqgiOdm7oC7XMgXlnPIYE./qacmP51dY2Pc6/cFL7R/xz82jpZmkW', 'alfons023@gmail.com', '', '', '2024-12-05 04:20:40', '', '2024-12-04 20:20:40', '2024-12-04 20:20:40', 0, 0, '2003-11-04', 21, '13123213112'),
(32, 'lafonso', 'malig', '$2y$10$NTS5dbweZegdT4iAwCEFpeaZMPqt5oMr7t7bBQWm2AsMirLSiqksG', 'adalfonso@gmail.com', '', '', '2024-12-05 04:33:05', '', '2024-12-04 20:33:05', '2024-12-04 20:33:05', 0, 0, '2015-03-20', 9, '13123213234');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `prescribed_date` datetime DEFAULT current_timestamp(),
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `medication` varchar(100) NOT NULL,
  `instructions` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `patient_id`, `doctor_id`, `medication_name`, `dosage`, `frequency`, `duration`, `notes`, `prescribed_date`, `status`, `created_at`, `updated_at`, `medication`, `instructions`) VALUES
(9, 46, 10, '', '1', '2', '3', NULL, '2024-12-05 01:14:20', 'active', '2024-12-04 17:14:20', '2024-12-04 17:14:20', 'tete', ''),
(10, 46, 8, '', 'asdasdas', 'adasd', 'asdasd', NULL, '2024-12-05 03:44:09', 'active', '2024-12-04 19:44:09', '2024-12-04 19:44:09', 'dasdas', 'asdasddas'),
(11, 45, 8, '', 'asdasdas', 'adasd', 'asdasd', NULL, '2024-12-05 03:49:58', 'active', '2024-12-04 19:49:58', '2024-12-04 19:49:58', 'dasdas', 'asdadsqewqqw');

-- --------------------------------------------------------

--
-- Table structure for table `specializations`
--

CREATE TABLE `specializations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specializations`
--

INSERT INTO `specializations` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(16, 'General Medicine', 'Primary healthcare and general medical treatment', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(17, 'Pediatrics', 'Medical care for infants, children, and adolescents', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(18, 'Cardiology', 'Diagnosis and treatment of heart conditions', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(19, 'Dermatology', 'Skin, hair, and nail conditions', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(20, 'Orthopedics', 'Musculoskeletal system and injuries', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(21, 'Neurology', 'Disorders of the nervous system', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(22, 'Psychiatry', 'Mental health and behavioral disorders', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(23, 'Obstetrics & Gynecology', 'Women\'s health and pregnancy care', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(24, 'Ophthalmology', 'Eye and vision care', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(25, 'ENT', 'Ear, nose, and throat specialists', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(26, 'Dentistry', 'Oral health and dental care', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(27, 'Endocrinology', 'Hormonal and metabolic disorders', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(28, 'Gastroenterology', 'Digestive system disorders', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(29, 'Pulmonology', 'Respiratory system disorders', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55'),
(30, 'Urology', 'Urinary tract and male reproductive system', 'active', '2024-11-28 07:24:55', '2024-11-28 07:24:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('doctor','admin','patient','') NOT NULL,
  `status` enum('active','inactive','','') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_date` (`appointment_date`,`patient_id`,`doctor_id`) USING BTREE,
  ADD UNIQUE KEY `unique_appointment` (`unique_appointment`),
  ADD KEY `FK_DOCTORSID` (`doctor_id`),
  ADD KEY `fK_doctors_id` (`patient_id`);

--
-- Indexes for table `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_appointments_id` (`appointment_id`),
  ADD KEY `FK_change_by` (`changed_by`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Fk_PATIENTIDS` (`patient_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctors_ibfk_1` (`specializations_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`doctor_id`,`day_of_week`);

--
-- Indexes for table `doctor_specializations`
--
ALTER TABLE `doctor_specializations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_doctor_spec` (`doctor_id`,`specialization_id`),
  ADD KEY `specialization_id` (`specialization_id`);

--
-- Indexes for table `doctor_time_off`
--
ALTER TABLE `doctor_time_off`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_dates` (`doctor_id`,`start_date`,`end_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `illness_symptoms`
--
ALTER TABLE `illness_symptoms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_patientsIDs_FK` (`patient_id`);

--
-- Indexes for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_patients_id_fk` (`patient_id`),
  ADD KEY `fk_doctor_id_fk` (`doctor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_request_date` (`request_date`),
  ADD KEY `fk_approve_by_fk` (`approved_by`),
  ADD KEY `fk_appointment_id_fk` (`appointment_id`),
  ADD KEY `fk_specializationsFk_` (`specializations`);

--
-- Indexes for table `medical_status`
--
ALTER TABLE `medical_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_patientsID_FK` (`patient_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `patients_login_fk` (`patients_login_id`);

--
-- Indexes for table `patients_login`
--
ALTER TABLE `patients_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_prescribed_date` (`prescribed_date`) USING BTREE;

--
-- Indexes for table `specializations`
--
ALTER TABLE `specializations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `appointment_history`
--
ALTER TABLE `appointment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_specializations`
--
ALTER TABLE `doctor_specializations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_time_off`
--
ALTER TABLE `doctor_time_off`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `illness_symptoms`
--
ALTER TABLE `illness_symptoms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `medical_status`
--
ALTER TABLE `medical_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `patients_login`
--
ALTER TABLE `patients_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `specializations`
--
ALTER TABLE `specializations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `FK_DOCTORS_IDfk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fK_doctors_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD CONSTRAINT `FK_appointments_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_change_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `Fk_PATIENTIDS` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`specializations_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_specializations`
--
ALTER TABLE `doctor_specializations`
  ADD CONSTRAINT `doctor_specializations_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_specializations_ibfk_2` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_time_off`
--
ALTER TABLE `doctor_time_off`
  ADD CONSTRAINT `doctor_time_off_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `illness_symptoms`
--
ALTER TABLE `illness_symptoms`
  ADD CONSTRAINT `fk_patientsIDs_FK` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  ADD CONSTRAINT `fk_appointment_id_fk` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_approve_by_fk` FOREIGN KEY (`approved_by`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_doctor_id_fk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_patients_id_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_specializationsFk_` FOREIGN KEY (`specializations`) REFERENCES `specializations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_specializationsfk` FOREIGN KEY (`specializations`) REFERENCES `specializations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medical_status`
--
ALTER TABLE `medical_status`
  ADD CONSTRAINT `fk_patientsID_FK` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_login_fk` FOREIGN KEY (`patients_login_id`) REFERENCES `patients_login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
