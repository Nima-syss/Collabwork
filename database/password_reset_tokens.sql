-- ============================================================
-- Password Reset Tokens Table
-- Add this to your MySQL database (ewallet)
-- Run once: mysql -u root ewallet < password_reset_tokens.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id`         INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`    INT(10) UNSIGNED    NOT NULL,
    `token_hash` VARCHAR(64)         NOT NULL,          -- SHA-256 hex of the raw token
    `expires_at` DATETIME            NOT NULL,          -- token valid for 1 hour
    `used`       TINYINT(1)          NOT NULL DEFAULT 0,-- 1 = already consumed
    `created_at` DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token_hash` (`token_hash`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_prt_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
