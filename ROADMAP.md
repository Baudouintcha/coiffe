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
- [ ] Créer le squelette MVC vide

---

### 🚧 PHASE 1 — Socle MVC
**Objectif : Le squelette qui fait tourner tout le reste**

- [ ] `composer.json` + autoloading PSR-4
- [ ] `.env` + `.env.example`
- [ ] `Core/Database.php` — Singleton PDO
- [ ] `Core/Router.php` — Dispatch des routes
- [ ] `Core/Request.php` — Encapsulation des entrées
- [ ] `Core/Response.php` — Redirections propres
- [ ] `Core/Session.php` — Gestion session
- [ ] `Core/View.php` — Rendu des vues
- [ ] `routes/web.php` — Définition des routes
- [ ] `.htaccess` — Redirection vers `public/index.php`
- [ ] Layout de base (header ghost + footer minimal)

---

### 🚧 PHASE 2 — Authentification
**Objectif : Inscription, connexion, sécurité**

- [ ] `LoginController.php`
- [ ] `RegisterController.php` (client + prestataire)
- [ ] `AuthMiddleware.php`
- [ ] `RoleMiddleware.php`
- [ ] `CsrfMiddleware.php`
- [ ] `AuthValidator.php`
- [ ] Suppression rôle Admin du formulaire public
- [ ] `UploadService.php` — Upload sécurisé
- [ ] Design connexion/inscription premium

---

### 🚧 PHASE 3 — Module Prestataire
**Objectif : Gérer profil, services et agenda**

- [ ] `ProfilController.php`
- [ ] `CatalogueController.php` — CRUD services
- [ ] `AgendaController.php` — Disponibilités
- [ ] Profil public du prestataire
- [ ] Calendrier interactif des créneaux
- [ ] Vues redesignées

---

### 🚧 PHASE 4 — Module Client
**Objectif : Trouver, réserver, gérer ses RDV**

- [ ] Annuaire des prestataires
- [ ] `ReservationController.php`
- [ ] `RendezVousController.php`
- [ ] `ReservationService.php` (règles 24h/2h/0h)
- [ ] Portefeuille réel (plus de mode test)
- [ ] Vues redesignées

---

### 🚧 PHASE 5 — Module Admin
**Objectif : Contrôle total de la plateforme**

- [ ] `DashboardController.php` — KPIs
- [ ] `ValidationController.php` — Approuver prestataires
- [ ] `ModerationController.php` — Plaintes et avis
- [ ] Vérification `is_approved` dans l'annuaire
- [ ] Bannissement de compte
- [ ] Statistiques financières
- [ ] Layout admin dédié

---

### 🚧 PHASE 6 — Paiement Kkiapay
**Objectif : Paiement Mobile Money réel**

- [ ] Compte Kkiapay + clés API
- [ ] `PaymentService.php`
- [ ] Webhook de confirmation
- [ ] Activation abonnement prestataire
- [ ] Dépôt portefeuille client
- [ ] Tests sandbox

---

### 🚧 PHASE 7 — Notifications & WhatsApp
**Objectif : Communication automatique**

- [ ] Choisir API WhatsApp
- [ ] `WhatsAppService.php`
- [ ] `NotificationService.php`
- [ ] Notifs RDV (prestataire + client)
- [ ] Reçu WhatsApp post-prestation
- [ ] Cron J+14 relance client

---

### 🚧 PHASE 8 — Avis & Notation
**Objectif : Système de confiance**

- [ ] Notation étoiles post-prestation
- [ ] Plainte confidentielle si note < 3
- [ ] Alerte admin sur plainte
- [ ] Note moyenne dans l'annuaire
- [ ] Dashboard prestataire — ses avis

---

### 🚧 PHASE 9 — Design Premium
**Objectif : Impact visuel fort**

- [ ] Charte graphique Dark/Gold
- [ ] Header "Ghost" scroll
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

## 🐛 BUGS HÉRITÉS (ne pas corriger — ils disparaissent avec la refonte)

| Bug | Fichier | Gravité |
|---|---|---|
| Code en double | `reserver.php` | 🔴 Critique |
| `$nom_clean` non défini | `inscription.php` | 🔴 Critique |
| URL `?id=` vs `?id_coiffeur=` | `annuaire → profil_public` | 🔴 Critique |
| `views/home.php` inexistant | `index.php` | 🔴 Critique |
| `SET FOREIGN_KEY_CHECKS=0` | `reserver.php` | 🔴 Sécurité |
| Rôle Admin dans inscription publique | `inscription.php` | 🔴 Sécurité |
| `quartier` vs `quartiers` | Plusieurs fichiers | 🟠 Important |

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
