-- phpMyAdmin SQL Dump
-- LibTech Solutions – Complete Database
-- Updated by: Nabil Sarwar (SCRUM Master)
-- Includes: All original tables + Member Management module
--           + 2FA columns + login_log + proper password hashes
--
-- Host: 127.0.0.1
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
--
-- HOW TO IMPORT (for teammates):
--   1. Open phpMyAdmin → http://localhost/phpmyadmin
--   2. Click on libtech_db in the left sidebar
--   3. Click Import tab
--   4. Choose this file → click Go
--
-- Test Login Credentials:
--   member@test.com      password: 1234   role: Member
--   librarian@test.com   password: 1234   role: Librarian
--   ggrabin50@gmail.com  password: 5678   role: Member
--   rabin55@gmail.com    password: 5678   role: Librarian

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- DATABASE: libtech_db
-- ============================================================

-- Drop all tables in correct order (foreign key safe)
DROP TABLE IF EXISTS `login_log`;
DROP TABLE IF EXISTS `notification`;
DROP TABLE IF EXISTS `transaction`;
DROP TABLE IF EXISTS `member`;
DROP TABLE IF EXISTS `book`;
DROP TABLE IF EXISTS `admin`;
DROP TABLE IF EXISTS `user`;

-- ============================================================
-- TABLE: admin
-- Stores login credentials for both Members and Librarians.
-- Password_hash uses bcrypt (password_hash PHP function).
-- two_factor_enabled: 0 = disabled, 1 = enabled
-- ============================================================
CREATE TABLE `admin` (
  `User_id`             int(11)                         NOT NULL AUTO_INCREMENT,
  `Email`               varchar(100)                    NOT NULL,
  `Password_hash`       varchar(255)                    NOT NULL,
  `Role`                enum('Member','Librarian')       NOT NULL DEFAULT 'Member',
  `Last_login`          timestamp                        NULL DEFAULT NULL,
  `Created_at`          datetime                         NOT NULL DEFAULT current_timestamp(),
  `Is_active`           tinyint(1)                       NOT NULL DEFAULT 1,
  `failed_attempts`     int(11)                          DEFAULT 0,
  `locked_until`        datetime                         DEFAULT NULL,
  `two_factor_enabled`  tinyint(1)                       NOT NULL DEFAULT 0,
  `two_factor_secret`   varchar(32)                      DEFAULT NULL,
  PRIMARY KEY (`User_id`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Admin test data – all passwords are proper bcrypt hashes
-- member@test.com     → 1234
-- librarian@test.com  → 1234
-- ggrabin50@gmail.com → 5678
-- rabin55@gmail.com   → 5678
INSERT INTO `admin`
  (`User_id`, `Email`, `Password_hash`, `Role`, `Is_active`, `failed_attempts`, `two_factor_enabled`)
VALUES
  (1, 'member@test.com',     '$2y$10$PqL13OeORVneQxHqnKirxOWj0ltu1uf.EqjbtTRrSY7bsUpq/sjOq', 'Member',    1, 0, 0),
  (2, 'librarian@test.com',  '$2y$10$PqL13OeORVneQxHqnKirxOWj0ltu1uf.EqjbtTRrSY7bsUpq/sjOq', 'Librarian', 1, 0, 0),
  (3, 'ggrabin50@gmail.com', '$2y$10$etSzmeG0E.e2z1.49CF8zeTylKtlO7jl/xY8YqmdkzX5G3oUqLRza', 'Member',    1, 0, 0),
  (4, 'rabin55@gmail.com',   '$2y$10$etSzmeG0E.e2z1.49CF8zeTylKtlO7jl/xY8YqmdkzX5G3oUqLRza', 'Librarian', 1, 0, 0);


-- ============================================================
-- TABLE: book
-- Stores all library book records.
-- ============================================================
CREATE TABLE `book` (
  `book_id`          int(11)       NOT NULL AUTO_INCREMENT,
  `title`            varchar(200)  NOT NULL,
  `author`           varchar(100)  NOT NULL,
  `isbn`             varchar(13)   DEFAULT NULL,
  `genre`            varchar(50)   DEFAULT NULL,
  `total_copies`     int(11)       DEFAULT 1,
  `available_copies` int(11)       DEFAULT 1,
  `added_date`       date          DEFAULT curdate(),
  PRIMARY KEY (`book_id`),
  UNIQUE KEY `isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `book`
  (`book_id`, `title`, `author`, `isbn`, `genre`, `total_copies`, `available_copies`, `added_date`)
VALUES
  (1, 'The Great Gatsby',        'F. Scott Fitzgerald', '9780743273565', 'Fiction',   3, 2, '2026-05-05'),
  (2, '1984',                    'George Orwell',        '9780452284234', 'Dystopian', 2, 0, '2026-05-05'),
  (3, 'To Kill a Mockingbird',   'Harper Lee',           '9780061120084', 'Fiction',   2, 1, '2026-05-05');


-- ============================================================
-- TABLE: member
-- Stores all library member records (Member Management Module).
-- admin_id links to the admin table for login credentials.
-- is_blocked / member_is_blocked: both kept for compatibility.
-- books_borrowed_current: live counter of active borrows.
-- total_overdue_count: cumulative overdue history.
-- ============================================================
CREATE TABLE `member` (
  `member_id`              int(11)       NOT NULL AUTO_INCREMENT,
  `admin_id`               int(11)       DEFAULT NULL,
  `full_name`              varchar(100)  NOT NULL,
  `email`                  varchar(100)  NOT NULL,
  `phone`                  varchar(20)   NOT NULL,
  `membership_date`        date          DEFAULT curdate(),
  `is_blocked`             tinyint(1)   DEFAULT 0,
  `member_is_blocked`      tinyint(1)   NOT NULL DEFAULT 0,
  `books_borrowed_current` int(11)      NOT NULL DEFAULT 0,
  `total_overdue_count`    int(11)      NOT NULL DEFAULT 0,
  `created_at`             datetime     DEFAULT current_timestamp(),
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `admin_id` (`admin_id`),
  KEY `idx_blocked` (`member_is_blocked`),
  KEY `idx_membership_date` (`membership_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `member`
  (`member_id`, `admin_id`, `full_name`, `email`, `phone`, `membership_date`, `is_blocked`, `member_is_blocked`, `books_borrowed_current`, `total_overdue_count`)
VALUES
  (1, 1, 'John Member',    'member@test.com',    '555-0101', '2026-05-08', 0, 0, 2, 0),
  (2, 2, 'Sarah Librarian','librarian@test.com', '555-0102', '2026-05-08', 0, 0, 0, 0),
  (3, 3, 'Rabin Ghimire',  'ggrabin50@gmail.com','91726984', '2026-05-10', 0, 0, 0, 0);


-- ============================================================
-- TABLE: notification
-- Stores email/system notifications sent to members.
-- ============================================================
CREATE TABLE `notification` (
  `notification_id`   int(11)      NOT NULL AUTO_INCREMENT,
  `member_id`         int(11)      NOT NULL,
  `notification_type` varchar(20)  NOT NULL,
  `subject`           varchar(200) NOT NULL,
  `message`           text         NOT NULL,
  `sent_date`         timestamp    NOT NULL DEFAULT current_timestamp(),
  `status`            varchar(20)  DEFAULT 'sent',
  `read_status`       tinyint(1)   DEFAULT 0,
  PRIMARY KEY (`notification_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notification`
  (`notification_id`, `member_id`, `notification_type`, `subject`, `message`, `sent_date`, `status`, `read_status`)
VALUES
  (1, 1, 'borrow', 'Book Borrowed Confirmation', 'You have borrowed a book. Please return by due date.', '2026-05-08 07:55:05', 'sent', 0);


-- ============================================================
-- TABLE: transaction
-- Records every borrow and return event.
-- fine_amount: calculated at $0.50 per overdue day.
-- ============================================================
CREATE TABLE `transaction` (
  `transaction_id` int(11)        NOT NULL AUTO_INCREMENT,
  `member_id`      int(11)        NOT NULL,
  `book_id`        int(11)        NOT NULL,
  `borrow_date`    date           NOT NULL,
  `due_date`       date           NOT NULL,
  `return_date`    date           DEFAULT NULL,
  `fine_amount`    decimal(10,2)  DEFAULT 0.00,
  `fine_paid`      tinyint(1)     DEFAULT 0,
  `status`         varchar(20)    DEFAULT 'Borrowed',
  PRIMARY KEY (`transaction_id`),
  KEY `member_id` (`member_id`),
  KEY `book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transaction`
  (`transaction_id`, `member_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `fine_amount`, `fine_paid`, `status`)
VALUES
  (1, 1, 1, '2026-05-03', '2026-05-17', NULL,         0.00, 0, 'Borrowed'),
  (2, 1, 3, '2026-05-09', '2026-05-23', NULL,         0.00, 0, 'Borrowed'),
  (3, 3, 2, '2026-05-10', '2026-05-24', '2026-05-10', 0.00, 0, 'Returned');


-- ============================================================
-- TABLE: login_log
-- Tracks every login attempt for security auditing.
-- ============================================================
CREATE TABLE `login_log` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `email`        varchar(100) NOT NULL,
  `success`      tinyint(1)   DEFAULT 0,
  `ip_address`   varchar(45)  DEFAULT NULL,
  `attempt_time` datetime     DEFAULT current_timestamp(),
  `reason`       varchar(100) DEFAULT NULL,
  `user_agent`   varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- FOREIGN KEY CONSTRAINTS
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE `member`
  ADD CONSTRAINT `member_ibfk_1`
  FOREIGN KEY (`admin_id`) REFERENCES `admin` (`User_id`) ON DELETE CASCADE;

ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1`
  FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE;

ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1`
  FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_ibfk_2`
  FOREIGN KEY (`book_id`) REFERENCES `book` (`book_id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;