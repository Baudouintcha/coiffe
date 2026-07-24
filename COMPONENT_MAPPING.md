# COMPONENT MAPPING — Coiffe Chez Toi
> Feuille de route officielle de la migration vers le Design System v2.0.
> Date : Juillet 2026 | Référence : DESIGN_SYSTEM.md v2.0 + COMPONENT_LIBRARY.md v2.0
> **Ne modifier aucune page avant validation de ce document.**

---

## CONVENTIONS DE LECTURE

- **→ Remplacé par** : le composant DS qui remplace l'ancien élément
- **✂️ Supprimer** : élément à faire disparaître
- **⚠️ Conserver logique** : le PHP/SQL ne change pas, seul le HTML/CSS est refactorisé

---

## PAGE 1 — `views/home.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `views/home.php` |
| Rôle | Page d'accueil publique — premier contact visiteur |
| Type | Visiteur |
| Parcours | Visiteur → Inscription (client ou coiffeur) → Connexion |

### 2. Composants actuels
- Hero slider plein écran custom (`.hero-slider`, `.slide`, `.overlay`)
- Boutons `.btn-gold` (inline CSS différent du DS)
- Titre H1 `.typing-effect`
- Script JS slider inline dans `script.js`

### 3. Composants DS à utiliser
- `.hero-slider` → `HeroPremium` (`hero_premium.php`)
- `.btn-gold` → `PrimaryButton` + `SecondaryButton` (`buttons.php` + `components.css`)
- `.btn-transparent` → `GhostButton` (`.btn-ghost`)

### 4. Composants à supprimer
- `.typing-effect` → remplacé par `.hp-title` (animation `fadeSlideIn`)
- Script slider inline → remplacé par le JS intégré dans `hero_premium.php`
- `css/style.css` `.btn-gold:hover` → remplacé par `components.css`

### 5. CSS à supprimer
- Tout le `<style>` de `css/style.css` sauf le reset `*`
- Variables `:root` dans `style.css` → remplacées par `variables.css`

### 6. Structure finale
```
<head> → variables.css + animations.css + components.css + Bootstrap
HeroPremium (hero_premium.php)
  └─ Slider images /imgid/
  └─ Boutons rôle (PrimaryButton + SecondaryButton)
  └─ Lien connexion (GhostButton)
```

### 7. Responsive
- Desktop : titre `clamp(3rem,10vw,6rem)`, centré
- Tablette : même layout, polices adaptées
- Mobile : titre `clamp(3rem,8vw,4rem)`, boutons en colonne

### 8. Animations
- Slider : `.hero-slide` + `.hero-slide.active` (animations.css)
- Contenu : `.page-transition` → `fadeSlideIn`

### 9. Difficulté de migration
**Faible** — La page est simple. Le slider PHP est intact, seul le CSS/HTML change.

### 10. Dépendances
- `css/variables.css`, `css/animations.css`, `css/components.css`
- `hero_premium.php`

### 11. Validation
- [ ] Composants depuis DS uniquement
- [ ] Aucun ancien composant conservé
- [ ] Aucun style inline
- [ ] Responsive conforme
- [ ] Accessibilité conforme
- [ ] Animations conformes
- [ ] Glassmorphism conforme
- [ ] Largeur max respectée
- [ ] Compatible avec les autres pages

---

## PAGE 2 — `views/client/dashboard.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `views/client/dashboard.php` |
| Rôle | Dashboard principal du client connecté |
| Type | Client |
| Parcours | Connexion → Dashboard → Annuaire → Profil coiffeur → Réservation |

### 2. Composants actuels
- Navbar custom `.cct-nav` (inline dans la page)
- Hero capsule `.hero` (inline, border-radius 52px)
- Profile glass card `.profile-glass-card` (inline)
- Search bar `.search-bar` (inline)
- Skeleton loaders `.skeleton`
- Service cards `.coiffeur-card` (inline)
- Section "Pourquoi" `.why-card` (inline)
- Footer `.cct-footer` (inline)
- CSS `<style>` 300+ lignes inline

### 3. Composants DS à utiliser
- Navbar inline → `navbar_client.php` (TopNavigation)
- Hero capsule → `hero_capsule.php` (HeroCapsule)
- Profile glass card → `floating_card.php` (FloatingCard)
- Search bar → `search_capsule.php` (SearchCapsule)
- Skeleton → classes `.skeleton-card` depuis `components.css`
- `.coiffeur-card` → `service_card.php` (ServiceCard)
- Footer inline → `footer_global.php` + `ia_assistant.php`
- Bottom nav → `bottom_nav_client.php`

### 4. Composants à supprimer
- Tout le bloc `<style>` de 300+ lignes → remplacé par imports CSS
- `.why-card` (section "Pourquoi") → remplacer par `FloatingSection` avec cards internes
- Footer inline → supprimé entièrement

### 5. CSS à supprimer
- Variables `:root` dupliquées
- `.cct-nav`, `.cct-brand`, `.nav-icon-btn` inline → dans `components.css` via `navbar_client.php`
- `.hero-*`, `.profile-glass-card`, `.search-bar` → dans `hero_capsule.php`
- `.why-card`, `.section-why` → dans `floating_section.php`
- `.cct-footer`, `.footer-*` → dans `components.css`

### 6. Structure finale
```
variables.css + animations.css + components.css + Bootstrap + Google Fonts
  └─ navbar_client.php (TopNavigation)
  └─ hero_capsule.php
       └─ floating_card.php (profil client)
       └─ search_capsule.php
  └─ Section "Coiffeurs proches" (.container)
       └─ FloatingSection (titre + grille)
            └─ service_card.php × N (ou skeleton si vide)
            └─ empty_state.php (si BDD vide)
  └─ Section "Pourquoi CCT"
       └─ FloatingSection × 3 cartes
  └─ footer_global.php
  └─ ia_assistant.php
  └─ bottom_nav_client.php
```

### 7. Responsive
- Desktop : navbar fixe 64px, hero capsule 420px radius 52px
- Tablette (768px) : profile card masquée, hero 340px radius 28px
- Mobile : hero 300px radius 20px, search bar en colonne, bottom nav visible

### 8. Animations
- Slider hero : `.hero-slide.active` (animations.css)
- Page : `.page-transition` sur le wrapper
- Cartes : `transition: var(--transition-spring)` via `.coiffeur-card`
- Skeleton : `.skeleton` shimmer (animations.css)

### 9. Difficulté
**Moyenne** — Page très avancée mais CSS 100% inline. L'essentiel du HTML est réutilisable, seul le CSS migrate.

### 10. Dépendances
- `navbar_client.php`, `hero_capsule.php`, `floating_card.php`, `search_capsule.php`
- `service_card.php`, `footer_global.php`, `ia_assistant.php`, `bottom_nav_client.php`
- `empty_state.php`, `floating_section.php`

### 11. Validation
- [ ] Composants depuis DS uniquement
- [ ] Aucun ancien composant conservé
- [ ] Aucun style inline
- [ ] Responsive conforme
- [ ] Accessibilité conforme
- [ ] Animations conformes
- [ ] Glassmorphism conforme
- [ ] Largeur max respectée
- [ ] Floating Sections présentes
- [ ] Compatible avec les autres pages

---

## PAGE 3 — `filter/annuaire_coiffeurs.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `filter/annuaire_coiffeurs.php` |
| Rôle | Annuaire de recherche de coiffeurs avec filtres |
| Type | Client |
| Parcours | Dashboard → Annuaire → Profil coiffeur |

### 2. Composants actuels
- `layout/header.php` (sidebar legacy)
- Hero `.annuaire-hero` avec fond flou (inline CSS)
- Glass card formulaire `.glass-card-annuaire` (inline)
- Formulaire filtres `.custom-input`
- Cartes `.card-luxury` (Bootstrap restyled inline)
- Profile placeholder `.profile-placeholder`
- Badge status `.badge-status`
- Empty state custom
- Footer via `layout/footer.php`

### 3. Composants DS à utiliser
- `layout/header.php` → `navbar_client.php` + `bottom_nav_client.php`
- `.annuaire-hero` → `hero_compact.php` (HeroCompact)
- Formulaire filtres → `form_field.php` × 4 (select + number + submit)
- `.card-luxury` → `service_card.php` (ServiceCard)
- `.profile-placeholder` → `avatar.php`
- `.badge-status` → `badges.php` → `render_badge_verified()`
- Empty state → `empty_state.php`
- Footer → `footer_global.php` + `ia_assistant.php`

### 4. Composants à supprimer
- Toutes les classes inline de `annuaire_coiffeurs.php` (200+ lignes CSS)
- `.custom-input` → `.fc-dark`
- `.btn-luxury-gold` → `.btn-gold`
- `.glass-card-annuaire` → `.glass-md` (via `hero_compact.php`)
- `layout/header.php` sidebar accordéon (legacy)

### 5. CSS à supprimer
- Variables `:root { --gold-premium }` → `--gold`
- `.card-luxury`, `.profile-img`, `.badge-status`, `.price-box` (inline)
- `.annuaire-hero::before`, `.glass-card-annuaire` (inline)
- `.annuaire-label` → `.form-label-cct`

### 6. Structure finale
```
variables.css + animations.css + components.css + Bootstrap
  └─ navbar_client.php
  └─ hero_compact.php
       └─ [form_field.php sélecteurs × 4] + btn-gold
  └─ Section résultats (.container)
       └─ FloatingSection (titre résultats)
            └─ service_card.php × N
            └─ empty_state.php (si aucun résultat)
            └─ pagination.php (si N > seuil)
  └─ footer_global.php + ia_assistant.php
  └─ bottom_nav_client.php
```

### 7. Responsive
- Desktop : hero 35vh, formulaire en grille 5 cols
- Tablette : formulaire 2 cols
- Mobile : formulaire en colonne, bottom nav

### 8. Animations
- Page : `.page-transition`
- Cartes : `transition: var(--transition-spring)` sur `.coiffeur-card`

### 9. Difficulté
**Haute** — CSS mixte important. Plusieurs patterns incohérents à fusionner.

### 10. Dépendances
- `hero_compact.php`, `form_field.php`, `service_card.php`
- `navbar_client.php`, `footer_global.php`, `pagination.php`, `empty_state.php`

### 11. Validation
- [ ] Composants depuis DS uniquement
- [ ] Aucun ancien composant conservé
- [ ] Aucun style inline
- [ ] Responsive conforme
- [ ] Accessibilité conforme
- [ ] Animations conformes
- [ ] Glassmorphism conforme
- [ ] Largeur max respectée
- [ ] Compatible avec les autres pages

---

---

## PAGE 4 — `coiffeurs/profil_public.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `coiffeurs/profil_public.php` |
| Rôle | Page profil publique d'un coiffeur — décision de réservation |
| Type | Client (page consultée par le client) |
| Parcours | Annuaire → Profil coiffeur → Choisir un créneau → Réservation |

### 2. Composants actuels
- Topbar custom `.profil-topbar` (inline, hauteur 58px non conforme)
- Animation `@keyframes fadeIn` (non conforme — doit venir de animations.css)
- Section profil avec avatar `bg-warning text-dark` (Bootstrap natif non DS)
- Badge note Bootstrap natif
- Section disponibilités `.day-selector-card` (inline CSS dupliqué)
- `.style-slots-box` (inline)
- Galerie prestations `.luxury-profile-card` (classe dépréciée)
- Bouton `.btn-outline-warning` (non conforme)
- Empty state catalogue custom inline
- Footer via `layout/footer.php`

### 3. Composants DS à utiliser
- `.profil-topbar` → `navbar_client.php` (TopNavigation)
- Section profil → `profile_summary.php` (ProfileSummary)
- Avatar bootstrap → `avatar.php` (Avatar LG)
- Badge note → `badges.php` → `render_badge_gold()`
- `.day-selector-card` → `availability_card.php` (AvailabilityCard)
- `.style-slots-box` → `slots_container.php` (SlotsContainer)
- `.luxury-profile-card` → `gallery_card.php` variante `view` (GalleryCard)
- `.btn-outline-warning` → `.btn-outline-gold`
- Empty state catalogue → `empty_state.php`
- Footer → `footer_global.php` + `ia_assistant.php`
- Bottom nav → `bottom_nav_client.php`

### 4. Composants à supprimer
- `.profil-topbar` custom (hauteur 58px, non conforme)
- `@keyframes fadeIn` inline → remplacé par `.page-transition` de animations.css
- `bg-warning text-dark` sur avatar → remplacé par `avatar.php`
- `badge bg-dark border border-warning` Bootstrap → `.badge-gold`
- `.luxury-profile-card` (classe dépréciée)
- Double bloc `<style>` (head + fin de fichier)

### 5. CSS à supprimer
- `.profil-topbar` (58px, non conforme)
- `@keyframes fadeIn { from{translateY(10px)} }` — variante non conforme
- `.day-selector-card` (dupliqué dans `components.css`)
- `.luxury-profile-card`, `.luxury-img-container` → `.gallery-card`
- `.style-slots-box` → `.slots-container`
- Tout le second `<style>` en bas de fichier

### 6. Structure finale
```
variables.css + animations.css + components.css + Bootstrap + Google Fonts
  └─ navbar_client.php (TopNavigation)
  └─ .page-transition wrapper
  └─ profile_summary.php
       └─ avatar.php (LG)
       └─ badges.php (note)
  └─ Section réservation (.container)
       └─ FloatingSection (titre "Réservation Express")
            └─ availability_card.php (7 jours)
            └─ slots_container.php
  └─ Section portfolio (.container)
       └─ FloatingSection (titre "Portfolio")
            └─ gallery_card.php × N (variante view)
            └─ empty_state.php (si vide)
  └─ footer_global.php + ia_assistant.php
  └─ bottom_nav_client.php
```

### 7. Responsive
- Desktop : profile summary centré, grille 3 colonnes galerie
- Tablette : grille 2 colonnes
- Mobile : bottom nav, jours scroll horizontal, galerie 1 colonne

### 8. Animations
- Page : `.page-transition` (`fadeSlideIn`)
- AvailabilityCard sélectionnée : `transform: scale(1.05)` via `.active-day` (components.css)

### 9. Difficulté
**Élevée** — Nombreux styles inline, double bloc `<style>`, animation non conforme, classe dépréciée.

### 10. Dépendances
- `navbar_client.php`, `profile_summary.php`, `avatar.php`, `badges.php`
- `availability_card.php`, `slots_container.php`
- `gallery_card.php`, `empty_state.php`, `floating_section.php`
- `footer_global.php`, `ia_assistant.php`, `bottom_nav_client.php`

### 11. Validation
- [ ] Composants depuis DS uniquement
- [ ] Aucun ancien composant conservé
- [ ] Aucun style inline
- [ ] Responsive conforme
- [ ] Accessibilité conforme
- [ ] Animations conformes (`fadeSlideIn`, pas `fadeIn`)
- [ ] Glassmorphism conforme
- [ ] `.gallery-card` utilisé (pas `.luxury-profile-card`)
- [ ] Compatible avec les autres pages

---

## PAGE 5 — `client/mes_rendezvous.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `client/mes_rendezvous.php` |
| Rôle | Liste et gestion des rendez-vous du client |
| Type | Client |
| Parcours | Dashboard → Mes RDV → Annuler un RDV |

### 2. Composants actuels
- `layout/header.php` (sidebar legacy)
- Section tableau Bootstrap dark `table-dark table-hover table-bordered border-warning`
- Cartes mobile Bootstrap `card.bg-dark.border.border-warning`
- Badges Bootstrap natifs (`bg-warning`, `bg-primary`, `bg-success`, `bg-secondary`)
- Modales Bootstrap 5 pour détails RDV
- `.btn-gold` custom (`background:#ffc107` — non conforme)
- Empty state Bootstrap `alert-secondary`
- Style inline `<style>` en bas de page
- Footer via `layout/footer.php`

### 3. Composants DS à utiliser
- `layout/header.php` → `navbar_client.php`
- Tableau → `data_table.php` (DataTable) + `appointment_card.php` variante `row`
- Cartes mobile → `appointment_card.php` variante `mobile`
- Badges Bootstrap → `status_badge.php` → `render_status_badge()`
- Modales Bootstrap → `modal.php` (Modal vanilla JS)
- `.btn-gold` custom → `.btn-gold` de `components.css`
- Empty state → `empty_state.php`
- Footer → `footer_global.php` + `ia_assistant.php`

### 4. Composants à supprimer
- `layout/header.php` sidebar
- `table-dark table-hover table-bordered border-warning` Bootstrap
- `card.bg-dark.border.border-warning` Bootstrap → `.glass-cct`
- Badges Bootstrap natifs → `status_badge.php`
- `.btn-gold { background:#ffc107 }` incorrect → remplacer par `.btn-gold` DS

### 5. CSS à supprimer
- `.btn-gold { background-color:#ffc107 }` — couleur non conforme (doit être `#D4AF37`)
- `.border-none` — classe inutile
- `border-warning` Bootstrap → `border:1px solid rgba(212,175,55,0.40)`

### 6. Structure finale
```
variables.css + animations.css + components.css + Bootstrap
  └─ navbar_client.php
  └─ .container.page-transition
       └─ Titre H2 Playfair gold
       └─ toast.php (message flash)
       └─ FloatingSection (Desktop — d-none d-md-block)
            └─ data_table.php
                 └─ appointment_card.php × N (variante row)
       └─ .d-md-none (Mobile)
            └─ appointment_card.php × N (variante mobile)
       └─ empty_state.php (si aucun RDV)
       └─ btn-ghost (Retour catalogue)
  └─ modal.php × N (un par RDV)
  └─ footer_global.php + ia_assistant.php
  └─ bottom_nav_client.php
```

### 7. Responsive
- Desktop : tableau DataTable
- Mobile : cartes AppointmentCard, bottom nav

### 8. Animations
- Page : `.page-transition`
- Modal : `.fade-in-card` à l'ouverture
- Toast : `.cct-toast--visible`

### 9. Difficulté
**Élevée** — Tableau + cartes mobiles + modales Bootstrap à refactoriser.

### 10. Dépendances
- `navbar_client.php`, `data_table.php`, `appointment_card.php`
- `status_badge.php`, `modal.php`, `toast.php`, `empty_state.php`
- `footer_global.php`, `ia_assistant.php`, `bottom_nav_client.php`

### 11. Validation
- [ ] Composants depuis DS uniquement
- [ ] `.btn-gold` couleur `#D4AF37` (pas `#ffc107`)
- [ ] Aucun style inline
- [ ] Responsive conforme
- [ ] Accessible
- [ ] Animations conformes

---

## PAGE 6 — `client/reserver.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `client/reserver.php` |
| Rôle | Tunnel de réservation d'un créneau |
| Type | Client |
| Parcours | Profil coiffeur → Choisir style → Confirmer RDV |

### 2. Composants actuels
- `layout/header.php` (legacy)
- Card Bootstrap `.card.p-4` (fond `#111`, border gold inline)
- Inputs Bootstrap `.form-control` (fond blanc — non DS)
- Bouton `.btn-gold` avec `background:#ffc107` (non conforme)
- Formulaire avec options checkbox
- Style inline

### 3. Composants DS à utiliser
- `layout/header.php` → `navbar_client.php`
- Card → `floating_section.php` (FloatingSection)
- Inputs → `form_field.php` (FormField)
- Bouton → `.btn-gold` conforme
- Message flash → `toast.php`
- Footer → `footer_global.php` + `ia_assistant.php`

### 4. Composants à supprimer
- `.form-control { background-color:#fff; color:#000 }` — totalement non conforme DS
- `.btn-gold { background:#ffc107 }` — incorrect
- Card Bootstrap `.card` → `.glass-cct`

### 5. CSS à supprimer
- Style inline `<style>` complet (fond blanc sur inputs)

### 6. Structure finale
```
  └─ navbar_client.php
  └─ .container.page-transition
       └─ floating_section.php
            └─ Récapitulatif prestation
            └─ form_field.php (date, heure)
            └─ Options checkbox (.fc-dark style)
            └─ Récap montant dynamique
            └─ btn-gold (confirmer)
  └─ toast.php (message confirmation/erreur)
  └─ footer_global.php + ia_assistant.php
  └─ bottom_nav_client.php
```

### 7. Difficulté
**Élevée** — Inputs blancs totalement non conformes. Reconstruction complète du style.

### 8. Dépendances
`navbar_client.php`, `floating_section.php`, `form_field.php`, `toast.php`

---

## PAGE 7 — `client/catalogue.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `client/catalogue.php` |
| Rôle | Galerie globale de tous les styles du catalogue |
| Type | Client / Coiffeur |
| Parcours | Navigation → Catalogue → Profil coiffeur |

### 2. Composants actuels
- `layout/header.php` (legacy)
- Cards Bootstrap `.card` (`border: 1px solid var(--gold)`, fond `#111`)
- Images `card-img-top` (180px, 230px inconsistant)
- Hover CSS inline
- Boutons Bootstrap `.btn-warning`, `.btn-success`, `.btn-danger`
- Footer via `layout/footer.php`

### 3. Composants DS à utiliser
- `layout/header.php` → `navbar_client.php`
- Cards → `gallery_card.php` (GalleryCard) variante `view` ou `edit`
- Boutons → `.btn-gold`, `.btn-outline-gold`, `.btn-danger-cct`
- Empty state → `empty_state.php`
- Footer → `footer_global.php` + `ia_assistant.php`

### 4. Composants à supprimer
- `.btn-warning`, `.btn-success`, `.btn-danger` Bootstrap → boutons DS
- `.card:hover { transform }` inline → `var(--transition-spring)` via `.gallery-card`

### 5. Difficulté
**Moyenne** — Grille de cartes simple. La logique PHP est propre.

### 6. Structure finale
```
  └─ navbar_client.php
  └─ FloatingSection (titre Catalogue)
       └─ gallery_card.php × N
       └─ empty_state.php
  └─ footer_global.php + ia_assistant.php + bottom_nav_client.php
```

---

## PAGE 8 — `client/laisser_avis.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `client/laisser_avis.php` |
| Rôle | Formulaire de notation d'un coiffeur après RDV |
| Type | Client |
| Parcours | Email/lien → Laisser avis |

### 2. Composants actuels
- CSS custom minimal (style.css Arial, pas Inter)
- Formulaire HTML natif sans DS
- Star rating via radio buttons custom
- Pas de header/footer DS

### 3. Composants DS à utiliser
- Refonte complète : `auth_card.php` ou `floating_section.php` comme wrapper
- Star rating → `review_card.php` pour affichage, inputs custom pour la saisie
- `form_field.php` (textarea commentaire)
- `.btn-gold` (submit)
- `navbar_client.php` + `footer_global.php`

### 4. Difficulté
**Très élevée** — Page avec BDD incompatible et style hors DS totalement. À reconstruire visuellement.

### 5. Dépendances
`navbar_client.php`, `floating_section.php`, `form_field.php`, `footer_global.php`

---

## PAGE 9 — `client/creer_rendezvous.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `client/creer_rendezvous.php` |
| Rôle | Création manuelle d'un RDV |
| Type | Client |
| Parcours | Profil coiffeur → Créer RDV |

### 2. Analyse
Fichier non analysé en profondeur. Structure identique à `reserver.php`.

### 3. Composants DS
Identiques à `reserver.php` : `navbar_client.php`, `floating_section.php`, `form_field.php`, `search_capsule.php`.

### 4. Difficulté
**Moyenne** (basé sur structure presumée similaire à reserver.php)

---

---

## PAGES 10–15 — ESPACE COIFFEUR

---

## PAGE 10 — `layout/header_coiffeur.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `layout/header_coiffeur.php` |
| Rôle | Header partagé de toutes les pages coiffeur |
| Type | Coiffeur |

### 2. Composants actuels
- Topbar `.coiffeur-topbar` (conforme à 90%)
- Bottom nav `.coiffeur-bottom-nav` (conforme)
- Classes utilitaires `.glass-cct`, `.fc-dark`, `.btn-gold-cct` (dépréciée), `.msg-*`

### 3. Composants DS à utiliser
- `.btn-gold-cct` → `.btn-gold` (composant Buttons)
- Conserver tout le reste (déjà conforme)
- Extraire progressivement vers `navbar_coiffeur.php`

### 4. CSS à supprimer
- `.btn-gold-cct` → remplacer par `.btn-gold` depuis `components.css`
- Blocs `<style>` redéfinis dans les pages individuelles (agenda, catalogue, portefeuille)

### 5. Difficulté
**Faible** — Déjà très conforme. Une seule classe à remplacer.

---

## PAGE 11 — `coiffeurs/gestion_catalogue.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `coiffeurs/gestion_catalogue.php` |
| Rôle | Gestion CRUD du catalogue de prestations |
| Type | Coiffeur |

### 2. Composants actuels
- `layout/header_coiffeur.php` ✅
- Formulaire Bootstrap `.form-control` (fond noir, non standardisé)
- Card sidebar Bootstrap `.card` (fond `#111`, border gold)
- Cards prestations Bootstrap (fond `#111`, border `#333`)
- Modales Bootstrap 5 dark restyled
- `.btn-gold-cct` (dépréciée)
- `.btn-outline-warning`, `.btn-outline-danger` Bootstrap
- Style `<style>` dupliqué en bas de page

### 3. Composants DS à utiliser
- Formulaire → `form_field.php` (FormField)
- Card sidebar → `floating_section.php` (FloatingSection sticky)
- Cards prestations → `gallery_card.php` variante `edit`
- Modales → `modal.php`
- `.btn-gold-cct` → `.btn-gold`
- `.btn-outline-warning` → `.btn-outline-gold`
- `.btn-outline-danger` → `.btn-danger-cct`
- ActionBar → `action_bar.php`

### 4. CSS à supprimer
- `<style>` en bas : `.fc-dark`, `.btn-gold-cct`, `.glass-cct`, `.msg-*` (tous dupliqués)

### 5. Structure finale
```
  └─ navbar_coiffeur.php (extrait de header_coiffeur.php)
  └─ action_bar.php (Mon Catalogue)
  └─ .row
       └─ floating_section.php (Formulaire ajout — sticky col-md-4)
            └─ form_field.php × 5
            └─ btn-gold (Ajouter)
       └─ .col-md-8 (Grille prestations)
            └─ gallery_card.php × N (variante edit)
            └─ empty_state.php (si vide)
  └─ modal.php × N (modifier chaque prestation)
```

### 6. Difficulté
**Haute** — Nombreux blocs CSS dupliqués, modales, formulaire complexe.

### 7. Dépendances
`form_field.php`, `floating_section.php`, `gallery_card.php`, `modal.php`, `action_bar.php`, `empty_state.php`

---

## PAGE 12 — `coiffeurs/agenda_coiffeurs.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `coiffeurs/agenda_coiffeurs.php` |
| Rôle | Configuration des disponibilités hebdomadaires |
| Type | Coiffeur |

### 2. Composants actuels
- `layout/header_coiffeur.php` ✅
- ActionBar inline
- `.glass-cct` dupliqué
- Tableau Bootstrap `table-dark table-striped table-hover`
- Inputs `form-control bg-dark` (Bootstrap)
- `.btn-gold-cct` (dépréciée)
- `.msg-success` dupliqué

### 3. Composants DS à utiliser
- ActionBar → `action_bar.php`
- Tableau → `data_table.php` (DataTable)
- Inputs → `form_field.php` (time, checkbox)
- `.glass-cct` → depuis `components.css` (plus de doublon)
- `.btn-gold-cct` → `.btn-gold`
- Message succès → `toast.php` ou `.msg-success`
- Aperçu jours → `availability_card.php` (lecture seule)

### 4. CSS à supprimer
- `<style>` en bas : tous les doublons de `glass-cct`, `btn-gold-cct`, `fc-dark`, `msg-success`

### 5. Difficulté
**Faible** — Structure simple, peu de composants.

### 6. Dépendances
`action_bar.php`, `data_table.php`, `form_field.php`, `toast.php`, `availability_card.php`

---

## PAGE 13 — `coiffeurs/valider_rendezvous.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `coiffeurs/valider_rendezvous.php` |
| Rôle | Gestion des demandes RDV (accepter/refuser) |
| Type | Coiffeur |

### 2. Composants actuels
- `layout/header_coiffeur.php` ✅
- ActionBar inline
- Tableau custom avec styles inline (pas `.table-dark-cct`)
- Badges statut inline (`background:rgba(...)`)
- Boutons Accepter/Refuser inline
- `.glass-cct` et `.msg-*` dupliqués

### 3. Composants DS à utiliser
- ActionBar → `action_bar.php`
- Tableau → `data_table.php` (DataTable)
- Badges statut → `status_badge.php` adapté + `.badge-pending`, `.badge-confirmed`, `.badge-cancelled`
- Boutons → `.btn-outline-gold` (Accepter) + `.btn-danger-cct` (Refuser)
- Message → `toast.php`
- Empty state → `empty_state.php`

### 4. CSS à supprimer
- Styles inline sur les `<tr>`, `<td>`, badges — tous remplacés par DS tokens

### 5. Difficulté
**Élevée** — Nombreux styles inline sur les cellules du tableau.

### 6. Dépendances
`action_bar.php`, `data_table.php`, `status_badge.php`, `toast.php`, `empty_state.php`

---

## PAGE 14 — `coiffeurs/portefeuille.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `coiffeurs/portefeuille.php` |
| Rôle | Finance — gains, abonnement, historique transactions |
| Type | Coiffeur |

### 2. Composants actuels
- `layout/header_coiffeur.php` ✅
- ActionBar inline
- StatisticCards `.glass-cct` avec ~47 styles inline
- InformationBlock abonnement entièrement inline
- Timeline transactions entièrement inline
- Modal historique vanilla JS entièrement inline
- `.glass-cct` dupliqué

### 3. Composants DS à utiliser
- ActionBar → `action_bar.php`
- Stats → `statistic_card.php` × 3 (StatisticCard)
- Abonnement → `information_block.php` (InformationBlock)
- Timeline → `timeline_item.php` × N (Timeline)
- Modal → `modal.php`
- `.glass-cct` → depuis `components.css`

### 4. CSS à supprimer
- ~47 attributs `style=""` inline (tous remplacés par composants)
- `<style>` en bas : `.glass-cct` doublon

### 5. Difficulté
**Très élevée** — 47+ styles inline. Plus grand volume de refactoring CSS du projet.

### 6. Dépendances
`action_bar.php`, `statistic_card.php`, `information_block.php`, `timeline_item.php`, `modal.php`

---

## PAGE 15 — `coiffeurs/mes_zones.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `coiffeurs/mes_zones.php` |
| Rôle | Sélection des zones géographiques d'intervention |
| Type | Coiffeur |

### 2. Composants actuels
- `layout/header_coiffeur.php` ✅
- ActionBar inline
- InformationBlock tip (inline)
- Checkboxes `.zone-label` (inline, conforme dans l'esprit)
- `.btn-gold-cct` (dépréciée)
- `.glass-cct` dupliqué

### 3. Composants DS à utiliser
- ActionBar → `action_bar.php`
- Tip → `floating_section.php` variante highlighted
- `.btn-gold-cct` → `.btn-gold`
- `.glass-cct` → depuis `components.css`
- Checkboxes → `.fc-dark` style, labels `.form-label-cct`

### 4. Difficulté
**Faible** — Structure simple, peu de CSS.

---

## PAGES 16–20 — AUTH, PROFIL, ADMIN

---

## PAGE 16 — `access/connexion.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `access/connexion.php` |
| Rôle | Formulaire de connexion |
| Type | Auth |
| Parcours | Home → Connexion → Dashboard |

### 2. Composants actuels
- AuthCard `.glass-card` (conforme à 85% — pas encore DS)
- Fond flou `.auth-bg` (conforme)
- Inputs `.form-control` avec override glass
- Input group avec icônes
- `.btn-gold` (conforme mais avec `font-family:'Segoe UI'` sur body)
- Alerte erreur `.alert-danger` Bootstrap

### 3. Composants DS à utiliser
- `.glass-card` → `auth_card.php` (`AuthCard--sm`)
- Inputs → `form_field.php` (FormField password avec toggle)
- Alerte → `toast.php` ou `.msg-danger`
- Font body → `var(--font-body)` via `variables.css`

### 4. CSS à supprimer
- `font-family:'Segoe UI'` → `var(--font-body)`
- Classes `.glass-card`, `.auth-bg`, `.auth-overlay`, `.auth-wrapper` inline → depuis `components.css`
- `.btn-gold` inline → depuis `components.css`
- `.alert-danger` Bootstrap → `.msg-danger`

### 5. Difficulté
**Faible** — Déjà très proche du DS. Alignement mineur.

---

## PAGE 17 — `access/inscription.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `access/inscription.php` |
| Rôle | Formulaire d'inscription (client ou coiffeur) |
| Type | Auth |

### 2. Composants actuels
- AuthCard `.glass-card` (conforme à 85%)
- `font-family:'Segoe UI'` (non conforme)
- `.alert-msg-danger` (non conforme — doit être `.msg-danger`)
- Formulaire multi-étapes (nom/prénom/sexe/ville/quartier)

### 3. Composants DS à utiliser
- `auth_card.php` (`AuthCard--lg` 620px)
- `form_field.php` pour chaque champ
- `.msg-danger` (pas `.alert-msg-danger`)
- Font body via `var(--font-body)`

### 4. Difficulté
**Faible** — Même démarche que connexion.php.

---

## PAGE 18 — `coiffeurs/profil_coiffeurs.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `coiffeurs/profil_coiffeurs.php` |
| Rôle | Page profil éditable du coiffeur |
| Type | Coiffeur |

### 2. Composants DS à utiliser
- `navbar_coiffeur.php`
- `profile_summary.php` (ProfileSummary)
- `avatar.php` (Avatar LG)
- `form_field.php` (FormField)
- `floating_section.php` (sections édition)
- `action_bar.php`

### 3. Difficulté
**Élevée** (non analysé en détail — probable présence de nombreux styles inline)

---

## PAGE 19 — `profil.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `profil.php` |
| Rôle | Dashboard profil partagé client/coiffeur avec statistiques et graphiques |
| Type | Client / Coiffeur |

### 2. Composants actuels
- `layout/header.php` (sidebar legacy)
- Card profil Bootstrap custom
- Blocs stats `.metric-box` (inline, ~200+ lignes CSS)
- Graphiques Chart.js
- Modal Bootstrap avis
- Modal Bootstrap diplôme
- Formulaire suppression compte
- Footer via `layout/footer.php`

### 3. Composants DS à utiliser
- `navbar_client.php` + `bottom_nav_client.php`
- `profile_summary.php` (profil card)
- `statistic_card.php` × 4 (métriques)
- `floating_section.php` (sections graphiques, portefeuille, infos perso)
- `modal.php` (avis, diplôme, suppression)
- `action_bar.php` ou titre section
- `footer_global.php` + `ia_assistant.php`

### 4. Difficulté
**Très élevée** — Page la plus complexe du projet côté CSS. 200+ lignes inline, plusieurs modales, graphiques.

---

## PAGE 20 — `modifier_profil.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Fichier | `modifier_profil.php` |
| Rôle | Édition des informations personnelles |
| Type | Client / Coiffeur |

### 2. Composants DS
- `navbar_client.php` / `navbar_coiffeur.php`
- `floating_section.php`
- `form_field.php` × N
- `avatar.php` + upload
- `.btn-gold`

### 3. Difficulté
**Élevée** (non analysé en détail)

---

## PAGES 21–25 — ADMIN, TECHNIQUE, SECONDAIRES

---

## PAGE 21 — `first/admin_dashboard.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Type | Admin |
| Rôle | Tableau de bord administrateur |

### 2. Composants DS à utiliser
- Navbar admin (peut réutiliser `navbar_client.php` avec personnalisation)
- `statistic_card.php` × N (KPIs)
- `data_table.php` (listes utilisateurs)
- `floating_section.php` (sections)
- `badges.php` (statuts)

### 3. Difficulté
**Élevée** (non analysé — supposée complexe)

---

## PAGE 22 — `first/admin_actions.php`

### 1. Type
Admin — backend + vues de confirmation

### 2. Composants DS
- `toast.php` (confirmations d'actions)
- `modal.php` (confirmations destructives)
- `floating_section.php`

### 3. Difficulté
**Moyenne**

---

## PAGE 23 — `views/domizi/choix_role.php`

### 1. Informations générales
| Champ | Valeur |
|-------|-------|
| Type | Visiteur |
| Rôle | Sélection du rôle (client / prestataire) pour l'onboarding Domizi |

### 2. Composants actuels
- CSS custom inline complet
- `font-family:'Segoe UI'` non conforme
- `.role-card` (très proche de `.glass-cct` dans l'esprit)
- `.metier-badge` (proche de `.badge-gold`)

### 3. Composants DS à utiliser
- Remplacer `.role-card` → `floating_section.php` avec style card
- `.metier-badge` → `.badge-gold`
- Font → `var(--font-body)`
- Body background → `var(--dark)`

### 4. Difficulté
**Faible** — CSS simple, aucune logique PHP complexe.

---

## PAGE 24 — `deconnexion.php`

### 1. Type
Technique

### 2. Rôle
Déconnexion + redirection. Peut afficher un écran flash avec Toast.

### 3. Composants DS
- `toast.php` (message "Vous avez été déconnecté")
- Pas de navbar nécessaire

### 4. Difficulté
**Très faible**

---

## PAGE 25 — `coiffeurs/sauvegarder_agenda.php`

### 1. Type
Backend / Traitement POST

### 2. Rôle
Traitement du formulaire agenda, redirection vers `agenda_coiffeurs.php`.
Aucune interface visuelle propre → hors-scope UI.

### 3. Difficulté
**Non applicable** (pas de vue)

---

## PAGE 26 — `envoyer_notification_whatsapp.php`

### 1. Type
Backend / API

### 2. Rôle
Envoi notification WhatsApp. Aucune interface → hors-scope UI.

### 3. Difficulté
**Non applicable**

---

---

## TABLEAU RÉCAPITULATIF FINAL

| # | Page | Type | Conformité actuelle | Difficulté migration | Priorité | Ordre | Statut |
|---|------|------|-------------------|---------------------|----------|-------|--------|
| 1 | `views/home.php` | Visiteur | 🟡 75% | Faible | 🔴 Haute | 2 | À migrer |
| 2 | `views/client/dashboard.php` | Client | 🟡 80% | Moyenne | 🔴 Haute | 3 | À migrer |
| 3 | `filter/annuaire_coiffeurs.php` | Client | 🔴 40% | Haute | 🔴 Haute | 4 | À migrer |
| 4 | `coiffeurs/profil_public.php` | Client | 🟡 50% | Élevée | 🔴 Haute | 5 | À migrer |
| 5 | `client/mes_rendezvous.php` | Client | 🔴 30% | Élevée | 🔴 Haute | 6 | À migrer |
| 6 | `client/reserver.php` | Client | 🔴 20% | Élevée | 🔴 Haute | 7 | À migrer |
| 7 | `client/catalogue.php` | Client | 🔴 35% | Moyenne | 🟡 Moyenne | 8 | À migrer |
| 8 | `client/laisser_avis.php` | Client | 🔴 5% | Très élevée | 🟡 Moyenne | 12 | À migrer |
| 9 | `client/creer_rendezvous.php` | Client | 🔴 25% | Moyenne | 🟡 Moyenne | 9 | À migrer |
| 10 | `access/connexion.php` | Auth | 🟡 85% | Faible | 🟢 Basse | 1 | À migrer |
| 11 | `access/inscription.php` | Auth | 🟡 80% | Faible | 🟢 Basse | 1 | À migrer |
| 12 | `layout/header_coiffeur.php` | Layout | 🟡 90% | Faible | 🔴 Haute | 0 | À migrer |
| 13 | `layout/header.php` | Layout | 🔴 30% | Élevée | 🔴 Haute | 0 | À migrer |
| 14 | `layout/footer.php` | Layout | 🟡 60% | Moyenne | 🔴 Haute | 0 | À migrer |
| 15 | `coiffeurs/gestion_catalogue.php` | Coiffeur | 🟡 55% | Haute | 🟡 Moyenne | 10 | À migrer |
| 16 | `coiffeurs/agenda_coiffeurs.php` | Coiffeur | 🟡 70% | Faible | 🟡 Moyenne | 10 | À migrer |
| 17 | `coiffeurs/valider_rendezvous.php` | Coiffeur | 🔴 40% | Élevée | 🟡 Moyenne | 11 | À migrer |
| 18 | `coiffeurs/portefeuille.php` | Coiffeur | 🟡 60% | Très élevée | 🟡 Moyenne | 11 | À migrer |
| 19 | `coiffeurs/mes_zones.php` | Coiffeur | 🟡 70% | Faible | 🟢 Basse | 12 | À migrer |
| 20 | `coiffeurs/profil_coiffeurs.php` | Coiffeur | 🔴 ? | Élevée | 🟡 Moyenne | 11 | À analyser |
| 21 | `profil.php` | Partagé | 🔴 20% | Très élevée | 🟡 Moyenne | 13 | À migrer |
| 22 | `modifier_profil.php` | Partagé | 🔴 ? | Élevée | 🟢 Basse | 14 | À analyser |
| 23 | `first/admin_dashboard.php` | Admin | 🔴 ? | Élevée | 🟢 Basse | 15 | À analyser |
| 24 | `first/admin_actions.php` | Admin | 🔴 ? | Moyenne | 🟢 Basse | 15 | À analyser |
| 25 | `views/domizi/choix_role.php` | Visiteur | 🔴 60% | Faible | 🟢 Basse | 14 | À migrer |
| 26 | `deconnexion.php` | Technique | 🔴 — | Très faible | 🟢 Basse | 14 | À migrer |
| 27 | `coiffeurs/sauvegarder_agenda.php` | Backend | — | N/A | — | — | Hors-scope |
| 28 | `envoyer_notification_whatsapp.php` | Backend | — | N/A | — | — | Hors-scope |

**Légende :** ✅ Conforme | 🟡 Partiellement conforme | 🔴 À migrer | 🔲 Hors-scope

---

## ORDRE DE MIGRATION RECOMMANDÉ

```
PHASE 0 — Fondations (css/variables.css, css/animations.css, css/components.css) ✅ FAIT
↓
PHASE 1 — Layouts (header_coiffeur.php, footer.php, header.php)
↓
PHASE 2 — Auth (connexion.php, inscription.php) — Priorité 1, difficulté faible
↓
PHASE 3 — Pages client haute priorité
    dashboard.php → profil_public.php → annuaire_coiffeurs.php → mes_rendezvous.php
    → reserver.php → catalogue.php → creer_rendezvous.php
↓
PHASE 4 — Pages coiffeur
    gestion_catalogue.php → agenda_coiffeurs.php → valider_rendezvous.php
    → portefeuille.php → mes_zones.php → profil_coiffeurs.php
↓
PHASE 5 — Profils et admin
    profil.php → modifier_profil.php → admin_dashboard.php → admin_actions.php
    → choix_role.php → deconnexion.php
↓
PHASE 6 — Nettoyage final
    Supprimer css/global.css, css/responsive-custom.css
    Supprimer tous les <style> inline
    Remplacer btn-gold-cct → btn-gold
    Remplacer luxury-profile-card → gallery-card
```

---

*Document généré le Juillet 2026 — Référence officielle de migration*  
*Ne modifier aucune page avant la Phase 0 validée.*
