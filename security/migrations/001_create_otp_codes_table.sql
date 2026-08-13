-- ============================================================
-- Migration: Créer la table otp_codes
-- Gère tous les codes OTP du système (centralisé)
-- Supporté: registration, password_reset, email_change, 
--           account_deletion, domizi_action, custom purposes
-- ============================================================

CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id`          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED      NOT NULL,
    `purpose`     VARCHAR(50)       NOT NULL,
    `code_hash`   VARCHAR(255)      NOT NULL,
    `expires_at`  DATETIME          NOT NULL,
    `attempts`    TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `used_at`     DATETIME          NULL DEFAULT NULL,
    `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_active_otp` (`user_id`, `purpose`),
    KEY `idx_user_id`  (`user_id`),
    KEY `idx_expires`  (`expires_at`),
    KEY `idx_purpose`  (`purpose`),

    CONSTRAINT `otp_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Index pour nettoyage optimisé des anciens OTP (cron job futur)
CREATE INDEX idx_cleanup ON `otp_codes`(`expires_at`, `used_at`);

-- Ajouter des colonnes de vérification au tableau users si elles n'existent pas
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME NULL DEFAULT NULL AFTER `email_verifie`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `otp_verified_at` DATETIME NULL DEFAULT NULL AFTER `email_verified_at`;
