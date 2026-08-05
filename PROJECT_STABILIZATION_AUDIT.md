# PROJECT STABILIZATION AUDIT — COIFFE CHEZ TOI
## Audit Global Pré-Production V1.0
**Date :** 4 Août 2026
**Auditeur :** Kiro — Analyse statique exhaustive de la base de code
**Périmètre :** Toutes les pages, tous les parcours, toutes les couches

---

## SCORE GLOBAL : 68 / 100

| Catégorie | Score | Niveau |
|---|---|---|
| Design System | 74/100 | ⚠️ Partiel |
| Composants | 78/100 | ⚠️ Partiel |
| CSS | 72/100 | ⚠️ Partiel |
| Responsive | 75/100 | ⚠️ Partiel |
| Parcours | 65/100 | ⚠️ Partiel |
| Fonctionnel | 70/100 | ⚠️ Partiel |
| PHP / Sécurité | 62/100 | ⚠️ Attention |
| Assets | 65/100 | ⚠️ Partiel |
| JavaScript | 72/100 | ⚠️ Partiel |
| Logique Métier | 72/100 | ⚠️ Partiel |
| Performance | 60/100 | 🔴 Faible |
| Sécurité | 65/100 | ⚠️ Attention |

---

## PARTIE 1 — AUDIT DU DESIGN SYSTEM

### 1.1 État de conformité par page

| Page | Statut | Score | Anomalies |
|---|---|---|---|
| `views/home.php` | ✅ Conforme | 90/100 | CSS inline minimal résiduel |
| `access/connexion.php` | ✅ Conforme | 92/100 | — |
| `access/inscription.php` | ✅ Conforme | 90/100 | — |
| `filter/annuaire_coiffeurs.php` | ✅ Conforme | 88/100 | CSS local mais propre |
| `coiffeurs/profil_public.php` | ✅ Conforme | 88/100 | CSS inline dans section avis |
| `client/reserver.php` | ✅ Conforme | 90/100 | — |
| `views/client/dashboard.php` | ✅ Conforme | 92/100 | — |
| `client/mes_rendezvous.php` | ✅ Conforme | 88/100 | — |
| `client/catalogue.php` | ✅ Conforme | 87/100 | CSS local sur btn-edit-owner |
| `client/laisser_avis.php` | ✅ Conforme | 90/100 | — |
| `client/creer_rendezvous.php` | ✅ Conforme | 88/100 | — |
| `client/notifications.php` | ✅ Conforme | 85/100 | — |
| `coiffeurs/gestion_catalogue.php` | ⚠️ Partiel | 78/100 | Charges DS v2.0 **après** header_coiffeur |
| `coiffeurs/agenda_coiffeurs.php` | ⚠️ Partiel | 78/100 | Charges DS v2.0 **après** header_coiffeur |
| `coiffeurs/valider_rendezvous.php` | ⚠️ Partiel | 78/100 | Charges DS v2.0 **après** header_coiffeur |
| `coiffeurs/portefeuille.php` | ⚠️ Partiel | 75/100 | Charges DS v2.0 **après** header_coiffeur |
| `views/coiffeur/dashboard.php` | 🔴 Non conforme | 45/100 | CSS inline complet, pas de variables DS |
| `profil.php` | ⚠️ Partiel | 72/100 | Utilise DS mais CSS inline massif |
| `first/admin_dashboard.php` | 🔴 Non conforme | 20/100 | Aucune intégration DS v2.0 |
| `layout/header.php` | 🔴 Non conforme | 15/100 | Ancien système Bootstrap pur, pas de DS |
| `layout/footer.php` | 🔴 Non conforme | 10/100 | Ancien footer Bootstrap, IA intégrée en inline |


### 1.2 Anomalies Design System détectées

**🔴 CRITIQUE — Ordre de chargement incorrect (Parcours Coiffeur)**
- Pages concernées : `gestion_catalogue.php`, `agenda_coiffeurs.php`, `valider_rendezvous.php`, `portefeuille.php`
- Symptôme : `include header_coiffeur.php` ouvre le `<html>` et `<head>`, mais les CSS DS v2.0 (`variables.css`, `animations.css`, `components.css`) sont injectés **dans le `<body>`** après la balise `<html>` fermée.
- Impact : L'ordre de chargement CSS est inversé. Les variables DS ne sont pas disponibles au moment où Bootstrap est chargé. Les composants DS risquent de ne pas recevoir leurs tokens.
- Correction : Déplacer les 3 balises `<link>` DS dans `layout/header_coiffeur.php` directement dans le `<head>`.

**🔴 CRITIQUE — `views/coiffeur/dashboard.php` : aucune intégration DS v2.0**
- Ce fichier définit toutes ses variables CSS en inline (`:root { --gold: #D4AF37; ... }`).
- Il ne charge pas `variables.css`, `animations.css`, ni `components.css`.
- Toutes les classes DS (`btn-gold`, `glass-cct`, `badge-gold`, etc.) sont réimplementées localement.
- Impact : incohérence visuelle majeure entre le dashboard coiffeur et tous les autres parcours.

**🟡 HAUTE — `first/admin_dashboard.php` : système CSS autonome**
- Utilise ses propres variables `:root { --gold: #D4AF37; --dark-bg: #000000; }`.
- Bootstrap 5 brut, aucune intégration du DS v2.0.
- Tables avec `.table-premium`, cartes avec `.card-custom` : styles orphelins non maintenables.
- Note : page admin — priorité moindre pour la démo, mais à refactoriser avant production.

**🟡 HAUTE — `layout/header.php` et `layout/footer.php` : héritage non migré**
- Ces deux fichiers sont encore actifs et utilisés dans au moins `profil.php` et `modifier_profil.php`.
- `header.php` génère un HTML complet avec `<html>`, `<head>`, `<body>` en Bootstrap pur.
- `footer.php` contient l'IA Bubule (assistant IA ancien) avec un JS de 150 lignes inline.
- La cohabitation de deux systèmes (layout/* ancien + views/components/* DS v2.0) est une dette importante.

**🟡 HAUTE — Double implémentation de l'assistant IA**
- `layout/footer.php` contient un assistant IA complet (chat, vocal, photo).
- `views/components/ia_assistant.php` est le composant officiel DS v2.0.
- Les pages migrées incluent `ia_assistant.php` mais les pages non migrées incluent `footer.php` qui contient aussi un IA.
- Risque : deux instances JS IA sur certaines pages.


---

## PARTIE 2 — AUDIT DES COMPOSANTS

### 2.1 Inventaire d'utilisation

**Composants bien utilisés (parcours visiteur + client) :**
- `navbar_client.php` : ✅ utilisé dans tous les parcours migrés
- `hero_premium.php` : ✅ `views/home.php`
- `hero_capsule.php` : ✅ `views/client/dashboard.php`
- `service_card.php` : ✅ `views/client/dashboard.php`
- `empty_state.php` : ✅ utilisé dans 6 pages
- `toast.php` : ✅ utilisé dans 5 pages
- `modal.php` : ✅ utilisé dans `mes_rendezvous.php`, `gestion_catalogue.php`, `portefeuille.php`
- `footer_global.php` : ✅ utilisé dans toutes les pages migrées
- `bottom_nav_client.php` : ✅ utilisé dans toutes les pages client
- `action_bar.php` : ✅ utilisé dans toutes les pages coiffeur
- `statistic_card.php` : ✅ `portefeuille.php`
- `timeline_item.php` : ✅ `portefeuille.php`
- `information_block.php` : ✅ `portefeuille.php`
- `gallery_card.php` : ✅ `profil_public.php`, `gestion_catalogue.php`
- `form_field.php` : ✅ `gestion_catalogue.php`, `profil.php`
- `notification_dropdown.php` : ✅ `navbar_client.php`

**Composants existants jamais utilisés :**
- `appointment_card.php` : 🔴 jamais inclus dans aucune page
- `auth_card.php` : ⚠️ n'est pas inclus en tant que composant — `connexion.php` et `inscription.php` réimplementent les classes `.auth-card` directement
- `availability_card.php` : ⚠️ les classes `.day-selector-card`, `.day-item-open` sont utilisées inline dans `profil_public.php` sans inclure le composant
- `avatar.php` : ⚠️ jamais inclus — les avatars sont réimplémentés partout avec `.avatar-placeholder`
- `badges.php` : ⚠️ jamais inclus — les badges sont créés directement en HTML partout
- `buttons.php` : ⚠️ jamais inclus — les boutons sont des classes CSS, pas des composants PHP
- `data_table.php` : ⚠️ jamais inclus — les tableaux sont codés directement
- `floating_card.php` : ⚠️ jamais inclus
- `floating_section.php` : ⚠️ jamais inclus
- `hero_compact.php` : ⚠️ jamais inclus (remplacé par CSS local dans `annuaire_coiffeurs.php`)
- `navbar_coiffeur.php` : ⚠️ existe mais `layout/header_coiffeur.php` est utilisé à la place
- `pagination.php` : ⚠️ jamais inclus — aucune pagination implémentée
- `profile_summary.php` : ⚠️ jamais inclus — `profil_public.php` réimplémente le bloc profil
- `review_card.php` : ⚠️ jamais inclus — les avis sont codés inline dans chaque page
- `search_capsule.php` : ⚠️ jamais inclus — les barres de recherche sont réimplémentées
- `slots_container.php` : ⚠️ jamais inclus — les créneaux sont codés inline dans `profil_public.php`
- `status_badge.php` : ⚠️ jamais inclus — les badges statuts sont des `match()` + HTML inline


### 2.2 Anomalies composants

**🟡 HAUTE — Composants PHP sous-utilisés vs réimplémentations inline**
- La bibliothèque de composants est bien créée mais la majorité des composants (17/34) ne sont jamais inclus via `include`.
- Cela signifie que le pattern `$var = [...]; include 'composant.php'` n'est appliqué que sur ~50% de la bibliothèque.
- Conséquence : la dette HTML inline reste élevée dans les pages migrées.

**🟡 HAUTE — `navbar_coiffeur.php` vs `layout/header_coiffeur.php` : doublon de navigation**
- `views/components/navbar_coiffeur.php` existe mais n'est pas utilisé.
- `layout/header_coiffeur.php` remplit ce rôle mais génère aussi le `<html>` + `<head>`.
- Cette dualité empêche les pages coiffeur d'avoir le même schéma d'architecture que les pages client.

**🟠 MOYENNE — `views/coiffeur/dashboard.php` n'utilise aucun composant de la bibliothèque**
- Ni `service_card`, ni `statistic_card`, ni `action_bar`, ni `navbar_client`/`navbar_coiffeur`.
- Tout est codé en CSS inline et HTML custom.
- Ce dashboard coiffeur est architecturalement isolé du reste du projet.

---

## PARTIE 3 — AUDIT CSS

### 3.1 Fichiers CSS actifs

| Fichier | Statut | Usage |
|---|---|---|
| `css/variables.css` | ✅ Actif | Chargé dans toutes les pages migrées |
| `css/animations.css` | ✅ Actif | Chargé dans toutes les pages migrées |
| `css/components.css` | ✅ Actif | Chargé dans toutes les pages migrées |
| `css/global.css` | ⚠️ Présent | Non chargé — statut inconnu |
| `css/responsive-custom.css` | ⚠️ Présent | Non chargé — statut inconnu |
| `css/style.css` | ⚠️ Présent | Non chargé dans les pages vérifiées |

### 3.2 Anomalies CSS détectées

**🟡 HAUTE — Styles inline massifs dans `views/coiffeur/dashboard.php`**
- 400+ lignes de CSS dans un bloc `<style>` interne.
- Redéfinit des tokens comme `--gold: #D4AF37`, `--gold-dim`, `--dark`, etc.
- Ces définitions n'utilisent pas `variables.css` et peuvent diverger si les tokens DS changent.

**🟡 HAUTE — Styles inline dans `first/admin_dashboard.php`**
- 120+ lignes de CSS dans le `<head>`.
- Variables CSS autonomes : `--gold: #D4AF37`, `--dark-bg: #000000`, `--card-bg: #0c0c0c`.
- Classes spécifiques admin : `.card-custom`, `.table-premium`, `.btn-whatsapp`, `.prevention-banner`.

**🟡 HAUTE — `profil.php` : 60+ lignes CSS inline avec classes non-DS**
- Utilise `.profil-card`, `.metric-box`, `.metric-highlight`, `.chart-box`, etc.
- Ces classes sont locales à la page et non documentées dans le DS.

**🟠 MOYENNE — Styles inline dans les sections `<style>` de chaque page migrée**
- Chaque page migrée (client + visiteur) contient un bloc `<style>` avec 20-80 lignes.
- La règle "zéro style inline" du DS n'est pas pleinement appliquée.
- Certains de ces styles sont légitimement spécifiques (ex: `.rdv-card`, `.avis-page`), d'autres pourraient être centralisés.

**🟠 MOYENNE — `css/global.css`, `css/responsive-custom.css`, `css/style.css` : statut ambigu**
- Ces fichiers existent mais ne sont jamais chargés dans les pages vérifiées.
- Soit ils sont obsolètes (à supprimer), soit oubliés (à intégrer).
- Sans analyse de contenu, impossible de savoir s'ils contiennent des règles importantes.

**🟢 FAIBLE — `btn-gold` : couleur correcte dans toutes les pages migrées**
- La correction historique (`#ffc107` → `#D4AF37`) a bien été appliquée partout.


---

## PARTIE 4 — AUDIT RESPONSIVE

### 4.1 État responsive par composant

| Composant / Élément | Desktop | Tablette | Mobile | Notes |
|---|---|---|---|---|
| Navbar client (`navbar_client.php`) | ✅ | ✅ | ✅ | Responsive natif |
| Hero Premium (`views/home.php`) | ✅ | ✅ | ✅ | `clamp()` utilisé |
| Hero Capsule (`dashboard.php`) | ✅ | ✅ | ✅ | — |
| Annuaire coiffeurs | ✅ | ✅ | ⚠️ | Filtre 5 colonnes trop dense sur 360px |
| Profil public | ✅ | ✅ | ✅ | Days scroll horizontal OK |
| Réservation | ✅ | ✅ | ✅ | Grid heures fluide |
| Mes réservations | ✅ | ✅ | ✅ | Table/Cards switch OK |
| Catalogue client | ✅ | ✅ | ✅ | — |
| Dashboard coiffeur (`views/coiffeur/dashboard.php`) | ✅ | ⚠️ | ⚠️ | Sidebar fixe non gérée sur tablette |
| Gestion catalogue coiffeur | ✅ | ✅ | ⚠️ | Formulaire + grille : dense sur 375px |
| Admin dashboard | ✅ | ⚠️ | ⚠️ | Onglets et tableaux non optimisés mobile |
| `profil.php` | ✅ | ✅ | ✅ | — |
| Bottom nav client | — | — | ✅ | — |
| Bottom nav coiffeur | — | — | ✅ | — |

### 4.2 Anomalies Responsive

**🟡 HAUTE — `views/coiffeur/dashboard.php` sidebar desktop seulement**
- La sidebar de 240px est `position:fixed` et ne s'affiche que ≥901px.
- Sur tablette (768px-900px), la sidebar est masquée ET le contenu principal n'a pas de menu mobile adéquat.
- La `bottom-nav` se déclenche à ≤900px mais ne couvre pas tous les écrans tablette intermédiaires.

**🟠 MOYENNE — Annuaire coiffeurs : filtres en 5 colonnes sur mobile**
- La ligne de filtres contient 5 champs + 1 bouton dans un `row g-3`.
- Sur 375px, chaque champ fait ~60px : illisible pour les labels tronqués.
- Recommandation : grouper en accordion ou en 2 rangées sur mobile.

**🟠 MOYENNE — `first/admin_dashboard.php` : onglets et tableaux non responsive**
- Les 4 onglets s'affichent en ligne → overflow horizontal sur mobile.
- Les tableaux `table-premium` n'ont pas de fallback card mobile.

**🟢 FAIBLE — `profil.php` Charts.js**
- Les graphiques Chart.js ont `max-height:160px` mais pas de `maxWidth`.
- Peuvent déborder sur très petits écrans.


---

## PARTIE 5 — AUDIT DES PARCOURS

### 5.1 Parcours Visiteur

```
Accueil (views/home.php)
  ↓ ✅ CTA "Trouver un coiffeur" → annuaire_coiffeurs.php
  ↓ ✅ CTA "Voir le profil" → profil_public.php
Annuaire (filter/annuaire_coiffeurs.php)
  ↓ ✅ Bouton "Réserver un créneau" → profil_public.php?id=X
Profil Public (coiffeurs/profil_public.php)
  ↓ ✅ Si visiteur non connecté → créneau visible, redirige vers login
  ↓ ✅ Bouton "Voir profil & Réserver" (catalogue.php) → profil_public.php
Connexion (access/connexion.php)
  ↓ ✅ Redirect après login → dashboard rôle
Inscription (access/inscription.php)
  ↓ ✅ Après inscription → dashboard client/coiffeur
Réservation (client/reserver.php)
  ↓ ✅ Formulaire → INSERT rendez_vous
  ↓ ✅ Notification coiffeur après réservation
```

**Ruptures détectées dans le parcours visiteur :**

**🟡 HAUTE — `client/reserver.php` accessible depuis `profil_public.php` avec `coiffeur_id` mais pas `presta_id`**
- Sur `profil_public.php`, le slot de créneau génère : `reserver.php?coiffeur_id=X&presta_id=Y&date_rdv=Z&heure_debut=H`
- Si le visiteur clique sur une slot **sans avoir sélectionné de prestation spécifique**, `presta_id` peut être vide.
- `reserver.php` chargera alors une prestation `id_prestation = 0` → `$prestation = null` → empty state.
- La navigation "choisir une prestation puis réserver" n'est pas guidée explicitement.

**🟠 MOYENNE — Pas de page de confirmation de réservation dédiée**
- Après une réservation réussie dans `reserver.php`, un message flash est affiché sur la même page.
- Il n'y a pas de page `/confirmation.php` ou de redirection vers `mes_rendezvous.php` automatique.
- L'UX de confirmation est minimal.

**🟠 MOYENNE — Pas de page de paiement réel dans le parcours**
- Le parcours documente "Réservation → Paiement → Confirmation" mais le paiement est simulé (`$mode_test = true`).
- `traiter_recharge.php` appelle `PaymentService.php` mais celui-ci est en mode simulation.
- Le parcours paiement Kkiapay n'est pas intégré.

### 5.2 Parcours Client

```
Dashboard (views/client/dashboard.php)
  ↓ ✅ Service Cards → profil_public.php
Mes Réservations (client/mes_rendezvous.php)
  ↓ ✅ Modal gestion + annulation
  ↓ ✅ Lien "Laisser un avis" pour RDV terminés
Catalogue (client/catalogue.php)
  ↓ ✅ Lien profil coiffeur unifié (?id=)
Laisser un avis (client/laisser_avis.php)
  ↓ ✅ Formulaire → traitement_avis.php
Créer RDV (client/creer_rendezvous.php)
  ↓ ✅ Sélecteur jours + heures
  ↓ ✅ Notification coiffeur
```

**Ruptures détectées :**

**🔴 CRITIQUE — `client/creer_rendezvous.php` : colonnes BDD incorrectes dans le INSERT**
- Ligne INSERT : `(id_client, id_coiffeur, id_prestation, date_rdv, heure_rdv, statut_rdv, date_creation)`
- La colonne `heure_rdv` n'existe probablement pas — dans `mes_rendezvous.php` la colonne est `heure_debut`.
- Dans `reserver.php` le INSERT utilise `(client_id, coiffeur_id, coiffure_id, date_rdv, heure_debut, heure_fin, statut_rdv)`.
- Il y a deux pages de réservation avec des schémas SQL différents — l'une d'elles est incorrecte.

**🟡 HAUTE — Double route de réservation : `reserver.php` vs `creer_rendezvous.php`**
- `client/reserver.php` : réservation depuis profil public, avec sélection de prestation.
- `client/creer_rendezvous.php` : réservation depuis dashboard, avec sélection de prestation par ID URL.
- Les deux pages font la même chose différemment. Clarifier quelle page est la route principale.

**🟠 MOYENNE — `client/notifications.php` : lien retour pointe vers une URL absolue de fichier**
- Le bouton retour pointe vers `/coiffons/views/client/dashboard.php` directement.
- La route correcte devrait être `/coiffons/index.php?page=dashboard`.


### 5.3 Parcours Coiffeur

```
Dashboard (views/coiffeur/dashboard.php)
  ↓ Inclus via index.php?page=dashboard_coiffeur
  ↓ Sidebar avec liens directs vers pages coiffeur
Gestion Catalogue (coiffeurs/gestion_catalogue.php)
  ↓ ✅ CRUD prestations + upload photos
Mon Agenda (coiffeurs/agenda_coiffeurs.php)
  ↓ ✅ Disponibilités hebdomadaires
Valider RDV (coiffeurs/valider_rendezvous.php)
  ↓ ✅ Accepter/Refuser + transactions portefeuille
Portefeuille (coiffeurs/portefeuille.php)
  ↓ ✅ Solde, abonnement, historique
Profil (coiffeurs/profil_coiffeurs.php) — NON MIGRÉ
Mes Zones (coiffeurs/mes_zones.php) — NON MIGRÉ
Mes Avis (coiffeurs/mes_avis.php) — NON MIGRÉ
```

**Anomalies parcours coiffeur :**

**🟡 HAUTE — 3 pages coiffeur non migrées : `profil_coiffeurs.php`, `mes_zones.php`, `mes_avis.php`**
- Ces pages sont référencées dans la sidebar du dashboard coiffeur.
- Leur statut de migration est inconnu (non lues dans cet audit).
- Risque : pages en rupture visuelle dans le parcours coiffeur.

**🟡 HAUTE — `layout/header_coiffeur.php` : lien vers `profil_coiffeurs.php` dans la topbar**
- L'avatar navigue vers `/coiffons/coiffeurs/profil_coiffeurs.php` qui n'a pas été migré.
- Risque : rupture visuelle à partir du clic profil.

**🟠 MOYENNE — Robot d'expiration dans `valider_rendezvous.php` s'exécute à chaque chargement**
- Le robot de nettoyage des RDV expirés (boucle foreach + UPDATE/INSERT) s'exécute à chaque visite de la page.
- Sans queue ni cron job, les requêtes BDD sont redondantes à chaque rechargement.

### 5.4 Parcours Admin

**🟡 HAUTE — `first/admin_actions.php` action `valider_fraude_contournement` : bug SQL résiduel**
- Ligne : `$stmt1 = $pdo->prepare("UPDATE users SET statut = 'banni', raison_ban = 'Contournement...' WHERE id_user = ?");`
- La colonne est `id` et non `id_user` dans la table `users`.
- Cette action planterait si elle était déclenchée.

**🟡 HAUTE — `first/admin_actions.php` action `pousser_notification` : colonne incorrecte**
- `INSERT INTO notifications (id_user, message, statut_lecture)` — la colonne `statut_lecture` n'accepte probablement pas `0` (entier) mais `'non_lu'` (string).
- La même colonne dans toutes les autres pages utilise `'non_lu'`.

**🔴 CRITIQUE — Redirection après connexion admin : chemin relatif**
- Dans `connexion.php` : `header("Location: /coiffons/first/admin_dashboard.php")` ✅
- Dans `first/admin_dashboard.php` : la navbar Déconnexion pointe vers `../deconnexion.php` (chemin relatif) ✅


---

## PARTIE 6 — AUDIT FONCTIONNEL

### 6.1 Flux de données inter-pages

| Flux | Statut | Anomalie |
|---|---|---|
| Accueil → Annuaire → Profil | ✅ Intact | — |
| Profil → Créneau → Connexion → Réservation | ✅ Intact | `redirect=` URL en paramètre GET |
| Réservation → INSERT rendez_vous | ⚠️ Double route | `reserver.php` vs `creer_rendezvous.php` |
| Réservation → Notification coiffeur | ✅ Intact | — |
| RDV terminé → Avis client | ✅ Intact | Vérification anti-doublon par JOIN |
| Avis → Profil coiffeur | ✅ Intact | Note moyenne recalculée en temps réel |
| Coiffeur Accepte RDV → Transaction portefeuille | ✅ Intact | Commission 5% déduite |
| Coiffeur Refuse RDV → Remboursement client | ✅ Intact | — |
| Client Annule RDV → Règle dégressive | ✅ Intact | +24h=100%, 2-24h=50%, -2h=0% |
| Recharge portefeuille → PaymentService | ⚠️ Mode test | `$mode_test = true` partout |
| Inscription → Diplôme upload | ✅ Intact | Base64 compress + file_put_contents |
| Admin valide diplôme → is_approved=1 + notification | ✅ Intact | — |

### 6.2 Anomalies fonctionnelles

**🔴 CRITIQUE — `creer_rendezvous.php` : colonne `heure_rdv` inexistante**
- Le INSERT utilise la colonne `heure_rdv` mais la BDD utilise `heure_debut` (confirmé par `mes_rendezvous.php` et `valider_rendezvous.php`).
- Cette page est donc non fonctionnelle en l'état.

**🔴 CRITIQUE — `profil.php` : détection abonnement coiffeur incorrecte**
- Ligne : `if (isset($user['abonnement_actif']) && $user['abonnement_actif'] == 1)`
- La colonne dans la BDD est `abonnement_status` (pas `abonnement_actif`).
- Le bloc "Compte Actif" / "Abonnement Expiré" ne s'affiche jamais correctement pour les coiffeurs.

**🟡 HAUTE — `traitement_avis.php` n'est pas dans les fichiers vérifiés**
- `laisser_avis.php` soumet à `/coiffons/traitement_avis.php`.
- Ce fichier n'était pas dans le scope de l'audit — son existence et sa conformité DS/SQL n'ont pas été vérifiées.

**🟡 HAUTE — `modifier_profil.php` non migré**
- `profil.php` contient un lien "MODIFIER MON PROFIL" → `/coiffons/modifier_profil.php`.
- Ce fichier n'a pas été vérifié dans cet audit.
- Dans le résumé de contexte, il était noté comme ayant un bug : `--gold: #f39c12` au lieu de `#D4AF37`.

**🟠 MOYENNE — `client/notifications.php` : route de retour incorrecte**
- Les deux liens "Retour au dashboard" pointent vers `/coiffons/views/client/dashboard.php`.
- Route correcte : `/coiffons/index.php?page=dashboard`.
- Accès direct au fichier PHP contourne le routeur `index.php`.

**🟠 MOYENNE — `profil.php` : suppression compte ne nettoie pas toutes les tables**
- `DELETE FROM users WHERE id = ?` : supprime l'utilisateur.
- Mais les rendez_vous, commentaires, notifications, transactions_portefeuille ne sont pas supprimés.
- Cela laisse des données orphelines en BDD.


---

## PARTIE 7 — AUDIT PHP

### 7.1 Problèmes PHP détectés

**🔴 CRITIQUE — `client/creer_rendezvous.php` : nom de colonne SQL incorrect**
- Fichier : `client/creer_rendezvous.php`, dans le bloc INSERT
- Colonne utilisée : `heure_rdv`
- Colonne réelle BDD : `heure_debut`
- Gravité : CRITIQUE — INSERT échoue en silence (PDO Exception non catchée au bon niveau)

**🔴 CRITIQUE — `profil.php` : variable `$user['abonnement_actif']` inexistante**
- Fichier : `profil.php`, bloc statut professionnel coiffeur
- Variable utilisée : `$user['abonnement_actif']`
- Variable réelle : `$user['abonnement_status']`
- Gravité : CRITIQUE — le badge de statut coiffeur ne s'affiche jamais

**🟡 HAUTE — `first/admin_actions.php` : `WHERE id_user = ?` dans action fraude**
- Fichier : `first/admin_actions.php`, action `valider_fraude_contournement`
- Ligne : `WHERE id_user = ?` dans UPDATE users
- Colonne réelle : `WHERE id = ?`
- Gravité : HAUTE — l'action échoue silencieusement

**🟡 HAUTE — `first/admin_actions.php` : `statut_lecture = 0` type incorrect dans notifications**
- Fichier : `first/admin_actions.php`, actions `pousser_notification` et `valider_fraude`
- Valeur utilisée : `0` (entier)
- Valeur attendue : `'non_lu'` (chaîne)
- Gravité : HAUTE — les notifications pushées ne s'affichent pas correctement

**🟡 HAUTE — `security/config.php` : credentials en clair dans le code**
- `$host = 'localhost'`, `$dbname = 'cft'`, `$username = 'root'`, `$password = ''`
- Ces valeurs devraient être dans le `.env` et lues via `$_ENV`.
- Le fichier `.env` existe mais `config.php` ne l'utilise pas.
- Gravité : HAUTE pour la production (risque d'exposition de credentials en dépôt public)

**🟡 HAUTE — `index.php` et `views/client/dashboard.php` : `$coiffeurs_matching` peut contenir des doublons**
- La requête utilise `DISTINCT` sur `p.*` mais joint `prestations` à `users`.
- Un coiffeur avec plusieurs prestations peut apparaître plusieurs fois si `DISTINCT` ne s'applique qu'aux colonnes sélectionnées.
- Gravité : HAUTE UX — doublon de cartes coiffeurs sur le dashboard

**🟠 MOYENNE — `views/coiffeur/dashboard.php` : champ `client_adresse_texte` / `client_gps_lat` / `client_gps_lng`**
- Ces colonnes sont utilisées dans le prochain RDV mais n'existent peut-être pas dans la table `rendez_vous` actuelle.
- Gravité : MOYENNE — le bloc adresse ne s'affiche pas si colonnes absentes

**🟠 MOYENNE — `client/catalogue.php` : vérification photo avec chemin relatif**
- `file_exists(__DIR__ . '/../' . $p['photo_style'])` — chemin relatif à `client/`
- Si la photo est en `uploads/prestations/`, le chemin devient `client/../uploads/prestations/` → correct.
- Mais si la photo est stockée avec un chemin absolu `/coiffons/uploads/...`, le `file_exists` échoue.

**🟠 MOYENNE — `views/home.php` : requête avis avec `c.message != ''`**
- Filtre les avis sans message, mais `c.message` pourrait être NULL (non vide, mais NULL).
- Utiliser `c.message IS NOT NULL AND c.message != ''` pour être robuste.

**🟠 MOYENNE — Absence de gestion des sessions expirées côté client**
- Aucune page ne vérifie la durée de vie des sessions.
- Un utilisateur avec une session expirée en BDD peut toujours accéder aux pages protégées jusqu'à fermeture du navigateur.


---

## PARTIE 8 — AUDIT DES ASSETS

### 8.1 Images et médias

| Dossier | Contenu | Statut |
|---|---|---|
| `/images/` | 14 images JPG (coupe, dame, logo, etc.) | ⚠️ Peu utilisées — `views/home.php` utilise `/imgid/` |
| `/imgid/` | 12 images WebP premium | ✅ Utilisées dans les heroes |
| `/access/uploads/diplomes/` | 3 diplômes coiffeurs | ✅ Chargés par path BDD |
| `/access/uploads/profil/` | 10 photos profil | ✅ Chargées par path BDD |
| `/coiffeurs/uploads/prestations/` | Vide | ⚠️ Dossier créé, aucune image |

### 8.2 Anomalies Assets

**🟡 HAUTE — `/coiffeurs/uploads/prestations/` vide mais référencé**
- `gestion_catalogue.php` sauvegarde les photos dans `uploads/prestations/` (chemin relatif à `coiffeurs/`).
- Le dossier `coiffeurs/uploads/prestations/` existe mais est vide.
- Le code vérifie aussi `__DIR__ . '/../uploads/prestations/'` ce qui pointe vers `coiffons/uploads/prestations/`.
- Il pourrait y avoir une confusion de chemin entre `/coiffeurs/uploads/` et `/uploads/` à la racine.

**🟠 MOYENNE — `images/` dossier présent mais peu utilisé**
- `views/home.php` utilise `/imgid/` pour les slides hero.
- `layout/header.php` référence `images/logo.png` pour le logo.
- Les 13 autres images dans `/images/` ne sont probablement pas utilisées dans les pages migrées.

**🟠 MOYENNE — Pas de lazy loading sur les images de l'annuaire**
- `annuaire_coiffeurs.php` : les photos de profil dans la grille n'ont pas `loading="lazy"`.
- Sur une liste de 20+ coiffeurs, cela peut ralentir le premier rendu.

**🟢 FAIBLE — Taille des images WebP dans `/imgid/` non vérifiée**
- Ces images sont utilisées comme fond de hero (slider).
- Si certaines pèsent > 500KB, l'animation de slider peut paraître lente sur mobile.

---

## PARTIE 9 — AUDIT JAVASCRIPT

### 9.1 Inventaire des scripts

| Fonctionnalité | Implémentation | Statut |
|---|---|---|
| Toast / Flash | `views/components/toast.php` + auto-affichage | ✅ |
| Modales | `openModal()` / `closeModal()` dans `components.css` ou JS global | ⚠️ Fonction non localisée |
| Dropdown notifications | `notification_dropdown.php` + AJAX `marquer_notifs_lu.php` | ✅ |
| Calcul heure de fin RDV | `reserver.php` inline | ✅ |
| Sélecteur jours/heures | `profil_public.php` + `creer_rendezvous.php` inline | ✅ |
| AJAX quartiers | `annuaire_coiffeurs.php`, `inscription.php` via `get_quartiers.php` | ✅ |
| Assistant IA | `views/components/ia_assistant.php` | ✅ |
| Assistant IA (ancien) | `layout/footer.php` | ⚠️ Doublon |
| Reconnaissance vocale | `layout/footer.php` inline | ⚠️ Dans ancien footer |
| Chart.js | `profil.php`, `first/admin_dashboard.php` | ✅ |
| Catalogue cartes cliquables | `client/catalogue.php` inline | ✅ |
| Toggle mot de passe | `connexion.php`, `inscription.php` inline | ✅ |
| Options dynamiques catalogue | `gestion_catalogue.php` inline | ✅ |
| Sidebar toggle dashboard coiffeur | `views/coiffeur/dashboard.php` inline | ✅ |

### 9.2 Anomalies JavaScript

**🔴 CRITIQUE — `openModal()` / `closeModal()` : fonctions non trouvées dans les composants vérifiés**
- Ces fonctions sont appelées dans `mes_rendezvous.php`, `profil.php`, `gestion_catalogue.php`, `portefeuille.php`.
- Elles ne sont pas définies dans `views/components/modal.php` (qui ne contient que le HTML).
- Elles doivent être dans `css/components.css` (script section) ou dans un fichier JS global non identifié.
- Si le fichier qui les définit n'est pas chargé, les modales ne s'ouvrent pas.
- Vérification urgente requise dans `js/script.js` ou `css/components.css`.

**🟡 HAUTE — Deux instances IA potentielles**
- Pages utilisant `layout/footer.php` (ancien) + `views/components/ia_assistant.php` (nouveau).
- Si une page charge les deux (ex: `profil.php` utilise `navbar_client.php` + ancien footer), deux fenêtres de chat peuvent coexister.

**🟡 HAUTE — `views/coiffeur/dashboard.php` : pas de JS externe, tout en inline**
- ~50 lignes de JS inline dans le dashboard coiffeur pour la sidebar mobile.
- Pas critique mais crée de la dette.

**🟠 MOYENNE — `valider_rendezvous.php` : actions accepter/refuser via GET sans CSRF**
- Les actions d'acceptation/refus de RDV se font par `<a href="?action=accepter&id_rdv=X">`.
- Pas de token CSRF sur ces actions GET.
- Vulnérabilité CSRF potentielle (voir Partie 12).

**🟠 MOYENNE — `inscription.php` : pushState anti-retour peut dérouter l'utilisateur**
- `history.pushState(null, null, location.href)` + `popstate` bloque le bouton retour.
- L'intention est de protéger le formulaire, mais peut être une expérience frustrante sur mobile.


---

## PARTIE 10 — AUDIT LOGIQUE MÉTIER

### 10.1 Analyse critique de la logique

**✅ Points forts de la logique métier**
- Règle d'annulation dégressive (+24h/2-24h/-2h) : logique correcte et bien implémentée dans `mes_rendezvous.php`.
- Commission 5% coiffeur : calculée et stockée dans `transactions_portefeuille` au moment de l'acceptation.
- Gel des fonds à la réservation + libération sur refus/expiration : pattern sécurisé.
- Robot d'expiration dans `valider_rendezvous.php` : concept correct (timeout 15min si même jour, 1h sinon).
- Anti-doublon d'avis : vérification par JOIN sur `commentaires` avant d'afficher le formulaire.
- Vérification solde avant réservation : barrière financière présente dans `reserver.php` et `creer_rendezvous.php`.

**Améliorations recommandées (sans modifier la logique actuelle) :**

**🟠 MOYENNE — Gestion des statuts RDV incohérente**
- `reserver.php` utilise : `en_attente` pour le statut initial.
- `valider_rendezvous.php` utilise : `confirme` ou `accepte` pour l'acceptation.
- `mes_rendezvous.php` gère les deux (`confirme` et `accepte`) avec `in_array`.
- Un statut unique (`confirme` OU `accepte`, pas les deux) réduirait la complexité.

**🟠 MOYENNE — `portefeuille.php` : solde via SUM sur transactions**
- Le solde disponible est recalculé à chaque visite : `SELECT SUM(montant)`.
- Sans cache ni colonne `solde` dénormalisée dans `users` (ou la table `portefeuilles`), cette requête peut être lente avec de nombreuses transactions.
- Note : `profil.php` lit `$user['solde']` directement depuis `users` — incohérence avec `portefeuille.php`.

**🟠 MOYENNE — Abonnement coiffeur : logique de renouvellement auto non implémentée**
- `portefeuille.php` permet d'activer/désactiver le renouvellement automatique.
- Mais il n'existe pas de cron job ou de trigger qui effectue le prélèvement automatique.
- La feature est UX seulement, sans backend opérationnel.

**🟠 MOYENNE — Calcul du solde client en mode test (`$mode_test = true`)**
- Dans `reserver.php` et `creer_rendezvous.php` : `$mode_test = true` force un solde fictif de 15 000 FCFA.
- Le gel réel ne se fait pas en BDD côté client.
- La plateforme n'est pas prête pour un paiement réel avant désactivation du mode test.

**🟢 FAIBLE — Catalogue `client/catalogue.php` : lien `presta_id` dans l'URL vers `profil_public.php`**
- `profil_public.php` reçoit `?presta_id=X` mais ne l'utilise que dans les URLs des slots JS.
- Si l'utilisateur arrive sur le profil sans prestation sélectionnée, les slots génèrent quand même un `reserver.php` correct.
- Comportement acceptable mais une sélection de prestation pré-remplie serait plus fluide.


---

## PARTIE 11 — AUDIT PERFORMANCE

### 11.1 Problèmes identifiés

**🟡 HAUTE — Requêtes N+1 dans `index.php` (home)**
- Sur la page d'accueil visiteur, la requête coiffeurs populaires est suivie par un affichage de 6 cartes.
- Si chaque carte devait charger des données supplémentaires (avis, disponibilités), ce serait N+1.
- Actuellement : données agrégées en une seule requête avec sous-requêtes. Acceptable.

**🟡 HAUTE — Robot d'expiration dans `valider_rendezvous.php` : exécution synchrone**
- À chaque visite de la page, la requête de vérification des RDV expirés s'exécute.
- Sur un coiffeur avec 50 RDV en attente, la boucle PHP fait 50 itérations avec des transactions BDD.
- Recommandation : déplacer dans `security/cron_notifications.php` qui existe déjà.

**🟡 HAUTE — Multiple chargements Bootstrap + Google Fonts sur chaque page**
- Chaque page charge indépendamment Bootstrap 5.3.3 (JS + CSS) depuis jsDelivr.
- Chaque page charge Google Fonts avec 2 familles.
- Sans cache navigateur établi, chaque navigation charge ces assets depuis le réseau.
- Impact sur réseau africain (Bénin) où la latence CDN peut être élevée.

**🟠 MOYENNE — `views/home.php` : 3 requêtes SQL distinctes**
- Coiffeurs populaires, avis récents, stats (4 requêtes aggregées) = 7 requêtes SQL au total pour la home.
- Acceptable mais une mise en cache courte (5-10min) des stats et coiffeurs populaires serait bénéfique.

**🟠 MOYENNE — Images dans `/images/` non converties en WebP**
- Les 14 images en `/images/*.jpg` ne sont pas converties en WebP.
- Seul le dossier `/imgid/` contient des WebP optimisés.
- Les pages qui utilisent encore `/images/*.jpg` servent des formats lourds.

**🟠 MOYENNE — `profil.php` : 8 requêtes SQL pour les statistiques**
- Les 4 blocs de stats + 2 graphiques + commentaires + note moyenne = 8+ requêtes SQL.
- Toutes dans un seul `try` global — une seule erreur masque toutes les autres.

**🟢 FAIBLE — `navbar_client.php` : requête BDD à chaque inclusion**
- La navbar charge les 5 dernières notifications à chaque inclusion.
- Chaque page qui inclut `navbar_client.php` fait une requête SQL de plus.
- Acceptable pour une V1 mais à mettre en cache session à terme.


---

## PARTIE 12 — AUDIT SÉCURITÉ

### 12.1 Bilan sécurité

| Contrôle | Statut | Notes |
|---|---|---|
| Protection CSRF sur formulaires POST | ✅ Présent | `csrf.php` utilisé dans `connexion.php`, `inscription.php`, `mes_rendezvous.php` |
| Parameterized queries (PDO) | ✅ Généralisé | Toutes les requêtes utilisent `prepare()` + `execute()` |
| `htmlspecialchars()` sur affichage | ✅ Généralisé | Appliqué sur toutes les variables utilisateur affichées |
| Protection brute force login | ✅ Présent | 5 tentatives, blocage 15min en session |
| Vérification de rôle sur pages protégées | ✅ Généralisé | `$_SESSION['role']` vérifié sur chaque page |
| Uploads : validation extension + taille | ✅ Présent | Whitelist d'extensions, limite 5Mo |
| Credentials BDD | ⚠️ En clair | `config.php` contient les credentials, `.env` non utilisé |
| CSRF sur actions GET (coiffeur RDV) | 🔴 Absent | Accepter/Refuser RDV via `<a href="?action=X">` |
| Séparation des rôles admin/client/coiffeur | ✅ Présent | Chaque page vérifie le rôle exact |
| Suppression compte : données orphelines | ⚠️ Partiel | `DELETE FROM users` sans CASCADE sur FK |
| SQL dans `admin_actions.php` : `WHERE id_user` | 🔴 Bug | Colonne inexistante dans certaines actions |
| OTP / 2FA | 🔴 Absent | Pas d'authentification à double facteur |
| Rate limiting API IA | ⚠️ Inconnu | `ia_controlleur.php` non audité |
| Sécurité upload diplôme : MIME check | ⚠️ Extension seulement | Pas de vérification MIME réelle |

### 12.2 Anomalies Sécurité

**🔴 CRITIQUE — Actions accepter/refuser RDV sans CSRF (`valider_rendezvous.php`)**
- Les URLs `?action=accepter&id_rdv=X` et `?action=refuser&id_rdv=X` sont déclenchables par une simple requête GET.
- Un lien malveillant dans un email ou une page externe pourrait accepter/refuser des RDV à l'insu du coiffeur.
- Correction : convertir en formulaire POST avec token CSRF.

**🟡 HAUTE — `security/config.php` : credentials hardcodés**
- `$dbname = 'cft'`, `$username = 'root'`, `$password = ''`.
- Si ce fichier est accidentellement exposé ou commité, les credentials sont lisibles.
- Le fichier `.env` existe à la racine avec la structure `KEY=VALUE` mais `config.php` ne le lit pas.
- Correction : `$dbname = $_ENV['DB_NAME'] ?? 'cft'` etc.

**🟡 HAUTE — Upload diplôme : pas de vérification MIME réelle**
- `inscription.php` et `gestion_catalogue.php` vérifient l'extension du fichier.
- Pas de `finfo_file()` ou de vérification des premiers octets pour confirmer le type réel du fichier.
- Un fichier PHP renommé en `.jpg` passerait la validation d'extension.
- Correction : ajouter `finfo_open(FILEINFO_MIME_TYPE)` avant l'upload.

**🟡 HAUTE — `profil.php` : suppression compte sans confirmation serveur robuste**
- La suppression utilise `onclick="return confirm(...)"` (JS contournable).
- Le formulaire POST avec `confirmer_suppression` est vulnérable si un attaquant forge une requête POST.
- Il n'y a pas de re-saisie du mot de passe avant suppression.

**🟠 MOYENNE — `index.php` : pas de protection contre le SQLI sur `$_GET['page']`**
- `$page = $_GET['page'] ?? 'home'` puis utilisé dans un `switch`.
- Pas d'injection SQL possible ici (switch, pas de requête).
- Mais `$_GET['role']` est passé directement à `in_array()` puis assigné à `$_GET['role']` validé. ✅ Safe.

**🟠 MOYENNE — `admin_actions.php` : redirection vers URL externe non validée**
- Action `marquer_wa_envoye` : `header("Location: " . $redirect_url)` où `$redirect_url = $_GET['redirect']`.
- La valeur de `$redirect_url` vient directement de `$_GET['redirect']` sans validation.
- Un attaquant pourrait forger `?redirect=https://site-malveillant.com`.
- Correction : vérifier que l'URL commence par `https://wa.me/` avant la redirection.

**🟢 FAIBLE — `connexion.php` : compteur de tentatives stocké en session**
- Le compteur brute-force (`login_attempts`) est stocké en session PHP et non en BDD.
- Un attaquant peut contourner en effaçant les cookies.
- Pour la démo : acceptable. Pour la production : stocker en BDD par IP.


---

## LISTE EXHAUSTIVE DES ANOMALIES — CLASSEMENT PAR PRIORITÉ

### 🔴 CRITIQUE (bloquer la démo / production)

| # | Anomalie | Fichier | Correction | Temps |
|---|---|---|---|---|
| C1 | `creer_rendezvous.php` : colonne `heure_rdv` → `heure_debut` dans INSERT | `client/creer_rendezvous.php` | Modifier la colonne dans le SQL | 15 min |
| C2 | `profil.php` : `$user['abonnement_actif']` → `$user['abonnement_status']` | `profil.php` | Changer le nom de variable | 5 min |
| C3 | `admin_actions.php` : `WHERE id_user = ?` → `WHERE id = ?` dans action fraude | `first/admin_actions.php` | Changer la colonne SQL | 5 min |
| C4 | Ordre de chargement DS v2.0 dans les pages coiffeur (CSS chargé dans body) | `layout/header_coiffeur.php` | Ajouter les 3 `<link>` DS dans le `<head>` | 20 min |
| C5 | `openModal()` / `closeModal()` : vérifier que les fonctions sont bien disponibles | `js/script.js` ou `components.css` | Localiser et vérifier | 30 min |
| C6 | `admin_actions.php` : `statut_lecture = 0` → `statut_lecture = 'non_lu'` dans notifications | `first/admin_actions.php` | Changer la valeur | 10 min |

### 🟡 HAUTE (corriger avant démo)

| # | Anomalie | Fichier | Correction | Temps |
|---|---|---|---|---|
| H1 | `views/coiffeur/dashboard.php` : intégrer DS v2.0 (chargement CSS + variables) | `views/coiffeur/dashboard.php` | Migrer la page vers DS v2.0 | 2-3h |
| H2 | `admin_actions.php` : redirection vers URL externe sans validation (`$redirect_url`) | `first/admin_actions.php` | Whitelist URL `wa.me` | 15 min |
| H3 | Actions accepter/refuser RDV sans CSRF (GET link) | `coiffeurs/valider_rendezvous.php` | Convertir en formulaire POST + CSRF | 45 min |
| H4 | Robot d'expiration exécuté à chaque page load → déplacer dans cron | `coiffeurs/valider_rendezvous.php` | Extraire dans `security/cron_notifications.php` | 1h |
| H5 | Double implémentation IA : `footer.php` + `ia_assistant.php` | `layout/footer.php` | Supprimer le code IA de l'ancien footer | 30 min |
| H6 | `config.php` : credentials en clair → utiliser `.env` | `security/config.php` | Lire via `$_ENV` | 20 min |
| H7 | 3 pages coiffeur non migrées (`profil_coiffeurs.php`, `mes_zones.php`, `mes_avis.php`) | `coiffeurs/` | Migrer vers DS v2.0 | 3-4h |
| H8 | `client/notifications.php` : liens retour vers URL directe de fichier PHP | `client/notifications.php` | Corriger vers `/coiffons/index.php?page=dashboard` | 5 min |
| H9 | Upload : pas de vérification MIME réelle | `access/inscription.php`, `coiffeurs/gestion_catalogue.php` | Ajouter `finfo_open()` | 30 min |
| H10 | `modifier_profil.php` : bug `--gold: #f39c12` et statut migration inconnu | `modifier_profil.php` | Migrer + corriger couleur | 1-2h |

### 🟠 MOYENNE (corriger avant production)

| # | Anomalie | Fichier | Correction | Temps |
|---|---|---|---|---|
| M1 | Double route réservation : `reserver.php` vs `creer_rendezvous.php` | Les deux | Choisir une route principale | 1h |
| M2 | Pas de page de confirmation dédiée après réservation | Nouveau fichier | Créer `client/confirmation_rdv.php` | 1h |
| M3 | `profil.php` : suppression compte sans re-saisie mot de passe | `profil.php` | Ajouter vérification password | 30 min |
| M4 | `profil.php` : suppression compte ne nettoie pas les FK | `profil.php` | Ajouter DELETE en cascade ou ON DELETE CASCADE BDD | 1h |
| M5 | Filtres annuaire 5 colonnes : dense sur mobile 375px | `filter/annuaire_coiffeurs.php` | Regrouper en 2 lignes sur mobile | 30 min |
| M6 | Dashboard coiffeur sidebar non gérée tablette 768-900px | `views/coiffeur/dashboard.php` | Ajouter breakpoint tablette | 30 min |
| M7 | Chargement Bootstrap/Fonts sans preconnect → latence CDN élevée au Bénin | Toutes les pages | Ajouter `<link rel="preconnect">` | 15 min |
| M8 | Statuts RDV `confirme` et `accepte` coexistent sans nécessité | Multiple pages | Normaliser vers un seul statut | 1h |
| M9 | `css/global.css`, `responsive-custom.css`, `style.css` : statut ambigu | Ces fichiers | Vérifier le contenu et supprimer si obsolètes | 30 min |
| M10 | `traitement_avis.php` non audité : statut inconnu | `traitement_avis.php` | Audit + migration DS si nécessaire | 1h |

### 🟢 FAIBLE (optimisations futures)

| # | Anomalie | Correction | Temps |
|---|---|---|---|
| F1 | Styles inline `<style>` dans les pages migrées | Centraliser dans `components.css` | 2-3h |
| F2 | 17 composants jamais utilisés comme includes PHP | Adopter le pattern `$var = [...]; include` | 4-6h |
| F3 | Chart.js dans profil.php sans maxWidth responsive | Wrapper avec `max-width:100%` | 10 min |
| F4 | `views/home.php` requête avis : `c.message != ''` → IS NOT NULL | Corriger le filtre SQL | 5 min |
| F5 | Images `/images/` non converties WebP | Convertir ou supprimer | 30 min |
| F6 | Lazy loading absent sur images annuaire | Ajouter `loading="lazy"` | 10 min |
| F7 | Brute force login : stockage session → BDD par IP | Implémenter table `login_attempts` | 1h |
| F8 | Renouvellement abonnement auto sans cron backend | Implémenter cron PHP ou script | 2h |
| F9 | Cache court-terme pour stats homepage et navbar notifications | Utiliser `$_SESSION` ou APCu | 1h |
| F10 | `inscription.php` : pushState anti-retour → peut frustrer sur mobile | Remplacer par `beforeunload` | 20 min |


---

## ORDRE OPTIMAL DE RÉSOLUTION

### Phase 1 — Corrections bloquantes (2-3h) — AVANT TOUTE DÉMO

```
1. C1 — creer_rendezvous.php : heure_rdv → heure_debut                [15 min]
2. C2 — profil.php : abonnement_actif → abonnement_status             [5 min]
3. C3 + C6 — admin_actions.php : 2 bugs SQL                           [15 min]
4. C4 — header_coiffeur.php : déplacer CSS DS dans <head>              [20 min]
5. C5 — Vérifier openModal() / closeModal() disponibles               [30 min]
6. H8 — notifications.php : corriger liens retour                      [5 min]
```

### Phase 2 — Corrections sécurité (3-4h) — AVANT DÉMO

```
7. H3 — valider_rendezvous.php : CSRF sur accepter/refuser             [45 min]
8. H2 — admin_actions.php : valider URL redirection WhatsApp           [15 min]
9. H6 — config.php : lire credentials depuis .env                     [20 min]
10. H9 — Vérification MIME upload diplôme                              [30 min]
```

### Phase 3 — Migrations restantes (4-6h) — AVANT DÉMO

```
11. H7 — Migrer profil_coiffeurs.php                                   [1h]
12. H7 — Migrer mes_zones.php                                          [1h]
13. H7 — Migrer mes_avis.php                                           [1h]
14. H10 — Migrer modifier_profil.php + corriger couleur gold           [1-2h]
```

### Phase 4 — Refactorisation dashboard coiffeur (2-3h) — RECOMMANDÉ AVANT DÉMO

```
15. H1 — Intégrer DS v2.0 dans views/coiffeur/dashboard.php           [2-3h]
16. H5 — Nettoyer double IA dans layout/footer.php                    [30 min]
```

### Phase 5 — Améliorations UX (2-3h) — APRÈS DÉMO

```
17. M5 — Responsive filtres annuaire sur mobile
18. M6 — Sidebar coiffeur tablette
19. M7 — Preconnect CDN
20. M9 — Nettoyer CSS obsolètes
21. M10 — Auditer traitement_avis.php
```

### Phase 6 — Optimisations production (6-10h) — AVANT PRODUCTION

```
22. M1 — Unifier les deux routes de réservation
23. M2 — Page de confirmation dédiée
24. M4 — Suppression compte : nettoyer FK
25. H4 — Déplacer robot expiration dans cron
26. F2 — Adopter le pattern composants PHP partout
27. F7 — Brute force par IP en BDD
28. F8 — Cron renouvellement abonnement
```

---

## RÉCAPITULATIF DES ESTIMATIONS

| Phase | Contenu | Temps estimé |
|---|---|---|
| Phase 1 | 6 corrections bloquantes | 1h30 |
| Phase 2 | 4 corrections sécurité | 1h50 |
| Phase 3 | 4 migrations restantes | 4-5h |
| Phase 4 | Dashboard coiffeur DS v2.0 | 2-3h |
| Phase 5 | Améliorations UX | 2-3h |
| Phase 6 | Optimisations production | 6-10h |
| **TOTAL pré-démo (Phase 1-4)** | | **~10h** |
| **TOTAL pré-production (Phase 1-6)** | | **~18-24h** |


---

## ÉTAT DE PRÉPARATION POUR LA DÉMONSTRATION FINALE

### ✅ Ce qui est prêt et impressionne

- **Parcours Visiteur complet** : Accueil → Annuaire → Profil → Connexion → Inscription → Réservation, cohérent visuellement, glassmorphism DS v2.0 appliqué, responsive OK
- **Parcours Client complet** : Dashboard → Mes RDV → Catalogue → Avis → Créer RDV, tous migrés DS v2.0
- **Dashboard Coiffeur** : visuellement fort (sidebar, stats, planning), présentation efficace même sans DS v2.0
- **Logique métier** : annulation dégressive, gel de fonds, commission 5%, notifications, tout est fonctionnel
- **Bibliothèque de composants** : 34 composants créés, 17 bien adoptés dans les pages principales
- **CSS Design System** : variables, animations, components bien définis et cohérents sur les parcours migrés

### ⚠️ Ce qui peut gêner la démo sans corrections

- **C1 : `creer_rendezvous.php` plantera** si un client tente de créer un RDV via ce formulaire (colonne SQL incorrecte)
- **C2 : `profil.php`** affichera "Abonnement expiré" pour tous les coiffeurs (variable inexistante)
- **C5 : Les modales** peuvent ne pas s'ouvrir si `openModal()` n'est pas disponible globalement
- **Pages coiffeur CSS décalées** : les 4 pages coiffeur chargeront les CSS DS après le `<body>` (rendu potentiellement incorrect)

### Verdict Démo

> **PARTIELLEMENT PRÊT.** Les parcours visiteur et client sont démontrables immédiatement avec une très bonne qualité visuelle. Cependant, **6 corrections critiques** (Phase 1 — ~1h30) sont nécessaires avant de démontrer les parcours coiffeur et la gestion de profil sans risquer des erreurs visuelles ou des crashes PHP.

---

## ÉTAT DE PRÉPARATION POUR LA PRODUCTION

### Ce qui manque encore

1. **Paiement Kkiapay** : `$mode_test = true` partout — aucun paiement réel possible
2. **CSRF sur actions GET** coiffeur : vulnérabilité de sécurité active
3. **Credentials BDD en clair** dans `config.php`
4. **3 pages coiffeur non migrées** : expérience incohérente dans le parcours coiffeur
5. **Vérification MIME uploads** : faille sécurité upload
6. **Cron jobs** : robot d'expiration, renouvellement abonnement
7. **Suppression compte** : données orphelines en BDD
8. **Double système layout** : `layout/header.php` + `views/components/navbar_client.php` coexistent
9. **Monitoring et logs** : aucune infrastructure de logs d'erreurs PHP visible

### Verdict Production

> **NON PRÊT POUR LA PRODUCTION.** Outre les corrections critiques, des fondations manquantes (paiement réel, sécurité, infrastructure) empêchent un déploiement public serein. Estimation : **3-4 semaines de travail** pour une mise en production sécurisée.

---

## FONCTIONNALITÉS DÉVELOPPABLES IMMÉDIATEMENT APRÈS STABILISATION

Une fois les **Phases 1 à 4** complétées (corrections critiques + migrations restantes), les features suivantes peuvent être démarrées sans dette bloquante :

1. **Intégration Kkiapay** — PaymentService.php est déjà structuré, il suffit de connecter l'API réelle
2. **Géolocalisation client** — Le dashboard coiffeur attend déjà `client_adresse_texte` et `client_gps_lat/lng`
3. **Système d'évaluation IA** — `ia_controlleur.php` existe, le composant IA DS est en place
4. **Notifications WhatsApp automatiques** — `envoyer_notification_whatsapp.php` existe à la racine
5. **Filtres avancés annuaire** — La structure de filtre est en place, ajouter filtre par note / disponibilité immédiate
6. **Système de favoris client** — La table et les composants visuels peuvent être ajoutés facilement
7. **Statistiques avancées coiffeur** — Chart.js est déjà intégré dans `profil.php` et le dashboard
8. **Application multi-métiers (Domizi)** — `app.php` et `views/domizi/` sont déjà architecturés pour l'extension
9. **PWA / App mobile** — Le responsive est en place, un `manifest.json` + service worker peuvent être ajoutés
10. **Dashboard admin DS v2.0** — La logique admin est complète, la migration visuelle est la seule étape manquante

---

*Rapport généré le 4 Août 2026 — Coiffe Chez Toi V1.0*
*Analyse basée sur lecture statique du code source. Certaines anomalies BDD nécessitent une vérification dynamique.*
