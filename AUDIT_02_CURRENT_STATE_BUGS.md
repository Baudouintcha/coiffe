# 🔴 AUDIT 2: CURRENT STATE & EXISTING BUGS

## 📋 CE QUI FONCTIONNE ACTUELLEMENT ✅

| Component | Status | Notes |
|-----------|--------|-------|
| index.php routing | ✅ | Redirige vers app.php si ?metier=coiffure |
| app.php routeur | ✅ | Switch sur ?page correctement |
| Database connexion | ✅ | Lecture depuis .env via Database class |
| security/config.php | ✅ | Charge .env |
| access/connexion.php | ✅ | Vérifie credentials, set SESSION |
| access/inscription.php | ✅ | Crée compte avec role='prestataire' |
| Role system | ✅ | users.role = enum('client','prestataire','admin') |
| SQL fixes | ✅ | u.id_ville, u.abonnement_status, etc. fixed |
| Annuaire affichage | ✅ | Si coiffeurs existent avec role='prestataire' |

---

## 🔴 BUGS IDENTIFIÉS

### BUG #1 : "Je suis coiffeur" renvoie sur accueil au lieu d'inscription
**Severité:** 🔴 CRITIQUE  
**Impact:** Impossible de devenir coiffeur

**Symptôme :**
```
User clique "Je suis coiffeur"
Expected: /coiffons/index.php?metier=coiffure&page=register&role=coiffeur
Actual: /coiffons/index.php (simplement index.php)
Résultat: views/domizi/home.php affichée (pas app.php)
```

**Cause Identifiée :**
- `views/home.php` ligne 19 : `$hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';`
- **MANQUE** le paramètre `?metier=coiffure`
- Sans `?metier=coiffure`, index.php NE route PAS vers app.php
- index.php affiche donc views/domizi/home.php par défaut

**Code Bugué :**
```php
// views/home.php ligne 19 ❌
$hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';

// Devrait être ✅
$hp_role_coiffeur_href = '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';
```

---

### BUG #2 : "Se connecter" renvoie sur accueil au lieu de login
**Severité:** 🔴 CRITIQUE  
**Impact:** Impossible de se connecter depuis accueil

**Symptôme :**
```
User clique "Se connecter"
Expected: /coiffons/index.php?metier=coiffure&page=login
Actual: /coiffons/index.php (simplement index.php)
Résultat: views/domizi/home.php affichée
```

**Cause :**
- `views/home.php` ligne 20 : `$hp_login_href = '/coiffons/index.php?page=login';`
- **MANQUE** le paramètre `?metier=coiffure`

**Code Bugué :**
```php
// views/home.php ligne 20 ❌
$hp_login_href = '/coiffons/index.php?page=login';

// Devrait être ✅
$hp_login_href = '/coiffons/index.php?metier=coiffure&page=login';
```

---

### BUG #3 : views/components/hero_premium.php a aussi les mêmes URLs buguées
**Severité:** 🔴 CRITIQUE  
**Impact:** Tous les clics depuis hero_premium ne routent pas correctement

**Code Bugué :**
```php
// views/components/hero_premium.php lignes 18-20 ❌
$hp_role_client_href   = '/coiffons/filter/annuaire_coiffeurs.php';
$hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';
$hp_login_href         = '/coiffons/index.php?page=login';

// Doivent être ✅
$hp_role_client_href   = '/coiffons/filter/annuaire_coiffeurs.php'; // ✓ CORRECT
$hp_role_coiffeur_href = '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';
$hp_login_href         = '/coiffons/index.php?metier=coiffure&page=login';
```

---

### BUG #4 : app.php ligne 34-36 cherche role='coiffeur' au lieu de 'prestataire'
**Severité:** 🔴 CRITIQUE  
**Impact:** Impossible pour prestataires connectés de voir leur dashboard

**Symptôme :**
```
Prestataire se connecte → Redirect fails → Affiche accueil
```

**Code Bugué :**
```php
// app.php ligne 34-36 ❌
case 'dashboard_coiffeur':
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'coiffeur') {
        header("Location: /coiffons/index.php?page=login");
```

**Correction Appliquée :** ✅
```php
// app.php ligne 34-36 ✅
case 'dashboard_coiffeur':
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'prestataire') {
```
**Status:** DÉJÀ CORRIGÉ dans session précédente

---

### BUG #5 : views/domizi/home.php aussi a des boutons avec URLs incorrectes
**Severité:** 🔴 CRITIQUE  
**Impact:** Page d'accueil DOMIZI ne route pas correctement vers Coiffure

**Localisation :** Boutons "Je suis coiffeur" et "Se connecter" dans domizi/home.php  
**Probable:** Même problème que BUG #1 et #2

---

### BUG #6 : app.php ligne 142 — default home redirige vers wrong URL
**Severité:** 🟠 MOYEN  
**Impact:** Utilisateurs connectés redirigés vers fausse URL

**Code Bugué :**
```php
// app.php ligne 142 ❌
if ($role_session === 'coiffeur') {
    header("Location: /coiffons/app.php?page=dashboard_coiffeur");
    
// Devrait être ✅
if ($role_session === 'prestataire') {
    header("Location: /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur");
```

**Status:** Pas corrigé — app.php ne vérifie pas '?metier=coiffure'

---

## 📊 RÉSUMÉ DES BUGS PAR FICHIER

| Fichier | Bug | Ligne | Severité |
|---------|-----|-------|----------|
| views/home.php | URLs manquent ?metier=coiffure | 19-20 | 🔴 CRITIQUE |
| views/components/hero_premium.php | URLs manquent ?metier=coiffure | 18-20 | 🔴 CRITIQUE |
| views/domizi/home.php | URLs manquent ?metier=coiffure | ? | 🔴 CRITIQUE |
| app.php | Redirect après login faux | 142 | 🟠 MOYEN |
| app.php | Role check='coiffeur' au lieu de 'prestataire' | 34 | 🔴 CRITIQUE (FIXED) |

---

## 🔍 TRACEBACK DU BUG #1 (Example)

```
User clique "Je suis coiffeur" sur accueil
│
├─ views/home.php HTML button
│  onclick → href="/coiffons/index.php?page=register&role=coiffeur" ❌
│  
├─ Navigateur charge index.php?page=register&role=coiffeur
│  
├─ index.php reçoit $_GET['page']='register', $_GET['role']='coiffeur'
│  ├─ Pas de $_GET['metier'] ❌
│  ├─ Condition: if (isset($_GET['metier'])) → FALSE
│  ├─ Condition: if (isset($_SESSION['user_id'])) → FALSE (pas connecté)
│  └─ Default: require __DIR__ . '/views/domizi/home.php'; ← PAGE D'ACCUEIL AFFICHÉE
│
└─ Result: User reste sur views/domizi/home.php (ACCUEIL DOMIZI)
   Expected: User devrait voir access/inscription.php (FORM INSCRIPTION)
```

---

## ✅ WHAT'S WORKING (NO CHANGES NEEDED)

1. ✅ Annuaire direct : `/coiffons/filter/annuaire_coiffeurs.php`
2. ✅ Profil coiffeur direct : `/coiffons/coiffeurs/profil_public.php?id=X`
3. ✅ Admin dashboard direct : `/coiffons/first/admin_dashboard.php`
4. ✅ App.php routing logic (IF called correctly with ?metier=coiffure)
5. ✅ Database queries et SQL fixes
