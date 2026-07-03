<?php

namespace App\Core;

/**
 * Lang - Gestionnaire de traductions multilingues
 *
 * CONCEPT : Toutes les chaînes de texte affichées sont stockées
 * dans des fichiers de langue. Selon la langue choisie en session,
 * le système charge le bon fichier et retourne la bonne traduction.
 *
 * Langues supportées : fr (défaut), en, es, ar
 *
 * USAGE dans les vues :
 *   <?= Lang::t('domizi.tagline') ?>
 *   <?= Lang::t('common.you_are_here') ?>
 */
class Lang
{
    private static array $translations = [];
    private static string $currentLang = 'fr';
    private static string $langPath    = __DIR__ . '/../../lang/';

    /**
     * Initialise la langue depuis la session
     */
    public static function init(): void
    {
        self::$currentLang = Session::getLang();
        self::load(self::$currentLang);
    }

    /**
     * Retourne la traduction d'une clé
     *
     * @param string $key     Format : 'fichier.cle' (ex: 'domizi.tagline')
     * @param array  $replace Remplacement de variables Ex: [':name' => 'Jean']
     */
    public static function t(string $key, array $replace = []): string
    {
        $parts = explode('.', $key, 2);
        $file  = $parts[0];
        $k     = $parts[1] ?? '';

        // Charge le fichier si pas encore en mémoire
        if (!isset(self::$translations[$file])) {
            self::load(self::$currentLang, $file);
        }

        $text = self::$translations[$file][$k]
             ?? self::$translations['fr'][$file][$k]
             ?? $key; // Fallback : retourne la clé si traduction manquante

        // Remplace les variables dans la traduction
        foreach ($replace as $search => $value) {
            $text = str_replace($search, $value, $text);
        }

        return $text;
    }

    /**
     * Retourne la langue courante
     */
    public static function current(): string
    {
        return self::$currentLang;
    }

    /**
     * Vérifie si la langue courante est RTL (arabe, hébreu...)
     */
    public static function isRtl(): bool
    {
        return in_array(self::$currentLang, ['ar']);
    }

    /**
     * Charge un fichier de langue en mémoire
     */
    private static function load(string $lang, ?string $file = null): void
    {
        if ($file !== null) {
            $path = self::$langPath . $lang . '/' . $file . '.php';

            if (file_exists($path)) {
                self::$translations[$file] = require $path;
            } elseif ($lang !== 'fr') {
                // Fallback vers le français si la traduction n'existe pas
                $fallback = self::$langPath . 'fr/' . $file . '.php';
                if (file_exists($fallback)) {
                    self::$translations[$file] = require $fallback;
                }
            }
        }
    }
}
