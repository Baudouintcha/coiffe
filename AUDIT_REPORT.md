# RAPPORT D'AUDIT UI — Coiffe Chez Toi
> Analyse complète du projet existant : composants, CSS, doublons, incohérences.
> Version : 1.0 — Juillet 2026

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
Ces variables n'existent nulle part mais sont utilisées sous forme de valeurs codées en dur :
- `--dark-3: #161616` — utilisé dans `dashboard.php` sans variable
- `--dark-4: #1A1A1A` — utilisé partout sans variable
- `--radius-*` — aucun border-radius n'est variabilisé
- `--shadow-*` — aucune ombre n'est variabilisée
- `--font-display` / `--font-body` — non définis, polices codées en dur
- `--transition-*` — non définis, transitions codées en dur

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

## 8. FICHIERS CSS — ÉTAT ACTUEL

| Fichier | Rôle actuel | Action |
|---------|-------------|--------|
| `css/style.css` | Hero slider home page | ✅ Garder, minimal |
| `css/global.css` | Variables + auth card + emerge animation | 🟡 Migrer vers `variables.css` + `components.css` |
| `css/responsive-custom.css` | Ajustements responsive (très minimal) | 🟡 Fusionner dans `components.css` |
| `css/variables.css` | — | 🔲 À créer |
| `css/components.css` | — | 🔲 À créer |

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

### Points à corriger en priorité
1. **Centraliser les variables CSS** → `css/variables.css` (aucune variable n'est partagée)
2. **Supprimer les 4 doublons critiques** (`.glass-cct`, `.btn-gold-cct`, `.fc-dark`, `.msg-*`)
3. **Unifier les polices** → remplacer Segoe UI / Arial / Verdana par Inter partout
4. **Extraire les composants partagés** → `views/components/`
5. **Refactoriser `layout/header.php`** → supprimer la sidebar legacy
6. **Réduire le style inline** → classes utilitaires
7. **Standardiser les noms de classes** → 1 nom par composant

### Nombre estimé de blocs CSS redondants à supprimer
- Blocs `<style>` en fin de page PHP : **~12 blocs** (un par fichier)
- Définitions `:root` dupliquées : **~8 occurrences**
- Classes identiques sous différents noms : **~7 groupes** à fusionner
- Attributs `style=""` inline : **~150+ attributs** à remplacer par des classes
