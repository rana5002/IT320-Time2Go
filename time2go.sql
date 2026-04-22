-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Apr 22, 2026 at 09:03 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `time2go`
--

-- --------------------------------------------------------

--
-- Table structure for table `Branch`
--

CREATE TABLE `Branch` (
  `branch_id` int NOT NULL,
  `location_id` int NOT NULL,
  `branch_name` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Branch`
--

INSERT INTO `Branch` (`branch_id`, `location_id`, `branch_name`, `address`) VALUES
(1, 1, 'King Fahd Road', 'King Fahd Rd, Al Wurud, Riyadh 12251'),
(2, 2, 'Al Mughrizat', 'Al Imam Saud Ibn Abdul Aziz Rd, Al Mughrizat, Riyadh 12483'),
(3, 3, 'Al Aqiq', 'Northern Ring Branch Rd, Al Aqiq, Riyadh 13511'),
(4, 4, 'Kingdom Centre', 'Kingdom Centre, Al Olaya, Riyadh'),
(5, 4, 'Al Nakheel Mall', 'Al Nakheel Mall, Al Mughrizat, Riyadh'),
(6, 4, 'Riyadh Park', 'Riyadh Park, Al Aqiq, Riyadh'),
(7, 4, 'Hayat Mall', 'Hayat Mall, At Takhassusi Rd, Al Nuzhah, Riyadh'),
(8, 5, 'Al Olaya', 'Olaya St, Al Olaya, Riyadh'),
(9, 5, 'Al Malqa', 'Prince Turki Al Awwal Rd, Al Malqa, Riyadh'),
(10, 5, 'Al Nakheel Mall', 'Al Nakheel Mall, Al Mughrizat, Riyadh'),
(11, 6, 'Hittin', 'Hittin District, Riyadh'),
(12, 6, 'Al Yasmin', 'Al Yasmin District, Riyadh'),
(13, 6, 'Panorama Mall', 'Panorama Mall, Al Wurud, Riyadh'),
(14, 7, 'Hayat Mall', 'Hayat Mall, At Takhassusi Rd, Al Nuzhah, Riyadh'),
(15, 7, 'Panorama Mall', 'Panorama Mall, Al Wurud, Riyadh'),
(16, 7, 'Granada Mall', 'Granada Mall, Ash Shuhada, Riyadh'),
(17, 8, 'Granada Mall', 'Granada Mall, Ash Shuhada, Riyadh'),
(18, 8, 'Hayat Mall', 'Hayat Mall, At Takhassusi Rd, Al Nuzhah, Riyadh'),
(19, 8, 'Al Nakheel Mall', 'Al Nakheel Mall, Al Mughrizat, Riyadh'),
(20, 9, 'Al Olaya', 'Olaya St, Al Olaya, Riyadh'),
(21, 9, 'Al Malqa', 'Prince Turki Al Awwal Rd, Al Malqa, Riyadh'),
(22, 9, 'Al Nuzhah', 'At Takhassusi Rd, Al Nuzhah, Riyadh');

-- --------------------------------------------------------

--
-- Table structure for table `CongestionRecord`
--

CREATE TABLE `CongestionRecord` (
  `record_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `current_level` enum('low','medium','high') NOT NULL,
  `predicted_level` enum('low','medium','high') NOT NULL,
  `suggestion_time` datetime DEFAULT NULL,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `CongestionRecord`
--

INSERT INTO `CongestionRecord` (`record_id`, `branch_id`, `current_level`, `predicted_level`, `suggestion_time`, `recorded_at`) VALUES
(1, 1, 'medium', 'high', '2026-04-22 21:00:00', '2026-04-22 11:49:32'),
(2, 2, 'high', 'medium', '2026-04-22 23:00:00', '2026-04-22 11:49:32'),
(3, 3, 'high', 'medium', '2026-04-22 22:30:00', '2026-04-22 11:49:32'),
(4, 4, 'high', 'medium', '2026-04-22 22:00:00', '2026-04-22 11:49:32'),
(5, 5, 'medium', 'low', '2026-04-22 21:00:00', '2026-04-22 11:49:32'),
(6, 6, 'low', 'medium', NULL, '2026-04-22 11:49:32'),
(7, 7, 'medium', 'high', '2026-04-22 20:00:00', '2026-04-22 11:49:32'),
(8, 8, 'high', 'medium', '2026-04-22 22:00:00', '2026-04-22 11:49:32'),
(9, 9, 'low', 'medium', NULL, '2026-04-22 11:49:32'),
(10, 10, 'medium', 'high', '2026-04-22 19:00:00', '2026-04-22 11:49:32'),
(11, 11, 'low', 'low', NULL, '2026-04-22 11:49:32'),
(12, 12, 'medium', 'low', '2026-04-22 21:30:00', '2026-04-22 11:49:32'),
(13, 13, 'high', 'high', '2026-04-22 22:00:00', '2026-04-22 11:49:32'),
(14, 14, 'low', 'medium', NULL, '2026-04-22 11:49:32'),
(15, 15, 'medium', 'medium', NULL, '2026-04-22 11:49:32'),
(16, 16, 'high', 'medium', '2026-04-22 21:00:00', '2026-04-22 11:49:32'),
(17, 17, 'high', 'high', '2026-04-22 21:00:00', '2026-04-22 11:49:32'),
(18, 18, 'medium', 'high', '2026-04-22 20:00:00', '2026-04-22 11:49:32'),
(19, 19, 'medium', 'medium', NULL, '2026-04-22 11:49:32'),
(20, 20, 'low', 'low', NULL, '2026-04-22 11:49:32'),
(21, 21, 'medium', 'low', '2026-04-22 22:00:00', '2026-04-22 11:49:32'),
(22, 22, 'low', 'medium', NULL, '2026-04-22 11:49:32');

-- --------------------------------------------------------

--
-- Table structure for table `Favorite`
--

CREATE TABLE `Favorite` (
  `favorite_id` int NOT NULL,
  `user_id` int NOT NULL,
  `branch_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Location`
--

CREATE TABLE `Location` (
  `location_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` enum('Mall','Cafe','Supermarket') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Location`
--

INSERT INTO `Location` (`location_id`, `name`, `category`) VALUES
(1, 'Panorama Mall', 'Mall'),
(2, 'Al Nakheel Mall', 'Mall'),
(3, 'Riyadh Park', 'Mall'),
(4, 'Starbucks', 'Cafe'),
(5, 'Dunkin', 'Cafe'),
(6, 'Tim Hortons', 'Cafe'),
(7, 'Danube', 'Supermarket'),
(8, 'Carrefour', 'Supermarket'),
(9, 'Tamimi Markets', 'Supermarket');

-- --------------------------------------------------------

--
-- Table structure for table `Notification`
--

CREATE TABLE `Notification` (
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `message` varchar(255) NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Notification`
--

INSERT INTO `Notification` (`notification_id`, `user_id`, `branch_id`, `message`, `status`, `timestamp`) VALUES
(9, 1, 2, 'Al Nakheel Mall is getting busy — consider coming later', 'unread', '2026-04-22 11:48:03'),
(10, 1, 4, 'Starbucks Kingdom Centre is crowded right now', 'unread', '2026-04-22 10:03:03'),
(11, 1, 8, 'Dunkin Al Olaya is quiet — good time to visit', 'unread', '2026-04-22 07:03:03'),
(12, 1, 14, 'Danube Hayat Mall is not busy right now', 'read', '2026-04-21 12:03:03'),
(13, 1, 17, 'Carrefour Granada Mall expects a busy evening', 'read', '2026-04-20 12:03:03'),
(14, 1, 20, 'Tamimi Al Olaya is quiet — perfect for a quick shop', 'read', '2026-04-18 12:03:03');

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `User`
--

INSERT INTO `User` (`user_id`, `name`, `email`, `password`, `notifications_enabled`) VALUES
(1, 'r', 'r@gmail.com', '$2y$10$.fMhWhGe6CiAG29K7bqGhuBSskzrtCgIxvLk32maai3nOv3RkP4kO', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Branch`
--
ALTER TABLE `Branch`
  ADD PRIMARY KEY (`branch_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `CongestionRecord`
--
ALTER TABLE `CongestionRecord`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `Favorite`
--
ALTER TABLE `Favorite`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `unique_fav` (`user_id`,`branch_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `Location`
--
ALTER TABLE `Location`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `Notification`
--
ALTER TABLE `Notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Branch`
--
ALTER TABLE `Branch`
  MODIFY `branch_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `CongestionRecord`
--
ALTER TABLE `CongestionRecord`
  MODIFY `record_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `Favorite`
--
ALTER TABLE `Favorite`
  MODIFY `favorite_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `Location`
--
ALTER TABLE `Location`
  MODIFY `location_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `Notification`
--
ALTER TABLE `Notification`
  MODIFY `notification_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Branch`
--
ALTER TABLE `Branch`
  ADD CONSTRAINT `branch_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `Location` (`location_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `CongestionRecord`
--
ALTER TABLE `CongestionRecord`
  ADD CONSTRAINT `congestionrecord_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `Branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `Favorite`
--
ALTER TABLE `Favorite`
  ADD CONSTRAINT `favorite_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `User` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `favorite_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `Branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `Notification`
--
ALTER TABLE `Notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `User` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `Branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
