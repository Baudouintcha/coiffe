-- ============================================================
-- DOMIZI - Base de données v2.0
-- Plateforme de services à domicile - Bénin & Sous-région
-- Multi-métiers, sous-région ready, propre et évolutive
-- Encodage : utf8mb4_general_ci
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- 1. ZONES GÉOGRAPHIQUES
-- Hiérarchie : pays → departements → villes → quartiers
-- Les départements servent à définir les zones d'intervention
-- des prestataires de manière logique et large
-- ============================================================

CREATE TABLE `pays` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom_pays`  VARCHAR(100) NOT NULL,
    `code_pays` VARCHAR(5)   NOT NULL,
    `actif`     TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code_pays` (`code_pays`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Départements : niveau intermédiaire entre pays et villes
-- Permet au prestataire de définir une zone large d'intervention
CREATE TABLE `departements` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_pays`   INT UNSIGNED NOT NULL,
    `nom`       VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `id_pays` (`id_pays`),
    CONSTRAINT `dep_ibfk_pays` FOREIGN KEY (`id_pays`)
        REFERENCES `pays` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `villes` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_pays`           INT UNSIGNED NOT NULL,
    `id_departement`    INT UNSIGNED NOT NULL,
    `nom_ville`         VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `id_pays`           (`id_pays`),
    KEY `id_departement`    (`id_departement`),
    CONSTRAINT `villes_ibfk_pays` FOREIGN KEY (`id_pays`)
        REFERENCES `pays` (`id`) ON DELETE CASCADE,
    CONSTRAINT `villes_ibfk_dep` FOREIGN KEY (`id_departement`)
        REFERENCES `departements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `quartiers` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_ville`      INT UNSIGNED NOT NULL,
    `nom_quartier`  VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `id_ville` (`id_ville`),
    CONSTRAINT `quartiers_ibfk_1` FOREIGN KEY (`id_ville`)
        REFERENCES `villes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 2. MÉTIERS & SPÉCIALITÉS
-- ============================================================

CREATE TABLE `domaines` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom`       VARCHAR(100) NOT NULL,
    `slug`      VARCHAR(100) NOT NULL,
    `icone`     VARCHAR(50)  DEFAULT NULL,
    `couleur`   VARCHAR(10)  DEFAULT NULL,
    `actif`     TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `metiers` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_domaine`    INT UNSIGNED NOT NULL,
    `nom`           VARCHAR(100) NOT NULL,
    `slug`          VARCHAR(100) NOT NULL,
    `description`   TEXT         DEFAULT NULL,
    `icone`         VARCHAR(50)  DEFAULT NULL,
    `actif`         TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `id_domaine` (`id_domaine`),
    CONSTRAINT `metiers_ibfk_1` FOREIGN KEY (`id_domaine`)
        REFERENCES `domaines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. UTILISATEURS
-- ============================================================

CREATE TABLE `users` (
    `id`                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nom`                   VARCHAR(100)  NOT NULL,
    `prenom`                VARCHAR(100)  NOT NULL,
    `sexe`                  ENUM('homme','femme') NOT NULL DEFAULT 'homme',
    `email`                 VARCHAR(150)  NOT NULL,
    `telephone`             VARCHAR(25)   NOT NULL,
    `password`              VARCHAR(255)  NOT NULL,
    `role`                  ENUM('client','prestataire','admin') NOT NULL DEFAULT 'client',
    `photo_profil`          VARCHAR(255)  DEFAULT NULL,
    `bio`                   TEXT          DEFAULT NULL,

    -- Localisation
    `id_pays`               INT UNSIGNED  DEFAULT NULL,
    `id_ville`              INT UNSIGNED  DEFAULT NULL,
    `id_quartier`           INT UNSIGNED  DEFAULT NULL,

    -- Statut du compte
    `statut`                ENUM('actif','inactif','banni') NOT NULL DEFAULT 'inactif',
    `is_approved`           TINYINT(1)    NOT NULL DEFAULT 0,
    `raison_ban`            VARCHAR(255)  DEFAULT NULL,

    -- Vérifications
    `email_verifie`         TINYINT(1)    NOT NULL DEFAULT 0,
    `telephone_verifie`     TINYINT(1)    NOT NULL DEFAULT 0,
    `code_verification`     VARCHAR(10)   DEFAULT NULL,
    `token_reset`           VARCHAR(100)  DEFAULT NULL,
    `token_expire_at`       DATETIME      DEFAULT NULL,

    -- Portefeuille intégré
    `solde`                 DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `solde_gele`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    `created_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `id_pays`     (`id_pays`),
    KEY `id_ville`    (`id_ville`),
    KEY `id_quartier` (`id_quartier`),

    CONSTRAINT `users_ibfk_pays`      FOREIGN KEY (`id_pays`)
        REFERENCES `pays` (`id`) ON DELETE SET NULL,
    CONSTRAINT `users_ibfk_ville`     FOREIGN KEY (`id_ville`)
        REFERENCES `villes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `users_ibfk_quartier`  FOREIGN KEY (`id_quartier`)
        REFERENCES `quartiers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 4. PROFIL PRESTATAIRE
-- (séparé de users pour ne pas alourdir la table principale)
-- ============================================================

CREATE TABLE `profils_prestataires` (
    `id`                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`               INT UNSIGNED  NOT NULL,
    `id_metier`             INT UNSIGNED  NOT NULL,

    -- Abonnement
    `abonnement_status`     ENUM('actif','inactif','expire') NOT NULL DEFAULT 'inactif',
    `date_debut_abo`        DATE          DEFAULT NULL,
    `date_expiration_abo`   DATE          DEFAULT NULL,

    -- Validation admin
    `diplome`               VARCHAR(255)  DEFAULT NULL,
    `note_admin`            TEXT          DEFAULT NULL,
    `date_validation`       DATETIME      DEFAULT NULL,
    `valide_par`            INT UNSIGNED  DEFAULT NULL,

    -- Tarification
    `tarif_base`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    -- GPS position permanente du prestataire (domicile / point de départ)
    `gps_lat`               DECIMAL(10,8) DEFAULT NULL,
    `gps_lng`               DECIMAL(11,8) DEFAULT NULL,

    -- Zone d'intervention GPS : rayon en km autour de sa position
    -- Ex: 5 = "j'interviens dans un rayon de 5 km"
    -- Complète la sélection manuelle de quartiers
    `rayon_intervention`    INT UNSIGNED  NOT NULL DEFAULT 10,

    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    KEY `id_metier`  (`id_metier`),
    KEY `valide_par` (`valide_par`),

    CONSTRAINT `pp_ibfk_user`      FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `pp_ibfk_metier`    FOREIGN KEY (`id_metier`)
        REFERENCES `metiers` (`id`),
    CONSTRAINT `pp_ibfk_admin`     FOREIGN KEY (`valide_par`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Zone d'intervention manuelle par quartiers (complément du rayon GPS)
-- Le prestataire peut combiner les deux approches :
-- 1. Rayon GPS automatique (profils_prestataires.rayon_intervention)
-- 2. Sélection manuelle de quartiers spécifiques (cette table)
CREATE TABLE `zones_prestataire` (
    `id_prestataire`    INT UNSIGNED NOT NULL,
    `id_quartier`       INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_prestataire`, `id_quartier`),
    CONSTRAINT `zp_ibfk_prestataire` FOREIGN KEY (`id_prestataire`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `zp_ibfk_quartier`    FOREIGN KEY (`id_quartier`)
        REFERENCES `quartiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 5. CATALOGUE DE SERVICES
-- ============================================================

CREATE TABLE `services` (
    `id_service`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `id_prestataire`    INT UNSIGNED    NOT NULL,
    `id_metier`         INT UNSIGNED    NOT NULL,
    `nom_service`       VARCHAR(150)    NOT NULL,
    `description`       TEXT            DEFAULT NULL,
    `options_json`      TEXT            DEFAULT NULL,
    `prix`              DECIMAL(10,2)   NOT NULL,
    `duree`             INT             NOT NULL DEFAULT 60,
    `photo`             VARCHAR(255)    DEFAULT NULL,
    `actif`             TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_service`),
    KEY `id_prestataire` (`id_prestataire`),
    KEY `id_metier`      (`id_metier`),

    CONSTRAINT `services_ibfk_presta` FOREIGN KEY (`id_prestataire`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `services_ibfk_metier` FOREIGN KEY (`id_metier`)
        REFERENCES `metiers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 6. DISPONIBILITÉS
-- ============================================================

CREATE TABLE `disponibilites` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `prestataire_id`    INT UNSIGNED    NOT NULL,
    `jour_semaine`      ENUM('Lundi','Mardi','Mercredi','Jeudi',
                             'Vendredi','Samedi','Dimanche') NOT NULL,
    `heure_debut`       TIME            NOT NULL,
    `heure_fin`         TIME            NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_dispo` (`prestataire_id`, `jour_semaine`),

    CONSTRAINT `dispo_ibfk_presta` FOREIGN KEY (`prestataire_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 7. RENDEZ-VOUS
-- ============================================================

CREATE TABLE `rendez_vous` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `client_id`             INT UNSIGNED    NOT NULL,
    `prestataire_id`        INT UNSIGNED    NOT NULL,
    `service_id`            INT UNSIGNED    NOT NULL,
    `options_choisies`      TEXT            DEFAULT NULL,

    -- Planification
    `date_rdv`              DATE            NOT NULL,
    `heure_debut`           TIME            NOT NULL,
    `heure_fin`             TIME            NOT NULL,

    -- Position du client (éphémère — propre à CE rendez-vous uniquement)
    -- N'est PAS sauvegardée dans le profil client
    -- Le client la renseigne au moment de la réservation
    -- comme sur WhatsApp : "Partager ma position actuelle"
    `client_gps_lat`        DECIMAL(10,8)   DEFAULT NULL,
    `client_gps_lng`        DECIMAL(11,8)   DEFAULT NULL,
    `client_adresse_texte`  VARCHAR(255)    DEFAULT NULL,
    `adresse_prestation`    TEXT            DEFAULT NULL,

    -- Financier
    `montant_total`         DECIMAL(10,2)   NOT NULL,
    `montant_gele`          DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `commission_site`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,

    -- Statuts
    `statut_rdv`            ENUM('en_attente','confirme',
                                 'annule','termine') NOT NULL DEFAULT 'en_attente',
    `statut_paiement`       ENUM('non_paye','gele','paye',
                                 'rembourse') NOT NULL DEFAULT 'non_paye',

    -- Annulation
    `annule_par`            ENUM('client','prestataire','admin') DEFAULT NULL,
    `motif_annulation`      TEXT            DEFAULT NULL,
    `date_annulation`       DATETIME        DEFAULT NULL,

    -- Notifications
    -- whatsapp_envoye gardé pour compatibilité future
    -- Le système utilise en priorité les Push Notifications navigateur
    `whatsapp_envoye`       TINYINT(1)      NOT NULL DEFAULT 0,
    `push_envoye`           TINYINT(1)      NOT NULL DEFAULT 0,

    `date_demande`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `client_id`      (`client_id`),
    KEY `prestataire_id` (`prestataire_id`),
    KEY `service_id`     (`service_id`),

    CONSTRAINT `rdv_ibfk_client`  FOREIGN KEY (`client_id`)
        REFERENCES `users` (`id`),
    CONSTRAINT `rdv_ibfk_presta`  FOREIGN KEY (`prestataire_id`)
        REFERENCES `users` (`id`),
    CONSTRAINT `rdv_ibfk_service` FOREIGN KEY (`service_id`)
        REFERENCES `services` (`id_service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 8. AVIS & PLAINTES (fusionnés)
-- ============================================================

CREATE TABLE `avis` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `rdv_id`            INT UNSIGNED    NOT NULL,
    `client_id`         INT UNSIGNED    NOT NULL,
    `prestataire_id`    INT UNSIGNED    NOT NULL,
    `note`              TINYINT UNSIGNED NOT NULL,
    `commentaire`       TEXT            DEFAULT NULL,
    `type`              ENUM('public','plainte') NOT NULL DEFAULT 'public',

    -- Modération admin
    `statut_admin`      ENUM('en_attente','traite') NOT NULL DEFAULT 'en_attente',
    `reponse_admin`     TEXT            DEFAULT NULL,

    `date_creation`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_avis_rdv` (`rdv_id`),
    KEY `client_id`      (`client_id`),
    KEY `prestataire_id` (`prestataire_id`),

    CONSTRAINT `avis_ibfk_rdv`    FOREIGN KEY (`rdv_id`)
        REFERENCES `rendez_vous` (`id`) ON DELETE CASCADE,
    CONSTRAINT `avis_ibfk_client` FOREIGN KEY (`client_id`)
        REFERENCES `users` (`id`),
    CONSTRAINT `avis_ibfk_presta` FOREIGN KEY (`prestataire_id`)
        REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 9. FINANCES
-- ============================================================

CREATE TABLE `transactions` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `rdv_id`        INT UNSIGNED    DEFAULT NULL,
    `type`          ENUM('depot','retrait','blocage','deblocage',
                         'gain','commission','remboursement') NOT NULL,
    `montant`       DECIMAL(10,2)   NOT NULL,
    `solde_avant`   DECIMAL(10,2)   NOT NULL,
    `solde_apres`   DECIMAL(10,2)   NOT NULL,
    `motif`         VARCHAR(255)    NOT NULL,
    `reference`     VARCHAR(100)    DEFAULT NULL,
    `date_creation` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `rdv_id`  (`rdv_id`),

    CONSTRAINT `trans_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`),
    CONSTRAINT `trans_ibfk_rdv`  FOREIGN KEY (`rdv_id`)
        REFERENCES `rendez_vous` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `paiements_kkiapay` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED    NOT NULL,
    `transaction_id`    VARCHAR(150)    NOT NULL,
    `montant`           DECIMAL(10,2)   NOT NULL,
    `type`              ENUM('abonnement','depot_portefeuille') NOT NULL,
    `statut`            ENUM('en_attente','valide','echoue') NOT NULL DEFAULT 'en_attente',
    `webhook_data`      TEXT            DEFAULT NULL,
    `date_creation`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `transaction_id` (`transaction_id`),
    KEY `user_id` (`user_id`),

    CONSTRAINT `kkiapay_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 10. NOTIFICATIONS
-- ============================================================

CREATE TABLE `notifications` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `rdv_id`        INT UNSIGNED    DEFAULT NULL,
    `type`          VARCHAR(50)     NOT NULL DEFAULT 'info',
    `titre`         VARCHAR(150)    DEFAULT NULL,
    `message`       TEXT            NOT NULL,
    `lu`            TINYINT(1)      NOT NULL DEFAULT 0,
    `date_creation` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `rdv_id`  (`rdv_id`),

    CONSTRAINT `notif_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `notif_ibfk_rdv`  FOREIGN KEY (`rdv_id`)
        REFERENCES `rendez_vous` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 11. SESSIONS IA (Chatbot)
-- ============================================================

CREATE TABLE `ia_sessions` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    DEFAULT NULL,
    `session_token` VARCHAR(255)    NOT NULL,
    `contexte_json` TEXT            DEFAULT NULL,
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `session_token` (`session_token`),
    KEY `user_id` (`user_id`),

    CONSTRAINT `ia_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 12. SUPPRESSIONS DE COMPTES (traçabilité admin)
-- ============================================================

CREATE TABLE `suppressions_comptes` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `id_user_supprime`  INT             NOT NULL,
    `nom`               VARCHAR(100)    NOT NULL,
    `prenom`            VARCHAR(100)    NOT NULL,
    `email`             VARCHAR(150)    NOT NULL,
    `role`              VARCHAR(50)     NOT NULL,
    `motif`             TEXT            DEFAULT NULL,
    `supprime_par`      INT UNSIGNED    DEFAULT NULL,
    `date_demande`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `supprime_par` (`supprime_par`),

    CONSTRAINT `supp_ibfk_admin` FOREIGN KEY (`supprime_par`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 13. FAVORIS
-- Un client peut sauvegarder ses prestataires préférés
-- pour les retrouver rapidement et les recontacter
-- ============================================================

CREATE TABLE `favoris` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `client_id`         INT UNSIGNED    NOT NULL,
    `prestataire_id`    INT UNSIGNED    NOT NULL,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    -- Un client ne peut mettre le même prestataire en favori qu'une fois
    UNIQUE KEY `unique_favori` (`client_id`, `prestataire_id`),
    KEY `client_id`      (`client_id`),
    KEY `prestataire_id` (`prestataire_id`),

    CONSTRAINT `fav_ibfk_client` FOREIGN KEY (`client_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fav_ibfk_presta` FOREIGN KEY (`prestataire_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 14. PUSH NOTIFICATIONS (Service Workers navigateur)
-- Remplace WhatsApp pour les notifications temps réel
-- Fonctionne sur Android (navigateur fermé inclus)
-- Le navigateur de l'utilisateur s'abonne une fois
-- puis reçoit les notifications automatiquement
-- ============================================================

CREATE TABLE `push_subscriptions` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    -- endpoint : URL unique générée par le navigateur
    `endpoint`      TEXT            NOT NULL,
    -- clés de chiffrement pour sécuriser les messages
    `p256dh_key`    TEXT            NOT NULL,
    `auth_key`      VARCHAR(255)    NOT NULL,
    -- device_type : pour savoir quel appareil notifier
    `device_type`   VARCHAR(50)     DEFAULT 'web',
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),

    CONSTRAINT `push_ibfk_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 15. DONNÉES DE BASE (Seed)
-- ============================================================

-- -----------------------------------------------
-- PAYS (sous-région Afrique de l'Ouest)
-- -----------------------------------------------
INSERT INTO `pays` (`nom_pays`, `code_pays`, `actif`) VALUES
('Bénin',          'BJ', 1),
('Togo',           'TG', 1),
('Côte d\'Ivoire', 'CI', 1),
('Sénégal',        'SN', 1),
('Burkina Faso',   'BF', 1);

-- -----------------------------------------------
-- DÉPARTEMENTS DU BÉNIN (id_pays = 1)
-- 12 départements officiels
-- -----------------------------------------------
INSERT INTO `departements` (`id_pays`, `nom`) VALUES
(1, 'Alibori'),
(1, 'Atacora'),
(1, 'Atlantique'),
(1, 'Borgou'),
(1, 'Collines'),
(1, 'Couffo'),
(1, 'Donga'),
(1, 'Littoral'),
(1, 'Mono'),
(1, 'Ouémé'),
(1, 'Plateau'),
(1, 'Zou');

-- -----------------------------------------------
-- VILLES DU BÉNIN
-- Format : (id_pays, id_departement, nom_ville)
-- Alibori=1, Atacora=2, Atlantique=3, Borgou=4
-- Collines=5, Couffo=6, Donga=7, Littoral=8
-- Mono=9, Ouémé=10, Plateau=11, Zou=12
-- -----------------------------------------------
INSERT INTO `villes` (`id_pays`, `id_departement`, `nom_ville`) VALUES
-- Alibori (dep=1)
(1, 1, 'Banikoara'),
(1, 1, 'Gogounou'),
(1, 1, 'Kandi'),
(1, 1, 'Karimama'),
(1, 1, 'Malanville'),
(1, 1, 'Ségbana'),
-- Atacora (dep=2)
(1, 2, 'Boukoumbé'),
(1, 2, 'Cobly'),
(1, 2, 'Kérou'),
(1, 2, 'Kouandé'),
(1, 2, 'Matéri'),
(1, 2, 'Natitingou'),
(1, 2, 'Pehunco'),
(1, 2, 'Tanguiéta'),
(1, 2, 'Toucountouna'),
-- Atlantique (dep=3)
(1, 3, 'Abomey-Calavi'),
(1, 3, 'Allada'),
(1, 3, 'Kpomassè'),
(1, 3, 'Ouidah'),
(1, 3, 'Toffo'),
(1, 3, 'Tori-Bossito'),
(1, 3, 'Zè'),
-- Borgou (dep=4)
(1, 4, 'Bembèrèkè'),
(1, 4, 'Kalalé'),
(1, 4, 'N\'Dali'),
(1, 4, 'Nikki'),
(1, 4, 'Parakou'),
(1, 4, 'Pèrèrè'),
(1, 4, 'Sinendé'),
(1, 4, 'Tchaourou'),
-- Collines (dep=5)
(1, 5, 'Bantè'),
(1, 5, 'Dassa-Zoumè'),
(1, 5, 'Glazoué'),
(1, 5, 'Ouèssè'),
(1, 5, 'Savalou'),
(1, 5, 'Savè'),
-- Couffo (dep=6)
(1, 6, 'Aplahoué'),
(1, 6, 'Djakotomè'),
(1, 6, 'Dogbo'),
(1, 6, 'Klouékanmè'),
(1, 6, 'Lalo'),
(1, 6, 'Toviklin'),
-- Donga (dep=7)
(1, 7, 'Bassila'),
(1, 7, 'Copargo'),
(1, 7, 'Djougou'),
(1, 7, 'Ouaké'),
-- Littoral (dep=8)
(1, 8, 'Cotonou'),
-- Mono (dep=9)
(1, 9, 'Athiémé'),
(1, 9, 'Bopa'),
(1, 9, 'Comè'),
(1, 9, 'Grand-Popo'),
(1, 9, 'Houéyogbé'),
(1, 9, 'Lokossa'),
-- Ouémé (dep=10)
(1, 10, 'Adjarra'),
(1, 10, 'Akpro-Missérété'),
(1, 10, 'Avrankou'),
(1, 10, 'Bonou'),
(1, 10, 'Dangbo'),
(1, 10, 'Porto-Novo'),
(1, 10, 'Sèmè-Kpodji'),
-- Plateau (dep=11)
(1, 11, 'Adja-Ouèrè'),
(1, 11, 'Ifangni'),
(1, 11, 'Kétou'),
(1, 11, 'Pobè'),
(1, 11, 'Sakété'),
-- Zou (dep=12)
(1, 12, 'Abomey'),
(1, 12, 'Agbangnizoun'),
(1, 12, 'Bohicon'),
(1, 12, 'Covè'),
(1, 12, 'Djidja'),
(1, 12, 'Ouinhi'),
(1, 12, 'Za-Kpota'),
(1, 12, 'Zogbodomey');

-- -----------------------------------------------
-- QUARTIERS DE COTONOU
-- Cotonou = id_ville 43 (Littoral, seule ville)
-- -----------------------------------------------
INSERT INTO `quartiers` (`id_ville`, `nom_quartier`) VALUES
(43, 'Agla'),
(43, 'Aibatin'),
(43, 'Akpakpa'),
(43, 'Akpakpa Dodomè'),
(43, 'Avotrou'),
(43, 'Ayélawadjè'),
(43, 'Cadjehoun'),
(43, 'Dantokpa'),
(43, 'Donatin'),
(43, 'Fifadji'),
(43, 'Fidjrossè'),
(43, 'Ganhi'),
(43, 'Gbèdjromèdji'),
(43, 'Godomey-Carrefour'),
(43, 'Guedehounou'),
(43, 'Haie Vive'),
(43, 'Houéyiho'),
(43, 'Jéricho'),
(43, 'Kpankpan'),
(43, 'Ladji'),
(43, 'Mènontin'),
(43, 'Midombo'),
(43, 'Missèbo'),
(43, 'Oganla'),
(43, 'Placodji'),
(43, 'Quartier des Ambassades'),
(43, 'Sainte-Rita'),
(43, 'Sikècodji'),
(43, 'Togbin'),
(43, 'Vèdoko'),
(43, 'Wologuèdè'),
(43, 'Xwlacodji'),
(43, 'Zogbo'),
(43, 'Zongo');

-- -----------------------------------------------
-- QUARTIERS DE PORTO-NOVO (id_ville = 49)
-- -----------------------------------------------
INSERT INTO `quartiers` (`id_ville`, `nom_quartier`) VALUES
(49, 'Agboville'),
(49, 'Agoudahoué'),
(49, 'Ahito'),
(49, 'Catchi'),
(49, 'Centre-ville'),
(49, 'Djassin'),
(49, 'Gankpétin'),
(49, 'Houinmè'),
(49, 'Hounsori'),
(49, 'Kpankou'),
(49, 'Louho'),
(49, 'Massanvi'),
(49, 'Ouando'),
(49, 'Tokpota'),
(49, 'Vodjè');

-- -----------------------------------------------
-- QUARTIERS D'ABOMEY-CALAVI (id_ville = 15)
-- -----------------------------------------------
INSERT INTO `quartiers` (`id_ville`, `nom_quartier`) VALUES
(15, 'Akassato'),
(15, 'Glo-Djigbé'),
(15, 'Godomey'),
(15, 'Kpanroun'),
(15, 'Ouèdo'),
(15, 'Togba'),
(15, 'Vêkky'),
(15, 'Zinvié'),
(15, 'Zogbadjè');

-- -----------------------------------------------
-- QUARTIERS DE PARAKOU (id_ville = 28)
-- -----------------------------------------------
INSERT INTO `quartiers` (`id_ville`, `nom_quartier`) VALUES
(28, 'Albarika'),
(28, 'Banikanni'),
(28, 'Banzara'),
(28, 'Bodèkpèrou'),
(28, 'Douroubè'),
(28, 'Kpébié'),
(28, 'Madécali'),
(28, 'Madina'),
(28, 'Nassarou Gah'),
(28, 'Pèpériyarou'),
(28, 'Titirou'),
(28, 'Tourou'),
(28, 'Zongo');

-- -----------------------------------------------
-- QUARTIERS DE BOHICON (id_ville = 59)
-- -----------------------------------------------
INSERT INTO `quartiers` (`id_ville`, `nom_quartier`) VALUES
(59, 'Avogbanna'),
(59, 'Centre'),
(59, 'Gnidjazoun'),
(59, 'Kpankou'),
(59, 'Lissèzoun'),
(59, 'Passagon'),
(59, 'Sokponta'),
(59, 'Zounzonmè');

-- -----------------------------------------------
-- QUARTIERS D'OUIDAH (id_ville = 18)
-- -----------------------------------------------
INSERT INTO `quartiers` (`id_ville`, `nom_quartier`) VALUES
(18, 'Ahouannonzoun'),
(18, 'Avlèkètè'),
(18, 'Centre-ville'),
(18, 'Djègbadji'),
(18, 'Hèvè'),
(18, 'Houakpè-Daho'),
(18, 'Pahou'),
(18, 'Savi');

-- -----------------------------------------------
-- QUARTIERS DE NATITINGOU (id_ville = 12)
-- -----------------------------------------------
INSERT INTO `quartiers` (`id_ville`, `nom_quartier`) VALUES
(12, 'Birni'),
(12, 'Bouyérou'),
(12, 'Centre'),
(12, 'Guessou-Sud'),
(12, 'Kossèwonoumon'),
(12, 'Perma');

-- -----------------------------------------------
-- QUARTIERS DE SÈME-KPODJI (id_ville = 53)
-- -----------------------------------------------
INSERT INTO `quartiers` (`id_ville`, `nom_quartier`) VALUES
(53, 'Agblangandan'),
(53, 'Aholouyèmè'),
(53, 'Ekpè'),
(53, 'Houakpè'),
(53, 'Kpodji'),
(53, 'Togoudo');

-- -----------------------------------------------
-- DOMAINES
-- -----------------------------------------------
INSERT INTO `domaines` (`nom`, `slug`, `icone`, `couleur`, `actif`) VALUES
('Beauté & Bien-être', 'beaute', 'bi-stars',       '#D4AF37', 1),
('Maison & Services',  'maison', 'bi-house-gear',  '#4A90D9', 0),
('Santé & Soins',      'sante',  'bi-heart-pulse', '#E84B4B', 0);

-- -----------------------------------------------
-- MÉTIERS DU DOMAINE BEAUTÉ (id_domaine = 1)
-- -----------------------------------------------
INSERT INTO `metiers` (`id_domaine`, `nom`, `slug`, `description`, `icone`, `actif`) VALUES
(1, 'Coiffure',   'coiffure',   'Coiffure homme et femme à domicile',       'bi-scissors',       1),
(1, 'Maquillage', 'maquillage', 'Maquillage professionnel à domicile',      'bi-palette',        1),
(1, 'Ongles',     'ongles',     'Manucure et pédicure à domicile',          'bi-gem',            1),
(1, 'Massage',    'massage',    'Massage bien-être et relaxant à domicile', 'bi-person-arms-up', 1),
(1, 'Soins',      'soins',      'Soins du visage et du corps',              'bi-droplet',        1);

COMMIT;
