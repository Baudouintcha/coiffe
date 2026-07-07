<?php

namespace App\Core;

/**
 * View - Moteur de rendu des vues
 *
 * CONCEPT : La vue ne doit jamais contenir de logique métier.
 * Cette classe charge les fichiers de vue en leur injectant
 * des données de façon propre et sécurisée.
 *
 * USAGE depuis un Controller :
 *   View::render('domizi/home', [
 *       'domaines' => $domaines,
 *       'lang'     => 'fr'
 *   ]);
 *
 *   // Avec un layout (header + footer) :
 *   View::render('coiffure/annuaire', $data, 'main');
 */
class View
{
    private static string $viewsPath = '';

    /**
     * Retourne le chemin vers les vues (auto-détecté)
     */
    private static function getViewsPath(): string
    {
        if (empty(self::$viewsPath)) {
            // Remonte jusqu'à la racine du projet
            // Fonctionne que le script soit dans public/ ou à la racine
            $root = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])), '/');

            // Si on est dans public/, remonter d'un niveau
            if (str_ends_with($root, '/public')) {
                $root = dirname($root);
            }

            self::$viewsPath = $root . '/views/';
        }
        return self::$viewsPath;
    }

    /**
     * Rend une vue avec des données optionnelles.
     *
     * @param string      $view    Chemin relatif depuis views/ (ex: 'domizi/home')
     * @param array       $data    Variables injectées dans la vue
     * @param string|null $layout  Nom du layout à utiliser (ex: 'main', 'admin')
     */
    public static function render(
        string $view,
        array $data = [],
        ?string $layout = null
    ): void {
        // Rend les clés du tableau accessibles comme variables dans la vue
        // ['user' => $user] devient $user dans la vue
        extract($data);

        $viewFile = self::getViewsPath() . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "<h1 style='font-family:sans-serif;color:#D4AF37;background:#0a0a0a;padding:40px'>Vue introuvable : {$view}</h1>";
            return;
        }

        if ($layout !== null) {
            ob_start();
            require $viewFile;
            $content = ob_get_clean();

            $layoutFile = self::getViewsPath() . 'layouts/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                echo $content;
            }
        } else {
            require $viewFile;
        }
    }

    /**
     * Retourne le HTML d'une vue sans l'afficher.
     * Utile pour les réponses JSON ou les emails.
     */
    public static function make(string $view, array $data = []): string
    {
        extract($data);
        $viewFile = self::getViewsPath() . $view . '.php';

        if (!file_exists($viewFile)) {
            return '';
        }

        ob_start();
        require $viewFile;
        return ob_get_clean();
    }

    /**
     * Échappe une chaîne pour l'affichage HTML sécurisé.
     * À utiliser systématiquement dans les vues.
     *
     * USAGE dans une vue : <?= View::e($user['nom']) ?>
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
