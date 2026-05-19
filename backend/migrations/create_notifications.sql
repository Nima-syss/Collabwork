-- Notifications table
-- Run once: SOURCE backend/migrations/create_notifications.sql;

CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    type        ENUM('money_received','money_sent','money_loaded','expense_added','budget_exceeded','budget_warning') NOT NULL,
    title       VARCHAR(120) NOT NULL,
    body        VARCHAR(255) NOT NULL,
    amount      DECIMAL(12,2) DEFAULT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
