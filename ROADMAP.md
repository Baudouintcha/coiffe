# 🗺️ DOMIZI — ROADMAP OFFICIELLE v2.0
> Plateforme de services à domicile — Bénin & Sous-région Ouest-Africaine
> Projet de soutenance + vision long terme
> Dernière mise à jour : Juillet 2026

---

## 🎯 VISION DU PROJET

**Domizi** est une marketplace de services à domicile pensée pour l'Afrique de l'Ouest.
Chaque domaine métier a sa propre "branche" :

| Branche | Domaine | Statut |
|---|---|---|
| **Coiffe Chez Toi** | Beauté & Coiffure | 🚧 En cours |
| Glam Chez Toi | Maquillage & Ongles | 🔜 Après soutenance |
| Fix Chez Toi | Plomberie & Électricité | 🔜 Futur |
| Clean Chez Toi | Ménage & Nettoyage | 🔜 Futur |

**Stack technique :**
- PHP natif — Architecture MVC (Structure 2 — Composer + PSR-4)
- MySQL (MariaDB)
- Bootstrap 5.3 + CSS Premium (Dark/Gold)
- XAMPP en local → VPS en production

---

## 📐 ARCHITECTURE CIBLE (Structure 2 — MVC Moderne)

```
domizi/
├── public/                    ← Point d'entrée unique (VPS-ready)
│   ├── index.php              ← Front controller
│   ├── css/
│   ├── js/
│   └── uploads/
├── src/
│   ├── Core/
│   │   ├── Database.php       ← Singleton PDO
│   │   ├── Router.php         ← Dispatch des routes
│   │   ├── Request.php        ← Encapsule $_GET, $_POST, $_FILES
│   │   ├── Response.php       ← Redirections, JSON responses
│   │   ├── Session.php        ← Gestion session centralisée
│   │   └── View.php           ← Rendu des vues
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   └── RegisterController.php
│   │   ├── Prestataire/
│   │   │   ├── CatalogueController.php
│   │   │   ├── AgendaController.php
│   │   │   └── ProfilController.php
│   │   ├── Client/
│   │   │   ├── ReservationController.php
│   │   │   └── RendezVousController.php
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       ├── ValidationController.php
│   │       └── ModerationController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Service.php
│   │   ├── RendezVous.php
│   │   ├── Notification.php
│   │   ├── Avis.php
│   │   └── Transaction.php
│   ├── Middlewares/
│   │   ├── AuthMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   └── CsrfMiddleware.php
│   ├── Services/
│   │   ├── PaymentService.php       ← Kkiapay
│   │   ├── WhatsAppService.php      ← API WhatsApp
│   │   ├── NotificationService.php
│   │   ├── ReservationService.php
│   │   └── UploadService.php
│   └── Validators/
│       ├── AuthValidator.php
│       └── ReservationValidator.php
├── views/
│   ├── layouts/
│   │   ├── main.php           ← Layout principal (header + footer)
│   │   ├── admin.php          ← Layout admin
│   │   └── guest.php          ← Layout visiteur
│   ├── home.php
│   ├── auth/
│   ├── prestataire/
│   ├── client/
│   └── admin/
├── routes/
│   └── web.php                ← Toutes les routes centralisées
├── config/
│   ├── app.php                ← Config globale
│   ├── database.php           ← Paramètres BDD
│   └── services.php           ← Clés API (Kkiapay, WhatsApp...)
├── .env                       ← Variables sensibles (ne jamais committer)
├── .env.example               ← Template .env
├── composer.json              ← Autoloading PSR-4
└── .htaccess                  ← Redirection tout vers public/index.php
```

---

## 🗄️ BASE DE DONNÉES v2.0

### Tables validées et finales

| Table | Rôle | Statut |
|---|---|---|
| `pays` | Gestion multi-pays sous-région | ✅ Validée |
| `villes` | Villes par pays | ✅ Validée |
| `quartiers` | Quartiers par ville | ✅ Validée |
| `domaines` | Domaines métiers (Beauté, Maison...) | ✅ Validée |
| `metiers` | Métiers par domaine (Coiffure, Massage...) | ✅ Validée |
| `users` | Tous les utilisateurs (client/prestataire/admin) | ✅ Validée |
| `profils_prestataires` | Données spécifiques prestataire | ✅ Validée |
| `zones_prestataire` | Zones d'intervention | ✅ Validée |
| `services` | Catalogue de prestations | ✅ Validée |
| `disponibilites` | Agenda hebdomadaire | ✅ Validée |
| `rendez_vous` | Réservations | ✅ Validée |
| `avis` | Notes + plaintes | ✅ Validée |
| `transactions` | Historique financier complet | ✅ Validée |
| `paiements_kkiapay` | Webhooks Kkiapay | ✅ Validée |
| `notifications` | Alertes in-app | ✅ Validée |
| `ia_sessions` | Contexte chatbot IA | ✅ Validée |

### Tables supprimées (obsolètes)
- ~~`catalogue`~~ → remplacée par `services`
- ~~`commentaires`~~ → remplacée par `avis`
- ~~`prestations`~~ → remplacée par `services`
- ~~`portefeuilles`~~ → fusionnée dans `users.solde`
- ~~`transactions_portefeuille`~~ → remplacée par `transactions`
- ~~`transactions_site`~~ → fusionnée dans `transactions`
- ~~`plaintes`~~ → fusionnée dans `avis.type = 'plainte'`
- ~~`abonnements_paiements`~~ → remplacée par `paiements_kkiapay`
- ~~`boutique`~~ → hors scope
c:\xampp\htdocs\coiffons\images\domizi-scene.jpg   c'rest le chemin du background image
---

## 🚀 PHASES DE DÉVELOPPEMENT

---

### ✅ PHASE 0 — Fondations (TERMINÉE)
**Objectif : Poser les bases solides avant de coder**

- [x] Analyse complète du projet existant
- [x] Choix de l'architecture (MVC Structure 2)
- [x] Nom du projet validé : **Domizi**
- [x] Vision multi-métiers définie
- [x] Nouvelle BDD v2.0 conçue et validée
- [x] ROADMAP créée
- [x] Créer la nouvelle BDD `domizi` dans phpMyAdmin
- [x] Installer Composer
- [x] Créer le squelette MVC vide

---

### ✅ PHASE 1 — Socle MVC (TERMINÉE)
**Objectif : Le squelette qui fait tourner tout le reste**

- [x] `composer.json` + autoloading PSR-4
- [x] `.env` + `.env.example`
- [x] `src/Core/Database.php` — Singleton PDO
- [x] `src/Core/Router.php` — Dispatch des routes
- [x] `src/Core/Session.php` — Gestion session
- [x] `src/Core/View.php` — Rendu des vues
- [x] `src/Core/Lang.php` — Gestionnaire multilingue FR/EN/ES/AR
- [x] `routes/web.php` — Définition des routes
- [x] `app.php` — Point d'entrée XAMPP
- [x] `public/index.php` — Point d'entrée VPS
- [x] `.htaccess` — Redirection
- [x] `views/errors/404.php`

---

### ✅ PHASE 1b — Portail DOMIZI (TERMINÉE)
- [x] `src/Controllers/BaseController.php`
- [x] `src/Controllers/DomiziController.php`
- [x] `views/domizi/home.php` — Page portail avec domaines
- [x] API AJAX métiers
- [x] Dropdown langue (icône globe)
- [x] Traductions FR/EN/ES/AR

---

### ✅ PHASE 2 — Page d'accueil "Coiffe Chez Toi" + Auth (TERMINÉE)
**Objectif : Le cœur fonctionnel pour la soutenance**

**Flux implémenté :**
```
app.php (DOMIZI) → clic Beauté → métiers → clic Coiffure
→ index.php (routeur CCT) → views/home.php (slider + boutons)
    ↓ bouton "Je suis Client"      → inscription.php?role=client
    ↓ bouton "Je suis Coiffeur"    → inscription.php?role=coiffeur
    ↓ bouton "Se connecter"        → connexion.php
    ↓ après connexion client       → ?page=dashboard (views/client/dashboard.php)
```

- [x] `app.php` branché → `index.php` pour le métier coiffure
- [x] `views/home.php` — slider carousel imgid/ + boutons rôles
- [x] Header supprimé de la page d'accueil CCT (vue autonome)
- [x] `access/inscription.php` — refonte complète :
  - [x] Fond flou glassmorphism (même image que slider)
  - [x] Rôle pré-défini via URL (plus de sélection dans le formulaire)
  - [x] Champs pré-remplis après erreur PHP
  - [x] Compression automatique photos côté client (Canvas JS)
  - [x] Fix bug `$nom_clean` → corrigé (`$nom` et `$prenom`)
  - [x] Admin supprimé du formulaire public
  - [x] Dropdown ville/quartier AJAX
  - [x] CSRF token sur tous les formulaires POST
  - [x] Validation stricte (MIME réel, strip_tags, filter_var)
  - [x] Protection retour arrière post-inscription
  - [x] Redirection client → dashboard après inscription
- [x] `access/connexion.php` — refonte complète :
  - [x] Fond flou glassmorphism
  - [x] Sans header/footer
  - [x] CSRF protection
  - [x] Protection brute force (5 tentatives / 15 min)
  - [x] Vérification compte banni
  - [x] Redirection client → dashboard après connexion
- [x] `security/csrf.php` — helper CSRF centralisé
- [x] `views/client/dashboard.php` — homepage client premium :
  - [x] Hero carousel images imgid/
  - [x] Carte profil glassmorphism (desktop)
  - [x] Barre de recherche coiffeur
  - [x] Cartes coiffeurs avec skeleton si BDD vide
  - [x] Section "Pourquoi Coiffe Chez Toi"
  - [x] Footer premium
- [x] Route `?page=dashboard` dans `index.php` avec matching géo

---

### 🚧 PHASE 2b — Correctifs & Améliorations UI (EN COURS)

- [x] Hero annuaire réduit (35vh glassmorphism)
- [x] Commentaires logo dans dashboard + navbar
- [ ] Fix : redirection post-inscription pointe encore `/coiffons/index.php` au lieu de `?page=dashboard` → à vérifier selon config BDD active
- [ ] Annuaire — support paramètre `?q=` pour la recherche par nom

---

### 🚧 PHASE 3 — Module Prestataire/Coiffeur (EN COURS)
**Objectif : Dashboard premium + gestion activité quotidienne**

**Flux validé :**
```
Inscription coiffeur → Dashboard coiffeur
    ↓ Bannière rouge si abonnement non payé
    ↓ Bannière jaune si diplôme non approuvé
    ↓ Pages existantes conservées (catalogue, agenda, RDV)
    ↓ Refonte visuelle Dark/Gold cohérente
```

**Données adaptées BDD `domizi` (migration future) :**
```
cft (actuelle)          domizi (future)
──────────────          ──────────────
prestations          →  services
users.abonnement_status → users.abonnement_status
rendez_vous          →  rendez_vous (même structure)
```

**Phase 3A — Dashboard coiffeur (UI premium)**
- [x] `views/coiffeur/dashboard.php` — Dashboard premium :
  - [x] Navbar sidebar desktop / bottom nav mobile
  - [x] Hero glassmorphism : stats du jour (RDV, gains, demandes)
  - [x] Bannière abonnement (rouge si inactif)
  - [x] Bannière approbation (jaune si diplôme en attente)
  - [x] Prochain RDV (carte avec adresse client + lien Maps)
  - [x] Planning du jour (liste chronologique)
  - [x] Demandes en attente (accepter/refuser)
  - [x] Complétude du profil (% calculé dynamiquement)
  - [x] Portefeuille solde disponible
  - [x] Actions rapides (catalogue, agenda, zones)
- [x] Route `?page=dashboard_coiffeur` dans `index.php`
- [x] Redirection post-connexion coiffeur → dashboard_coiffeur
- [x] Redirection post-inscription coiffeur → dashboard_coiffeur

**Phase 3B — Refactoring pages existantes**
- [ ] `coiffeurs/gestion_catalogue.php` — rebrand Dark/Gold (logique conservée)
- [ ] `coiffeurs/agenda_coiffeurs.php` — rebrand (logique conservée)
- [ ] `coiffeurs/valider_rendezvous.php` — rebrand (logique conservée)
- [ ] `coiffeurs/mes_zones.php` — rebrand (logique conservée)
- [ ] `coiffeurs/portefeuille.php` — intégré dans dashboard
- [ ] Navbar coiffeur commune (header partagé)

**Phase 3C — Profil public coiffeur**
- [ ] `coiffeurs/profil_public.php` — fix URL `?id_coiffeur=` vs `?id=`
- [ ] Calendrier interactif des créneaux (existant amélioré)
- [ ] Note moyenne + avis affichés

**🔜 Reporté après soutenance :**
- Statistiques complexes (graphiques, courbes)
- Messagerie client-coiffeur
- "Mes déplacements du jour" (carte interactive Google Maps)
- Navigation GPS temps réel

---

### 🚧 PHASE 4 — Module Client (EN COURS)
**Objectif : Réservation complète avec localisation**

- [x] `views/client/dashboard.php` — Homepage client premium ✔
- [ ] Page réservation `client/reserver.php` refactorisée :
  - [ ] Saisie localisation client (GPS + adresse manuelle)
  - [ ] Stockage dans `rendez_vous.client_gps_lat/lng`
  - [ ] Fix code en double
  - [ ] Suppression `SET FOREIGN_KEY_CHECKS=0`
- [ ] `client/mes_rendezvous.php` — rebrand Dark/Gold
- [ ] `filter/annuaire_coiffeurs.php` — support `?q=` recherche par nom
- [ ] Portefeuille client (solde + dépôt Kkiapay)

**🔜 Reporté après soutenance :**
- Système de favoris coiffeurs
- Filtres avancés dans l'annuaire

---

### 🚧 PHASE 5 — Module Admin
**Objectif : Contrôle total de la plateforme**

- [ ] Dashboard admin — KPIs globaux
- [ ] Validation diplômes prestataires
- [ ] Gestion plaintes et avis
- [ ] Vérification `is_approved` dans l'annuaire
- [ ] Bannissement de compte
- [ ] Statistiques financières (commissions)
- [ ] Layout admin dédié (sidebar pro)

---

### 🚧 PHASE 6 — Paiement Kkiapay
**Objectif : Paiement Mobile Money réel**

**Flux abonnement coiffeur :**
```
Coiffeur clique "Souscrire" → Kkiapay popup
→ Paiement Mobile Money
→ Webhook reçu → abonnement_status = 'actif'
→ Notification in-app "Abonnement activé"
```

**Flux portefeuille client :**
```
Client clique "Recharger" → Kkiapay popup
→ Paiement → users.solde += montant
→ Réservation → users.solde_gele += prix_rdv
→ RDV terminé → coiffeur reçoit paiement
```

- [ ] Compte Kkiapay + clés API dans `.env`
- [ ] `PaymentService.php` — SDK Kkiapay
- [ ] Webhook endpoint `payment/kkiapay_callback.php`
- [ ] Activation auto abonnement coiffeur
- [ ] Dépôt portefeuille client
- [ ] Tests sandbox Kkiapay

---

### 🚧 PHASE 7 — Notifications (Push Web + In-app)
**Objectif : Interpeller les utilisateurs même hors du site**

**Réponse à la question "est-on sûr que ça peut interpeller les users ?"**

Oui, avec deux niveaux :

**Niveau 1 — In-app (sûr à 100%)** :
- Cloche dans la navbar (déjà en place)
- Badge rouge sur l'icône
- Liste des 5 dernières notifs en dropdown
- Fonctionne si l'utilisateur est sur le site

**Niveau 2 — Push Notifications (Web Service Workers)** :
- Le navigateur demande la permission une fois
- Les notifications arrivent même si le site est fermé
- Fonctionne sur Android Chrome/Firefox
- Fonctionne sur desktop (Chrome, Edge, Firefox)
- **Limitation iOS** : Safari iOS < 16.4 ne supporte pas les Push Web
  → Solution palliative iOS : email de notification en fallback

**Table `push_subscriptions`** déjà dans la BDD `domizi`.

**Événements notifiés :**
```
ÉVÉNEMENT                   COIFFEUR  CLIENT   CANAL
Nouveau RDV créé            ✅ Push   ─        Push + in-app
RDV accepté                 ─         ✅ Push  Push + in-app
RDV annulé                  ✅        ✅       Push + in-app
Diplôme approuvé            ✅        ─        in-app
Abonnement expiré           ✅        ─        in-app (bannière)
Nouvel avis reçu            ✅        ─        in-app
Paiement reçu               ✅        ─        in-app
```

- [ ] `security/push_service_worker.js` — Service Worker
- [ ] `security/push_subscribe.php` — Endpoint souscription
- [ ] `security/push_send.php` — Envoi notifications
- [ ] VAPID keys dans `.env`
- [ ] Intégration dans pages dashboard (demande permission)
- [ ] Fallback email pour iOS

---

### 🚧 PHASE 8 — Avis & Notation
**Objectif : Système de confiance**

- [ ] Formulaire notation étoiles post-prestation (client)
- [ ] Plainte confidentielle si note < 3
- [ ] Alerte admin si plainte déposée
- [ ] Note moyenne affichée dans l'annuaire
- [ ] Dashboard coiffeur — section ses avis

---

### 🔜 PHASE 9 — Post-Soutenance
**Objectif : Fonctionnalités avancées**

- [ ] Statistiques complexes (graphiques revenus)
- [ ] Messagerie client-coiffeur
- [ ] "Mes déplacements du jour" (carte Google Maps)
- [ ] Navigation GPS temps réel
- [ ] Cron J+14 relance client (WhatsApp/Email)
- [ ] Module WhatsApp API
- [ ] Favoris coiffeurs
- [ ] Système de parrainage

---

### 🔜 PHASE 10 — Soutenance
**Objectif : Présentation professionnelle**

- [ ] Demo data complète (comptes test, prestations, RDV)
- [ ] Documentation technique
- [ ] Déploiement accessible (hébergement ou VPS)

---

### 🔜 PHASE 11 — Post-Soutenance (Lancement)
**Objectif : Mise en production**

- [ ] VPS + domaine `domizi.bj`
- [ ] SSL / HTTPS
- [ ] Migration BDD `cft` → `domizi`
- [ ] Extension sous-région (TG, CI, SN...)
- [ ] PWA mobile
- [ ] Application native (React Native ou Flutter)
- [ ] Hero section full-screen
- [ ] Glassmorphism sur les cartes
- [ ] Animations cubic-bezier
- [ ] Responsive Mobile-First parfait
- [ ] Page d'accueil immersive

---

### 🔜 PHASE 10 — Soutenance
**Objectif : Présentation professionnelle**

- [ ] Demo data complète
- [ ] Documentation technique
- [ ] Slides de présentation
- [ ] Déploiement accessible

---

### 🔜 PHASE 11 — Post-Soutenance
**Objectif : Lancement réel**

- [ ] VPS + domaine `domizi.bj`
- [ ] SSL / HTTPS
- [ ] Optimisation performances
- [ ] Extension sous-région (TG, CI, SN...)
- [ ] PWA mobile

---

## 🐛 BUGS HÉRITÉS

| Bug | Fichier | Gravité | Statut |
|---|---|---|---|
| Code en double | `reserver.php` | 🔴 Critique | ⏳ Sera corrigé Phase 4 |
| `$nom_clean` non défini | `inscription.php` | 🔴 Critique | ✅ Corrigé |
| URL `?id=` vs `?id_coiffeur=` | `annuaire → profil_public` | 🔴 Critique | ⏳ Phase 3 |
| `views/home.php` inexistant | `index.php` | 🔴 Critique | ✅ Créé |
| `SET FOREIGN_KEY_CHECKS=0` | `reserver.php` | 🔴 Sécurité | ⏳ Phase 4 |
| Rôle Admin dans inscription publique | `inscription.php` | 🔴 Sécurité | ✅ Corrigé |
| `quartier` vs `quartiers` | Plusieurs fichiers | 🟠 Important | ⏳ Phase 3 |

---

## 📏 RÈGLES D'OR DU PROJET

1. Un fichier = une responsabilité
2. Jamais de SQL dans les vues
3. Toujours valider les entrées côté serveur
4. CSRF sur tous les formulaires POST
5. Expliquer chaque concept avant de coder
6. Tester chaque module avant de passer au suivant

---

*Ce fichier est le point d'ancrage du projet Domizi.*
*Il sera mis à jour à chaque phase validée.*
