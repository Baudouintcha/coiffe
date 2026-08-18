-- ============================================================
-- TABLES MANQUANTES À AJOUTER À DOMIZI
-- (Nécessaires pour la stabilité du projet)
-- ============================================================

-- 1. PRESTATIONS (Services du coiffeur - alias "services")
-- Cette table existe déjà dans domizi_v2.sql sous le nom "services"
-- Pas besoin de la recréer

-- 2. ABONNEMENTS PAIEMENTS (Suivi des paiements d'abonnement)
CREATE TABLE IF NOT EXISTS `abonnements_paiements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `prestataire_id` INT UNSIGNED NOT NULL,
    `date_paiement` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `montant` DECIMAL(10,2) NOT NULL,
    `expire_le` DATE DEFAULT NULL,
    `statut` ENUM('valide','expire') DEFAULT 'valide',
    PRIMARY KEY (`id`),
    KEY `prestataire_id` (`prestataire_id`),
    CONSTRAINT `abo_ibfk_presta` FOREIGN KEY (`prestataire_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. TRANSACTIONS PORTEFEUILLE (Mouvements d'argent utilisateur)
CREATE TABLE IF NOT EXISTS `transactions_portefeuille` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `type_transaction` ENUM('depot','retrait','blocage','deblocage','gain','commission') NOT NULL,
    `montant` DECIMAL(10,2) NOT NULL,
    `motif` VARCHAR(255) NOT NULL,
    `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `trans_port_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. COMMENTAIRES/PLAINTES UNIFIÉS 
-- (Remplace la table "plaintes" - avis gère déjà les commentaires)
CREATE TABLE IF NOT EXISTS `plaintes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rdv_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `prestataire_id` INT UNSIGNED NOT NULL,
    `motif` TEXT NOT NULL,
    `statut` ENUM('active','resolue','en_attente') DEFAULT 'en_attente',
    `reponse_admin` TEXT DEFAULT NULL,
    `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `rdv_id` (`rdv_id`),
    KEY `client_id` (`client_id`),
    KEY `prestataire_id` (`prestataire_id`),
    CONSTRAINT `plainte_ibfk_rdv` FOREIGN KEY (`rdv_id`)
        REFERENCES `rendez_vous` (`id`) ON DELETE CASCADE,
    CONSTRAINT `plainte_ibfk_client` FOREIGN KEY (`client_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `plainte_ibfk_presta` FOREIGN KEY (`prestataire_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. EMAIL ACTIONS (Trace des emails envoyés)
CREATE TABLE IF NOT EXISTS `email_actions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `email_address` VARCHAR(255) NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `purpose` VARCHAR(50) DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('pending','sent','delivered','bounced','failed') DEFAULT 'pending',
    `sent_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `action_type` (`action_type`),
    CONSTRAINT `email_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. OTP CODES (Codes de vérification par SMS/Email)
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `purpose` VARCHAR(50) NOT NULL,
    `code_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `tentatives` TINYINT UNSIGNED DEFAULT 0,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_active_otp` (`user_id`,`purpose`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `otp_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. ZONES COIFFEUR (Quartiers où intervient le prestataire)
-- Cette table existe déjà sous le nom "zones_prestataire" dans domizi_v2.sql
-- Pas besoin de la recréer

-- TABLES À IGNORER (Inutiles pour domizi) :
-- - boutique (e-commerce non utilisé)
-- - catalogue (remplacé par "services")
-- - transactions_site (pas de gestion de commission multi-niveaux)
-- - portefeuilles (solde intégré dans "users")

-- ============================================================
-- FIN DES TABLES MANQUANTES
-- ============================================================
