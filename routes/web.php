<?php

use App\Core\Router;
use App\Controllers\DomiziController;

/**
 * routes/web.php — Toutes les routes de l'application
 *
 * CONCEPT : Toutes les URLs sont définies ici, en un seul endroit.
 * Plus besoin de chercher dans 20 fichiers pour savoir quelle page
 * gère quelle URL.
 *
 * Format : $router->get('/url', [Controller::class, 'methode'])
 *          $router->post('/url', [Controller::class, 'methode'])
 */

// ================================================================
// PORTAIL DOMIZI
// ================================================================

// Page d'accueil principale
$router->get('/', [DomiziController::class, 'index']);

// Changement de langue
// Ex: /lang/en  /lang/fr  /lang/ar
$router->get('/lang/{code}', [DomiziController::class, 'setLang']);

// API : récupérer les métiers d'un domaine (AJAX)
// Ex: /api/metiers/1 → métiers du domaine Beauté
$router->get('/api/metiers/{id}', [DomiziController::class, 'getMetiers']);

// Branche métier : page d'accueil du métier choisi
// Ex: /coiffure → Coiffe Chez Toi
//     /massage  → Massage Chez Toi
$router->get('/{metier}', [DomiziController::class, 'branche']);


// ================================================================
// AUTHENTIFICATION
// (à compléter en Phase 2)
// ================================================================

// $router->get('/connexion',  [AuthController::class, 'showLogin']);
// $router->post('/connexion', [AuthController::class, 'login']);
// $router->get('/inscription',[AuthController::class, 'showRegister']);
// $router->post('/inscription',[AuthController::class, 'register']);
// $router->get('/deconnexion',[AuthController::class, 'logout']);


// ================================================================
// MODULE CLIENT
// (à compléter en Phase 4)
// ================================================================

// $router->get('/annuaire',           [AnnuaireController::class, 'index']);
// $router->get('/prestataire/{id}',   [ProfilController::class, 'show']);
// $router->get('/reserver',           [ReservationController::class, 'show']);
// $router->post('/reserver',          [ReservationController::class, 'store']);
// $router->get('/mes-rendezvous',     [RendezVousController::class, 'index']);


// ================================================================
// MODULE PRESTATAIRE
// (à compléter en Phase 3)
// ================================================================

// $router->get('/mon-catalogue',      [CatalogueController::class, 'index']);
// $router->get('/mon-agenda',         [AgendaController::class, 'index']);
// $router->get('/mon-profil',         [ProfilPrestaController::class, 'edit']);


// ================================================================
// MODULE ADMIN
// (à compléter en Phase 5)
// ================================================================

// $router->get('/admin',              [AdminController::class, 'dashboard']);
// $router->get('/admin/prestataires', [ValidationController::class, 'index']);
// $router->get('/admin/plaintes',     [ModerationController::class, 'index']);
