# Migration vers domizi_v2 - Documentation

## 📋 Changements effectués

### 1. **Échange des fichiers index.php et app.php**
- **Ancien `app.php`** (page Domizi avec sélection métiers) → **Nouveau `index.php`**
- **Ancien `index.php`** (routeur Coiffe Chez Toi) → **Nouveau `app.php`**

### 2. **Unification de la connexion base de données**
Avant, le projet utilisait **deux systèmes de connexion différents** :
- `security/config.php` → base `cft` (ancienne)
- `src/Core/Database.php` → base via `.env` (nouvelle architecture)

**Solution** : Tout utilise maintenant `Database::getInstance()` avec la base `domizi_v2`

### 3. **Structure de base de données unifiée**
La nouvelle base `domizi_v2` contient :
- **Tables métiers/domaines** (pour le système multi-métiers Domizi)
- **Toutes les données de `cft`** (users, prestations, rendez-vous, etc.)

## 🚀 Instructions de migration

### Étape 1 : Exécuter le script de migration
Ouvrir dans le navigateur :
```
http://localhost/coiffons/migrate_to_domizi_v2.php
```

Ce script va :
1. ✅ Créer la base `domizi_v2`
2. ✅ Importer le schéma complet depuis `security/domizi_v2.sql`
3. ✅ Migrer toutes les données de `cft` vers `domizi_v2`
4. ✅ Conserver les métiers et domaines prédéfinis

### Étape 2 : Vérification
Le fichier `.env` a déjà été mis à jour avec :
```env
DB_NAME=domizi_v2
```

### Étape 3 : Tester l'application
Ouvrir : `http://localhost/coiffons/`

Vous devriez voir :
1. **Page d'accueil Domizi** avec sélection de domaines (Beauté & Bien-être)
2. **Cliquer sur un domaine** affiche les métiers (Coiffure, Maquillage, etc.)
3. **Cliquer sur Coiffure** charge l'application "Coiffe Chez Toi"

## 📂 Architecture des fichiers

### Flux de navigation
```
index.php (Domizi - Sélection métier)
    ↓
    ?metier=coiffure
    ↓
app.php (Coiffe Chez Toi - Routeur)
    ↓
    ?page=login | ?page=register | ?page=dashboard
```

### Connexion base de données
```php
// Partout dans le code maintenant :
use App\Core\Database;
$pdo = Database::getInstance();

// Au lieu de l'ancien :
require_once __DIR__ . '/security/config.php';
$pdo = ... // variable globale
```

## 🔧 Corrections appliquées

### 1. Boucle infinie corrigée
**Problème** : `index.php` ligne 106 appelait `require __DIR__ . '/index.php'`
**Solution** : Changé en `require __DIR__ . '/app.php'`

### 2. Redirections mises à jour
Toutes les redirections vers `/coiffons/app.php` dans `index.php` ont été changées en `/coiffons/index.php`

### 3. Architecture unifiée
- `app.php` charge maintenant `Database::getInstance()` au lieu de `security/config.php`
- Suppression des appels à `$pdo` global

## ✅ Checklist post-migration

- [ ] Script de migration exécuté sans erreur
- [ ] Page d'accueil Domizi s'affiche
- [ ] Sélection de métier fonctionne
- [ ] Connexion utilisateur fonctionne
- [ ] Dashboard client/coiffeur accessibles
- [ ] Données utilisateurs préservées

## 🗑️ Nettoyage optionnel

Une fois que tout fonctionne :
1. Vous pouvez supprimer la base `cft` (ancien système)
2. Vous pouvez supprimer le fichier `migrate_to_domizi_v2.php`
3. Le fichier `security/config.php` peut être conservé pour compatibilité ou supprimé

## 📝 Notes importantes

- **Ne pas supprimer `domizi_v2.sql`** : c'est le schéma de référence
- **Garder le backup de `cft`** avant suppression (export SQL)
- Les tables `domaines` et `metiers` sont essentielles au nouveau système

## 🐛 Troubleshooting

### Erreur "Table metiers doesn't exist"
→ Le script de migration n'a pas été exécuté ou a échoué
→ Relancer `migrate_to_domizi_v2.php`

### Erreur "Service temporairement indisponible"
→ Vérifier que `.env` contient bien `DB_NAME=domizi_v2`
→ Vérifier que la base `domizi_v2` existe dans phpMyAdmin

### Page blanche après sélection métier
→ Vérifier les logs d'erreurs PHP
→ S'assurer que `app.php` charge bien `Database::getInstance()`

## 📞 Support

En cas de problème, vérifier :
1. Les erreurs PHP dans le navigateur (mode debug activé)
2. Les logs MySQL
3. Que XAMPP (Apache + MySQL) est bien démarré
