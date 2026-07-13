<?php

/**
 * security/csrf.php — Protection CSRF centralisée
 *
 * CONCEPT : CSRF (Cross-Site Request Forgery) = une page externe
 * force un utilisateur connecté à exécuter une action à son insu.
 * Le token CSRF prouve que le formulaire vient BIEN de notre site.
 *
 * USAGE :
 *   // Dans un formulaire HTML :
 *   <?php echo csrf_field(); ?>
 *
 *   // Dans le traitement PHP :
 *   csrf_verify();
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Génère un token CSRF unique par session.
 * Crée le token s'il n'existe pas encore.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Retourne le champ hidden HTML avec le token CSRF.
 * À insérer dans chaque formulaire POST.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Vérifie que le token CSRF soumis est valide.
 * Arrête l'exécution si invalide (attaque détectée).
 */
function csrf_verify(): void
{
    $token_soumis = $_POST['_csrf_token'] ?? '';
    $token_session = $_SESSION['_csrf_token'] ?? '';

    if (empty($token_soumis) || !hash_equals($token_session, $token_soumis)) {
        http_response_code(403);
        die('Requête invalide. Veuillez réessayer.');
    }

    // Régénère le token après utilisation (one-time token)
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}
