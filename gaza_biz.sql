-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2026 at 10:53 AM
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
-- Database: `gaza_biz`
--

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `investor_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `donation_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `project_id`, `investor_id`, `amount`, `donation_date`) VALUES
(1, 3, 4, 50.00, '2026-07-25 07:20:55'),
(2, 3, 4, 200.00, '2026-07-25 07:33:58'),
(3, 3, 4, 200.00, '2026-07-25 07:39:12'),
(4, 3, 4, 4500.00, '2026-07-25 07:39:30'),
(5, 3, 2, 50.00, '2026-07-25 07:44:02');

-- --------------------------------------------------------

--
-- Table structure for table `interests`
--

CREATE TABLE `interests` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `investor_id` int(11) NOT NULL,
  `interest_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interests`
--

INSERT INTO `interests` (`id`, `project_id`, `investor_id`, `interest_date`) VALUES
(6, 3, 4, '2026-07-24 19:25:41'),
(12, 3, 2, '2026-07-25 07:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `project_id`, `message`, `sent_at`, `is_read`) VALUES
(2, 4, 5, 3, 'مرحبا', '2026-07-24 19:28:07', 1),
(3, 5, 5, 3, 'اهلا', '2026-07-24 19:35:59', 1),
(4, 4, 5, 3, 'ييري', '2026-07-24 19:36:29', 1),
(5, 4, 5, 3, 'هلا', '2026-07-24 19:43:30', 1),
(6, 5, 4, 3, 'يريي', '2026-07-24 19:46:27', 1),
(7, 5, 4, 3, 'نرحب', '2026-07-24 19:46:31', 1),
(8, 5, 4, 3, 'ؤر ؤ ر', '2026-07-24 19:47:06', 1),
(9, 4, 5, 3, 'ربرسءر', '2026-07-24 19:47:29', 1),
(10, 4, 5, 3, '44', '2026-07-24 19:47:32', 1),
(11, 5, 4, 3, 'تمام', '2026-07-24 19:50:19', 1),
(12, 4, 5, 3, 'تمام', '2026-07-24 19:50:49', 1),
(13, 4, 5, 3, 'هلا', '2026-07-24 19:59:05', 1),
(14, 4, 5, 3, 'مرحب', '2026-07-24 20:01:26', 1),
(15, 5, 4, 3, ',gh', '2026-07-25 07:16:09', 1),
(16, 4, 5, 3, 'jlhl', '2026-07-25 07:16:25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `target_budget` decimal(12,2) NOT NULL,
  `current_investment` decimal(12,2) DEFAULT 0.00,
  `interest_count` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `user_id`, `title`, `category`, `description`, `target_budget`, `current_investment`, `interest_count`, `status`, `created_at`) VALUES
(2, 5, 'منصة لتعليم الاطفال', 'تعليم', 'هذه المنصة تهدف الى تعليم الطلاب بشكل كامل ومتكامل', 5000.00, 0.00, 0, 'approved', '2026-07-24 19:01:16'),
(3, 5, 'سرسبربر', 'زراعة', 'سريثلقل', 5000.00, 5000.00, 2, 'approved', '2026-07-24 19:02:13'),
(5, 5, 'احمد', 'بيئة', 'ؤ ءبلا ب', 2000.00, 0.00, 0, 'approved', '2026-07-24 19:15:56'),
(6, 5, 'ئؤب', 'تجارة', 'ءر ء ؤبء', 410.00, 0.00, 0, 'rejected', '2026-07-24 19:16:20'),
(8, 5, 'سابتنسرنت', 'تجارة', 'لا]لأآلأ]لآ', 5000.00, 0.00, 0, 'approved', '2026-07-25 08:42:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('entrepreneur','investor','admin') DEFAULT 'entrepreneur',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `wallet` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `created_at`, `wallet`) VALUES
(1, 'محمد', 'moh@m.com', '$2y$10$pKv84OI/35CQw.vhgtJmjOSAaIp64lkJw45sSdNl9fr6LXZdO5rqu', 'admin', '2026-07-23 12:04:31', 0.00),
(2, 'احمد', 'Ahm@c.c', '$2y$10$/eH28exH3u37Y.YrtJvPUOutJmb3IgB0qNDq1t30vFRoyMxtX1sZe', 'investor', '2026-07-23 12:10:37', 9950.00),
(4, 'صابرين', 'ss@s.s', '$2y$10$UngjZGfy00RESN2KV0noCOT4hv2COrbh8eHNbPsutE/HWckEl.4Nq', 'investor', '2026-07-24 11:39:21', 5100.00),
(5, 'فهمي', 'fah@a.a', '$2y$10$uIg4nv1YT5KqKhvO9R5G4eE16U7mEAIQ2L6mDTb0Q/zkkoNum9dIC', 'entrepreneur', '2026-07-24 19:00:08', 10000.00),
(6, 'ياسر', 'yas@y.y', '$2y$10$YIxquKNnuuq.3JmBpH9czOhMqnLuiyg/l85z/3YM3xCN0kckH1YI.', 'investor', '2026-07-25 07:45:09', 20000.00),
(8, 'sab', 'sAn@a.m', '$2y$10$EnZXK74Os10hacdNR69B1OUZzriUSnVYyjuoLga6zizPEkSGp4i.S', 'entrepreneur', '2026-07-25 08:44:33', 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `investor_id` (`investor_id`);

--
-- Indexes for table `interests`
--
ALTER TABLE `interests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_interest` (`project_id`,`investor_id`),
  ADD KEY `investor_id` (`investor_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `interests`
--
ALTER TABLE `interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`investor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interests`
--
ALTER TABLE `interests`
  ADD CONSTRAINT `interests_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interests_ibfk_2` FOREIGN KEY (`investor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
