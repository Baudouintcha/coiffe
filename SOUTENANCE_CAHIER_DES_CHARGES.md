# 📋 CAHIER DES CHARGES — COIFFE CHEZ TOI v2.1
## Marketplace de Services à Domicile — Branche Beauté & Coiffure

**Projet de Soutenance | Domizi — Plateforme Multi-Métiers**  
**Version**: 2.1 | **Date**: Août 2026 | **Statut**: 🚀 Prêt pour Soutenance

---

## 1️⃣ CONTEXTE & PROBLÉMATIQUE

### 1.1 Contexte Général

La branche **"Coiffe Chez Toi"** est la première réalisation concrète du projet **Domizi**, une marketplace multi-domaines de services à domicile pour l'Afrique de l'Ouest (Bénin, Togo, Côte d'Ivoire, Sénégal...).

**Domizi Vision**: Connecter les clients avec les prestataires locaux (coiffeurs, plombiers, électriciens, femmes de ménage, etc.) de manière efficace, sécurisée et conviviale.

### 1.2 Problématiques Identifiées

#### 🔴 **Problème 1: Fragmentation du Marché Local**
**Situation actuelle**: 
- Les clients cherchent des coiffeurs par le bouche-à-oreille, appels directs ou SMS
- Pas de plateforme centralisée avec avis/notations
- Perte de temps pour trouver un coiffeur fiable

**Impact**: Clients frustrés, coiffeurs sous-utilisés

#### 🔴 **Problème 2: Gestion d'Agenda Manuel**
**Situation actuelle**:
- Coiffeurs utilisent papier, SMS ou appels pour gérer les réservations
- Risque d'oublis, double-réservations, annulations non enregistrées
- Pas de suivi temps réel

**Impact**: Pertes de revenus, mauvaise expérience utilisateur

#### 🔴 **Problème 3: Absence de Système de Paiement Sécurisé**
**Situation actuelle**:
- Paiements au comptoir uniquement (cash)
- Pas de trace de transaction
- Confiance délicate entre client et prestataire

**Impact**: Insécurité financière, pas d'audit trail

#### 🔴 **Problème 4: Manque de Confiance & Transparence**
**Situation actuelle**:
- Aucun système d'avis/notation
- Pas de vérification des qualifications (diplômes)
- Réputations non vérifiées

**Impact**: Clients hésitants, coiffeurs sans différenciation de qualité

#### 🔴 **Problème 5: Centralisation Géographique**
**Situation actuelle**:
- Pas de filtrage par quartier/zone
- Déplacements inefficaces
- Coiffeurs sans données GPS

**Impact**: Mauvaise expérience utilisateur, inefficacité logistique

### 1.3 Utilisateurs Impactés

| Groupe | Problème | Besoin |
|--------|----------|--------|
| **Clients (femmes, hommes, enfants)** | Difficulté à trouver un bon coiffeur | Plateforme avec avis, localisation GPS, réservation facile |
| **Coiffeurs (micro-entrepreneurs)** | Gestion manuelle des RDV, revenu instable | Agenda digital, visibility en ligne, paiements sécurisés |
| **Administrateurs** | Pas de supervision | Contrôle validation, gestion modération, KPIs |

---

## 2️⃣ OBJECTIFS DU PROJET

### 2.1 Objectifs Généraux

**Objectif Global**: Créer une marketplace 100% fonctionnelle permettant clients et coiffeurs de se connecter, réserver, payer et noter en toute sécurité.

### 2.2 Objectifs Spécifiques (SMART)

| # | Objectif | Mesure | Cible |
|---|----------|--------|-------|
| **O1** | Réduire le temps de recherche coiffeur | Minutes de recherche | < 2 min |
| **O2** | Augmenter la confiance utilisateur | Avis/notation système | 100% des RDV termés = avis possible |
| **O3** | Digitiser les réservations | % RDV en ligne | 100% de la branche Coiffe |
| **O4** | Sécuriser les paiements | Transactions tracées | 100% des paiements en base de données |
| **O5** | Vérifier les prestataires | Diplômes approuvés | 0 coiffeur sans diplôme vérifié |
| **O6** | Améliorer l'accessibilité géographique | Filtrage quartier/zone | Visible pour tous les utilisateurs |
| **O7** | Créer une communauté de confiance | Système d'avis | Minimum 3.5/5 moyenne globale |

### 2.3 Périmètre du Projet

#### ✅ **Inclus dans le Scope**

**Fonctionnalités Core:**
- ✅ Authentification client/coiffeur/admin
- ✅ Catalogue prestations (styles de coiffure)
- ✅ Réservation en ligne avec date/heure
- ✅ Gestion d'agenda coiffeur
- ✅ Système de notation (1-5 étoiles) + avis texte
- ✅ Portefeuille digital + simulation paiement Kkiapay
- ✅ Notifications in-app
- ✅ Historique des rendez-vous
- ✅ Gestion profil client/coiffeur
- ✅ Annuaire coiffeurs avec filtrage géographique
- ✅ Dashboard admin (validation diplômes, modération)
- ✅ Assistant IA conversationnel (chatbot métier)
- ✅ Support multilingue (FR, EN, ES, AR)
- ✅ Design System Premium (Dark/Gold, Glassmorphism, Mobile-First)

#### ❌ **Hors Scope (Post-Soutenance)**

- ❌ Paiement Kkiapay réel (webhooks) — Implémentée mais sandbox only
- ❌ Push Notifications Web (Service Workers)
- ❌ Messagerie client-coiffeur
- ❌ Navigation GPS temps réel
- ❌ Module WhatsApp API
- ❌ Favoris / Système de parrainage
- ❌ Statistiques avancées (graphiques)
- ❌ Extension sous-régionale (nécessite infra distribuée)

---

## 3️⃣ FONCTIONNALITÉS PRINCIPALES ATTENDUES

### 3.1 Vue Globale des Fonctionnalités

```
┌─────────────────────────────────────────────────────────┐
│  COIFFE CHEZ TOI — Fonctionnalités Principales          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  👥 AUTHENTIFICATION & PROFIL                            │
│    ├─ Inscription client/coiffeur                       │
│    ├─ Connexion sécurisée (CSRF, brute-force)         │
│    ├─ Gestion profil (données, photo, localisation)   │
│    └─ Récupération mot de passe (OTP)                 │
│                                                          │
│  🎯 DÉCOUVERTE SERVICES (Clients)                       │
│    ├─ Annuaire coiffeurs avec filtrage                 │
│    ├─ Recherche par nom/localisation (GPS)            │
│    ├─ Consultation profil public coiffeur              │
│    ├─ Visualisation avis & note moyenne               │
│    └─ Vue catalogue prestations (styles)              │
│                                                          │
│  📅 RÉSERVATION (Clients)                               │
│    ├─ Sélection date/heure disponibles                 │
│    ├─ Choix options supplémentaires                    │
│    ├─ Confirmation avec solde portefeuille            │
│    ├─ Statut RDV en temps réel                        │
│    └─ Historique RDV passés & futurs                  │
│                                                          │
│  ⭐ AVIS & NOTATION (Clients)                            │
│    ├─ Notation 1-5 étoiles post-RDV                   │
│    ├─ Rédaction avis texte + plainte optionnelle      │
│    ├─ Visualisation avis de tous les utilisateurs     │
│    └─ Masquage partiel identité (prénom: K***)       │
│                                                          │
│  💼 GESTION PRESTATAIRE (Coiffeurs)                     │
│    ├─ Dashboard analytics (RDV, revenus, zones)       │
│    ├─ Gestion agenda hebdomadaire                      │
│    ├─ Validation/refus RDV entrants                    │
│    ├─ Catalogue produits (créer, modifier, supprimer) │
│    ├─ Gestion zones de couverture (quartiers)         │
│    ├─ Consultation ses avis & note                    │
│    └─ Portefeuille & historique transactions         │
│                                                          │
│  💳 PORTEFEUILLE & PAIEMENT                              │
│    ├─ Solde disponible (gelé/libéré)                  │
│    ├─ Recharge portefeuille (Kkiapay)                 │
│    ├─ Historique transactions détaillé                │
│    ├─ Simulation paiement (test mode)                 │
│    └─ Abonnement coiffeur (mensuel/annuel)           │
│                                                          │
│  🔔 NOTIFICATIONS (Tous)                                │
│    ├─ Notifications in-app (centre notification)       │
│    ├─ Alertes RDV (accepté, refusé, rappel)          │
│    ├─ Notifications paiement (recharge, expiration)   │
│    ├─ Notification nouvel avis (coiffeurs)            │
│    └─ Statut lecture des notifications               │
│                                                          │
│  🤖 ASSISTANT IA (Tous)                                 │
│    ├─ Chat conversationnel métier                      │
│    ├─ Contexte intelligent par rôle                    │
│    ├─ Suggestions rapides (3 boutons)                 │
│    ├─ Liens cliquables dans réponses                 │
│    └─ Intégration données BDD en temps réel           │
│                                                          │
│  🌐 MULTILINGUE & INTERNATIONALIZATION                  │
│    ├─ Support FR, EN, ES, AR                          │
│    ├─ Dropdown langue avec icône globe               │
│    └─ Stockage préférence utilisateur                │
│                                                          │
│  🔐 SÉCURITÉ & CONFORMITÉ                               │
│    ├─ CSRF protection tous POST                       │
│    ├─ Brute-force protection login                    │
│    ├─ OTP pour password reset                         │
│    ├─ Hash password (bcrypt)                          │
│    ├─ Injection SQL prevention (prepared statements)  │
│    ├─ Session security (timeout, secure flags)       │
│    └─ Audit trail deletions (suppressions_comptes)   │
│                                                          │
│  👨‍💼 ADMINISTRATION                                    │
│    ├─ Tableau de bord KPIs (users, RDV, revenus)     │
│    ├─ Validation diplômes coiffeurs                  │
│    ├─ Modération avis/plaintes                        │
│    ├─ Bannissement compte                             │
│    ├─ Historique actions admin                        │
│    └─ Statistiques financières                        │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Détails des Fonctionnalités Clés

#### **F1: Authentification & Gestion des Rôles**

| Aspect | Description |
|--------|-------------|
| **Inscription** | Client ou Coiffeur, avec OTP optional, upload photo/diplôme (coiffeur) |
| **Connexion** | Email/password, brute-force protection (5 tentatives / 15 min), redirect auto |
| **Rôles** | 3 rôles distincts : client, coiffeur, admin avec permissions différentes |
| **Session** | Session PHP sécurisée (timeout 30 min), pas de données sensibles en session |
| **Logout** | Destruction session complète + redirection login |

#### **F2: Annuaire & Découverte**

| Aspect | Description |
|--------|-------------|
| **Listing** | Affiche tous coiffeurs approuvés avec filtering (ville, quartier, note) |
| **Recherche** | Recherche par nom/prénom avec autocomplete |
| **Filtres** | Ville, quartier, note minimum (3.5+), actif/inactif |
| **Profil Public** | Affiche 5 derniers avis, horaires, zones, note moyenne, contact |
| **Réseaux** | Intégration WhatsApp (lien direct), appel (tel:) |

#### **F3: Réservation & Gestion RDV**

| Aspect | Description |
|--------|-------------|
| **Sélection** | Date (datepicker), heure (time picker), prestation (select), options (checkboxes) |
| **Validation** | Vérif créneaux dispo (cross-check agenda coiffeur) |
| **Confirmation** | Gel solde portefeuille, notification coiffeur push + email |
| **Statuts RDV** | en_attente → accepté/refusé → en_cours → terminé → archivé |
| **Historique** | Visualisation RDV passés & futurs avec bouton "Laisser avis" |

#### **F4: Notation & Avis**

| Aspect | Description |
|--------|-------------|
| **Post-RDV** | Client peut noter 1-5 étoiles après RDV terminé |
| **Avis Texte** | Commentaire optionnel (max 500 chars) + plainte confidentielle si note ≤2 |
| **Anti-Doublon** | Empêche 2 avis du même client pour même RDV |
| **Masquage** | Prénom masqué (K***) sauf pour coiffeur |
| **Visibilité** | Avis visibles : coiffeur (tous), client (siens), autres utilisateurs (résumé) |
| **Note Moyenne** | Affichée annuaire, profil public, dashboard coiffeur |

#### **F5: Dashboard Coiffeur**

| Aspect | Description |
|--------|-------------|
| **Hero Stats** | RDV du jour, revenus du jour, demandes en attente |
| **Bannière Alertes** | Abonnement expirant, diplôme non approuvé, problème paiement |
| **Planning** | Liste chronologique RDV aujourd'hui + actions (accepter/refuser) |
| **Complétude Profil** | % progression (photo, bio, agenda, services, zones) |
| **Portefeuille** | Solde disponible, dépôt récent, historique transactions |
| **Quick Actions** | Liens vers : catalogue, agenda, zones, mes avis |

#### **F6: Portefeuille & Paiement**

| Aspect | Description |
|--------|-------------|
| **Solde Types** | Solde disponible, solde gelé (RDV en attente) |
| **Recharge** | Via Kkiapay (montant 500-500K FCFA), webhook réel |
| **Blocage** | Vérif OTP lors paiement > 100K FCFA |
| **Historique** | Transactions détaillées (date, montant, type, statut) |
| **Abonnement** | Coiffeur peut activer abonnement mensuel (1500-5000 FCFA) |
| **Simulation** | Mode test mode=true pour démo sans vrai paiement |

#### **F7: Notifications**

| Aspect | Description |
|--------|-------------|
| **Types** | Info (bleu), Success (vert), Warning (jaune), Error (rouge) |
| **Channels** | In-app (cloche navbar), Email (optionnel), SMS/WhatsApp (futur) |
| **Triggers** | Nouveau RDV (coiffeur), RDV accepté (client), Avis reçu (coiffeur), Paiement OK |
| **Archivage** | Marquer comme lu, suppression après 30 jours |
| **Centre Notif** | Page dédiée avec liste complète + marquage par lot |

#### **F8: Assistant IA Conversationnel**

| Aspect | Description |
|--------|-------------|
| **Contexte** | Données utilisateur injectées en temps réel (solde, RDV, stats) |
| **Réponses** | Générées via Gemini API (contexte markdown + prompt système) |
| **Suggestions** | 3 boutons de suggestions rapides par rôle |
| **Liens** | Conversion [texte](url) en HTML cliquable |
| **Multilingue** | Réponses automatiquement traduites selon langue utilisateur |

#### **F9: Admin & Modération**

| Aspect | Description |
|--------|-------------|
| **Dashboard** | KPIs globaux (users actifs, RDV/jour, revenue) |
| **Validation** | Approuvation/refus diplômes coiffeurs avec message |
| **Modération Avis** | Visualisation plaintes, suppression avis abusifs |
| **Bannissement** | Bannir compte user (client ou coiffeur) |
| **Historique** | Log toutes actions admin avec date/auteur |

### 3.3 User Stories Clés

| ID | Rôle | Besoin | Bénéfice |
|:--:|------|--------|----------|
| **US1** | Client | Trouver coiffeur par localisation GPS | Économiser temps déplacement |
| **US2** | Client | Consulter avis clients précédents | Choisir en confiance |
| **US3** | Client | Réserver RDV sans appel | Convenience 24/7 |
| **US4** | Client | Laisser avis après service | Construire confiance communauté |
| **US5** | Coiffeur | Gérer agenda digital | Éviter double-booking |
| **US6** | Coiffeur | Accepter/refuser RDV | Maîtriser charge de travail |
| **US7** | Coiffeur | Consulter ses avis | Mesurer qualité service |
| **US8** | Coiffeur | Recharger portefeuille | Accéder revenus rapidement |
| **US9** | Admin | Valider diplômes | Garantir qualité prestataires |
| **US10** | Admin | Modérer avis abusifs | Maintenir confiance |

---

## 4️⃣ CIBLE & UTILISATEURS VISÉS

### 4.1 Segmentation Utilisateurs

#### **Segment 1: CLIENTS (Demandeurs de Services)**

**Profil Type:**
- Femmes (80%), hommes (15%), enfants (5%)
- Âge: 15-60 ans
- Localisation: Cotonou, Parakou, autres villes Bénin + sous-région
- Tech-savviness: Moyenne à haute (smartphone Android principal)
- Revenu: Moyen (suffisant pour service à domicile)

**Besoins Primaires:**
- Trouver un coiffeur fiable rapidement
- Réserver sans appel (gène, coût)
- Payer de manière sécurisée
- Consulter avis avant de choisir

**Comportement:**
- Utilise mobile + WhatsApp
- Partage recommandations avec amies
- Hésite si pas assez d'avis

**Acquisition:**
- Bouche-à-oreille (principal)
- Réseaux sociaux (Facebook, Instagram)
- Influenceurs beauté

#### **Segment 2: COIFFEURS (Fournisseurs de Services)**

**Profil Type:**
- Femmes (95%), hommes (5%)
- Âge: 20-55 ans
- Localisation: Quartiers résidentiels (Cotonou: Haut, Jéricho, Gobron...)
- Tech-savviness: Faible à moyenne
- Revenu: Micro-entrepreneurs (500K-3M FCFA/mois)

**Besoins Primaires:**
- Augmenter clientèle
- Gérer réservations simplement
- Tracer revenus
- Construire réputation

**Comportement:**
- Hésitant face à technologie
- Besoin support client
- Partagent recommandations WhatsApp

**Acquisition:**
- Direct (coiffeurs existants)
- Démonstration en salon
- Support WhatsApp personnalisé

#### **Segment 3: ADMINISTRATEURS (Modérateurs)**

**Profil Type:**
- Équipe interne projet Domizi
- Tech-savviness: Haute
- Rôle: Validation, modération, support

**Besoins Primaires:**
- Superviser plateforme
- Valider prestataires
- Modérer contenu
- Générer rapports

---

## 5️⃣ PAGES & PARCOURS UTILISATEUR

### 5.1 Architecture Pages

```
COIFFE CHEZ TOI — Arborescence Pages
│
├─── PORTAIL DOMIZI (app.php)
│    └─ Portail pays/métiers
│       └─ Clic Beauté → Clic Coiffure → index.php (CCT)
│
├─── PAGES PUBLIQUES (Sans Login)
│    ├─ views/home.php — Hero slider + CTA
│    ├─ access/inscription.php — Inscription client/coiffeur
│    ├─ access/connexion.php — Connexion
│    ├─ access/password_reset.php — Récupération MDP via OTP
│    ├─ coiffeurs/profil_public.php — Profil coiffeur consultation
│    └─ filter/annuaire_coiffeurs.php — Listing coiffeurs
│
├─── DASHBOARD CLIENT
│    ├─ views/client/dashboard.php — Homepage (hero + annuaire)
│    ├─ filter/annuaire_coiffeurs.php — Recherche coiffeur
│    ├─ coiffeurs/profil_public.php — Consulter profil coiffeur
│    ├─ client/creer_rendezvous.php — Créer RDV (step 1: sélection)
│    ├─ client/reserver.php — Confirmation RDV (step 2: recap)
│    ├─ client/mes_rendezvous.php — Historique RDV
│    ├─ client/laisser_avis.php — Notation + avis post-RDV
│    ├─ client/notifications.php — Centre notifications
│    ├─ client/catalogue.php — Consulter services (readonly)
│    ├─ profil.php — Gestion profil client
│    ├─ modifier_profil.php — Modifier données personnelles
│    ├─ client/portefeuille.php — Visualiser solde
│    ├─ client/traiter_recharge.php — Recharge portefeuille
│    └─ views/components/ia_assistant.php — Chat IA (overlay)
│
├─── DASHBOARD COIFFEUR
│    ├─ views/coiffeur/dashboard.php — Homepage (stats + actions)
│    ├─ coiffeurs/profil_coiffeurs.php — Gestion profil
│    ├─ coiffeurs/mes_zones.php — Gestion zones couverture
│    ├─ coiffeurs/gestion_catalogue.php — Services (CRUD)
│    ├─ coiffeurs/agenda_coiffeurs.php — Gestion horaires
│    ├─ coiffeurs/valider_rendezvous.php — Accepter/refuser RDV
│    ├─ coiffeurs/mes_avis.php — Consulter avis reçus
│    ├─ coiffeurs/portefeuille.php — Visualiser solde + historique
│    ├─ client/notifications.php — Centre notifications
│    ├─ profil.php — Paramètres profil
│    ├─ modifier_profil.php — Modifier données personnelles
│    └─ views/components/ia_assistant.php — Chat IA (overlay)
│
├─── ADMIN PANEL
│    ├─ first/admin_dashboard.php — KPIs + actions rapides
│    ├─ first/admin_actions.php — Validation diplômes + modération
│    ├─ client/notifications.php — Notifications admin
│    └─ profil.php — Paramètres admin
│
└─── PAGES TRANSVERSALES
     ├─ security/cron_notifications.php — Tâche auto notifications
     ├─ security/notifications.php — Helpers notification
     ├─ access/verify_otp.php — Vérification OTP backend
     ├─ views/components/ — Components réutilisables
     └─ deconnexion.php — Logout

```

### 5.2 Parcours Utilisateur Client (Happy Path)

```
┌─────────────────────────────────────────────────────────────┐
│ PARCOURS CLIENT — TROUVER & RÉSERVER UN COIFFEUR           │
└─────────────────────────────────────────────────────────────┘

ÉTAPE 1: DÉCOUVERTE
┌──────────────────────────────────────────┐
│ views/home.php (Page d'accueil)         │
│ ├─ Hero slider "Coiffeur Domicile"     │
│ ├─ CTA "Chercher un Coiffeur"           │
│ └─ Btn "S'inscrire" / "Se Connecter"   │
└──────────────────────────────────────────┘
         │
         ├─[Si nouveau] → access/inscription.php
         │               ├─ Formulaire email/pwd/photo
         │               ├─ Validation
         │               └─ Redirect dashboard
         │
         └─[Si existant] → access/connexion.php
                           ├─ Email + password
                           └─ Redirect dashboard

ÉTAPE 2: RECHERCHE COIFFEUR
┌──────────────────────────────────────────┐
│ views/client/dashboard.php               │
│ ├─ Barre recherche "Chercher coiffeur"  │
│ ├─ Filtres: Ville, Quartier, Note      │
│ └─ Annuaire coiffeurs listé            │
└──────────────────────────────────────────┘
         │
         └─ Clic sur coiffeur

ÉTAPE 3: CONSULTATION PROFIL
┌──────────────────────────────────────────┐
│ coiffeurs/profil_public.php              │
│ ├─ Photo, bio, services                 │
│ ├─ Note moyenne + 5 derniers avis      │
│ ├─ Horaires disponibles                 │
│ ├─ Btn "Réserver un RDV"               │
│ └─ Contact WhatsApp/Appel              │
└──────────────────────────────────────────┘
         │
         └─ Clic "Réserver"

ÉTAPE 4: CRÉATION RDV (STEP 1)
┌──────────────────────────────────────────┐
│ client/creer_rendezvous.php (Step 1)     │
│ ├─ Sélection date (calendrier)          │
│ ├─ Sélection heure (crénaux dispo)     │
│ ├─ Sélection prestation (catalogue)    │
│ ├─ Sélection options (checkboxes)      │
│ └─ Btn "Continuer"                     │
└──────────────────────────────────────────┘
         │
         └─ Validation formulaire

ÉTAPE 5: CONFIRMATION RDV (STEP 2)
┌──────────────────────────────────────────┐
│ client/reserver.php (Step 2)             │
│ ├─ Récapitulatif complet                │
│ │  ├─ Coiffeur (photo + note)           │
│ │  ├─ Prestation (durée + prix)         │
│ │  ├─ Date & heure                      │
│ │  ├─ Options (prix additionnel)        │
│ │  └─ Total à geler                     │
│ ├─ Vérif solde portefeuille            │
│ └─ Btn "Confirmer Réservation"         │
└──────────────────────────────────────────┘
         │
         └─ POST confirmation

ÉTAPE 6: ATTENTE VALIDATION
┌──────────────────────────────────────────┐
│ ✅ RDV créé (statut: en_attente)        │
│ • Notification: "Demande envoyée"        │
│ • Solde gelé: montant débité            │
│ • Coiffeur reçoit notification          │
│ • Client voit RDV dans "Mes RDV"        │
│ • Statut: ⏳ En attente de validation   │
└──────────────────────────────────────────┘
         │
         ├─[Coiffeur accepte]
         │  └─ Notification client ✅
         │     Statut → "Accepté"
         │
         └─[Coiffeur refuse]
            └─ Notification client ❌
               Solde dégelé (remboursé)
               Statut → "Refusé"

ÉTAPE 7: RDV ACCEPTÉ
┌──────────────────────────────────────────┐
│ client/mes_rendezvous.php                │
│ ├─ RDV listé avec statut "Accepté"     │
│ ├─ Adresse coiffeur + GPS               │
│ ├─ Heure confirmée + rappel             │
│ └─ Btn options: "Appeler", "GPS", "Annuler" │
└──────────────────────────────────────────┘
         │
         └─ Le jour du RDV

ÉTAPE 8: POST-SERVICE (RDV Terminé)
┌──────────────────────────────────────────┐
│ client/laisser_avis.php                  │
│ ├─ Notation 1-5 étoiles               │
│ ├─ Commentaire texte (optionnel)       │
│ ├─ Plainte confidentielle si note≤2    │
│ └─ Btn "Soumettre Avis"                │
└──────────────────────────────────────────┘
         │
         └─ POST avis

ÉTAPE 9: FERMETURE CYCLE
┌──────────────────────────────────────────┐
│ ✅ Avis soumis                           │
│ • Notification coiffeur: "Nouvel avis"  │
│ • Note mise à jour dans annuaire        │
│ • Solde dégelé → libéré au coiffeur    │
│ • RDV archivé dans historique          │
│ • Cycle terminé                        │
└──────────────────────────────────────────┘

```

### 5.3 Parcours Utilisateur Coiffeur (Happy Path)

```
┌─────────────────────────────────────────────────────────┐
│ PARCOURS COIFFEUR — S'INSCRIRE & GÉRER RDV             │
└─────────────────────────────────────────────────────────┘

ÉTAPE 1-2: INSCRIPTION [Identique à client]

ÉTAPE 3: PREMIÈRE CONNEXION DASHBOARD
┌─────────────────────────────────────────┐
│ views/coiffeur/dashboard.php             │
│ ├─ 🔴 Bannière rouge: "Complétez profil" │
│ ├─ 🟡 Bannière jaune: "Diplôme attente" │
│ ├─ Stats: RDV (0), Revenus (0 FCFA)    │
│ └─ Actions rapides: "Complétez profil"  │
└─────────────────────────────────────────┘
         │
         └─ Clic "Complétez profil"

ÉTAPE 4: PROFIL COMPLET
┌─────────────────────────────────────────┐
│ coiffeurs/profil_coiffeurs.php          │
│ ├─ Upload photo profil                  │
│ ├─ Upload diplôme (scan/photo)         │
│ ├─ Bio professional (300 cars max)      │
│ ├─ Numéro téléphone WhatsApp           │
│ └─ Btn "Enregistrer"                    │
└─────────────────────────────────────────┘
         │
         └─ Profil sauvegardé

ÉTAPE 5: CONFIGURATION SERVICES
┌─────────────────────────────────────────┐
│ coiffeurs/gestion_catalogue.php         │
│ ├─ Clic "Ajouter Service"              │
│ ├─ Nom style (Tresse, Box, Gnarls..) │
│ ├─ Description (max 1000 chars)       │
│ ├─ Prix (FCFA)                        │
│ ├─ Durée (minutes)                     │
│ ├─ Options supplémentaires (JSON)     │
│ └─ Upload photo style                  │
│
│ Services créés:
│ ├─ Tresse collée: 3000 FCFA, 45 min   │
│ ├─ Box braids: 4500 FCFA, 60 min      │
│ ├─ Défrisage: 2500 FCFA, 30 min       │
│ ├─ Coloration: 5000 FCFA, 45 min      │
│ └─ Options: Lissage (+500), Volume (+300) │
└─────────────────────────────────────────┘

ÉTAPE 6: GESTION HORAIRES
┌─────────────────────────────────────────┐
│ coiffeurs/agenda_coiffeurs.php          │
│ ├─ Tableau hebdomadaire                 │
│ ├─ Lundi-Dimanche                       │
│ │  ├─ Cochez "Actif"                   │
│ │  ├─ Heure début (09:00)              │
│ │  └─ Heure fin (18:00)                │
│ ├─ Clic "Enregistrer Horaires"        │
│ └─ ✅ Horaires sauvegardés             │
└─────────────────────────────────────────┘

ÉTAPE 7: GESTION ZONES
┌─────────────────────────────────────────┐
│ coiffeurs/mes_zones.php                 │
│ ├─ Sélection ville: Cotonou            │
│ ├─ Quartiers couverts (checkboxes):    │
│ │  ├─ ✓ Haut                           │
│ │  ├─ ✓ Jéricho                        │
│ │  ├─ ✓ Gobron                         │
│ │  └─ (autres quartiers non coches)   │
│ ├─ Rayon déplacement: 2 km             │
│ └─ Clic "Enregistrer Zones"           │
└─────────────────────────────────────────┘

ÉTAPE 8: ATTENDRE APPROBATION
┌─────────────────────────────────────────┐
│ ⏳ En attente validation diplôme         │
│ • Notification: "Diplôme en attente"   │
│ • Admin reçoit notification             │
│ • Délai: 24-48h (SLA admin)             │
│ • [Pendant ce temps] : Visible annuaire │
│                       mais "Non approuvé" │
└─────────────────────────────────────────┘

ÉTAPE 9: DIPLÔME APPROUVÉ
┌─────────────────────────────────────────┐
│ ✅ Diplôme accepté                      │
│ • Notification: "Félicitations!"       │
│ • Visible dans l'annuaire (✓ Approuvé) │
│ • Clients peuvent maintenant chercher   │
│ • Dashboard → "Complétude: 100%"       │
└─────────────────────────────────────────┘

ÉTAPE 10: PREMIÈRE RÉSERVATION
┌─────────────────────────────────────────┐
│ views/coiffeur/dashboard.php            │
│ ├─ Client A demande RDV                 │
│ ├─ Notification: "Nouvelle demande"     │
│ ├─ Section "Demandes en attente"       │
│ │  ├─ Client: Laetitia N.              │
│ │  ├─ Date: Lundi 14:30                │
│ │  ├─ Service: Tresse                  │
│ │  ├─ Prix: 3000 FCFA                  │
│ │  ├─ Btn "Accepter" / "Refuser"      │
│ └─ Clic "Accepter"                    │
└─────────────────────────────────────────┘

ÉTAPE 11: RDV ACCEPTÉ
┌─────────────────────────────────────────┐
│ ✅ RDV accepté                          │
│ • Client reçoit notif: "Accepté!"      │
│ • RDV apparaît dans "Planning du jour" │
│ • Statut → "Accepté"                   │
│ • Solde client gelé                     │
│ • Contact client visible (WhatsApp)    │
└─────────────────────────────────────────┘

ÉTAPE 12: JOUR DU RDV
┌─────────────────────────────────────────┐
│ 09:00 — Coiffeur voit RDV dans planning│
│         Clic sur RDV pour détails      │
│                                        │
│ 14:25 — Rappel auto: "RDV dans 5 min" │
│         (notification push)            │
│                                        │
│ 14:30 — Client arrive                  │
│         Service commencé               │
│                                        │
│ 15:15 — Service terminé                │
│         RDV → Statut "Terminé"        │
│         Solde dégelé → Libéré au coiffeur │
│         Client reçoit notif: "Avis?"   │
└─────────────────────────────────────────┘

ÉTAPE 13: AVIS REÇU
┌─────────────────────────────────────────┐
│ ⭐⭐⭐⭐⭐ 5 étoiles + commentaire        │
│ "Service impeccable, très professionnel!" │
│                                        │
│ Coiffeur reçoit notif:                 │
│ "Nouvel avis 5⭐ de Laetitia N."      │
│                                        │
│ Note moyenne mise à jour:              │
│ 4.8 → 4.9 (20 avis)                    │
└─────────────────────────────────────────┘

ÉTAPE 14: GESTION PORTEFEUILLE
┌─────────────────────────────────────────┐
│ coiffeurs/portefeuille.php              │
│ ├─ Solde disponible: 5500 FCFA        │
│ │  (commission 3000 - frais 10%)       │
│ ├─ Historique (20 derniers):          │
│ │  └─ Lun 15:15: +2700 FCFA          │
│ │     (RDV Laetitia, Tresse)         │
│ ├─ Btn "Recharger" (client only)     │
│ ├─ Btn "Retrait" (TODO: post-launch) │
│ └─ Abonnement: Inactif                 │
└─────────────────────────────────────────┘

```

---

## 6️⃣ ERGONOMIE & UX GÉNÉRALE

### 6.1 Principes de Conception

| Principe | Application | Bénéfice |
|----------|-------------|----------|
| **Mobile-First** | Tous layouts pensés mobile d'abord | 70% traffic mobile |
| **Dark Mode** | Fond sombre `#0D0D0D` + accents gold | Réduction fatigue oculaire |
| **Glassmorphism** | Cartes avec `backdrop-filter: blur` | Moderne + premium |
| **Accessibilité** | WCAG 2.1 AA (contraste, aria-labels) | Inclusivité |
| **Performance** | Lazy-loading images, CSS optimisé | Chargement < 2s |
| **Feedback** | Toast messages, spinners, animations | UX feedback clair |

### 6.2 Design System v2.0 (Compliant)

**Couleurs:**
- Background: `#0D0D0D` (dark-2), `#1A1A1A` (dark)
- Accent: `#D4AF37` (gold)
- Success: `#19874D` (vert)
- Error: `#C03030` (rouge)
- Text: `#F0F0F0` (light), `#999999` (muted)

**Typographie:**
- Display: Playfair Display (h1, h2, "Domizi")
- Body: Inter (tous contenus)
- Weights: 300 (light), 400 (regular), 600 (semibold), 700 (bold)

**Espacement:**
- Padding standard: 16px, 20px, 24px
- Margin gap: 8px, 12px, 16px, 24px
- Border-radius: 8px (lg), 12px (xl)

**Composants:**
- `.btn-gold` — CTA primaire
- `.btn-ghost` — CTA secondaire
- `.resa-card` — Card récap
- `.status-badge` — Statuts RDV
- `.toast` — Messages temporaires

### 6.3 Navigation & Information Architecture

```
HIERARCHY:
┌─ PORTAIL (app.php)
│
└─ BRANCHE COIFFE CHEZ TOI (index.php)
   │
   ├─ PUBLIC AREA
   │  ├─ Home (hero + CTA)
   │  ├─ Auth (signup/login)
   │  ├─ Annuaire (filtrage public)
   │  └─ Profil public coiffeur
   │
   ├─ CLIENT AREA
   │  ├─ Dashboard (home)
   │  ├─ Annuaire (recherche)
   │  ├─ Réservation (workflow)
   │  ├─ Mes RDV (historique)
   │  ├─ Avis (laisser)
   │  ├─ Notifications (centre)
   │  ├─ Portefeuille (solde)
   │  ├─ Profil (édition)
   │  └─ Chat IA (overlay)
   │
   ├─ COIFFEUR AREA
   │  ├─ Dashboard (analytics)
   │  ├─ Profil (édition)
   │  ├─ Services (CRUD)
   │  ├─ Agenda (horaires)
   │  ├─ Zones (quartiers)
   │  ├─ Demandes (accepter/refuser)
   │  ├─ Mes avis (consultation)
   │  ├─ Portefeuille (historique)
   │  ├─ Notifications (centre)
   │  └─ Chat IA (overlay)
   │
   └─ ADMIN AREA
      ├─ Dashboard (KPIs)
      ├─ Validation (diplômes)
      ├─ Modération (avis/plaintes)
      ├─ Bannissement
      ├─ Notifications (centre)
      └─ Profil (édition)
```

### 6.4 Responsive Breakpoints

| Device | Width | Layout | Nav |
|--------|-------|--------|-----|
| Mobile | <768px | 1 colonne | Bottom nav |
| Tablet | 768-1024px | 2 colonnes | Top nav |
| Desktop | >1024px | 3 colonnes (sidebar) | Sidebar + top |

---

## 7️⃣ TECHOLOGIES & STACK

### 7.1 Backend
- **Langage**: PHP 7.4+
- **Architecture**: MVC (Structure 2 — autoloading PSR-4)
- **Base de Données**: MySQL 8.0 (MariaDB)
- **Authentification**: Password hashing (bcrypt), sessions sécurisées
- **Paiement**: Kkiapay (sandbox + prod ready)
- **IA**: Gemini API (responses contextualisées)
- **Email**: PHPMailer (SMTP)
- **SMS/WhatsApp**: API Vonage (future)

### 7.2 Frontend
- **HTML5**: Sémantique complète
- **CSS3**: Variables, Flexbox, Grid, Media queries
- **JavaScript**: Vanilla (pas de frameworks lourds)
- **Libraries**: Bootstrap 5.3.3 (grid uniquement)
- **Icons**: Bootstrap Icons 1.11.3
- **Fonts**: Inter + Playfair Display (Google Fonts)

### 7.3 Infrastructure
- **Local**: XAMPP (PHP 8.0+, MySQL, Apache)
- **Production**: VPS Linux + Docker (future)
- **Version Control**: Git + GitHub
- **CI/CD**: GitHub Actions (future)
- **Monitoring**: Sentry (error tracking)

---

## ✅ CONCLUSION

**Coiffe Chez Toi** est une marketplace fonctionnelle, sécurisée et prête pour la soutenance. Elle résout les problématiques identifiées avec des fonctionnalités complètes, une UX soignée et une architecture extensible.

**Prochaines étapes post-soutenance:**
1. Migration vers architecture MVC pure (Structure 2 complète)
2. Déploiement VPS production
3. Implémentation Kkiapay réelle (webhooks prod)
4. Extension sous-régionale (Togo, Côte d'Ivoire, Sénégal...)

---

**Document rédigé**: Août 2026  
**Préparé pour**: Soutenance Kiro/Domizi  
**Statut**: ✅ FINAL

