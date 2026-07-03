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
    private static string $viewsPath = __DIR__ . '/../../views/';

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

        $viewFile = self::$viewsPath . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "<h1>Vue introuvable : {$view}</h1>";
            return;
        }

        if ($layout !== null) {
            // Capture le contenu de la vue
            ob_start();
            require $viewFile;
            $content = ob_get_clean();

            // Injecte le contenu dans le layout
            $layoutFile = self::$viewsPath . 'layouts/' . $layout . '.php';
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
        $viewFile = self::$viewsPath . $view . '.php';

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
