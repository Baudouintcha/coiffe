<?php
/**
 * access/search_coiffeurs.php — Endpoint AJAX autocomplete coiffeurs
 * Design System v2.0 | Annuaire Premium
 *
 * GET ?q= → retourne JSON max 8 coiffeurs approuvés correspondant au fragment
 * Recherche insensible aux accents, nom + prénom
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$q = trim($_GET['q'] ?? '');

// Sécurité : requête minimale de 2 caractères
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $fragment = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT u.id, u.nom, u.prenom, u.photo_profil, v.nom_ville
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        WHERE u.role = 'coiffeur'
          AND u.is_approved = 1
          AND (u.abonnement_status = 1 OR LOWER(u.abonnement_status) = 'actif')
          AND (u.nom LIKE ? OR u.prenom LIKE ? OR CONCAT(u.prenom, ' ', u.nom) LIKE ? OR CONCAT(u.nom, ' ', u.prenom) LIKE ?)
        ORDER BY u.nom ASC
        LIMIT 8
    ");
    $stmt->execute([$fragment, $fragment, $fragment, $fragment]);
    $results = $stmt->fetchAll();

    $output = [];
    foreach ($results as $r) {
        $photo_url = null;
        if (!empty($r['photo_profil'])) {
            $path = __DIR__ . '/' . $r['photo_profil'];
            if (file_exists($path)) {
                $photo_url = '/coiffons/access/' . $r['photo_profil'];
            }
        }
        $output[] = [
            'id'        => (int) $r['id'],
            'nom'       => $r['nom'],
            'prenom'    => $r['prenom'],
            'photo_url' => $photo_url,
            'ville'     => $r['nom_ville'] ?? '',
        ];
    }

    echo json_encode($output, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([]);
}
exit();
