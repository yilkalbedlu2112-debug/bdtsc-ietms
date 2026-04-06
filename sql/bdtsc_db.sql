-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 01:12 PM
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
(5, 2, 'Login', 'User logged into the system', '::1', '2026-04-06 08:45:20', '2026-04-06 08:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_name`, `description`, `created_at`) VALUES
(1, 'General Management', 'ለጥጥ ፈትል ዝግጅት የሚሰራ ክፍል', '2026-04-04 20:30:03'),
(2, 'Engineering Department', 'ብልሽት ሲያጋጥም ጥገና የሚሰራ ክፍል ነው', '2026-04-04 20:32:14'),
(3, 'Garment Department', 'ተፈትለው ይተዘጋጁት መስራት', '2026-04-04 20:34:13'),
(4, 'Strategy / Innovation', NULL, '2026-04-06 10:32:45'),
(5, 'Planning', NULL, '2026-04-06 10:32:45'),
(6, 'System Research & Development', NULL, '2026-04-06 10:32:45'),
(7, 'Finance Department', 'እያንዳንዱ ወጭ ገቢ መቆጣጠር ', '2026-04-05 09:42:39'),
(8, 'Spinning Department', NULL, '2026-04-06 10:32:45'),
(9, 'Weaving Department', NULL, '2026-04-06 10:32:45'),
(10, 'Processing Department', NULL, '2026-04-06 10:32:45'),
(11, 'Audit & Inspection', NULL, '2026-04-06 10:32:45'),
(12, 'Human Resource (HR)', NULL, '2026-04-06 10:32:45'),
(13, 'Quality Assurance', NULL, '2026-04-06 10:32:45'),
(14, 'Procurement & Property', NULL, '2026-04-06 10:32:45'),
(15, 'Legal Service', NULL, '2026-04-06 10:32:45');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `machine_name` varchar(100) NOT NULL,
  `issue_description` text NOT NULL,
  `feedback` text DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Pending Approval','Approved','Rejected','Assigned','In Progress','Completed') DEFAULT 'Pending Approval',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `user_id`, `dept_id`, `assigned_to`, `supervisor_id`, `machine_name`, `issue_description`, `feedback`, `priority`, `status`, `created_at`) VALUES
(1, 4, 2, NULL, NULL, 'Spinning Machine Belt ', 'Spinning Machine Belt Broken', NULL, 'Urgent', 'Completed', '2026-04-04 21:24:55');

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
  `role` enum('General Manager','Deputy Manager','Department Manager','Shift Leader','Supervisor','Technician','Employee') DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `dept_id`, `status`, `last_login`) VALUES
(2, 'BDTSC General Manager', 'gm@bdtsc.com', '$2y$10$FtUh9Dhi56cMdZRXr5Rt4OnCriEVn71BWWe9D9vKFRO8Q9QZwHr.S', 'General Manager', NULL, 'Active', NULL),
(3, 'አለምነው አያና ', 'alem12@gmail.com', '123456', 'Department Manager', 2, 'Active', NULL),
(4, 'ካሳሁን ዋሴ', 'kassa@gmail.com', '123456', 'Employee', 3, 'Active', NULL),
(5, 'ዮናስ አለሙ', 'yonas@gmail.com', '123456', 'Department Manager', 3, 'Active', NULL),
(6, 'አስማማው አንተነህ', 'asmamaw@gmail.com', '123456', '', 3, 'Active', NULL),
(7, 'እመቤት ከበደ', 'emebet@gmail.com', '123456', '', 3, 'Active', NULL),
(8, 'ሙሉ ጎጃም', 'mulu@gmail.com', '123456', 'Technician', 2, 'Active', NULL),
(9, 'ታደሰ በቀለ', 'tadese@gmail.com', '123456', 'Technician', 2, 'Active', NULL);

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
  ADD KEY `dept_id` (`dept_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
