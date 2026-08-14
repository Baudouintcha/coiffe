Oui. Voici **un seul bloc complet**, prêt à copier directement dans `ROADMAP.md`. J’ai gardé l’énergie et la vision de ta première version, tout en intégrant l’état réel du projet de la deuxième version, avec une logique qui permettra à ton éditeur de code de savoir précisément **ce qui est terminé, en cours et à faire**.

````markdown
# 🗺️ DOMIZI — ROADMAP OFFICIELLE MASTER v3.0

> **Plateforme de services à domicile — Bénin & Sous-région Ouest-Africaine**
> **Projet de soutenance + vision produit long terme**
> **Document de référence unique du projet**
> **Dernière mise à jour : Août 2026**

---

# 🎯 1. VISION DU PROJET

**Domizi** est une marketplace de services à domicile pensée pour le Bénin et, à terme, pour l'Afrique de l'Ouest.

L'idée centrale est simple :

> **Permettre à un utilisateur de trouver un professionnel qualifié, disponible dans sa zone, de consulter ses services, choisir un créneau et réserver une prestation directement depuis Domizi.**

Domizi n'est pas limité à la coiffure.

La plateforme est conçue comme une **marque mère** pouvant accueillir plusieurs verticales spécialisées.

## 🌍 Écosystème Domizi

| Branche | Domaine | Statut |
|---|---|---|
| **Coiffe Chez Toi** | 💇 Beauté & Coiffure | 🟢 Produit principal / soutenance |
| **Glam Chez Toi** | 💄 Maquillage & Ongles | 🔜 Après soutenance |
| **Fix Chez Toi** | 🔧 Plomberie & Électricité | 🔜 Futur |
| **Clean Chez Toi** | 🧹 Ménage & Nettoyage | 🔜 Futur |

## Vision à long terme

L'objectif n'est pas simplement de créer un site de réservation de coiffeurs.

L'objectif est de construire progressivement une **marketplace de services à domicile multi-métiers**, capable de s'étendre à plusieurs pays d'Afrique de l'Ouest.

```text
                         DOMIZI
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
 COIFFE CHEZ TOI     GLAM CHEZ TOI       FIX CHEZ TOI
 Coiffure            Beauté              Maison
        │
        ▼
 Première verticale
 pleinement opérationnelle
        │
        ▼
 Expansion progressive
 à d'autres métiers
        │
        ▼
 Afrique de l'Ouest
````

---

# 💡 2. PRODUIT ACTUEL : COIFFE CHEZ TOI

**Coiffe Chez Toi** constitue la première verticale métier de Domizi.

Elle permet de construire le parcours principal de la plateforme :

```text
Utilisateur
    │
    ▼
Domizi
    │
    ▼
Beauté
    │
    ▼
Coiffe Chez Toi
    │
    ├── Visiteur
    │      ├── Découvrir les coiffeurs
    │      ├── Rechercher
    │      ├── Voir un profil
    │      └── Se connecter / s'inscrire
    │
    ├── Client
    │      ├── Rechercher
    │      ├── Consulter un profil
    │      ├── Choisir une prestation
    │      ├── Choisir date / heure
    │      ├── Réserver
    │      ├── Suivre ses RDV
    │      ├── Recevoir des notifications
    │      └── Laisser un avis
    │
    └── Coiffeur
           ├── Gérer son profil
           ├── Gérer ses services
           ├── Gérer ses disponibilités
           ├── Gérer ses RDV
           ├── Gérer ses zones
           ├── Consulter ses avis
           └── Gérer son portefeuille
```

---

# 🧭 3. ÉTAT GLOBAL ACTUEL DU PROJET

## 🟢 État général

Le projet **n'est plus au stade de fondation**.

Les fondations MVC, le portail Domizi, l'authentification, les parcours client et coiffeur, l'annuaire, les réservations, les avis, les notifications, l'administration, le portefeuille simulé, l'OTP et l'assistant IA sont déjà implémentés.

Le projet est actuellement dans une phase de **finalisation, stabilisation et préparation à la soutenance**.

## État actuel

| Domaine                  | État               |
| ------------------------ | ------------------ |
| Vision Domizi            | 🟢 TERMINÉ         |
| Architecture MVC         | 🟢 TERMINÉ         |
| Portail Domizi           | 🟢 TERMINÉ         |
| Authentification         | 🟢 TERMINÉ         |
| OTP                      | 🟢 TERMINÉ         |
| Parcours visiteur        | 🟢 TERMINÉ         |
| Parcours client          | 🟢 TERMINÉ         |
| Parcours coiffeur        | 🟢 TERMINÉ         |
| Annuaire                 | 🟢 TERMINÉ         |
| Recherche coiffeur       | 🟢 TERMINÉ         |
| Profils publics          | 🟢 TERMINÉ         |
| Catalogue prestations    | 🟢 TERMINÉ         |
| Disponibilités / agenda  | 🟢 TERMINÉ         |
| Réservations             | 🟢 FONCTIONNEL     |
| Gestion des RDV          | 🟢 TERMINÉ         |
| Avis & réputation        | 🟢 TERMINÉ         |
| Notifications in-app     | 🟢 TERMINÉ         |
| Administration           | 🟢 TERMINÉ         |
| Portefeuille simulé      | 🟢 TERMINÉ         |
| Abonnement simulé        | 🟢 TERMINÉ         |
| Assistant IA métier      | 🟢 TERMINÉ         |
| Paiement Kkiapay réel    | 🟡 À FINALISER     |
| Push notifications       | 🟡 À FINALISER     |
| WhatsApp                 | ⚪ À FAIRE          |
| Données de démonstration | ⚪ À FAIRE          |
| Préparation soutenance   | 🟡 EN COURS        |
| Statistiques avancées    | 🔵 POST-SOUTENANCE |
| Messagerie               | 🔵 POST-SOUTENANCE |
| GPS avancé               | 🔵 POST-SOUTENANCE |
| Nouveaux métiers         | 🔵 POST-SOUTENANCE |
| Expansion régionale      | 🔵 POST-SOUTENANCE |

---

# 🏗️ 4. ARCHITECTURE TECHNIQUE

## Architecture cible

Domizi utilise une architecture PHP native structurée autour d'un modèle MVC.

```text
domizi/
├── public/
│   ├── index.php
│   ├── css/
│   ├── js/
│   └── uploads/
│
├── src/
│   ├── Core/
│   │   ├── Database.php
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Session.php
│   │   └── View.php
│   │
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── Auth/
│   │   ├── Prestataire/
│   │   ├── Client/
│   │   └── Admin/
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Service.php
│   │   ├── RendezVous.php
│   │   ├── Notification.php
│   │   ├── Avis.php
│   │   └── Transaction.php
│   │
│   ├── Middlewares/
│   ├── Services/
│   └── Validators/
│
├── views/
│   ├── layouts/
│   ├── auth/
│   ├── prestataire/
│   ├── client/
│   └── admin/
│
├── routes/
├── config/
├── .env
├── .env.example
├── composer.json
└── .htaccess
```

## Stack technique

* PHP natif
* Architecture MVC
* Composer / PSR-4
* MySQL / MariaDB
* Bootstrap 5.3
* CSS Premium
* JavaScript
* XAMPP en développement
* VPS en production
* Kkiapay pour les paiements
* Gemini pour l'assistant IA

---

# 🗄️ 5. BASE DE DONNÉES DOMIZI

## Tables principales

| Table                  | Fonction                 | État |
| ---------------------- | ------------------------ | ---- |
| `pays`                 | Gestion des pays         | 🟢   |
| `villes`               | Gestion des villes       | 🟢   |
| `quartiers`            | Gestion des quartiers    | 🟢   |
| `domaines`             | Domaines métiers         | 🟢   |
| `metiers`              | Métiers                  | 🟢   |
| `users`                | Utilisateurs             | 🟢   |
| `profils_prestataires` | Profils professionnels   | 🟢   |
| `zones_prestataire`    | Zones d'intervention     | 🟢   |
| `services`             | Catalogue de prestations | 🟢   |
| `disponibilites`       | Disponibilités           | 🟢   |
| `rendez_vous`          | Réservations             | 🟢   |
| `avis`                 | Avis / réputation        | 🟢   |
| `transactions`         | Transactions financières | 🟢   |
| `paiements_kkiapay`    | Paiements Kkiapay        | 🟢   |
| `notifications`        | Notifications            | 🟢   |
| `ia_sessions`          | Sessions assistant IA    | 🟢   |

## Anciennes structures

Les anciennes structures suivantes sont considérées comme obsolètes et ne doivent pas être recréées sans justification :

```text
catalogue
    → services

commentaires
    → avis

prestations
    → services

portefeuilles
    → users.solde

transactions_portefeuille
    → transactions

transactions_site
    → transactions

plaintes
    → avis.type = 'plainte'

abonnements_paiements
    → paiements_kkiapay

boutique
    → hors scope
```

---

# 🚀 6. PHASES DE DÉVELOPPEMENT

---

## ✅ PHASE 0 — FONDATIONS

**Statut : TERMINÉE**

### Objectif

Poser les bases du projet Domizi.

* [x] Analyse du projet existant
* [x] Choix de l'architecture MVC
* [x] Nom du projet : **Domizi**
* [x] Vision multi-métiers
* [x] Nouvelle BDD Domizi
* [x] ROADMAP
* [x] Composer
* [x] Squelette MVC

---

# ✅ PHASE 1 — SOCLE MVC

**Statut : TERMINÉE**

* [x] `composer.json`
* [x] Autoloading PSR-4
* [x] `Database.php`
* [x] `Router.php`
* [x] `Session.php`
* [x] `View.php`
* [x] `Lang.php`
* [x] `routes/web.php`
* [x] `app.php`
* [x] `public/index.php`
* [x] `.htaccess`
* [x] Gestion des erreurs 404

---

# ✅ PHASE 1B — PORTAIL DOMIZI

**Statut : TERMINÉE**

* [x] Portail Domizi
* [x] Présentation des domaines
* [x] `DomiziController.php`
* [x] `views/domizi/home.php`
* [x] API AJAX métiers
* [x] Sélection du domaine
* [x] Dropdown langue
* [x] Traductions FR / EN / ES / AR

---

# ✅ PHASE 2 — COIFFE CHEZ TOI + AUTHENTIFICATION

**Statut : TERMINÉE**

### Homepage

* [x] Homepage Coiffe Chez Toi
* [x] Hero premium
* [x] Carousel
* [x] Navigation
* [x] Accès inscription client
* [x] Accès inscription coiffeur
* [x] Accès connexion

### Inscription

* [x] Inscription client
* [x] Inscription coiffeur
* [x] Rôle pré-déterminé
* [x] Validation serveur
* [x] CSRF
* [x] Upload photo profil
* [x] Upload diplôme coiffeur
* [x] Validation MIME réelle
* [x] Compression image côté client
* [x] Ville / quartier
* [x] Protection retour arrière
* [x] Redirections selon rôle
* [x] Suppression du rôle admin du formulaire public

### Connexion

* [x] Connexion premium
* [x] CSRF
* [x] Protection brute force
* [x] Vérification compte banni
* [x] Redirection client
* [x] Redirection coiffeur

### OTP

* [x] Système OTP
* [x] Vérification OTP
* [x] Intégration dans les parcours nécessitant une vérification
* [x] Gestion de l'interface de saisie OTP

---

# ✅ PHASE 3 — PARCOURS COIFFEUR

**Statut : TERMINÉE**

## Dashboard coiffeur

* [x] Dashboard premium
* [x] Sidebar desktop
* [x] Bottom navigation mobile
* [x] Statistiques essentielles
* [x] Prochains rendez-vous
* [x] Planning du jour
* [x] Demandes en attente
* [x] Complétude du profil
* [x] Portefeuille
* [x] Actions rapides
* [x] Bannière abonnement
* [x] Bannière approbation diplôme

## Catalogue

* [x] Gestion des prestations
* [x] Création
* [x] Modification
* [x] Suppression
* [x] Affichage public

## Agenda

* [x] Disponibilités
* [x] Gestion des horaires
* [x] Sauvegarde agenda
* [x] Gestion des créneaux

## Rendez-vous

* [x] Liste des demandes
* [x] Acceptation
* [x] Refus
* [x] Validation
* [x] Notifications

## Zones

* [x] Gestion des zones d'intervention
* [x] Ville
* [x] Quartier

## Profil public

* [x] Profil public
* [x] Informations professionnelles
* [x] Services
* [x] Note moyenne
* [x] Nombre d'avis
* [x] Avis publics

---

# ✅ PHASE 4 — PARCOURS CLIENT

**Statut : TERMINÉE / FONCTIONNELLE**

## Dashboard

* [x] Dashboard client
* [x] Recherche coiffeur
* [x] Coiffeurs disponibles
* [x] Matching géographique
* [x] Affichage ville / quartier
* [x] Interface mobile

## Annuaire

* [x] Liste des coiffeurs
* [x] Recherche par nom / prénom
* [x] Filtre ville
* [x] Filtre quartier
* [x] Filtre prix
* [x] Vérification `is_approved`
* [x] Note moyenne
* [x] Nombre d'avis

## Profil public

* [x] Consultation profil
* [x] Services
* [x] Tarifs
* [x] Disponibilités
* [x] Avis
* [x] Informations de localisation

## Réservation

* [x] Choix prestation
* [x] Choix date
* [x] Choix heure
* [x] Vérification disponibilité
* [x] Création RDV
* [x] Notification coiffeur
* [x] Redirection après inscription
* [x] Rendez-vous express
* [x] Gestion des créneaux
* [x] Localisation client

## Mes rendez-vous

* [x] Liste des RDV
* [x] Statuts
* [x] Détails
* [x] Annulation
* [x] Bouton laisser un avis après RDV terminé

---

# ✅ PHASE 5 — AVIS & RÉPUTATION

**Statut : TERMINÉE**

* [x] Notation étoiles
* [x] Commentaire
* [x] Avis public
* [x] Note moyenne
* [x] Nombre d'avis
* [x] Anti-doublon
* [x] Vérification que le RDV appartient au client
* [x] Notification coiffeur
* [x] Alerte admin pour notes faibles
* [x] Page `mes_avis.php`
* [x] Avis récents dashboard
* [x] Avis dans profil public
* [x] Avis dans annuaire

---

# ✅ PHASE 6 — NOTIFICATIONS

**Statut : TERMINÉE POUR L'IN-APP**

## Notifications actuellement disponibles

* [x] Nouveau RDV
* [x] RDV accepté
* [x] RDV refusé
* [x] RDV annulé
* [x] Diplôme approuvé / refusé
* [x] Abonnement
* [x] Nouvel avis
* [x] Notifications administrateur
* [x] Centre de notifications
* [x] Marquage lu / non lu
* [x] Cron notifications

## Push Web

**Statut : 🟡 À FINALISER**

* [ ] Service Worker
* [ ] Souscription push
* [ ] VAPID
* [ ] Envoi push
* [ ] Demande de permission
* [ ] Intégration dashboards
* [ ] Fallback email

---

# ✅ PHASE 7 — ADMINISTRATION

**Statut : TERMINÉE**

* [x] Dashboard admin
* [x] KPIs
* [x] Nombre utilisateurs
* [x] Nombre clients
* [x] Nombre coiffeurs
* [x] RDV en attente
* [x] Diplômes en attente
* [x] Validation diplôme
* [x] Refus diplôme
* [x] Notification coiffeur
* [x] Approbation `is_approved`
* [x] Bannissement compte
* [x] Gestion avis
* [x] Gestion plaintes
* [x] Notifications admin
* [x] Gestion abonnements

---

# ✅ PHASE 8 — PORTEFEUILLE & PAIEMENT SIMULÉ

**Statut : TERMINÉE**

## Portefeuille

* [x] Solde client
* [x] Solde coiffeur
* [x] Recharge simulée
* [x] Transactions
* [x] Gestion du solde
* [x] Gel du montant
* [x] Paiement après prestation

## Abonnement

* [x] Souscription simulée
* [x] Solde affiché
* [x] Confirmation CSRF
* [x] Activation abonnement

## Architecture paiement

* [x] `PaymentService.php`
* [x] Structure Kkiapay
* [x] Callback préparé

## Kkiapay réel

**Statut : 🟡 À FINALISER**

* [ ] Compte Kkiapay production
* [ ] Clés API
* [ ] SDK
* [ ] Webhook réel
* [ ] Activation automatique abonnement
* [ ] Recharge réelle portefeuille
* [ ] Tests sandbox
* [ ] Tests production

---

# ✅ PHASE 9 — ASSISTANT IA MÉTIER

**Statut : TERMINÉE**

L'assistant IA adapte son contexte au rôle de l'utilisateur.

## Visiteur

* [x] Recherche coiffeur
* [x] Coiffeurs actifs
* [x] Liens profils publics

## Client

* [x] Solde
* [x] RDV
* [x] Statuts
* [x] Intentions de réservation
* [x] Intentions financières

## Coiffeur

* [x] Abonnement
* [x] Solde
* [x] Planning
* [x] Demandes
* [x] Catalogue

## Admin

* [x] KPIs
* [x] Utilisateurs
* [x] RDV
* [x] Diplômes
* [x] Informations administratives

## Interface IA

* [x] Suggestions contextuelles
* [x] Markdown
* [x] Liens cliquables
* [x] Typing indicator
* [x] Responsive
* [x] Design glassmorphism

---

# 🟡 PHASE 10 — FINALISATION TECHNIQUE

**Statut : PROCHAINE GRANDE ÉTAPE**

### Objectif

Passer d'un projet fonctionnel à un projet **propre, stable, cohérent et prêt pour la soutenance**.

## Nettoyage

* [ ] Nettoyer CSS
* [ ] Supprimer code mort
* [ ] Supprimer anciennes références
* [ ] Vérifier anciens layouts
* [ ] Vérifier anciennes classes CSS
* [ ] Vérifier anciennes tables
* [ ] Vérifier anciennes requêtes

## Sécurité

* [ ] Vérifier CSRF
* [ ] Vérifier sessions
* [ ] Vérifier permissions
* [ ] Vérifier uploads
* [ ] Vérifier SQL
* [ ] Vérifier injections
* [ ] Vérifier accès direct aux fichiers sensibles

## Fonctionnel

* [ ] Tester inscription
* [ ] Tester connexion
* [ ] Tester OTP
* [ ] Tester recherche
* [ ] Tester profil
* [ ] Tester catalogue
* [ ] Tester agenda
* [ ] Tester réservation
* [ ] Tester annulation
* [ ] Tester avis
* [ ] Tester notifications
* [ ] Tester admin
* [ ] Tester portefeuille

## Responsive

* [ ] Mobile
* [ ] Tablette
* [ ] Desktop

---

# 🟡 PHASE 11 — DONNÉES DE DÉMONSTRATION

**Statut : À FAIRE**

Créer un environnement de démonstration réaliste.

* [ ] Comptes clients
* [ ] Comptes coiffeurs
* [ ] Compte admin
* [ ] Prestations
* [ ] Disponibilités
* [ ] RDV
* [ ] Avis
* [ ] Notifications
* [ ] Transactions
* [ ] Villes
* [ ] Quartiers
* [ ] Diplômes
* [ ] Données permettant de démontrer l'administration

---

# 🟡 PHASE 12 — PRÉPARATION SOUTENANCE

**Statut : À FAIRE**

## Démonstration

Le scénario principal doit être :

```text
Domizi
   ↓
Beauté
   ↓
Coiffe Chez Toi
   ↓
Recherche coiffeur
   ↓
Profil
   ↓
Choix prestation
   ↓
Date / heure
   ↓
Réservation
   ↓
Notification coiffeur
   ↓
Acceptation
   ↓
Rendez-vous
   ↓
Fin prestation
   ↓
Avis
   ↓
Note du coiffeur mise à jour
```

## Livrables

* [ ] Documentation technique
* [ ] Documentation utilisateur
* [ ] Slides
* [ ] Scénario de démonstration
* [ ] Comptes de démonstration
* [ ] Données de démonstration
* [ ] Version stable
* [ ] Déploiement accessible

---

# 🔵 PHASE 13 — POST-SOUTENANCE

**Statut : REPORTÉE**

## Paiements réels

* [ ] Kkiapay production
* [ ] Mobile Money
* [ ] Webhooks
* [ ] Sécurisation financière

## Communication

* [ ] WhatsApp API
* [ ] Email transactionnel
* [ ] Push notifications
* [ ] Relances automatiques
* [ ] Cron J+14

## Fonctionnalités avancées

* [ ] Messagerie client / coiffeur
* [ ] Favoris
* [ ] Parrainage
* [ ] Statistiques avancées
* [ ] Graphiques
* [ ] Google Maps
* [ ] GPS
* [ ] Navigation temps réel

---

# 🔵 PHASE 14 — NOUVEAUX MÉTIERS

**Statut : VISION LONG TERME**

Une fois Coiffe Chez Toi stabilisé :

```text
                    DOMIZI
                      │
          ┌───────────┼───────────┐
          │           │           │
          ▼           ▼           ▼
   Coiffe Chez Toi  Glam Chez Toi  Fix Chez Toi
          │
          ▼
      Clean Chez Toi
          │
          ▼
    Nouveaux métiers
```

Chaque nouvelle verticale doit réutiliser autant que possible :

* authentification ;
* utilisateurs ;
* géolocalisation ;
* réservation ;
* notifications ;
* paiements ;
* avis ;
* IA ;
* administration.

---

# 🌍 PHASE 15 — EXPANSION RÉGIONALE

**Statut : VISION LONG TERME**

Après validation du modèle au Bénin :

* [ ] Togo
* [ ] Côte d'Ivoire
* [ ] Sénégal
* [ ] Autres pays d'Afrique de l'Ouest

L'architecture doit permettre :

```text
Pays
   ↓
Ville
   ↓
Quartier
   ↓
Zone
   ↓
Prestataire
   ↓
Service
   ↓
Réservation
```

---

# 🎨 16. IDENTITÉ VISUELLE

Domizi doit conserver une identité :

> **Premium — moderne — africaine — technologique — accessible.**

## Direction artistique

* Dark / Gold
* Glassmorphism
* Images immersives
* Hero full-screen lorsque pertinent
* Animations fluides
* Responsive mobile-first
* Interfaces simples
* Hiérarchie visuelle forte

## Principe

> **Premium ne signifie pas compliqué.**

Chaque interface doit privilégier :

```text
Clarté
   ↓
Hiérarchie
   ↓
Action principale
   ↓
Élégance
```

Les éléments décoratifs ou graphiques qui alourdissent inutilement une page doivent être supprimés.

---

# 📍 17. LOCALISATION

Domizi est pensé autour d'une logique géographique :

```text
Pays
  ↓
Ville
  ↓
Quartier
  ↓
Zone d'intervention
  ↓
Prestataire
```

Les interfaces doivent afficher les **noms humains** plutôt que les IDs techniques.

### Mauvais

```text
ville_id = 4
quartier_id = 17
```

### Correct

```text
Cotonou
Fidjrossè
```

Cette règle s'applique notamment :

* dashboard client ;
* dashboard coiffeur ;
* annuaire ;
* profil public ;
* administration ;
* zones d'intervention.

---

# 🔐 18. RÈGLES D'OR DU PROJET

1. Un fichier = une responsabilité.
2. Jamais de SQL inutile dans les vues.
3. Toujours valider les entrées côté serveur.
4. CSRF obligatoire sur les formulaires POST.
5. Ne jamais exposer de données sensibles.
6. Ne jamais recréer une fonctionnalité déjà terminée.
7. Ne jamais réintroduire une ancienne table supprimée sans justification.
8. Préserver les fonctionnalités existantes lors d'une modification.
9. Tester chaque parcours après modification.
10. Toute nouvelle fonctionnalité doit être ajoutée à cette roadmap.
11. Toute fonctionnalité supprimée doit être signalée.
12. Lire le code existant avant de le modifier.
13. Une correction doit rester ciblée.
14. Ne pas refactoriser inutilement une fonctionnalité stable.
15. Ne pas modifier l'architecture globale sans justification.
16. Respecter le Design System existant.
17. Préserver le responsive.
18. Les noms affichés à l'utilisateur doivent être compréhensibles et non des IDs techniques.

---

# 🤖 19. RÈGLES POUR L'ÉDITEUR DE CODE / IA

Cette roadmap constitue la **source de vérité fonctionnelle et technique du projet**.

Avant toute modification, l'éditeur doit obligatoirement :

## Étape 1 — Comprendre l'état

Identifier :

```text
Qu'est-ce qui est terminé ?
Qu'est-ce qui est réellement en cours ?
Qu'est-ce qui reste à faire ?
Qu'est-ce qui est obsolète ?
Existe-t-il déjà une implémentation de cette fonctionnalité ?
```

## Étape 2 — Lire le code

Avant de modifier un fichier :

```text
Lire le fichier concerné
        ↓
Lire les dépendances importantes
        ↓
Comprendre le fonctionnement actuel
        ↓
Comparer avec cette roadmap
        ↓
Identifier l'écart réel
        ↓
Modifier uniquement ce qui est nécessaire
```

## Étape 3 — Ne jamais supposer

Une fonctionnalité marquée `[x]` signifie qu'elle est considérée comme terminée.

L'éditeur doit cependant **vérifier le code avant toute modification**.

Il ne doit pas :

* recréer une fonctionnalité existante ;
* remplacer une architecture fonctionnelle sans raison ;
* supprimer une fonctionnalité existante ;
* migrer arbitrairement une table ;
* inventer une nouvelle structure ;
* refaire une page uniquement parce qu'elle pourrait être différente ;
* considérer automatiquement qu'une fonctionnalité marquée `[ ]` est absente sans vérifier le code.

---

# 📊 20. FORMAT DE SUIVI OBLIGATOIRE POUR L'ÉDITEUR

À chaque intervention importante, l'éditeur doit pouvoir déterminer :

```text
ÉTAT DU PROJET

Phase actuelle :
Dernière phase terminée :

Fonctionnalités terminées :
- ...
- ...
- ...

Fonctionnalités en cours :
- ...
- ...

Fonctionnalités restantes :
- ...
- ...

Fichiers concernés :
- ...
- ...

Fichiers modifiés :
- ...
- ...

Tests effectués :
- ...
- ...

Problèmes détectés :
- ...
- ...

Prochaine étape recommandée :
- ...
```

La réponse doit être basée sur **le code réellement présent dans le projet**, et non uniquement sur cette roadmap.

---

# 🚦 21. LÉGENDE DES STATUTS

```text
🟢 TERMINÉ
Fonctionnalité implémentée et considérée comme validée.

🟡 EN COURS
Fonctionnalité partiellement implémentée.

🟠 À CORRIGER
Fonctionnalité existante mais présentant un problème.

🔴 BLOQUÉ
Impossible de continuer sans résoudre un problème préalable.

⚪ À FAIRE
Fonctionnalité prévue mais pas encore commencée.

🔵 POST-SOUTENANCE
Fonctionnalité volontairement reportée après la soutenance.
```

---

# 🐛 22. BUGS ET DETTES TECHNIQUES

Cette section doit rester **vivante**.

Lorsqu'un bug est découvert, il doit être ajouté ici.

| Problème                                                     | Fichier | Priorité | Statut |
| ------------------------------------------------------------ | ------- | -------- | ------ |
| Aucun bug critique actuellement identifié dans cette roadmap | —       | —        | 🟢     |

Lorsqu'un problème est corrigé :

* conserver une trace si nécessaire ;
* mettre à jour son statut ;
* ne pas laisser une ancienne information contradictoire dans la roadmap.

---

# 🖼️ 23. ASSET IMPORTANT

## Background Domizi

```text
c:\xampp\htdocs\coiffons\images\domizi-scene.jpg
```

Cet asset correspond au background visuel utilisé pour l'identité Domizi.

---

# 🏁 24. ÉTAT CIBLE AVANT SOUTENANCE

Avant de considérer Domizi comme **prêt pour la soutenance**, le parcours principal doit être stable :

```text
                    DOMIZI
                      │
                      ▼
              Portail Domizi
                      │
                      ▼
             Coiffe Chez Toi
                      │
          ┌───────────┴───────────┐
          ▼                       ▼
       CLIENT                  COIFFEUR
          │                       │
          ▼                       ▼
      Recherche                Profil
          │                  Catalogue
          ▼                    Agenda
       Profil                    RDV
          │                     Avis
          ▼                  Portefeuille
     Réservation
          │
          ▼
       RDV
          │
          ▼
        Avis
          │
          ▼
    Notifications

                +
                
              ADMIN
                │
       ┌────────┼────────┐
       ▼        ▼        ▼
    Users    Diplômes   Avis
       │
       ▼
   Validation
```

---

# 🎓 25. CRITÈRE DE FIN DE SOUTENANCE

Domizi n'est pas considéré comme terminé simplement parce que toutes les fonctionnalités imaginées existent.

Le MVP de soutenance est considéré comme prêt lorsqu'il est :

> **Stable → cohérent → sécurisé → démontrable → compréhensible.**

Les fonctionnalités prioritaires sont :

1. Authentification
2. OTP
3. Recherche
4. Profil prestataire
5. Catalogue
6. Disponibilités
7. Réservation
8. Gestion des rendez-vous
9. Avis
10. Notifications
11. Administration
12. Portefeuille simulé
13. Assistant IA

Les fonctionnalités avancées peuvent rester volontairement post-soutenance.

---

# 📌 26. PRINCIPE DIRECTEUR DU PROJET

> **Domizi commence avec Coiffe Chez Toi, mais Coiffe Chez Toi n'est que la première expression de Domizi.**

Le projet doit donc être construit de manière à permettre l'ajout futur de nouveaux métiers **sans détruire l'architecture existante**.

```text
Aujourd'hui
    ↓
Coiffe Chez Toi
    ↓
MVP solide
    ↓
Soutenance
    ↓
Paiement réel
    ↓
Nouveaux métiers
    ↓
Nouveaux pays
    ↓
                    DOMIZI
        Marketplace de services à domicile
                 en Afrique de l'Ouest
```

---

# 🔄 27. RÈGLE DE MISE À JOUR DE CETTE ROADMAP

Cette roadmap doit évoluer avec le projet.

Lorsqu'une fonctionnalité est terminée :

```text
[ ] → [x]
```

Lorsqu'une fonctionnalité devient temporairement bloquée :

```text
[ ] → 🔴 BLOQUÉ
```

Lorsqu'une fonctionnalité est reportée volontairement :

```text
[ ] → 🔵 POST-SOUTENANCE
```

Lorsqu'une fonctionnalité est supprimée du périmètre :

```text
SUPPRIMÉE — raison : ...
```

Lorsqu'une nouvelle fonctionnalité importante est ajoutée :

```text
NOUVELLE FONCTIONNALITÉ
→ Phase concernée
→ Statut
→ Description
```

---

# 🧠 28. SOURCE DE VÉRITÉ

Cette roadmap est le **document de référence principal du projet Domizi**.

Cependant, pour déterminer l'état réel d'une fonctionnalité, le code présent dans le projet reste la référence technique finale.

En cas de contradiction :

```text
ROADMAP
   +
CODE RÉEL
   +
BASE DE DONNÉES RÉELLE
   ↓
ÉTAT RÉEL DU PROJET
```

L'éditeur de code doit donc toujours vérifier les trois lorsqu'une information est incertaine.

Il ne doit jamais considérer une ancienne documentation, un ancien commentaire ou une ancienne roadmap comme supérieure au fonctionnement réel du projet.

---

# 🚀 29. PROCHAINE PRIORITÉ

## PRIORITÉ IMMÉDIATE

> **Finaliser et stabiliser Coiffe Chez Toi avant d'ajouter de nouvelles fonctionnalités majeures.**

Ordre recommandé :

```text
1. Vérification état réel du projet
            ↓
2. Correction des bugs restants
            ↓
3. Nettoyage technique
            ↓
4. Vérification sécurité
            ↓
5. Vérification parcours client
            ↓
6. Vérification parcours coiffeur
            ↓
7. Vérification administration
            ↓
8. Données de démonstration
            ↓
9. Tests complets
            ↓
10. Préparation soutenance
            ↓
11. Paiements / fonctionnalités avancées
            ↓
12. Expansion Domizi
```

---

# 🏆 OBJECTIF FINAL

Construire **Domizi**, une plateforme de services à domicile née au Bénin, dont **Coiffe Chez Toi** constitue la première verticale métier, avec une architecture suffisamment solide pour évoluer vers plusieurs métiers et plusieurs pays d'Afrique de l'Ouest.

> **Domizi n'est pas seulement le projet d'aujourd'hui.**
>
> **Coiffe Chez Toi est le premier produit.**
>
> **Domizi est la vision.**

---

*Ce fichier est le point d'ancrage du projet Domizi.*
*Il doit être mis à jour lorsque l'état réel du projet évolue.*
*Il ne doit jamais être remplacé par une ancienne roadmap sans fusion préalable de l'état actuel du projet.*

```
```
