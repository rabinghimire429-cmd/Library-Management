-- ============================================================
-- MEMBER MANAGEMENT MODULE – DATABASE SCHEMA
-- LibTech Solutions | Developer: Nabil Sarwar
-- ============================================================
-- Run this SQL in phpMyAdmin or via MySQL CLI to create
-- the MEMBER table as specified in the System Specification.
--
-- Prerequisites: the libtech_db database must already exist.
-- Run this AFTER the main database.sql has been executed.
-- ============================================================

USE libtech_db;

-- ──────────────────────────────────────────────────────────────
-- TABLE: member
-- Stores all library member records.
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `member` (
    -- Primary key – auto-incremented unique ID for every member
    `member_id`              INT(11)      NOT NULL AUTO_INCREMENT,

    -- Member's full legal name – required
    `full_name`              VARCHAR(100) NOT NULL,

    -- Email address – used for login and notifications; must be unique
    `email`                  VARCHAR(100) NOT NULL UNIQUE,

    -- Contact phone number
    `phone`                  VARCHAR(15)  NOT NULL,

    -- Date the membership was created; defaults to today
    `membership_date`        DATE         NOT NULL DEFAULT (CURRENT_DATE),

    -- How many books the member currently has borrowed (live counter)
    `books_borrowed_current` INT(11)      NOT NULL DEFAULT 0,

    -- Cumulative count of times this member has had overdue books
    `total_overdue_count`    INT(11)      NOT NULL DEFAULT 0,

    -- Flag: TRUE (1) = member is blocked from borrowing; FALSE (0) = active
    `member_is_blocked`      TINYINT(1)   NOT NULL DEFAULT 0,

    -- Timestamp recorded when the row was first inserted
    `created_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`member_id`),

    -- Index on email for fast login look-ups
    INDEX `idx_email` (`email`),

    -- Index on blocked status for filtered queries
    INDEX `idx_blocked` (`member_is_blocked`),

    -- Index on membership date for date-range filters
    INDEX `idx_membership_date` (`membership_date`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ──────────────────────────────────────────────────────────────
-- SAMPLE DATA – a few test members
-- Passwords are hashed via PHP password_hash() / bcrypt.
-- Plain text for testing: password = "1234"
-- ──────────────────────────────────────────────────────────────
INSERT INTO `member`
    (`full_name`, `email`, `phone`, `membership_date`, `books_borrowed_current`, `total_overdue_count`, `member_is_blocked`)
VALUES
    ('Jane Doe',      'member@test.com',   '+45 1111 2222', '2024-01-15', 2, 0, 0),
    ('John Smith',    'john@test.com',     '+45 3333 4444', '2024-03-10', 0, 1, 0),
    ('Alice Johnson', 'alice@test.com',    '+45 5555 6666', '2024-06-01', 1, 2, 0),
    ('Bob Blocked',   'blocked@test.com',  '+45 7777 8888', '2023-11-20', 0, 5, 1);


-- ──────────────────────────────────────────────────────────────
-- USER TABLE (if not already created by auth module)
-- The user table stores login credentials for both members and
-- librarians. member_id is NULL for librarian accounts.
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user` (
    `user_id`       INT(11)      NOT NULL AUTO_INCREMENT,
    `full_name`     VARCHAR(100) NOT NULL,
    `email`         VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,

    -- Role controls dashboard access: 'Member' or 'Librarian'
    `role`          ENUM('Member','Librarian') NOT NULL DEFAULT 'Member',

    -- Links to member record (NULL for librarians)
    `member_id`     INT(11)      NULL,

    -- Soft-disable flag; blocked users cannot log in
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,

    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`user_id`),
    INDEX `idx_email` (`email`),
    FOREIGN KEY (`member_id`) REFERENCES `member`(`member_id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
