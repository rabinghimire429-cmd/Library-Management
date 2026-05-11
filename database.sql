-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 09:53 AM
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
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`User_id`, `Email`, `Password_hash`, `Role`, `Last_login`, `Created_at`, `Is_active`, `failed_attempts`, `locked_until`) VALUES
(1, 'member@test.com', '1234', 'Member', '2026-05-10 20:13:15', '2026-05-08 09:55:05', 1, 0, NULL),
(2, 'librarian@test.com', '1234', 'Librarian', '2026-05-10 21:39:45', '2026-05-08 09:55:05', 1, 0, NULL),
(3, 'rabinghimire429@gmail.com', '$2y$10$0g0v1tKleGLKvfRNJAbW5O.DUjkV3SOzhSxZRtU.U58S/AwKJ/BtO', 'Member', NULL, '2026-05-10 22:13:56', 1, 5, '2026-05-10 23:17:23'),
(4, 'ghimirerabin50@gmail.com', 'Earthquake@321', 'Member', '2026-05-10 21:10:34', '2026-05-10 22:19:08', 1, 0, NULL),
(5, 'ggrabin50@gmail.com', '5678', 'Member', '2026-05-10 20:25:49', '2026-05-10 22:25:37', 1, 0, NULL),
(6, 'rabin55@gmail.com', '5678', 'Librarian', '2026-05-10 20:33:59', '2026-05-10 22:33:38', 1, 0, NULL);

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
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 'Fiction', 3, 2, '2026-05-05'),
(2, '1984', 'George Orwell', '9780452284234', 'Dystopian', 2, 0, '2026-05-05'),
(3, 'To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'Fiction', 2, 1, '2026-05-05');

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
(1, 1, 'John Member', 'member@test.com', '555-0101', '2026-05-08', 0, '2026-05-08 09:55:05'),
(2, 2, 'Sarah Librarian', 'librarian@test.com', '555-0102', '2026-05-08', 0, '2026-05-08 09:55:05'),
(3, 3, 'Rabin Ghimire', 'rabinghimire429@gmail.com', '91726984', '2026-05-10', 0, '2026-05-10 22:13:56'),
(4, 4, 'Rabin ', 'ghimirerabin50@gmail.com', '91726984', '2026-05-10', 0, '2026-05-10 22:19:08'),
(5, 5, 'Rabin Ghimire', 'ggrabin50@gmail.com', '91726984', '2026-05-10', 0, '2026-05-10 22:25:37');

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
(1, 1, 'borrow', 'Book Borrowed Confirmation', 'You have borrowed a book. Please return by due date.', '2026-05-08 07:55:05', 'sent', 0),
(2, 4, 'borrow', '📖 Book Borrowed Confirmation - LibTech Solutions', 'Dear ghimirerabin50@gmail.com,\n\nYou have successfully borrowed \"1984\" by George Orwell.\n\n📅 Borrow Date: 2026-05-10\n⏰ Due Date: 2026-05-24\n\nPlease return the book by the due date to avoid fines of $0.50 per day.\n\nThank you for using LibTech Solutions!\n\nBest regards,\nLibTech Team', '2026-05-10 21:30:14', 'sent', 0);

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
(2, 1, 3, '2026-05-09', '2026-05-23', NULL, 0.00, 0, 'Borrowed'),
(3, 4, 2, '2026-05-10', '2026-05-24', '2026-05-10', 0.00, 0, 'Returned'),
(4, 4, 2, '2026-05-10', '2026-05-24', NULL, 0.00, 0, 'Borrowed'),
(5, 4, 2, '2026-05-10', '2026-05-24', NULL, 0.00, 0, 'Borrowed');

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
-- Indexes for table `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

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
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
