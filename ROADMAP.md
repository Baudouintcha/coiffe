# 🗺️ DOMIZI — ROADMAP OFFICIELLE v2.0
> Plateforme de services à domicile — Bénin & Sous-région Ouest-Africaine
> Projet de soutenance + vision long terme
> Dernière mise à jour : Juillet 2026

---

## ✅ PHASE 3 — ASSISTANT IA MÉTIER (TERMINÉE)
> Date : 2025-07-21

- `ia_controlleur.php` enrichi : contexte métier complet par rôle (visiteur / client / coiffeur / admin)
- Données BDD injectées en temps réel dans le contexte Gemini (RDV, solde, stats plateforme, diplômes en attente)
- Correction requêtes SQL : `rendezvous` → `rendez_vous` (table réelle)
- Toutes les nouvelles requêtes SQL sont dans des blocs try/catch non-bloquants
- `ia_assistant.php` vérifié et aligné DS v2.0 : glassmorphism, backdrop-filter, police Inter, animation slide-in, typing dots, close button amélioré, responsive mobile

---

## ✅ PHASE 5 — AVIS & RÉPUTATION (TERMINÉE)
> Date : 2025-07-20

- Note moyenne + nb avis dans l'annuaire (requête optimisée via sous-requête scalaire dans la SELECT principale)
- `profil_public.php` : section 5 derniers avis publics avec étoiles, avatar initiale, commentaire, date relative
- `views/coiffeur/dashboard.php` : section "Mes avis récents" dans la colonne latérale (3 avis + badge note globale)

## ✅ PHASE 6 — PORTEFEUILLE & PAIEMENT SIMULATION (TERMINÉE)
> Date : 2025-07-20

- `payment/PaymentService.php` : couche d'abstraction complète (recharger, geler, activerAbonnement, squelette webhook)
- `payment/kkiapay_callback.php` : squelette webhook documenté (prêt pour prod post-soutenance)
- `payment/simuler_abonnement.php` : page souscription abonnement simulation (1 500 FCFA, solde affiché, confirmation CSRF)

---

## ✅ PHASE 1 — STABILISATION TECHNIQUE (TERMINÉE)
> Date : 2025-07-14

### Corrections effectuées — Coiffe Chez Toi V1.0

1. **Audit imports CSS obsolètes** — Aucune référence active à `css/global.css`, `css/responsive-custom.css`, `css/style.css`, `layout/header.php` ou `layout/footer.php` trouvée dans les fichiers PHP (présences uniquement dans des commentaires documentaires — aucune action nécessaire).

2. **`layout/header_coiffeur.php` — suppression `.btn-gold-cct`** — La définition CSS de la classe `.btn-gold-cct` (interdite en DS v2.0) a été supprimée du bloc `<style>` interne. Remplacée par un commentaire renvoyant vers `.btn-gold` du DS v2.0 (`css/components.css`).

3. **`views/coiffeur/dashboard.php` — bug requête complétude** — Corrigé : `WHERE prestataire_id = ?` → `WHERE coiffeur_id = ?` dans la requête de vérification des disponibilités du calcul de complétude du profil.

4. **`coiffeurs/sauvegarder_agenda.php` — vérification** — Fichier conforme : utilise `coiffeur_id` dans toutes ses requêtes SQL, dépendances présentes (`security/config.php`), logique transactionnelle correcte.

5. **`client/traiter_recharge.php` — création** — Fichier créé (manquant). Gère la recharge simulée du portefeuille client : validation du montant (500 – 500 000 FCFA), mise à jour du solde via `UPDATE users SET solde = solde + ?`, insertion dans `transactions_portefeuille`, gestion des erreurs par rollback, flash messages session.

6. **`client/reserver.php` — suppression `SET FOREIGN_KEY_CHECKS`** — Supprimé le bloc de nettoyage FK (`SET FOREIGN_KEY_CHECKS = 0/1`, `ALTER TABLE DROP FOREIGN KEY`, `DROP INDEX`) qui ne doit pas s'exécuter à chaque réservation.

7. **`access/inscription.php` — redirections post-inscription** — Vérifiées et conformes : client → `/coiffons/index.php?page=dashboard`, coiffeur → `/coiffons/index.php?page=dashboard_coiffeur`. Aucune modification nécessaire.

---

## ✅ PHASE 4 — NOTIFICATIONS COMPLÈTES (TERMINÉE)
> Date : 2025-07-23

- `client/notifications.php` créé — centre de notifications complet (LIMIT 50, mark-all-read à l'ouverture, icônes colorées par type, dates relatives, bouton Retour)
- `security/cron_notifications.php` créé — notifications automatiques (RDV demain, abonnement expirant, RDV du jour, diplômes en attente)
- `security/notification_events.php` créé — architecture extensible (constantes NOTIF_*, `notifier_evenement()`, email/push/SMS/WhatsApp ready)
- `coiffeurs/valider_rendezvous.php` : notifications acceptation/refus via helper `notifier()` centralisé (messages enrichis avec date/heure)

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

### ✅ PHASE 2 — PARCOURS UTILISATEUR (TERMINÉE)
> Date : 2025-07-15

### Corrections effectuées — Coiffe Chez Toi V1.0 — Parcours Complet

1. **`filter/annuaire_coiffeurs.php`** — Ajout `AND u.is_approved = 1` dans la requête SQL principale + support du paramètre `?q=` (recherche par nom/prénom) avec champ `<input type="text" name="q">` dans le formulaire.

2. **`coiffeurs/profil_public.php`** — Vérification effectuée : la note moyenne et le nombre d'avis sont déjà calculés depuis la vraie BDD via `AVG(note)` sur la table `commentaires`. Aucune modification nécessaire.

3. **`client/mes_rendezvous.php`** — Ajout d'un bouton "Laisser un avis" pour tous les RDV avec `statut_rdv = 'termine'`, pointant vers `/coiffons/client/laisser_avis.php?id_rdv=X&id_coiffeur=Y`. Ajouté dans la vue tableau (desktop) et dans les cartes mobile.

4. **`client/laisser_avis.php`** — Correction du paramètre GET (`?rdv=` ou `?id_rdv=` désormais acceptés) + correction de la requête de sécurité (`r.coiffeur_id` au lieu de `r.id_coiffeur`) + vérification anti-doublon intégrée dans la requête SQL (JOIN `commentaires c ON c.id IS NULL`).

5. **`traitement_avis.php`** — Refactoring complet : `$_SESSION['id_client']` → `$_SESSION['id_user']`, suppression de la connexion PDO locale au profit de `security/config.php`, ajout vérification anti-doublon explicite avant INSERT, vérification que le RDV appartient au client, redirection vers `mes_rendezvous.php` (fichier existant).

6. **`access/inscription.php`** — Exploitation de `$_SESSION['redirect_url']` après inscription client : si une URL de redirection est présente en session (ex. page de réservation), l'utilisateur est redirigé vers cette page après inscription, puis la clé est supprimée.

7. **`client/reserver.php`** — Ajout d'un INSERT dans `notifications` après création du RDV pour notifier le coiffeur : `INSERT INTO notifications (id_user, message, type, statut_lecture, date_notification) VALUES (id_coiffeur, 'Nouvelle demande...', 'info', 'non_lu', NOW())`. Non-bloquant (try/catch).

8. **`client/creer_rendezvous.php`** — Même notification ajoutée après l'INSERT du RDV. Non-bloquante.

---

- [x] Hero annuaire réduit (35vh glassmorphism)
- [x] Commentaires logo dans dashboard + navbar
- [ ] Fix : redirection post-inscription pointe encore `/coiffons/index.php` au lieu de `?page=dashboard` → à vérifier selon config BDD active
- [ ] Annuaire — support paramètre `?q=` pour la recherche par nom

---

## ✅ PHASE 3 — ASSISTANT IA MÉTIER (TERMINÉE)
> Date : 2025-07-16

### Améliorations effectuées — `ia_controlleur.php` v2.0

1. **Contexte enrichi par rôle** — 4 blocs distincts (visiteur, client, coiffeur, admin) avec données BDD injectées en temps réel dans le prompt système.
2. **Visiteur** — liste des 6 derniers coiffeurs actifs avec liens cliquables vers leurs profils publics + détection intention recherche coiffeur.
3. **Client** — solde portefeuille, 3 derniers RDV avec statuts et liens profils coiffeurs, compteur RDV en attente, détection intentions (RDV, solde, réservation).
4. **Coiffeur** — statut abonnement + date expiration, solde, planning du jour, demandes en attente, nombre de services catalogue, détection intentions (RDV, finances, abonnement, catalogue).
5. **Admin** — KPIs temps réel : nombre users/clients/coiffeurs actifs-inactifs, RDV en attente, diplômes en attente, liens back-office.
6. **Liens Markdown** — l'IA génère des liens `[texte](url)` convertis en HTML cliquable par le JS (`markdownToHtml()`).
7. **`ia_assistant.php`** — rendu Markdown (liens, gras, listes) + suggestions rapides contextuelles par rôle (3 boutons pré-remplis selon visiteur/client/coiffeur/admin).

### Fichiers modifiés
- `ia_controlleur.php` — refonte complète v2.0
- `views/components/ia_assistant.php` — Markdown renderer + suggestions rapides

---
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
- [x] `layout/header_coiffeur.php` — Navbar Dark/Gold partagée créée
- [x] `layout/footer_coiffeur.php` — Footer minimal créé
- [x] `coiffeurs/gestion_catalogue.php` — rebrand Dark/Gold ✔
- [x] `coiffeurs/agenda_coiffeurs.php` — rebrand ✔
- [x] `coiffeurs/valider_rendezvous.php` — rebrand Dark/Gold ✔
- [x] `coiffeurs/mes_zones.php` — rebrand Dark/Gold ✔
- [x] `coiffeurs/portefeuille.php` — rebrand Dark/Gold ✔

**Phase 3C — Profil public coiffeur**
- [x] `coiffeurs/profil_public.php` — fix URL `?id=` + `?id_coiffeur=` acceptés
- [x] Requêtes SQL optimisées (AVG direct, filtrage par ID coiffeur)
- [x] Header/footer standalone premium (sans ancien layout)
- [x] Note moyenne + avis affichés depuis vraies données BDD
- [ ] Calendrier créneaux avec intervalles configurables

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
- [x] `client/mes_rendezvous.php` — bouton "Laisser un avis" post-RDV terminé
- [x] `filter/annuaire_coiffeurs.php` — support `?q=` recherche par nom + filtre `is_approved`
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

## ✅ PHASE 5 — AVIS & RÉPUTATION (TERMINÉE)
> Date : 2025-07-16

### Implémentations effectuées

1. **`traitement_avis.php`** — Notifications post-avis ajoutées :
   - Notification au coiffeur après chaque avis soumis (★★★★★ incluses dans le message)
   - Alerte admin automatique si note ≤ 2 via `notifier_admins()` (helper `security/notifications.php`)

2. **`client/mes_rendezvous.php`** — Flash messages branchés :
   - Lecture de `$_SESSION['success']` et `$_SESSION['error']` en début de page
   - Affichage via toast DS v2.0 après redirection depuis `traitement_avis.php`

3. **`coiffeurs/mes_avis.php`** — Page créée :
   - Score global avec note affichée en grand (format "4.7 / 5")
   - Distribution graphique des notes (barres colorées : gold ≥4, warning =3, danger ≤2)
   - Liste des 20 derniers avis avec prénom masqué (K***), date relative, étoiles
   - Empty state si aucun avis

4. **`views/coiffeur/dashboard.php`** — Lien "Mes Avis" ajouté dans la sidebar

5. **`layout/header_coiffeur.php`** — Lien "Avis" ajouté dans le bottom nav mobile

6. **`filter/annuaire_coiffeurs.php`** — Note moyenne et nombre d'avis déjà affichés sur chaque carte (Phase 2)

7. **`coiffeurs/profil_public.php`** — Section avis publics déjà implémentée avec date relative et prénom masqué (Phase 2)

### Cycle complet validé
```
RDV terminé → client reçoit bouton "Laisser un avis"
→ laisser_avis.php (star rating + commentaire + signalement optionnel)
→ traitement_avis.php (anti-doublon + INSERT commentaires + notification coiffeur + alerte admin si note ≤ 2)
→ note moyenne recalculée en temps réel dans annuaire + profil public
→ coiffeur consulte ses avis sur mes_avis.php
```

---

### ✅ PHASE 6 — Portefeuille & Paiement simulé (TERMINÉE — voir entrée ci-dessus)
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

## ✅ PHASE 7 — ADMINISTRATION (TERMINÉE)
> Date : 2025-07-17

### Corrections `admin_dashboard.php`
- Toutes les requêtes utilisant `transactions_site` et `abonnements_paiements` (tables obsolètes) remplacées par les vraies tables : `transactions_portefeuille`, `users`, `commentaires`
- Requêtes wrappées dans `try/catch` — plus aucune erreur fatale si une table manque
- `$diplomes_attente` — nouvelle requête pour les coiffeurs non approuvés avec diplôme uploadé
- `$nb_notifs_admin` — compteur notifications admin non lues
- `$abonnements_attente` — basé sur `users.abonnement_status` (table réelle)

### Nouvelles actions `admin_actions.php`
- `ACTION 10 : approuver_diplome` — `UPDATE users SET is_approved = 1` + notification coiffeur
- `ACTION 11 : refuser_diplome` — notification coiffeur avec demande de resoumission

### 🚧 PHASE 8 — DONNÉES DE DÉMONSTRATION (À FAIRE)


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
