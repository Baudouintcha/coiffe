# RAPPORT D'AUDIT UI — Coiffe Chez Toi
> Analyse complète du projet existant : composants, CSS, doublons, incohérences.
> Version : 1.1 — Juillet 2026 | Mis à jour suite à la stabilisation Design System v2.0

> **Note de mise à jour :** Les corrections documentées dans `DESIGN_SYSTEM_REVIEW.md` ont été appliquées dans `DESIGN_SYSTEM.md` v2.0 et `COMPONENT_LIBRARY.md` v2.0. Ce rapport reflète l'état actuel du code source (pages PHP non encore migrées) et reste valide comme référence de l'état initial.

---

## 1. COMPOSANTS DÉJÀ EXISTANTS

### Composants fonctionnels et visuellement cohérents
| Composant | Fichier(s) source | Qualité |
|-----------|------------------|---------|
| Hero Slider plein écran | `views/home.php` + `css/style.css` | ✅ Bonne |
| Auth Glass Card (connexion) | `access/connexion.php` | ✅ Très bonne |
| Auth Glass Card (inscription) | `access/inscription.php` | ✅ Très bonne |
| Hero Capsule flottante | `views/client/dashboard.php` | ✅ Excellente |
| Search Capsule glassmorphisme | `views/client/dashboard.php` | ✅ Excellente |
| Navbar client premium | `views/client/dashboard.php` | ✅ Excellente |
| Topbar coiffeur sticky | `layout/header_coiffeur.php` | ✅ Très bonne |
| Bottom nav coiffeur (mobile) | `layout/header_coiffeur.php` | ✅ Très bonne |
| Service Card (coiffeur) | `views/client/dashboard.php` | ✅ Excellente |
| Statistic Card (portefeuille) | `coiffeurs/portefeuille.php` | ✅ Bonne |
| Timeline transactions | `coiffeurs/portefeuille.php` | ✅ Bonne |
| Information Block (abonnement) | `coiffeurs/portefeuille.php` | ✅ Bonne |
| Modal historique complet | `coiffeurs/portefeuille.php` | ✅ Bonne |
| Gallery Card (prestation) | `coiffeurs/profil_public.php` + `gestion_catalogue.php` | 🟡 À harmoniser |
| Availability Card (jours) | `coiffeurs/profil_public.php` | ✅ Bonne |
| Profile Summary | `coiffeurs/profil_public.php` | 🟡 Partiellement |
| Action Bar (titre + retour) | Toutes les pages coiffeur | ✅ Consistante |
| NotificationDropdown | `layout/header.php` | 🟡 Fonctionnel |
| IA Bubble + Chat window | `layout/footer.php` | ✅ Très bonne |
| Hero Annuaire (compact) | `filter/annuaire_coiffeurs.php` | ✅ Bonne |
| Appointment Card (mobile) | `client/mes_rendezvous.php` | 🔴 Legacy |
| Appointment Table (desktop) | `client/mes_rendezvous.php` | 🔴 Legacy |
| Prevention Modal (annulation) | `client/mes_rendezvous.php` | 🟡 Fonctionnel |
| Sidebar accordéon | `layout/header.php` | 🔴 Legacy |

---

## 2. COMPOSANTS DUPLIQUÉS

Ces blocs CSS sont **redéfinis dans chaque fichier** au lieu d'être centralisés.

### Doublon critique n°1 — `.glass-cct`
**Défini dans :** `coiffeurs/agenda_coiffeurs.php`, `coiffeurs/gestion_catalogue.php`, `coiffeurs/portefeuille.php`

```css
/* Copie identique dans les 3 fichiers */
.glass-cct { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:16px; }
```
**Solution :** Déplacer vers `css/components.css`. Supprimer les 3 copies.

### Doublon critique n°2 — `.btn-gold-cct`
**Défini dans :** `coiffeurs/agenda_coiffeurs.php`, `coiffeurs/gestion_catalogue.php`, `coiffeurs/portefeuille.php`, `layout/header_coiffeur.php`

```css
/* Identique dans les 4 fichiers */
.btn-gold-cct { background:var(--gold); color:#000; font-weight:700; border:none; border-radius:10px; }
.btn-gold-cct:hover { background:#c9a227; color:#000; }
```
**Solution :** Définir une seule fois dans `css/components.css`.

### Doublon critique n°3 — `.fc-dark` (form control dark)
**Défini dans :** `coiffeurs/agenda_coiffeurs.php`, `coiffeurs/gestion_catalogue.php`, `layout/header_coiffeur.php`

```css
.fc-dark { background:rgba(255,255,255,0.06)!important; border:1px solid rgba(255,255,255,0.12)!important; color:#fff!important; border-radius:10px!important; }
```
**Solution :** Centraliser dans `css/components.css`.

### Doublon critique n°4 — `.msg-success` / `.msg-danger`
**Défini dans :** `coiffeurs/agenda_coiffeurs.php`, `coiffeurs/gestion_catalogue.php`, `layout/header_coiffeur.php`

**Solution :** Centraliser dans `css/components.css`.

---

## 3. VARIABLES CSS DUPLIQUÉES

### `:root` redéfini dans chaque fichier
Chaque page redéclare ses propres variables CSS au lieu d'utiliser un fichier centralisé.

| Variable | Fichiers où elle est redéclarée |
|----------|--------------------------------|
| `--gold: #D4AF37` | `global.css`, `header.php`, `header_coiffeur.php`, `dashboard.php`, `profil_public.php`, `connexion.php`, `inscription.php`, `annuaire_coiffeurs.php` |
| `--dark: #0A0A0A` | `header_coiffeur.php`, `dashboard.php` |
| `--dark-2: #111` | `dashboard.php` |
| `--gold-dim: rgba(212,175,55,0.12)` | `header_coiffeur.php`, `dashboard.php` |
| `--glass: rgba(0,0,0,0.6)` | `global.css` (valeur différente des autres !) |
| `--border-glass: 1px solid rgba(255,255,255,0.1)` | `global.css` uniquement |

### Incohérence détectée
- **`global.css`** définit `--glass: rgba(0,0,0,0.6)` — c'est un fond noir opaque, PAS du glassmorphisme
- **`dashboard.php`** et **`header_coiffeur.php`** définissent `rgba(255,255,255,0.04)` pour les glass cards — c'est le bon style
- → `global.css` doit être mis à jour pour aligner avec la référence du dashboard

### Variables manquantes dans le projet
Ces variables n'existent nulle part mais sont utilisées sous forme de valeurs codées en dur.
**Elles sont désormais définies dans DS §2 v2.0** et seront disponibles après création de `css/variables.css` :
- `--dark-3: #161616` ✅ Défini dans DS v2.0
- `--dark-4: #1A1A1A` ✅ Défini dans DS v2.0
- `--radius-*` ✅ Définis dans DS v2.0
- `--shadow-*` ✅ Définis dans DS v2.0
- `--font-display` / `--font-body` ✅ Définis dans DS v2.0
- `--transition-*` ✅ Définis dans DS v2.0
- `--danger-icon: #FF6B6B` ✅ Ajouté dans DS v2.0
- `--select-active: #FFC107` ✅ Ajouté dans DS v2.0
- `--glass-bg-subtle: rgba(255,255,255,0.02)` ✅ À ajouter dans `css/variables.css`
- `--z-navbar`, `--z-modal`, etc. ✅ Ajoutés dans DS v2.0

---

## 4. CSS REDONDANTS ET INCOHÉRENTS

### Polices incohérentes
| Fichier | Police utilisée | Police correcte |
|---------|----------------|----------------|
| `global.css` | `'Arial', sans-serif` | `Inter` |
| `layout/header.php` | `'Segoe UI', Tahoma, Geneva, Verdana` | `Inter` |
| `client/mes_rendezvous.php` | hérite de Bootstrap (pas défini) | `Inter` |
| `filter/annuaire_coiffeurs.php` | hérite de Bootstrap (pas défini) | `Inter` |
| `coiffeurs/profil_public.php` | `'Inter'` ✅ | — |
| `views/client/dashboard.php` | `'Inter'` ✅ | — |

### Noms de classes CSS incohérents
| Usage | Classes utilisées (variantes) | Classe unifiée |
|-------|------------------------------|----------------|
| Bouton gold | `.btn-gold`, `.btn-gold-cct`, `.btn-luxury-gold`, `.btn-warning` | `.btn-gold` |
| Glass card | `.glass-cct`, `.glass-card`, `.glass-card-annuaire`, `.content-wrapper`, `.auth-card` | `.glass`, `.glass-md`, `.glass-lg` |
| Form control dark | `.fc-dark`, `.custom-input`, `[style inline]`, Bootstrap `.form-control bg-dark` | `.fc-dark` |
| Message succès | `.msg-success`, `.alert-success`, `alert alert-success` | `.msg-success` |
| Message erreur | `.msg-danger`, `.alert-msg-danger`, `alert alert-danger`, `[style inline]` | `.msg-danger` |
| Table dark | `.table-dark`, `.table-dark table-striped`, `[style inline]` | `.table-dark-cct` |

### Background du body incohérent
| Fichier | Background | Correct |
|---------|-----------|---------|
| `css/global.css` | `var(--black)` = `#000` | Devrait être `#0A0A0A` |
| `css/style.css` | `#000` | Devrait être `#0A0A0A` |
| `header_coiffeur.php` | `var(--dark)` = `#0A0A0A` ✅ | — |
| `dashboard.php` | `var(--dark)` = `#0A0A0A` ✅ | — |
| `connexion.php` | Fond d'image uniquement (correct pour page auth) | — |

---

## 5. ANIMATIONS DUPLIQUÉES

| Animation | Définitions | Fichiers |
|-----------|-------------|---------|
| `fadeSlideIn` / `fadeIn` | 3 variantes légèrement différentes | `header_coiffeur.php`, `profil_public.php`, `dashboard.php` |
| `shimmer` (skeleton) | 1 définition | `dashboard.php` uniquement → à centraliser |
| `pulseBell` | 2 variantes | `header.php` (`animate-pulse-bell`) et `header_coiffeur.php` (non présent) |
| `emerge` | 1 définition | `global.css` uniquement → à centraliser |

### Détail des variantes `fadeIn`
```css
/* Variante 1 — header_coiffeur.php */
@keyframes fadeSlideIn { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }

/* Variante 2 — profil_public.php */
@keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

/* Variante 3 — dashboard.php (skeleton shimmer séparé) */
```
→ Uniformiser en une seule : `fadeSlideIn` avec `translateY(12px)`, `0.3s ease`

---

## 6. COMPOSANTS À FUSIONNER

### Fusion 1 : Les boutons gold
`btn-gold` + `btn-gold-cct` + `btn-luxury-gold` → **1 seule classe `.btn-gold`**  
Différence de border-radius à harmoniser selon le contexte (pill pour hero, `10px` pour formulaires).

### Fusion 2 : Les glass cards
`.glass-card` (auth) + `.glass-cct` (coiffeur) + `.glass-card-annuaire` (annuaire) + `.content-wrapper` (global.css) → **3 niveaux : `.glass`, `.glass-md`, `.glass-lg`**

### Fusion 3 : Les navbars client
La `cct-nav` de `dashboard.php` et la navbar de `layout/header.php` (sidebar + Bootstrap navbar) → **1 seul composant `navbar_client.php`**  
Le pattern sidebar-accordéon de `header.php` est legacy et sera remplacé par la bottom nav mobile.

### Fusion 4 : Les footers
`layout/footer.php` (structure vieille) + footer inline dans `dashboard.php` → **1 seul composant `footer_global.php`**

### Fusion 5 : Les form controls dark
`.fc-dark`, `.custom-input`, classes Bootstrap surchargées inline → **1 seule classe `.fc-dark`** centralisée

### Fusion 6 : Les cards de prestation
`luxury-profile-card` (`profil_public.php`) + `card` de `gestion_catalogue.php` (Bootstrap restyled) → **1 seul composant `GalleryCard`** avec variantes "view" (profil public) et "edit" (gestion)

### Fusion 7 : Les messages d'alerte
`alert-msg-danger` (inscription) + `msg-danger` (header_coiffeur) + `alert alert-danger` (Bootstrap) → **`.msg-danger`** uniquement, supprimer les autres

---

## 7. CSS INLINE À SUPPRIMER

### Volume de style inline
Problème majeur : **de nombreux styles sont écrits directement en `style=""`** au lieu d'utiliser des classes.

#### Exemple dans `portefeuille.php`
```html
<!-- 47 attributs style="" sur des div, span, form, button -->
<div style="background:rgba(220,53,69,0.12);border:1px solid rgba(220,53,69,0.3);border-radius:14px;padding:20px 24px;margin-bottom:1.5rem;">
```
→ Ce pattern se retrouve dans `portefeuille.php`, `profil_public.php`, `annuaire_coiffeurs.php`, `mes_rendezvous.php`

#### Fichiers les plus affectés par le style inline
| Fichier | Niveau de style inline |
|---------|----------------------|
| `coiffeurs/portefeuille.php` | 🔴 Très élevé |
| `coiffeurs/profil_public.php` | 🔴 Élevé |
| `filter/annuaire_coiffeurs.php` | 🟡 Moyen |
| `client/mes_rendezvous.php` | 🟡 Moyen |
| `coiffeurs/gestion_catalogue.php` | 🟡 Moyen |
| `layout/header.php` | 🟡 Moyen |
| `coiffeurs/agenda_coiffeurs.php` | 🟢 Faible |
| `views/client/dashboard.php` | 🟢 Faible |
| `access/connexion.php` | 🟢 Très faible |
| `access/inscription.php` | 🟢 Très faible |

---

## 8. FICHIERS CSS — ÉTAT ACTUEL ET CIBLE

| Fichier | Rôle actuel | Action |
|---------|-------------|--------|
| `css/style.css` | Hero slider home page | ✅ Garder, minimal |
| `css/global.css` | Variables + auth card + emerge animation | 🔴 Supprimer en Phase 6 (remplacé par les 3 fichiers ci-dessous) |
| `css/responsive-custom.css` | Ajustements responsive (très minimal) | 🔴 Supprimer en Phase 6 (fusionner dans `components.css`) |
| `css/variables.css` | Tokens officiels DS §2 | 🔲 À créer en Phase 0 |
| `css/animations.css` | Animations officielles DS §8 | 🔲 À créer en Phase 0 |
| `css/components.css` | Classes utilitaires communes | 🔲 À créer en Phase 0 |

---

## 9. BIBLIOTHÈQUES EXTERNES

### Actuellement utilisées
| Bibliothèque | Version | Usage | Chargement |
|-------------|---------|-------|-----------|
| Bootstrap CSS | 5.3.3 | Grille + composants restyled | CDN, dans chaque page |
| Bootstrap JS | 5.3.3 | Accordéon, modales, dropdowns | CDN, dans footer |
| Bootstrap Icons | 1.11.3 | Toutes les icônes | CDN, dans chaque page |
| Google Fonts | Playfair Display + Inter | Typographie | CDN, dans header_coiffeur.php + dashboard.php |

### Problème détecté
- Google Fonts est chargé uniquement dans `header_coiffeur.php` et `dashboard.php`
- Les autres pages (`connexion.php`, `inscription.php`, `annuaire_coiffeurs.php`, `header.php`) n'ont pas les polices → fallback sur Segoe UI/Arial
- **Solution :** Ajouter le lien Google Fonts dans tous les headers

---

## 10. RÉSUMÉ EXÉCUTIF

### Points forts du projet
1. `views/client/dashboard.php` — design très abouti, proche du Design System final
2. `layout/header_coiffeur.php` — le plus avancé des layouts partagés
3. `access/connexion.php` et `access/inscription.php` — glassmorphisme bien réalisé
4. La charte couleur gold/dark est globalement cohérente

### Points à corriger en priorité (restants — non encore corrigés dans le code)
1. **Créer les 3 fichiers CSS fondations** → `css/variables.css`, `css/animations.css`, `css/components.css`
2. **Remplacer `.btn-gold-cct` → `.btn-gold`** dans 4 fichiers PHP (Phase 6)
3. **Remplacer `.luxury-profile-card` → `.gallery-card`** dans 2 fichiers PHP (Phase 6)
4. **Unifier les polices** → remplacer Segoe UI / Arial / Verdana par `var(--font-body)` partout
5. **Extraire les composants partagés** → `views/components/`
6. **Refactoriser `layout/header.php`** → supprimer la sidebar legacy
7. **Réduire le style inline** → remplacer ~150 attributs `style=""` par des classes
8. **Centraliser toutes les animations** → supprimer tous les `@keyframes` dans les pages PHP

### Nombre estimé de blocs CSS redondants à supprimer
- Blocs `<style>` en fin de page PHP : **~12 blocs** (un par fichier)
- Définitions `:root` dupliquées : **~8 occurrences**
- Classes identiques sous différents noms : **~7 groupes** à fusionner
- Attributs `style=""` inline : **~150+ attributs** à remplacer par des classes
