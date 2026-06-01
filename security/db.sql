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



///// clé api google gemini AIzaSyDMsHNIu_0QGlT7i7t1L4_zJb9qCMnNkLg




-- =========================================================================
-- 5. AJOUT DE LA CLÉ ÉTRANGÈRE SUR LA TABLE USERS
-- =========================================================================
-- Cela force l'application à n'accepter que des quartiers qui existent vraiment dans notre liste
ALTER TABLE `users` ADD CONSTRAINT `fk_users_quartier` 
FOREIGN KEY (`id_quartier`) REFERENCES `quartiers`(`id`) ON DELETE SET NULL;

ALTER TABLE rendez_vous CHANGE heure_rdv heure_debut TIME NOT NULL;
ALTER TABLE rendez_vous ADD heure_fin TIME NOT NULL AFTER heure_debut;

-- Vider proprement les tables pour éviter les conflits avant l'insertion complète
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE quartiers;
TRUNCATE TABLE villes;
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- 1. INSERTION DE TOUTES LES VILLES (COMMUNES) DU BÉNIN
-- ==========================================================
INSERT INTO villes (nom_ville) VALUES 
-- Atlantique & Littoral
('Cotonou'), ('Abomey-Calavi'), ('Allada'), ('Ouidah'), ('Kpomasse'), ('Toffo'), ('Tori-Bossito'), ('Zè'), ('Sô-Ava'),
-- Ouémé & Plateau
('Porto-Novo'), ('Adjarra'), ('Ajohoun'), ('Avrankou'), ('Bonou'), ('Dangbo'), ('Sèmè-Podji'), ('Akpro-Missérété'), ('Pobè'), ('Adja-Ouère'), ('Ifangni'), ('Kétou'), ('Sakété'),
-- Zou & Collines
('Abomey'), ('Bohicon'), ('Dassa-Zoumé'), ('Glazoué'), ('Savè'), ('Savalou'), ('Bantè'), ('Ouèssè'), ('Agbangnizoun'), ('Djidja'), ('Covè'), ('Zagnanado'), ('Za-Kpota'), ('Zogbodomey'), ('Ouinhi'),
-- Mono & Couffo
('Lokossa'), ('Athiémé'), ('Bopa'), ('Comè'), ('Grand-Popo'), ('Houéyogbé'), ('Aplahoué'), ('Djakotomey'), ('Dogbo'), ('Klouékanmè'), ('Lalo'), ('Toviklin'),
-- Borgou & Alibori
('Parakou'), ('Bembéréké'), ('N''Dali'), ('Nikki'), ('Pèrèrè'), ('Sinendé'), ('Tchaourou'), ('Kandi'), ('Banikoara'), ('Gogounou'), ('Karimama'), ('Malanville'), ('Segbana'),
-- Atacora & Donga
('Natitingou'), ('Boukoumbé'), ('Cobly'), ('Kérou'), ('Kouandé'), ('Matéri'), ('Pehunco'), ('Tanguiéta'), ('Toucountouna'), ('Djougou'), ('Bassila'), ('Copargo'), ('Ouaké');

-- ==========================================================
-- 2. INSERTION DES QUARTIERS / ARRONDISSEMENTS CORRESPONDANTS
-- ==========================================================

-- --- LITTORAL & ATLANTIQUE ---
INSERT INTO quartiers (nom_quartier, id_ville) VALUES
('Cadjèhoun', (SELECT id FROM villes WHERE nom_ville = 'Cotonou')),
('Fidjrossè', (SELECT id FROM villes WHERE nom_ville = 'Cotonou')),
('Haie Vive', (SELECT id FROM villes WHERE nom_ville = 'Cotonou')),
('Akpakpa', (SELECT id FROM villes WHERE nom_ville = 'Cotonou')),
('Gbégamey', (SELECT id FROM villes WHERE nom_ville = 'Cotonou')),
('Godomey', (SELECT id FROM villes WHERE nom_ville = 'Abomey-Calavi')),
('Calavi Centre', (SELECT id FROM villes WHERE nom_ville = 'Abomey-Calavi')),
('Zogbadjè', (SELECT id FROM villes WHERE nom_ville = 'Abomey-Calavi')),
('Akassato', (SELECT id FROM villes WHERE nom_ville = 'Abomey-Calavi')),
('Kpota', (SELECT id FROM villes WHERE nom_ville = 'Abomey-Calavi')),
('Allada Centre', (SELECT id FROM villes WHERE nom_ville = 'Allada')),
('Hinvi', (SELECT id FROM villes WHERE nom_ville = 'Allada')),
('Ahouandjigo', (SELECT id FROM villes WHERE nom_ville = 'Ouidah')),
('Pahou', (SELECT id FROM villes WHERE nom_ville = 'Ouidah')),
('Agonmè', (SELECT id FROM villes WHERE nom_ville = 'Kpomasse')),
('Toffo Centre', (SELECT id FROM villes WHERE nom_ville = 'Toffo')),
('Tori Centre', (SELECT id FROM villes WHERE nom_ville = 'Tori-Bossito')),
('Zè Centre', (SELECT id FROM villes WHERE nom_ville = 'Zè')),
('Sô-Ava Centre', (SELECT id FROM villes WHERE nom_ville = 'Sô-Ava'));

-- --- OUÉMÉ & PLATEAU ---
INSERT INTO quartiers (nom_quartier, id_ville) VALUES
('Ouando', (SELECT id FROM villes WHERE nom_ville = 'Porto-Novo')),
('Avakpa', (SELECT id FROM villes WHERE nom_ville = 'Porto-Novo')),
('Tokpota', (SELECT id FROM villes WHERE nom_ville = 'Porto-Novo')),
('Adjarra Centre', (SELECT id FROM villes WHERE nom_ville = 'Adjarra')),
('Adjohoun Centre', (SELECT id FROM villes WHERE nom_ville = 'Ajohoun')),
('Avrankou Centre', (SELECT id FROM villes WHERE nom_ville = 'Avrankou')),
('Bonou Centre', (SELECT id FROM villes WHERE nom_ville = 'Bonou')),
('Dangbo Centre', (SELECT id FROM villes WHERE nom_ville = 'Dangbo')),
('Agblangandan', (SELECT id FROM villes WHERE nom_ville = 'Sèmè-Podji')),
('Ekpè', (SELECT id FROM villes WHERE nom_ville = 'Sèmè-Podji')),
('Missérété Centre', (SELECT id FROM villes WHERE nom_ville = 'Akpro-Missérété')),
('Pobè Centre', (SELECT id FROM villes WHERE nom_ville = 'Pobè')),
('Adja-Ouère Centre', (SELECT id FROM villes WHERE nom_ville = 'Adja-Ouère')),
('Ifangni Centre', (SELECT id FROM villes WHERE nom_ville = 'Ifangni')),
('Kétou Centre', (SELECT id FROM villes WHERE nom_ville = 'Kétou')),
('Sakété Centre', (SELECT id FROM villes WHERE nom_ville = 'Sakété'));

-- --- ZOU & COLLINES ---
INSERT INTO quartiers (nom_quartier, id_ville) VALUES
('Hounli', (SELECT id FROM villes WHERE nom_ville = 'Abomey')),
('Vidolé', (SELECT id FROM villes WHERE nom_ville = 'Abomey')),
('Agongointo', (SELECT id FROM villes WHERE nom_ville = 'Bohicon')),
('Sodohomè', (SELECT id FROM villes WHERE nom_ville = 'Bohicon')),
('Dassa Centre', (SELECT id FROM villes WHERE nom_ville = 'Dassa-Zoumé')),
('Glazoué Centre', (SELECT id FROM villes WHERE nom_ville = 'Glazoué')),
('Savè Centre', (SELECT id FROM villes WHERE nom_ville = 'Savè')),
('Savalou Centre', (SELECT id FROM villes WHERE nom_ville = 'Savalou')),
('Bantè Centre', (SELECT id FROM villes WHERE nom_ville = 'Bantè')),
('Ouèssè Centre', (SELECT id FROM villes WHERE nom_ville = 'Ouèssè')),
('Agbangnizoun Centre', (SELECT id FROM villes WHERE nom_ville = 'Agbangnizoun')),
('Djidja Centre', (SELECT id FROM villes WHERE nom_ville = 'Djidja')),
('Covè Centre', (SELECT id FROM villes WHERE nom_ville = 'Covè')),
('Zagnanado Centre', (SELECT id FROM villes WHERE nom_ville = 'Zagnanado')),
('Za-Kpota Centre', (SELECT id FROM villes WHERE nom_ville = 'Za-Kpota')),
('Zogbodomey Centre', (SELECT id FROM villes WHERE nom_ville = 'Zogbodomey')),
('Ouinhi Centre', (SELECT id FROM villes WHERE nom_ville = 'Ouinhi'));

-- --- MONO & COUFFO ---
INSERT INTO quartiers (nom_quartier, id_ville) VALUES
('Lokossa Centre', (SELECT id FROM villes WHERE nom_ville = 'Lokossa')),
('Athiémé Centre', (SELECT id FROM villes WHERE nom_ville = 'Athiémé')),
('Bopa Centre', (SELECT id FROM villes WHERE nom_ville = 'Bopa')),
('Comè Centre', (SELECT id FROM villes WHERE nom_ville = 'Comè')),
('Grand-Popo Centre', (SELECT id FROM villes WHERE nom_ville = 'Grand-Popo')),
('Houéyogbé Centre', (SELECT id FROM villes WHERE nom_ville = 'Houéyogbé')),
('Aplahoué Centre', (SELECT id FROM villes WHERE nom_ville = 'Aplahoué')),
('Djakotomey Centre', (SELECT id FROM villes WHERE nom_ville = 'Djakotomey')),
('Dogbo Centre', (SELECT id FROM villes WHERE nom_ville = 'Dogbo')),
('Klouékanmè Centre', (SELECT id FROM villes WHERE nom_ville = 'Klouékanmè')),
('Lalo Centre', (SELECT id FROM villes WHERE nom_ville = 'Lalo')),
('Toviklin Centre', (SELECT id FROM villes WHERE nom_ville = 'Toviklin'));

-- --- BORGOU & ALIBORI ---
INSERT INTO quartiers (nom_quartier, id_ville) VALUES
('Albarika', (SELECT id FROM villes WHERE nom_ville = 'Parakou')),
('Banikanni', (SELECT id FROM villes WHERE nom_ville = 'Parakou')),
('Zongo', (SELECT id FROM villes WHERE nom_ville = 'Parakou')),
('Bembéréké Centre', (SELECT id FROM villes WHERE nom_ville = 'Bembéréké')),
('N''Dali Centre', (SELECT id FROM villes WHERE nom_ville = 'N''Dali')),
('Nikki Centre', (SELECT id FROM villes WHERE nom_ville = 'Nikki')),
('Pèrèrè Centre', (SELECT id FROM villes WHERE nom_ville = 'Pèrèrè')),
('Sinendé Centre', (SELECT id FROM villes WHERE nom_ville = 'Sinendé')),
('Tchaourou Centre', (SELECT id FROM villes WHERE nom_ville = 'Tchaourou')),
('Kandi Centre', (SELECT id FROM villes WHERE nom_ville = 'Kandi')),
('Banikoara Centre', (SELECT id FROM villes WHERE nom_ville = 'Banikoara')),
('Gogounou Centre', (SELECT id FROM villes WHERE nom_ville = 'Gogounou')),
('Karimama Centre', (SELECT id FROM villes WHERE nom_ville = 'Karimama')),
('Malanville Centre', (SELECT id FROM villes WHERE nom_ville = 'Malanville')),
('Segbana Centre', (SELECT id FROM villes WHERE nom_ville = 'Segbana'));

-- --- ATACORA & DONGA ---
INSERT INTO quartiers (nom_quartier, id_ville) VALUES
('Natitingou Centre', (SELECT id FROM villes WHERE nom_ville = 'Natitingou')),
('Boukoumbé Centre', (SELECT id FROM villes WHERE nom_ville = 'Boukoumbé')),
('Cobly Centre', (SELECT id FROM villes WHERE nom_ville = 'Cobly')),
('Kérou Centre', (SELECT id FROM villes WHERE nom_ville = 'Kérou')),
('Kouandé Centre', (SELECT id FROM villes WHERE nom_ville = 'Kouandé')),
('Matéri Centre', (SELECT id FROM villes WHERE nom_ville = 'Matéri')),
('Pehunco Centre', (SELECT id FROM villes WHERE nom_ville = 'Pehunco')),
('Tanguiéta Centre', (SELECT id FROM villes WHERE nom_ville = 'Tanguiéta')),
('Toucountouna Centre', (SELECT id FROM villes WHERE nom_ville = 'Toucountouna')),
('Djougou Centre', (SELECT id FROM villes WHERE nom_ville = 'Djougou')),
('Bassila Centre', (SELECT id FROM villes WHERE nom_ville = 'Bassila')),
('Copargo Centre', (SELECT id FROM villes WHERE nom_ville = 'Copargo')),
('Ouaké Centre', (SELECT id FROM villes WHERE nom_ville = 'Ouaké'));