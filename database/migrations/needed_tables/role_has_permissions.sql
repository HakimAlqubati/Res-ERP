-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2024 at 11:14 PM
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
-- Table structure for table `role_has_permissions`
--

CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(7, 1),
(7, 3),
(7, 9),
(8, 1),
(8, 3),
(8, 9),
(9, 1),
(9, 3),
(9, 9),
(10, 1),
(10, 3),
(10, 9),
(11, 1),
(11, 3),
(11, 9),
(12, 1),
(12, 3),
(12, 9),
(13, 1),
(13, 3),
(13, 9),
(14, 1),
(14, 3),
(14, 9),
(15, 1),
(15, 3),
(15, 9),
(16, 1),
(16, 3),
(16, 9),
(17, 1),
(17, 3),
(17, 9),
(18, 1),
(18, 3),
(18, 9),
(19, 1),
(19, 3),
(19, 9),
(20, 1),
(20, 3),
(20, 9),
(21, 1),
(21, 3),
(21, 9),
(22, 1),
(22, 3),
(22, 9),
(23, 1),
(23, 3),
(23, 9),
(24, 1),
(24, 3),
(24, 9),
(25, 1),
(25, 3),
(25, 9),
(26, 1),
(26, 3),
(26, 9),
(27, 1),
(27, 3),
(27, 9),
(28, 1),
(28, 3),
(28, 9),
(29, 1),
(29, 3),
(29, 9),
(30, 1),
(30, 3),
(30, 9),
(31, 1),
(31, 3),
(31, 9),
(32, 1),
(32, 3),
(32, 9),
(33, 1),
(33, 3),
(33, 9),
(34, 1),
(34, 3),
(34, 9),
(35, 1),
(35, 3),
(35, 9),
(36, 1),
(36, 3),
(36, 9),
(37, 1),
(37, 3),
(37, 9),
(38, 1),
(38, 3),
(38, 9),
(39, 1),
(39, 3),
(39, 9),
(40, 1),
(40, 3),
(40, 9),
(41, 1),
(41, 3),
(41, 9),
(42, 1),
(42, 3),
(42, 9),
(43, 1),
(43, 3),
(43, 9),
(44, 1),
(44, 3),
(44, 9),
(45, 1),
(45, 3),
(45, 9),
(46, 1),
(46, 3),
(46, 9),
(47, 1),
(47, 3),
(47, 9),
(48, 1),
(48, 3),
(48, 9),
(49, 1),
(49, 3),
(49, 9),
(50, 1),
(50, 3),
(50, 9),
(51, 1),
(51, 3),
(51, 9),
(52, 1),
(52, 3),
(52, 9),
(53, 1),
(53, 3),
(53, 9),
(54, 1),
(54, 3),
(54, 9),
(55, 1),
(55, 3),
(55, 9),
(56, 1),
(56, 3),
(56, 9),
(57, 1),
(57, 3),
(57, 9),
(58, 1),
(58, 3),
(58, 9),
(59, 1),
(59, 3),
(59, 9),
(60, 1),
(60, 3),
(60, 9),
(61, 1),
(61, 3),
(61, 9),
(62, 1),
(62, 3),
(62, 9),
(63, 1),
(63, 3),
(63, 9),
(64, 1),
(64, 3),
(64, 9),
(65, 1),
(65, 3),
(65, 9),
(66, 1),
(66, 3),
(66, 9),
(67, 1),
(67, 3),
(67, 9),
(68, 1),
(68, 3),
(68, 9),
(69, 1),
(69, 3),
(69, 9),
(70, 1),
(70, 3),
(70, 9),
(71, 1),
(71, 3),
(71, 9),
(72, 1),
(72, 3),
(72, 9),
(73, 1),
(73, 3),
(73, 9),
(74, 1),
(74, 3),
(74, 9),
(75, 1),
(75, 3),
(75, 9),
(76, 1),
(76, 3),
(76, 9),
(77, 1),
(77, 3),
(77, 9),
(78, 1),
(78, 3),
(78, 9),
(79, 1),
(79, 3),
(79, 9),
(80, 1),
(80, 3),
(80, 9),
(81, 1),
(81, 3),
(81, 9),
(82, 1),
(82, 3),
(82, 9),
(83, 1),
(83, 3),
(83, 9),
(84, 1),
(84, 3),
(84, 9),
(85, 1),
(85, 3),
(85, 9),
(86, 1),
(86, 3),
(86, 9),
(87, 1),
(87, 3),
(87, 9),
(88, 1),
(88, 3),
(88, 9),
(89, 1),
(89, 3),
(89, 9),
(90, 1),
(90, 3),
(90, 9),
(91, 1),
(91, 3),
(91, 9),
(92, 3),
(93, 3),
(94, 3),
(95, 1),
(96, 1),
(97, 1),
(98, 1),
(99, 1),
(100, 1),
(101, 1),
(102, 1),
(103, 1),
(104, 1),
(105, 1),
(106, 1),
(107, 1),
(108, 1),
(109, 1),
(110, 1),
(111, 1),
(112, 1),
(113, 1),
(114, 1),
(115, 1),
(116, 1),
(117, 1),
(118, 1),
(119, 1),
(120, 1),
(121, 1),
(122, 1),
(123, 1),
(124, 1),
(125, 1),
(126, 1),
(127, 1),
(128, 1),
(129, 1),
(130, 1),
(131, 1),
(132, 1),
(133, 1),
(134, 1),
(135, 1),
(136, 1),
(137, 1),
(138, 1),
(139, 1),
(140, 1),
(141, 1),
(142, 1),
(143, 1),
(144, 1),
(145, 1),
(146, 1),
(147, 1),
(148, 1),
(149, 1),
(150, 1),
(151, 1),
(152, 1),
(153, 1),
(154, 1),
(155, 1),
(156, 1),
(157, 1),
(158, 1),
(159, 1),
(160, 1),
(161, 1),
(162, 1),
(163, 1),
(164, 1),
(165, 1),
(166, 1),
(167, 8),
(167, 9),
(168, 8),
(168, 9),
(169, 9);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
