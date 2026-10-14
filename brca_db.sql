-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2026 at 05:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `brca_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `adoption_foster_apps`
--

CREATE TABLE `adoption_foster_apps` (
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `application_type` enum('Adopt','Foster') NOT NULL,
  `additional_info` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `application_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `animals`
--

CREATE TABLE `animals` (
  `animal_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `species` enum('Dog','Cat') NOT NULL,
  `breed` varchar(50) DEFAULT NULL,
  `estimated_age` varchar(20) DEFAULT NULL,
  `sex` enum('Male','Female','Unknown') DEFAULT NULL,
  `health_condition` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `intake_date` date NOT NULL,
  `intake_source` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('Available','Foster','Pending','Medical','Adopted') DEFAULT 'Available',
  `is_published` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `animals`
--

INSERT INTO `animals` (`animal_id`, `name`, `species`, `breed`, `estimated_age`, `sex`, `health_condition`, `description`, `intake_date`, `intake_source`, `photo`, `status`, `is_published`) VALUES
(1, 'Luna', 'Cat', '', '', 'Unknown', '', '', '2026-10-14', '', NULL, 'Available', 0),
(2, 'Luna', 'Dog', '', '', 'Unknown', '', '', '2026-10-14', '', NULL, 'Available', 0),
(3, 'Tabby', 'Cat', 'Tabby Cat', '1', 'Female', 'N/A', 'Cutie with stripes', '2026-10-14', 'Rescued', '3beaf61c10947b8d.jpg', 'Available', 0);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `announcement_type` varchar(30) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date_posted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donation_info`
--

CREATE TABLE `donation_info` (
  `donation_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `method_type` varchar(50) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `qr_image` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `record_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kennels`
--

CREATE TABLE `kennels` (
  `kennel_id` int(11) NOT NULL,
  `kennel_name` varchar(20) NOT NULL,
  `section` varchar(30) DEFAULT NULL,
  `capacity` int(11) DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kennels`
--

INSERT INTO `kennels` (`kennel_id`, `kennel_name`, `section`, `capacity`) VALUES
(1, 'A-1', 'Section A - Dogs', 4),
(2, 'A-2', 'Section A - Dogs', 4),
(3, 'A-3', 'Section A - Dogs', 4),
(4, 'A-4', 'Section A - Dogs', 4),
(5, 'A-5', 'Section A - Dogs', 4),
(6, 'A-6', 'Section A - Dogs', 4),
(7, 'B-1', 'Section B - Cats', 4),
(8, 'B-2', 'Section B - Cats', 4),
(9, 'B-3', 'Section B - Cats', 4),
(10, 'B-4', 'Section B - Cats', 4),
(11, 'B-5', 'Section B - Cats', 4),
(12, 'B-6', 'Section B - Cats', 4),
(13, 'Quarantine', 'Quarantine', 10),
(14, 'Medical', 'Medical', 10);

-- --------------------------------------------------------

--
-- Table structure for table `kennel_assignments`
--

CREATE TABLE `kennel_assignments` (
  `assignment_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `kennel_id` int(11) NOT NULL,
  `date_in` datetime DEFAULT current_timestamp(),
  `date_out` datetime DEFAULT NULL,
  `moved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kennel_assignments`
--

INSERT INTO `kennel_assignments` (`assignment_id`, `animal_id`, `kennel_id`, `date_in`, `date_out`, `moved_by`) VALUES
(1, 1, 9, '2026-10-14 07:05:17', NULL, 1),
(2, 2, 5, '2026-10-14 07:06:15', NULL, 1),
(3, 3, 1, '2026-10-14 13:17:13', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `contact_number`, `address`, `role`, `created_at`) VALUES
(1, 'Nicole', 'Tungol', 'nntungol@brca.com', '$2y$10$xdVvzQCGr5Jl.iF9Kn3k2OpnsjHWKLU/3j1gL9VxYIXBg.gfGJdHm', NULL, NULL, 'admin', '2026-10-14 03:49:18');

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_applications`
--

CREATE TABLE `volunteer_applications` (
  `volunteer_application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `application_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_schedules`
--

CREATE TABLE `volunteer_schedules` (
  `schedule_id` int(11) NOT NULL,
  `volunteer_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slots_available` int(11) NOT NULL,
  `status` enum('Open','Full','Closed') DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adoption_foster_apps`
--
ALTER TABLE `adoption_foster_apps`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `animal_id` (`animal_id`);

--
-- Indexes for table `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`animal_id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `donation_info`
--
ALTER TABLE `donation_info`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `animal_id` (`animal_id`);

--
-- Indexes for table `kennels`
--
ALTER TABLE `kennels`
  ADD PRIMARY KEY (`kennel_id`);

--
-- Indexes for table `kennel_assignments`
--
ALTER TABLE `kennel_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `animal_id` (`animal_id`),
  ADD KEY `kennel_id` (`kennel_id`),
  ADD KEY `moved_by` (`moved_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `volunteer_applications`
--
ALTER TABLE `volunteer_applications`
  ADD PRIMARY KEY (`volunteer_application_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `volunteer_schedules`
--
ALTER TABLE `volunteer_schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adoption_foster_apps`
--
ALTER TABLE `adoption_foster_apps`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `animals`
--
ALTER TABLE `animals`
  MODIFY `animal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donation_info`
--
ALTER TABLE `donation_info`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kennels`
--
ALTER TABLE `kennels`
  MODIFY `kennel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `kennel_assignments`
--
ALTER TABLE `kennel_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `volunteer_applications`
--
ALTER TABLE `volunteer_applications`
  MODIFY `volunteer_application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `volunteer_schedules`
--
ALTER TABLE `volunteer_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adoption_foster_apps`
--
ALTER TABLE `adoption_foster_apps`
  ADD CONSTRAINT `adoption_foster_apps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `adoption_foster_apps_ibfk_2` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `donation_info`
--
ALTER TABLE `donation_info`
  ADD CONSTRAINT `donation_info_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE CASCADE;

--
-- Constraints for table `kennel_assignments`
--
ALTER TABLE `kennel_assignments`
  ADD CONSTRAINT `kennel_assignments_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kennel_assignments_ibfk_2` FOREIGN KEY (`kennel_id`) REFERENCES `kennels` (`kennel_id`),
  ADD CONSTRAINT `kennel_assignments_ibfk_3` FOREIGN KEY (`moved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `volunteer_applications`
--
ALTER TABLE `volunteer_applications`
  ADD CONSTRAINT `volunteer_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `volunteer_applications_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `volunteer_schedules` (`schedule_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
