<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Session;
use App\Core\Lang;

/**
 * BaseController - Classe parente de tous les Controllers
 *
 * CONCEPT : Tous les controllers héritent de cette classe.
 * Elle fournit les méthodes communes dont ils ont tous besoin :
 * rendre une vue, rediriger, vérifier l'auth, etc.
 *
 * Un controller enfant fait :
 *   class HomeController extends BaseController { ... }
 */
abstract class BaseController
{
    /**
     * Rend une vue avec layout et données.
     * Injecte automatiquement la langue et le user connecté.
     */
    protected function render(
        string $view,
        array $data = [],
        string $layout = 'main'
    ): void {
        // Données disponibles dans TOUTES les vues automatiquement
        $globalData = [
            'lang'          => Lang::current(),
            'isRtl'         => Lang::isRtl(),
            'currentUser'   => Session::get('user'),
            'role'          => Session::get('role', 'invite'),
            'flashSuccess'  => Session::flash('success'),
            'flashError'    => Session::flash('error'),
        ];

        View::render($view, array_merge($globalData, $data), $layout);
    }

    /**
     * Rend une vue SANS layout (pas de header/footer)
     * Utile pour la page portail DOMIZI qui a son propre design
     */
    protected function renderRaw(string $view, array $data = []): void
    {
        $globalData = [
            'lang'  => Lang::current(),
            'isRtl' => Lang::isRtl(),
        ];

        View::render($view, array_merge($globalData, $data));
    }

    /**
     * Redirige vers une URL
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit();
    }

    /**
     * Retourne une réponse JSON
     * Utile pour les appels AJAX (chargement des métiers, quartiers...)
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Vérifie que l'utilisateur est connecté
     * Sinon redirige vers la connexion
     */
    protected function requireAuth(): void
    {
        if (!Session::has('user')) {
            $this->redirect('/connexion');
        }
    }

    /**
     * Vérifie que l'utilisateur a le bon rôle
     */
    protected function requireRole(string $role): void
    {
        $this->requireAuth();

        if (Session::get('role') !== $role) {
            $this->redirect('/');
        }
    }
}
