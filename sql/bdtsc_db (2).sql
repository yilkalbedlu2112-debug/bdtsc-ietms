
CREATE DATABASE IF NOT EXISTS `bdtsc_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `bdtsc_db`;

-- Dumping structure for table bdtsc_db.audit_logs
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bdtsc_db.audit_logs: ~174 rows (approximately)
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
	(75, 15, 'Login', 'User logged into the system', '::1', '2026-04-09 22:28:16', '2026-04-09 22:28:16'),
	(76, 12, 'Login', 'User logged into the system', '::1', '2026-04-20 21:10:16', '2026-04-20 21:10:16'),
	(77, 2, 'Login', 'User logged into the system', '::1', '2026-04-20 21:11:42', '2026-04-20 21:11:42'),
	(78, 7, 'Login', 'User logged into the system', '::1', '2026-04-20 22:04:06', '2026-04-20 22:04:06'),
	(79, 2, 'Login', 'User logged into the system', '::1', '2026-04-20 22:34:34', '2026-04-20 22:34:34'),
	(80, 2, 'Password Reset Approved', 'Admin approved reset request for: Beyene Gebeyaw (ID: 8)', '::1', '2026-04-20 22:52:52', '2026-04-20 22:52:52'),
	(81, 2, 'Password Reset Approved', 'Admin approved reset request for: Beyene Gebeyaw (ID: 8)', '::1', '2026-04-20 22:55:49', '2026-04-20 22:55:49'),
	(82, 2, 'Password Reset Approved', 'Admin approved reset request for: እመቤት ከበደ (ID: 7)', '::1', '2026-04-20 23:02:53', '2026-04-20 23:02:53'),
	(83, 2, 'Login', 'User logged into the system', '::1', '2026-04-21 11:08:50', '2026-04-21 11:08:50'),
	(84, 2, 'Department Update', 'Updated department: General Management (ID: 1)', '::1', '2026-04-21 11:09:55', '2026-04-21 11:09:55'),
	(85, 2, 'Login', 'User logged into the system', '::1', '2026-04-21 11:11:43', '2026-04-21 11:11:43'),
	(86, 2, 'Login', 'User logged into the system', '::1', '2026-04-21 11:15:11', '2026-04-21 11:15:11'),
	(87, 2, 'Login', 'User logged into the system', '::1', '2026-04-21 11:24:08', '2026-04-21 11:24:08'),
	(88, 2, 'Password Reset', 'Password Reset for Yilkal Getnet', '::1', '2026-04-21 11:24:19', '2026-04-21 11:24:19'),
	(89, 2, 'Password Reset', 'Password Reset for System Admin', '::1', '2026-04-21 11:24:28', '2026-04-21 11:24:28'),
	(90, 16, 'Login', 'User logged into the system', '::1', '2026-04-21 11:24:53', '2026-04-21 11:24:53'),
	(91, 12, 'Login', 'User logged into the system', '::1', '2026-04-21 11:26:42', '2026-04-21 11:26:42'),
	(92, 8, 'Login', 'User logged into the system', '::1', '2026-04-21 11:28:50', '2026-04-21 11:28:50'),
	(93, 9, 'Login', 'User logged into the system', '::1', '2026-04-21 12:23:20', '2026-04-21 12:23:20'),
	(94, 2, 'Login', 'User logged into the system', '::1', '2026-04-21 12:25:51', '2026-04-21 12:25:51'),
	(95, 2, 'User Update', 'Updated user: ታደሰ በቀለ (ID: 9)', '::1', '2026-04-21 12:26:24', '2026-04-21 12:26:24'),
	(96, 9, 'Login', 'User logged into the system', '::1', '2026-04-21 12:27:00', '2026-04-21 12:27:00'),
	(97, 2, 'Login', 'User logged into the system', '::1', '2026-04-21 12:56:06', '2026-04-21 12:56:06'),
	(98, 2, 'User Update', 'Updated user: ታደሰ በቀለ (ID: 9)', '::1', '2026-04-21 12:56:25', '2026-04-21 12:56:25'),
	(99, 9, 'Login', 'User logged into the system', '::1', '2026-04-21 12:56:45', '2026-04-21 12:56:45'),
	(100, 9, 'Login', 'User logged into the system', '::1', '2026-04-21 17:56:48', '2026-04-21 17:56:48'),
	(101, 13, 'Login', 'User logged into the system', '::1', '2026-04-21 19:10:20', '2026-04-21 19:10:20'),
	(102, 2, 'Login', 'User logged into the system', '::1', '2026-04-21 19:12:15', '2026-04-21 19:12:15'),
	(103, 2, 'Password Reset', 'Password Reset for ይልቃል በድሉ', '::1', '2026-04-21 19:12:56', '2026-04-21 19:12:56'),
	(104, 11, 'Login', 'User logged into the system', '::1', '2026-04-21 19:13:23', '2026-04-21 19:13:23'),
	(105, 2, 'Login', 'User logged into the system', '::1', '2026-04-21 19:16:42', '2026-04-21 19:16:42'),
	(106, 2, 'User Registration', 'Registered new user: Hewan Fantahun as Store Keeper', '::1', '2026-04-21 19:19:30', '2026-04-21 19:19:30'),
	(107, 2, 'User Registration', 'Registered new user: Zemenu Asfera as Officer', '::1', '2026-04-21 19:29:53', '2026-04-21 19:29:53'),
	(108, 25, 'Login', 'User logged into the system', '::1', '2026-04-21 19:30:27', '2026-04-21 19:30:27'),
	(109, 6, 'Login', 'User logged into the system', '::1', '2026-04-21 19:34:47', '2026-04-21 19:34:47'),
	(110, 7, 'Login', 'User logged into the system', '::1', '2026-04-21 20:39:42', '2026-04-21 20:39:42'),
	(111, 6, 'Login', 'User logged into the system', '::1', '2026-04-21 20:40:41', '2026-04-21 20:40:41'),
	(112, 5, 'Login', 'User logged into the system', '::1', '2026-04-21 20:43:31', '2026-04-21 20:43:31'),
	(113, 12, 'Login', 'User logged into the system', '::1', '2026-04-21 21:38:13', '2026-04-21 21:38:13'),
	(114, 2, 'Login', 'User logged into the system', '::1', '2026-04-23 06:35:50', '2026-04-23 06:35:50'),
	(115, 12, 'Login', 'User logged into the system', '::1', '2026-04-23 08:27:44', '2026-04-23 08:27:44'),
	(116, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 16:33:45', '2026-04-23 16:33:45'),
	(117, 12, 'Login', 'User logged into the system', '::1', '2026-04-23 16:39:39', '2026-04-23 16:39:39'),
	(118, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 16:45:30', '2026-04-23 16:45:30'),
	(119, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 16:50:34', '2026-04-23 16:50:34'),
	(120, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 17:13:13', '2026-04-23 17:13:13'),
	(121, 2, 'Login', 'User logged into the system', '192.168.137.16', '2026-04-23 17:16:32', '2026-04-23 17:16:32'),
	(122, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 17:18:22', '2026-04-23 17:18:22'),
	(123, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 17:19:24', '2026-04-23 17:19:24'),
	(124, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 17:21:59', '2026-04-23 17:21:59'),
	(125, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 17:41:42', '2026-04-23 17:41:42'),
	(126, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 17:55:21', '2026-04-23 17:55:21'),
	(127, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 17:57:58', '2026-04-23 17:57:58'),
	(128, 13, 'Login', 'User logged into the system', '192.168.137.1', '2026-04-23 18:00:51', '2026-04-23 18:00:51'),
	(129, 5, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:06:37', '2026-04-23 18:06:37'),
	(130, 4, 'Login', 'User logged into the system', '192.168.137.1', '2026-04-23 18:11:05', '2026-04-23 18:11:05'),
	(131, 5, 'Task Created', 'Department Manager (HONELEGN DIRES) created task #2 [Cross-Dept → dept ID 16] | Type: Maintenance | Subject: Repairing machine', '192.168.137.168', '2026-04-23 18:11:28', '2026-04-23 18:11:28'),
	(132, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:12:34', '2026-04-23 18:12:34'),
	(133, 4, 'Task Update', 'Task #2 changed to In Progress', '192.168.137.1', '2026-04-23 18:13:32', '2026-04-23 18:13:32'),
	(134, 5, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:20:10', '2026-04-23 18:20:10'),
	(135, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:20:26', '2026-04-23 18:20:26'),
	(136, 2, 'Login', 'User logged into the system', '192.168.137.16', '2026-04-23 18:25:39', '2026-04-23 18:25:39'),
	(137, 5, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:27:09', '2026-04-23 18:27:09'),
	(138, 6, 'Login', 'User logged into the system', '192.168.137.16', '2026-04-23 18:27:22', '2026-04-23 18:27:22'),
	(139, 5, 'Task Created', 'Department Manager (HONELEGN DIRES) created task #3 | Type: Daily Production | Subject: production', '192.168.137.168', '2026-04-23 18:33:08', '2026-04-23 18:33:08'),
	(140, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:44:10', '2026-04-23 18:44:10'),
	(141, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:44:31', '2026-04-23 18:44:31'),
	(142, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:49:04', '2026-04-23 18:49:04'),
	(143, 2, 'Login', 'User logged into the system', '192.168.137.16', '2026-04-23 18:49:19', '2026-04-23 18:49:19'),
	(144, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:49:27', '2026-04-23 18:49:27'),
	(145, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:49:50', '2026-04-23 18:49:50'),
	(146, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:51:34', '2026-04-23 18:51:34'),
	(147, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:52:41', '2026-04-23 18:52:41'),
	(148, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:54:21', '2026-04-23 18:54:21'),
	(149, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:55:02', '2026-04-23 18:55:02'),
	(150, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:56:06', '2026-04-23 18:56:06'),
	(151, 6, 'Login', 'User logged into the system', '192.168.137.16', '2026-04-23 18:57:10', '2026-04-23 18:57:10'),
	(152, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:58:05', '2026-04-23 18:58:05'),
	(153, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 18:58:13', '2026-04-23 18:58:13'),
	(154, 2, 'Login', 'User logged into the system', '192.168.137.16', '2026-04-23 18:58:51', '2026-04-23 18:58:51'),
	(155, 2, 'Login', 'User logged into the system', '192.168.137.16', '2026-04-23 19:00:14', '2026-04-23 19:00:14'),
	(156, 15, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:01:01', '2026-04-23 19:01:01'),
	(157, 15, 'Task Created', 'Department Manager (TEWABE GETU) created task #4 | Type: Report Preparation | Subject: Prepare report', '192.168.137.168', '2026-04-23 19:11:43', '2026-04-23 19:11:43'),
	(158, 15, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:12:20', '2026-04-23 19:12:20'),
	(159, 13, 'Login', 'User logged into the system', '192.168.137.1', '2026-04-23 19:12:43', '2026-04-23 19:12:43'),
	(160, 5, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:13:20', '2026-04-23 19:13:20'),
	(161, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:14:29', '2026-04-23 19:14:29'),
	(162, 5, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:14:45', '2026-04-23 19:14:45'),
	(163, 5, 'Task Created', 'Department Manager (HONELEGN DIRES) created task #5 | Type: Quality Check | Subject: cheke it', '192.168.137.168', '2026-04-23 19:16:33', '2026-04-23 19:16:33'),
	(164, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:16:54', '2026-04-23 19:16:54'),
	(165, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:23:12', '2026-04-23 19:23:12'),
	(166, 5, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:23:24', '2026-04-23 19:23:24'),
	(167, 5, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:24:36', '2026-04-23 19:24:36'),
	(168, 5, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:32:53', '2026-04-23 19:32:53'),
	(169, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:33:25', '2026-04-23 19:33:25'),
	(170, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:35:56', '2026-04-23 19:35:56'),
	(171, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:38:32', '2026-04-23 19:38:32'),
	(172, 15, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:39:18', '2026-04-23 19:39:18'),
	(173, 7, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:44:09', '2026-04-23 19:44:09'),
	(174, 4, 'Login', 'User logged into the system', '192.168.137.168', '2026-04-23 19:44:19', '2026-04-23 19:44:19');

-- Dumping structure for table bdtsc_db.daily_reports
DROP TABLE IF EXISTS `daily_reports`;
CREATE TABLE IF NOT EXISTS `daily_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dept_id` int NOT NULL,
  `created_by` int NOT NULL,
  `shift_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `report_summary` text COLLATE utf8mb4_general_ci NOT NULL,
  `total_tasks` int NOT NULL DEFAULT '0',
  `completed_tasks` int NOT NULL DEFAULT '0',
  `blocked_tasks` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `dept_id` (`dept_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bdtsc_db.daily_reports: ~0 rows (approximately)

-- Dumping structure for table bdtsc_db.departments
DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `dept_type` enum('Production','Support') COLLATE utf8mb4_general_ci DEFAULT 'Production',
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_name` (`dept_name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bdtsc_db.departments: ~16 rows (approximately)
INSERT INTO `departments` (`id`, `dept_name`, `dept_type`, `description`, `created_at`) VALUES
	(1, 'General Management', 'Production', '', '2026-04-04 20:30:03'),
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

-- Dumping structure for table bdtsc_db.feedback_logs
DROP TABLE IF EXISTS `feedback_logs`;
CREATE TABLE IF NOT EXISTS `feedback_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `dept_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Pending','Reviewed','Resolved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dept_id` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bdtsc_db.feedback_logs: ~0 rows (approximately)

-- Dumping structure for table bdtsc_db.maintenance_requests
DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Maintenance',
  `user_id` int DEFAULT NULL,
  `dept_id` int DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `supervisor_id` int DEFAULT NULL,
  `machine_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `issue_description` text COLLATE utf8mb4_general_ci NOT NULL,
  `feedback` text COLLATE utf8mb4_general_ci,
  `priority` enum('Normal','Low','Medium','High','Urgent','Emergency') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Normal',
  `status` enum('Pending','Pending Approval','Approved','Rejected','Assigned','In Progress','Completed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date` datetime DEFAULT NULL,
  `severity` enum('Low','Medium','High') COLLATE utf8mb4_general_ci DEFAULT 'Low',
  `assigned_to_dept` enum('Internal','Engineering') COLLATE utf8mb4_general_ci DEFAULT 'Internal',
  `completion_notes` text COLLATE utf8mb4_general_ci,
  `is_verified` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sender_dept_id` int DEFAULT NULL,
  `receiver_dept_id` int DEFAULT NULL,
  `request_type` enum('Repair','Manpower','Resource','Legal','Maintenance','Administrative','Other') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Maintenance',
  `is_read_by_receiver` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `deadline` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dept_id` (`dept_id`),
  KEY `fk_mr_assigned_to` (`assigned_to`),
  KEY `fk_mr_created_by` (`created_by`),
  KEY `idx_sender_dept` (`sender_dept_id`),
  KEY `idx_receiver_dept` (`receiver_dept_id`),
  KEY `idx_request_type` (`request_type`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_mr_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mr_receiver_dept` FOREIGN KEY (`receiver_dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mr_sender_dept` FOREIGN KEY (`sender_dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bdtsc_db.maintenance_requests: ~5 rows (approximately)
INSERT INTO `maintenance_requests` (`id`, `task_type`, `user_id`, `dept_id`, `assigned_to`, `supervisor_id`, `machine_name`, `issue_description`, `feedback`, `priority`, `status`, `created_at`, `due_date`, `severity`, `assigned_to_dept`, `completion_notes`, `is_verified`, `updated_at`, `sender_dept_id`, `receiver_dept_id`, `request_type`, `is_read_by_receiver`, `title`, `assigned_at`, `completed_at`, `description`, `deadline`, `created_by`) VALUES
	(1, 'Production', 4, 2, NULL, NULL, 'Spinning Machine Belt ', 'Spinning Machine Belt Broken', NULL, 'Urgent', 'Completed', '2026-04-04 21:24:55', NULL, 'Low', 'Internal', NULL, 0, '2026-04-20 21:57:14', 2, NULL, 'Maintenance', 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(2, 'Maintenance', 5, 3, 4, NULL, 'Repairing machine', 'please complete  this task.', NULL, 'Normal', 'In Progress', '2026-04-23 18:11:28', '2026-04-23 15:07:00', 'Low', 'Internal', NULL, 0, '2026-04-23 18:13:32', 3, 16, 'Repair', 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(3, 'Daily Production', 5, 3, 6, NULL, 'production', 'please produce', NULL, 'Normal', 'Assigned', '2026-04-23 18:33:08', '2026-04-23 15:30:00', 'Low', 'Internal', NULL, 0, '2026-04-23 18:33:08', 3, 3, 'Administrative', 1, NULL, NULL, NULL, NULL, NULL, NULL),
	(4, 'Report Preparation', 15, 12, 13, NULL, 'Prepare report', 'please submit report', NULL, 'Normal', 'Assigned', '2026-04-23 19:11:43', '2026-04-23 16:02:00', 'Low', 'Internal', NULL, 0, '2026-04-23 19:11:43', 12, 12, 'Manpower', 1, NULL, NULL, NULL, NULL, NULL, NULL),
	(5, 'Quality Check', 5, 3, 4, NULL, 'cheke it', 'please check production quality', NULL, 'Normal', 'Assigned', '2026-04-23 19:16:33', '2026-04-23 16:15:00', 'Low', 'Internal', NULL, 0, '2026-04-23 19:16:33', 3, 3, 'Manpower', 1, NULL, NULL, NULL, NULL, NULL, NULL);

-- Dumping structure for table bdtsc_db.notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `user_role` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dept_id` int DEFAULT NULL,
  `role_target` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dept_id` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bdtsc_db.notifications: ~0 rows (approximately)

-- Dumping structure for table bdtsc_db.production_reports
DROP TABLE IF EXISTS `production_reports`;
CREATE TABLE IF NOT EXISTS `production_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `dept_id` int NOT NULL,
  `machine_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity_produced` decimal(10,2) NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Meters',
  `shift` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reported_to` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_general_ci,
  `report_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dept_id` (`dept_id`),
  CONSTRAINT `production_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `production_reports_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bdtsc_db.production_reports: ~1 rows (approximately)
INSERT INTO `production_reports` (`id`, `user_id`, `dept_id`, `machine_name`, `quantity_produced`, `unit`, `shift`, `reported_to`, `remarks`, `report_date`) VALUES
	(1, 4, 3, 'Spinning Machine Belt ', 30.00, 'Meters', 'Night', NULL, NULL, '2026-04-05 12:39:04');

-- Dumping structure for table bdtsc_db.tasks
DROP TABLE IF EXISTS `tasks`;
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_title` varchar(255) NOT NULL,
  `description` text,
  `assigned_to_dept` int NOT NULL,
  `assigned_employee` int DEFAULT '0',
  `status` enum('Pending','Assigned','In Progress','Completed') DEFAULT 'Pending',
  `priority` enum('Low','Normal','High','Urgent') DEFAULT 'Normal',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `assigned_to_dept` (`assigned_to_dept`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to_dept`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table bdtsc_db.tasks: ~0 rows (approximately)

-- Dumping structure for table bdtsc_db.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `user_role` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `dept_id` int DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL,
  `reset_approved` tinyint(1) NOT NULL DEFAULT '0',
  `profile_pic` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'default_user.jpg',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `dept_id` (`dept_id`),
  KEY `idx_reset_token` (`reset_token`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bdtsc_db.users: ~17 rows (approximately)
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `reset_token`, `token_expiry`, `user_role`, `dept_id`, `status`, `last_login`, `reset_approved`, `profile_pic`) VALUES
	(2, 'ASCHALEW MULUALEM', 'yilkalbedlu1993@gmail.com', '$2y$10$FtUh9Dhi56cMdZRXr5Rt4OnCriEVn71BWWe9D9vKFRO8Q9QZwHr.S', NULL, NULL, 'General Manager', NULL, 'Active', NULL, 0, 'user_2.jpg'),
	(3, 'BAHIRU ADMASSIE', 'bahiru@gmail.com', '123456', NULL, NULL, 'Department Manager', 5, 'Active', NULL, 0, 'default_user.jpg'),
	(4, 'ካሳሁን ዋሴ', 'kassa@gmail.com', '$2y$10$WsaQhT8m4pEpQ.sNdzRgL.m76krDyNCvM7v7GKjwojTf6kqN7hmOG', NULL, NULL, 'Employee', 3, 'Active', NULL, 0, 'default_user.jpg'),
	(5, 'HONELEGN DIRES', 'honelegn@gmail.com', '$2y$10$JPqsOb2TfgpqztTu/ybsGuBIHpcqLF8jcFr6y9qTMOP7Ut5AyItLy', NULL, NULL, 'Department Manager', 3, 'Active', NULL, 0, 'default_user.jpg'),
	(6, 'አስማማው አንተነህ', 'asmamaw@gmail.com', '$2y$10$SPt01MhwC0SQsPpRKS9pXeJ4t7603OGMTQ7Hp27OPc/o2HyMs3aWu', NULL, NULL, 'Shift Leader', 3, 'Active', NULL, 0, 'user_6.jpg'),
	(7, 'እመቤት ከበደ', 'emebet@gmail.com', '$2y$10$j1yrFHxxhbIM6iRP0pw/5OfT7UJ4j.qMHV9tvRKreMOqtIXBeYGKW', '97b79e9cc62e00b2b99f6d871f507cc81057de9c8377aac87b1d3a5a89f1f645', '2026-04-21 03:02:53', 'Supervisor', 3, 'Active', NULL, 1, 'user_7.jpg'),
	(8, 'Beyene Gebeyaw', 'beyenege845@gmail.com', '$2y$10$qL5zkCNF1BK9J0KA/p.DR.oVP12LfsfoXvcG3wZmfnIB4mZCEA9EG', '361ea0c06d85d7b8fbf89ca6b25b43f1e6c71b9230b0134b3862205133b90221', '2026-04-21 02:55:49', 'Engineering Manager', 2, 'Active', NULL, 1, 'default_user.jpg'),
	(9, 'ታደሰ በቀለ', 'tadese@gmail.com', '$2y$10$1aDCHaJdHSsJDAIUptW4h.kUjTclmvFE5KIO//Yg1D4BnpfP38xHy', NULL, NULL, 'Technician', 2, 'Active', NULL, 0, 'default_user.jpg'),
	(11, 'ይልቃል በድሉ', 'yilqal@gmail.com', '$2y$10$dq5rWP6FQ1k5CBp3v.MctuZa96dzWiljWRd4v6Y1pkW.HBLduLvoa', NULL, NULL, 'Employee', 8, 'Active', NULL, 0, 'default_user.jpg'),
	(12, 'Yilkal Getnet', 'yilkalbedlu2112@gmail.com', '$2y$10$AYwTERX4zGVM4hPhtAvdA.1.oXlb2Eha0vG7CxWooGbVgmUkYRLOW', NULL, NULL, 'Deputy General Manager', NULL, 'Active', NULL, 0, 'user_12.jpg'),
	(13, 'Abebe Abebaw', 'abebe@gmail.com', '$2y$10$DUvkUvZX5esrE1WL98VUpueom3ph/UXdj0SOy1gf6yUykq6XKrCEG', NULL, NULL, 'Employee', 12, 'Active', NULL, 0, 'default_user.jpg'),
	(14, 'DESALEGN ABEBE', 'desalegn@gmail.com', '$2y$10$ix2ek1DHMYOy0Vu1KZKh7O1yEbwVfl4oxDvV0YnDBuqv0xjSDBzZq', NULL, NULL, 'Department Manager', 14, 'Active', NULL, 0, 'default_user.jpg'),
	(15, 'TEWABE GETU', 'tewabe@gmail.com', '$2y$10$xkGDe2lpBlSOyG1wM.cxcOPIjh6EqoJPsHyH6t4VlZeljjfgvdZvG', NULL, NULL, 'Department Manager', 12, 'Active', NULL, 0, 'default_user.jpg'),
	(16, 'System Admin', 'admin@bdtsc.et', '$2y$10$oJiCLHha0A6UI/f4H9pTRueXx4.5NbhwoL7pOdhO2TV2pZi9n1enu', NULL, NULL, 'Admin', NULL, 'Active', NULL, 0, 'default_user.jpg'),
	(17, 'Test Manager', 'manager@bdtsc.et', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'Department Manager', 1, 'Active', NULL, 0, 'default_user.jpg'),
	(24, 'Hewan Fantahun', 'hewan@gmail.com', '$2y$10$/TZTdDE4Q7rAPFiBi7w2GOX651nF9cDZbPpPeWOO7VarOKQoAg3Pm', NULL, NULL, 'Store Keeper', 14, 'Active', NULL, 0, 'default_user.jpg'),
	(25, 'Zemenu Asfera', 'zemenu@gmail.com', '$2y$10$I5BV0rs40GRuZIA0QXelIe1VTwQCV2/.RgQ.PfYxW/v5h4UTUsnWa', NULL, NULL, 'Officer', 15, 'Active', NULL, 0, 'default_user.jpg');


