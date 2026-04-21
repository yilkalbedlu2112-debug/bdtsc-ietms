-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 12:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bdtsc_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`, `timestamp`) VALUES
(1, 2, 'Login', 'User logged into the system', '::1', '2026-04-05 20:42:48', '2026-04-05 20:37:41'),
(2, 2, 'Login', 'User logged into the system', '::1', '2026-04-05 22:05:38', '2026-04-05 22:05:38'),
(3, 2, 'Login', 'User logged into the system', '::1', '2026-04-06 08:06:12', '2026-04-06 08:06:12'),
(4, 2, 'Login', 'User logged into the system', '::1', '2026-04-06 08:30:13', '2026-04-06 08:30:13'),
(5, 2, 'Login', 'User logged into the system', '::1', '2026-04-06 08:45:20', '2026-04-06 08:45:20'),
(6, 2, 'Login', 'User logged into the system', '::1', '2026-04-07 08:40:15', '2026-04-07 08:40:15'),
(7, 2, 'User Registration', 'Registered new user: ይልቃል በድሉ as Employee', '::1', '2026-04-07 09:22:26', '2026-04-07 09:22:26'),
(8, 11, 'Login', 'User logged into the system', '::1', '2026-04-07 09:23:11', '2026-04-07 09:23:11'),
(9, 2, 'Login', 'User logged into the system', '::1', '2026-04-07 09:24:37', '2026-04-07 09:24:37'),
(10, 2, 'User Update', 'Updated user: እመቤት ከበደ (ID: 7)', '::1', '2026-04-07 09:26:19', '2026-04-07 09:26:19'),
(11, 2, 'Password Reset', 'Password Reset for ሙሉ ጎጃም (ID: 8)', '::1', '2026-04-07 09:26:48', '2026-04-07 09:26:48'),
(12, 2, 'Password Reset', 'Password Reset for እመቤት ከበደ (ID: 7)', '::1', '2026-04-07 09:27:43', '2026-04-07 09:27:43'),
(13, 2, 'User Update', 'Updated user: እመቤት ከበደ (ID: 7)', '::1', '2026-04-07 09:27:51', '2026-04-07 09:27:51'),
(14, 2, 'User Update', 'Updated user: እመቤት ከበደ (ID: 7)', '::1', '2026-04-07 09:27:57', '2026-04-07 09:27:57'),
(15, 2, 'User Update', 'Updated user: እመቤት ከበደ (ID: 7)', '::1', '2026-04-07 09:28:02', '2026-04-07 09:28:02'),
(16, 7, 'Login', 'User logged into the system', '::1', '2026-04-07 09:28:47', '2026-04-07 09:28:47'),
(17, 2, 'Login', 'User logged into the system', '::1', '2026-04-07 09:31:00', '2026-04-07 09:31:00'),
(18, 2, 'Login', 'User logged into the system', '::1', '2026-04-07 09:54:08', '2026-04-07 09:54:08'),
(19, 2, 'Login', 'User logged into the system', '::1', '2026-04-07 19:52:40', '2026-04-07 19:52:40'),
(20, 7, 'Login', 'User logged into the system', '::1', '2026-04-07 19:53:47', '2026-04-07 19:53:47'),
(21, 7, 'Login', 'User logged into the system', '::1', '2026-04-08 06:13:33', '2026-04-08 06:13:33'),
(22, 7, 'Login', 'User logged into the system', '::1', '2026-04-08 09:19:45', '2026-04-08 09:19:45'),
(23, 2, 'Login', 'User logged into the system', '::1', '2026-04-08 10:04:38', '2026-04-08 10:04:38'),
(24, 2, 'Login', 'User logged into the system', '::1', '2026-04-08 17:51:58', '2026-04-08 17:51:58'),
(25, 7, 'Login', 'User logged into the system', '::1', '2026-04-08 17:55:16', '2026-04-08 17:55:16'),
(26, 2, 'Login', 'User logged into the system', '::1', '2026-04-08 21:04:07', '2026-04-08 21:04:07'),
(27, 2, 'Password Reset', 'Password Reset for ታደሰ በቀለ (ID: 9)', '::1', '2026-04-08 21:05:33', '2026-04-08 21:05:33'),
(28, 2, 'Password Reset', 'Password Reset for ሙሉ ጎጃም (ID: 8)', '::1', '2026-04-08 21:05:40', '2026-04-08 21:05:40'),
(29, 8, 'Login', 'User logged into the system', '::1', '2026-04-08 21:06:07', '2026-04-08 21:06:07'),
(30, 7, 'Login', 'User logged into the system', '::1', '2026-04-08 21:06:58', '2026-04-08 21:06:58'),
(31, 2, 'Login', 'User logged into the system', '::1', '2026-04-08 21:08:46', '2026-04-08 21:08:46'),
(32, 7, 'Login', 'User logged into the system', '::1', '2026-04-08 21:30:46', '2026-04-08 21:30:46'),
(33, 2, 'Login', 'User logged into the system', '::1', '2026-04-08 21:41:02', '2026-04-08 21:41:02'),
(34, 2, 'User Registration', 'Registered new user: Yilkal Getnet as Deputy General Manager', '::1', '2026-04-08 22:26:10', '2026-04-08 22:26:10'),
(35, 12, 'Login', 'User logged into the system', '::1', '2026-04-08 22:26:29', '2026-04-08 22:26:29'),
(36, 12, 'Security', 'User updated their password via Profile Manager.', '::1', '2026-04-08 22:28:32', '2026-04-08 22:28:32'),
(37, 12, 'Login', 'User logged into the system', '::1', '2026-04-08 22:29:01', '2026-04-08 22:29:01'),
(38, 2, 'Login', 'User logged into the system', '::1', '2026-04-09 05:24:21', '2026-04-09 05:24:21'),
(39, 2, 'Login', 'User logged into the system', '::1', '2026-04-09 06:51:25', '2026-04-09 06:51:25'),
(40, 12, 'Login', 'User logged into the system', '::1', '2026-04-09 07:12:27', '2026-04-09 07:12:27'),
(41, 2, 'Login', 'User logged into the system', '::1', '2026-04-09 07:27:40', '2026-04-09 07:27:40'),
(42, 2, 'User Update', 'Updated user: Yilkal Bedlu (ID: 2)', '::1', '2026-04-09 07:41:23', '2026-04-09 07:41:23'),
(43, 2, 'User Update', 'Updated user: ሙሉ ጎጃም (ID: 8)', '::1', '2026-04-09 07:45:57', '2026-04-09 07:45:57'),
(44, 2, 'User Update', 'Updated user: አስማማው አንተነህ (ID: 6)', '::1', '2026-04-09 07:46:57', '2026-04-09 07:46:57'),
(45, 2, 'Password Reset', 'Password Reset for አስማማው አንተነህ (ID: 6)', '::1', '2026-04-09 07:47:23', '2026-04-09 07:47:23'),
(46, 2, 'Password Reset', 'Password Reset for ካሳሁን ዋሴ (ID: 4)', '::1', '2026-04-09 07:47:34', '2026-04-09 07:47:34'),
(47, 2, 'User Update', 'Updated user: እመቤት ከበደ (ID: 7)', '::1', '2026-04-09 07:48:22', '2026-04-09 07:48:22'),
(48, 2, 'Password Reset', 'Password Reset for ዮናስ አለሙ (ID: 5)', '::1', '2026-04-09 07:48:56', '2026-04-09 07:48:56'),
(49, 4, 'Login', 'User logged into the system', '::1', '2026-04-09 07:49:35', '2026-04-09 07:49:35'),
(50, 2, 'Login', 'User logged into the system', '::1', '2026-04-09 07:52:44', '2026-04-09 07:52:44'),
(51, 6, 'Login', 'User logged into the system', '::1', '2026-04-09 07:59:11', '2026-04-09 07:59:11'),
(52, 7, 'Login', 'User logged into the system', '::1', '2026-04-09 08:22:37', '2026-04-09 08:22:37'),
(53, 6, 'Login', 'User logged into the system', '::1', '2026-04-09 08:30:31', '2026-04-09 08:30:31'),
(54, 7, 'Login', 'User logged into the system', '::1', '2026-04-09 08:31:53', '2026-04-09 08:31:53'),
(55, 5, 'Login', 'User logged into the system', '::1', '2026-04-09 08:44:27', '2026-04-09 08:44:27'),
(56, 5, 'Login', 'User logged into the system', '::1', '2026-04-09 08:48:57', '2026-04-09 08:48:57'),
(57, 12, 'Login', 'User logged into the system', '::1', '2026-04-09 18:21:12', '2026-04-09 18:21:12'),
(58, 6, 'Login', 'User logged into the system', '::1', '2026-04-09 18:24:31', '2026-04-09 18:24:31'),
(59, 6, 'Login', 'User logged into the system', '::1', '2026-04-09 18:26:34', '2026-04-09 18:26:34'),
(60, 12, 'Login', 'User logged into the system', '::1', '2026-04-09 18:33:00', '2026-04-09 18:33:00'),
(61, 2, 'Login', 'User logged into the system', '::1', '2026-04-09 18:38:12', '2026-04-09 18:38:12'),
(62, 2, 'Login', 'User logged into the system', '::1', '2026-04-09 18:41:44', '2026-04-09 18:41:44'),
(63, 2, 'User Update', 'Updated user: Beyene Gebeyaw (ID: 8)', '::1', '2026-04-09 18:44:29', '2026-04-09 18:44:29'),
(64, 8, 'Login', 'User logged into the system', '::1', '2026-04-09 18:45:14', '2026-04-09 18:45:14'),
(65, 8, 'Login', 'User logged into the system', '::1', '2026-04-09 19:05:28', '2026-04-09 19:05:28'),
(66, 2, 'Login', 'User logged into the system', '::1', '2026-04-09 19:07:25', '2026-04-09 19:07:25'),
(67, 2, 'User Registration', 'Registered new user: Abebe Abebaw as Employee', '::1', '2026-04-09 19:12:03', '2026-04-09 19:12:03'),
(68, 13, 'Login', 'User logged into the system', '::1', '2026-04-09 19:21:16', '2026-04-09 19:21:16'),
(69, 2, 'Login', 'User logged into the system', '::1', '2026-04-09 19:55:21', '2026-04-09 19:55:21'),
(70, 2, 'User Registration', 'Registered new user: DESALEGN ABEBE as Department Manager', '::1', '2026-04-09 19:57:51', '2026-04-09 19:57:51'),
(71, 2, 'User Registration', 'Registered new user: TEWABE GETU as Department Manager', '::1', '2026-04-09 19:59:25', '2026-04-09 19:59:25'),
(72, 2, 'User Update', 'Updated user: BAHIRU ADMASSIE (ID: 3)', '::1', '2026-04-09 20:02:00', '2026-04-09 20:02:00'),
(73, 2, 'User Update', 'Updated user: HONELEGN DIRES (ID: 5)', '::1', '2026-04-09 20:03:56', '2026-04-09 20:03:56'),
(74, 2, 'User Update', 'Updated user: ASCHALEW MULUALEM (ID: 2)', '::1', '2026-04-09 20:56:05', '2026-04-09 20:56:05'),
(75, 15, 'Login', 'User logged into the system', '::1', '2026-04-09 22:28:16', '2026-04-09 22:28:16');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_type` enum('Production','Support') DEFAULT 'Production',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_name`, `dept_type`, `description`, `created_at`) VALUES
(1, 'General Management', 'Production', 'ለጥጥ ፈትል ዝግጅት የሚሰራ ክፍል', '2026-04-04 20:30:03'),
(2, 'Engineering Department', 'Production', 'ብልሽት ሲያጋጥም ጥገና የሚሰራ ክፍል ነው', '2026-04-04 20:32:14'),
(3, 'Garment Department', 'Production', 'ተፈትለው ይተዘጋጁት መስራት', '2026-04-04 20:34:13'),
(4, 'Strategy / Innovation', 'Production', NULL, '2026-04-06 10:32:45'),
(5, 'Planning', 'Production', NULL, '2026-04-06 10:32:45'),
(6, 'System Research & Development', 'Production', NULL, '2026-04-06 10:32:45'),
(7, 'Finance Department', 'Production', 'እያንዳንዱ ወጭ ገቢ መቆጣጠር ', '2026-04-05 09:42:39'),
(8, 'Spinning Department', 'Production', NULL, '2026-04-06 10:32:45'),
(9, 'Weaving Department', 'Production', NULL, '2026-04-06 10:32:45'),
(10, 'Processing Department', 'Production', NULL, '2026-04-06 10:32:45'),
(11, 'Audit & Inspection', 'Production', NULL, '2026-04-06 10:32:45'),
(12, 'Human Resource (HR)', 'Production', NULL, '2026-04-06 10:32:45'),
(13, 'Quality Assurance', 'Production', NULL, '2026-04-06 10:32:45'),
(14, 'Procurement & Property', 'Production', NULL, '2026-04-06 10:32:45'),
(15, 'Legal Service', 'Production', NULL, '2026-04-06 10:32:45'),
(16, 'Engineering', 'Support', NULL, '2026-04-08 22:07:52');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `task_type` enum('Production','Maintenance','Administrative','Quality') DEFAULT 'Production',
  `user_id` int(11) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `machine_name` varchar(100) NOT NULL,
  `issue_description` text NOT NULL,
  `feedback` text DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Pending Approval','Approved','Rejected','Assigned','In Progress','Completed') DEFAULT 'Pending Approval',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` datetime DEFAULT NULL,
  `severity` enum('Low','Medium','High') DEFAULT 'Low',
  `assigned_to_dept` enum('Internal','Engineering') DEFAULT 'Internal',
  `completion_notes` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `task_type`, `user_id`, `dept_id`, `assigned_to`, `supervisor_id`, `machine_name`, `issue_description`, `feedback`, `priority`, `status`, `created_at`, `due_date`, `severity`, `assigned_to_dept`, `completion_notes`, `is_verified`, `updated_at`) VALUES
(1, 'Production', 4, 2, NULL, NULL, 'Spinning Machine Belt ', 'Spinning Machine Belt Broken', NULL, 'Urgent', 'Completed', '2026-04-04 21:24:55', NULL, 'Low', 'Internal', NULL, 0, '2026-04-09 18:53:39');

-- --------------------------------------------------------

--
-- Table structure for table `production_reports`
--

CREATE TABLE `production_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `dept_id` int(11) NOT NULL,
  `machine_name` varchar(100) NOT NULL,
  `quantity_produced` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT 'Meters',
  `shift` varchar(20) DEFAULT NULL,
  `report_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_reports`
--

INSERT INTO `production_reports` (`id`, `user_id`, `dept_id`, `machine_name`, `quantity_produced`, `unit`, `shift`, `report_date`) VALUES
(1, 4, 3, 'Spinning Machine Belt ', 30.00, 'Meters', 'Night', '2026-04-05 12:39:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `role` enum('Admin','General Manager','Department Manager','Supervisor','Shift Leader','Employee','Technician','Deputy General Manager','Engineering Manager') DEFAULT 'Employee',
  `dept_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `reset_token`, `token_expiry`, `role`, `dept_id`, `status`, `last_login`) VALUES
(2, 'ASCHALEW MULUALEM', 'yilkalbedlu1993@gmail.com', '$2y$10$FtUh9Dhi56cMdZRXr5Rt4OnCriEVn71BWWe9D9vKFRO8Q9QZwHr.S', NULL, NULL, 'General Manager', NULL, 'Active', NULL),
(3, 'BAHIRU ADMASSIE', 'bahiru@gmail.com', '123456', NULL, NULL, 'Department Manager', 5, 'Active', NULL),
(4, 'ካሳሁን ዋሴ', 'kassa@gmail.com', '$2y$10$WsaQhT8m4pEpQ.sNdzRgL.m76krDyNCvM7v7GKjwojTf6kqN7hmOG', NULL, NULL, 'Employee', 3, 'Active', NULL),
(5, 'HONELEGN DIRES', 'honelegn@gmail.com', '$2y$10$JPqsOb2TfgpqztTu/ybsGuBIHpcqLF8jcFr6y9qTMOP7Ut5AyItLy', NULL, NULL, 'Department Manager', 3, 'Active', NULL),
(6, 'አስማማው አንተነህ', 'asmamaw@gmail.com', '$2y$10$SPt01MhwC0SQsPpRKS9pXeJ4t7603OGMTQ7Hp27OPc/o2HyMs3aWu', NULL, NULL, 'Shift Leader', 3, 'Active', NULL),
(7, 'እመቤት ከበደ', 'emebet@gmail.com', '$2y$10$j1yrFHxxhbIM6iRP0pw/5OfT7UJ4j.qMHV9tvRKreMOqtIXBeYGKW', '97b79e9cc62e00b2b99f6d871f507cc81057de9c8377aac87b1d3a5a89f1f645', '2026-04-08 10:44:02', 'Supervisor', 3, 'Active', NULL),
(8, 'Beyene Gebeyaw', 'beyenege845@gmail.com', '$2y$10$qL5zkCNF1BK9J0KA/p.DR.oVP12LfsfoXvcG3wZmfnIB4mZCEA9EG', '361ea0c06d85d7b8fbf89ca6b25b43f1e6c71b9230b0134b3862205133b90221', '2026-04-09 21:30:13', 'Engineering Manager', 2, 'Active', NULL),
(9, 'ታደሰ በቀለ', 'tadese@gmail.com', '$2y$10$1aDCHaJdHSsJDAIUptW4h.kUjTclmvFE5KIO//Yg1D4BnpfP38xHy', NULL, NULL, 'Technician', 2, 'Active', NULL),
(11, 'ይልቃል በድሉ', 'yilqal@gmail.com', '$2y$10$W761Af3ghTIw1dH/eXZ.iuLNBRAE32KD6x.SwXsQzvLf.eWn0tD72', NULL, NULL, 'Employee', 8, 'Active', NULL),
(12, 'Yilkal Getnet', 'yilkalbedlu2112@gmail.com', '$2y$10$TckWmA98baAigmdIEO6nWuanD5bH110FDEcrhMPtoWKgQ5dXEbXsi', NULL, NULL, 'Deputy General Manager', NULL, 'Active', NULL),
(13, 'Abebe Abebaw', 'abebe@gmail.com', '$2y$10$DUvkUvZX5esrE1WL98VUpueom3ph/UXdj0SOy1gf6yUykq6XKrCEG', NULL, NULL, 'Employee', 12, 'Active', NULL),
(14, 'DESALEGN ABEBE', 'desalegn@gmail.com', '$2y$10$ix2ek1DHMYOy0Vu1KZKh7O1yEbwVfl4oxDvV0YnDBuqv0xjSDBzZq', NULL, NULL, 'Department Manager', 14, 'Active', NULL),
(15, 'TEWABE GETU', 'tewabe@gmail.com', '$2y$10$xkGDe2lpBlSOyG1wM.cxcOPIjh6EqoJPsHyH6t4VlZeljjfgvdZvG', NULL, NULL, 'Department Manager', 12, 'Active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dept_name` (`dept_name`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `production_reports`
--
ALTER TABLE `production_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `production_reports`
--
ALTER TABLE `production_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `production_reports`
--
ALTER TABLE `production_reports`
  ADD CONSTRAINT `production_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `production_reports_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
