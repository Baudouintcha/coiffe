# ✅ AUDIT 3: COMPLETE FIX METHOD — STEP BY STEP

## 🎯 OBJECTIF
Corriger tous les bugs de routing pour que les URLs passent correctement par le routeur app.php avec `?metier=coiffure`.

---

## 🔧 FIXES À APPLIQUER

### FIX #1 : views/home.php — Ajouter ?metier=coiffure
**Fichier :** `views/home.php`  
**Lignes :** 19-20  
**Criticité :** 🔴 CRITIQUE

**AVANT ❌**
```php
$hp_role_client_href   = '/coiffons/filter/annuaire_coiffeurs.php';
$hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';
$hp_login_href         = '/coiffons/index.php?page=login';
```

**APRÈS ✅**
```php
$hp_role_client_href   = '/coiffons/filter/annuaire_coiffeurs.php';
$hp_role_coiffeur_href = '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';
$hp_login_href         = '/coiffons/index.php?metier=coiffure&page=login';
```

**Commande :**
```bash
str_replace in views/home.php:
  OLD: $hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';
  NEW: $hp_role_coiffeur_href = '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';

  OLD: $hp_login_href         = '/coiffons/index.php?page=login';
  NEW: $hp_login_href         = '/coiffons/index.php?metier=coiffure&page=login';
```

---

### FIX #2 : views/components/hero_premium.php — Ajouter ?metier=coiffure
**Fichier :** `views/components/hero_premium.php`  
**Lignes :** 18-20  
**Criticité :** 🔴 CRITIQUE

**AVANT ❌**
```php
$hp_role_client_href   = $hp_role_client_href   ?? '/coiffons/filter/annuaire_coiffeurs.php';
$hp_role_coiffeur_href = $hp_role_coiffeur_href ?? '/coiffons/index.php?page=register&role=coiffeur';
$hp_login_href         = $hp_login_href         ?? '/coiffons/index.php?page=login';
```

**APRÈS ✅**
```php
$hp_role_client_href   = $hp_role_client_href   ?? '/coiffons/filter/annuaire_coiffeurs.php';
$hp_role_coiffeur_href = $hp_role_coiffeur_href ?? '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';
$hp_login_href         = $hp_login_href         ?? '/coiffons/index.php?metier=coiffure&page=login';
```

**Commande :**
```bash
str_replace in views/components/hero_premium.php:
  OLD: $hp_role_coiffeur_href = $hp_role_coiffeur_href ?? '/coiffons/index.php?page=register&role=coiffeur';
  NEW: $hp_role_coiffeur_href = $hp_role_coiffeur_href ?? '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';

  OLD: $hp_login_href         = $hp_login_href         ?? '/coiffons/index.php?page=login';
  NEW: $hp_login_href         = $hp_login_href         ?? '/coiffons/index.php?metier=coiffure&page=login';
```

---

### FIX #3 : views/domizi/home.php — Chercher et ajouter ?metier=coiffure
**Fichier :** `views/domizi/home.php`  
**Criticité :** 🔴 CRITIQUE

**ACTION :** 
1. Grep pour trouver TOUS les clics "Je suis coiffeur" et "Se connecter"
2. Chercher `/coiffons/index.php?page=`
3. Remplacer par `/coiffons/index.php?metier=coiffure&page=`

**Exemple pattern :**
```php
// AVANT
<a href="/coiffons/index.php?page=register&role=coiffeur">

// APRÈS
<a href="/coiffons/index.php?metier=coiffure&page=register&role=coiffeur">
```

**Commande :**
```bash
grep -n "page=" views/domizi/home.php | grep "index.php"
# Puis manually remplacer chaque ligne trouvée
```

---

### FIX #4 : app.php — Corriger les redirects après login
**Fichier :** `app.php`  
**Lignes :** 142 (default case → home redirige)  
**Criticité :** 🟠 MOYEN

**AVANT ❌**
```php
case 'home':
default:
    // Si l'utilisateur est déjà connecté, redirige vers son dashboard
    if (isset($_SESSION['user_id'])) {
        $role_session = $_SESSION['role'] ?? 'client';
        if ($role_session === 'coiffeur') {
            header("Location: /coiffons/app.php?page=dashboard_coiffeur");
            exit();
        } elseif ($role_session === 'client') {
            header("Location: /coiffons/app.php?page=dashboard");
            exit();
        }
    }
```

**APRÈS ✅**
```php
case 'home':
default:
    // Si l'utilisateur est déjà connecté, redirige vers son dashboard
    if (isset($_SESSION['user_id'])) {
        $role_session = $_SESSION['role'] ?? 'client';
        if ($role_session === 'prestataire') {
            header("Location: /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur");
            exit();
        } elseif ($role_session === 'client') {
            header("Location: /coiffons/index.php?metier=coiffure&page=dashboard");
            exit();
        }
    }
```

**Changements :**
- Ligne 142: `'coiffeur'` → `'prestataire'`
- Ligne 143: `/coiffons/app.php?page=dashboard_coiffeur` → `/coiffons/index.php?metier=coiffure&page=dashboard_coiffeur`
- Ligne 145: `/coiffons/app.php?page=dashboard` → `/coiffons/index.php?metier=coiffure&page=dashboard`

**Commande :**
```bash
str_replace in app.php:
  OLD: if ($role_session === 'coiffeur') {
  NEW: if ($role_session === 'prestataire') {

  OLD: header("Location: /coiffons/app.php?page=dashboard_coiffeur");
  NEW: header("Location: /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur");

  OLD: header("Location: /coiffons/app.php?page=dashboard");
  NEW: header("Location: /coiffons/index.php?metier=coiffure&page=dashboard");
```

---

### FIX #5 : access/connexion.php — Corriger les redirects après login
**Fichier :** `access/connexion.php`  
**Lignes :** 68-77  
**Criticité :** 🔴 CRITIQUE

**AVANT ❌**
```php
if ($user['role'] === 'admin') {
    header("Location: /coiffons/first/admin_dashboard.php");
} elseif ($user['role'] === 'client') {
    header("Location: /coiffons/index.php?page=dashboard");
} elseif ($user['role'] === 'prestataire') {
    header("Location: /coiffons/index.php?page=dashboard_coiffeur");
} else {
    header("Location: /coiffons/index.php");
}
```

**APRÈS ✅**
```php
if ($user['role'] === 'admin') {
    header("Location: /coiffons/first/admin_dashboard.php");
} elseif ($user['role'] === 'client') {
    header("Location: /coiffons/index.php?metier=coiffure&page=dashboard");
} elseif ($user['role'] === 'prestataire') {
    header("Location: /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur");
} else {
    header("Location: /coiffons/index.php?metier=coiffure");
}
```

**Changements :**
- Ligne 71: Ajouter `?metier=coiffure&` avant `page=dashboard`
- Ligne 73: Ajouter `?metier=coiffure&` avant `page=dashboard_coiffeur`
- Ligne 75: `/coiffons/index.php` → `/coiffons/index.php?metier=coiffure`

**Commande :**
```bash
str_replace in access/connexion.php:
  OLD: header("Location: /coiffons/index.php?page=dashboard");
  NEW: header("Location: /coiffons/index.php?metier=coiffure&page=dashboard");

  OLD: header("Location: /coiffons/index.php?page=dashboard_coiffeur");
  NEW: header("Location: /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur");

  OLD: header("Location: /coiffons/index.php");
  NEW: header("Location: /coiffons/index.php?metier=coiffure");
```

---

### FIX #6 : access/inscription.php — Corriger les redirects après inscription
**Fichier :** `access/inscription.php`  
**Lignes :** 116  
**Criticité :** 🔴 CRITIQUE

**AVANT ❌**
```php
$message = "msg-success::Inscription réussie ! Bienvenue sur Coiffe Chez Toi.";
header("Location: /coiffons/index.php?page=dashboard_coiffeur");
exit();
```

**APRÈS ✅**
```php
$message = "msg-success::Inscription réussie ! Bienvenue sur Coiffe Chez Toi.";
header("Location: /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur");
exit();
```

**Commande :**
```bash
str_replace in access/inscription.php:
  OLD: header("Location: /coiffons/index.php?page=dashboard_coiffeur");
  NEW: header("Location: /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur");
```

---

## 📋 CHECKLIST D'EXÉCUTION

```
✓ FIX 1 : views/home.php — 2 remplacements
  □ $hp_role_coiffeur_href
  □ $hp_login_href

✓ FIX 2 : views/components/hero_premium.php — 2 remplacements
  □ $hp_role_coiffeur_href
  □ $hp_login_href

✓ FIX 3 : views/domizi/home.php — Grep + remplacements manuels
  □ Tous les liens avec ?page=register&role=coiffeur
  □ Tous les liens avec ?page=login

✓ FIX 4 : app.php — 3 remplacements
  □ 'coiffeur' → 'prestataire'
  □ /coiffons/app.php?page=dashboard_coiffeur
  □ /coiffons/app.php?page=dashboard

✓ FIX 5 : access/connexion.php — 3 remplacements
  □ ?page=dashboard
  □ ?page=dashboard_coiffeur
  □ Fallback redirect

✓ FIX 6 : access/inscription.php — 1 remplacement
  □ ?page=dashboard_coiffeur

TOTAL: 12 remplacements
```

---

## 🧪 TESTS À FAIRE APRÈS LES FIXES

### Test 1 : Flux Client
```
1. Accès /coiffons/index.php?metier=coiffure
   ✓ Affiche views/home.php
   
2. Clique "Je cherche un coiffeur"
   ✓ Va à /coiffons/filter/annuaire_coiffeurs.php
   
3. Clique "Se connecter"
   ✓ Va à access/connexion.php (pas accueil)
   ✓ Login → Redirect vers dashboard client
   
4. Clique "Pas encore inscrit ?"
   ✓ Va à /coiffons/index.php?metier=coiffure&page=register&role=client
```

### Test 2 : Flux Coiffeur
```
1. Accès /coiffons/index.php?metier=coiffure
   ✓ Affiche views/home.php
   
2. Clique "Je suis coiffeur"
   ✓ Va à access/inscription.php (pas accueil)
   ✓ Remplissez form + diplôme
   ✓ Submit → Inscription réussie
   ✓ Auto-redirect vers /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur
   ✓ Affiche views/coiffeur/dashboard.php
```

### Test 3 : Flux Login
```
1. Clique "Se connecter"
   ✓ Va à access/connexion.php
   
2. Email + password client
   ✓ Redirect vers dashboard client
   
3. Email + password coiffeur
   ✓ Redirect vers dashboard coiffeur
   
4. Email + password admin
   ✓ Redirect vers admin_dashboard.php
```

---

## ⚠️ IMPORTANT — APRÈS LES FIXES

1. **Vider cache navigateur** (Ctrl+Shift+Del)
2. **Vérifier sessions PHP** (pas de session fantôme)
3. **Tester avec navigateur incognito** (si besoin)
4. **Vérifier .htaccess** n'interfère pas avec les redirects
5. **Checker logs PHP** pour erreurs 302/Location headers

---

## 🚀 ORDRE D'EXÉCUTION RECOMMANDÉ

1. FIX 1 (views/home.php)
2. FIX 2 (hero_premium.php)
3. FIX 5 (connexion.php)
4. FIX 6 (inscription.php)
5. FIX 4 (app.php)
6. FIX 3 (domizi/home.php)
7. **TESTS COMPLETES**

**Raison:** Priorité aux fichiers critiques (home → auth → routing)
