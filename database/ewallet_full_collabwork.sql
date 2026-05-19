-- ============================================================
-- EWallet Complete Database Schema
-- Version: 1.0
-- Generated: May 17, 2026
-- ============================================================
-- This is the complete unified schema for the EWallet application.
-- It includes all tables: users, admins, budgets, expenses, transactions,
-- chat_history, user_settings, password_reset_tokens, and notifications.
--
-- Import instructions:
--   phpMyAdmin:  paste into SQL tab and click Go
--   MySQL CLI:   mysql -u root < ewallet_complete_schema.sql
--   Terminal:    mysql -u root -p ewallet < ewallet_complete_schema.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Create database
CREATE DATABASE IF NOT EXISTS `ewallet` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ewallet`;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables (if any)
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `user_settings`;
DROP TABLE IF EXISTS `chat_history`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(30) NOT NULL,
  `email` varchar(180) NOT NULL,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Table: admins
-- ============================================================
CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Table: budgets
-- ============================================================
CREATE TABLE `budgets` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `budget_month` date NOT NULL DEFAULT date_format(curdate(),'%Y-%m-01'),
  `category` enum('Foods','Transportation','Housing','Shopping','Health and Wellness','Education','Entertainment','Others','Unbudgeted') NOT NULL,
  `monthly_limit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `used` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_category_month` (`user_id`,`category`,`budget_month`),
  CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Table: expenses
-- ============================================================
CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category` enum('Foods','Transportation','Housing','Shopping','Health and Wellness','Education','Entertainment','Others','Unbudgeted') NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_date` (`user_id`,`expense_date`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Table: transactions
-- ============================================================
CREATE TABLE `transactions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `related_user_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('load','send','receive','expense') NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Table: chat_history
-- ============================================================
CREATE TABLE `chat_history` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` varchar(32) NOT NULL,
  `message` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_chat_user_time` (`user_id`, `created_at`),
  CONSTRAINT `chat_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Table: user_settings
-- ============================================================
CREATE TABLE `user_settings` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'en-US',
  `theme` varchar(10) NOT NULL DEFAULT 'light',
  `notif_email` tinyint(1) NOT NULL DEFAULT 0,
  `notif_push` tinyint(1) NOT NULL DEFAULT 0,
  `notif_expense` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Table: password_reset_tokens
-- ============================================================
CREATE TABLE `password_reset_tokens` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(64) NOT NULL COMMENT 'SHA-256 hex of the raw token',
  `expires_at` datetime NOT NULL COMMENT 'token valid for 1 hour',
  `used` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = already consumed',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int UNSIGNED NOT NULL,
  `type` enum('money_received','money_sent','money_loaded','expense_added','budget_exceeded','budget_warning') NOT NULL,
  `title` varchar(120) NOT NULL,
  `body` varchar(255) NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  INDEX `idx_user_read` (`user_id`, `is_read`),
  INDEX `idx_user_created` (`user_id`, `created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Insert sample users
INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `balance`, `password_hash`, `created_at`) VALUES
(1, 'nima gelu sherpa', 'nima', 'nima@gmail.com', 9390.00, '$2y$10$koA0nSORfB9z497L2ixiYu/FOBrG5CbgenRW.C.IYZFKJaWUD.2jm', '2026-04-12 21:02:23'),
(2, 'Kushal Shrestha', 'KushalStha', 'kushalstha234@gmail.com', 8160.00, '$2y$10$Ht4ErWk6eQ9zjV/u8fwY9uNhM9yTOoP7lvwwKrKhQfRj5l2KJhXi2', '2026-04-30 08:14:36'),
(3, 'Sam Bahadur', 'sadsam', 'sam123@gmail.com', 5000.00, '$2y$10$k9I2MNPjZF0EL0kssd78ZeMkMUY7hdw3Jg2PyHD30y1cmshXfhQzK', '2026-04-30 08:27:59'),
(4, 'Mahesh Shahi', 'Mahesh', 'mahesh12@gmail.com', 650.00, '$2y$10$wVZznq1btJk5N7BRtLAUAOxnzXaDyzSQk6tZqBCq6YlPi8yMQhCg.', '2026-05-03 18:22:39'),
(5, 'Neymar Jr', 'Neyjr', 'neymar12@gmail.com', 2450.00, '$2y$10$gRcKTeSBAgwKXIpPqeGGjuedbhucS4Z2iXuR9LpdqVaCYO1dkD0u6', '2026-05-03 21:05:59'),
(6, 'Hari Shrestha', 'HariStha', 'hari12@gmail.com', 4620.00, '$2y$10$xgxJSgAbsUnJKWdKNQ9ztOPQqr27JZ7PBzUg9RtX0usr5fkNO1cs6', '2026-05-05 19:37:56'),
(7, 'Devi Prasad', 'DeviPrasad', 'devi12@gmail.com', 740.00, '$2y$10$RVrY1hiQ1ziH/XGyGTyXLeKUbVFgDTINQvKrMmNkfB0vENIz5sRYq', '2026-05-05 20:55:19'),
(8, 'Aarav Gautam', 'aarav_g23', 'aarav.gautam23@gmail.com', 1490.00, '$2y$10$3Wh5QdHJuJpjBBOxkPh/z.8kGoBdGs4S0LKlz8oV7U/h0qveg9QVy', '2026-05-06 14:18:03');

-- Insert sample admin
INSERT INTO `admins` (`id`, `fullname`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Super Admin', 'admin@ewallet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-12 20:43:20');

-- Insert sample budgets
INSERT INTO `budgets` (`id`, `user_id`, `budget_month`, `category`, `monthly_limit`, `used`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-05-01', 'Foods', 250.00, 500.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(3, 1, '2026-05-01', 'Transportation', 100.00, 110.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(5, 2, '2026-05-01', 'Foods', 500.00, 1120.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(6, 2, '2026-05-01', 'Transportation', 200.00, 300.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(8, 2, '2026-05-01', 'Housing', 100.00, 50.00, '2026-05-05 20:31:31', '2026-05-06 14:12:20'),
(9, 2, '2026-05-01', 'Education', 5000.00, 2500.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(10, 2, '2026-05-01', 'Shopping', 1000.00, 600.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(11, 2, '2026-05-01', 'Health and Wellness', 1000.00, 500.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(12, 2, '2026-05-01', 'Entertainment', 1000.00, 250.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(13, 4, '2026-05-01', 'Foods', 500.00, 250.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(14, 4, '2026-05-01', 'Transportation', 100.00, 50.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(15, 4, '2026-05-01', 'Education', 250.00, 50.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(16, 5, '2026-05-01', 'Foods', 500.00, 250.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(17, 5, '2026-05-01', 'Transportation', 500.00, 50.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(18, 5, '2026-05-01', 'Housing', 500.00, 500.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(20, 5, '2026-05-01', 'Shopping', 1000.00, 750.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(21, 5, '2026-05-01', 'Health and Wellness', 1500.00, 1000.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(22, 2, '2026-05-01', 'Others', 100.00, 20.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(23, 2, '2026-04-01', 'Foods', 1000.00, 120.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(24, 6, '2026-03-01', 'Foods', 1000.00, 170.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(25, 6, '2025-12-01', 'Foods', 100.00, 50.00, '2026-05-05 20:31:31', '2026-05-05 20:31:31'),
(26, 6, '2026-05-01', 'Foods', 1000.00, 0.00, '2026-05-05 20:35:07', '2026-05-05 20:35:07'),
(27, 6, '2026-04-01', 'Foods', 1000.00, 210.00, '2026-05-05 20:50:49', '2026-05-05 20:51:03'),
(28, 7, '2026-03-01', 'Foods', 1000.00, 210.00, '2026-05-05 20:55:42', '2026-05-05 20:56:02'),
(29, 7, '2026-03-01', 'Transportation', 500.00, 50.00, '2026-05-05 20:56:43', '2026-05-05 20:57:36'),
(30, 7, '2026-03-01', 'Housing', 500.00, 0.00, '2026-05-05 20:56:47', '2026-05-05 20:56:47'),
(31, 2, '2026-04-01', 'Transportation', 1000.00, 100.00, '2026-05-06 13:56:26', '2026-05-06 13:56:42'),
(32, 8, '2026-05-01', 'Foods', 1000.00, 260.00, '2026-05-06 14:18:26', '2026-05-06 15:23:31'),
(33, 8, '2026-04-01', 'Foods', 500.00, 150.00, '2026-05-06 14:21:59', '2026-05-06 14:22:20');

-- Insert sample expenses
INSERT INTO `expenses` (`id`, `user_id`, `category`, `amount`, `note`, `expense_date`, `created_at`) VALUES
(2, 1, 'Foods', 500.00, '', '2026-04-12', '2026-04-12 21:05:30'),
(3, 1, 'Transportation', 110.00, 'bus', '2026-04-15', '2026-04-23 07:37:02'),
(4, 2, 'Foods', 1000.00, 'mom', '2026-04-30', '2026-04-30 08:17:13'),
(5, 2, 'Transportation', 300.00, 'going to college', '2026-04-23', '2026-04-30 08:20:52'),
(6, 2, 'Education', 2500.00, 'semester', '2026-04-22', '2026-04-30 08:24:43'),
(7, 2, 'Shopping', 500.00, 'dress', '2026-05-01', '2026-05-01 23:15:30'),
(8, 2, 'Shopping', 100.00, 'shoes', '2026-04-08', '2026-05-01 23:17:45'),
(10, 2, 'Health and Wellness', 500.00, 'vacination injection', '2026-05-02', '2026-05-02 22:12:44'),
(11, 2, 'Entertainment', 250.00, 'gaming fc26', '2026-05-01', '2026-05-02 22:51:15'),
(12, 4, 'Foods', 250.00, 'momo', '2026-05-03', '2026-05-03 18:24:28'),
(13, 4, 'Transportation', 50.00, 'going to college', '2026-05-03', '2026-05-03 18:24:56'),
(14, 4, 'Education', 50.00, 'books', '2026-04-02', '2026-05-03 18:26:03'),
(15, 5, 'Foods', 250.00, 'momo', '2026-05-03', '2026-05-03 21:12:21'),
(16, 5, 'Transportation', 50.00, 'going to college', '2026-05-10', '2026-05-03 21:13:08'),
(17, 5, 'Housing', 500.00, 'maintaining', '2026-05-17', '2026-05-03 21:14:10'),
(18, 5, 'Shopping', 750.00, 'Dior Dress', '2026-05-24', '2026-05-03 21:14:48'),
(19, 5, 'Health and Wellness', 1000.00, 'vaccination', '2026-04-30', '2026-05-03 21:15:50'),
(20, 2, 'Others', 20.00, 'room decoration', '2026-05-04', '2026-05-04 21:08:48'),
(21, 2, 'Foods', 120.00, 'channa', '2026-04-15', '2026-05-05 19:35:35'),
(22, 6, 'Foods', 120.00, 'channa', '2026-04-06', '2026-05-05 19:38:48'),
(23, 6, 'Foods', 50.00, 'chilli momo', '2025-12-05', '2026-05-05 19:42:10'),
(24, 6, 'Foods', 210.00, 'chicken thali', '2026-04-06', '2026-05-05 20:51:03'),
(25, 7, 'Foods', 210.00, 'chicken thali', '2026-03-17', '2026-05-05 20:56:02'),
(26, 7, 'Transportation', 50.00, 'going to college', '2026-03-05', '2026-05-05 20:57:36'),
(27, 7, 'Foods', 1000.00, 'chilli momo', '2026-05-05', '2026-05-05 20:57:54'),
(28, 2, 'Transportation', 100.00, 'going patan', '2026-04-15', '2026-05-06 13:56:42'),
(29, 2, 'Housing', 50.00, 'maintaining', '2026-05-06', '2026-05-06 14:12:20'),
(30, 8, 'Foods', 210.00, 'chicken thali', '2026-05-06', '2026-05-06 14:18:57'),
(31, 8, 'Foods', 50.00, 'Pani puri', '2026-05-06', '2026-05-06 14:19:26'),
(33, 8, 'Foods', 150.00, 'buff momo', '2026-04-13', '2026-05-06 14:22:20');

-- Insert sample transactions
INSERT INTO `transactions` (`id`, `user_id`, `related_user_id`, `type`, `amount`, `description`, `created_at`) VALUES
(1, 1, NULL, 'load', 5000.00, 'Loaded money via Visa debit card', '2026-04-12 21:02:58'),
(2, 1, NULL, 'expense', 500.00, 'Expense: Foods', '2026-04-12 21:03:11'),
(3, 1, NULL, 'expense', 500.00, 'Expense: Foods', '2026-04-12 21:05:30'),
(4, 1, NULL, 'load', 5000.00, 'Loaded money via Visa debit card', '2026-04-23 07:33:20'),
(5, 1, NULL, 'expense', 110.00, 'Expense: Transportation - bus', '2026-04-23 07:37:02'),
(6, 2, NULL, 'load', 1000.00, 'Loaded money via Visa debit card', '2026-04-30 08:16:43'),
(7, 2, NULL, 'expense', 1000.00, 'Expense: Foods - mom', '2026-04-30 08:17:13'),
(8, 2, NULL, 'load', 1000.00, 'Loaded money via Visa debit card', '2026-04-30 08:20:18'),
(9, 2, NULL, 'expense', 300.00, 'Expense: Transportation - going to college', '2026-04-30 08:20:52'),
(10, 2, NULL, 'load', 10000.00, 'Loaded money via Bank', '2026-04-30 08:24:22'),
(11, 2, NULL, 'expense', 2500.00, 'Expense: Education - semester', '2026-04-30 08:24:43'),
(12, 3, NULL, 'load', 5000.00, 'Loaded money via Visa debit card', '2026-04-30 08:28:16'),
(13, 2, NULL, 'expense', 500.00, 'Expense: Shopping - dress', '2026-05-01 23:15:30'),
(14, 2, NULL, 'expense', 100.00, 'Expense: Shopping - shoes', '2026-05-01 23:17:45'),
(15, 2, NULL, 'expense', 500.00, 'Expense: Health and Wellness - vacination', '2026-05-02 22:12:10'),
(16, 2, NULL, 'expense', 500.00, 'Expense: Health and Wellness - vacination', '2026-05-02 22:12:44'),
(17, 2, NULL, 'expense', 250.00, 'Expense: Entertainment - gaming', '2026-05-02 22:51:15'),
(18, 4, NULL, 'load', 1000.00, 'Loaded money via Visa debit card', '2026-05-03 18:23:50'),
(19, 4, NULL, 'expense', 250.00, 'Expense: Foods - momo', '2026-05-03 18:24:28'),
(20, 4, NULL, 'expense', 50.00, 'Expense: Transportation - going to college', '2026-05-03 18:24:56'),
(21, 4, NULL, 'expense', 50.00, 'Expense: Education - books', '2026-05-03 18:26:03'),
(22, 5, NULL, 'load', 1000.00, 'Loaded money via Bank', '2026-05-03 21:11:59'),
(23, 5, NULL, 'expense', 250.00, 'Expense: Foods - momo', '2026-05-03 21:12:21'),
(24, 5, NULL, 'load', 5000.00, 'Loaded money via Bank', '2026-05-03 21:12:38'),
(25, 5, NULL, 'expense', 50.00, 'Expense: Transportation - going to college', '2026-05-03 21:13:08'),
(26, 5, NULL, 'expense', 500.00, 'Expense: Housing - maintaining', '2026-05-03 21:14:10'),
(27, 5, NULL, 'expense', 750.00, 'Expense: Shopping - Dior Dress', '2026-05-03 21:14:48'),
(28, 5, NULL, 'expense', 1000.00, 'Expense: Health and Wellness - vaccination', '2026-05-03 21:15:50'),
(29, 5, 2, 'send', 1000.00, 'Sent money to @KushalStha', '2026-05-03 22:09:27'),
(30, 2, 5, 'receive', 1000.00, 'Received money from @Neyjr', '2026-05-03 22:09:27'),
(31, 2, NULL, 'load', 500.00, 'Loaded money via Bank', '2026-05-04 09:01:15'),
(32, 2, NULL, 'expense', 20.00, 'Expense: Others - room decoration', '2026-05-04 21:08:48'),
(33, 2, NULL, 'expense', 120.00, 'Expense: Foods - channa', '2026-05-05 19:35:35'),
(34, 6, NULL, 'load', 5000.00, 'Loaded money via Visa debit card', '2026-05-05 19:38:06'),
(35, 6, NULL, 'expense', 120.00, 'Expense: Foods - channa', '2026-05-05 19:38:48'),
(36, 6, NULL, 'expense', 50.00, 'Expense: Foods - chilli momo', '2026-05-05 19:42:10'),
(37, 6, NULL, 'expense', 210.00, 'Expense: Foods - chicken thali', '2026-05-05 20:51:03'),
(38, 7, NULL, 'load', 2000.00, 'Loaded money via Visa debit card', '2026-05-05 20:55:25'),
(39, 7, NULL, 'expense', 210.00, 'Expense: Foods - chicken thali', '2026-05-05 20:56:02'),
(40, 7, NULL, 'expense', 50.00, 'Expense: Transportation - going to college', '2026-05-05 20:57:36'),
(41, 7, NULL, 'expense', 1000.00, 'Expense: Foods - chilli momo', '2026-05-05 20:57:54'),
(42, 2, NULL, 'expense', 100.00, 'Expense: Transportation - going patan', '2026-05-06 13:56:42'),
(43, 2, NULL, 'expense', 50.00, 'Expense: Housing - maintaining', '2026-05-06 14:12:20'),
(44, 8, NULL, 'load', 1000.00, 'Loaded money via Visa debit card', '2026-05-06 14:18:44'),
(45, 8, NULL, 'expense', 210.00, 'Expense: Foods - chicken thali', '2026-05-06 14:18:57'),
(46, 8, NULL, 'expense', 50.00, 'Expense: Foods - Pani puri', '2026-05-06 14:19:26'),
(47, 8, NULL, 'expense', 150.00, 'Expense: Foods - hamburger', '2026-05-06 14:19:50'),
(48, 8, NULL, 'load', 1000.00, 'Loaded money via Bank', '2026-05-06 14:21:51'),
(49, 8, NULL, 'expense', 150.00, 'Expense: Foods - buff momo', '2026-05-06 14:22:20'),
(50, 8, 2, 'send', 100.00, 'Sent money to @KushalStha', '2026-05-06 14:47:17'),
(51, 2, 8, 'receive', 100.00, 'Received money from @aarav_g23', '2026-05-06 14:47:17');

-- Insert sample chat history
INSERT INTO `chat_history` (`id`, `user_id`, `role`, `message`, `created_at`) VALUES
(27, 4, 'user', 'What did I spend this month?', '2026-05-03 18:25:18'),
(28, 4, 'assistant', ' You spent NRP 250.00 on foods and NRP 50.00 on transportation this month. Your total expenses for May 2026 are NRP 300.00.', '2026-05-03 18:25:23'),
(29, 4, 'user', 'Did i spend money on april?', '2026-05-03 18:26:27'),
(30, 4, 'assistant', 'Yes, you spent NRP 50.00 on education in April 2026.', '2026-05-03 18:26:30'),
(31, 5, 'user', 'What did i spend most in?', '2026-05-03 21:17:16'),
(32, 5, 'assistant', ' You spent the most on Shopping in May 2026, with a total expense of NRP 750.00.', '2026-05-03 21:17:20'),
(33, 5, 'user', 'Where did i spend least', '2026-05-03 21:17:38'),
(34, 5, 'assistant', ' You spent the least on Transportation in May 2026, with a total expense of NRP 50.00.', '2026-05-03 21:17:41'),
(35, 5, 'user', 'Did i spend on Health facility?', '2026-05-03 21:18:41'),
(36, 5, 'assistant', ' Yes, you spent NRP 1,000.00 on Health and Wellness in April 2026.', '2026-05-03 21:18:45'),
(37, 5, 'user', 'can you provide me the exact date?', '2026-05-03 21:18:59'),
(38, 5, 'assistant', 'Sure! You spent NRP 1,000.00 on Health and Wellness on May 3, 2026 at 9:15 PM. That\'s the closest matching transaction based on your recent transaction history.', '2026-05-03 21:19:03'),
(43, 2, 'user', 'What did i spend on April?', '2026-05-05 19:37:03'),
(44, 2, 'assistant', ' You spent NRP 2,500.00 on Education in April 2026. For other categories, please check the all-time expenses or the monthly breakdown provided.', '2026-05-05 19:37:07'),
(53, 6, 'user', 'what did i spend on april give me a repot', '2026-05-05 20:51:47'),
(54, 6, 'assistant', ' You spent NRP 330.00 on foods in April 2026.', '2026-05-05 20:51:50'),
(55, 2, 'user', 'what did i spend on April?', '2026-05-06 13:57:14'),
(56, 2, 'assistant', ' You spent NRP 2,500.00 on Education in April 2026. For other categories, please check the all-time expenses or the monthly breakdown provided.', '2026-05-06 13:57:18'),
(57, 2, 'user', 'What category did i spend on April?', '2026-05-06 14:04:03'),
(58, 2, 'assistant', 'Sorry, no response received. Please try again.', '2026-05-06 14:04:04'),
(59, 2, 'user', 'What category did i spend on April?', '2026-05-06 14:04:52'),
(60, 2, 'assistant', ' You spent on the Education category in April 2026. The amounts for other categories can be found in the all-time expenses or the monthly breakdown provided.', '2026-05-06 14:04:57'),
(61, 2, 'user', 'Can you provide me the report of spending in May?', '2026-05-06 14:05:52'),
(62, 2, 'assistant', 'In May 2026, you spent NRP 1,270.00 total across Shopping (NRP 500.00), Health and Wellness (NRP 500.00), Entertainment (NRP 250.00), and Others (NRP 20.00). Your budget shows you\'re over limit in Foods by NRP 620.00 and Transportation by NRP 100.00.', '2026-05-06 14:05:56'),
(63, 2, 'user', 'What is my current wallet balance?', '2026-05-06 14:11:26'),
(64, 2, 'assistant', ' Your current wallet balance is NRP 8,110.00.', '2026-05-06 14:11:29'),
(65, 2, 'user', 'How much did I spend in May 2026?', '2026-05-06 14:11:48'),
(66, 2, 'assistant', ' You spent NRP 0.00 in May 2026 as per the given data.', '2026-05-06 14:11:51'),
(67, 2, 'user', 'How much did I spend in May 2026?', '2026-05-06 14:12:27'),
(68, 2, 'assistant', ' You did not spend any amount in May 2026 as per the given data.', '2026-05-06 14:12:31'),
(69, 2, 'user', 'How much did I spend in May 2026?', '2026-05-06 14:14:50'),
(70, 2, 'assistant', ' Based on the provided data, you did not spend any amount in May 2026.', '2026-05-06 14:14:53'),
(71, 8, 'user', 'What did i spend today?', '2026-05-06 14:20:14'),
(72, 8, 'assistant', ' Today, you spent a total of NRP 770.00 on expenses (NPR 410.00 on Foods) and added a load of NRP 1,000.00 to your wallet. Therefore, your current wallet balance is NRP 1,770.00 (NPR 590.00 + NRP 1,180.00). Your Foods budget for May 2026 is NRP 1,000.00 with a used amount of NRP 410.00 and a remaining amount of NRP 590.00. Since the used amount is less than the limit, your Foods budget is on track.', '2026-05-06 14:20:27'),
(73, 8, 'user', 'Can your provide me a report of my spending?', '2026-05-06 14:20:58'),
(74, 8, 'assistant', ' Yes, your total spending for May 2026 is NRP 410.00 on Foods, making your current wallet balance NRP 1,770.00 (NP 590.00 + NRP 1,180.00). Your Foods budget for May 2026 has a limit of NRP 1,000.00, a used amount of NRP 410.00, and a remaining amount of NRP 590.00, which is considered on track. Your recent transactions include expenses of NRP 150.00, NRP 50.00, and NRP 210.00 for a total of NRP 410.00 on May 6, 2026, as well as a load of NRP 1,000.00 on the same day.', '2026-05-06 14:21:13'),
(75, 8, 'user', 'What did i spend on April?', '2026-05-06 14:22:42'),
(76, 8, 'assistant', ' You spent a total of NRP 150.00 on expenses for Foods in April 2026.', '2026-05-06 14:22:45'),
(77, 8, 'user', 'can you also provide what note i have written?', '2026-05-06 14:23:22'),
(78, 8, 'assistant', ' I cannot provide information about notes as there is no note data available in the provided information.', '2026-05-06 14:23:25');

-- Insert sample user settings
INSERT INTO `user_settings` (`user_id`, `language`, `theme`, `notif_email`, `notif_push`, `notif_expense`) VALUES
(1, 'en-US', 'light', 0, 0, 0),
(2, 'en-US', 'dark', 0, 0, 0),
(8, 'en-US', 'dark', 0, 0, 0);

-- ============================================================
-- FINAL COMMIT
-- ============================================================
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================
-- MERGE COMPLETE
-- All tables: users, admins, budgets, expenses, transactions,
-- chat_history, user_settings, password_reset_tokens, and
-- notifications are now integrated into one complete schema.
-- ============================================================