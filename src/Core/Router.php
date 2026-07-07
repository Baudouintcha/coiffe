<?php

namespace App\Core;

/**
 * Router - Dispatcher des routes HTTP
 *
 * CONCEPT : Le routeur lit l'URL et décide quel Controller
 * doit traiter la requête. C'est l'aiguilleur central.
 *
 * USAGE dans public/index.php :
 *   $router = new Router();
 *   $router->get('/', [HomeController::class, 'index']);
 *   $router->post('/connexion', [AuthController::class, 'login']);
 *   $router->dispatch();
 */
class Router
{
    private array $routes = [];

    /**
     * Enregistre une route GET
     */
    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Enregistre une route POST
     */
    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Analyse l'URL actuelle et appelle le bon Controller
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $this->parseUri();

        // Cherche une correspondance exacte d'abord
        if (isset($this->routes[$method][$uri])) {
            $this->call($this->routes[$method][$uri]);
            return;
        }

        // Cherche une correspondance avec paramètres dynamiques
        // Ex: /prestataire/42 matche /prestataire/{id}
        foreach ($this->routes[$method] ?? [] as $path => $handler) {
            $pattern = $this->pathToRegex($path);
            if (preg_match($pattern, $uri, $matches)) {
                // Enlève le premier match (le match complet)
                array_shift($matches);
                $this->call($handler, $matches);
                return;
            }
        }

        // Aucune route trouvée
        $this->notFound();
    }

    /**
     * Appelle le Controller et la méthode associés
     * Ex: [HomeController::class, 'index'] avec params [42]
     */
    private function call(array $handler, array $params = []): void
    {
        [$class, $method] = $handler;

        if (!class_exists($class)) {
            $this->notFound();
            return;
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            $this->notFound();
            return;
        }

        call_user_func_array([$controller, $method], $params);
    }

    /**
     * Nettoie l'URI : retire la base du projet et le query string
     * Fonctionne que l'entrée soit via public/index.php ou app.php
     */
    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Retire le query string
        $uri = strtok($uri, '?');

        // Cas 1 : accès via /coiffons/public/index.php ou /coiffons/public/xxx
        // → retire tout jusqu'à /public inclus
        if (str_contains($uri, '/public/') || str_ends_with($uri, '/public')) {
            $uri = preg_replace('#^.*/public#', '', $uri);
            if (empty($uri)) $uri = '/';
        }
        // Cas 2 : accès via /coiffons/app.php ou /coiffons/xxx (via .htaccess)
        else {
            // Retire le préfixe /coiffons
            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
                $uri = substr($uri, strlen($scriptDir));
            }

            // Si l'URI commence encore par /app.php → c'est la racine
            $uri = preg_replace('#^/app\.php#', '', $uri);
            if (empty($uri)) $uri = '/';
        }

        $result = '/' . trim($uri, '/');
        return $result === '//' ? '/' : $result;
    }

    /**
     * Convertit un path avec paramètres en regex
     * Ex: /prestataire/{id} → #^/prestataire/([^/]+)$#
     */
    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404');
    }
}
