<?php
/**
 * install_db.php — Installation complète de la base domizi
 * Crée les tables dans le bon ordre avec les bonnes contraintes
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'domizi';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✓ Connexion à $db\n\n";
    
    // Désactiver les contraintes étrangères temporairement
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    
    // 1. ZONES GÉOGRAPHIQUES
    echo "1️⃣  Création des tables de géographie...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `pays` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nom_pays` VARCHAR(100) NOT NULL,
        `code_pays` VARCHAR(5) NOT NULL,
        `actif` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `code_pays` (`code_pays`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `departements` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_pays` INT UNSIGNED NOT NULL,
        `nom` VARCHAR(100) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `id_pays` (`id_pays`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `villes` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_pays` INT UNSIGNED NOT NULL,
        `id_departement` INT UNSIGNED NOT NULL,
        `nom_ville` VARCHAR(100) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `id_pays` (`id_pays`),
        KEY `id_departement` (`id_departement`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `quartiers` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_ville` INT UNSIGNED NOT NULL,
        `nom_quartier` VARCHAR(100) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `id_ville` (`id_ville`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Géographie créée\n";
    
    // 2. MÉTIERS & DOMAINES
    echo "2️⃣  Création des métiers et domaines...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `domaines` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nom` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL,
        `icone` VARCHAR(50) DEFAULT NULL,
        `couleur` VARCHAR(10) DEFAULT NULL,
        `actif` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `metiers` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_domaine` INT UNSIGNED NOT NULL,
        `nom` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `icone` VARCHAR(50) DEFAULT NULL,
        `actif` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`),
        KEY `id_domaine` (`id_domaine`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Métiers créés\n";
    
    // 3. UTILISATEURS
    echo "3️⃣  Création de la table users...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nom` VARCHAR(100) NOT NULL,
        `prenom` VARCHAR(100) NOT NULL,
        `sexe` ENUM('homme','femme') NOT NULL DEFAULT 'homme',
        `email` VARCHAR(150) NOT NULL,
        `telephone` VARCHAR(25) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('client','prestataire','admin') NOT NULL DEFAULT 'client',
        `photo_profil` VARCHAR(255) DEFAULT NULL,
        `bio` TEXT DEFAULT NULL,
        `id_pays` INT UNSIGNED DEFAULT NULL,
        `id_ville` INT UNSIGNED DEFAULT NULL,
        `id_quartier` INT UNSIGNED DEFAULT NULL,
        `statut` ENUM('actif','inactif','banni') NOT NULL DEFAULT 'inactif',
        `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
        `raison_ban` VARCHAR(255) DEFAULT NULL,
        `email_verifie` TINYINT(1) NOT NULL DEFAULT 0,
        `telephone_verifie` TINYINT(1) NOT NULL DEFAULT 0,
        `code_verification` VARCHAR(10) DEFAULT NULL,
        `token_reset` VARCHAR(100) DEFAULT NULL,
        `token_expire_at` DATETIME DEFAULT NULL,
        `solde` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `solde_gele` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `diplome` VARCHAR(255) DEFAULT NULL,
        `abonnement_status` TINYINT(1) NOT NULL DEFAULT 0,
        `date_expiration_abo` DATE DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`),
        KEY `id_pays` (`id_pays`),
        KEY `id_ville` (`id_ville`),
        KEY `id_quartier` (`id_quartier`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Users créé\n";
    
    // 4. PROFIL PRESTATAIRE
    echo "4️⃣  Création du profil prestataire...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `profils_prestataires` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `id_metier` INT UNSIGNED NOT NULL,
        `abonnement_status` ENUM('actif','inactif','expire') NOT NULL DEFAULT 'inactif',
        `date_debut_abo` DATE DEFAULT NULL,
        `date_expiration_abo` DATE DEFAULT NULL,
        `diplome` VARCHAR(255) DEFAULT NULL,
        `note_admin` TEXT DEFAULT NULL,
        `date_validation` DATETIME DEFAULT NULL,
        `valide_par` INT UNSIGNED DEFAULT NULL,
        `tarif_base` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `gps_lat` DECIMAL(10,8) DEFAULT NULL,
        `gps_lng` DECIMAL(11,8) DEFAULT NULL,
        `rayon_intervention` INT UNSIGNED NOT NULL DEFAULT 10,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_id` (`user_id`),
        KEY `id_metier` (`id_metier`),
        KEY `valide_par` (`valide_par`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Profil prestataire créé\n";
    
    // 5. ZONES PRESTATAIRE
    echo "5️⃣  Création des zones prestataire...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `zones_prestataire` (
        `id_prestataire` INT UNSIGNED NOT NULL,
        `id_quartier` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`id_prestataire`, `id_quartier`),
        KEY `id_quartier` (`id_quartier`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Zones prestataire créées\n";
    
    // 6. SERVICES
    echo "6️⃣  Création des services...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `services` (
        `id_service` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_prestataire` INT UNSIGNED NOT NULL,
        `id_metier` INT UNSIGNED NOT NULL,
        `nom_service` VARCHAR(150) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `options_json` TEXT DEFAULT NULL,
        `prix` DECIMAL(10,2) NOT NULL,
        `duree` INT NOT NULL DEFAULT 60,
        `photo` VARCHAR(255) DEFAULT NULL,
        `actif` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_service`),
        KEY `id_prestataire` (`id_prestataire`),
        KEY `id_metier` (`id_metier`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Services créés\n";
    
    // 7. DISPONIBILITÉS
    echo "7️⃣  Création des disponibilités...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `disponibilites` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `prestataire_id` INT UNSIGNED NOT NULL,
        `jour_semaine` ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche') NOT NULL,
        `heure_debut` TIME NOT NULL,
        `heure_fin` TIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_dispo` (`prestataire_id`, `jour_semaine`),
        KEY `prestataire_id` (`prestataire_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Disponibilités créées\n";
    
    // 8. RENDEZ-VOUS
    echo "8️⃣  Création des rendez-vous...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `rendez_vous` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `client_id` INT UNSIGNED NOT NULL,
        `prestataire_id` INT UNSIGNED NOT NULL,
        `service_id` INT UNSIGNED NOT NULL,
        `options_choisies` TEXT DEFAULT NULL,
        `date_rdv` DATE NOT NULL,
        `heure_debut` TIME NOT NULL,
        `heure_fin` TIME NOT NULL,
        `client_gps_lat` DECIMAL(10,8) DEFAULT NULL,
        `client_gps_lng` DECIMAL(11,8) DEFAULT NULL,
        `client_adresse_texte` VARCHAR(255) DEFAULT NULL,
        `adresse_prestation` TEXT DEFAULT NULL,
        `montant_total` DECIMAL(10,2) NOT NULL,
        `montant_gele` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `commission_site` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `statut_rdv` ENUM('en_attente','confirme','annule','termine') NOT NULL DEFAULT 'en_attente',
        `statut_paiement` ENUM('non_paye','gele','paye','rembourse') NOT NULL DEFAULT 'non_paye',
        `annule_par` ENUM('client','prestataire','admin') DEFAULT NULL,
        `motif_annulation` TEXT DEFAULT NULL,
        `date_annulation` DATETIME DEFAULT NULL,
        `whatsapp_envoye` TINYINT(1) NOT NULL DEFAULT 0,
        `push_envoye` TINYINT(1) NOT NULL DEFAULT 0,
        `date_demande` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `client_id` (`client_id`),
        KEY `prestataire_id` (`prestataire_id`),
        KEY `service_id` (`service_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Rendez-vous créés\n";
    
    // 9. AVIS
    echo "9️⃣  Création des avis...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `avis` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `rdv_id` INT UNSIGNED NOT NULL,
        `client_id` INT UNSIGNED NOT NULL,
        `prestataire_id` INT UNSIGNED NOT NULL,
        `note` TINYINT UNSIGNED NOT NULL,
        `commentaire` TEXT DEFAULT NULL,
        `type` ENUM('public','plainte') NOT NULL DEFAULT 'public',
        `statut_admin` ENUM('en_attente','traite') NOT NULL DEFAULT 'en_attente',
        `reponse_admin` TEXT DEFAULT NULL,
        `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_avis_rdv` (`rdv_id`),
        KEY `client_id` (`client_id`),
        KEY `prestataire_id` (`prestataire_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Avis créés\n";
    
    // 10. TRANSACTIONS & FINANCES
    echo "🔟  Création des transactions...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `transactions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `rdv_id` INT UNSIGNED DEFAULT NULL,
        `type` ENUM('depot','retrait','blocage','deblocage','gain','commission','remboursement') NOT NULL,
        `montant` DECIMAL(10,2) NOT NULL,
        `solde_avant` DECIMAL(10,2) NOT NULL,
        `solde_apres` DECIMAL(10,2) NOT NULL,
        `motif` VARCHAR(255) NOT NULL,
        `reference` VARCHAR(100) DEFAULT NULL,
        `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `rdv_id` (`rdv_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `transactions_portefeuille` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `type_transaction` VARCHAR(50) NOT NULL,
        `montant` DECIMAL(10,2) NOT NULL,
        `motif` VARCHAR(255) DEFAULT NULL,
        `date_demande` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `paiements_kkiapay` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `transaction_id` VARCHAR(150) NOT NULL,
        `montant` DECIMAL(10,2) NOT NULL,
        `type` ENUM('abonnement','depot_portefeuille') NOT NULL,
        `statut` ENUM('en_attente','valide','echoue') NOT NULL DEFAULT 'en_attente',
        `webhook_data` TEXT DEFAULT NULL,
        `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `transaction_id` (`transaction_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Transactions créées\n";
    
    // 11. AUDIT & SÉCURITÉ
    echo "1️⃣1️⃣ Création de l'audit et de la sécurité...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `audit_transactions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED DEFAULT NULL,
        `user_role` VARCHAR(50) DEFAULT NULL,
        `transaction_type` VARCHAR(100) DEFAULT NULL,
        `montant` DECIMAL(10,2) DEFAULT NULL,
        `status` VARCHAR(50) DEFAULT NULL,
        `motif` TEXT DEFAULT NULL,
        `error_message` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `session_id` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `idempotency_transactions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `idempotency_key` VARCHAR(255) NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `montant` DECIMAL(10,2) DEFAULT NULL,
        `result_json` LONGTEXT DEFAULT NULL,
        `status` ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_idempotency` (`user_id`, `idempotency_key`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `abonnements_paiements` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `prestataire_id` INT UNSIGNED NOT NULL,
        `montant` DECIMAL(10,2) NOT NULL,
        `date_debut` DATE NOT NULL,
        `date_fin` DATE NOT NULL,
        `statut` ENUM('actif','expire','annule') NOT NULL DEFAULT 'actif',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `prestataire_id` (`prestataire_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Audit et sécurité créés\n";
    
    // 12. NOTIFICATIONS
    echo "1️⃣2️⃣ Création des notifications...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `rdv_id` INT UNSIGNED DEFAULT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'info',
        `titre` VARCHAR(150) DEFAULT NULL,
        `message` TEXT NOT NULL,
        `lu` TINYINT(1) NOT NULL DEFAULT 0,
        `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `rdv_id` (`rdv_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `push_subscriptions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `endpoint` TEXT NOT NULL,
        `p256dh_key` TEXT NOT NULL,
        `auth_key` VARCHAR(255) NOT NULL,
        `device_type` VARCHAR(50) DEFAULT 'web',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Notifications créées\n";
    
    // 13. AUTRES TABLES
    echo "1️⃣3️⃣ Création des autres tables...\n";
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `ia_sessions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED DEFAULT NULL,
        `session_token` VARCHAR(255) NOT NULL,
        `contexte_json` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `session_token` (`session_token`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `suppressions_comptes` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_user_supprime` INT NOT NULL,
        `nom` VARCHAR(100) NOT NULL,
        `prenom` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `role` VARCHAR(50) NOT NULL,
        `motif` TEXT DEFAULT NULL,
        `supprime_par` INT UNSIGNED DEFAULT NULL,
        `date_demande` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `supprime_par` (`supprime_par`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `favoris` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `client_id` INT UNSIGNED NOT NULL,
        `prestataire_id` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_favori` (`client_id`, `prestataire_id`),
        KEY `client_id` (`client_id`),
        KEY `prestataire_id` (`prestataire_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `plaintes` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `rdv_id` INT UNSIGNED DEFAULT NULL,
        `client_id` INT UNSIGNED NOT NULL,
        `prestataire_id` INT UNSIGNED NOT NULL,
        `motif` TEXT NOT NULL,
        `description` TEXT DEFAULT NULL,
        `statut` ENUM('en_attente','traite','rejetee') NOT NULL DEFAULT 'en_attente',
        `reponse` TEXT DEFAULT NULL,
        `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `rdv_id` (`rdv_id`),
        KEY `client_id` (`client_id`),
        KEY `prestataire_id` (`prestataire_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `otp_codes` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `purpose` VARCHAR(50) NOT NULL,
        `code` VARCHAR(10) NOT NULL,
        `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `expires_at` DATETIME NOT NULL,
        `verified_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        UNIQUE KEY `unique_otp` (`user_id`, `purpose`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `email_actions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `token` VARCHAR(255) NOT NULL,
        `type` VARCHAR(50) NOT NULL,
        `expires_at` DATETIME NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `token` (`token`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    
    echo "  ✓ Autres tables créées\n";
    
    // DONNÉES DE BASE (SEED)
    echo "\n📊 Insertion des données de base...\n";
    
    // Pays
    $pdo->exec("
    INSERT INTO `pays` (`nom_pays`, `code_pays`, `actif`) VALUES
    ('Bénin', 'BJ', 1),
    ('Togo', 'TG', 1),
    ('Côte d\\'Ivoire', 'CI', 1),
    ('Sénégal', 'SN', 1),
    ('Burkina Faso', 'BF', 1)
    ON DUPLICATE KEY UPDATE `id`=`id`;
    ");
    
    echo "  ✓ Pays insérés\n";
    
    // Domaines
    $pdo->exec("
    INSERT INTO `domaines` (`nom`, `slug`, `icone`, `couleur`, `actif`) VALUES
    ('Beauté & Bien-être', 'beaute', 'bi-stars', '#D4AF37', 1),
    ('Maison & Services', 'maison', 'bi-house-gear', '#4A90D9', 0),
    ('Santé & Soins', 'sante', 'bi-heart-pulse', '#E84B4B', 0)
    ON DUPLICATE KEY UPDATE `id`=`id`;
    ");
    
    echo "  ✓ Domaines insérés\n";
    
    // Métiers
    $pdo->exec("
    INSERT INTO `metiers` (`id_domaine`, `nom`, `slug`, `description`, `icone`, `actif`) VALUES
    (1, 'Coiffure', 'coiffure', 'Coiffure homme et femme à domicile', 'bi-scissors', 1),
    (1, 'Maquillage', 'maquillage', 'Maquillage professionnel à domicile', 'bi-palette', 1),
    (1, 'Ongles', 'ongles', 'Manucure et pédicure à domicile', 'bi-gem', 1),
    (1, 'Massage', 'massage', 'Massage bien-être et relaxant à domicile', 'bi-person-arms-up', 1),
    (1, 'Soins', 'soins', 'Soins du visage et du corps', 'bi-droplet', 1)
    ON DUPLICATE KEY UPDATE `id`=`id`;
    ");
    
    echo "  ✓ Métiers insérés\n";
    
    // Réactiver les contraintes étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    
    // Vérifier
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $table_count = $stmt->fetchColumn();
    
    echo "\n✅ Installation complétée! ($table_count tables créées)\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    }
    exit(1);
}
?>
