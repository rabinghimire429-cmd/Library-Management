

USE libtech_db;


DROP TABLE IF EXISTS `user`;

UPDATE `admin` SET `Password_hash` = '$2y$10$vneTB9P4SLfBzO.iToIXe.Eu4DzzubeV1slxuoU/27kLCpjL9MOuu'
WHERE `Email` = 'member@test.com';

UPDATE `admin` SET `Password_hash` = '$2y$10$vneTB9P4SLfBzO.iToIXe.Eu4DzzubeV1slxuoU/27kLCpjL9MOuu'
WHERE `Email` = 'librarian@test.com';

UPDATE `admin` SET `Password_hash` = '$2y$10$vneTB9P4SLfBzO.iToIXe.Eu4DzzubeV1slxuoU/27kLCpjL9MOuu'
WHERE `Email` = 'ghimirerabin50@gmail.com';

UPDATE `admin` SET `Password_hash` = '$2y$10$ug8fhQY2T/33hKwE1VnPNecbIEZ2wKRl7Rw5q54hAAF9uGbmgiyL2'
WHERE `Email` = 'ggrabin50@gmail.com';

UPDATE `admin` SET `Password_hash` = '$2y$10$ug8fhQY2T/33hKwE1VnPNecbIEZ2wKRl7Rw5q54hAAF9uGbmgiyL2'
WHERE `Email` = 'rabin55@gmail.com';

-- Also unlock the locked account (rabinghimire429)
UPDATE `admin` SET `failed_attempts` = 0, `locked_until` = NULL
WHERE `Email` = 'rabinghimire429@gmail.com';


-- ============================================================
-- STEP 3: FIX MEMBER TABLE
-- The existing member table is missing the columns needed by
-- the Member Management module. We add them safely with
-- ALTER TABLE so existing data is NOT lost.
-- ============================================================

-- Add books_borrowed_current if it doesn't exist
ALTER TABLE `member`
    ADD COLUMN IF NOT EXISTS `books_borrowed_current` INT(11) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `total_overdue_count`    INT(11) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `member_is_blocked`      TINYINT(1) NOT NULL DEFAULT 0;

-- Sync member_is_blocked with existing is_blocked column
UPDATE `member` SET `member_is_blocked` = `is_blocked`;

-- ============================================================
-- STEP 4: ADD missing member rows for test accounts that
--         exist in admin but not in member table
--         (rabin55@gmail.com = Librarian, skip member row)
-- ============================================================

-- Insert member row for rabinghimire429 if not already linked
INSERT IGNORE INTO `member` (`admin_id`, `full_name`, `email`, `phone`, `membership_date`, `is_blocked`, `member_is_blocked`)
SELECT `User_id`, 'Rabin Ghimire', `Email`, '91726984', CURDATE(), 0, 0
FROM `admin`
WHERE `Email` = 'rabinghimire429@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM `member` WHERE `email` = 'rabinghimire429@gmail.com');


