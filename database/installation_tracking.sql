-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2026 at 02:59 AM
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
-- Database: `installation_tracking`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `reference_type`, `reference_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:45:44'),
(2, 1, 'reset_password', 'users', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:46:34'),
(3, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:46:41'),
(4, 2, 'login', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:46:54'),
(5, 2, 'created_item', 'inventory', 'inventory_items', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:48:14'),
(6, 2, 'created_item', 'inventory', 'inventory_items', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:49:45'),
(7, 2, 'created_assignment', 'assignments', 'assignments', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-10 04:09:40'),
(8, 2, 'logout', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-10 04:10:09'),
(9, 3, 'login', 'auth', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-02-10 04:10:15'),
(10, 3, 'submitted_installation', 'installations', 'installation_reports', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 05:06:01'),
(11, 3, 'logout', 'auth', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 05:06:30'),
(12, 2, 'login', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 05:06:40'),
(13, 2, 'reset_password', 'users', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 05:06:53'),
(14, 2, 'logout', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 05:07:02'),
(15, 4, 'login', 'auth', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 05:07:07'),
(16, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 05:43:42'),
(17, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 05:56:02'),
(18, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 05:56:21'),
(19, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:04:42'),
(20, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:04:45'),
(21, 1, 'reset_password', 'users', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:06:00'),
(22, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:06:08'),
(23, 3, 'login', 'auth', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:06:15'),
(24, 3, 'logout', 'auth', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:06:53'),
(25, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:07:00'),
(26, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:08:04'),
(27, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:08:18'),
(28, 1, 'reset_password', 'users', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:08:31'),
(29, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:08:37'),
(30, 2, 'login', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:09:02'),
(31, 2, 'logout', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:10:10'),
(32, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:10:14'),
(33, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:10:31'),
(34, 2, 'login', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:10:40'),
(35, 2, 'logout', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:12:05'),
(36, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:12:08'),
(37, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:12:17'),
(38, 2, 'login', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:12:23'),
(39, 2, 'created_assignment', 'assignments', 'assignments', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:12:59'),
(40, 2, 'logout', 'auth', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:13:11'),
(41, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:13:15'),
(42, 1, 'reset_password', 'users', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:13:36'),
(43, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:14:09'),
(44, 3, 'login', 'auth', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:14:17'),
(45, 3, 'submitted_installation', 'installations', 'installation_reports', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:17:53'),
(46, 3, 'logout', 'auth', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:17:59'),
(47, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:18:02'),
(48, 1, 'reset_password', 'users', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:18:41'),
(49, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:18:46'),
(50, 4, 'login', 'auth', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:18:52'),
(51, 4, 'logout', 'auth', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:20:14'),
(52, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:20:28'),
(53, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:21:07'),
(54, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:21:33'),
(55, 1, 'reset_password', 'users', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:22:16'),
(56, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:22:20'),
(57, 5, 'login', 'auth', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:22:32'),
(58, 5, 'logout', 'auth', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:22:58'),
(59, 4, 'login', 'auth', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:23:07'),
(60, 4, 'completed_inspection', 'inspections', 'inspection_reports', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:23:58'),
(61, 4, 'logout', 'auth', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:24:03'),
(62, 5, 'login', 'auth', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:24:26'),
(63, 5, 'logout', 'auth', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:24:51'),
(64, 4, 'login', 'auth', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:25:08'),
(65, 4, 'completed_inspection', 'inspections', 'inspection_reports', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:25:48'),
(66, 4, 'logout', 'auth', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:25:51'),
(67, 5, 'login', 'auth', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:25:56'),
(68, 5, 'logout', 'auth', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:26:48'),
(69, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:26:50'),
(70, 1, 'assigned_ticket', 'maintenance', 'maintenance_tickets', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:26:58'),
(71, 1, 'assigned_ticket', 'maintenance', 'maintenance_tickets', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:27:02'),
(72, 1, 'logout', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:27:05'),
(73, 5, 'login', 'auth', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:27:14'),
(74, 5, 'logout', 'auth', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:28:19'),
(75, 1, 'login', 'auth', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 07:28:22');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assignment_code` varchar(50) NOT NULL,
  `assigned_to` int(10) UNSIGNED NOT NULL,
  `area_id` int(10) UNSIGNED NOT NULL,
  `assignment_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `assigned_by` int(10) UNSIGNED NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `assignment_code`, `assigned_to`, `area_id`, `assignment_date`, `due_date`, `priority`, `status`, `notes`, `assigned_by`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'ASN-20260210-EFB3', 3, 1, '2026-02-10', '2026-02-27', 'normal', 'in_progress', '', 2, NULL, '2026-02-10 04:09:40', '2026-02-10 05:06:01'),
(2, 'ASN-20260302-2865', 3, 1, '2026-03-02', '0000-00-00', 'normal', 'completed', '', 2, '2026-03-02 15:17:53', '2026-03-02 07:12:59', '2026-03-02 07:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_items`
--

CREATE TABLE `assignment_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `quantity_assigned` int(10) UNSIGNED NOT NULL,
  `quantity_installed` int(10) UNSIGNED DEFAULT 0,
  `status` enum('pending','partial','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignment_items`
--

INSERT INTO `assignment_items` (`id`, `assignment_id`, `item_id`, `quantity_assigned`, `quantity_installed`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 20, 1, 'partial', '2026-02-10 04:09:40', '2026-02-10 05:06:01'),
(2, 1, 2, 10, 1, 'partial', '2026-02-10 04:09:40', '2026-02-10 05:06:01'),
(3, 2, 1, 1, 1, 'completed', '2026-03-02 07:12:59', '2026-03-02 07:17:53'),
(4, 2, 2, 1, 1, 'completed', '2026-03-02 07:12:59', '2026-03-02 07:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `inspection_items`
--

CREATE TABLE `inspection_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inspection_report_id` bigint(20) UNSIGNED NOT NULL,
  `installation_report_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity_intact` int(10) UNSIGNED DEFAULT 0,
  `quantity_damaged` int(10) UNSIGNED DEFAULT 0,
  `quantity_missing` int(10) UNSIGNED DEFAULT 0,
  `quantity_needs_replacement` int(10) UNSIGNED DEFAULT 0,
  `item_status` enum('intact','damaged','missing','needs_replacement','mixed') DEFAULT 'intact',
  `photo` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `escalate_to_maintenance` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inspection_items`
--

INSERT INTO `inspection_items` (`id`, `inspection_report_id`, `installation_report_item_id`, `quantity_intact`, `quantity_damaged`, `quantity_missing`, `quantity_needs_replacement`, `item_status`, `photo`, `remarks`, `escalate_to_maintenance`, `created_at`) VALUES
(1, 1, 3, 0, 1, 0, 0, 'damaged', NULL, '', 0, '2026-03-02 07:23:58'),
(2, 1, 4, 0, 1, 0, 0, 'damaged', NULL, '', 0, '2026-03-02 07:23:58'),
(3, 2, 1, 0, 1, 0, 0, 'damaged', NULL, '', 1, '2026-03-02 07:25:48'),
(4, 2, 2, 0, 1, 0, 0, 'damaged', NULL, '', 1, '2026-03-02 07:25:48');

-- --------------------------------------------------------

--
-- Table structure for table `inspection_reports`
--

CREATE TABLE `inspection_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inspection_code` varchar(50) NOT NULL,
  `schedule_id` bigint(20) UNSIGNED NOT NULL,
  `inspector_id` int(10) UNSIGNED NOT NULL,
  `inspection_date` date NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `overall_status` enum('all_intact','issues_found','critical') DEFAULT 'all_intact',
  `overall_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inspection_reports`
--

INSERT INTO `inspection_reports` (`id`, `inspection_code`, `schedule_id`, `inspector_id`, `inspection_date`, `latitude`, `longitude`, `overall_status`, `overall_remarks`, `created_at`, `updated_at`) VALUES
(1, 'INP-20260302-61B6', 7, 4, '2026-03-02', 14.56124480, 121.01829014, 'issues_found', '', '2026-03-02 07:23:58', '2026-03-02 07:23:58'),
(2, 'INP-20260302-6454', 1, 4, '2026-03-02', 14.56126983, 121.01831699, 'issues_found', '', '2026-03-02 07:25:48', '2026-03-02 07:25:48');

-- --------------------------------------------------------

--
-- Table structure for table `inspection_schedules`
--

CREATE TABLE `inspection_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `installation_report_id` bigint(20) UNSIGNED NOT NULL,
  `month_number` tinyint(3) UNSIGNED NOT NULL COMMENT '1-6 for 6 months',
  `scheduled_date` date NOT NULL,
  `inspector_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','scheduled','completed','overdue','skipped') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inspection_schedules`
--

INSERT INTO `inspection_schedules` (`id`, `installation_report_id`, `month_number`, `scheduled_date`, `inspector_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-03-10', 4, 'completed', '2026-02-10 05:06:01', '2026-03-02 07:25:48'),
(2, 1, 2, '2026-04-10', NULL, 'pending', '2026-02-10 05:06:01', '2026-02-10 05:06:01'),
(3, 1, 3, '2026-05-10', NULL, 'pending', '2026-02-10 05:06:01', '2026-02-10 05:06:01'),
(4, 1, 4, '2026-06-10', NULL, 'pending', '2026-02-10 05:06:01', '2026-02-10 05:06:01'),
(5, 1, 5, '2026-07-10', NULL, 'pending', '2026-02-10 05:06:01', '2026-02-10 05:06:01'),
(6, 1, 6, '2026-08-10', NULL, 'pending', '2026-02-10 05:06:01', '2026-02-10 05:06:01'),
(7, 2, 1, '2026-04-02', 4, 'completed', '2026-03-02 07:17:53', '2026-03-02 07:23:58'),
(8, 2, 2, '2026-05-02', NULL, 'pending', '2026-03-02 07:17:53', '2026-03-02 07:17:53'),
(9, 2, 3, '2026-06-02', NULL, 'pending', '2026-03-02 07:17:53', '2026-03-02 07:17:53'),
(10, 2, 4, '2026-07-02', NULL, 'pending', '2026-03-02 07:17:53', '2026-03-02 07:17:53'),
(11, 2, 5, '2026-08-02', NULL, 'pending', '2026-03-02 07:17:53', '2026-03-02 07:17:53'),
(12, 2, 6, '2026-09-02', NULL, 'pending', '2026-03-02 07:17:53', '2026-03-02 07:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `installation_areas`
--

CREATE TABLE `installation_areas` (
  `id` int(10) UNSIGNED NOT NULL,
  `area_code` varchar(50) NOT NULL,
  `area_name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `installation_areas`
--

INSERT INTO `installation_areas` (`id`, `area_code`, `area_name`, `address`, `city`, `province`, `region`, `latitude`, `longitude`, `status`, `created_at`, `updated_at`) VALUES
(1, 'AC111', 'MAKATI CITY', 'MAKATI CITY METRO MANILA NCR', 'TEST', 'TEST', 'TEST', NULL, NULL, 'active', '2026-02-10 03:50:33', '2026-02-10 03:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `installation_detailed_addresses`
--

CREATE TABLE `installation_detailed_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `house_no` varchar(50) DEFAULT NULL COMMENT 'House number',
  `block` varchar(50) DEFAULT NULL COMMENT 'Block number',
  `lot` varchar(50) DEFAULT NULL COMMENT 'Lot number',
  `street_name` varchar(200) DEFAULT NULL COMMENT 'Street name',
  `purok` varchar(100) DEFAULT NULL COMMENT 'Purok',
  `sitio` varchar(100) DEFAULT NULL COMMENT 'Sitio',
  `zone` varchar(100) DEFAULT NULL COMMENT 'Zone',
  `phase` varchar(100) DEFAULT NULL COMMENT 'Phase (for subdivisions)',
  `road` varchar(200) DEFAULT NULL COMMENT 'Road name',
  `barangay` varchar(200) NOT NULL COMMENT 'Barangay',
  `city` varchar(100) NOT NULL COMMENT 'City/Municipality',
  `province` varchar(100) NOT NULL COMMENT 'Province',
  `complete_address` text GENERATED ALWAYS AS (concat_ws(', ',nullif(concat_ws(' ',nullif(`house_no`,''),nullif(`block`,''),nullif(`lot`,''),nullif(`street_name`,'')),''),nullif(`purok`,''),nullif(`sitio`,''),nullif(`zone`,''),nullif(`phase`,''),nullif(`road`,''),`barangay`,`city`,`province`)) STORED COMMENT 'Auto-generated complete address string',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detailed address breakdown for installations';

--
-- Dumping data for table `installation_detailed_addresses`
--

INSERT INTO `installation_detailed_addresses` (`id`, `report_id`, `house_no`, `block`, `lot`, `street_name`, `purok`, `sitio`, `zone`, `phase`, `road`, `barangay`, `city`, `province`, `created_at`, `updated_at`) VALUES
(1, 1, '', '', '', '', '', '', '', '', '', 'dfs', 'sdf', 'sdf', '2026-02-10 05:06:01', '2026-02-10 05:06:01'),
(2, 2, 'hjmhjm', 'hjmhj', 'hjmh', '', 'hjmhj', 'hjmh', 'hmj', 'hmj', 'hjmhj', 'hjmmh', 'hmjmhj', 'hmj', '2026-03-02 07:17:52', '2026-03-02 07:17:52');

-- --------------------------------------------------------

--
-- Table structure for table `installation_item_photos`
--

CREATE TABLE `installation_item_photos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_item_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Links to installation_report_items',
  `photo_type` enum('before','after') NOT NULL COMMENT 'Before or after installation',
  `photo_filename` varchar(255) NOT NULL COMMENT 'Filename with GPS watermark',
  `caption` varchar(255) DEFAULT NULL COMMENT 'Optional description',
  `display_order` tinyint(3) UNSIGNED DEFAULT 0 COMMENT 'Order for display (0-255)',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Multiple photos per installed item';

--
-- Dumping data for table `installation_item_photos`
--

INSERT INTO `installation_item_photos` (`id`, `report_item_id`, `photo_type`, `photo_filename`, `caption`, `display_order`, `uploaded_at`) VALUES
(1, 1, 'before', '698abcb98da7d_1770699961.jpg', NULL, 0, '2026-02-10 05:06:01'),
(2, 1, 'after', '698abcb995ab3_1770699961.jpg', NULL, 0, '2026-02-10 05:06:01'),
(3, 2, 'before', '698abcb99dbec_1770699961.jpg', NULL, 0, '2026-02-10 05:06:01'),
(4, 2, 'after', '698abcb9a2f92_1770699961.jpg', NULL, 0, '2026-02-10 05:06:01'),
(5, 3, 'before', '69a539a10912d_1772435873.jpg', NULL, 0, '2026-03-02 07:17:53'),
(6, 3, 'after', '69a539a12897d_1772435873.jpg', NULL, 0, '2026-03-02 07:17:53'),
(7, 4, 'before', '69a539a14dd99_1772435873.jpg', NULL, 0, '2026-03-02 07:17:53'),
(8, 4, 'after', '69a539a16b0dc_1772435873.jpg', NULL, 0, '2026-03-02 07:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `installation_reports`
--

CREATE TABLE `installation_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_code` varchar(50) NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `installer_id` int(10) UNSIGNED NOT NULL,
  `installation_date` date NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `location_address` text DEFAULT NULL,
  `overall_remarks` text DEFAULT NULL,
  `status` enum('submitted','reviewed','approved','rejected') DEFAULT 'submitted',
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `installation_reports`
--

INSERT INTO `installation_reports` (`id`, `report_code`, `assignment_id`, `installer_id`, `installation_date`, `latitude`, `longitude`, `location_address`, `overall_remarks`, `status`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 'INS-20260210-B16D', 1, 3, '2026-02-10', 14.71986194, 120.96753259, 'MAKATI CITY METRO MANILA NCR', '', 'submitted', NULL, NULL, '2026-02-10 05:06:01', '2026-02-10 05:06:01'),
(2, 'INS-20260302-FACD', 2, 3, '2026-03-02', 14.56127479, 121.01833686, 'MAKATI CITY METRO MANILA NCR', '', 'submitted', NULL, NULL, '2026-03-02 07:17:52', '2026-03-02 07:17:52');

-- --------------------------------------------------------

--
-- Table structure for table `installation_report_items`
--

CREATE TABLE `installation_report_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `assignment_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity_installed` int(10) UNSIGNED NOT NULL,
  `before_photo` varchar(255) NOT NULL,
  `after_photo` varchar(255) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `installation_report_items`
--

INSERT INTO `installation_report_items` (`id`, `report_id`, `assignment_item_id`, `quantity_installed`, `before_photo`, `after_photo`, `remarks`, `created_at`) VALUES
(1, 1, 1, 1, '698abcb98da7d_1770699961.jpg', '698abcb995ab3_1770699961.jpg', 'test', '2026-02-10 05:06:01'),
(2, 1, 2, 1, '698abcb99dbec_1770699961.jpg', '698abcb9a2f92_1770699961.jpg', '', '2026-02-10 05:06:01'),
(3, 2, 3, 1, '69a539a10912d_1772435873.jpg', '69a539a12897d_1772435873.jpg', '', '2026-03-02 07:17:53'),
(4, 2, 4, 1, '69a539a14dd99_1772435873.jpg', '69a539a16b0dc_1772435873.jpg', '', '2026-03-02 07:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `installation_report_photos`
--

CREATE TABLE `installation_report_photos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `photo_type` enum('before','after') NOT NULL COMMENT 'Before or after installation',
  `photo_filename` varchar(255) NOT NULL COMMENT 'Filename with GPS watermark',
  `caption` varchar(255) DEFAULT NULL COMMENT 'Optional description',
  `display_order` tinyint(3) UNSIGNED DEFAULT 0 COMMENT 'Order for display (0-255)',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Overall installation photos (before/after store photos)';

--
-- Dumping data for table `installation_report_photos`
--

INSERT INTO `installation_report_photos` (`id`, `report_id`, `photo_type`, `photo_filename`, `caption`, `display_order`, `uploaded_at`) VALUES
(1, 2, 'before', '69a539a0c1305_1772435872.jpg', NULL, 0, '2026-03-02 07:17:52'),
(2, 2, 'after', '69a539a0df9c3_1772435872.jpg', NULL, 0, '2026-03-02 07:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `installation_store_details`
--

CREATE TABLE `installation_store_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `agency_store_code` varchar(100) DEFAULT NULL COMMENT 'Agency-assigned store code',
  `date_of_visit` date NOT NULL COMMENT 'Date of dress-up/installation visit',
  `store_status` enum('agree_to_dressup','store_closed','refused_to_dressup','reschedule','others') NOT NULL COMMENT 'Status upon visit',
  `reschedule_date` datetime DEFAULT NULL COMMENT 'Reschedule date and time if applicable',
  `status_remarks` varchar(500) DEFAULT NULL COMMENT 'Additional remarks for status (max 500 chars)',
  `store_name_before` varchar(200) DEFAULT NULL COMMENT 'Store name before installation',
  `store_name_after` varchar(200) DEFAULT NULL COMMENT 'POS name in signage after installation',
  `owner_name` varchar(200) NOT NULL COMMENT 'Store owner complete name',
  `contact_number` varchar(15) NOT NULL COMMENT 'Contact number format: 0000-000-0000',
  `area_length` decimal(10,2) DEFAULT NULL COMMENT 'Length of store area in meters',
  `area_width` decimal(10,2) DEFAULT NULL COMMENT 'Width of store area in meters',
  `additional_area_sqm` decimal(10,2) DEFAULT NULL COMMENT 'Additional area for irregular shapes (L-shaped, etc.)',
  `total_area_sqm` decimal(10,2) GENERATED ALWAYS AS (ifnull(`area_length`,0) * ifnull(`area_width`,0) + ifnull(`additional_area_sqm`,0)) STORED COMMENT 'Auto-calculated total area in square meters',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Store-specific details and area measurements';

--
-- Dumping data for table `installation_store_details`
--

INSERT INTO `installation_store_details` (`id`, `report_id`, `agency_store_code`, `date_of_visit`, `store_status`, `reschedule_date`, `status_remarks`, `store_name_before`, `store_name_after`, `owner_name`, `contact_number`, `area_length`, `area_width`, `additional_area_sqm`, `created_at`, `updated_at`) VALUES
(1, 1, '', '2026-02-10', 'agree_to_dressup', NULL, '', 'awdaw', 'dsfsd', 'sdfs', '0912-345-6789', 10.00, 5.00, NULL, '2026-02-10 05:06:01', '2026-02-10 05:06:01'),
(2, 2, 'jhh', '2026-03-02', 'agree_to_dressup', NULL, '', 'ghjm', 'hgm', 'hjm', '0912-345-6789', 10.00, 10.00, NULL, '2026-03-02 07:17:52', '2026-03-02 07:17:52');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'piece',
  `quantity_available` int(10) UNSIGNED DEFAULT 0,
  `quantity_reserved` int(10) UNSIGNED DEFAULT 0,
  `quantity_installed` int(10) UNSIGNED DEFAULT 0,
  `reorder_level` int(10) UNSIGNED DEFAULT 10,
  `unit_cost` decimal(12,2) DEFAULT 0.00,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `item_code`, `item_name`, `category_id`, `description`, `unit`, `quantity_available`, `quantity_reserved`, `quantity_installed`, `reorder_level`, `unit_cost`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ITM111', 'ITEM NAME 1', 1, 'TESTTTTTTTTTTTTTTTTTT', 'piece', 79, 19, 2, 20, 0.00, 'active', 2, '2026-02-10 03:48:14', '2026-03-02 07:17:53'),
(2, 'ITM222', 'ITEM NAME 2', 2, 'TESTTTTTTTTT', 'piece', 39, 9, 2, 15, 0.00, 'active', 2, '2026-02-10 03:49:45', '2026-03-02 07:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `transaction_type` enum('stock_in','stock_out','reserved','released','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_type` enum('purchase','assignment','installation','maintenance','adjustment','return') DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `item_id`, `transaction_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'stock_in', 100, 'adjustment', NULL, 'Initial stock', 2, '2026-02-10 03:48:14'),
(2, 2, 'stock_in', 50, 'adjustment', NULL, 'Initial stock', 2, '2026-02-10 03:49:45'),
(3, 1, 'reserved', 20, 'assignment', 1, 'Reserved for assignment ASN-20260210-EFB3', 2, '2026-02-10 04:09:40'),
(4, 2, 'reserved', 10, 'assignment', 1, 'Reserved for assignment ASN-20260210-EFB3', 2, '2026-02-10 04:09:40'),
(5, 1, 'stock_out', 1, 'installation', 1, 'Installed via report INS-20260210-B16D', 3, '2026-02-10 05:06:01'),
(6, 2, 'stock_out', 1, 'installation', 1, 'Installed via report INS-20260210-B16D', 3, '2026-02-10 05:06:01'),
(7, 1, 'reserved', 1, 'assignment', 2, 'Reserved for assignment ASN-20260302-2865', 2, '2026-03-02 07:12:59'),
(8, 2, 'reserved', 1, 'assignment', 2, 'Reserved for assignment ASN-20260302-2865', 2, '2026-03-02 07:12:59'),
(9, 1, 'stock_out', 1, 'installation', 2, 'Installed via report INS-20260302-FACD', 3, '2026-03-02 07:17:53'),
(10, 2, 'stock_out', 1, 'installation', 2, 'Installed via report INS-20260302-FACD', 3, '2026-03-02 07:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `item_categories`
--

CREATE TABLE `item_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_categories`
--

INSERT INTO `item_categories` (`id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Category 1', 'Testing', 'active', '2026-02-10 03:47:24', '2026-02-10 03:47:24'),
(2, 'Category 2', 'TESTSSSSSS', 'active', '2026-02-10 03:49:12', '2026-02-10 03:49:12'),
(3, 'Testing', 'Testing', 'active', '2026-03-02 07:10:52', '2026-03-02 07:10:52');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_actions`
--

CREATE TABLE `maintenance_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED NOT NULL,
  `action_type` enum('repair','replace','reinstall','remove','other') NOT NULL,
  `performed_by` int(10) UNSIGNED NOT NULL,
  `action_date` date NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `before_photo` varchar(255) DEFAULT NULL,
  `after_photo` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `items_used` text DEFAULT NULL COMMENT 'JSON array of items and quantities used',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_item_requests`
--

CREATE TABLE `maintenance_item_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_code` varchar(50) NOT NULL,
  `ticket_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','partially_approved','rejected','issued','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_request_items`
--

CREATE TABLE `maintenance_request_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `quantity_requested` int(10) UNSIGNED NOT NULL,
  `quantity_approved` int(10) UNSIGNED DEFAULT 0,
  `quantity_issued` int(10) UNSIGNED DEFAULT 0,
  `status` enum('pending','approved','rejected','issued') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_tickets`
--

CREATE TABLE `maintenance_tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_code` varchar(50) NOT NULL,
  `inspection_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `installation_report_id` bigint(20) UNSIGNED NOT NULL,
  `maintenance_type` enum('repair','replacement','missing_item','general') NOT NULL,
  `priority` enum('low','normal','high','critical') DEFAULT 'normal',
  `description` text NOT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('open','assigned','in_progress','pending_items','completed','closed','cancelled') DEFAULT 'open',
  `created_by` int(10) UNSIGNED NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_tickets`
--

INSERT INTO `maintenance_tickets` (`id`, `ticket_code`, `inspection_item_id`, `installation_report_id`, `maintenance_type`, `priority`, `description`, `assigned_to`, `status`, `created_by`, `completed_at`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 'MNT-20260302-8923', 3, 1, 'repair', 'normal', 'Issue found during Month 1 inspection. Damaged: 1, Missing: 0, Needs Replacement: 0. ', 5, 'assigned', 4, NULL, NULL, '2026-03-02 07:25:48', '2026-03-02 07:26:58'),
(2, 'MNT-20260302-0226', 4, 1, 'repair', 'normal', 'Issue found during Month 1 inspection. Damaged: 1, Missing: 0, Needs Replacement: 0. ', 5, 'assigned', 4, NULL, NULL, '2026-03-02 07:25:48', '2026-03-02 07:27:02');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(1, 2, 'Password Reset', 'Your password has been reset by an administrator. Please login with your new password.', 'warning', NULL, 0, '2026-02-10 03:46:34'),
(2, 3, 'New Assignment', 'You have been assigned to install items at a new location. Code: ASN-20260210-EFB3', 'info', 'http://localhost/installation-tracking-system/modules/assignments/view.php?id=1', 0, '2026-02-10 04:09:40'),
(3, 2, 'New Installation Report', 'Installation report INS-20260210-B16D has been submitted for review.', 'info', 'http://localhost/installation-tracking-system/modules/installations/view.php?id=1', 0, '2026-02-10 05:06:01'),
(4, 4, 'Password Reset', 'Your password has been reset by an administrator. Please login with your new password.', 'warning', NULL, 0, '2026-02-10 05:06:53'),
(5, 3, 'Password Reset', 'Your password has been reset by an administrator. Please login with your new password.', 'warning', NULL, 0, '2026-03-02 07:06:00'),
(6, 2, 'Password Reset', 'Your password has been reset by an administrator. Please login with your new password.', 'warning', NULL, 0, '2026-03-02 07:08:31'),
(7, 3, 'New Assignment', 'You have been assigned to install items at a new location. Code: ASN-20260302-2865', 'info', 'http://localhost/installation-tracking-system/modules/assignments/view.php?id=2', 0, '2026-03-02 07:12:59'),
(8, 3, 'Password Reset', 'Your password has been reset by an administrator. Please login with your new password.', 'warning', NULL, 0, '2026-03-02 07:13:36'),
(9, 2, 'New Installation Report', 'Installation report INS-20260302-FACD has been submitted for review.', 'info', 'http://localhost/installation-tracking-system/modules/installations/view.php?id=2', 0, '2026-03-02 07:17:53'),
(10, 4, 'Password Reset', 'Your password has been reset by an administrator. Please login with your new password.', 'warning', NULL, 0, '2026-03-02 07:18:41'),
(11, 5, 'Password Reset', 'Your password has been reset by an administrator. Please login with your new password.', 'warning', NULL, 0, '2026-03-02 07:22:16'),
(12, 1, 'Inspection Completed', 'Month 1 inspection for INS-20260302-FACD completed. Status: Issues found', 'warning', 'http://localhost/installation-tracking-system/modules/inspections/view.php?id=1', 0, '2026-03-02 07:23:58'),
(13, 2, 'Inspection Completed', 'Month 1 inspection for INS-20260302-FACD completed. Status: Issues found', 'warning', 'http://localhost/installation-tracking-system/modules/inspections/view.php?id=1', 0, '2026-03-02 07:23:58'),
(14, 5, 'New Maintenance Ticket', 'Maintenance ticket MNT-20260302-8923 has been created from inspection.', 'warning', 'http://localhost/installation-tracking-system/modules/maintenance/view.php?id=1', 0, '2026-03-02 07:25:48'),
(15, 5, 'New Maintenance Ticket', 'Maintenance ticket MNT-20260302-0226 has been created from inspection.', 'warning', 'http://localhost/installation-tracking-system/modules/maintenance/view.php?id=2', 0, '2026-03-02 07:25:48'),
(16, 1, 'Inspection Completed', 'Month 1 inspection for INS-20260210-B16D completed. Status: Issues found', 'warning', 'http://localhost/installation-tracking-system/modules/inspections/view.php?id=2', 0, '2026-03-02 07:25:48'),
(17, 2, 'Inspection Completed', 'Month 1 inspection for INS-20260210-B16D completed. Status: Issues found', 'warning', 'http://localhost/installation-tracking-system/modules/inspections/view.php?id=2', 0, '2026-03-02 07:25:48'),
(18, 5, 'New Ticket Assigned', 'Maintenance ticket MNT-20260302-8923 has been assigned to you.', 'info', 'http://localhost/installation-tracking-system/modules/maintenance/view.php?id=1', 0, '2026-03-02 07:26:58'),
(19, 5, 'New Ticket Assigned', 'Maintenance ticket MNT-20260302-0226 has been assigned to you.', 'info', 'http://localhost/installation-tracking-system/modules/maintenance/view.php?id=2', 0, '2026-03-02 07:27:02');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `survey_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','active','closed') DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `allow_multiple` tinyint(1) DEFAULT 0 COMMENT 'Allow multiple responses per user',
  `target_roles` varchar(255) DEFAULT NULL COMMENT 'JSON array of roles that can answer',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `survey_code`, `title`, `description`, `status`, `start_date`, `end_date`, `is_anonymous`, `allow_multiple`, `target_roles`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'SRV-20251204-E3E9', 'sdfsdfsdfsdfsdf', 'sdfsdf', 'active', NULL, NULL, 0, 0, NULL, 1, '2025-12-04 08:23:46', '2025-12-04 08:23:58'),
(3, 'SRV-20251204-49F0', 'Testing Survey', 'Brief Description', 'active', '2025-12-03', '2025-12-08', 0, 1, NULL, 1, '2025-12-04 08:34:43', '2025-12-04 08:34:50'),
(4, 'SRV-20251205-4438', 'Testing Title', 'Testing Description', 'active', '2025-12-03', '2025-12-08', 0, 0, NULL, 2, '2025-12-05 05:43:59', '2025-12-05 05:44:08');

-- --------------------------------------------------------

--
-- Table structure for table `survey_answers`
--

CREATE TABLE `survey_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `response_id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `answer_text` text DEFAULT NULL COMMENT 'For text/textarea/number/date',
  `answer_options` longtext DEFAULT NULL COMMENT 'JSON array for checkbox, single value for radio/dropdown',
  `rating_value` int(11) DEFAULT NULL COMMENT 'For rating type',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `survey_answers`
--

INSERT INTO `survey_answers` (`id`, `response_id`, `question_id`, `answer_text`, `answer_options`, `rating_value`, `created_at`, `updated_at`) VALUES
(1, 1, 10, 'adasd', NULL, NULL, '2025-12-04 08:24:18', '2025-12-04 08:24:18'),
(2, 1, 11, 'asdasd', NULL, NULL, '2025-12-04 08:24:18', '2025-12-04 08:24:18'),
(3, 2, 12, NULL, 'b', NULL, '2025-12-04 08:35:21', '2025-12-04 08:35:21'),
(4, 2, 13, NULL, '[\"w\",\"e\",\"r\"]', NULL, '2025-12-04 08:35:21', '2025-12-04 08:35:21'),
(5, 2, 14, NULL, 's', NULL, '2025-12-04 08:35:21', '2025-12-04 08:35:21');

-- --------------------------------------------------------

--
-- Table structure for table `survey_questions`
--

CREATE TABLE `survey_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text','textarea','radio','checkbox','dropdown','rating','number','date') NOT NULL DEFAULT 'text',
  `options` longtext DEFAULT NULL COMMENT 'JSON array of options for radio/checkbox/dropdown',
  `is_required` tinyint(1) DEFAULT 1,
  `sort_order` int(10) UNSIGNED DEFAULT 0,
  `min_value` int(11) DEFAULT NULL COMMENT 'For rating/number types',
  `max_value` int(11) DEFAULT NULL COMMENT 'For rating/number types',
  `placeholder` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `survey_questions`
--

INSERT INTO `survey_questions` (`id`, `survey_id`, `question_text`, `question_type`, `options`, `is_required`, `sort_order`, `min_value`, `max_value`, `placeholder`, `created_at`, `updated_at`) VALUES
(10, 2, 'dsfsdf', 'text', NULL, 1, 0, 1, 5, '', '2025-12-04 08:23:46', '2025-12-04 08:23:46'),
(11, 2, 'sdfsd', 'text', NULL, 1, 1, 1, 5, '', '2025-12-04 08:23:46', '2025-12-04 08:23:46'),
(12, 3, 'Question 1', 'radio', '[\"a\",\"b\",\"c\"]', 1, 0, 1, 5, '', '2025-12-04 08:34:43', '2025-12-04 08:34:43'),
(13, 3, 'Question 2', 'checkbox', '[\"q\",\"w\",\"e\",\"r\",\"t\",\"y\"]', 1, 1, 1, 5, '', '2025-12-04 08:34:43', '2025-12-04 08:34:43'),
(14, 3, 'Question 3', 'dropdown', '[\"a\",\"s\",\"d\",\"f\",\"g\"]', 1, 2, 1, 5, '', '2025-12-04 08:34:43', '2025-12-04 08:34:43'),
(15, 4, 'Testing Question 1', 'text', NULL, 1, 0, 1, 5, 'Hint text hehehe', '2025-12-05 05:43:59', '2025-12-05 05:43:59'),
(16, 4, 'Testing Question 2', 'rating', NULL, 1, 1, 1, 5, '', '2025-12-05 05:43:59', '2025-12-05 05:43:59');

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses`
--

CREATE TABLE `survey_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `respondent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL if anonymous',
  `response_code` varchar(50) NOT NULL,
  `status` enum('in_progress','completed') DEFAULT 'in_progress',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `survey_responses`
--

INSERT INTO `survey_responses` (`id`, `survey_id`, `respondent_id`, `response_code`, `status`, `submitted_at`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 1, 'RSP-20251204-21487F', 'completed', '2025-12-04 08:24:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 08:24:18'),
(2, 3, 1, 'RSP-20251204-9C8907', 'completed', '2025-12-04 08:35:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 08:35:21');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'system_name', 'Installation & Maintenance Tracking System', 'text', 'System display name', '2025-12-03 02:43:11'),
(2, 'inspection_months', '6', 'number', 'Number of months for inspection cycle', '2025-12-03 02:43:11'),
(3, 'items_per_page', '25', 'number', 'Default pagination limit', '2025-12-03 02:43:11'),
(4, 'max_upload_size', '10485760', 'number', 'Maximum upload size in bytes (10MB)', '2025-12-03 02:43:11'),
(5, 'allowed_image_types', '[\"jpg\",\"jpeg\",\"png\",\"webp\"]', 'json', 'Allowed image file types', '2025-12-03 02:43:11'),
(6, 'gps_watermark_enabled', '1', 'boolean', 'Enable GPS watermark on photos', '2025-12-03 02:43:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('super_admin','user_1','user_2','user_3','user_4') NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `username`, `password`, `email`, `full_name`, `phone`, `role`, `profile_image`, `status`, `last_login`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'EMP-0001', 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin@system.com', 'Super Administrator', NULL, 'super_admin', NULL, 'active', '2026-03-02 15:28:22', NULL, '2025-12-03 02:43:11', '2026-03-02 07:28:22'),
(2, '2316117', 'salambra', '$2y$10$frZFUwIRR1W3CVORWr4xfeCaqXeagF6.F5FpegYqSqcHNnrJUTtAu', 'alambrashane0@gmail.com', 'Shane Alambra', '09123456789', 'user_1', NULL, 'active', '2026-03-02 15:12:23', 1, '2025-12-03 02:45:57', '2026-03-02 07:12:23'),
(3, 'EMP123', 'tuser2', '$2y$10$2g7EsmYhUau6.Hx2/uW2IutWf.HY6m8iSDyOTmCR7dVQ3zh0LmKKW', 'tuser@gmail.com', 'Testing User 2', '09123456789', 'user_2', NULL, 'active', '2026-03-02 15:14:17', 2, '2025-12-03 02:52:54', '2026-03-02 07:14:17'),
(4, 'EMP3434', 'tuser3', '$2y$10$yknnKaw7GicqSDjyH71BXeCvX7fIfIHuBxBMo39WKC5yCLcKdrd36', 'tuser3@gmail.com', 'Testing User 3', '09987654321', 'user_3', NULL, 'active', '2026-03-02 15:25:08', 2, '2025-12-03 03:15:46', '2026-03-02 07:25:08'),
(5, 'EMP232323', 'tuser4', '$2y$10$fKhDD7YzOLJxFV8jyPdKxe6NwGgrzvOAq9.tf/ZuknBtpnu83y9g2', 'tuser4@gmail.com', 'Testing User 43', '09123456788', 'user_4', NULL, 'active', '2026-03-02 15:27:14', 2, '2025-12-03 03:25:33', '2026-03-02 07:27:14'),
(6, 'EMP343434', 'tuser5', '$2y$10$ZjkLeXWm.MJzHzV522cIAeSQEX6CyVslRNqswY26l4AJ.Pmd.6/.S', 'testinguser5@gmail.com', 'Testing User 5', '123123123', 'user_3', NULL, 'active', '2025-12-04 10:38:35', 2, '2025-12-04 01:57:20', '2025-12-04 02:38:35');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `vw_dashboard_stats` (
`total_active_users` bigint(21)
,`total_items` bigint(21)
,`total_stock` decimal(32,0)
,`pending_assignments` bigint(21)
,`ongoing_assignments` bigint(21)
,`pending_reviews` bigint(21)
,`overdue_inspections` bigint(21)
,`open_tickets` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_inspections_due`
-- (See below for the actual view)
--
CREATE TABLE `vw_inspections_due` (
`schedule_id` bigint(20) unsigned
,`month_number` tinyint(3) unsigned
,`scheduled_date` date
,`status` enum('pending','scheduled','completed','overdue','skipped')
,`report_code` varchar(50)
,`installation_date` date
,`area_name` varchar(200)
,`city` varchar(100)
,`inspector_name` varchar(200)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_installation_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_installation_summary` (
`id` bigint(20) unsigned
,`report_code` varchar(50)
,`installation_date` date
,`latitude` decimal(10,8)
,`longitude` decimal(11,8)
,`status` enum('submitted','reviewed','approved','rejected')
,`assignment_code` varchar(50)
,`area_name` varchar(200)
,`city` varchar(100)
,`installer_name` varchar(200)
,`item_types_installed` bigint(21)
,`total_items_installed` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_maintenance_overview`
-- (See below for the actual view)
--
CREATE TABLE `vw_maintenance_overview` (
`id` bigint(20) unsigned
,`ticket_code` varchar(50)
,`maintenance_type` enum('repair','replacement','missing_item','general')
,`priority` enum('low','normal','high','critical')
,`status` enum('open','assigned','in_progress','pending_items','completed','closed','cancelled')
,`created_at` timestamp
,`installation_code` varchar(50)
,`area_name` varchar(200)
,`assigned_to_name` varchar(200)
,`created_by_name` varchar(200)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_survey_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_survey_summary` (
`id` bigint(20) unsigned
,`survey_code` varchar(50)
,`title` varchar(255)
,`status` enum('draft','active','closed')
,`start_date` date
,`end_date` date
,`is_anonymous` tinyint(1)
,`created_at` timestamp
,`created_by_name` varchar(200)
,`question_count` bigint(21)
,`response_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_dashboard_stats`
--
DROP TABLE IF EXISTS `vw_dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_dashboard_stats`  AS SELECT (select count(0) from `users` where `users`.`status` = 'active') AS `total_active_users`, (select count(0) from `inventory_items` where `inventory_items`.`status` = 'active') AS `total_items`, (select coalesce(sum(`inventory_items`.`quantity_available`),0) from `inventory_items`) AS `total_stock`, (select count(0) from `assignments` where `assignments`.`status` = 'pending') AS `pending_assignments`, (select count(0) from `assignments` where `assignments`.`status` = 'in_progress') AS `ongoing_assignments`, (select count(0) from `installation_reports` where `installation_reports`.`status` = 'submitted') AS `pending_reviews`, (select count(0) from `inspection_schedules` where `inspection_schedules`.`status` = 'pending' and `inspection_schedules`.`scheduled_date` <= curdate()) AS `overdue_inspections`, (select count(0) from `maintenance_tickets` where `maintenance_tickets`.`status` in ('open','assigned','in_progress')) AS `open_tickets` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_inspections_due`
--
DROP TABLE IF EXISTS `vw_inspections_due`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_inspections_due`  AS SELECT `isc`.`id` AS `schedule_id`, `isc`.`month_number` AS `month_number`, `isc`.`scheduled_date` AS `scheduled_date`, `isc`.`status` AS `status`, `ir`.`report_code` AS `report_code`, `ir`.`installation_date` AS `installation_date`, `ia`.`area_name` AS `area_name`, `ia`.`city` AS `city`, `u`.`full_name` AS `inspector_name` FROM ((((`inspection_schedules` `isc` join `installation_reports` `ir` on(`isc`.`installation_report_id` = `ir`.`id`)) join `assignments` `a` on(`ir`.`assignment_id` = `a`.`id`)) join `installation_areas` `ia` on(`a`.`area_id` = `ia`.`id`)) left join `users` `u` on(`isc`.`inspector_id` = `u`.`id`)) WHERE `isc`.`status` in ('pending','scheduled','overdue') ;

-- --------------------------------------------------------

--
-- Structure for view `vw_installation_summary`
--
DROP TABLE IF EXISTS `vw_installation_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_installation_summary`  AS SELECT `ir`.`id` AS `id`, `ir`.`report_code` AS `report_code`, `ir`.`installation_date` AS `installation_date`, `ir`.`latitude` AS `latitude`, `ir`.`longitude` AS `longitude`, `ir`.`status` AS `status`, `a`.`assignment_code` AS `assignment_code`, `ia`.`area_name` AS `area_name`, `ia`.`city` AS `city`, `u`.`full_name` AS `installer_name`, count(`iri`.`id`) AS `item_types_installed`, sum(`iri`.`quantity_installed`) AS `total_items_installed` FROM ((((`installation_reports` `ir` join `assignments` `a` on(`ir`.`assignment_id` = `a`.`id`)) join `installation_areas` `ia` on(`a`.`area_id` = `ia`.`id`)) join `users` `u` on(`ir`.`installer_id` = `u`.`id`)) left join `installation_report_items` `iri` on(`ir`.`id` = `iri`.`report_id`)) GROUP BY `ir`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_maintenance_overview`
--
DROP TABLE IF EXISTS `vw_maintenance_overview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_maintenance_overview`  AS SELECT `mt`.`id` AS `id`, `mt`.`ticket_code` AS `ticket_code`, `mt`.`maintenance_type` AS `maintenance_type`, `mt`.`priority` AS `priority`, `mt`.`status` AS `status`, `mt`.`created_at` AS `created_at`, `ir`.`report_code` AS `installation_code`, `ia`.`area_name` AS `area_name`, `u1`.`full_name` AS `assigned_to_name`, `u2`.`full_name` AS `created_by_name` FROM (((((`maintenance_tickets` `mt` join `installation_reports` `ir` on(`mt`.`installation_report_id` = `ir`.`id`)) join `assignments` `a` on(`ir`.`assignment_id` = `a`.`id`)) join `installation_areas` `ia` on(`a`.`area_id` = `ia`.`id`)) left join `users` `u1` on(`mt`.`assigned_to` = `u1`.`id`)) join `users` `u2` on(`mt`.`created_by` = `u2`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_survey_summary`
--
DROP TABLE IF EXISTS `vw_survey_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_survey_summary`  AS SELECT `s`.`id` AS `id`, `s`.`survey_code` AS `survey_code`, `s`.`title` AS `title`, `s`.`status` AS `status`, `s`.`start_date` AS `start_date`, `s`.`end_date` AS `end_date`, `s`.`is_anonymous` AS `is_anonymous`, `s`.`created_at` AS `created_at`, `u`.`full_name` AS `created_by_name`, (select count(0) from `survey_questions` where `survey_questions`.`survey_id` = `s`.`id`) AS `question_count`, (select count(0) from `survey_responses` where `survey_responses`.`survey_id` = `s`.`id` and `survey_responses`.`status` = 'completed') AS `response_count` FROM (`surveys` `s` join `users` `u` on(`s`.`created_by` = `u`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_assignment_code` (`assignment_code`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_area` (`area_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`assignment_date`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `fk_assign_by` (`assigned_by`);

--
-- Indexes for table `assignment_items`
--
ALTER TABLE `assignment_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_assignment_item` (`assignment_id`,`item_id`),
  ADD KEY `idx_item` (`item_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `inspection_items`
--
ALTER TABLE `inspection_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inspection_report` (`inspection_report_id`),
  ADD KEY `idx_installation_item` (`installation_report_item_id`),
  ADD KEY `idx_status` (`item_status`),
  ADD KEY `idx_escalate` (`escalate_to_maintenance`);

--
-- Indexes for table `inspection_reports`
--
ALTER TABLE `inspection_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_inspection_code` (`inspection_code`),
  ADD KEY `idx_schedule` (`schedule_id`),
  ADD KEY `idx_inspector` (`inspector_id`),
  ADD KEY `idx_date` (`inspection_date`),
  ADD KEY `idx_status` (`overall_status`);

--
-- Indexes for table `inspection_schedules`
--
ALTER TABLE `inspection_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_report_month` (`installation_report_id`,`month_number`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`),
  ADD KEY `idx_inspector` (`inspector_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `installation_areas`
--
ALTER TABLE `installation_areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_area_code` (`area_code`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_coordinates` (`latitude`,`longitude`);

--
-- Indexes for table `installation_detailed_addresses`
--
ALTER TABLE `installation_detailed_addresses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_report_id` (`report_id`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_province` (`province`),
  ADD KEY `idx_barangay` (`barangay`);
ALTER TABLE `installation_detailed_addresses` ADD FULLTEXT KEY `ft_complete_address` (`complete_address`);

--
-- Indexes for table `installation_item_photos`
--
ALTER TABLE `installation_item_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_item_id` (`report_item_id`),
  ADD KEY `idx_photo_type` (`photo_type`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `installation_reports`
--
ALTER TABLE `installation_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_report_code` (`report_code`),
  ADD KEY `idx_assignment` (`assignment_id`),
  ADD KEY `idx_installer` (`installer_id`),
  ADD KEY `idx_date` (`installation_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_coordinates` (`latitude`,`longitude`);

--
-- Indexes for table `installation_report_items`
--
ALTER TABLE `installation_report_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report` (`report_id`),
  ADD KEY `idx_assignment_item` (`assignment_item_id`);

--
-- Indexes for table `installation_report_photos`
--
ALTER TABLE `installation_report_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_id` (`report_id`),
  ADD KEY `idx_photo_type` (`photo_type`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `installation_store_details`
--
ALTER TABLE `installation_store_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_report_id` (`report_id`),
  ADD KEY `idx_agency_code` (`agency_store_code`),
  ADD KEY `idx_store_status` (`store_status`),
  ADD KEY `idx_visit_date` (`date_of_visit`),
  ADD KEY `idx_total_sqm` (`total_area_sqm`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_item_code` (`item_code`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_quantity` (`quantity_available`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item` (`item_id`),
  ADD KEY `idx_type` (`transaction_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `item_categories`
--
ALTER TABLE `item_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `maintenance_actions`
--
ALTER TABLE `maintenance_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_performed_by` (`performed_by`),
  ADD KEY `idx_date` (`action_date`),
  ADD KEY `idx_type` (`action_type`);

--
-- Indexes for table `maintenance_item_requests`
--
ALTER TABLE `maintenance_item_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_request_code` (`request_code`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_requested_by` (`requested_by`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `maintenance_request_items`
--
ALTER TABLE `maintenance_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request` (`request_id`),
  ADD KEY `idx_item` (`item_id`);

--
-- Indexes for table `maintenance_tickets`
--
ALTER TABLE `maintenance_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_ticket_code` (`ticket_code`),
  ADD KEY `idx_inspection_item` (`inspection_item_id`),
  ADD KEY `idx_installation_report` (`installation_report_id`),
  ADD KEY `idx_type` (`maintenance_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_survey_code` (`survey_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_response_question` (`response_id`,`question_id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_survey` (`survey_id`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_response_code` (`response_code`),
  ADD KEY `idx_survey` (`survey_id`),
  ADD KEY `idx_respondent` (`respondent_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD UNIQUE KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assignment_items`
--
ALTER TABLE `assignment_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inspection_items`
--
ALTER TABLE `inspection_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inspection_reports`
--
ALTER TABLE `inspection_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inspection_schedules`
--
ALTER TABLE `inspection_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `installation_areas`
--
ALTER TABLE `installation_areas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `installation_detailed_addresses`
--
ALTER TABLE `installation_detailed_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `installation_item_photos`
--
ALTER TABLE `installation_item_photos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `installation_reports`
--
ALTER TABLE `installation_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `installation_report_items`
--
ALTER TABLE `installation_report_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `installation_report_photos`
--
ALTER TABLE `installation_report_photos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `installation_store_details`
--
ALTER TABLE `installation_store_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `item_categories`
--
ALTER TABLE `item_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `maintenance_actions`
--
ALTER TABLE `maintenance_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_item_requests`
--
ALTER TABLE `maintenance_item_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_request_items`
--
ALTER TABLE `maintenance_request_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_tickets`
--
ALTER TABLE `maintenance_tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `survey_answers`
--
ALTER TABLE `survey_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `survey_questions`
--
ALTER TABLE `survey_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_assign_area` FOREIGN KEY (`area_id`) REFERENCES `installation_areas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assign_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assign_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `assignment_items`
--
ALTER TABLE `assignment_items`
  ADD CONSTRAINT `fk_ai_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ai_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `inspection_items`
--
ALTER TABLE `inspection_items`
  ADD CONSTRAINT `fk_ii_inspection` FOREIGN KEY (`inspection_report_id`) REFERENCES `inspection_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ii_install_item` FOREIGN KEY (`installation_report_item_id`) REFERENCES `installation_report_items` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `inspection_reports`
--
ALTER TABLE `inspection_reports`
  ADD CONSTRAINT `fk_insp_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_insp_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `inspection_schedules` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `inspection_schedules`
--
ALTER TABLE `inspection_schedules`
  ADD CONSTRAINT `fk_is_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_is_report` FOREIGN KEY (`installation_report_id`) REFERENCES `installation_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `installation_detailed_addresses`
--
ALTER TABLE `installation_detailed_addresses`
  ADD CONSTRAINT `fk_detailed_address_report` FOREIGN KEY (`report_id`) REFERENCES `installation_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `installation_item_photos`
--
ALTER TABLE `installation_item_photos`
  ADD CONSTRAINT `fk_item_photos_report_item` FOREIGN KEY (`report_item_id`) REFERENCES `installation_report_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `installation_reports`
--
ALTER TABLE `installation_reports`
  ADD CONSTRAINT `fk_ir_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ir_installer` FOREIGN KEY (`installer_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `installation_report_items`
--
ALTER TABLE `installation_report_items`
  ADD CONSTRAINT `fk_iri_assignment_item` FOREIGN KEY (`assignment_item_id`) REFERENCES `assignment_items` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_iri_report` FOREIGN KEY (`report_id`) REFERENCES `installation_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `installation_report_photos`
--
ALTER TABLE `installation_report_photos`
  ADD CONSTRAINT `fk_report_photos_report` FOREIGN KEY (`report_id`) REFERENCES `installation_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `installation_store_details`
--
ALTER TABLE `installation_store_details`
  ADD CONSTRAINT `fk_store_details_report` FOREIGN KEY (`report_id`) REFERENCES `installation_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `fk_item_category` FOREIGN KEY (`category_id`) REFERENCES `item_categories` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `fk_trans_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_actions`
--
ALTER TABLE `maintenance_actions`
  ADD CONSTRAINT `fk_ma_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `maintenance_tickets` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ma_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_item_requests`
--
ALTER TABLE `maintenance_item_requests`
  ADD CONSTRAINT `fk_mir_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `maintenance_tickets` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mir_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_request_items`
--
ALTER TABLE `maintenance_request_items`
  ADD CONSTRAINT `fk_mri_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mri_request` FOREIGN KEY (`request_id`) REFERENCES `maintenance_item_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_tickets`
--
ALTER TABLE `maintenance_tickets`
  ADD CONSTRAINT `fk_mt_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mt_inspection_item` FOREIGN KEY (`inspection_item_id`) REFERENCES `inspection_items` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mt_installation` FOREIGN KEY (`installation_report_id`) REFERENCES `installation_reports` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `fk_survey_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answer_response` FOREIGN KEY (`response_id`) REFERENCES `survey_responses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD CONSTRAINT `fk_question_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD CONSTRAINT `fk_response_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_user` FOREIGN KEY (`respondent_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
