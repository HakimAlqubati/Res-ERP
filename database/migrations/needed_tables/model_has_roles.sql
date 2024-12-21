-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2024 at 11:12 PM
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
-- Table structure for table `model_has_roles`
--

CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(1, 'App\\Models\\User', 109),
(3, 'App\\Models\\User', 78),
(3, 'App\\Models\\User', 80),
(3, 'App\\Models\\User', 95),
(3, 'App\\Models\\User', 100),
(3, 'App\\Models\\User', 112),
(3, 'App\\Models\\User', 150),
(5, 'App\\Models\\User', 27),
(5, 'App\\Models\\User', 62),
(5, 'App\\Models\\User', 63),
(5, 'App\\Models\\User', 76),
(5, 'App\\Models\\User', 87),
(5, 'App\\Models\\User', 97),
(5, 'App\\Models\\User', 163),
(6, 'App\\Models\\User', 2),
(6, 'App\\Models\\User', 47),
(6, 'App\\Models\\User', 110),
(7, 'App\\Models\\User', 3),
(7, 'App\\Models\\User', 4),
(7, 'App\\Models\\User', 34),
(7, 'App\\Models\\User', 35),
(7, 'App\\Models\\User', 36),
(7, 'App\\Models\\User', 37),
(7, 'App\\Models\\User', 39),
(7, 'App\\Models\\User', 41),
(7, 'App\\Models\\User', 43),
(7, 'App\\Models\\User', 44),
(7, 'App\\Models\\User', 48),
(7, 'App\\Models\\User', 49),
(7, 'App\\Models\\User', 50),
(7, 'App\\Models\\User', 51),
(7, 'App\\Models\\User', 54),
(7, 'App\\Models\\User', 82),
(7, 'App\\Models\\User', 86),
(7, 'App\\Models\\User', 88),
(7, 'App\\Models\\User', 89),
(7, 'App\\Models\\User', 91),
(7, 'App\\Models\\User', 94),
(7, 'App\\Models\\User', 104),
(7, 'App\\Models\\User', 108),
(7, 'App\\Models\\User', 111),
(7, 'App\\Models\\User', 116),
(7, 'App\\Models\\User', 119),
(7, 'App\\Models\\User', 120),
(7, 'App\\Models\\User', 157),
(8, 'App\\Models\\User', 5),
(8, 'App\\Models\\User', 6),
(8, 'App\\Models\\User', 7),
(8, 'App\\Models\\User', 9),
(8, 'App\\Models\\User', 31),
(8, 'App\\Models\\User', 32),
(8, 'App\\Models\\User', 33),
(8, 'App\\Models\\User', 38),
(8, 'App\\Models\\User', 40),
(8, 'App\\Models\\User', 45),
(8, 'App\\Models\\User', 46),
(8, 'App\\Models\\User', 52),
(8, 'App\\Models\\User', 53),
(8, 'App\\Models\\User', 56),
(8, 'App\\Models\\User', 57),
(8, 'App\\Models\\User', 58),
(8, 'App\\Models\\User', 59),
(8, 'App\\Models\\User', 60),
(8, 'App\\Models\\User', 61),
(8, 'App\\Models\\User', 64),
(8, 'App\\Models\\User', 65),
(8, 'App\\Models\\User', 66),
(8, 'App\\Models\\User', 67),
(8, 'App\\Models\\User', 68),
(8, 'App\\Models\\User', 69),
(8, 'App\\Models\\User', 70),
(8, 'App\\Models\\User', 71),
(8, 'App\\Models\\User', 72),
(8, 'App\\Models\\User', 73),
(8, 'App\\Models\\User', 74),
(8, 'App\\Models\\User', 75),
(8, 'App\\Models\\User', 79),
(8, 'App\\Models\\User', 83),
(8, 'App\\Models\\User', 84),
(8, 'App\\Models\\User', 92),
(8, 'App\\Models\\User', 105),
(8, 'App\\Models\\User', 106),
(8, 'App\\Models\\User', 107),
(8, 'App\\Models\\User', 115),
(8, 'App\\Models\\User', 117),
(8, 'App\\Models\\User', 118),
(8, 'App\\Models\\User', 121),
(8, 'App\\Models\\User', 122),
(8, 'App\\Models\\User', 123),
(8, 'App\\Models\\User', 124),
(8, 'App\\Models\\User', 125),
(8, 'App\\Models\\User', 126),
(8, 'App\\Models\\User', 127),
(8, 'App\\Models\\User', 128),
(8, 'App\\Models\\User', 129),
(8, 'App\\Models\\User', 130),
(8, 'App\\Models\\User', 131),
(8, 'App\\Models\\User', 132),
(8, 'App\\Models\\User', 133),
(8, 'App\\Models\\User', 134),
(8, 'App\\Models\\User', 135),
(8, 'App\\Models\\User', 136),
(8, 'App\\Models\\User', 137),
(8, 'App\\Models\\User', 138),
(8, 'App\\Models\\User', 139),
(8, 'App\\Models\\User', 140),
(8, 'App\\Models\\User', 141),
(8, 'App\\Models\\User', 142),
(8, 'App\\Models\\User', 144),
(8, 'App\\Models\\User', 146),
(8, 'App\\Models\\User', 147),
(8, 'App\\Models\\User', 148),
(8, 'App\\Models\\User', 149),
(8, 'App\\Models\\User', 154),
(8, 'App\\Models\\User', 156),
(8, 'App\\Models\\User', 159),
(8, 'App\\Models\\User', 160),
(8, 'App\\Models\\User', 161),
(8, 'App\\Models\\User', 164),
(8, 'App\\Models\\User', 165),
(9, 'App\\Models\\User', 55),
(9, 'App\\Models\\User', 81),
(9, 'App\\Models\\User', 96),
(9, 'App\\Models\\User', 98),
(9, 'App\\Models\\User', 113),
(9, 'App\\Models\\User', 155),
(9, 'App\\Models\\User', 158),
(10, 'App\\Models\\User', 85),
(14, 'App\\Models\\User', 151),
(15, 'App\\Models\\User', 145),
(16, 'App\\Models\\User', 143),
(16, 'App\\Models\\User', 152),
(16, 'App\\Models\\User', 153),
(16, 'App\\Models\\User', 162),
(17, 'App\\Models\\User', 114);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
