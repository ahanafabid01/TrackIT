-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 05:30 PM
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
-- Database: `trackit`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('Owner','Moderator','Accountant','Admin In-charge','Store In-charge') NOT NULL DEFAULT 'Owner',
  `owner_id` int(11) DEFAULT NULL COMMENT 'References the owner user_id. NULL for Owners, set for role-based users',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `owner_id`, `status`, `profile_picture`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Ahanaf Abid Sazid', 'srsrizon665@gmail.com', '$2y$10$JooP0F7doRrn5kXtJYQf9OPPkJmcNePu9ZYxG4YKRY4Kgm8tNsLta', NULL, 'Owner', NULL, 'Active', NULL, '2025-11-03 16:26:48', '2025-11-03 15:22:45', '2025-11-03 16:26:48'),
(8, 'Ahanaf Abid Sazid', 'ext.ahanaf@gmail.com', '$2y$10$vPCQquZNIqT6oqA6xJAG/uTsYqfXDTraPskkUwq340mrAiTdMdwmO', NULL, 'Owner', NULL, 'Active', NULL, NULL, '2025-11-03 16:20:59', '2025-11-03 16:20:59'),
(9, 'Ahanaf Abid Sazid', 'ahanaf.abid.sazid@g.bracu.ac.bd', '$2y$10$MBBka9MFn/ObK/owJSnB0.ZVEEoTNhXTnjaxeYFkfq1oHqRqFpLoi', NULL, 'Accountant', 1, 'Active', NULL, '2025-11-03 16:25:55', '2025-11-03 16:25:42', '2025-11-03 16:25:55'),
(10, 'Mr. accountant', 'ext.ahanaf.abid@gmail.com', '$2y$10$GUN1V8COnltfp4Gccz1k/e5b5ZSClsE0V1FOqd1nnzXtoMQnJrbJe', NULL, 'Moderator', 1, 'Active', NULL, NULL, '2025-11-03 16:27:28', '2025-11-03 16:27:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
