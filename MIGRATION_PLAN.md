# PLAN DE MIGRATION — Coiffe Chez Toi
> Feuille de route pour la refonte UI vers le nouveau Design System.
> Version : 2.0 — Juillet 2026 | Mis à jour suite à la stabilisation Design System v2.0

---

## PRINCIPE DE LA MIGRATION

La migration suit une logique **du général au particulier** :

1. D'abord les **fondations CSS partagées** (variables, animations, composants)
2. Ensuite les **composants partagés** (layouts header/footer)
3. Ensuite les **pages à fort trafic** (pages d'entrée et conversion)
4. Ensuite les **pages secondaires** par espace (client, coiffeur, admin)
5. Enfin le **nettoyage** (suppression des CSS inline, des doublons, des anciens fichiers)

**Règle d'or :** Chaque étape est validée avant de passer à la suivante.  
**Aucune logique PHP n'est modifiée** pendant la migration.

---

## PHASE 0 — FONDATIONS (Prérequis)

> Durée estimée : 1–2 jours  
> À faire AVANT toute modification de page

### 0.1 — Créer `css/variables.css`
- Copier-coller le bloc `:root` complet depuis DS §2
- Ce fichier sera importé EN PREMIER dans chaque page

### 0.2 — Créer `css/animations.css` et `css/components.css`

**`css/animations.css`** — Copier les 6 animations officielles de DS §8 :
- `@keyframes fadeSlideIn` + `.page-transition`
- `@keyframes emerge` + `.fade-in-card`
- `@keyframes shimmer` + `.skeleton`, `.skeleton-*`
- `@keyframes pulseBell` + `.animate-pulse`
- `@keyframes blink` + `.typing-dot`
- `.hero-slide` + `.hero-slide.active`

**`css/components.css`** — Regrouper les classes utilitaires communes :
  - `.glass`, `.glass-md`, `.glass-lg` (+ alias `.glass-cct`)
  - `.btn-gold`, `.btn-outline-gold`, `.btn-ghost`, `.btn-danger-cct`
  - `.badge-*` (verified, gold, pending, confirmed, done, cancelled)
  - `.msg-success`, `.msg-danger`, `.msg-warning`
  - `.skeleton-card`, `.skeleton-text`, `.skeleton-title`, `.skeleton-avatar`
  - `.empty-state`
  - `.page-transition`, `.fade-in-card`
  - `.avatar-placeholder`
  - `.table-dark-cct`
  - `.fc-dark`, `.form-label-cct`, `.input-group-cct`
  - `.gallery-card`, `.gallery-card-img`
  - `.cct-footer` et ses enfants
  - `.info-block`, `.info-block--danger`, `.info-block--success`
  - `.slots-container`

### 0.3 — Créer `views/components/` (dossier de composants PHP)
- `views/components/navbar_client.php`
- `views/components/navbar_coiffeur.php` (extraire de header_coiffeur.php)
- `views/components/bottom_nav_client.php`
- `views/components/footer_global.php`
- `views/components/ia_assistant.php` (extraire de footer.php)
- `views/components/status_badge.php`
- `views/components/avatar.php`

### 0.4 — Valider le Design System sur `views/home.php`
- La page d'accueil est déjà la référence visuelle
- La valider comme conforme à 100% avant de migrer les autres

**Checklist de validation obligatoire :**
- [ ] `css/variables.css` importé en tête de page
- [ ] `css/animations.css` importé en tête de page
- [ ] `css/components.css` importé en tête de page
- [ ] Polices Google Fonts chargées (Playfair Display + Inter)
- [ ] Bootstrap 5.3.3 chargé après les variables
- [ ] Animation `fadeSlideIn` utilisée (pas `fadeIn`)
- [ ] Bouton primaire utilise `.btn-gold` (pas `.btn-gold-cct`)
- [ ] Aucun `@keyframes` défini dans la page
- [ ] Bottom nav présente sur mobile (< 768px)
- [ ] Aucun style inline `style=""` sauf exceptions documentées
- [ ] `--glass-cct` ou `.glass` utilisé pour les glass cards
- [ ] Fond body : `var(--dark)` = `#0A0A0A`

---

## PHASE 1 — LAYOUTS PARTAGÉS

> Durée estimée : 1–2 jours  
> Impact : toutes les pages

### Ordre de priorité
| # | Fichier | Raison |
|---|---------|--------|
| 1 | `layout/header_coiffeur.php` | Déjà le plus avancé — servira de référence |
| 2 | `layout/footer_coiffeur.php` | Minimal, facile à compléter |
| 3 | `layout/header.php` | Ancien pattern (sidebar) à unifier |
| 4 | `layout/footer.php` | IA assistant à extraire en composant |

### Tâches pour `layout/header.php`
- Remplacer `font-family: 'Segoe UI'` par `Inter`
- Extraire la navbar en composant `navbar_client.php`
- Ajouter bottom nav client (icônes: Accueil, Rechercher, Mes RDV, Profil)
- Aligner le style avec `header_coiffeur.php`
- Supprimer les styles inline redondants

### Tâches pour `layout/footer.php`
- Extraire `#ai-bubble` + `#chat-window` → `ia_assistant.php`
- Remplacer le footer legacy par la structure `cct-footer` du dashboard.php
- Standardiser les couleurs (supprimer les `style=""` directs)

---

## PHASE 2 — PAGES D'AUTHENTIFICATION

> Durée estimée : 1 jour  
> Impact : premier contact utilisateur

### Ordre
| # | Fichier | Priorité |
|---|---------|----------|
| 1 | `access/connexion.php` | ⭐⭐⭐ Déjà très bien stylée, alignement mineur |
| 2 | `access/inscription.php` | ⭐⭐⭐ Déjà bien stylée, harmonisation labels/spacing |

### Tâches communes
- Remplacer `font-family: 'Segoe UI'` par variable `var(--font-body)`
- Uniformiser les messages d'erreur avec `.msg-danger`
- S'assurer que le fond flou utilise `--black` et non `#000` codé en dur
- Vérifier responsive mobile (champs empilés correctement)

---

## PHASE 3 — PAGES CLIENT

> Durée estimée : 3–4 jours

### Ordre (du plus visible au moins visible)

| # | Fichier | Raison | Composants à créer |
|---|---------|--------|-------------------|
| 1 | `views/client/dashboard.php` | Page principale du client | HeroCapsule, SearchCapsule, ServiceCard, Footer |
| 2 | `filter/annuaire_coiffeurs.php` | Conversion principale | HeroCompact, ServiceCard (variante annuaire) |
| 3 | `coiffeurs/profil_public.php` | Page décision de réservation | ProfileSummary, AvailabilityCard, GalleryCard |
| 4 | `client/mes_rendezvous.php` | Gestion des RDV | AppointmentCard, StatusBadge, Modal |
| 5 | `client/reserver.php` | Tunnel de réservation | Form styles, recap card |
| 6 | `client/catalogue.php` | Galerie styles | GalleryCard grid |
| 7 | `client/laisser_avis.php` | Formulaire avis | ReviewCard, form dark |
| 8 | `client/creer_rendezvous.php` | Formulaire création RDV | Form dark, SearchCapsule |

### Note sur `dashboard.php`
La page `dashboard.php` est déjà très avancée et proche du Design System. La migration consistera principalement à :
- Extraire la navbar en composant partagé
- Extraire le footer en composant partagé
- Remplacer les variables CSS codées en dur par les variables globales
- Standardiser les noms de classes CSS

### Note sur `profil_public.php`
Cette page est physiquement dans `/coiffeurs/` mais fonctionnellement une **page client** (vue en lecture seule du profil d'un coiffeur). Elle est listée ici car c'est un point de conversion critique (passage de l'annuaire à la réservation).

---

## PHASE 4 — PAGES COIFFEUR

> Durée estimée : 3–4 jours

### Ordre

| # | Fichier | Raison | Composants à créer/utiliser |
|---|---------|--------|----------------------------|
| 1 | `coiffeurs/profil_coiffeurs.php` | Page profil éditable | ProfileSummary, Avatar, form dark |
| 2 | `coiffeurs/gestion_catalogue.php` | Gestion des prestations | GalleryCard, Modal, form dark |
| 3 | `coiffeurs/agenda_coiffeurs.php` | Planning | TableDark, AvailabilityCard |
| 4 | `coiffeurs/valider_rendezvous.php` | Gestion demandes | AppointmentCard, StatusBadge, ActionBar |
| 5 | `coiffeurs/portefeuille.php` | Finance | StatisticCard, Timeline, InformationBlock, Modal |
| 6 | `coiffeurs/mes_zones.php` | Zones d'intervention | Form dark, carte/map if applicable |

### Note
Les pages coiffeur utilisent déjà `header_coiffeur.php` qui est le plus avancé. La migration consistera principalement à supprimer les styles CSS dupliqués en bas des fichiers (`.glass-cct`, `.btn-gold-cct`, `.fc-dark` redéfinis partout).

---

## PHASE 5 — PROFIL PARTAGÉ & ADMIN

> Durée estimée : 1–2 jours

| # | Fichier | Raison |
|---|---------|--------|
| 1 | `profil.php` | Page profil partagée client/coiffeur |
| 2 | `modifier_profil.php` | Édition profil |
| 3 | `first/admin_dashboard.php` | Interface admin |
| 4 | `first/admin_actions.php` | Actions admin |
| 5 | `views/domizi/choix_role.php` | Page de choix rôle |
| 6 | `deconnexion.php` | Page de déconnexion — peut afficher un écran flash de confirmation |
| 7 | `coiffeurs/sauvegarder_agenda.php` | Page de traitement POST de l'agenda — pas de vue, vérifier si elle retourne un redirect vers une page UI |
| 8 | `envoyer_notification_whatsapp.php` | Backend uniquement — confirmer si elle a une interface utilisateur. Si non, hors-scope UI. |

---

## PHASE 6 — NETTOYAGE FINAL

> Durée estimée : 1 jour

### Tâches
1. **Supprimer** tous les blocs `<style>` inline dans les fichiers PHP
2. **Supprimer** les doublons de `.glass-cct`, `.btn-gold-cct`, `.fc-dark` dans chaque page
3. **Supprimer** `css/global.css` (remplacé par `css/variables.css` + `css/animations.css` + `css/components.css`)
4. **Supprimer** `css/responsive-custom.css` (fusionner dans `css/components.css`)
5. **Garder** `css/style.css` uniquement pour la home page hero slider
6. **Remplacer** toutes les occurrences de `.btn-gold-cct` par `.btn-gold`
7. **Remplacer** toutes les occurrences de `.luxury-profile-card` par `.gallery-card`
8. **Vérifier** la responsive sur : Chrome mobile, Firefox, Safari iOS
9. **Tester** l'accessibilité (contraste minimum 4.5:1 entre gold et fond sombre)

---

## TABLEAU DE BORD DE MIGRATION

| Fichier | Statut | Phase |
|---------|--------|-------|
| `css/variables.css` | 🔲 À créer | 0 |
| `css/animations.css` | 🔲 À créer | 0 |
| `css/components.css` | 🔲 À créer | 0 |
| `views/components/*` | 🔲 À créer | 0 |
| `views/home.php` | ✅ Référence | — |
| `layout/header_coiffeur.php` | 🟡 Avancé | 1 |
| `layout/footer_coiffeur.php` | 🟡 À compléter | 1 |
| `layout/header.php` | 🔴 À migrer | 1 |
| `layout/footer.php` | 🟡 À refactoriser | 1 |
| `access/connexion.php` | 🟡 Avancé | 2 |
| `access/inscription.php` | 🟡 Avancé | 2 |
| `views/client/dashboard.php` | 🟡 Très avancé | 3 |
| `filter/annuaire_coiffeurs.php` | 🔴 CSS mixte | 3 |
| `coiffeurs/profil_public.php` | 🟡 Partiel (page client) | 3 |
| `client/mes_rendezvous.php` | 🔴 Legacy | 3 |
| `client/reserver.php` | 🔴 Non analysé | 3 |
| `client/catalogue.php` | 🔴 Non analysé | 3 |
| `client/laisser_avis.php` | 🔴 Non analysé | 3 |
| `client/creer_rendezvous.php` | 🔴 Non analysé | 3 |
| `coiffeurs/profil_coiffeurs.php` | 🔴 Non analysé | 4 |
| `coiffeurs/gestion_catalogue.php` | 🟡 Partiel | 4 |
| `coiffeurs/agenda_coiffeurs.php` | 🟡 Partiel | 4 |
| `coiffeurs/valider_rendezvous.php` | 🔴 Non analysé | 4 |
| `coiffeurs/portefeuille.php` | 🟡 Avancé | 4 |
| `coiffeurs/mes_zones.php` | 🔴 Non analysé | 4 |
| `profil.php` | 🔴 Non analysé | 5 |
| `modifier_profil.php` | 🔴 Non analysé | 5 |
| `first/admin_dashboard.php` | 🔴 Non analysé | 5 |
| `first/admin_actions.php` | 🔴 Non analysé | 5 |
| `views/domizi/choix_role.php` | 🔴 Non analysé | 5 |
| `deconnexion.php` | 🔴 Non analysé | 5 |
| `coiffeurs/sauvegarder_agenda.php` | 🔴 À vérifier (backend ?) | 5 |
| `envoyer_notification_whatsapp.php` | 🔴 À vérifier (backend ?) | 5 |
| Nettoyage CSS global | 🔲 À faire | 6 |

**Légende :** ✅ Conforme | 🟡 Partiellement conforme | 🔴 À migrer | 🔲 À créer
