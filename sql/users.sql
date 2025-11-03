-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 04:53 PM
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('Owner','Moderator','Accountant','Admin In-charge','Store In-charge') NOT NULL DEFAULT 'Owner',
  `owner_id` int(11) DEFAULT NULL COMMENT 'References the owner user_id. NULL for Owners, set for role-based users',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `oauth_provider` varchar(50) DEFAULT NULL,
  `oauth_uid` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Add foreign key constraint after table creation
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Dumping data for table `users` (Sample data - Optional)
--

-- Uncomment the line below if you want sample data
-- INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `owner_id`, `status`, `oauth_provider`, `oauth_uid`, `profile_picture`, `created_at`, `last_login`) VALUES
-- (1, 'Sample Owner', 'owner@example.com', '$2y$10$WF8VKavUMyKBC18rzP6oZ.bq9Jg53NYwUMVOpNMKZ39ekmQwoszKq', 'Owner', NULL, 'Active', NULL, NULL, NULL, '2025-11-03 00:00:00', NULL);

--
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
