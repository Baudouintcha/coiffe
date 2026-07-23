# DESIGN SYSTEM — Coiffe Chez Toi
> Référence officielle du projet. Toute modification d'interface doit respecter ce document.
> Version : 1.0 — Juillet 2026

---

## 1. PALETTE DE COULEURS

### Couleurs primaires
| Nom | Variable CSS | Valeur | Usage |
|-----|-------------|--------|-------|
| Gold | `--gold` | `#D4AF37` | Accents, boutons CTA, icônes actives, bordures premium |
| Gold dim | `--gold-dim` | `rgba(212,175,55,0.12)` | Fond de badges, hover léger, backgrounds secondaires |
| Gold hover | `--gold-hover` | `#C9A227` | État hover sur boutons gold |
| Gold foncé | `--gold-dark` | `#B38F1D` | Gradients, dégradés |

### Couleurs de fond (Dark theme)
| Nom | Variable CSS | Valeur | Usage |
|-----|-------------|--------|-------|
| Background 1 | `--dark` | `#0A0A0A` | Fond principal des pages |
| Background 2 | `--dark-2` | `#111111` | Cartes, modales |
| Background 3 | `--dark-3` | `#161616` | Sous-sections, tableaux |
| Background 4 | `--dark-4` | `#1A1A1A` | Inputs, éléments interactifs |
| Black pur | `--black` | `#000000` | Navbar, footer, overlays |

### Couleurs de texte
| Nom | Variable CSS | Valeur | Usage |
|-----|-------------|--------|-------|
| Text primary | `--text-primary` | `#FFFFFF` | Titres, textes importants |
| Text secondary | `--text-secondary` | `rgba(255,255,255,0.65)` | Labels, descriptions |
| Text muted | `--text-muted` | `rgba(255,255,255,0.35)` | Metadata, dates |
| Text disabled | `--text-disabled` | `rgba(255,255,255,0.20)` | Éléments désactivés |

### Couleurs fonctionnelles
| Nom | Variable CSS | Valeur | Usage |
|-----|-------------|--------|-------|
| Success | `--success` | `rgba(25,135,84,0.15)` | Fond alertes succès |
| Success text | `--success-text` | `#6EE7B7` | Texte succès |
| Danger | `--danger` | `rgba(220,53,69,0.15)` | Fond alertes danger |
| Danger text | `--danger-text` | `#FFB3B3` | Texte danger/erreur |
| Warning | `--warning-color` | `#FFD60A` | Alertes urgentes |
| Info | `--info-text` | `#93C5FD` | Texte informatif |

---

## 2. VARIABLES CSS — FICHIER DE RÉFÉRENCE

Toutes ces variables doivent être définies dans un futur fichier `css/variables.css` et importées en tête de chaque page.

```css
:root {
  /* Couleurs */
  --gold:            #D4AF37;
  --gold-dim:        rgba(212,175,55,0.12);
  --gold-hover:      #C9A227;
  --gold-dark:       #B38F1D;

  --dark:            #0A0A0A;
  --dark-2:          #111111;
  --dark-3:          #161616;
  --dark-4:          #1A1A1A;
  --black:           #000000;

  --text-primary:    #FFFFFF;
  --text-secondary:  rgba(255,255,255,0.65);
  --text-muted:      rgba(255,255,255,0.35);
  --text-disabled:   rgba(255,255,255,0.20);

  --success-bg:      rgba(25,135,84,0.15);
  --success-border:  rgba(25,135,84,0.30);
  --success-text:    #6EE7B7;
  --danger-bg:       rgba(220,53,69,0.15);
  --danger-border:   rgba(220,53,69,0.30);
  --danger-text:     #FFB3B3;
  --warning-color:   #FFD60A;

  /* Glassmorphism */
  --glass-bg:        rgba(255,255,255,0.06);
  --glass-bg-strong: rgba(255,255,255,0.10);
  --glass-border:    rgba(255,255,255,0.08);
  --glass-border-md: rgba(255,255,255,0.14);
  --glass-border-lg: rgba(255,255,255,0.22);

  /* Typographie */
  --font-display:    'Playfair Display', Georgia, serif;
  --font-body:       'Inter', 'Segoe UI', sans-serif;

  /* Espacements */
  --space-xs:        4px;
  --space-sm:        8px;
  --space-md:        16px;
  --space-lg:        24px;
  --space-xl:        40px;
  --space-2xl:       64px;

  /* Border Radius */
  --radius-sm:       8px;
  --radius-md:       12px;
  --radius-lg:       16px;
  --radius-xl:       20px;
  --radius-2xl:      28px;
  --radius-pill:     50px;
  --radius-circle:   50%;

  /* Ombres */
  --shadow-sm:       0 2px 8px rgba(0,0,0,0.20);
  --shadow-md:       0 8px 32px rgba(0,0,0,0.35);
  --shadow-lg:       0 12px 40px rgba(0,0,0,0.50);
  --shadow-xl:       0 24px 60px rgba(0,0,0,0.55);
  --shadow-gold:     0 0 20px rgba(212,175,55,0.30);
  --shadow-gold-lg:  0 0 30px rgba(212,175,55,0.60);

  /* Transitions */
  --transition-fast: all 0.2s ease;
  --transition-base: all 0.3s ease;
  --transition-slow: all 0.5s ease;
  --transition-spring: all 0.3s cubic-bezier(0.25,0.8,0.25,1);

  /* Layout */
  --max-width:       1400px;
  --navbar-height:   64px;
  --bottomnav-height: 56px;
}
```

---

## 3. TYPOGRAPHIE

### Polices autorisées
| Police | Famille | Usage |
|--------|---------|-------|
| **Playfair Display** | Serif | Titres principaux (H1, H2, brand, hero) |
| **Inter** | Sans-serif | Corps de texte, labels, navigation, UI |

> ⚠️ Aucune autre police ne doit être introduite dans le projet.  
> `'Segoe UI'` et `'Arial'` encore présents dans certaines pages doivent être remplacés par `Inter`.

### Hiérarchie des titres
```css
h1 { font-family: var(--font-display); font-size: clamp(2.6rem,8vw,5rem); font-weight:900; letter-spacing:3px; }
h2 { font-family: var(--font-display); font-size: clamp(1.4rem,3vw,2rem);  font-weight:700; }
h3 { font-family: var(--font-display); font-size: 1.2rem;  font-weight:700; }
h4 { font-family: var(--font-body);    font-size: 1rem;    font-weight:700; }
h5 { font-family: var(--font-body);    font-size: 0.9rem;  font-weight:700; }
h6 { font-family: var(--font-body);    font-size: 0.82rem; font-weight:600; }
p  { font-family: var(--font-body);    font-size: 0.9rem;  font-weight:400; line-height:1.6; }
```

### Tailles de texte UI
| Rôle | Taille | Poids |
|------|--------|-------|
| Label uppercase (inputs) | `0.72rem` | `600` + `letter-spacing: 0.8px` + `uppercase` |
| Metadata / date / muted | `0.68–0.72rem` | `400` |
| Small description | `0.82rem` | `400` |
| Body standard | `0.9rem` | `400` |
| Body emphasis | `0.9rem` | `600` |
| Prix / valeur | `1rem–1.4rem` | `700` |
| Brand / logo | `1.15rem` | `700` + Playfair |

---

## 4. ESPACEMENTS

### Système de grille interne
- Toutes les sections utilisent `padding: 4rem 0` (desktop) → `2rem 0` (mobile)
- Toutes les pages à layout ont un wrapper `.container` Bootstrap standard
- La largeur maximale du contenu est `1400px` (`--max-width`)
- Le padding horizontal du body sur mobile : `1rem` (16px)
- Écart entre les cartes : `gap: 1.5rem` (grille Bootstrap `g-4`)

### Padding des composants
| Composant | Padding |
|-----------|---------|
| Navbar | `0 2rem` (height: 64px) |
| Carte petite | `12px 16px` |
| Carte standard | `16px 20px` |
| Carte large | `24px 28px` |
| Modal | `24px` |
| Section page | `4rem 0` |
| Form field | `11px 14px` |
| Bouton SM | `6px 14px` |
| Bouton MD | `10px 22px` |
| Bouton LG | `12px 30px` |

---

## 5. BORDER RADIUS

| Nom | Valeur | Usage |
|-----|--------|-------|
| `--radius-sm` | `8px` | Boutons SM, inputs, badges |
| `--radius-md` | `12px` | Cartes secondaires, tags |
| `--radius-lg` | `16px` | Cartes principales, modales |
| `--radius-xl` | `20px` | Glass cards, formulaires |
| `--radius-2xl` | `28px` | Hero capsule mobile |
| `--radius-pill` | `50px` | Capsule de recherche, badges pill |
| `--radius-circle` | `50%` | Avatars, boutons circulaires |
| Héro desktop | `52px` | Capsule flottante principale du dashboard |

---

## 6. OMBRES

```css
/* Ombre légère — cartes secondaires */
box-shadow: 0 2px 8px rgba(0,0,0,0.20);

/* Ombre standard — cartes, dropdowns */
box-shadow: 0 8px 32px rgba(0,0,0,0.35);

/* Ombre forte — hero, modales */
box-shadow: 0 12px 40px rgba(0,0,0,0.50);

/* Ombre XL — hero capsule */
box-shadow: 0 24px 60px rgba(0,0,0,0.55), 0 4px 16px rgba(0,0,0,0.30), 0 0 0 1px rgba(255,255,255,0.06);

/* Aura dorée — bulles IA, éléments actifs */
box-shadow: 0 0 20px rgba(212,175,55,0.30);

/* Aura dorée forte — hover bulle IA */
box-shadow: 0 0 30px rgba(212,175,55,0.60);

/* Ombre bouton gold (hover) */
box-shadow: 0 4px 15px rgba(212,175,55,0.35);
```

---

## 7. GLASSMORPHISM

Le glassmorphism est le langage visuel central du projet. Il s'applique en 3 niveaux d'intensité.

### Niveau 1 — Léger (cartes secondaires, navbars)
```css
background: rgba(255,255,255,0.04);
backdrop-filter: blur(20px);
-webkit-backdrop-filter: blur(20px);
border: 1px solid rgba(255,255,255,0.08);
border-radius: var(--radius-lg);
```

### Niveau 2 — Standard (formulaires, hero cards, profil cards)
```css
background: rgba(255,255,255,0.08);
backdrop-filter: blur(24px);
-webkit-backdrop-filter: blur(24px);
border: 1px solid rgba(255,255,255,0.14);
border-radius: var(--radius-xl);
box-shadow: 0 8px 40px rgba(0,0,0,0.40);
```

### Niveau 3 — Fort (hero search bar, popups premium)
```css
background: rgba(255,255,255,0.12);
backdrop-filter: blur(24px);
-webkit-backdrop-filter: blur(24px);
border: 1px solid rgba(255,255,255,0.22);
border-radius: var(--radius-pill);
box-shadow: 0 8px 32px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.10);
```

### Classe utilitaire
```css
.glass { background:var(--glass-bg); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border:1px solid var(--glass-border); border-radius:var(--radius-lg); }
.glass-md { background:var(--glass-bg-strong); backdrop-filter:blur(24px); -webkit-backdrop-filter:blur(24px); border:1px solid var(--glass-border-md); border-radius:var(--radius-xl); }
```

---

## 8. ANIMATIONS

### Animations de base autorisées dans le projet

```css
/* Apparition fluide — transitions de page, cartes */
@keyframes fadeSlideIn {
  from { opacity:0; transform:translateY(12px); }
  to   { opacity:1; transform:translateY(0); }
}
.page-transition { animation: fadeSlideIn 0.3s ease forwards; }

/* Émergence — modales, glass cards */
@keyframes emerge {
  from { opacity:0; transform:translateY(30px) scale(0.95); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}
.fade-in-card { animation: emerge 0.6s cubic-bezier(0.22,1,0.36,1); }

/* Shimmer — skeleton loaders */
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.skeleton {
  background: linear-gradient(90deg, #1a1a1a 25%, #222 50%, #1a1a1a 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

/* Pulse — cloche notifications, alertes */
@keyframes pulseBell {
  0%   { transform:scale(0.9); opacity:0.8; }
  50%  { transform:scale(1.1); opacity:1; }
  100% { transform:scale(0.9); opacity:0.8; }
}
.animate-pulse { animation: pulseBell 1.5s infinite; }

/* Blink — indicateur de saisie IA */
@keyframes blink {
  0%   { opacity:.2; }
  20%  { opacity:1; }
  100% { opacity:.2; }
}
```

### Règles d'animation
- Durée standard : `0.2s` (hover léger), `0.3s` (transition), `0.6s` (émergence)
- Easing standard : `ease` ou `cubic-bezier(0.25,0.8,0.25,1)` pour les cartes
- Easing premium : `cubic-bezier(0.22,1,0.36,1)` pour les modales
- **Aucune animation > 0.6s** sauf skeleton et pulse
- Toutes les transitions hover sur cartes : `transform: translateY(-4px) à -8px`
- Transition de bordure gold au hover : `border-color: rgba(212,175,55,0.3–0.4)`

---

## 9. RESPONSIVE — BREAKPOINTS

| Breakpoint | Valeur | Comportement clé |
|-----------|--------|-----------------|
| XS | `< 320px` | Support smartwatch (classe `.style-smartwatch-container`) |
| SM | `480px` | Hero radius réduit, search bar en colonne |
| MD | `768px` | Bottom nav apparaît, sidebar topbar masquée, hero profile card masquée |
| LG | `1024px` | Grille 3 colonnes active |
| XL | `1400px` | Largeur max conteneur |

### Règles responsive obligatoires
- Toute page a une `bottom nav` sur mobile (< 768px)
- La navbar desktop est sticky et blurée (`position: sticky; backdrop-filter: blur`)
- Les hero desktop (`border-radius: 52px`) passent à `28px` sur MD et `20px` sur SM
- Le padding des pages passe de `2rem` à `1rem` sur mobile
- Les formulaires d'auth sont `max-width: 440px` (connexion) / `620px` (inscription)
- Les grilles de cartes : `col-12 col-sm-6 col-lg-4` (3 colonnes max)

---

## 10. NAVIGATION

### Navbar principale (pages client)
- `position: fixed` ou `sticky`, `height: 64px`
- Background : `rgba(10,10,10,0.85)` + `backdrop-filter: blur(20px)`
- Border bottom : `1px solid rgba(255,255,255,0.06)`
- Brand : Police `Playfair Display`, couleur `--gold`, `font-size: 1.15rem`
- Icônes d'action : cercles 38×38px, `border-radius: 50%`, `background: rgba(255,255,255,0.05)`
- Hover icône : `background: var(--gold-dim)`, `color: var(--gold)`, `border-color: var(--gold)`

### Navbar coiffeur (espace coiffeur)
- `position: sticky`, `height: 60px`
- Même traitement glassmorphism
- Brand `CCT` → sera remplacé par logo image quand disponible
- Affiche le nom de page courante à droite du brand (`:` separator)

### Sidebar accordéon (mode invité/legacy)
- Largeur : `280px` quand ouverte, `0` quand fermée
- Background : `#000`, bordure droite `2px solid var(--gold)`
- Transition : `0.5s`
- Overlay fond : `rgba(0,0,0,0.7)` avec `z-index: 2999`

### Bottom Navigation (mobile uniquement, < 768px)
- `position: fixed; bottom: 0`
- Background : `#000`, border top : `1px solid rgba(255,255,255,0.06)`
- Hauteur : `56px` + safe area bottom
- Items : flex, space-around, icône + label `0.58rem`
- Couleur active/hover : `--gold`
- Couleur inactive : `rgba(255,255,255,0.30)`

---

## 11. FORMULAIRES & CHAMPS

### Style standard dark glass
```css
.form-field {
  background: rgba(255,255,255,0.10);
  border: 1px solid rgba(255,255,255,0.20);
  color: #fff;
  border-radius: var(--radius-sm);
  padding: 11px 14px;
  backdrop-filter: blur(4px);
  transition: var(--transition-fast);
}
.form-field:focus {
  background: rgba(255,255,255,0.18);
  border-color: var(--gold);
  box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
  color: #fff;
}
.form-field::placeholder { color: rgba(255,255,255,0.30); }
```

### Labels
- `font-size: 0.72rem`, `font-weight: 600`, `letter-spacing: 0.8px`, `text-transform: uppercase`
- Couleur : `rgba(255,255,255,0.65)`

### Select
- Même style que `.form-field`
- Options background : `#1A1A1A`, couleur : `#fff`

### Input group (icône + champ + toggle)
- Icône gauche : `background: rgba(255,255,255,0.08)`, bordure sans bord droit
- Toggle droit : même fond, bordure sans bord gauche
- Radius : `10px 0 0 10px` / `0 10px 10px 0`

### Messages de feedback
```css
.msg-success { background:rgba(25,135,84,0.15); border:1px solid rgba(25,135,84,0.30); color:#6EE7B7; border-radius:10px; padding:10px 16px; font-size:0.85rem; }
.msg-danger  { background:rgba(220,53,69,0.15);  border:1px solid rgba(220,53,69,0.30);  color:#FFB3B3; border-radius:10px; padding:10px 16px; font-size:0.85rem; }
.msg-warning { background:rgba(255,214,10,0.12); border:1px solid rgba(255,214,10,0.30);  color:#FFD60A;  border-radius:10px; padding:10px 16px; font-size:0.85rem; }
```

---

## 12. BOUTONS

### Hiérarchie des boutons
| Variant | Classe | Usage |
|---------|--------|-------|
| Primary (gold) | `.btn-gold` | Action principale, CTA |
| Secondary (outline gold) | `.btn-outline-gold` | Action secondaire |
| Ghost | `.btn-ghost` | Action tertiaire, navigation |
| Danger | `.btn-danger-cct` | Suppression, annulation |
| Floating | `.btn-floating` | Actions rapides en overlay |

### Styles

```css
/* Primary */
.btn-gold { background:var(--gold); color:#000; font-weight:700; border:none; border-radius:var(--radius-sm); transition:var(--transition-fast); }
.btn-gold:hover { background:var(--gold-hover); transform:translateY(-1px); box-shadow:0 4px 15px rgba(212,175,55,0.35); }

/* Outline Gold */
.btn-outline-gold { background:transparent; color:var(--gold); border:1px solid var(--gold); font-weight:700; border-radius:var(--radius-sm); transition:var(--transition-fast); }
.btn-outline-gold:hover { background:var(--gold-dim); }

/* Ghost */
.btn-ghost { background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.60); border:1px solid rgba(255,255,255,0.08); border-radius:var(--radius-sm); transition:var(--transition-fast); }
.btn-ghost:hover { background:var(--gold-dim); color:var(--gold); border-color:var(--gold); }

/* Danger */
.btn-danger-cct { background:rgba(220,53,69,0.20); color:#FFB3B3; border:1px solid rgba(220,53,69,0.35); border-radius:var(--radius-pill); font-weight:700; }
.btn-danger-cct:hover { background:rgba(220,53,69,0.35); }
```

### Tailles
| Taille | Padding | Font-size |
|--------|---------|-----------|
| SM | `6px 14px` | `0.72rem` |
| MD (default) | `10px 22px` | `0.82rem` |
| LG | `12px 30px` | `0.9rem` |
| XL (hero) | `12px 30px` | `0.9rem` + `border-radius: 50px` |

---

## 13. STYLE DES CARTES

### Carte coiffeur (catalogue / annuaire)
```css
.coiffeur-card {
  background: var(--dark-2);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: transform 0.3s cubic-bezier(0.25,0.8,0.25,1), border-color 0.3s;
}
.coiffeur-card:hover {
  transform: translateY(-6px);
  border-color: rgba(212,175,55,0.40);
  box-shadow: 0 12px 40px rgba(0,0,0,0.50);
}
```
- Cover : `height: 180px`, `object-fit: cover`
- Avatar : `44px × 44px`, `border: 3px solid var(--dark-2)`, décalé à `-20px` du bas de la cover
- Badge vérifié : `position: absolute; top:10px; right:10px`, fond vert `rgba(25,135,84,0.9)`, blur

### Glass card (portefeuille, sections coiffeur)
```css
.glass-cct {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: var(--radius-lg);
  transition: border-color 0.3s;
}
.glass-cct:hover { border-color: rgba(212,175,55,0.25); }
```

### Auth card (connexion / inscription)
```css
.auth-card {
  background: rgba(255,255,255,0.08);
  backdrop-filter: blur(28px);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: var(--radius-xl);
  box-shadow: 0 10px 50px rgba(0,0,0,0.50);
  max-width: 440px; /* connexion */ /* 620px inscription */
}
```

### Carte statistique (portefeuille coiffeur)
- Fond : `.glass-cct`
- Icône centrée : `font-size: 1.3rem`, couleur `--gold` ou rouge
- Label uppercase : `0.65rem`, `rgba(255,255,255,0.35)`
- Valeur : `1rem`, `font-weight: 700`

---

## 14. BADGES & TAGS

```css
/* Badge certifié (vert) */
.badge-verified { background:rgba(25,135,84,0.90); backdrop-filter:blur(8px); color:#fff; font-size:0.68rem; font-weight:700; padding:3px 8px; border-radius:20px; }

/* Badge gold (catégorie, type) */
.badge-gold { background:rgba(212,175,55,0.10); border:1px solid rgba(212,175,55,0.20); color:var(--gold); font-size:0.65rem; padding:2px 8px; border-radius:20px; }

/* Status badge */
.badge-pending  { background:#FFC107; color:#000; }
.badge-confirmed{ background:#0D6EFD; color:#fff; }
.badge-done     { background:#198754; color:#fff; }
.badge-cancelled{ background:#6C757D; color:#fff; }

/* Tous les badges : border-radius: 20px; font-weight:700; padding: 3px 10px; font-size: 0.75rem */
```

---

## 15. MODALES & POPUPS

### Modal standard
```css
/* Fond */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.70); backdrop-filter:blur(8px); z-index:500; display:flex; align-items:center; justify-content:center; }

/* Contenu */
.modal-box { background:#111; border:1px solid rgba(212,175,55,0.20); border-radius:var(--radius-xl); width:90%; max-width:700px; max-height:80vh; overflow-y:auto; padding:24px; }
```
- Header : `border-bottom: 1px solid rgba(255,255,255,0.08)`, titre en `--gold`, bouton close `&times;` couleur `rgba(255,255,255,0.4)`
- Fermeture : clic sur overlay OU bouton close
- Scrollbar custom : `width: 4px`, thumb `var(--gold)`, `border-radius: 10px`

### Toast / notification flash
- `position: fixed; bottom: 80px; right: 20px` (au-dessus de la bottom nav)
- Fond glassmorphisme niveau 2
- Durée d'affichage : 4 secondes, disparaît avec `fadeSlideIn` inversé
- Types : success (vert), danger (rouge), warning (gold)

---

## 16. TABLEAUX

```css
/* Style de base dark */
.table-dark-cct { background:var(--dark-2); border:1px solid rgba(255,255,255,0.08); border-radius:var(--radius-md); overflow:hidden; }
.table-dark-cct thead tr { background:rgba(255,255,255,0.04); }
.table-dark-cct thead th { color:rgba(255,255,255,0.50); font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; padding:12px 16px; border:none; }
.table-dark-cct tbody tr { border-bottom:1px solid rgba(255,255,255,0.04); transition:background 0.2s; }
.table-dark-cct tbody tr:hover { background:rgba(255,255,255,0.03); }
.table-dark-cct tbody td { padding:12px 16px; font-size:0.85rem; color:#fff; border:none; vertical-align:middle; }
```

---

## 17. STATES VIDES (Empty States)

### Règle d'affichage
Toute liste, tableau ou section vide doit afficher un empty state cohérent.

```html
<div class="empty-state">
  <i class="bi bi-[icone-contexte]"></i>
  <p>[Message descriptif]</p>
  <a class="btn-gold" href="[action]">[CTA si pertinent]</a>
</div>
```

```css
.empty-state { text-align:center; padding:4rem 2rem; color:rgba(255,255,255,0.30); }
.empty-state i { font-size:3rem; display:block; margin-bottom:1rem; opacity:0.4; }
.empty-state p { font-size:0.85rem; margin-bottom:1.5rem; }
```

---

## 18. LOADERS / SKELETON

```css
.skeleton {
  background: linear-gradient(90deg, #1a1a1a 25%, #222222 50%, #1a1a1a 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: var(--radius-md);
}
.skeleton-card  { height: 340px; border-radius: var(--radius-lg); }
.skeleton-text  { height: 14px; margin-bottom: 8px; }
.skeleton-title { height: 22px; width: 60%; margin-bottom: 12px; }
.skeleton-avatar{ height: 44px; width: 44px; border-radius: 50%; }
```

---

## 19. HERO SECTION

### Hero Premium (page d'accueil)
- Plein écran `100vw × 100vh`
- Slider d'images avec transition `opacity 1s ease-in-out`
- Overlay : `rgba(0,0,0,0.50)`, `z-index: 1`
- Contenu centré : `position: absolute; top:50%; left:50%; transform:translate(-50%,-50%)`
- Titre : Playfair Display, `clamp(3rem,10vw,6rem)`, uppercase, `letter-spacing: 5px`

### Hero Capsule (dashboard client)
- `border-radius: 52px`, largeur `92%`, max `1400px`, hauteur `420px`
- Ombre multi-couche premium
- Profile glass card en haut à droite (masquée sur mobile < 768px)
- Search bar glassmorphisme au bas de la capsule
- Carousel d'images confiné dans la capsule

### Hero Annuaire (compact)
- Hauteur `min 35vh`
- Background flou `filter:blur(4px) brightness(0.18)`
- Formulaire de recherche dans glass card en bas de section

---

## 20. SEARCH CAPSULE

```css
.search-bar {
  display: flex; align-items: center;
  background: rgba(255,255,255,0.12);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,0.22);
  border-radius: 50px;
  padding: 6px 6px 6px 20px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.10);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.search-bar:focus-within {
  border-color: var(--gold);
  box-shadow: 0 8px 32px rgba(0,0,0,0.40), 0 0 0 3px rgba(212,175,55,0.15);
}
/* Input interne */
.search-bar input { flex:1; background:none; border:none; outline:none; color:#fff; font-size:0.92rem; }
/* Bouton interne */
.search-bar .search-btn { background:var(--gold); color:#000; border:none; border-radius:50px; padding:10px 22px; font-weight:700; font-size:0.82rem; }
```

---

## 21. FOOTER

```css
.cct-footer { background:#000; border-top:1px solid rgba(212,175,55,0.20); padding:3rem 0 1.5rem; }
.footer-brand { font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:var(--gold); }
.footer-desc  { color:rgba(255,255,255,0.35); font-size:0.82rem; line-height:1.6; }
.footer-link  { color:rgba(255,255,255,0.40); text-decoration:none; font-size:0.82rem; display:block; margin-bottom:6px; transition:color 0.2s; }
.footer-link:hover { color:var(--gold); }
.footer-social a { color:rgba(255,255,255,0.40); font-size:1.2rem; margin-right:12px; transition:color 0.2s; }
.footer-social a:hover { color:var(--gold); }
.footer-bottom { border-top:1px solid rgba(255,255,255,0.05); margin-top:2rem; padding-top:1rem; color:rgba(255,255,255,0.20); font-size:0.75rem; text-align:center; }
```

Colonnes footer : Brand + desc (col-4) | Navigation (col-2) | Légal (col-2) | Contact + social (col-4)  
Sur mobile : tout centré, stacked

---

## 22. GALERIES & IMAGES

- Images de cover : `width:100%; height:180–240px; object-fit:cover`
- Placeholder image : fond `linear-gradient(135deg, #151515, #252525)` + icône Bootstrap centrée
- Galerie prestations : grille `col-sm-6 col-md-4`, `border-radius: var(--radius-md)`, overflow hidden
- Photos profil (avatar) : `44px–100px`, `border-radius: 50%`, `border: 2px solid var(--gold)`

---

## 23. ICÔNES

- Bibliothèque unique : **Bootstrap Icons** (`bi bi-*`)
- Taille standard dans cartes : `1rem–1.3rem`
- Taille hero/section : `1.4rem–2rem`
- Taille empty state : `3rem`
- Couleur active : `var(--gold)` ou `#fff`
- Couleur inactive : `rgba(255,255,255,0.30–0.50)`
- Aucune autre bibliothèque d'icônes (pas de FontAwesome, pas d'Heroicons)

---

## 24. RÈGLES GÉNÉRALES DE MISE EN PAGE

1. Toute page a un fond `background: var(--dark)` (#0A0A0A)
2. Toute page inclut la navbar (client ou coiffeur selon le contexte)
3. Toute page mobile a une bottom navigation fixe
4. Le contenu commence à `padding-top: var(--navbar-height)` ou via wrapper
5. Sections alternent entre `var(--dark)` et `var(--dark-2)` si plusieurs blocs
6. Aucune couleur blanche de fond — le projet est 100% dark theme
7. Tous les textes par défaut sont `color: #fff`
8. Les liens sans style explicite héritent de `color: var(--gold)`
9. Le project utilise Bootstrap 5.3.3 comme grille — les composants Bootstrap natifs sont restyled en dark
10. La scrollbar custom s'applique aux modales et aux zones scrollables : `4px wide`, `thumb: var(--gold)`
