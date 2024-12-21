-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2024 at 11:13 PM
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
-- Table structure for table `permissions`
--

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'view_role', 'web', '2023-05-18 23:53:35', '2023-05-18 23:53:35'),
(2, 'view_any_role', 'web', '2023-05-18 23:53:35', '2023-05-18 23:53:35'),
(3, 'create_role', 'web', '2023-05-18 23:53:35', '2023-05-18 23:53:35'),
(4, 'update_role', 'web', '2023-05-18 23:53:35', '2023-05-18 23:53:35'),
(5, 'delete_role', 'web', '2023-05-18 23:53:35', '2023-05-18 23:53:35'),
(6, 'delete_any_role', 'web', '2023-05-18 23:53:35', '2023-05-18 23:53:35'),
(7, 'view_order', 'web', '2023-07-18 10:16:31', '2023-07-18 10:16:31'),
(8, 'view_any_order', 'web', '2023-07-18 10:16:46', '2023-07-18 10:16:46'),
(9, 'view_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(10, 'view_any_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(11, 'create_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(12, 'update_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(13, 'restore_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(14, 'restore_any_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(15, 'replicate_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(16, 'reorder_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(17, 'delete_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(18, 'delete_any_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(19, 'force_delete_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(20, 'force_delete_any_branch', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(21, 'view_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(22, 'view_any_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(23, 'create_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(24, 'update_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(25, 'restore_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(26, 'restore_any_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(27, 'replicate_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(28, 'reorder_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(29, 'delete_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(30, 'delete_any_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(31, 'force_delete_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(32, 'force_delete_any_category', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(33, 'create_order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(34, 'update_order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(35, 'delete_order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(36, 'delete_any_order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(37, 'publish_order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(38, 'view_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(39, 'view_any_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(40, 'create_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(41, 'update_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(42, 'restore_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(43, 'restore_any_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(44, 'replicate_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(45, 'reorder_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(46, 'delete_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(47, 'delete_any_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(48, 'force_delete_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(49, 'force_delete_any_product', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(50, 'view_shield::role', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(51, 'view_any_shield::role', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(52, 'create_shield::role', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(53, 'update_shield::role', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(54, 'delete_shield::role', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(55, 'delete_any_shield::role', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(56, 'view_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(57, 'view_any_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(58, 'create_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(59, 'update_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(60, 'restore_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(61, 'restore_any_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(62, 'replicate_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(63, 'reorder_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(64, 'delete_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(65, 'delete_any_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(66, 'force_delete_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(67, 'force_delete_any_transfer::order', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(68, 'view_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(69, 'view_any_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(70, 'create_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(71, 'update_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(72, 'restore_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(73, 'restore_any_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(74, 'replicate_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(75, 'reorder_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(76, 'delete_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(77, 'delete_any_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(78, 'force_delete_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(79, 'force_delete_any_unit', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(80, 'view_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(81, 'view_any_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(82, 'create_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(83, 'update_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(84, 'restore_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(85, 'restore_any_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(86, 'replicate_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(87, 'reorder_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(88, 'delete_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(89, 'delete_any_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(90, 'force_delete_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(91, 'force_delete_any_user', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(92, 'widget_LatestOrders', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(93, 'widget_OrderChart', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(94, 'widget_StatsOverview', 'web', '2023-07-18 10:17:13', '2023-07-18 10:17:13'),
(95, 'view_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(96, 'view_any_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(97, 'create_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(98, 'update_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(99, 'restore_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(100, 'restore_any_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(101, 'replicate_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(102, 'reorder_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(103, 'delete_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(104, 'delete_any_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(105, 'force_delete_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(106, 'force_delete_any_purchase::invoice', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(107, 'view_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(108, 'view_any_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(109, 'create_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(110, 'update_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(111, 'restore_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(112, 'restore_any_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(113, 'replicate_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(114, 'reorder_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(115, 'delete_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(116, 'delete_any_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(117, 'force_delete_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(118, 'force_delete_any_reports::purchase::invoice::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(119, 'view_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(120, 'view_any_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(121, 'create_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(122, 'update_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(123, 'restore_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(124, 'restore_any_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(125, 'replicate_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(126, 'reorder_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(127, 'delete_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(128, 'delete_any_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(129, 'force_delete_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(130, 'force_delete_any_reports::stores::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(131, 'view_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(132, 'view_any_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(133, 'create_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(134, 'update_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(135, 'restore_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(136, 'restore_any_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(137, 'replicate_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(138, 'reorder_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(139, 'delete_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(140, 'delete_any_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(141, 'force_delete_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(142, 'force_delete_any_stock::report', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(143, 'view_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(144, 'view_any_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(145, 'create_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(146, 'update_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(147, 'restore_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(148, 'restore_any_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(149, 'replicate_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(150, 'reorder_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(151, 'delete_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(152, 'delete_any_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(153, 'force_delete_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(154, 'force_delete_any_store', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(155, 'view_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(156, 'view_any_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(157, 'create_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(158, 'update_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(159, 'restore_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(160, 'restore_any_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(161, 'replicate_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(162, 'reorder_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(163, 'delete_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(164, 'delete_any_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(165, 'force_delete_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(166, 'force_delete_any_supplier', 'web', '2024-03-28 08:15:00', '2024-03-28 08:15:00'),
(167, 'create_employee::application', 'web', '2024-10-20 15:14:01', '2024-10-20 15:14:01'),
(168, 'view_employee::application', 'web', '2024-10-20 15:14:01', '2024-10-20 15:14:01'),
(169, 'view_any_employee::application', 'web', '2024-11-11 08:57:08', '2024-11-11 08:57:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
