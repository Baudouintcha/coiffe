-- Désactivation des vérifications pour une installation propre
SET FOREIGN_KEY_CHECKS = 0;

-- 1. UTILISATEURS (Clients, Coiffeurs, Admins)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255),
    telephone VARCHAR(20),
    role ENUM('client', 'coiffeur', 'admin') DEFAULT 'client',
    ville VARCHAR(100),
    quartier VARCHAR(100),
    gps_lat DECIMAL(10, 8), -- Pour la Map
    gps_lng DECIMAL(11, 8), -- Pour la Map
    bio TEXT,
    abonnement_status ENUM('actif', 'inactif') DEFAULT 'inactif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. CATALOGUE (Les coiffures de chaque coiffeur)
CREATE TABLE IF NOT EXISTS catalogue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coiffeur_id INT UNSIGNED,
    nom_coiffure VARCHAR(100),
    description TEXT,
    prix INT,
    temps_estime INT,
    image_url VARCHAR(255),
    note_moyenne DECIMAL(3, 2) DEFAULT 0.00,
    FOREIGN KEY (coiffeur_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. DISPONIBILITÉS (Heures de travail des coiffeurs)
CREATE TABLE IF NOT EXISTS disponibilites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coiffeur_id INT UNSIGNED,
    jour_semaine ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'),
    heure_debut TIME,
    heure_fin TIME,
    FOREIGN KEY (coiffeur_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. RENDEZ-VOUS (Avec Mobile Money & Localisation)
CREATE TABLE IF NOT EXISTS rendez_vous (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED,
    coiffeur_id INT UNSIGNED,
    coiffure_id INT UNSIGNED,
    date_rdv DATE,
    heure_rdv TIME,
    statut_paiement ENUM('en_attente', 'paye', 'echoue') DEFAULT 'en_attente',
    transaction_id VARCHAR(100),
    statut_rdv ENUM('confirme', 'annule', 'termine') DEFAULT 'confirme',
    adresse_prestation TEXT, -- Adresse saisie ou détectée par GPS
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (coiffeur_id) REFERENCES users(id),
    FOREIGN KEY (coiffure_id) REFERENCES catalogue(id)
) ENGINE=InnoDB;

-- 5. BOUTIQUE (Peignes, serviettes, etc.)
CREATE TABLE IF NOT EXISTS boutique (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom_produit VARCHAR(100),
    prix INT,
    stock INT,
    image_url VARCHAR(255)
) ENGINE=InnoDB;

-- 6. AVIS (Notation sur 10)
CREATE TABLE IF NOT EXISTS avis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rdv_id INT UNSIGNED,
    client_id INT UNSIGNED,
    note TINYINT UNSIGNED,
    commentaire TEXT,
    FOREIGN KEY (rdv_id) REFERENCES rendez_vous(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- 7. ABONNEMENTS COIFFEURS (Nouveau : pour ton business model)
CREATE TABLE IF NOT EXISTS abonnements_paiements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coiffeur_id INT UNSIGNED,
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    montant INT,
    expire_le DATE,
    statut ENUM('valide', 'expire') DEFAULT 'valide',
    FOREIGN KEY (coiffeur_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. LOGS IA (Nouveau : pour que l'IA se souvienne de la discussion)
CREATE TABLE IF NOT EXISTS ia_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL, -- NULL si visiteur non connecté
    session_id VARCHAR(255), -- Pour identifier le visiteur anonyme
    dernier_message TEXT,
    contexte_json TEXT, -- Stocke les préférences (style choisi, ville...)
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- Ajout des colonnes pour la gestion des teintes et colorations
ALTER TABLE catalogue 
ADD COLUMN prix_teinte_supp INT DEFAULT 0 AFTER prix,
ADD COLUMN temps_teinte_supp INT DEFAULT 0 AFTER temps_estime;


-- Table des villes du Bénin (répertoire de base)
CREATE TABLE villes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_ville VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion des principales villes du Bénin
INSERT INTO villes (nom_ville) VALUES 
('Cotonou'), ('Abomey-Calavi'), ('Porto-Novo'), ('Parakou'), 
('Djougou'), ('Bohicon'), ('Abomey'), ('Natitingou'), 
('Kandi'), ('Lokossa'), ('Ouidah'), ('Allada');


CREATE TABLE prestations (
    id_prestation INT AUTO_INCREMENT PRIMARY KEY,
    id_coiffeur INT NOT NULL,
    nom_style VARCHAR(100) NOT NULL,
    prix DECIMAL(10, 2) NOT NULL,
    photo_style VARCHAR(255),
    description TEXT,
    FOREIGN KEY (id_coiffeur) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS coiffeur_disponibilites (
    id_dispo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_coiffeur INT UNSIGNED NOT NULL,
    jour_semaine VARCHAR(20) NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    FOREIGN KEY (id_coiffeur) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS rendezvous (
    id_rdv INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_client INT UNSIGNED NOT NULL,
    id_coiffeur INT UNSIGNED NOT NULL,
    id_prestation INT UNSIGNED NOT NULL,
    date_rdv DATE NOT NULL,
    heure_rdv TIME NOT NULL,
    lieu VARCHAR(255) NOT NULL,
    statut VARCHAR(50) DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_coiffeur) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions_site (
    id_transaction INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_coiffeur INT UNSIGNED NOT NULL,
    montant_commission DECIMAL(10, 2) NOT NULL,
    montant_abonnement DECIMAL(10, 2) NOT NULL,
    type_transaction VARCHAR(50) DEFAULT 'commission_et_abonnement',
    date_transaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_coiffeur) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;