<?php

namespace App\Core;

/**
 * Session - Gestion centralisée de la session PHP
 *
 * CONCEPT : Plutôt que d'appeler session_start() partout
 * et d'accéder à $_SESSION directement, on centralise ici.
 * Une seule classe responsable des données de session.
 *
 * USAGE :
 *   Session::set('user_id', 42);
 *   Session::get('user_id');       // 42
 *   Session::has('user_id');       // true
 *   Session::delete('user_id');
 *   Session::flush();              // vide tout
 */
class Session
{
    /**
     * Démarre la session si elle n'est pas déjà active.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function delete(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Vide toute la session (déconnexion)
     */
    public static function flush(): void
    {
        self::start();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Message flash : affiché une seule fois puis supprimé
     * Utile pour les messages de succès/erreur après une action
     */
    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            // Écriture
            self::set('_flash_' . $key, $value);
            return null;
        }

        // Lecture et suppression immédiate
        $msg = self::get('_flash_' . $key);
        self::delete('_flash_' . $key);
        return $msg;
    }

    /**
     * Retourne la langue active de la session
     * Langues supportées : fr, en, es, ar
     */
    public static function getLang(): string
    {
        return self::get('lang', 'fr');
    }

    public static function setLang(string $lang): void
    {
        $supported = ['fr', 'en', 'es', 'ar'];
        if (in_array($lang, $supported)) {
            self::set('lang', $lang);
        }
    }
}
