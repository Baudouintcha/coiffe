-- ════════════════════════════════════════════════════════════════════
-- Migration 002: Create email_actions table for logging all email activities
-- Purpose: Track all email sends, delivery status, bounces, failures
-- Created: August 12, 2026
-- ════════════════════════════════════════════════════════════════════

-- Drop table if exists (for migration safety)
DROP TABLE IF EXISTS `email_actions`;

-- Create email_actions table
CREATE TABLE `email_actions` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `email_address` VARCHAR(255) NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `purpose` VARCHAR(50) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body_preview` TEXT NULL,
    `status` ENUM('pending', 'sent', 'delivered', 'bounced', 'failed', 'opened', 'clicked') DEFAULT 'pending',
    `error_message` TEXT NULL,
    `retry_count` TINYINT DEFAULT 0,
    `max_retries` TINYINT DEFAULT 3,
    `sent_at` DATETIME NULL,
    `delivered_at` DATETIME NULL,
    `opened_at` DATETIME NULL,
    `clicked_at` DATETIME NULL,
    `bounced_at` DATETIME NULL,
    `failed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `metadata` JSON NULL,
    
    -- Indexes for fast querying
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_email_address` (`email_address`),
    INDEX `idx_action_type` (`action_type`),
    INDEX `idx_purpose` (`purpose`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_sent_at` (`sent_at`),
    UNIQUE INDEX `idx_unique_otp_send` (`user_id`, `purpose`, `created_at`),
    
    -- Foreign key to users table
    CONSTRAINT `fk_email_actions_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════════════
-- Table structure explanation:
-- ════════════════════════════════════════════════════════════════════
--
-- id:               Unique identifier for each email action
-- user_id:          Link to users table (NULL for system emails)
-- email_address:    Recipient email address
-- action_type:      Type of email (OTP, notification, reminder, alert, etc.)
-- purpose:          OTP purpose (registration, password_reset, email_change, etc.)
-- subject:          Email subject line
-- body_preview:     First 500 chars of email body for debugging
-- status:           Current status of email delivery
-- error_message:    Error details if delivery failed
-- retry_count:      Number of retry attempts made
-- max_retries:      Maximum retry attempts allowed
-- sent_at:          When email was actually sent
-- delivered_at:     When email was confirmed delivered (by provider)
-- opened_at:        When recipient opened email (tracking pixel)
-- clicked_at:       When recipient clicked link in email
-- bounced_at:       When email bounced (hard/soft bounce)
-- failed_at:        When email delivery permanently failed
-- created_at:       When email action was created
-- updated_at:       When email action was last updated
-- metadata:         JSON field for additional tracking data
--
-- ════════════════════════════════════════════════════════════════════
-- Status meanings:
-- ════════════════════════════════════════════════════════════════════
-- pending:          Queued but not yet sent
-- sent:             Successfully sent to email provider
-- delivered:        Confirmed received by email provider
-- bounced:          Email address rejected (invalid, full inbox, etc.)
-- failed:           Permanent failure (too many retries, provider error)
-- opened:           Email opened by recipient (if tracking enabled)
-- clicked:          Link in email was clicked (if tracking enabled)
--
-- ════════════════════════════════════════════════════════════════════
-- Action types:
-- ════════════════════════════════════════════════════════════════════
-- otp_code:         OTP verification codes
-- password_reset:   Password reset requests
-- email_change:     Email change verification
-- account_deletion: Account deletion confirmation
-- notification:     General notifications
-- reminder:         Appointment/event reminders
-- alert:            System alerts
-- promotion:        Promotional emails
-- confirmation:     Booking/order confirmations
-- report:           Periodic reports
--
-- ════════════════════════════════════════════════════════════════════
