# 🌐 Plan d'Action d'Implémentation du Système de Langue

**Objectif**: Activer le support multilingue complet (FR, EN, ES, AR) sur l'ensemble du site web  
**Statut Actuel**: 5% implémenté (portail Domizi seul)  
**Statut Cible**: 100% implémenté  
**Estimation d'Effort**: 2-3 jours

---

## Phase 1 : Créer les Fichiers de Traduction (Priorité: CRITIQUE)

### 1.1 Traductions Communes (`/lang/*/common.php`)

Créer ces fichiers avec les éléments UI partagés:

**`/lang/fr/common.php`**
```php
<?php
return [
    // Navigation
    'home'                  => 'Accueil',
    'my_profile'            => 'Mon profil',
    'edit_profile'          => 'Modifier mon profil',
    'logout'                => 'Se déconnecter',
    'search'                => 'Rechercher',
    'search_coiffeur'       => 'Rechercher un coiffeur',
    'my_appointments'       => 'Mes rendez-vous',
    'directory'             => 'Annuaire',
    
    // Boutons courants
    'back'                  => 'Retour',
    'next'                  => 'Suivant',
    'confirm'               => 'Confirmer',
    'cancel'                => 'Annuler',
    'save'                  => 'Enregistrer',
    'delete'                => 'Supprimer',
    'edit'                  => 'Modifier',
    'close'                 => 'Fermer',
    'submit'                => 'Soumettre',
    'loading'               => 'Chargement...',
    
    // Messages courants
    'success'               => 'Succès',
    'error'                 => 'Erreur',
    'warning'               => 'Attention',
    'info'                  => 'Information',
    'confirm_delete'        => 'Êtes-vous sûr(e) de vouloir supprimer ?',
    'operation_success'     => 'Opération réussie',
    'operation_failed'      => 'L\'opération a échoué',
    
    // Formulaires
    'email'                 => 'Email',
    'password'              => 'Mot de passe',
    'phone'                 => 'Téléphone',
    'city'                  => 'Ville',
    'neighborhood'          => 'Quartier',
    'address'               => 'Adresse',
    'name'                  => 'Nom',
    'first_name'            => 'Prénom',
    
    // Statut
    'active'                => 'Actif',
    'inactive'              => 'Inactif',
    'pending'               => 'En attente',
    'completed'             => 'Terminé',
    'cancelled'             => 'Annulé',
];
```

**`/lang/en/common.php`**
```php
<?php
return [
    // Navigation
    'home'                  => 'Home',
    'my_profile'            => 'My Profile',
    'edit_profile'          => 'Edit Profile',
    'logout'                => 'Logout',
    'search'                => 'Search',
    'search_coiffeur'       => 'Search Hairdresser',
    'my_appointments'       => 'My Appointments',
    'directory'             => 'Directory',
    
    // Common buttons
    'back'                  => 'Back',
    'next'                  => 'Next',
    'confirm'               => 'Confirm',
    'cancel'                => 'Cancel',
    'save'                  => 'Save',
    'delete'                => 'Delete',
    'edit'                  => 'Edit',
    'close'                 => 'Close',
    'submit'                => 'Submit',
    'loading'               => 'Loading...',
    
    // Common messages
    'success'               => 'Success',
    'error'                 => 'Error',
    'warning'               => 'Warning',
    'info'                  => 'Information',
    'confirm_delete'        => 'Are you sure you want to delete?',
    'operation_success'     => 'Operation successful',
    'operation_failed'      => 'Operation failed',
    
    // Forms
    'email'                 => 'Email',
    'password'              => 'Password',
    'phone'                 => 'Phone',
    'city'                  => 'City',
    'neighborhood'          => 'Neighborhood',
    'address'               => 'Address',
    'name'                  => 'Name',
    'first_name'            => 'First Name',
    
    // Status
    'active'                => 'Active',
    'inactive'              => 'Inactive',
    'pending'               => 'Pending',
    'completed'             => 'Completed',
    'cancelled'             => 'Cancelled',
];
```

**`/lang/es/common.php`** et **`/lang/ar/common.php`** - Même structure avec traductions

### 1.2 Traductions des Pages Client (`/lang/*/client.php`)

**`/lang/fr/client.php`**
```php
<?php
return [
    // Tableau de bord
    'welcome'               => 'Bienvenue',
    'my_appointments'       => 'Mes rendez-vous',
    'no_appointments'       => 'Vous n\'avez aucun rendez-vous',
    'create_appointment'    => 'Créer un rendez-vous',
    'book_service'          => 'Réserver un service',
    
    // Rendez-vous
    'appointment_date'      => 'Date du rendez-vous',
    'appointment_time'      => 'Heure du rendez-vous',
    'appointment_status'    => 'Statut du rendez-vous',
    'appointment_price'     => 'Prix du rendez-vous',
    'duration'              => 'Durée',
    'minutes'               => 'minutes',
    
    // Profil
    'my_profile'            => 'Mon profil',
    'my_balance'            => 'Mon solde',
    'available_balance'     => 'Solde disponible',
    'recharge_account'      => 'Recharger mon compte',
    'account_status'        => 'Statut du compte',
    'account_verified'      => 'Compte vérifié',
    
    // Infos coiffeur
    'hairdresser_name'      => 'Nom du coiffeur',
    'hairdresser_rating'    => 'Note du coiffeur',
    'view_profile'          => 'Voir le profil',
    'contact_hairdresser'   => 'Contacter le coiffeur',
    
    // Messages
    'appointment_confirmed' => 'Rendez-vous confirmé',
    'appointment_cancelled' => 'Rendez-vous annulé',
    'insufficient_balance'  => 'Solde insuffisant',
];
```

**`/lang/en/client.php`** - Même structure avec traductions anglaises

### 1.3 Traductions des Pages Coiffeur (`/lang/*/coiffeur.php`)

**`/lang/fr/coiffeur.php`**
```php
<?php
return [
    // Tableau de bord
    'welcome'               => 'Bienvenue',
    'my_schedule'           => 'Mon agenda',
    'pending_requests'      => 'Demandes en attente',
    'accepted_appointments' => 'Rendez-vous acceptés',
    
    // Agenda
    'working_hours'         => 'Heures de travail',
    'set_schedule'          => 'Définir mon agenda',
    'monday'                => 'Lundi',
    'tuesday'               => 'Mardi',
    'wednesday'             => 'Mercredi',
    'thursday'              => 'Jeudi',
    'friday'                => 'Vendredi',
    'saturday'              => 'Samedi',
    'sunday'                => 'Dimanche',
    'start_time'            => 'Heure de début',
    'end_time'              => 'Heure de fin',
    'closed_today'          => 'Fermé aujourd\'hui',
    
    // Services
    'my_services'           => 'Mes services',
    'add_service'           => 'Ajouter un service',
    'service_name'          => 'Nom du service',
    'service_price'         => 'Prix du service',
    'service_duration'      => 'Durée du service',
    
    // Gains
    'my_earnings'           => 'Mes gains',
    'total_earnings'        => 'Total des gains',
    'monthly_earnings'      => 'Gains du mois',
    'secured_earnings'      => 'Gains sécurisés',
    
    // Zones
    'my_zones'              => 'Mes zones',
    'zones_covered'         => 'Zones couvertes',
    'modify_zones'          => 'Modifier mes zones',
    
    // Avis
    'ratings'               => 'Avis',
    'average_rating'        => 'Note moyenne',
    'total_reviews'         => 'Nombre d\'avis',
    'view_reviews'          => 'Voir les avis',
];
```

**`/lang/en/coiffeur.php`** - Même structure avec traductions anglaises

---

## Phase 2 : Mettre à Jour les Fichiers Essentiels pour Supporter Plusieurs Langues

### 2.1 Mettre à Jour `navbar_client.php` - Ajouter le Sélecteur de Langue

Ajouter ce code au composant barre de navigation:

```php
<!-- Sélecteur de Langue -->
<div class="lang-switcher-compact">
    <button class="lang-toggle-compact" onclick="toggleLangDropdown(event)" title="<?= $_SESSION['lang'] === 'en' ? 'Change Language' : 'Changer la langue' ?>">
        <i class="bi bi-globe2"></i>
        <span><?= strtoupper(Session::getLang()) ?></span>
        <i class="bi bi-chevron-down"></i>
    </button>
    <div class="lang-dropdown-compact" id="langDropdownCompact">
        <?php
        $langs = ['fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'ar' => 'العربية'];
        foreach ($langs as $code => $label):
        ?>
            <a href="?lang=<?= $code ?>"
               class="lang-option-compact <?= Session::getLang() === $code ? 'active' : '' ?>">
                <span class="lang-code"><?= strtoupper($code) ?></span>
                <span class="lang-label"><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
```

Ajouter CSS à style.css ou components.css:

```css
.lang-switcher-compact {
    position: relative;
}

.lang-toggle-compact {
    display: flex;
    align-items: center;
    gap: 4px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.6);
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}

.lang-toggle-compact:hover {
    background: var(--gold-dim);
    border-color: var(--gold);
    color: var(--gold);
}

.lang-dropdown-compact {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    background: #111;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    overflow: hidden;
    min-width: 140px;
    display: none;
    box-shadow: 0 8px 30px rgba(0,0,0,0.6);
    z-index: 1000;
}

.lang-dropdown-compact.open {
    display: block;
}

.lang-option-compact {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    text-decoration: none;
    color: rgba(255,255,255,0.6);
    font-size: 0.75rem;
    transition: all 0.15s;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}

.lang-option-compact:last-child {
    border-bottom: none;
}

.lang-option-compact:hover {
    background: rgba(255,255,255,0.04);
    color: #fff;
}

.lang-option-compact.active {
    color: var(--gold);
}

.lang-code {
    font-weight: 700;
    min-width: 20px;
    font-size: 0.7rem;
}
```

Ajouter JavaScript:

```javascript
function toggleLangDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('langDropdownCompact');
    if (dropdown) {
        dropdown.classList.toggle('open');
    }
}

// Fermer au clic hors de la zone
document.addEventListener('click', function() {
    const dropdown = document.getElementById('langDropdownCompact');
    if (dropdown) {
        dropdown.classList.remove('open');
    }
});
```

---

## Phase 3 : Mettre à Jour les Pages pour Utiliser Lang::t()

### 3.1 Mettre à Jour les Pages Client

**Exemple : `client/mes_rendezvous.php`**

Remplacer le texte codé en dur:

```php
// AVANT
<h1>Mes rendez-vous</h1>
<p>Vous n'avez aucun rendez-vous</p>

// APRÈS
<?php use App\Core\Lang; ?>
<h1><?= Lang::t('client.my_appointments') ?></h1>
<p><?= Lang::t('client.no_appointments') ?></p>
```

**Exemple : `client/catalogue.php`**

```php
// AVANT
<button>Réserver maintenant</button>

// APRÈS
<button><?= Lang::t('client.book_now') ?></button>
```

### 3.2 Mettre à Jour les Pages Coiffeur

**Exemple : `coiffeurs/agenda_coiffeurs.php`**

```php
// AVANT
<h2>MON AGENDA</h2>
<label>Lundi</label>

// APRÈS
<?php use App\Core\Lang; ?>
<h2><?= Lang::t('coiffeur.my_schedule') ?></h2>
<label><?= Lang::t('coiffeur.monday') ?></label>
```

---

## Phase 4 : Créer un Composant Sélecteur de Langue Réutilisable (Optionnel)

Créer un composant réutilisable:

**`views/components/lang_switcher.php`**

```php
<?php
use App\Core\Session;

$current_lang = Session::getLang();
$langs = ['fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'ar' => 'العربية'];
?>

<div class="lang-switcher-inline">
    <button class="lang-toggle" onclick="toggleLang(event)">
        <i class="bi bi-globe2"></i>
        <span><?= strtoupper($current_lang) ?></span>
    </button>
    <div class="lang-dropdown" id="langDropdown">
        <?php foreach ($langs as $code => $label): ?>
            <a href="?lang=<?= $code ?>" class="lang-option <?= $current_lang === $code ? 'active' : '' ?>">
                <span class="lang-code"><?= strtoupper($code) ?></span>
                <span class="lang-label"><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleLang(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('langDropdown');
    dropdown.classList.toggle('open');
}

document.addEventListener('click', () => {
    document.getElementById('langDropdown')?.classList.remove('open');
});
</script>

<style>
.lang-switcher-inline { position: relative; }
.lang-toggle { /* ... styles ... */ }
.lang-dropdown { /* ... styles ... */ }
</style>
```

Ensuite inclure dans la barre de navigation:

```php
<?php include __DIR__ . '/lang_switcher.php'; ?>
```

---

## Phase 5 : Stratégie de Test

### 5.1 Liste de Vérification de Test

**Tests du Portail Domizi**
- [ ] Sélecteur de langue visible sur `/coiffons/app.php`
- [ ] Peut basculer FR → EN → ES → AR → FR
- [ ] Tout le texte du portail se met à jour correctement
- [ ] La langue persiste après rechargement de page
- [ ] URL directe `?lang=en` fonctionne

**Tests des Pages Client**
- [ ] Sélecteur de langue visible dans la barre de navigation
- [ ] Peut changer de langue sur le tableau de bord
- [ ] Tout le texte se met à jour correctement (tableau de bord, rendez-vous, profil)
- [ ] La langue persiste après navigation
- [ ] Fonctionne sur vue mobile

**Tests des Pages Coiffeur**
- [ ] Sélecteur de langue visible
- [ ] Agenda s'affiche dans la bonne langue
- [ ] Toutes les étiquettes se traduisent correctement
- [ ] Le mode RTL fonctionne pour l'arabe

**Tests des Fichiers de Langue**
- [ ] Les 4 fichiers de langue présents
- [ ] Toutes les clés ont des traductions
- [ ] Pas de traductions manquantes (fallback au français)
- [ ] Les caractères spéciaux s'affichent correctement (é, ñ, ع, etc.)

### 5.2 Commandes de Test

```bash
# Vérifier l'existence des fichiers de langue
ls -la /lang/*/common.php
ls -la /lang/*/client.php
ls -la /lang/*/coiffeur.php

# Tester le changement de langue via URL
curl "http://localhost/coiffons/app.php?lang=en"

# Vérifier que la Session est définie correctement
# (Peut ajouter le débogage en PHP: Session::getLang() devrait retourner 'en')
```

---

## Priorité d'Implémentation

### Doit Avoir (Critique)
1. ✅ Créer fichiers `/lang/*/common.php`
2. ✅ Créer fichiers `/lang/*/client.php`
3. ✅ Ajouter sélecteur de langue à la barre de navigation
4. ✅ Mettre à jour les pages client pour utiliser `Lang::t()`

### Devrait Avoir (Important)
5. Créer fichiers `/lang/*/coiffeur.php`
6. Mettre à jour les pages coiffeur pour utiliser `Lang::t()`
7. Créer composant sélecteur de langue
8. Ajouter des tests complets

### Agréable à Avoir (Amélioration)
9. Améliorations de mise en page RTL pour l'arabe
10. Formats de date/heure spécifiques à la langue
11. Étiquettes de formulaires de droite à gauche
12. Optimisation des performances (cache des traductions)

---

## Chronologie Estimée

| Phase | Tâche | Temps |
|-------|-------|-------|
| 1 | Créer fichiers de traduction | 4 heures |
| 2 | Mettre à jour barre de navigation avec sélecteur | 1 heure |
| 3 | Mettre à jour pages pour utiliser Lang::t() | 6 heures |
| 4 | Créer composant | 1 heure |
| 5 | Tests et correctifs | 3 heures |
| **Total** | | **~15 heures** |

---

## Liste de Contrôle des Fichiers

### À Créer
- [ ] `/lang/fr/common.php`
- [ ] `/lang/en/common.php`
- [ ] `/lang/es/common.php`
- [ ] `/lang/ar/common.php`
- [ ] `/lang/fr/client.php`
- [ ] `/lang/en/client.php`
- [ ] `/lang/es/client.php`
- [ ] `/lang/ar/client.php`
- [ ] `/lang/fr/coiffeur.php`
- [ ] `/lang/en/coiffeur.php`
- [ ] `/lang/es/coiffeur.php`
- [ ] `/lang/ar/coiffeur.php`
- [ ] `/views/components/lang_switcher.php` (optionnel)

### À Modifier
- [ ] `views/components/navbar_client.php` - Ajouter sélecteur lang
- [ ] `client/mes_rendezvous.php` - Utiliser Lang::t()
- [ ] `client/creer_rendezvous.php` - Utiliser Lang::t()
- [ ] `client/reserver.php` - Utiliser Lang::t()
- [ ] `client/catalogue.php` - Utiliser Lang::t()
- [ ] `profil.php` - Utiliser Lang::t()
- [ ] `coiffeurs/agenda_coiffeurs.php` - Utiliser Lang::t()
- [ ] `coiffeurs/profil_coiffeurs.php` - Utiliser Lang::t()
- [ ] `coiffeurs/mes_zones.php` - Utiliser Lang::t()
- [ ] `coiffeurs/profil_public.php` - Utiliser Lang::t()
- [ ] `css/style.css` ou `css/components.css` - Ajouter styles sélecteur

---

---

## Phase 6 : Intégration Google Translate API pour Contenu Utilisateur (Futur)

### 6.1 Installation et Configuration

#### Étape 1 : Installer la bibliothèque Google Translate

```bash
composer require google/cloud-translate
```

#### Étape 2 : Configurer les Clés API

**Créer un fichier de configuration** : `config/translation.php`

```php
<?php
return [
    'google_translate' => [
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
        'project_id' => env('GOOGLE_TRANSLATE_PROJECT_ID'),
        'cache_translations' => true, // Cacher les traductions traduites
        'cache_duration' => 86400 * 30, // 30 jours
    ]
];
```

**Ajouter les variables d'environnement** : `.env`

```
GOOGLE_TRANSLATE_API_KEY=votre_clé_api_ici
GOOGLE_TRANSLATE_PROJECT_ID=votre_project_id_ici
```

### 6.2 Créer une Classe de Service de Traduction

**Créer le fichier** : `src/Services/TranslationService.php`

```php
<?php

namespace App\Services;

use Google\Cloud\Translate\TranslateClient;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    private $translateClient;
    private $apiKey;
    private $cacheEnabled;
    private $cacheDuration;

    public function __construct()
    {
        $this->apiKey = config('translation.google_translate.api_key');
        $this->cacheEnabled = config('translation.google_translate.cache_translations', true);
        $this->cacheDuration = config('translation.google_translate.cache_duration', 86400);

        $this->translateClient = new TranslateClient([
            'key' => $this->apiKey
        ]);
    }

    /**
     * Traduire un texte d'une langue à une autre
     *
     * @param string $text Texte à traduire
     * @param string $targetLang Langue cible (en, es, ar, etc.)
     * @param string $sourceLang Langue source (par défaut: fr)
     * @return string Texte traduit
     */
    public function translate($text, $targetLang, $sourceLang = 'fr')
    {
        // Si déjà dans la langue cible, retourner le texte original
        if ($sourceLang === $targetLang) {
            return $text;
        }

        // Générer une clé de cache unique
        $cacheKey = 'translation_' . md5($text . $sourceLang . $targetLang);

        // Vérifier le cache
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Appeler l'API Google Translate
            $result = $this->translateClient->translate($text, [
                'targetLanguage' => $targetLang,
                'sourceLanguage' => $sourceLang,
            ]);

            $translatedText = $result['translatedText'];

            // Stocker en cache
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $translatedText, $this->cacheDuration);
            }

            return $translatedText;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner le texte original
            \Log::error('Translation error: ' . $e->getMessage());
            return $text;
        }
    }

    /**
     * Traduire plusieurs textes à la fois
     *
     * @param array $texts Tableau de textes à traduire
     * @param string $targetLang Langue cible
     * @param string $sourceLang Langue source
     * @return array Textes traduits
     */
    public function translateMultiple(array $texts, $targetLang, $sourceLang = 'fr')
    {
        $translated = [];
        foreach ($texts as $key => $text) {
            $translated[$key] = $this->translate($text, $targetLang, $sourceLang);
        }
        return $translated;
    }

    /**
     * Traduire un objet (ex: service coiffeur)
     *
     * @param object $object Objet avec propriétés à traduire
     * @param array $fields Champs à traduire
     * @param string $targetLang Langue cible
     * @return object Objet avec traductions
     */
    public function translateObject($object, $fields, $targetLang)
    {
        foreach ($fields as $field) {
            if (isset($object->$field)) {
                $object->{$field . '_translated'} = $this->translate(
                    $object->$field,
                    $targetLang
                );
            }
        }
        return $object;
    }
}
```

### 6.3 Utilisation dans les Pages

#### Exemple 1 : Traduire une Description de Service

**Dans `client/catalogue.php`** :

```php
<?php
use App\Services\TranslationService;
use App\Core\Session;

$translationService = new TranslationService();
$currentLang = Session::getLang();

// Récupérer les services du coiffeur
$services = $coiffeur->services;

foreach ($services as $service) {
    // Si pas en français, traduire la description
    if ($currentLang !== 'fr') {
        $service->description_translated = $translationService->translate(
            $service->description,
            $currentLang,
            'fr'
        );
    } else {
        $service->description_translated = $service->description;
    }
}
?>

<div class="services-list">
    <?php foreach ($services as $service): ?>
        <div class="service-card">
            <h3><?= htmlspecialchars($service->nom) ?></h3>
            <p><?= htmlspecialchars($service->description_translated) ?></p>
            <span class="price"><?= $service->prix ?> €</span>
        </div>
    <?php endforeach; ?>
</div>
```

#### Exemple 2 : Traduire une Biographie de Coiffeur

**Dans `coiffeurs/profil_public.php`** :

```php
<?php
use App\Services\TranslationService;
use App\Core\Session;

$translationService = new TranslationService();
$currentLang = Session::getLang();

// Traduire la biographie si nécessaire
$bioTranslated = ($currentLang !== 'fr')
    ? $translationService->translate($coiffeur->bio, $currentLang, 'fr')
    : $coiffeur->bio;
?>

<div class="coiffeur-profile">
    <h1><?= htmlspecialchars($coiffeur->nom) ?></h1>
    <p class="bio"><?= htmlspecialchars($bioTranslated) ?></p>
</div>
```

#### Exemple 3 : Traduire les Avis des Clients

**Dans `coiffeurs/mes_avis.php`** :

```php
<?php
use App\Services\TranslationService;
use App\Core\Session;

$translationService = new TranslationService();
$currentLang = Session::getLang();

$avis = $coiffeur->avis;

foreach ($avis as $avis_item) {
    $avis_item->texte_translated = ($currentLang !== 'fr')
        ? $translationService->translate($avis_item->texte, $currentLang, 'fr')
        : $avis_item->texte;
}
?>

<div class="reviews">
    <?php foreach ($avis as $review): ?>
        <div class="review-card">
            <p class="review-text"><?= htmlspecialchars($review->texte_translated) ?></p>
            <span class="rating">★ <?= $review->note ?>/5</span>
        </div>
    <?php endforeach; ?>
</div>
```

### 6.4 Helper Function pour Simplicité

**Créer un helper** : `app/Helpers/TranslationHelper.php`

```php
<?php

namespace App\Helpers;

use App\Services\TranslationService;
use App\Core\Session;

class TranslationHelper
{
    private static $service;

    public static function getService()
    {
        if (!self::$service) {
            self::$service = new TranslationService();
        }
        return self::$service;
    }

    /**
     * Traduire un texte utilisant la langue de session actuelle
     *
     * @param string $text Texte à traduire
     * @return string Texte traduit
     */
    public static function translate($text)
    {
        $currentLang = Session::getLang();
        if ($currentLang === 'fr') {
            return $text;
        }
        return self::getService()->translate($text, $currentLang, 'fr');
    }

    /**
     * Alias court pour traduction
     */
    public static function t($text)
    {
        return self::translate($text);
    }
}
```

**Utilisation simplifiée dans les pages:**

```php
<?php
use App\Helpers\TranslationHelper;
?>

<p><?= htmlspecialchars(TranslationHelper::t($service->description)) ?></p>
<!-- Ou encore plus court: -->
<p><?= htmlspecialchars(TranslationHelper::t($coiffeur->bio)) ?></p>
```

### 6.5 Gestion du Cache

**Créer une commande pour nettoyer le cache** : `app/Console/Commands/ClearTranslationCache.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearTranslationCache extends Command
{
    protected $signature = 'translation:clear-cache';
    protected $description = 'Nettoyer le cache de traductions';

    public function handle()
    {
        // Chercher toutes les clés commençant par 'translation_'
        // Note: Cette approche dépend de votre driver de cache
        $this->info('Cache de traductions nettoyé');
    }
}
```

### 6.6 Base de Données - Structure pour Cacher les Traductions

**Optionnel : Créer une table pour stocker les traductions mises en cache**

```sql
CREATE TABLE translation_cache (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source_text LONGTEXT NOT NULL,
    source_lang VARCHAR(10) DEFAULT 'fr',
    target_lang VARCHAR(10) NOT NULL,
    translated_text LONGTEXT NOT NULL,
    hash VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT NULL,
    
    INDEX idx_hash (hash),
    INDEX idx_target_lang (target_lang)
);
```

### 6.7 Coûts et Considérations

**Tarification Google Translate API :**
- ~$5-20 pour 1 million de caractères
- Gratuit jusqu'à 500,000 caractères/mois (selon l'année)
- Cache = économies importantes

**Recommandations :**
1. Activer le cache pour éviter les appels redondants
2. Traduire uniquement quand l'utilisateur change de langue
3. Monitorer les appels API pour éviter les dépassements
4. Implémenter une queue pour les traductions en arrière-plan

### 6.8 Gestion des Erreurs

```php
// Exemple : Gestion gracieuse des erreurs
$translationService = new TranslationService();

try {
    $translated = $translationService->translate($text, 'en', 'fr');
} catch (\Exception $e) {
    // Log l'erreur
    \Log::error('Translation failed: ' . $e->getMessage());
    
    // Afficher le texte original
    $translated = $text;
}

echo $translated;
```

---

## Résumé du Plan Complet (Phases 1-6)

Ce plan d'action transforme le système de langue de **couverture 5% (Domizi seul)** en **couverture 100% (site entier + contenu utilisateur traduit)**.

### **Phase 1-5 : Interface Multilingue** (15 heures)
- Traduction des éléments système (boutons, labels, messages)
- Changement de langue en temps réel
- Support RTL pour l'arabe

### **Phase 6 : Google Translate API** (5-10 heures supplémentaires)
- Traduction automatique du contenu utilisateur
- Cache intelligent pour économiser les appels API
- Support multi-langue pour descriptions et biographies

**Une fois complété, les utilisateurs pourront :**
- Voir l'interface en FR, EN, ES, AR ✅
- Lire les services et descriptions traduits automatiquement ✅
- Accéder au contenu en leur langue préférée ✅

---

**Prochaines Étapes** : 
1. Commencer par Phase 1 (créer fichiers de traduction) - c'est la fondation
2. Après réussite, implémenter Phase 6 avec Google Translate API
