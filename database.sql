-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2026 at 01:57 PM
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
-- Database: `libtech_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `User_id` int(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password_hash` char(60) NOT NULL,
  `Role` enum('Member','Librarian') NOT NULL DEFAULT 'Member',
  `Last_login` timestamp NULL DEFAULT NULL,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `Is_active` tinyint(1) NOT NULL DEFAULT 1,
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_code` varchar(10) DEFAULT NULL,
  `two_factor_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`User_id`, `Email`, `Password_hash`, `Role`, `Last_login`, `Created_at`, `Is_active`, `failed_attempts`, `locked_until`, `two_factor_enabled`, `two_factor_secret`, `two_factor_code`, `two_factor_expires`) VALUES
(1, 'member@test.com', '$2y$10$U8jgasVpk6.EcNJMb.zBLuQf6gtt3sPrC7MUNZMHOFIwDYGqOJwyS', 'Member', '2026-05-20 07:38:38', '2026-05-08 09:55:05', 1, 0, NULL, 0, NULL, NULL, NULL),
(2, 'librarian@test.com', '$2y$10$U8jgasVpk6.EcNJMb.zBLuQf6gtt3sPrC7MUNZMHOFIwDYGqOJwyS', 'Librarian', '2026-05-24 11:54:36', '2026-05-08 09:55:05', 1, 0, NULL, 0, NULL, NULL, NULL),
(5, 'ggrabin50@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Member', '2026-05-10 20:25:49', '2026-05-10 22:25:37', 1, 0, NULL, 0, NULL, NULL, NULL),
(6, 'rabin55@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Librarian', '2026-05-19 08:38:06', '2026-05-10 22:33:38', 1, 0, NULL, 0, NULL, NULL, NULL),
(7, 'rabin67@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Member', '2026-05-11 08:07:51', '2026-05-11 10:07:38', 1, 0, NULL, 0, NULL, NULL, NULL),
(11, 'librarian@libtech.com', '$2y$10$U8jgasVpk6.EcNJMb.zBLuQf6gtt3sPrC7MUNZMHOFIwDYGqOJwyS', 'Librarian', NULL, '2026-05-19 10:33:47', 1, 0, NULL, 0, NULL, NULL, NULL),
(12, 'admin@libtech.com', '$2y$10$U8jgasVpk6.EcNJMb.zBLuQf6gtt3sPrC7MUNZMHOFIwDYGqOJwyS', 'Librarian', NULL, '2026-05-19 10:34:19', 1, 0, NULL, 0, NULL, NULL, NULL),
(14, 'testlibrarian@libtech.com', '$2y$10$U8jgasVpk6.EcNJMb.zBLuQf6gtt3sPrC7MUNZMHOFIwDYGqOJwyS', 'Librarian', NULL, '2026-05-19 10:36:40', 1, 0, NULL, 0, NULL, NULL, NULL),
(18, 'aayushapandit68@gmail.com', '$2y$10$esuHMz.7hcl5HKa3JGyjteUJgZxk60fHLUpRZfs2xs9Lw.WUz0sPi', 'Member', '2026-05-22 09:50:45', '2026-05-21 23:35:46', 1, 0, NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book` (
  `book_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(100) NOT NULL,
  `isbn` varchar(13) DEFAULT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `total_copies` int(11) DEFAULT 1,
  `available_copies` int(11) DEFAULT 1,
  `added_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`book_id`, `title`, `author`, `isbn`, `genre`, `total_copies`, `available_copies`, `added_date`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 'Fiction', 3, 1, '2026-05-05'),
(2, '1984', 'George Orwell', '9780452284234', 'Dystopian', 2, 0, '2026-05-05'),
(3, 'To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'Fiction', 2, 1, '2026-05-05'),
(94, 'Moby Dick', 'Herman Melville', '9781503280786', 'Fiction', 50, 50, '2026-05-22'),
(95, 'The Catcher in the Rye', 'J.D. Salinger', '9780316769488', 'Fiction', 50, 50, '2026-05-22'),
(96, 'Little Women', 'Louisa May Alcott', '9780147514011', 'Fiction', 50, 50, '2026-05-22'),
(97, 'Beloved', 'Toni Morrison', '9781400033416', 'Fiction', 50, 50, '2026-05-22'),
(98, 'The Old Man and the Sea', 'Ernest Hemingway', '9780684801223', 'Fiction', 50, 50, '2026-05-22'),
(99, 'The Grapes of Wrath', 'John Steinbeck', '9780143039433', 'Fiction', 50, 50, '2026-05-22'),
(100, 'Fahrenheit 451', 'Ray Bradbury', '9781451673319', 'Science', 50, 50, '2026-05-22'),
(101, 'The Road', 'Cormac McCarthy', '9780307387899', 'Fiction', 50, 50, '2026-05-22'),
(102, 'The Alchemist', 'Paulo Coelho', '9780061122415', 'Fiction', 50, 50, '2026-05-22'),
(103, 'Eleven Minutes', 'Paulo Coelho', '9780060589288', 'Fiction', 50, 50, '2026-05-22'),
(104, 'Brida', 'Paulo Coelho', '9780061762703', 'Fiction', 50, 50, '2026-05-22'),
(105, 'Veronika Decides to Die', 'Paulo Coelho', '9780061124266', 'Fiction', 50, 50, '2026-05-22'),
(106, 'Dom Casmurro', 'Machado de Assis', '9780199536214', 'History', 50, 50, '2026-05-22'),
(107, 'The Hour of the Star', 'Clarice Lispector', '9780811219498', 'Fiction', 50, 50, '2026-05-22'),
(108, 'Gabriela, Clove and Cinnamon', 'Jorge Amado', '9780380012053', 'Fiction', 50, 50, '2026-05-22'),
(109, 'Captains of the Sands', 'Jorge Amado', '9780143106289', 'Fiction', 50, 50, '2026-05-22'),
(110, 'Near to the Wild Heart', 'Clarice Lispector', '9780811219627', 'Fiction', 50, 50, '2026-05-22'),
(111, 'The Posthumous Memoirs of Bras Cubas', 'Machado de Assis', '9780140449808', 'History', 50, 50, '2026-05-22'),
(112, 'Seto Dharti', 'Amar Neupane', '9789937706335', 'Fiction', 50, 50, '2026-05-22'),
(113, 'Palpasa Cafe', 'Narayan Wagle', '9789993325853', 'Fiction', 50, 50, '2026-05-22'),
(114, 'Summer Love', 'Subin Bhattarai', '9789937822455', 'Fiction', 50, 50, '2026-05-22'),
(115, 'Saaya', 'Subin Bhattarai', '9789937822462', 'Fiction', 50, 50, '2026-05-22'),
(116, 'Karnali Blues', 'Buddhisagar', '9789937821205', 'Fiction', 50, 50, '2026-05-22'),
(117, 'Jiwan Kada Ki Phool', 'Jhamak Ghimire', '9789937824558', 'Biography', 50, 50, '2026-05-22'),
(118, 'Radha', 'Krishna Dharabasi', '9789937828884', 'Fiction', 50, 50, '2026-05-22'),
(119, 'Muna Madan', 'Laxmi Prasad Devkota', '9789994619029', 'Poetry', 50, 50, '2026-05-22'),
(120, 'Shirishko Phool', 'Parijat', '9789993322432', 'Fiction', 50, 50, '2026-05-22'),
(121, 'China Harayeko Manchhe', 'Hari Bansha Acharya', '9789937827788', 'Biography', 50, 50, '2026-05-22'),
(122, 'Godaan', 'Munshi Premchand', '9788126701881', 'Fiction', 50, 50, '2026-05-22'),
(123, 'Gaban', 'Munshi Premchand', '9788126713648', 'Fiction', 50, 50, '2026-05-22'),
(124, 'Raag Darbari', 'Shrilal Shukla', '9788171788668', 'Fiction', 50, 50, '2026-05-22'),
(125, 'Gunahon Ka Devta', 'Dharamvir Bharati', '9788126713617', 'Fiction', 50, 50, '2026-05-22'),
(126, 'Madhushala', 'Harivansh Rai Bachchan', '9788170281108', 'Poetry', 50, 50, '2026-05-22'),
(127, 'Nirmala', 'Munshi Premchand', '9788126701874', 'Fiction', 50, 50, '2026-05-22'),
(128, 'Chandrakanta', 'Devaki Nandan Khatri', '9788126705209', 'Fantasy', 50, 50, '2026-05-22'),
(129, 'Maila Anchal', 'Phanishwar Nath Renu', '9788171194957', 'Fiction', 50, 50, '2026-05-22'),
(130, 'Kamayani', 'Jaishankar Prasad', '9788126710456', 'Poetry', 50, 50, '2026-05-22'),
(131, 'Tyagpatra', 'Jainendra Kumar', '9788126719985', 'Fiction', 50, 50, '2026-05-22'),
(132, 'Smillas Sense of Snow', 'Peter Hoeg', '9780312421304', 'Fiction', 50, 50, '2026-05-22'),
(133, 'The Keeper of Lost Causes', 'Jussi Adler-Olsen', '9780452297906', 'Crime', 50, 50, '2026-05-22'),
(134, 'Miss Smillas Feeling for Snow', 'Peter Hoeg', '9781860468230', 'Fiction', 50, 50, '2026-05-22'),
(135, 'We the Drowned', 'Carsten Jensen', '9780099540380', 'History', 50, 50, '2026-05-22'),
(136, 'The Absent One', 'Jussi Adler-Olsen', '9780451417589', 'Crime', 50, 50, '2026-05-22'),
(137, 'The Hanging Girl', 'Jussi Adler-Olsen', '9781524742553', 'Crime', 50, 50, '2026-05-22'),
(138, 'Borderliners', 'Peter Hoeg', '9781860468018', 'Fiction', 50, 50, '2026-05-22'),
(139, 'The Plan of the Heart', 'Mette Holm', '9788702294955', 'Fiction', 50, 50, '2026-05-22'),
(140, 'Winter Brothers', 'Rasmus Bjerg', '9788740042105', 'Fiction', 50, 50, '2026-05-22'),
(141, 'The Exception', 'Christian Jungersen', '9780385522182', 'Thriller', 50, 50, '2026-05-22');

-- --------------------------------------------------------

--
-- Table structure for table `login_log`
--

CREATE TABLE `login_log` (
  `log_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL,
  `reason` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_log`
--

INSERT INTO `login_log` (`log_id`, `email`, `success`, `ip_address`, `attempt_time`, `reason`, `user_agent`) VALUES
(1, 'librarian@test.com', 1, '::1', '2026-05-20 09:35:11', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(2, 'member@test.com', 1, '::1', '2026-05-20 09:35:31', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(3, 'member@test.com', 1, '::1', '2026-05-20 09:36:03', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(4, 'member@test.com', 1, '::1', '2026-05-20 09:38:38', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(5, 'aayushapandit68@gmail.com', 1, '::1', '2026-05-21 23:36:01', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(6, 'librarian@test.com', 1, '::1', '2026-05-21 23:38:49', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(7, 'aayushapandit68@gmail.com', 1, '::1', '2026-05-22 09:16:07', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(8, 'librarian@test.com', 1, '::1', '2026-05-22 09:16:28', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(9, 'librarian@test.com', 1, '::1', '2026-05-22 09:19:20', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(10, 'librarian@test.com', 1, '::1', '2026-05-22 09:50:07', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(11, 'librarian@test.com', 1, '::1', '2026-05-22 10:43:26', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(12, 'librarian@test.com', 1, '::1', '2026-05-22 11:27:24', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(13, 'aayushapandit68@gmail.com', 1, '::1', '2026-05-22 11:50:45', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(14, 'librarian@test.com', 1, '::1', '2026-05-24 13:54:36', 'success', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `member_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `membership_date` date DEFAULT curdate(),
  `is_blocked` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`member_id`, `admin_id`, `full_name`, `email`, `phone`, `membership_date`, `is_blocked`, `created_at`) VALUES
(1, 1, 'Test Member', 'member@test.com', '555-0101', '2026-05-08', 0, '2026-05-08 09:55:05'),
(2, 2, 'Aayusha Librarian', 'librarian@test.com', '555-0102', '2026-05-08', 0, '2026-05-08 09:55:05'),
(5, 5, 'Rabin Ghimire', 'ggrabin50@gmail.com', '91726984', '2026-05-10', 0, '2026-05-10 22:25:37'),
(6, 7, 'Rabin', 'rabin67@gmail.com', '99335500', '2026-05-11', 0, '2026-05-11 10:07:38'),
(10, 11, 'Head Librarian', 'librarian@libtech.com', '555-0000', '2026-05-19', 0, '2026-05-19 10:33:47'),
(11, 12, 'System Administrator', 'admin@libtech.com', '000-0000', '2026-05-19', 0, '2026-05-19 10:34:19'),
(12, 14, 'Test Librarian', 'testlibrarian@libtech.com', '555-1234', '2026-05-19', 0, '2026-05-19 10:36:40'),
(15, 18, 'Aayusha Pandit', 'aayushapandit68@gmail.com', '71535356', '2026-05-21', 0, '2026-05-21 23:35:46');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notification_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `notification_type` varchar(20) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `sent_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'sent',
  `read_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notification_id`, `member_id`, `notification_type`, `subject`, `message`, `sent_date`, `status`, `read_status`) VALUES
(1, 1, 'borrow', 'Book Borrowed Confirmation', 'You have borrowed a book. Please return by due date.', '2026-05-08 07:55:05', '0', 0);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `transaction_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_paid` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'Borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`transaction_id`, `member_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `fine_amount`, `fine_paid`, `status`) VALUES
(1, 1, 1, '2026-05-03', '2026-05-17', NULL, 0.00, 0, 'Borrowed'),
(2, 1, 3, '2026-05-09', '2026-05-23', NULL, 0.00, 0, 'Borrowed');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`User_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `login_log`
--
ALTER TABLE `login_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_attempt_time` (`attempt_time`),
  ADD KEY `idx_success` (`success`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `book_id` (`book_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `login_log`
--
ALTER TABLE `login_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `member`
--
ALTER TABLE `member`
  ADD CONSTRAINT `member_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`User_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `book` (`book_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
