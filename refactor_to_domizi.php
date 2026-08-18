<?php
/**
 * Script de refactoring : migrer le code PHP de cft → domizi
 * Remplace automatiquement les colonnes et tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Refactoring PHP pour DOMIZI</h1>";
echo "<style>
    body { font-family: monospace; background: #f0f0f0; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: white; padding: 15px; border-left: 4px solid #4A90D9; max-width: 1200px; word-wrap: break-word; }
</style>";
echo "<pre>";

// Fichiers à mettre à jour
$files_to_update = [
    'client/catalogue.php',
    'client/mes_rendezvous.php',
    'client/creer_rendezvous.php',
    'client/reserver.php',
    'coiffeurs/gestion_catalogue.php',
    'coiffeurs/mes_zones.php',
    'coiffeurs/profil_coiffeurs.php',
    'coiffeurs/profil_public.php',
    'coiffeurs/portefeuille.php',
    'security/notifications.php',
    'security/cron_notifications.php',
    'security/PaymentService.php',
];

// Mappings : ancien → nouveau
$replacements = [
    // Tables
    '`prestations`' => '`services`',
    "'prestations'" => "'services'",
    '"prestations"' => '"services"',
    '`zones_coiffeur`' => '`zones_prestataire`',
    "'zones_coiffeur'" => "'zones_prestataire'",
    '"zones_coiffeur"' => '"zones_prestataire"',
    '`commentaires`' => '`avis`',
    "'commentaires'" => "'avis'",
    '"commentaires"' => '"avis"',
    '`portefeuilles`' => '/* N/A - solde dans users */',
    
    // Colonnes rendez_vous
    '.coiffeur_id' => '.prestataire_id',
    'r.coiffeur_id' => 'r.prestataire_id',
    'rdv.coiffeur_id' => 'rdv.prestataire_id',
    '`coiffeur_id`' => '`prestataire_id`',
    "r . coiffeur_id" => "r . prestataire_id",
    
    'coiffure_id' => 'service_id',
    'coiffure_id,' => 'service_id,',
    'coiffure_id ' => 'service_id ',
    'coiffure_id)' => 'service_id)',
    
    // Colonnes prestations (ex-table)
    'id_prestation' => 'id_service',
    'id_coiffeur' => 'id_prestataire',
    'nom_style' => 'nom_service',
    'photo_style' => 'photo',
    
    // Colonnes disponibilites
    'coiffeur_id' => 'prestataire_id',
    
    // Colonnes users - abonnement
    "'abonnement_status'" => "'abonnement_status'", // Garder mais colonne existe
    
    // Colonnes notifications
    "'statut_lecture'" => "'lu'",
    '"statut_lecture"' => '"lu"',
    'statut_lecture' => 'lu',
    "'non_lu'" => "0",
    "'lu'" => "1",
    "'date_notification'" => "'date_creation'",
    '"date_notification"' => '"date_creation"',
    
    // Colonnes avis
    'type_commentaire' => 'type',
    'id_coiffeur' => 'prestataire_id',
    'id_client' => 'client_id',
];

$count_files = 0;
$count_replacements = 0;

foreach ($files_to_update as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "<span class='warning'>⊘ $file : non trouvé</span>\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    $original_content = $content;
    $file_replacements = 0;
    
    // Appliquer les remplacements
    foreach ($replacements as $old => $new) {
        $count = 0;
        $content = str_replace($old, $new, $content, $count);
        if ($count > 0) {
            $file_replacements += $count;
        }
    }
    
    // Sauvegarder si changements
    if ($content !== $original_content) {
        file_put_contents($filepath, $content);
        $count_files++;
        $count_replacements += $file_replacements;
        echo "<span class='success'>✓ $file : $file_replacements remplacements</span>\n";
    } else {
        echo "<span class='info'>ℹ $file : aucun changement</span>\n";
    }
}

echo "\n<span class='success'>════════════════════════════════════════</span>\n";
echo "<span class='success'>✓ REFACTORING TERMINÉ</span>\n";
echo "<span class='success'>  $count_files fichiers modifiés</span>\n";
echo "<span class='success'>  $count_replacements remplacements effectués</span>\n";
echo "<span class='success'>════════════════════════════════════════</span>\n\n";

echo "⚠ REMARQUES IMPORTANTES :\n";
echo "1. Vérifier les fichiers modifiés manuellement\n";
echo "2. Certains remplacements peuvent nécessiter des ajustements\n";
echo "3. Tester l'application après les changements\n";

echo "\n</pre>";
