<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Lang;

/**
 * DomiziController - Gère la page portail principale de DOMIZI
 *
 * Responsabilités :
 * - Afficher la page d'accueil DOMIZI (choix du domaine/métier)
 * - Gérer le changement de langue
 * - Fournir les domaines et métiers en JSON pour l'interface dynamique
 * - Rediriger vers la branche métier choisie
 */
class DomiziController extends BaseController
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * GET / — Page portail principale
     * Affiche DOMIZI avec tous les domaines actifs
     */
    public function index(): void
    {
        // Récupère tous les domaines actifs depuis la BDD
        $stmt = $this->pdo->query("
            SELECT d.*, COUNT(m.id) as nb_metiers
            FROM domaines d
            LEFT JOIN metiers m ON m.id_domaine = d.id AND m.actif = 1
            WHERE d.actif = 1
            GROUP BY d.id
            ORDER BY d.id ASC
        ");
        $domaines = $stmt->fetchAll();

        // Pas de layout ici — la page DOMIZI a son propre design complet
        $this->renderRaw('domizi/home', [
            'domaines'    => $domaines,
            'pageTitle'   => 'Domizi',
        ]);
    }

    /**
     * GET /lang/{code} — Change la langue et redirige
     * Ex: /lang/en → change en anglais → retour à la page précédente
     */
    public function setLang(string $lang): void
    {
        Session::setLang($lang);

        // Redirige vers la page précédente ou l'accueil
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    /**
     * GET /api/metiers/{id_domaine} — Retourne les métiers d'un domaine en JSON
     * Appelé en AJAX quand l'utilisateur clique sur un domaine
     */
    public function getMetiers(string $idDomaine): void
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nom, slug, icone, description
            FROM metiers
            WHERE id_domaine = ? AND actif = 1
            ORDER BY nom ASC
        ");
        $stmt->execute([(int) $idDomaine]);
        $metiers = $stmt->fetchAll();

        $this->json([
            'success' => true,
            'metiers' => $metiers,
        ]);
    }

    /**
     * GET /{slug_metier} — Redirige vers la branche du métier choisi
     * Ex: /coiffure → page d'accueil Coiffe Chez Toi
     *     /massage  → page d'accueil Massage Chez Toi
     *
     * Demande ensuite : "Je suis client" ou "Je suis prestataire"
     */
    public function branche(string $slugMetier): void
    {
        // Vérifie que le métier existe
        $stmt = $this->pdo->prepare("
            SELECT m.*, d.nom as domaine_nom, d.slug as domaine_slug
            FROM metiers m
            JOIN domaines d ON m.id_domaine = d.id
            WHERE m.slug = ? AND m.actif = 1
        ");
        $stmt->execute([$slugMetier]);
        $metier = $stmt->fetch();

        if (!$metier) {
            http_response_code(404);
            $this->renderRaw('errors/404');
            return;
        }

        // Mémorise le métier choisi en session pour tout le parcours
        Session::set('metier_actif', $metier);

        // Affiche la page de choix de rôle (client ou prestataire)
        $this->renderRaw('domizi/choix_role', [
            'metier'    => $metier,
            'pageTitle' => $metier['nom'],
        ]);
    }
}
