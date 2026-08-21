# 📊 AUDIT 1: IDEAL ROUTING FLOW (UI-BASED)

## 🎯 ARCHITECTURE GLOBALE

```
┌─────────────────────────────────────────────────────────┐
│ index.php (DOMIZI — Point d'entrée GLOBAL)              │
│ ✓ Charge .env                                           │
│ ✓ Charge Lang system                                    │
│ ✓ Gère langues (?lang=fr)                               │
│ ✓ API métiers (?action=api_metiers)                     │
└─────────────────────────────────────────────────────────┘
              ↓
    ┌─ Si ?metier=coiffure
    │   ↓
    └──→ app.php (ROUTEUR MÉTIER — Coiffe Chez Toi)
         ├─ ?page=home → views/home.php
         ├─ ?page=login → access/connexion.php
         ├─ ?page=register → access/inscription.php
         ├─ ?page=dashboard → views/client/dashboard.php
         └─ ?page=dashboard_coiffeur → views/coiffeur/dashboard.php
    
    └─ Sinon → views/domizi/home.php (PAGE DOMIZI GLOBALE)
```

---

## 📱 USER JOURNEY — FLUX IDÉAL COMPLET

### ÉTAPE 1 : ARRIVÉE SUR LE SITE
```
User accède à http://localhost/coiffons/

index.php affiche views/domizi/home.php
├─ Titre: "DOMIZI — Le service à domicile"
├─ Cartes domaines: Coiffure, Plomberie, Électricité, etc.
└─ "Choisir un domaine" → clique sur Coiffure card
```

### ÉTAPE 2 : ENTRÉE DANS LA PLATEFORME COIFFURE
```
User clique "COIFFURE"
│
└─→ index.php?metier=coiffure
    ↓
    app.php chargé (default ?page=home)
    ↓
    views/home.php affichée (Hero Premium + Accueil Coiffure)
    
    UI: 3 CHOIX AFFICHÉS
    ┌────────────────────────────────────┐
    │ 1. "Je cherche un coiffeur"        │ → /coiffons/filter/annuaire_coiffeurs.php
    │ 2. "Je suis coiffeur"              │ → /coiffons/index.php?metier=coiffure&page=register&role=coiffeur
    │ 3. "Se connecter" (déjà inscrit)   │ → /coiffons/index.php?metier=coiffure&page=login
    └────────────────────────────────────┘
```

---

## 🔄 FLUX COMPLETS PAR RÔLE

### FLUX A : CLIENT — RECHERCHE ET RÉSERVATION

```
1. Accueil Coiffure
   ├─ User clique "Je cherche un coiffeur"
   │  ↓
   │  filter/annuaire_coiffeurs.php
   │  ├─ Liste coiffeurs filtrables
   │  └─ User clique profil coiffeur
   │     ↓
   │     coiffeurs/profil_public.php
   │     ├─ Voir prestations, avis, agenda
   │     └─ User clique "Réserver"
   │        ├─ Si NON connecté → redirect ?metier=coiffure&page=login
   │        └─ Si connecté → client/creer_rendezvous.php
   │           ├─ Choisir prestation, date, heure
   │           └─ Submit
   │              ↓
   │              Notification coiffeur
   │              Dashboard client (mis à jour)
   │
   ├─ User clique "Se connecter"
   │  ↓
   │  /index.php?metier=coiffure&page=login
   │  ↓
   │  app.php → access/connexion.php
   │  ├─ Form: email + password
   │  └─ Submit → PDO verify + SESSION set
   │     ├─ Si ADMIN → /coiffons/first/admin_dashboard.php
   │     ├─ Si CLIENT → /index.php?metier=coiffure&page=dashboard
   │     └─ Si PRESTATAIRE → /index.php?metier=coiffure&page=dashboard_coiffeur
   │
   └─ Client connecté → /index.php?metier=coiffure&page=dashboard
      ├─ Voir mes réservations
      ├─ Laisser avis
      ├─ Historique RDV
      └─ Profil

```

### FLUX B : COIFFEUR — INSCRIPTION ET GESTION

```
1. Accueil Coiffure
   ├─ User clique "Je suis coiffeur"
   │  ↓
   │  /index.php?metier=coiffure&page=register&role=coiffeur
   │  ↓
   │  app.php → access/inscription.php
   │  ├─ Form: nom, prenom, email, telephone, password
   │  ├─       sexe, ville, quartier
   │  ├─       diplôme (REQUIRED)
   │  ├─       photo profil (optional)
   │  └─ Submit
   │     ├─ Validate + PDO INSERT
   │     └─ SESSION set → redirect
   │        ↓
   │        /index.php?metier=coiffure&page=dashboard_coiffeur
   │
   └─ Coiffeur connecté → Dashboard Coiffeur
      ├─ Mes demandes RDV
      ├─ Mon agenda
      ├─ Gestion coiffures/services
      ├─ Mes zones d'intervention
      ├─ Mes gains (portefeuille)
      ├─ Profil public
      └─ Avis reçus
```

### FLUX C : ADMIN — GESTION PLATEFORME

```
1. Login
   /index.php?metier=coiffure&page=login
   → access/connexion.php
   → Submit → PDO check role=admin
   ↓
2. Admin connecté
   /coiffons/first/admin_dashboard.php
   ├─ Approuver coiffeurs
   ├─ Gérer demandes
   ├─ Stats
   └─ Actions

```

---

## 🔗 URLS CORRECTES (À UTILISER PARTOUT)

| Page | URL Correct | Fichier |
|------|------------|---------|
| **Accueil DOMIZI** | `/coiffons/` | index.php + views/domizi/home.php |
| **Accueil Coiffure** | `/coiffons/index.php?metier=coiffure` | index.php → app.php + views/home.php |
| **Login** | `/coiffons/index.php?metier=coiffure&page=login` | app.php → access/connexion.php |
| **Inscription Client** | `/coiffons/index.php?metier=coiffure&page=register&role=client` | app.php → access/inscription.php |
| **Inscription Coiffeur** | `/coiffons/index.php?metier=coiffure&page=register&role=coiffeur` | app.php → access/inscription.php |
| **Dashboard Client** | `/coiffons/index.php?metier=coiffure&page=dashboard` | app.php → views/client/dashboard.php |
| **Dashboard Coiffeur** | `/coiffons/index.php?metier=coiffure&page=dashboard_coiffeur` | app.php → views/coiffeur/dashboard.php |
| **Admin Dashboard** | `/coiffons/first/admin_dashboard.php` | Direct (no routing) |
| **Annuaire** | `/coiffons/filter/annuaire_coiffeurs.php` | Direct (no routing) |
| **Profil Coiffeur** | `/coiffons/coiffeurs/profil_public.php?id=X` | Direct (no routing) |

---

## ✅ POINTS CLÉS À RESPECTER

1. **Tous les liens "Login" et "Register" doivent avoir `?metier=coiffure`**
2. **app.php est le routeur central pour les pages auth/dashboard**
3. **Les redirections après login doivent inclure `?metier=coiffure`**
4. **Les clics depuis views/home.php doivent passer par le routeur**
5. **Les pages directes (annuaire, profil) n'ont PAS besoin de ?metier=**
