-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 07:44 AM
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
-- Database: `etech_borrow`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrow_history`
--

CREATE TABLE `borrow_history` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `user_fullname` varchar(100) DEFAULT NULL,
  `action_type` enum('borrow','return') DEFAULT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `borrower_name` varchar(100) DEFAULT NULL,
  `borrower_phone` varchar(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `overdue_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_history`
--

INSERT INTO `borrow_history` (`id`, `equipment_id`, `user_fullname`, `action_type`, `action_date`, `borrower_name`, `borrower_phone`, `quantity`, `overdue_reason`) VALUES
(1, 1, 'พลอยหมวย ผู้ช่วยอาจารย์', 'return', '2026-04-28 02:28:23', NULL, NULL, 1, NULL),
(2, 2, 'พลอยหมวย ผู้ช่วยอาจารย์', 'return', '2026-04-28 02:28:24', NULL, NULL, 1, NULL),
(3, 2, 'พลอยหมวย ผู้ช่วยอาจารย์', 'borrow', '2026-04-28 02:30:45', NULL, NULL, 1, NULL),
(4, 1, 'พลอยหมวย ผู้ช่วยอาจารย์', 'borrow', '2026-04-28 02:30:47', NULL, NULL, 1, NULL),
(5, 1, 'กุลลดา เจริญ', 'return', '2026-04-28 02:53:34', NULL, NULL, 1, NULL),
(6, 1, 'กุลลดา เจริญ', 'borrow', '2026-04-28 02:53:38', NULL, NULL, 1, NULL),
(7, 1, 'กุลลดา เจริญ', 'return', '2026-04-28 03:00:57', NULL, NULL, 1, NULL),
(8, 1, 'กุลลดา เจริญ', 'borrow', '2026-04-28 03:01:26', 'กุลลดา เจริญ', '0628318047', 1, NULL),
(9, 1, 'กุลลดา เจริญ', 'return', '2026-04-28 03:03:55', NULL, NULL, 1, NULL),
(10, 1, 'กุลลดา เจริญ', 'borrow', '2026-04-28 03:04:10', 'กุลลดา เจริญ', '0628318047', 1, NULL),
(11, 1, 'กุลลดา เจริญ', 'return', '2026-04-28 03:04:20', NULL, NULL, 1, NULL),
(12, 1, 'กุลลดา เจริญ', 'borrow', '2026-04-28 03:07:49', '680528212', '0628318047', 2, NULL),
(13, 1, 'กุลลดา เจริญ', 'return', '2026-04-28 03:18:51', NULL, NULL, 1, NULL),
(14, 1, 'กุลลดา เจริญ', 'borrow', '2026-04-28 03:18:59', 'jdopsjgaporjfah', '64546635135186', 1, NULL),
(15, 3, 'กุลลดา เจริญ', 'return', '2026-04-28 03:24:47', NULL, NULL, 1, NULL),
(16, 3, 'กุลลดา เจริญ', 'borrow', '2026-04-28 03:24:58', 'กุลลดา เจริญ', '05413845451', 1, NULL),
(17, 1, 'พลอยหมวย (ผู้ดูแลระบบ)', 'return', '2026-04-28 03:57:59', NULL, NULL, 1, NULL),
(18, 1, 'พลอยหมวย (ผู้ดูแลระบบ)', 'return', '2026-04-28 03:58:02', NULL, NULL, 1, 'ส่งซ่อมบำรุง'),
(19, 1, 'พลอยหมวย (ผู้ดูแลระบบ)', 'return', '2026-04-28 03:58:07', NULL, NULL, 1, NULL),
(20, 1, 'พลอยหมวย (ผู้ดูแลระบบ)', 'return', '2026-04-28 03:58:10', NULL, NULL, 1, 'ส่งซ่อมบำรุง'),
(21, 1, 'พลอยหมวย (ผู้ดูแลระบบ)', 'return', '2026-04-28 04:01:45', NULL, NULL, 1, NULL),
(22, 1, 'พลอยหมวย (ผู้ดูแลระบบ)', 'return', '2026-04-28 04:01:47', NULL, NULL, 1, 'ส่งซ่อมบำรุง');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('available','borrowed','repairing') DEFAULT 'available',
  `image_url` varchar(255) DEFAULT NULL,
  `due_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `category`, `status`, `image_url`, `due_date`) VALUES
(1, 'Keyboard Mechanical Logitech', 'Peripheral', 'repairing', NULL, NULL),
(2, 'Monitor Dell 24 inch', 'Monitor', 'borrowed', NULL, NULL),
(3, 'iPad Pro 11 (2024)', 'Tablet', 'borrowed', NULL, '2026-05-05 05:24:58'),
(4, 'MacBook Air M2', 'Laptop', 'repairing', NULL, NULL),
(5, 'Mouse Wireless HP', 'Peripheral', 'borrowed', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `role` enum('admin','ta') DEFAULT 'ta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `role`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พลอยหมวย ผู้ช่วยอาจารย์', 'admin'),
(2, '680528212', 'ploy_200946', 'พลอยหมวย (ผู้ดูแลระบบ)', 'admin'),
(3, '67701901', '254699', 'กุลลดา เจริญ', 'ta');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrow_history`
--
ALTER TABLE `borrow_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrow_history`
--
ALTER TABLE `borrow_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_history`
--
ALTER TABLE `borrow_history`
  ADD CONSTRAINT `borrow_history_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
