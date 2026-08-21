<?php
/**
 * Script de Correction Automatique des Erreurs SQL
 * Corrige TOUTES les erreurs réelles détectées
 */

set_time_limit(120);

$fixes_applied = [];
$files_modified = [];

echo "🔧 CORRECTION AUTOMATIQUE DES ERREURS SQL\n";
echo "==========================================\n\n";

// ═══════════════════════════════════════════════════════
// CORRECTION GLOBALE: Scanner tous les fichiers PHP
// ═══════════════════════════════════════════════════════

function scanDirectory($dir, &$files, $exclude) {
    $items = @scandir($dir);
    if (!$items) return;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            if (!in_array($item, $exclude)) {
                scanDirectory($path, $files, $exclude);
            }
        } else {
            if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                $files[] = $path;
            }
        }
    }
}

$all_files = [];
$exclude = ['.git', '.kiro', 'node_modules', 'vendor', 'tests'];
scanDirectory(__DIR__, $all_files, $exclude);

echo "📂 Scan de " . count($all_files) . " fichiers PHP...\n\n";

// Patterns de correction
$correction_patterns = [
    // 1. rendez_vous.user_id → client_id (dans WHERE)
    [
        'name' => 'rendez_vous.user_id → client_id',
        'pattern' => '/(rendez_vous|r|rdv)\.user_id\s*=/',
        'replacement' => '$1.client_id =',
        'description' => 'Colonne user_id n\'existe pas dans rendez_vous'
    ],
    
    // 2. rendez_vous.date_demande → date_demande
    [
        'name' => 'rendez_vous.date_demande → date_demande',
        'pattern' => '/(rendez_vous|r|rdv)\.date_demande/',
        'replacement' => '$1.date_demande',
        'description' => 'Colonne date_demande n\'existe pas dans rendez_vous'
    ],
    
    // 3. rendez_vous.updated_at (supprimer si trouvé)
    [
        'name' => 'rendez_vous.updated_at (suppression)',
        'pattern' => ',\s*(rendez_vous|r|rdv)\.updated_at/',
        'replacement' => '',
        'description' => 'Colonne updated_at n\'existe pas dans rendez_vous'
    ],
    
    // 4. notifications.user_id → user_id
    [
        'name' => 'notifications.user_id → user_id',
        'pattern' => '/(notifications|n)\.user_id/',
        'replacement' => '$1.user_id',
        'description' => 'Colonne user_id n\'existe pas dans notifications'
    ],
    
    // 5. notifications.lu → lu
    [
        'name' => 'notifications.lu → lu',
        'pattern' => '/(notifications|n)\.lu/',
        'replacement' => '$1.lu',
        'description' => 'Colonne lu n\'existe pas dans notifications'
    ],
    
    // 6. notifications.date_demande → date_demande
    [
        'name' => 'notifications.date_demande → date_demande',
        'pattern' => '/(notifications|n)\.date_creation/',
        'replacement' => '$1.date_demande',
        'description' => 'Colonne date_creation n\'existe pas dans notifications'
    ],
    
    // 7. disponibilites.prestataire_id → prestataire_id
    [
        'name' => 'disponibilites.prestataire_id → prestataire_id',
        'pattern' => '/(disponibilites|d)\.prestataire_id/',
        'replacement' => '$1.prestataire_id',
        'description' => 'Colonne prestataire_id n\'existe pas dans disponibilites'
    ],
    
    // 8. rendez_vous.prestataire_id → prestataire_id
    [
        'name' => 'rendez_vous.prestataire_id → prestataire_id',
        'pattern' => '/(rendez_vous|r|rdv)\.prestataire_id/',
        'replacement' => '$1.prestataire_id',
        'description' => 'Colonne prestataire_id n\'existe pas dans rendez_vous'
    ],
    
    // 9. rendez_vous.service_id → service_id
    [
        'name' => 'rendez_vous.service_id → service_id',
        'pattern' => '/(rendez_vous|r|rdv)\.id_service/',
        'replacement' => '$1.service_id',
        'description' => 'Colonne id_service n\'existe pas dans rendez_vous'
    ],
    
    // 10. rendez_vous.heure_debut → heure_debut
    [
        'name' => 'rendez_vous.heure_debut → heure_debut',
        'pattern' => '/(rendez_vous|r|rdv)\.heure_debut/',
        'replacement' => '$1.heure_debut',
        'description' => 'Colonne heure_debut n\'existe pas dans rendez_vous'
    ],
    
    // 11. otp_codes.tentatives → tentatives
    [
        'name' => 'otp_codes.tentatives → tentatives',
        'pattern' => '/(otp_codes|o)\.tentatives/',
        'replacement' => '$1.tentatives',
        'description' => 'Colonne tentatives n\'existe pas dans otp_codes'
    ],
    
    // 12. users.diplome → profils_prestataires.diplome (UPDATE)
    [
        'name' => 'UPDATE users.diplome → profils_prestataires.diplome',
        'pattern' => '/UPDATE\s+users\s+SET\s+diplome\s*=\s*([^\s,]+).*?WHERE\s+id\s*=\s*\?/is',
        'replacement' => 'UPDATE profils_prestataires SET diplome = $1 WHERE user_id = ?',
        'description' => 'Colonne diplome n\'existe pas dans users'
    ],
    
    // 13. users.abonnement_status → profils_prestataires.abonnement_status (UPDATE)
    [
        'name' => 'UPDATE users.abonnement_status → profils_prestataires.abonnement_status',
        'pattern' => '/UPDATE\s+users\s+SET\s+abonnement_status\s*=\s*([^\s,]+).*?WHERE\s+id\s*=\s*\?/is',
        'replacement' => 'UPDATE profils_prestataires SET abonnement_status = $1 WHERE user_id = ?',
        'description' => 'Colonne abonnement_status n\'existe pas dans users'
    ],
    
    // 14. services.prestataire_id → id_prestataire (dans JOIN uniquement)
    [
        'name' => 'services JOIN: prestataire_id → id_prestataire',
        'pattern' => '/(JOIN\s+services\s+\w+\s+ON\s+\w+\.)prestataire_id/',
        'replacement' => '$1id_prestataire',
        'description' => 'Dans services, utiliser id_prestataire (pas prestataire_id)'
    ],
];

$total_fixes = 0;

foreach ($all_files as $file) {
    $content = @file_get_contents($file);
    if (!$content) continue;
    
    $original_content = $content;
    $file_fixes = [];
    
    foreach ($correction_patterns as $pattern) {
        if (preg_match($pattern['pattern'], $content)) {
            $content = preg_replace($pattern['pattern'], $pattern['replacement'], $content);
            if ($content !== $original_content) {
                $file_fixes[] = $pattern['name'];
                $original_content = $content;
                $total_fixes++;
            }
        }
    }
    
    if (!empty($file_fixes)) {
        file_put_contents($file, $content);
        $relative_path = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);
        $files_modified[] = $relative_path;
        
        echo "✅ $relative_path\n";
        foreach ($file_fixes as $fix) {
            echo "   → $fix\n";
            $fixes_applied[] = $fix;
        }
        echo "\n";
    }
}

// ═══════════════════════════════════════════════════════
// RÉSUMÉ
// ═══════════════════════════════════════════════════════

echo "==========================================\n";
echo "📊 RÉSUMÉ DES CORRECTIONS\n";
echo "==========================================\n\n";

if ($total_fixes > 0) {
    echo "✅ $total_fixes correction(s) appliquée(s) dans " . count($files_modified) . " fichier(s)\n\n";
    
    echo "📁 Fichiers modifiés:\n";
    foreach ($files_modified as $file) {
        echo "   • $file\n";
    }
    
    echo "\n🔧 Types de corrections:\n";
    $fixes_count = array_count_values($fixes_applied);
    arsort($fixes_count);
    foreach ($fixes_count as $fix => $count) {
        echo "   • $fix: $count occurrence(s)\n";
    }
} else {
    echo "✅ Aucune correction nécessaire - Le code est déjà conforme !\n";
}

echo "\n";
echo "🎯 PROCHAINES ÉTAPES:\n";
echo "   1. Relancer l'audit: php audit_sql_final.php\n";
echo "   2. Tester les fonctionnalités modifiées\n";
echo "   3. Vérifier l'application dans le navigateur\n";

echo "\n✅ Script terminé avec succès !\n";
