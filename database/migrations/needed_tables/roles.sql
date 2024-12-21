-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2024 at 11:11 PM
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
-- Database: `workbench1`
--

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'web', '2023-05-19 08:53:35', '2023-05-19 08:53:35'),
(3, 'Ops Manager', 'web', '2023-05-19 09:05:33', '2023-05-19 09:05:33'),
(4, 'Customer', 'web', '2023-05-19 09:05:43', '2023-05-19 09:05:43'),
(5, 'Store', 'web', '2023-05-19 09:06:01', '2023-05-19 09:06:01'),
(6, 'Driver', 'web', '2023-05-19 09:06:10', '2023-05-19 09:06:10'),
(7, 'Branch Manager', 'web', '2023-05-26 09:20:05', '2023-05-26 09:20:05'),
(8, 'Branch Staff', 'web', '2023-05-26 09:20:18', '2023-05-26 09:20:18'),
(9, 'Accountant', 'web', '2023-05-26 09:20:18', '2023-05-26 09:20:18'),
(10, 'Supplier', 'web', '2023-05-26 09:20:18', '2023-05-26 09:20:18'),
(11, 'panel_user', 'web', '2024-09-05 01:01:59', '2024-09-05 01:01:59'),
(14, 'Maintenance Manager', 'web', '2024-09-22 01:01:59', '2024-09-22 01:01:59'),
(15, 'Supervisor', 'web', '2024-09-22 01:01:59', '2024-09-22 01:01:59'),
(16, 'Finance Manager', 'web', '2024-09-22 01:01:59', '2024-09-22 01:01:59'),
(17, 'Attendance', 'web', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
