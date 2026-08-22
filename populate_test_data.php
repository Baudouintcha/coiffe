<?php
/**
 * SCRIPT DE REMPLISSAGE — 5 Coiffeurs + 5 Clients + Rendez-vous
 * Date : Août 2026
 * Objectif : Peupler la base de données pour tester le tableau de bord
 */

require_once __DIR__ . '/security/config.php';

$bdd = $pdo;

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║         REMPLISSAGE BASE DE DONNÉES — COIFFEURS + CLIENTS          ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

// ========================================================================
// PARTIE 1 : INSÉRER 5 COIFFEURS
// ========================================================================

echo "📍 ÉTAPE 1 : Insertion de 5 coiffeurs\n";
echo str_repeat("-", 70) . "\n";

$coiffeurs_data = [
    [
        'nom' => 'Diallo',
        'prenom' => 'Aïssatou',
        'email' => 'aissatou.diallo@coiffons.com',
        'telephone' => '22797654321',
        'password' => 'Coiffeur123!',
        'bio' => 'Passionnée par les tresses africaines depuis 10 ans',
        'sexe' => 'femme'
    ],
    [
        'nom' => 'Sow',
        'prenom' => 'Mariam',
        'email' => 'mariam.sow@coiffons.com',
        'telephone' => '22797654322',
        'password' => 'Coiffeur123!',
        'bio' => 'Expert en lissage et traitement des cheveux',
        'sexe' => 'femme'
    ],
    [
        'nom' => 'Toure',
        'prenom' => 'Fatima',
        'email' => 'fatima.toure@coiffons.com',
        'telephone' => '22797654323',
        'password' => 'Coiffeur123!',
        'bio' => 'Styliste professionnelle avec 15 ans d\'expérience',
        'sexe' => 'femme'
    ],
    [
        'nom' => 'Bah',
        'prenom' => 'Awa',
        'email' => 'awa.bah@coiffons.com',
        'telephone' => '22797654324',
        'password' => 'Coiffeur123!',
        'bio' => 'Spécialiste en extensions et tissage haute qualité',
        'sexe' => 'femme'
    ],
    [
        'nom' => 'Cisse',
        'prenom' => 'Khadidja',
        'email' => 'khadidja.cisse@coiffons.com',
        'telephone' => '22797654325',
        'password' => 'Coiffeur123!',
        'bio' => 'Experte en soins capillaires naturels et chimiques',
        'sexe' => 'femme'
    ]
];

$coiffeurs_inseres = [];
foreach ($coiffeurs_data as $idx => $coiffeur) {
    try {
        $password_hash = password_hash($coiffeur['password'], PASSWORD_BCRYPT);
        $stmt = $bdd->prepare("
            INSERT INTO users 
            (nom, prenom, email, telephone, password, role, sexe, bio, is_approved, abonnement_status, created_at)
            VALUES 
            (:nom, :prenom, :email, :telephone, :password, 'prestataire', :sexe, :bio, 1, 1, NOW())
        ");
        
        $stmt->execute([
            ':nom' => $coiffeur['nom'],
            ':prenom' => $coiffeur['prenom'],
            ':email' => $coiffeur['email'],
            ':telephone' => $coiffeur['telephone'],
            ':password' => $password_hash,
            ':sexe' => $coiffeur['sexe'],
            ':bio' => $coiffeur['bio']
        ]);
        
        $coiffeur_id = $bdd->lastInsertId();
        $coiffeurs_inseres[] = $coiffeur_id;
        
        echo "✅ Coiffeur #$idx insérée : {$coiffeur['prenom']} {$coiffeur['nom']} (ID: $coiffeur_id)\n";
        
    } catch (Exception $e) {
        echo "❌ Erreur insertion coiffeur: {$e->getMessage()}\n";
    }
}

echo "\n✅ {" . count($coiffeurs_inseres) . "} coiffeurs insérés\n\n";

// ========================================================================
// PARTIE 2 : INSÉRER 5 CLIENTS
// ========================================================================

echo "📍 ÉTAPE 2 : Insertion de 5 clients\n";
echo str_repeat("-", 70) . "\n";

$clients_data = [
    [
        'nom' => 'Coulibaly',
        'prenom' => 'Hawa',
        'email' => 'hawa.coulibaly@email.com',
        'telephone' => '22798765432',
        'password' => 'Client123!',
        'sexe' => 'F',
        'adresse' => 'Cotonou, Carrefour'
    ],
    [
        'nom' => 'Traore',
        'prenom' => 'Lala',
        'email' => 'lala.traore@email.com',
        'telephone' => '22798765433',
        'password' => 'Client123!',
        'sexe' => 'F',
        'adresse' => 'Porto-Novo, Centre'
    ],
    [
        'nom' => 'Kone',
        'prenom' => 'Ami',
        'email' => 'ami.kone@email.com',
        'telephone' => '22798765434',
        'password' => 'Client123!',
        'sexe' => 'F',
        'adresse' => 'Cotonou, Menontin'
    ],
    [
        'nom' => 'Diarra',
        'prenom' => 'Fatoumata',
        'email' => 'fatoumata.diarra@email.com',
        'telephone' => '22798765435',
        'password' => 'Client123!',
        'sexe' => 'F',
        'adresse' => 'Parakou, Centre ville'
    ],
    [
        'nom' => 'Bah',
        'prenom' => 'Zainab',
        'email' => 'zainab.bah@email.com',
        'telephone' => '22798765436',
        'password' => 'Client123!',
        'sexe' => 'F',
        'adresse' => 'Cotonou, Gbégamey'
    ]
];

$clients_inseres = [];
foreach ($clients_data as $idx => $client) {
    try {
        $password_hash = password_hash($client['password'], PASSWORD_BCRYPT);
        $stmt = $bdd->prepare("
            INSERT INTO users 
            (nom, prenom, email, telephone, password, role, sexe, is_approved, created_at)
            VALUES 
            (:nom, :prenom, :email, :telephone, :password, 'client', :sexe, 1, NOW())
        ");
        
        $stmt->execute([
            ':nom' => $client['nom'],
            ':prenom' => $client['prenom'],
            ':email' => $client['email'],
            ':telephone' => $client['telephone'],
            ':password' => $password_hash,
            ':sexe' => $client['sexe']
        ]);
        
        $client_id = $bdd->lastInsertId();
        $clients_inseres[] = $client_id;
        
        echo "✅ Client #$idx insérée : {$client['prenom']} {$client['nom']} (ID: $client_id)\n";
        
    } catch (Exception $e) {
        echo "❌ Erreur insertion client: {$e->getMessage()}\n";
    }
}

echo "\n✅ {" . count($clients_inseres) . "} clients insérés\n\n";

// ========================================================================
// PARTIE 3 : GÉNÉRER RENDEZ-VOUS DE TEST
// ========================================================================

echo "📍 ÉTAPE 3 : Génération de rendez-vous de test\n";
echo str_repeat("-", 70) . "\n";

// Services disponibles
$services = $bdd->query("SELECT id_service, nom_service FROM services LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

if (empty($services)) {
    echo "⚠️  Aucun service trouvé. Création de services de test...\n";
    $services_test = [
        'Coupe classique',
        'Lissage brésilien',
        'Tressage africain',
        'Coloration',
        'Traitement profond'
    ];
    
    foreach ($services_test as $service_name) {
        try {
            $stmt = $bdd->prepare("INSERT INTO services (nom_service, description, prix) VALUES (:nom, :desc, :prix)");
            $stmt->execute([
                ':nom' => $service_name,
                ':desc' => 'Service de ' . strtolower($service_name),
                ':prix' => rand(3000, 10000)
            ]);
            $service_id = $bdd->lastInsertId();
            $services[] = ['id_service' => $service_id, 'nom_service' => $service_name];
        } catch (Exception $e) {
            echo "❌ Erreur création service: {$e->getMessage()}\n";
        }
    }
}

// Générer 8-12 rendez-vous
$rdv_count = 0;
$dates_possibles = [
    '2026-08-25',
    '2026-08-26',
    '2026-08-27',
    '2026-08-28',
    '2026-08-29',
];
$heures_possibles = ['09:00:00', '10:00:00', '14:00:00', '15:00:00', '16:00:00', '11:00:00'];

$date_idx = 0;
foreach ($dates_possibles as $date) {
    for ($h = 0; $h < 3 && $rdv_count < 12; $h++) {
        
        $client_id = $clients_inseres[array_rand($clients_inseres)];
        $coiffeur_id = $coiffeurs_inseres[array_rand($coiffeurs_inseres)];
        $service = $services[array_rand($services)];
        
        // Mix de statuts
        $statuts = ['confirme', 'en_attente', 'en_attente', 'confirme'];
        $statut = $statuts[array_rand($statuts)];
        
        // Prix aléatoire
        $montant = rand(3000, 12000);
        $heure = $heures_possibles[array_rand($heures_possibles)];
        
        try {
            $stmt = $bdd->prepare("
                INSERT INTO rendez_vous 
                (client_id, prestataire_id, service_id, date_rdv, heure_debut, montant_total, statut_rdv, statut_paiement, date_demande)
                VALUES 
                (:client, :coiffeur, :service, :date, :heure, :montant, :statut, 'non_paye', NOW())
            ");
            
            $stmt->execute([
                ':client' => $client_id,
                ':coiffeur' => $coiffeur_id,
                ':service' => $service['id_service'],
                ':date' => $date,
                ':heure' => $heure,
                ':montant' => $montant,
                ':statut' => $statut
            ]);
            
            $rdv_id = $bdd->lastInsertId();
            $rdv_count++;
            
            echo "✅ RDV #$rdv_count créé (ID: $rdv_id) - Client $client_id → Coiffeur $coiffeur_id - {$service['nom_service']}\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur création RDV: {$e->getMessage()}\n";
        }
    }
}

echo "\n✅ {$rdv_count} rendez-vous créés\n\n";

// ========================================================================
// PARTIE 4 : AJOUTER SOLDES DE PORTEFEUILLE
// ========================================================================

echo "📍 ÉTAPE 4 : Création de soldes de portefeuille\n";
echo str_repeat("-", 70) . "\n";

// Ajouter des soldes aux coiffeurs
foreach ($coiffeurs_inseres as $coiffeur_id) {
    try {
        $solde = rand(10000, 100000);
        $stmt = $bdd->prepare("UPDATE users SET solde = :solde WHERE id = :id");
        $stmt->execute([':solde' => $solde, ':id' => $coiffeur_id]);
        echo "✅ Solde coiffeur $coiffeur_id : $solde FCFA\n";
    } catch (Exception $e) {
        echo "❌ Erreur solde: {$e->getMessage()}\n";
    }
}

echo "\n";

// ========================================================================
// PARTIE 5 : AFFICHER RÉSUMÉ
// ========================================================================

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                        RÉSUMÉ DE L'INSERTION                       ║\n";
echo "╠════════════════════════════════════════════════════════════════════╣\n";

// Récupérer les stats
try {
    $nb_coiffeurs = $bdd->query("SELECT COUNT(*) FROM users WHERE role='prestataire'")->fetchColumn();
    $nb_clients = $bdd->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
    $nb_rdv = $bdd->query("SELECT COUNT(*) FROM rendez_vous")->fetchColumn();
    $commission = $bdd->query("SELECT COALESCE(SUM(montant_total * 0.05), 0) FROM rendez_vous WHERE statut_rdv='confirme'")->fetchColumn();
    $tresorerie = $bdd->query("SELECT COALESCE(SUM(solde), 0) FROM users")->fetchColumn();
    
    echo "║ ✅ Coiffeurs total en BD        : $nb_coiffeurs\n";
    echo "║ ✅ Clients total en BD          : $nb_clients\n";
    echo "║ ✅ Rendez-vous créés            : {$rdv_count}\n";
    echo "║ ✅ Commission estimée (5%)      : " . number_format($commission, 0, ',', ' ') . " FCFA\n";
    echo "║ ✅ Trésorerie séquestrée       : " . number_format($tresorerie, 0, ',', ' ') . " FCFA\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lecture stats: {$e->getMessage()}\n";
}

echo "╠════════════════════════════════════════════════════════════════════╣\n";
echo "║                    ACCÈS POUR TESTER                              ║\n";
echo "╠════════════════════════════════════════════════════════════════════╣\n";
echo "║ ADMIN :                                                            ║\n";
echo "║   Email    : admin@domizi.local                                    ║\n";
echo "║   Password : Admin2026!                                            ║\n";
echo "║   URL      : http://localhost/coiffons/first/admin_dashboard.php   ║\n";
echo "║                                                                    ║\n";
echo "║ COIFFEURS (exemples) :                                             ║\n";
echo "║   Email    : aissatou.diallo@coiffons.com                          ║\n";
echo "║   Password : Coiffeur123!                                          ║\n";
echo "║                                                                    ║\n";
echo "║ CLIENTS (exemples) :                                               ║\n";
echo "║   Email    : hawa.coulibaly@email.com                              ║\n";
echo "║   Password : Client123!                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ REMPLISSAGE TERMINÉ — Allez sur le dashboard admin pour voir les données !\n";
?>
