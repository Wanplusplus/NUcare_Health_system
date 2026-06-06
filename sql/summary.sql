-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 04, 2026 at 05:23 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nucaredb`
--

-- --------------------------------------------------------

--
-- Table structure for table `attachment_document_types`
--

CREATE TABLE `attachment_document_types` (
  `DocumentTypeID` smallint NOT NULL,
  `Category` varchar(60) NOT NULL,
  `DocumentType` varchar(100) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  `SortOrder` smallint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attachment_document_types`
--

INSERT INTO `attachment_document_types` (`DocumentTypeID`, `Category`, `DocumentType`, `IsActive`, `SortOrder`) VALUES
(1, 'Laboratory', 'Lab Result', 1, 10),
(2, 'Laboratory', 'CBC (Complete Blood Count)', 1, 11),
(3, 'Laboratory', 'Urinalysis', 1, 12),
(4, 'Laboratory', 'Blood Chemistry', 1, 13),
(5, 'Laboratory', 'Drug Test Result', 1, 14),
(6, 'Imaging', 'X-Ray', 1, 20),
(7, 'Imaging', 'Ultrasound', 1, 21),
(8, 'Imaging', 'ECG / EKG', 1, 22),
(9, 'Medical Certificate', 'Medical Certificate', 1, 30),
(10, 'Medical Certificate', 'Medical Certificate – Dress Down', 1, 31),
(11, 'Medical Certificate', 'Medical Certificate – Absence', 1, 32),
(12, 'Medical Certificate', 'Fit-to-Return Clearance', 1, 33),
(13, 'Medical Certificate', 'Medical Clearance', 1, 34),
(14, 'School Form', 'Health Status Declaration Form', 1, 40),
(15, 'School Form', 'Permit to Leave Form', 1, 41),
(16, 'School Form', 'Physical Examination Form', 1, 42),
(17, 'School Form', 'Immunization Record', 1, 43),
(18, 'Dental', 'Dental Examination Form', 1, 50),
(19, 'Dental', 'Dental Treatment Record', 1, 51),
(20, 'Prescription', 'Prescription', 1, 60),
(21, 'Referral', 'Referral Letter', 1, 70),
(22, 'Referral', 'Referral – Specialist', 1, 71),
(23, 'Other', 'Other', 1, 99);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `AuditLogID` int NOT NULL,
  `UserID` int NOT NULL,
  `Action` varchar(255) NOT NULL,
  `ModuleName` varchar(100) DEFAULT NULL,
  `TableAffected` varchar(100) DEFAULT NULL,
  `RecordID` int DEFAULT NULL,
  `ActionTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`AuditLogID`, `UserID`, `Action`, `ModuleName`, `TableAffected`, `RecordID`, `ActionTimestamp`) VALUES
(1, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 06:30:44'),
(2, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 06:30:52'),
(3, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 06:31:41'),
(4, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 06:31:48'),
(5, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 06:54:29'),
(6, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 06:54:34'),
(7, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 07:07:11'),
(8, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 07:07:53'),
(9, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 07:25:06'),
(10, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 07:30:07'),
(11, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:06:22'),
(12, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:06:39'),
(13, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:44:03'),
(14, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:44:08'),
(15, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:44:40'),
(16, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:44:47'),
(17, 9, 'Removed role', 'Admin Panel', 'Removed role: Admin', 8, '2026-06-03 10:45:00'),
(18, 9, 'Assigned role', 'Admin Panel', 'Assigned role: Doctor', 8, '2026-06-03 10:45:00'),
(19, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:45:05'),
(20, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:45:11'),
(21, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:45:18'),
(22, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:45:18'),
(23, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:45:39'),
(24, 9, 'Removed role', 'Admin Panel', 'Removed role: Doctor', 8, '2026-06-03 10:45:48'),
(25, 9, 'Assigned role', 'Admin Panel', 'Assigned role: Admin', 8, '2026-06-03 10:45:48'),
(26, 9, 'Deactivated user account', 'Admin Panel', 'Account deactivated', 8, '2026-06-03 10:45:58'),
(27, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:46:03'),
(28, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:46:29'),
(29, 9, 'Created/activated user account', 'Admin Panel', 'Account activated', 8, '2026-06-03 10:46:33'),
(30, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:46:34'),
(31, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:46:39'),
(32, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:53:22'),
(33, 5, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:53:32'),
(34, 5, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:56:35'),
(35, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:56:39'),
(36, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:57:01'),
(37, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:57:18'),
(38, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 10:58:05'),
(39, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 10:58:18'),
(40, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 11:18:14'),
(41, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 11:18:21'),
(42, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 11:18:34'),
(43, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 11:20:11'),
(44, 5, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 11:20:16'),
(45, 5, 'Added medicine Amoxicilin', 'Medicine', 'Added medicine Amoxicilin to inventory (Qty: 1000, Batch: N/A)', NULL, '2026-06-03 11:21:02'),
(46, 5, 'Adjusted inventory for Amoxicilin', 'Medicine', 'Stock in for Amoxicilin (Qty: 1000, Batch: N/A)', NULL, '2026-06-03 11:21:03'),
(47, 5, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 11:21:21'),
(48, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 11:21:28'),
(49, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 11:44:31'),
(50, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 11:44:40'),
(51, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 11:45:05'),
(52, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 11:45:14'),
(53, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 12:26:24'),
(54, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 12:26:34'),
(55, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 12:26:41'),
(56, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 12:26:50'),
(57, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 12:27:27'),
(58, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 12:27:39'),
(59, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 12:27:52'),
(60, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 12:28:03'),
(61, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 13:00:36'),
(62, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 13:00:49'),
(63, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 13:01:07'),
(64, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 13:01:12'),
(65, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 13:01:21'),
(66, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 13:01:49'),
(67, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 14:07:50'),
(68, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 14:17:35'),
(69, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 14:17:51'),
(70, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 14:18:01'),
(71, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 14:18:06'),
(72, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 14:18:14'),
(73, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 14:18:23'),
(74, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 14:18:36'),
(75, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 14:18:45'),
(76, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 14:20:23'),
(77, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 14:58:43'),
(78, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:05:18'),
(79, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:05:33'),
(80, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:05:39'),
(81, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:05:47'),
(82, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:05:53'),
(83, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:06:30'),
(84, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:06:40'),
(85, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:06:50'),
(86, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:06:55'),
(87, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:07:10'),
(88, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:07:18'),
(89, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:07:55'),
(90, 4, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:08:27'),
(91, 4, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:08:32'),
(92, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:08:40'),
(93, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:08:43'),
(94, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:08:57'),
(95, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:10:41'),
(96, 10, 'Created user account', 'User Management', 'Signup successful. Assigned role: Student', NULL, '2026-06-03 15:10:53'),
(97, 10, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:11:00'),
(98, 10, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:11:06'),
(99, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:11:11'),
(100, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:16:44'),
(101, 5, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:16:50'),
(102, 5, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:17:00'),
(103, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:18:03'),
(104, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:18:37'),
(105, 5, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:18:43'),
(106, 5, 'Started consultation for Samuel Lim', 'Consultation', 'Started a new consultation for Samuel Lim (ClinicTransactionID 1)', NULL, '2026-06-03 15:18:50'),
(107, 5, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 15:19:06'),
(108, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 15:19:11'),
(109, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 16:43:14'),
(110, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 16:43:23'),
(111, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 16:43:28'),
(112, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 16:46:10'),
(113, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 16:46:20'),
(114, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 16:47:12'),
(115, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 16:47:37'),
(116, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 16:47:56'),
(117, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 16:48:05'),
(118, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 16:51:30'),
(119, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 16:51:37'),
(120, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 16:57:54'),
(121, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 17:30:11'),
(122, 1, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 17:30:19'),
(123, 1, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 17:30:34'),
(124, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 17:30:41'),
(125, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 17:50:29'),
(126, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 17:50:41'),
(127, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 17:50:59'),
(128, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 17:51:11'),
(129, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 17:51:16'),
(130, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 17:51:34'),
(131, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 17:51:50'),
(132, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 18:04:19'),
(133, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 18:04:43'),
(134, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 18:15:12'),
(135, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 18:15:23'),
(136, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-03 18:47:25'),
(137, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-03 18:47:34'),
(138, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 02:58:31'),
(139, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 02:59:27'),
(140, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 02:59:49'),
(141, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 02:59:49'),
(142, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 03:00:04'),
(143, 9, 'reports_generated', 'Reports', 'Report History', NULL, '2026-06-04 03:00:46'),
(144, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 03:00:57'),
(145, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 03:01:43'),
(146, 9, 'reports_generated', 'Reports', 'Role & Permission Report', NULL, '2026-06-04 03:01:47'),
(147, 9, 'reports_generated', 'Reports', 'Role & Permission Report', NULL, '2026-06-04 03:02:17'),
(148, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 03:03:22'),
(149, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 03:08:35'),
(150, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 03:08:44'),
(151, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 03:09:14'),
(152, 9, 'reports_generated', 'Reports', 'Report History', NULL, '2026-06-04 03:09:17'),
(153, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 04:15:13'),
(154, 9, 'reports_generated', 'Reports', 'Report History', NULL, '2026-06-04 04:15:18'),
(155, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 04:15:24'),
(156, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 04:15:24'),
(157, 9, 'reports_generated', 'Reports', 'Role & Permission Report', NULL, '2026-06-04 04:15:35'),
(158, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 04:15:38'),
(159, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 04:15:38'),
(160, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 14:32:59'),
(161, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 14:34:49'),
(162, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 14:34:54'),
(163, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 14:35:02'),
(164, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 14:38:04'),
(165, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 14:39:07'),
(166, 9, 'reports_generated', 'Reports', 'Role & Permission Report', NULL, '2026-06-04 14:39:14'),
(167, 9, 'reports_generated', 'Reports', 'Account Status Report', NULL, '2026-06-04 14:51:41'),
(168, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 14:52:07'),
(169, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 14:52:20'),
(170, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 14:57:00'),
(171, 9, 'reports_generated', 'Reports', 'Account Status Report', NULL, '2026-06-04 15:17:03'),
(172, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 15:17:05'),
(173, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 15:17:27'),
(174, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:32'),
(175, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:32'),
(176, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:32'),
(177, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:32'),
(178, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:33'),
(179, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:33'),
(180, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:33'),
(181, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:33'),
(182, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:33'),
(183, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:34'),
(184, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:34'),
(185, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:17:34'),
(186, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 15:18:07'),
(187, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 15:18:15'),
(188, 9, 'reports_generated', 'Reports', 'Role & Permission Report', NULL, '2026-06-04 15:18:18'),
(189, 9, 'reports_generated', 'Reports', 'Account Status Report', NULL, '2026-06-04 15:18:20'),
(190, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:18:23'),
(191, 9, 'reports_generated', 'Reports', 'Report History', NULL, '2026-06-04 15:18:26'),
(192, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:18:29'),
(193, 9, 'reports_generated', 'Reports', 'Account Status Report', NULL, '2026-06-04 15:18:32'),
(194, 9, 'reports_generated', 'Reports', 'Role & Permission Report', NULL, '2026-06-04 15:18:34'),
(195, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 15:18:37'),
(196, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 15:18:40'),
(197, 9, 'reports_generated', 'Reports', 'Account Status Report', NULL, '2026-06-04 15:18:42'),
(198, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 15:18:44'),
(199, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 15:19:02'),
(200, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:05'),
(201, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:06'),
(202, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:06'),
(203, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:06'),
(204, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:06'),
(205, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:06'),
(206, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:06'),
(207, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:07'),
(208, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:07'),
(209, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:07'),
(210, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:19:07'),
(211, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 15:19:12'),
(212, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 15:19:23'),
(213, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 15:19:44'),
(214, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:36:22'),
(215, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:36:27'),
(216, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:36:29'),
(217, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:36:29'),
(218, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:36:29'),
(219, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:36:29'),
(220, 9, 'reports_generated', 'Reports', 'System Usage Report', NULL, '2026-06-04 15:36:30'),
(221, 9, 'reports_generated', 'Reports', 'Role & Permission Report', NULL, '2026-06-04 15:37:23'),
(222, 9, 'reports_generated', 'Reports', 'Role & Permission Report', NULL, '2026-06-04 15:37:23'),
(223, 9, 'reports_generated', 'Reports', 'Account Status Report', NULL, '2026-06-04 15:37:43'),
(224, 9, 'reports_generated', 'Reports', 'User Report', NULL, '2026-06-04 15:37:48'),
(225, 9, 'reports_generated', 'Reports', 'Audit Log Report', NULL, '2026-06-04 15:37:56'),
(226, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 15:44:03'),
(227, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 15:44:03'),
(228, 5, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 15:44:10'),
(229, 5, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 15:44:35'),
(230, 9, 'Failed login attempt', 'Authentication', 'Failed login attempt for School ID: 2024-116613 (Password mismatch)', NULL, '2026-06-04 15:44:39'),
(231, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 15:44:44'),
(232, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 15:44:55'),
(233, 8, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 15:45:05'),
(234, 8, 'reports_generated', 'Reports', 'Account Status Report', NULL, '2026-06-04 15:45:17'),
(235, 8, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 15:45:55'),
(236, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 15:46:01'),
(237, 9, 'Logged into the system', 'User Management', 'Login successful', NULL, '2026-06-04 17:20:27'),
(238, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 17:20:36'),
(239, 9, 'Logged out of the system', 'User Management', NULL, NULL, '2026-06-04 17:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `BookingID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `MedProfID` int DEFAULT NULL,
  `AvailabilityID` int DEFAULT NULL,
  `BookingType` enum('Appointment','Walk-In') NOT NULL DEFAULT 'Appointment',
  `ServiceType` varchar(100) DEFAULT NULL,
  `AppointmentDate` date DEFAULT NULL,
  `AppointmentStart` time DEFAULT NULL,
  `AppointmentEnd` time DEFAULT NULL,
  `ReasonForVisit` varchar(255) DEFAULT NULL,
  `BookingStatus` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
  `RequestDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `RescheduleProposedDate` date DEFAULT NULL,
  `RescheduleProposedStart` time DEFAULT NULL,
  `RescheduleProposedEnd` time DEFAULT NULL,
  `RescheduleStatus` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_transactions`
--

CREATE TABLE `clinic_transactions` (
  `ClinicTransactionID` int NOT NULL,
  `BookingID` int DEFAULT NULL,
  `SchoolPersonID` int NOT NULL,
  `MedProfID` int DEFAULT NULL,
  `VisitDate` date NOT NULL,
  `Complaint` varchar(255) DEFAULT NULL,
  `ServiceType` varchar(150) DEFAULT NULL,
  `ConsultationStatus` enum('Waiting','Consulting','Completed','Cancelled') DEFAULT 'Waiting',
  `Notes` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `clinic_transactions`
--

INSERT INTO `clinic_transactions` (`ClinicTransactionID`, `BookingID`, `SchoolPersonID`, `MedProfID`, `VisitDate`, `Complaint`, `ServiceType`, `ConsultationStatus`, `Notes`, `CreatedAt`) VALUES
(1, NULL, 1, NULL, '2026-06-03', NULL, NULL, 'Waiting', NULL, '2026-06-03 15:18:50');

-- --------------------------------------------------------

--
-- Table structure for table `consultation_attachments`
--

CREATE TABLE `consultation_attachments` (
  `AttachmentID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `UploadedBy` int DEFAULT NULL,
  `FileName` varchar(255) NOT NULL,
  `StoredName` varchar(255) NOT NULL,
  `FilePath` varchar(500) NOT NULL,
  `FileType` enum('image/jpeg','image/png','application/pdf') NOT NULL,
  `FileSizeBytes` int NOT NULL,
  `AttachmentCategory` enum('Lab Result','Medical Certificate','Dental','X-Ray','Prescription','Other') NOT NULL DEFAULT 'Other',
  `DocumentTypeID` smallint DEFAULT NULL,
  `Notes` varchar(500) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dental_transactions`
--

CREATE TABLE `dental_transactions` (
  `DentalTransactionID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `InventoryID` int DEFAULT NULL,
  `AttachmentID` int DEFAULT NULL,
  `AttachmentCategory` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergencies`
--

CREATE TABLE `emergencies` (
  `EmergencyID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `IncidentDate` date DEFAULT NULL,
  `IncidentTime` time DEFAULT NULL,
  `IncidentLocation` varchar(255) DEFAULT NULL,
  `BP` varchar(20) DEFAULT NULL,
  `RR` int DEFAULT NULL,
  `HR` int DEFAULT NULL,
  `Temperature` decimal(5,2) DEFAULT NULL,
  `TreatmentGiven` varchar(255) DEFAULT NULL,
  `AmbulanceNo` varchar(20) DEFAULT NULL,
  `TimeDispatched` time DEFAULT NULL,
  `TimeArrived` time DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_assignments`
--

CREATE TABLE `employee_assignments` (
  `AssignmentID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `Department` varchar(150) DEFAULT NULL,
  `PositionTitle` varchar(150) DEFAULT NULL,
  `EmploymentStatus` enum('Employed','Resigned','Inactive') NOT NULL DEFAULT 'Employed',
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_assignments`
--

INSERT INTO `employee_assignments` (`AssignmentID`, `SchoolPersonID`, `Department`, `PositionTitle`, `EmploymentStatus`, `StartDate`, `EndDate`, `CreatedAt`) VALUES
(1, 3, 'CCS', 'Professor', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33'),
(2, 4, 'Security Office', 'Security Guard', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33'),
(3, 5, 'Clinic', 'Doctor', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33'),
(4, 6, 'Clinic', 'Nurse', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33'),
(5, 7, 'Clinic', 'Dentist', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33'),
(6, 8, 'ICT Office', 'System Administrator', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33'),
(7, 9, 'ICT Office', 'Super Administrator', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33'),
(8, 12, 'CAS', 'Instructor', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33'),
(9, 13, 'Registrar', 'Office Staff', 'Employed', '2024-06-01', NULL, '2026-06-03 06:30:33');

-- --------------------------------------------------------

--
-- Table structure for table `medical_certificates`
--

CREATE TABLE `medical_certificates` (
  `MedicalCertificateID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `AttachmentID` int DEFAULT NULL,
  `IssuedByMedProfID` int DEFAULT NULL,
  `CertificateType` varchar(100) DEFAULT NULL,
  `ValidUntil` date DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_professionals`
--

CREATE TABLE `medical_professionals` (
  `MedProfID` int NOT NULL,
  `UserID` int NOT NULL,
  `Profession` enum('Doctor','Dentist','Nurse') NOT NULL,
  `Unit` varchar(100) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medical_professionals`
--

INSERT INTO `medical_professionals` (`MedProfID`, `UserID`, `Profession`, `Unit`, `CreatedAt`) VALUES
(1, 5, 'Doctor', 'General Medicine', '2026-06-03 06:30:33'),
(2, 6, 'Nurse', 'Clinic Nurse Station', '2026-06-03 06:30:33'),
(3, 7, 'Dentist', 'Dental Clinic', '2026-06-03 06:30:33'),
(4, 8, 'Doctor', 'General', '2026-06-03 10:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `medical_professional_availability`
--

CREATE TABLE `medical_professional_availability` (
  `AvailabilityID` int NOT NULL,
  `MedProfID` int NOT NULL,
  `AvailableDate` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `SlotDurationMinutes` int DEFAULT '30',
  `AvailabilityStatus` enum('Available','Unavailable','Cancelled') DEFAULT 'Available',
  `Notes` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `MedicineID` int NOT NULL,
  `MedicineName` varchar(150) NOT NULL,
  `GenericName` varchar(150) DEFAULT NULL,
  `MedicineType` varchar(100) DEFAULT NULL,
  `Dosage` varchar(100) DEFAULT NULL,
  `Unit` varchar(50) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`MedicineID`, `MedicineName`, `GenericName`, `MedicineType`, `Dosage`, `Unit`, `Description`, `CreatedAt`) VALUES
(1, 'Amoxicilin', 'Paracetamol', 'Antibiotic', '500 mg', 'Bottle', '', '2026-06-03 11:21:02');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_dispensing`
--

CREATE TABLE `medicine_dispensing` (
  `DispensingID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `InventoryID` int NOT NULL,
  `QuantityDispensed` int NOT NULL,
  `Instructions` varchar(255) DEFAULT NULL,
  `DispensedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_inventory`
--

CREATE TABLE `medicine_inventory` (
  `InventoryID` int NOT NULL,
  `MedicineID` int NOT NULL,
  `BatchNumber` varchar(100) DEFAULT NULL,
  `Quantity` int NOT NULL DEFAULT '0',
  `ExpiryDate` date DEFAULT NULL,
  `DateReceived` date DEFAULT NULL,
  `ReorderLevel` int DEFAULT '10',
  `Status` enum('Available','Low Stock','Out Of Stock','Expired') DEFAULT 'Available',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medicine_inventory`
--

INSERT INTO `medicine_inventory` (`InventoryID`, `MedicineID`, `BatchNumber`, `Quantity`, `ExpiryDate`, `DateReceived`, `ReorderLevel`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 1, '', 1000, '2029-11-04', '2026-06-03', 10, 'Available', '2026-06-03 11:21:02', '2026-06-03 11:21:02');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_inventory_logs`
--

CREATE TABLE `medicine_inventory_logs` (
  `LogID` int NOT NULL,
  `InventoryID` int NOT NULL,
  `ActionType` enum('Stock In','Dispensed','Adjusted','Expired') NOT NULL,
  `QuantityChanged` int NOT NULL,
  `PerformedByUserID` int DEFAULT NULL,
  `Notes` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `ModuleID` int NOT NULL,
  `ModuleName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`ModuleID`, `ModuleName`, `Description`) VALUES
(1, 'Consultation', 'Consultation management'),
(2, 'Records', 'Clinic records'),
(3, 'Reports', 'Generated reports'),
(4, 'Medicine', 'Medicine inventory'),
(5, 'Schedule', 'Appointment scheduling'),
(6, 'Admin Panel', 'Administrative panel'),
(7, 'RBAC Management', 'Role permissions management'),
(8, 'User Management', 'Manage users'),
(9, 'Audit Logs', 'System audit logs');

-- --------------------------------------------------------

--
-- Table structure for table `patients_info`
--

CREATE TABLE `patients_info` (
  `id` int NOT NULL,
  `UserID` int NOT NULL,
  `contact_no` varchar(20) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `birth_date` date NOT NULL,
  `age` int NOT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `religion` varchar(30) DEFAULT NULL,
  `address` text,
  `guardian_name` varchar(100) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `mobile_no` varchar(20) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `emergency_address` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_family_history`
--

CREATE TABLE `patient_family_history` (
  `FamilyHistoryID` int NOT NULL,
  `UserID` int NOT NULL,
  `condition_name` varchar(120) NOT NULL,
  `relationship` varchar(80) NOT NULL,
  `notes` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `PermissionID` int NOT NULL,
  `PermissionName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`PermissionID`, `PermissionName`, `Description`) VALUES
(1, 'access', 'Allow module access'),
(2, 'view', 'Can view records'),
(3, 'create', 'Can create records'),
(4, 'edit', 'Can edit records'),
(5, 'delete', 'Can delete records'),
(6, 'approve', 'Can approve requests'),
(7, 'manage', 'Full management permissions');

-- --------------------------------------------------------

--
-- Table structure for table `physical_examinations`
--

CREATE TABLE `physical_examinations` (
  `PhysicalExamID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `ExamDate` date DEFAULT NULL,
  `Height` decimal(5,2) DEFAULT NULL,
  `Weight` decimal(5,2) DEFAULT NULL,
  `BloodPressure` varchar(20) DEFAULT NULL,
  `PulseRate` int DEFAULT NULL,
  `Ears` varchar(100) DEFAULT NULL,
  `EyesPupil` varchar(100) DEFAULT NULL,
  `Heart` varchar(100) DEFAULT NULL,
  `Nose` varchar(100) DEFAULT NULL,
  `Thorax` varchar(100) DEFAULT NULL,
  `Abdomen` varchar(100) DEFAULT NULL,
  `Lungs` varchar(100) DEFAULT NULL,
  `Skin` varchar(100) DEFAULT NULL,
  `Extremities` varchar(100) DEFAULT NULL,
  `Deformities` varchar(100) DEFAULT NULL,
  `CardioClearance` enum('Fit','Unfit','Pending') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `physical_examinations`
--

INSERT INTO `physical_examinations` (`PhysicalExamID`, `ClinicTransactionID`, `ExamDate`, `Height`, `Weight`, `BloodPressure`, `PulseRate`, `Ears`, `EyesPupil`, `Heart`, `Nose`, `Thorax`, `Abdomen`, `Lungs`, `Skin`, `Extremities`, `Deformities`, `CardioClearance`) VALUES
(1, 1, '2026-06-03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `ProgramID` int NOT NULL,
  `ProgramName` varchar(150) NOT NULL,
  `Department` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`ProgramID`, `ProgramName`, `Department`) VALUES
(1, 'BS Information Technology', 'CCS'),
(2, 'BS Nursing', 'CON'),
(3, 'BS Psychology', 'CAS');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `ReportID` int NOT NULL,
  `GeneratedByUserID` int NOT NULL,
  `ReportType` varchar(100) DEFAULT NULL,
  `ReportDescription` varchar(255) DEFAULT NULL,
  `GeneratedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`ReportID`, `GeneratedByUserID`, `ReportType`, `ReportDescription`, `GeneratedAt`) VALUES
(1, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 02:58:31'),
(2, 9, 'User Report', 'User Report | Range: All Time | Role: Staff', '2026-06-04 02:59:27'),
(3, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 02:59:49'),
(4, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 02:59:49'),
(5, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 03:00:04'),
(6, 9, 'Report History', 'Report History | Range: All Time', '2026-06-04 03:00:46'),
(7, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 03:00:57'),
(8, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 03:01:43'),
(9, 9, 'Role & Permission Report', 'Role & Permission Report | Range: All Time', '2026-06-04 03:01:47'),
(10, 9, 'Role & Permission Report', 'Role & Permission Report | Range: All Time', '2026-06-04 03:02:17'),
(11, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 03:03:22'),
(12, 9, 'User Report', 'User Report | Range: Today', '2026-06-04 03:08:35'),
(13, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 03:08:44'),
(14, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 03:09:14'),
(15, 9, 'Report History', 'Report History | Range: All Time', '2026-06-04 03:09:17'),
(16, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 04:15:13'),
(17, 9, 'Report History', 'Report History | Range: All Time', '2026-06-04 04:15:18'),
(18, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 04:15:24'),
(19, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 04:15:24'),
(20, 9, 'Role & Permission Report', 'Role & Permission Report | Range: All Time', '2026-06-04 04:15:35'),
(21, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 04:15:38'),
(22, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 04:15:38'),
(23, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 14:34:49'),
(24, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 14:34:54'),
(25, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 14:35:02'),
(26, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 14:38:04'),
(27, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 14:39:07'),
(28, 9, 'Role & Permission Report', 'Role & Permission Report | Range: All Time', '2026-06-04 14:39:14'),
(29, 9, 'Account Status Report', 'Account Status Report | Range: All Time', '2026-06-04 14:51:41'),
(30, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 14:52:07'),
(31, 9, 'User Report', 'User Report | Range: All Time | Role: Dentist', '2026-06-04 14:52:20'),
(32, 9, 'Account Status Report', 'Account Status Report | Range: All Time', '2026-06-04 15:17:03'),
(33, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 15:17:05'),
(34, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 15:18:15'),
(35, 9, 'Role & Permission Report', 'Role & Permission Report | Range: All Time', '2026-06-04 15:18:18'),
(36, 9, 'Account Status Report', 'Account Status Report | Range: All Time', '2026-06-04 15:18:20'),
(37, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:18:23'),
(38, 9, 'Report History', 'Report History | Range: All Time', '2026-06-04 15:18:26'),
(39, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:18:29'),
(40, 9, 'Account Status Report', 'Account Status Report | Range: All Time', '2026-06-04 15:18:32'),
(41, 9, 'Role & Permission Report', 'Role & Permission Report | Range: All Time', '2026-06-04 15:18:34'),
(42, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 15:18:37'),
(43, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 15:18:40'),
(44, 9, 'Account Status Report', 'Account Status Report | Range: All Time', '2026-06-04 15:18:42'),
(45, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 15:18:44'),
(46, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 15:19:23'),
(47, 9, 'Audit Log Report', 'Audit Log Report | Range: Today', '2026-06-04 15:19:44'),
(48, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:36:22'),
(49, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:36:27'),
(50, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:36:28'),
(51, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:36:29'),
(52, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:36:29'),
(53, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:36:29'),
(54, 9, 'System Usage Report', 'System Usage Report | Range: All Time', '2026-06-04 15:36:30'),
(55, 9, 'Role & Permission Report', 'Role & Permission Report | Range: All Time', '2026-06-04 15:37:23'),
(56, 9, 'Role & Permission Report', 'Role & Permission Report | Range: All Time', '2026-06-04 15:37:23'),
(57, 9, 'Account Status Report', 'Account Status Report | Range: All Time', '2026-06-04 15:37:43'),
(58, 9, 'User Report', 'User Report | Range: All Time', '2026-06-04 15:37:48'),
(59, 9, 'Audit Log Report', 'Audit Log Report | Range: All Time', '2026-06-04 15:37:56'),
(60, 8, 'Account Status Report', 'Account Status Report | Range: All Time', '2026-06-04 15:45:17');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int NOT NULL,
  `RoleName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`RoleID`, `RoleName`, `Description`) VALUES
(1, 'Student', 'Default student role'),
(2, 'Faculty', 'Default faculty role'),
(3, 'Staff', 'Default staff role'),
(4, 'Doctor', 'Medical doctor role'),
(5, 'Dentist', 'Medical dentist role'),
(6, 'Nurse', 'Medical nurse role'),
(7, 'Admin', 'Administrative role'),
(8, 'Super Admin', 'Full system access');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `RolePermissionID` int NOT NULL,
  `RoleID` int NOT NULL,
  `ModuleID` int NOT NULL,
  `PermissionID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`RolePermissionID`, `RoleID`, `ModuleID`, `PermissionID`) VALUES
(1, 1, 2, 1),
(2, 1, 5, 1),
(4, 2, 2, 1),
(5, 2, 5, 1),
(7, 3, 2, 1),
(8, 3, 5, 1),
(11, 4, 1, 1),
(10, 4, 1, 7),
(15, 4, 2, 1),
(14, 4, 2, 7),
(17, 4, 3, 1),
(16, 4, 3, 7),
(13, 4, 4, 1),
(12, 4, 4, 7),
(19, 4, 5, 1),
(18, 4, 5, 7),
(26, 5, 1, 1),
(25, 5, 1, 7),
(30, 5, 2, 1),
(29, 5, 2, 7),
(32, 5, 3, 1),
(31, 5, 3, 7),
(28, 5, 4, 1),
(27, 5, 4, 7),
(34, 5, 5, 1),
(33, 5, 5, 7),
(41, 6, 1, 1),
(40, 6, 1, 7),
(45, 6, 2, 1),
(44, 6, 2, 7),
(47, 6, 3, 1),
(46, 6, 3, 7),
(43, 6, 4, 1),
(42, 6, 4, 7),
(49, 6, 5, 1),
(48, 6, 5, 7),
(57, 7, 3, 1),
(55, 7, 6, 1),
(58, 7, 8, 1),
(56, 7, 9, 1),
(65, 8, 3, 1),
(62, 8, 6, 1),
(64, 8, 7, 1),
(66, 8, 8, 1),
(63, 8, 9, 1);

-- --------------------------------------------------------

--
-- Table structure for table `school_people`
--

CREATE TABLE `school_people` (
  `SchoolPersonID` int NOT NULL,
  `SchoolID` varchar(50) DEFAULT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `MiddleName` varchar(100) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `PersonType` enum('Student','Faculty','Staff','Guard','Visitor','ROMAC') NOT NULL DEFAULT 'Visitor',
  `Sex` enum('Male','Female') NOT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `school_people`
--

INSERT INTO `school_people` (`SchoolPersonID`, `SchoolID`, `FirstName`, `LastName`, `MiddleName`, `Email`, `PersonType`, `Sex`, `CreatedAt`) VALUES
(1, '2024-116605', 'John', 'Carter', 'Lee', 'john.carter@nucare.edu', 'Student', 'Male', '2026-06-03 06:30:33'),
(2, '2024-116606', 'Angela', 'Reyes', 'Mae', 'angela.reyes@nucare.edu', 'Student', 'Female', '2026-06-03 06:30:33'),
(3, '2024-116607', 'Maria', 'Santos', 'Lopez', 'maria.santos@nucare.edu', 'Faculty', 'Female', '2026-06-03 06:30:33'),
(4, '2024-116608', 'Robert', 'Diaz', 'Tan', 'robert.diaz@nucare.edu', 'Staff', 'Male', '2026-06-03 06:30:33'),
(5, '2024-116609', 'Samuel', 'Lim', 'Torres', 'samuel.lim@nucare.edu', 'Staff', 'Male', '2026-06-03 06:30:33'),
(6, '2024-116610', 'Patricia', 'Cruz', 'Mendoza', 'patricia.cruz@nucare.edu', 'Staff', 'Female', '2026-06-03 06:30:33'),
(7, '2024-116611', 'Kevin', 'Garcia', 'Flores', 'kevin.garcia@nucare.edu', 'Staff', 'Male', '2026-06-03 06:30:33'),
(8, '2024-116612', 'Alice', 'Fernandez', 'Uy', 'alice.admin@nucare.edu', 'Staff', 'Female', '2026-06-03 06:30:33'),
(9, '2024-116613', 'Brian', 'Villanueva', 'Go', 'brian.super@nucare.edu', 'Staff', 'Male', '2026-06-03 06:30:33'),
(10, '2024-116614', 'Chris', 'Navarro', 'Lim', 'chris.navarro@nucare.edu', 'Student', 'Male', '2026-06-03 06:30:33'),
(11, '2024-116615', 'Samantha', 'Yu', 'Reyes', 'samantha.yu@nucare.edu', 'Student', 'Female', '2026-06-03 06:30:33'),
(12, '2024-116616', 'Daniel', 'Ong', 'Torres', 'daniel.ong@nucare.edu', 'Faculty', 'Male', '2026-06-03 06:30:33'),
(13, '2024-116617', 'Monica', 'Ramos', 'Sy', 'monica.ramos@nucare.edu', 'Staff', 'Female', '2026-06-03 06:30:33');

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `EnrollmentID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `ProgramID` int DEFAULT NULL,
  `AcademicYear` varchar(20) NOT NULL,
  `Semester` varchar(20) NOT NULL,
  `EnrollmentStatus` enum('Enrolled','Not Enrolled','Dropped','Graduated') NOT NULL DEFAULT 'Enrolled',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`EnrollmentID`, `SchoolPersonID`, `ProgramID`, `AcademicYear`, `Semester`, `EnrollmentStatus`, `CreatedAt`) VALUES
(1, 1, 1, '2025-2026', '1st Semester', 'Enrolled', '2026-06-03 06:30:33'),
(2, 2, 3, '2025-2026', '1st Semester', 'Enrolled', '2026-06-03 06:30:33'),
(3, 10, 1, '2025-2026', '1st Semester', 'Enrolled', '2026-06-03 06:30:33'),
(4, 11, 2, '2025-2026', '1st Semester', 'Enrolled', '2026-06-03 06:30:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `ResetToken` varchar(255) DEFAULT NULL,
  `TokenExpiry` datetime DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  `LastLogin` datetime DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `SchoolPersonID`, `PasswordHash`, `ResetToken`, `TokenExpiry`, `IsActive`, `LastLogin`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 1, '703b0a3d6ad75b649a28adde7d83c6251da457549263bc7ff45ec709b0a8448b', NULL, NULL, 1, '2026-06-04 01:30:19', '2026-06-03 06:30:33', '2026-06-03 17:30:19'),
(2, 2, 'fb1549ec668427876d6567d44607845418b75dd11639a2d0a3cbdcf826e878c2', NULL, NULL, 1, NULL, '2026-06-03 06:30:33', '2026-06-03 06:30:33'),
(3, 3, '27041f5856c7387a997252694afb048d1aa939228ffcdbd6285b979b8da20e7a', NULL, NULL, 1, NULL, '2026-06-03 06:30:33', '2026-06-03 06:30:33'),
(4, 4, '10176e7b7b24d317acfcf8d2064cfd2f24e154f7b5a96603077d5ef813d6a6b6', NULL, NULL, 1, '2026-06-03 23:08:27', '2026-06-03 06:30:33', '2026-06-03 15:08:27'),
(5, 5, 'f348d5628621f3d8f59c8cabda0f8eb0aa7e0514a90be7571020b1336f26c113', NULL, NULL, 1, '2026-06-04 23:44:10', '2026-06-03 06:30:33', '2026-06-04 15:44:10'),
(6, 6, '35608f3146571aa100227a3e68290979ba8a452179a080f888625106076e7de2', NULL, NULL, 1, NULL, '2026-06-03 06:30:33', '2026-06-03 06:30:33'),
(7, 7, '22990c57fbef2aeac16a2bf5e0caeafc43717c99e2040b0e3ac8d468d42794f0', NULL, NULL, 1, NULL, '2026-06-03 06:30:33', '2026-06-03 06:30:33'),
(8, 8, '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', NULL, NULL, 1, '2026-06-04 23:45:05', '2026-06-03 06:30:33', '2026-06-04 15:45:05'),
(9, 9, 'e34f92a20532a873cb3184398070b4b82a8fa29cf48572c203dc5f0fa6158231', NULL, NULL, 1, '2026-06-05 01:20:27', '2026-06-03 06:30:33', '2026-06-04 17:20:27'),
(10, 10, '96cae35ce8a9b0244178bf28e4966c2ce1b8385723a96a6b838858cdd6ca0a1e', NULL, NULL, 1, '2026-06-03 23:11:00', '2026-06-03 15:10:53', '2026-06-03 15:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `UserRoleID` int NOT NULL,
  `UserID` int NOT NULL,
  `RoleID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`UserRoleID`, `UserID`, `RoleID`) VALUES
(1, 1, 1),
(2, 2, 1),
(3, 3, 2),
(4, 4, 3),
(5, 5, 4),
(6, 6, 6),
(7, 7, 5),
(11, 8, 7),
(9, 9, 8),
(12, 10, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attachment_document_types`
--
ALTER TABLE `attachment_document_types`
  ADD PRIMARY KEY (`DocumentTypeID`),
  ADD UNIQUE KEY `uq_doc_type` (`Category`,`DocumentType`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`AuditLogID`),
  ADD KEY `fk_audit_user` (`UserID`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `fk_booking_person` (`SchoolPersonID`),
  ADD KEY `fk_booking_medprof` (`MedProfID`),
  ADD KEY `fk_booking_availability` (`AvailabilityID`);

--
-- Indexes for table `clinic_transactions`
--
ALTER TABLE `clinic_transactions`
  ADD PRIMARY KEY (`ClinicTransactionID`),
  ADD KEY `fk_transaction_booking` (`BookingID`),
  ADD KEY `fk_transaction_person` (`SchoolPersonID`),
  ADD KEY `fk_transaction_medprof` (`MedProfID`);

--
-- Indexes for table `consultation_attachments`
--
ALTER TABLE `consultation_attachments`
  ADD PRIMARY KEY (`AttachmentID`),
  ADD KEY `fk_attach_transaction` (`ClinicTransactionID`),
  ADD KEY `fk_ca_doctype` (`DocumentTypeID`);

--
-- Indexes for table `dental_transactions`
--
ALTER TABLE `dental_transactions`
  ADD PRIMARY KEY (`DentalTransactionID`),
  ADD UNIQUE KEY `uq_dental_ctid` (`ClinicTransactionID`),
  ADD KEY `fk_dental_inv` (`InventoryID`),
  ADD KEY `fk_dental_att` (`AttachmentID`);

--
-- Indexes for table `emergencies`
--
ALTER TABLE `emergencies`
  ADD PRIMARY KEY (`EmergencyID`),
  ADD KEY `fk_emergency_person` (`SchoolPersonID`);

--
-- Indexes for table `employee_assignments`
--
ALTER TABLE `employee_assignments`
  ADD PRIMARY KEY (`AssignmentID`),
  ADD KEY `fk_employee_person` (`SchoolPersonID`);

--
-- Indexes for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  ADD PRIMARY KEY (`MedicalCertificateID`),
  ADD KEY `fk_medcert_transaction` (`ClinicTransactionID`),
  ADD KEY `fk_medcert_medprof` (`IssuedByMedProfID`),
  ADD KEY `fk_mc_attachment` (`AttachmentID`);

--
-- Indexes for table `medical_professionals`
--
ALTER TABLE `medical_professionals`
  ADD PRIMARY KEY (`MedProfID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `medical_professional_availability`
--
ALTER TABLE `medical_professional_availability`
  ADD PRIMARY KEY (`AvailabilityID`),
  ADD KEY `fk_availability_medprof` (`MedProfID`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`MedicineID`);

--
-- Indexes for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  ADD PRIMARY KEY (`DispensingID`),
  ADD KEY `fk_dispense_transaction` (`ClinicTransactionID`),
  ADD KEY `fk_dispense_inventory` (`InventoryID`);

--
-- Indexes for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD PRIMARY KEY (`InventoryID`),
  ADD KEY `fk_inventory_medicine` (`MedicineID`);

--
-- Indexes for table `medicine_inventory_logs`
--
ALTER TABLE `medicine_inventory_logs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `fk_inventory_log_inventory` (`InventoryID`),
  ADD KEY `fk_inventory_log_user` (`PerformedByUserID`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`ModuleID`),
  ADD UNIQUE KEY `ModuleName` (`ModuleName`);

--
-- Indexes for table `patients_info`
--
ALTER TABLE `patients_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_patients_info_user` (`UserID`);

--
-- Indexes for table `patient_family_history`
--
ALTER TABLE `patient_family_history`
  ADD PRIMARY KEY (`FamilyHistoryID`),
  ADD KEY `idx_family_history_user` (`UserID`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`PermissionID`),
  ADD UNIQUE KEY `PermissionName` (`PermissionName`);

--
-- Indexes for table `physical_examinations`
--
ALTER TABLE `physical_examinations`
  ADD PRIMARY KEY (`PhysicalExamID`),
  ADD KEY `fk_physical_transaction` (`ClinicTransactionID`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`ProgramID`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `fk_report_user` (`GeneratedByUserID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`RolePermissionID`),
  ADD UNIQUE KEY `RoleID` (`RoleID`,`ModuleID`,`PermissionID`),
  ADD KEY `fk_rp_module` (`ModuleID`),
  ADD KEY `fk_rp_permission` (`PermissionID`);

--
-- Indexes for table `school_people`
--
ALTER TABLE `school_people`
  ADD PRIMARY KEY (`SchoolPersonID`),
  ADD UNIQUE KEY `SchoolID` (`SchoolID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_school_people_name_type` (`LastName`,`FirstName`,`PersonType`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD KEY `fk_enrollment_person` (`SchoolPersonID`),
  ADD KEY `fk_enrollment_program` (`ProgramID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `SchoolPersonID` (`SchoolPersonID`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`UserRoleID`),
  ADD UNIQUE KEY `UserID` (`UserID`,`RoleID`),
  ADD KEY `fk_ur_role` (`RoleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attachment_document_types`
--
ALTER TABLE `attachment_document_types`
  MODIFY `DocumentTypeID` smallint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `AuditLogID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `BookingID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinic_transactions`
--
ALTER TABLE `clinic_transactions`
  MODIFY `ClinicTransactionID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `consultation_attachments`
--
ALTER TABLE `consultation_attachments`
  MODIFY `AttachmentID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dental_transactions`
--
ALTER TABLE `dental_transactions`
  MODIFY `DentalTransactionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergencies`
--
ALTER TABLE `emergencies`
  MODIFY `EmergencyID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_assignments`
--
ALTER TABLE `employee_assignments`
  MODIFY `AssignmentID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  MODIFY `MedicalCertificateID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_professionals`
--
ALTER TABLE `medical_professionals`
  MODIFY `MedProfID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_professional_availability`
--
ALTER TABLE `medical_professional_availability`
  MODIFY `AvailabilityID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `MedicineID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  MODIFY `DispensingID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  MODIFY `InventoryID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medicine_inventory_logs`
--
ALTER TABLE `medicine_inventory_logs`
  MODIFY `LogID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `ModuleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `patients_info`
--
ALTER TABLE `patients_info`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_family_history`
--
ALTER TABLE `patient_family_history`
  MODIFY `FamilyHistoryID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `PermissionID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `physical_examinations`
--
ALTER TABLE `physical_examinations`
  MODIFY `PhysicalExamID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `ProgramID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `ReportID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `RolePermissionID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `school_people`
--
ALTER TABLE `school_people`
  MODIFY `SchoolPersonID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `EnrollmentID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `UserRoleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_availability` FOREIGN KEY (`AvailabilityID`) REFERENCES `medical_professional_availability` (`AvailabilityID`),
  ADD CONSTRAINT `fk_booking_medprof` FOREIGN KEY (`MedProfID`) REFERENCES `medical_professionals` (`MedProfID`),
  ADD CONSTRAINT `fk_booking_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `clinic_transactions`
--
ALTER TABLE `clinic_transactions`
  ADD CONSTRAINT `fk_transaction_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`),
  ADD CONSTRAINT `fk_transaction_medprof` FOREIGN KEY (`MedProfID`) REFERENCES `medical_professionals` (`MedProfID`),
  ADD CONSTRAINT `fk_transaction_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `consultation_attachments`
--
ALTER TABLE `consultation_attachments`
  ADD CONSTRAINT `fk_attach_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ca_doctype` FOREIGN KEY (`DocumentTypeID`) REFERENCES `attachment_document_types` (`DocumentTypeID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `dental_transactions`
--
ALTER TABLE `dental_transactions`
  ADD CONSTRAINT `fk_dental_att` FOREIGN KEY (`AttachmentID`) REFERENCES `consultation_attachments` (`AttachmentID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dental_ct` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dental_inv` FOREIGN KEY (`InventoryID`) REFERENCES `medicine_inventory` (`InventoryID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `employee_assignments`
--
ALTER TABLE `employee_assignments`
  ADD CONSTRAINT `fk_employee_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  ADD CONSTRAINT `fk_mc_attachment` FOREIGN KEY (`AttachmentID`) REFERENCES `consultation_attachments` (`AttachmentID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_medcert_medprof` FOREIGN KEY (`IssuedByMedProfID`) REFERENCES `medical_professionals` (`MedProfID`),
  ADD CONSTRAINT `fk_medcert_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`);

--
-- Constraints for table `medical_professionals`
--
ALTER TABLE `medical_professionals`
  ADD CONSTRAINT `fk_medprof_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `medical_professional_availability`
--
ALTER TABLE `medical_professional_availability`
  ADD CONSTRAINT `fk_availability_medprof` FOREIGN KEY (`MedProfID`) REFERENCES `medical_professionals` (`MedProfID`);

--
-- Constraints for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  ADD CONSTRAINT `fk_dispense_inventory` FOREIGN KEY (`InventoryID`) REFERENCES `medicine_inventory` (`InventoryID`),
  ADD CONSTRAINT `fk_dispense_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`);

--
-- Constraints for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD CONSTRAINT `fk_inventory_medicine` FOREIGN KEY (`MedicineID`) REFERENCES `medicines` (`MedicineID`);

--
-- Constraints for table `medicine_inventory_logs`
--
ALTER TABLE `medicine_inventory_logs`
  ADD CONSTRAINT `fk_inventory_log_inventory` FOREIGN KEY (`InventoryID`) REFERENCES `medicine_inventory` (`InventoryID`),
  ADD CONSTRAINT `fk_inventory_log_user` FOREIGN KEY (`PerformedByUserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `patients_info`
--
ALTER TABLE `patients_info`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient_family_history`
--
ALTER TABLE `patient_family_history`
  ADD CONSTRAINT `fk_family_history_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `physical_examinations`
--
ALTER TABLE `physical_examinations`
  ADD CONSTRAINT `fk_physical_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`GeneratedByUserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_module` FOREIGN KEY (`ModuleID`) REFERENCES `modules` (`ModuleID`),
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`PermissionID`) REFERENCES `permissions` (`PermissionID`),
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`);

--
-- Constraints for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `fk_enrollment_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`),
  ADD CONSTRAINT `fk_enrollment_program` FOREIGN KEY (`ProgramID`) REFERENCES `programs` (`ProgramID`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_ur_role` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`),
  ADD CONSTRAINT `fk_ur_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

ALTER TABLE clinic_transactions
  ADD COLUMN UpdatedAt TIMESTAMP NULL DEFAULT NULL;