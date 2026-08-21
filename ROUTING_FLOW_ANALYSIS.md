# 🗺️ ROUTING FLOW ANALYSIS — Coiffe Chez Toi

## 📊 Architecture Actuelle

```
index.php (point d'entrée DOMIZI)
    ↓
    ├─ Si ?metier=coiffure
    │   ↓
    │   app.php (routeur Coiffure Chez Toi)
    │   ├─ ?page=home → views/home.php
    │   ├─ ?page=login → access/connexion.php
    │   ├─ ?page=register&role=X → access/inscription.php
    │   ├─ ?page=dashboard → views/client/dashboard.php
    │   └─ ?page=dashboard_coiffeur → views/coiffeur/dashboard.php
    │
    ├─ Si ?lang=fr → redirige (clean URL)
    ├─ Si action=api_metiers → JSON response
    └─ Sinon → views/domizi/home.php (page DOMIZI globale)
```

---

## 🎨 UI USER JOURNEY

### Page d'accueil (views/home.php ou views/domizi/home.php)

**Buttons disponibles :**
```
1. "Je cherche un coiffeur"     → /coiffons/filter/annuaire_coiffeurs.php
2. "Je suis coiffeur"            → /coiffons/index.php?page=register&role=coiffeur ❌ BROKEN
3. "Se connecter"                → /coiffons/index.php?page=login ❌ BROKEN
4. "Déjà inscrit ? Se connecter" → /coiffons/index.php?page=login ❌ BROKEN
```

---

## ❌ BUGS IDENTIFIÉS

### BUG #1 : "Je suis coiffeur" redirige sur index.php au lieu de inscription
**URL cliquée :** `/coiffons/index.php?page=register&role=coiffeur`
**Chemin actuel :**
1. index.php reçoit `?page=register&role=coiffeur`
2. Pas de `?metier=coiffure` → index.php NE roule PAS vers app.php
3. index.php affiche views/domizi/home.php à la place
4. Boucle infinie sur home page

**SOLUTION :** Ajouter `?metier=coiffure` à tous les clics ou modifier index.php pour reconnaître ?page=

---

### BUG #2 : "Se connecter" redirige sur index.php au lieu de connexion.php
**URL cliquée :** `/coiffons/index.php?page=login`
**Même problème que BUG #1**
- index.php n'a pas de branche pour `?page=login` seul
- Affiche views/domizi/home.php par défaut

**SOLUTION :** Même que BUG #1

---

## ✅ FLUX CORRECT (À IMPLÉMENTER)

### PARCOURS CLIENT - Inscription Client
```
Accueil (/coiffons/index.php?metier=coiffure)
  ↓ clique "Je cherche un coiffeur"
  ↓
Annuaire (/coiffons/filter/annuaire_coiffeurs.php)
  ↓ clique "Réserver"
  ↓
Doit être connecté ?
  ├─ OUI → Dashboard Client
  └─ NON → /coiffons/index.php?metier=coiffure&page=register&role=client
            ↓
            app.php ?page=register → access/inscription.php (FORM CLIENT)
            ↓
            Connexion auto → /coiffons/index.php?metier=coiffure&page=dashboard
```

### PARCOURS COIFFEUR - Inscription Coiffeur
```
Accueil (/coiffons/index.php?metier=coiffure)
  ↓ clique "Je suis coiffeur"
  ↓
/coiffons/index.php?metier=coiffure&page=register&role=coiffeur
  ↓
app.php ?page=register&role=coiffeur → access/inscription.php (FORM COIFFEUR)
  ↓
Connexion auto → /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur
```

### PARCOURS CONNEXION
```
Accueil (/coiffons/index.php?metier=coiffure)
  ↓ clique "Se connecter"
  ↓
/coiffons/index.php?metier=coiffure&page=login
  ↓
app.php ?page=login → access/connexion.php
  ↓ submit
  ├─ Si ADMIN → /coiffons/first/admin_dashboard.php
  ├─ Si CLIENT → /coiffons/index.php?metier=coiffure&page=dashboard
  └─ Si COIFFEUR → /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur
```

---

## 🔧 FIXES REQUIS

### FIX #1 : Modifier hero_premium.php
**Fichier :** `views/components/hero_premium.php`

Remplacer les URLs :
```php
// AVANT ❌
$hp_login_href         = '/coiffons/index.php?page=login';
$hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';

// APRÈS ✅
$hp_login_href         = '/coiffons/index.php?metier=coiffure&page=login';
$hp_role_coiffeur_href = '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';
```

### FIX #2 : Modifier views/home.php
**Fichier :** `views/home.php` ligne 20

```php
// AVANT ❌
$hp_login_href         = '/coiffons/index.php?page=login';
$hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';

// APRÈS ✅
$hp_login_href         = '/coiffons/index.php?metier=coiffure&page=login';
$hp_role_coiffeur_href = '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';
```

### FIX #3 : Modifier accueil domizi
**Fichier :** `views/domizi/home.php`

Ajouter `?metier=coiffure` à tous les clics "Je suis coiffeur" et "Se connecter"

---

## 📋 CHECKLIST FINAL

- [ ] FIX #1 : hero_premium.php URLs
- [ ] FIX #2 : views/home.php URLs
- [ ] FIX #3 : views/domizi/home.php URLs
- [ ] Test "Je suis coiffeur" → inscription.php ✅
- [ ] Test "Se connecter" → connexion.php ✅
- [ ] Test flux CLIENT complet (recherche → réservation)
- [ ] Test flux COIFFEUR complet (inscription → dashboard)
- [ ] Test flux ADMIN complet (login → admin_dashboard)
