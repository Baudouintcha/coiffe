# DESIGN SYSTEM REVIEW — Coiffe Chez Toi
> Revue d'architecture par Tech Lead — Analyse croisée des 4 documents de référence.
> Date : Juillet 2026 | Statut : **RÉVISIONS REQUISES AVANT VALIDATION OFFICIELLE**

---

## MÉTHODE DE REVUE

Documents analysés en lecture croisée :
- `DESIGN_SYSTEM.md` (DS) — 24 sections
- `COMPONENT_LIBRARY.md` (CL) — 32 composants
- `MIGRATION_PLAN.md` (MP) — 6 phases
- `AUDIT_REPORT.md` (AR) — 10 sections

Critères évalués :
1. Cohérence inter-documents
2. Exhaustivité des composants
3. Respect du DS dans la CL
4. Couverture du plan de migration
5. Présence de toutes les pages
6. Réutilisabilité réelle des composants
7. Opportunités de fusion
8. Cohérence des nommages
9. Centralisation des animations
10. Provenance des styles

---

## PARTIE 1 — INCOHÉRENCES DÉTECTÉES

### INC-01 — Hauteur navbar : 64px (DS) vs 60px (CL) [SÉVÉRITÉ : HAUTE]

**DS §10** déclare `height: 64px` pour la navbar principale et définit `--navbar-height: 64px` dans les variables.
**CL §23 (HeaderCoiffeur)** déclare `height: 60px` pour la topbar coiffeur sans justification documentée.
La variable `--navbar-height: 64px` ne peut pas s'appliquer aux deux contextes.

**Correction :** Soit aligner les deux à `64px`, soit ajouter `--navbar-height-coiffeur: 60px` dans DS §2 avec une note expliquant la distinction.

---

### INC-02 — `.btn-gold` vs `.btn-gold-cct` : contradiction active [SÉVÉRITÉ : CRITIQUE]

**DS §12** établit `.btn-gold` comme classe canonique du bouton primaire.
**CL §23** liste `.btn-gold-cct` dans les classes utilitaires du HeaderCoiffeur.
**AR §6 Fusion 1** demande la suppression de `.btn-gold-cct` au profit de `.btn-gold`.

Le DS dit unifier, mais la CL maintient `.btn-gold-cct` comme classe à utiliser. Les deux documents sont en contradiction directe. En l'état, un développeur qui suit la CL §23 créera du code non conforme au DS §12.

**Correction :** Supprimer `.btn-gold-cct` de la CL §23. La remplacer par `.btn-gold`.

---

### INC-03 — Niveau 3 du glassmorphism sans classe CSS [SÉVÉRITÉ : MOYENNE]

**DS §7** définit 3 niveaux d'intensité glassmorphisme mais ne fournit des classes utilitaires que pour les niveaux 1 (`.glass`) et 2 (`.glass-md`). Le niveau 3 est documenté en prose mais n'a pas de classe associée.
**CL §10 (SearchCapsule)** référence "glass niveau 3" sans pouvoir donner de classe.

**Correction :** Ajouter `.glass-lg` dans DS §7 correspondant au niveau Fort, et l'utiliser dans CL §10.

---

### INC-04 — `.glass-cct` : statut ambigu entre DS et CL [SÉVÉRITÉ : HAUTE]

**DS §7** liste `.glass` et `.glass-md` comme classes utilitaires canoniques.
**DS §13** définit `.glass-cct` comme style de la Glass Card mais sans la lister dans §7.
**CL §3, CL §9, CL §23** utilisent systématiquement `.glass-cct`.

Résultat : le DS dit d'utiliser `.glass`, mais la CL dit d'utiliser `.glass-cct`. Les deux coexistent sans hiérarchie claire.

**Correction :** Décision architecturale requise. Option A : `.glass-cct` = alias de `.glass` (niveau 1) — le documenter dans DS §7. Option B : remplacer toutes les occurrences `.glass-cct` dans la CL par `.glass`. Recommandation : **Option A** pour préserver la compatibilité avec le code existant.

---

### INC-05 — Footer : `background:#000` codé en dur [SÉVÉRITÉ : BASSE]

**DS §24 règle 1** impose `background: var(--dark)` pour toutes les pages.
**DS §21** définit `.cct-footer { background:#000; }` avec une valeur codée en dur.

Le footer utilise intentionnellement le noir pur (`--black`) et non `--dark`. C'est justifiable mais non documenté comme exception intentionnelle.

**Correction :** Dans DS §21, remplacer `#000` par `var(--black)` et ajouter une note : *"Le footer utilise `--black` (#000) intentionnellement pour créer un contraste maximal avec le contenu de page (`--dark`)."*

---

### INC-06 — Badge : deux valeurs de padding contradictoires [SÉVÉRITÉ : BASSE]

**DS §14** déclare pour les badges :
- `.badge-verified` : `padding: 3px 8px`
- `.badge-gold` : `padding: 2px 8px`
- Note générale : `padding: 3px 10px; font-size: 0.75rem`

Trois valeurs différentes pour le même type de composant dans la même section.

**Correction :** Uniformiser à `padding: 3px 10px; font-size: 0.75rem` pour tous les badges. Documenter `.badge-verified` comme cas particulier si sa compacité est voulue.

---

### INC-07 — Animation `blink` non associée à une classe CSS [SÉVÉRITÉ : BASSE]

**DS §8** définit `@keyframes blink` sans attribuer de classe (contrairement aux 4 autres animations qui ont toutes leur classe : `.page-transition`, `.fade-in-card`, `.skeleton`, `.animate-pulse`).
**CL §32 (IAAssistant)** mentionne "blink animation" sans classe exploitable.

**Correction :** Ajouter dans DS §8 :
```css
.typing-dot { animation: blink 1.4s infinite both; }
.typing-dot:nth-child(2) { animation-delay: .2s; }
.typing-dot:nth-child(3) { animation-delay: .4s; }
```

---

### INC-08 — `ProfileSummary` : `background rgba(255,255,255,0.02)` hors système [SÉVÉRITÉ : BASSE]

**CL §28 (ProfileSummary)** décrit la section avec `background rgba(255,255,255,0.02)`.
Cette valeur n'existe dans aucune variable DS et n'est pas listée dans les niveaux de glassmorphisme.
C'est une valeur orpheline qui ne peut pas être maintenue via token.

**Correction :** Soit l'assimiler à `var(--glass-bg)` = `rgba(255,255,255,0.06)` (légèrement plus opaque mais dans le système), soit ajouter `--glass-bg-subtle: rgba(255,255,255,0.02)` dans DS §2.

---

### INC-09 — `AppointmentCard` mobile : `.border-warning` Bootstrap non conforme [SÉVÉRITÉ : BASSE]

**CL §27 (AppointmentCard)** variante mobile : `card.bg-dark.border.border-warning`.
**DS §12** définit `.btn-outline-gold` pour les bordures gold, et les cartes utilisent `rgba(212,175,55,0.4)` au hover.
`border-warning` Bootstrap correspond à `#FFC107` (amber/jaune), pas à `--gold` (#D4AF37).

**Correction :** CL §27 doit remplacer `border-warning` par une bordure `1px solid rgba(212,175,55,0.4)` ou une classe custom `.border-gold`.

---

### INC-10 — `AvailabilityCard` état sélectionné : `#ffc107` hors palette [SÉVÉRITÉ : MOYENNE]

**DS §1** ne contient pas `#ffc107` dans sa palette. Il s'agit d'une couleur Bootstrap (warning) qui n'est pas `--gold` (#D4AF37).
**CL §8 (AvailabilityCard)** état sélectionné : `background: #ffc107` codé en dur.

C'est une couleur non tokenisée qui dérive de la palette officielle.

**Correction :** Définir une variable `--select-active: #FFC107` dans DS §1 sous "Couleurs fonctionnelles" avec la note *"Utilisé exclusivement pour les états de sélection active (jour calendrier)"*, ou remplacer par `var(--gold)` si l'écart visuel est acceptable.

---

### INC-11 — `StatisticCard` : couleur danger `#ff6b6b` hors variables DS [SÉVÉRITÉ : BASSE]

**CL §9 (StatisticCard)** variante Danger utilise `#ff6b6b` pour l'icône.
**DS §1** définit `--danger-text: #FFB3B3` (rouge pastel) et non `#ff6b6b` (rouge vif).
Les deux coexistent sans règle claire sur lequel utiliser où.

**Correction :** Ajouter `--danger-icon: #FF6B6B` dans DS §2 comme couleur d'icône danger distincte du texte danger, ou unifier sur `--danger-text`.

---

## PARTIE 2 — COMPOSANTS MANQUANTS

### MISS-01 — `FormField` : composant de champ de formulaire non défini dans la CL

**DS §11** documente en détail le style des champs (`form-field`, labels, select, input group).
**CL** ne contient aucun composant `FormField` ou `InputGroup`.
Pourtant les formulaires apparaissent dans : inscription, connexion, réservation, catalogue, agenda, profil.

**Composant manquant à ajouter :** `FormField` (CL §33) avec :
- Variantes : text, email, password, select, time, file
- Style `.fc-dark` (existant dans le code, absent de la CL)
- Input group avec icône gauche + toggle droit
- Labels uppercase
- Messages d'erreur inline

---

### MISS-02 — `AuthCard` : composant de carte d'authentification non défini dans la CL

**DS §13** définit le style `.auth-card` (fond glass, max-width 440px/620px).
**CL** ne contient pas ce composant malgré son usage dans connexion.php et inscription.php.
C'est pourtant un composant réutilisable documenté dans le DS.

**Composant manquant à ajouter :** `AuthCard` (CL §34) avec :
- Variante connexion (440px)
- Variante inscription (620px)
- Fond glassmorphisme niveau 2
- Utilisation : `access/connexion.php`, `access/inscription.php`

---

### MISS-03 — `SlotsContainer` : grille de créneaux horaires sans composant

Le sélecteur de créneaux horaires dans `profil_public.php` (la zone `#zone-slots-horaires` avec les boutons d'heures) est un pattern récurrent et interactif non documenté dans la CL.

**Composant manquant à ajouter :** `SlotsContainer` (CL §35) avec :
- Wrapper `.style-slots-box` (fond `--dark-2`, bordure gold dashed)
- Boutons créneaux libres : `.btn-outline-gold`
- Boutons créneaux occupés : désactivés, opacité 0.25
- Comportement JS : génération dynamique depuis les attributs data-start/data-end

---

### MISS-04 — `DashboardHero` coiffeur absent de la CL

La CL couvre `HeroPremium` (accueil visiteur) et `HeroCapsule` (dashboard client) mais **aucun hero pour le dashboard coiffeur** n'est documenté.
Le plan de migration (MP Phase 4) liste `coiffeurs/profil_coiffeurs.php` sans préciser quel hero utiliser.

**Composant à définir :** préciser dans CL §1 (variantes HeroPremium) ou créer une section spécifique pour le dashboard coiffeur.

---

### MISS-05 — `Divider` / séparateur de section non documenté

Les sections utilisent des `<hr>`, des `border-bottom: 1px solid rgba(255,255,255,0.06)` ou `border-bottom: 1px solid rgba(212,175,55,0.2)` de manière inconsistante.
Aucune règle dans DS ni composant dans CL ne standardise les séparateurs.

**Règle à ajouter dans DS §24 :**
- Séparateur standard : `border-bottom: 1px solid rgba(255,255,255,0.06)`
- Séparateur gold (footers, sections premium) : `border-bottom: 1px solid rgba(212,175,55,0.20)`
- Ne jamais utiliser `<hr>` Bootstrap natif sans classe custom

---

### MISS-06 — Page `deconnexion.php` absente du plan de migration

**MP** liste toutes les pages PHP mais omet `deconnexion.php`.
C'est une page de redirection simple mais elle peut afficher un écran interstitiel ou un message flash.
Elle devrait apparaître dans MP Phase 5 ou comme note dans Phase 1.

---

---

## PARTIE 3 — COMPOSANTS FUSIONNABLES

### FUSE-01 — `HeroPremium`, `HeroCapsule`, `HeroCompact` : hiérarchie à clarifier

Les 3 variantes hero sont documentées dans **CL §1** (sous HeroPremium) mais `HeroCapsule` a sa propre section (CL §2) et `HeroCompact` n'a pas de section propre — il est juste mentionné comme variante de HeroPremium.

**Proposition :** Regrouper sous un seul composant `Hero` avec 3 variantes nommées explicitement :
- `Hero--premium` (plein écran, home)
- `Hero--capsule` (dashboard client)
- `Hero--compact` (annuaire)

Cela évite la fragmentation et le risque de traiter HeroCapsule comme un composant indépendant alors qu'il partage 80% du code avec HeroPremium.

---

### FUSE-02 — `TopNavigation` (CL §21) et `HeaderCoiffeur` (CL §23) : deux composants navbar

Les deux remplissent le même rôle (navbar sticky glassmorphisme) avec des différences mineures (hauteur 64 vs 60px, items différents).
**CL §21** est marqué "à extraire" — c'est un composant pas encore réalisé.
**CL §23** est le composant existant le plus avancé.

**Proposition :** Fusionner en un seul composant `NavBar` avec variantes `--client` et `--coiffeur`. Partage : fond glass, brand Playfair, icônes action circulaires. Différences : items de navigation, avatar/profil.

---

### FUSE-03 — `ProfileSummary` (CL §28) et `FloatingCard` (CL §4)

`FloatingCard` est une carte glassmorphisme flottante avec avatar + nom + stat.
`ProfileSummary` est une section avec avatar + nom + note + bio.

Les deux affichent des informations de profil avec un avatar. `FloatingCard` est une version compacte de `ProfileSummary`.

**Proposition :** Conserver les deux mais documenter explicitement que `FloatingCard` est la version "pill" de `ProfileSummary` et qu'ils partagent le composant `Avatar`. Ajouter une note de dépendance dans les deux sections.

---

### FUSE-04 — `InformationBlock` (CL §29) et les blocs `.msg-*` du DS §11

`InformationBlock` (abonnement actif/expiré dans portefeuille) et les `.msg-success`/`.msg-danger` du DS §11 remplissent la même fonction : afficher un état contextuel avec icône + texte + action optionnelle.

La différence est la taille : `.msg-*` est compact (padding 10px), `InformationBlock` est plus grand (padding 20-24px) avec un CTA intégré.

**Proposition :** Dans la CL, documenter `InformationBlock` comme une variante large des messages de feedback (`.msg-block-success`, `.msg-block-danger`), en soulignant qu'ils partagent les mêmes tokens de couleur que DS §11.

---

### FUSE-05 — `StatusBadge` (CL §31) et `Badge` (CL §16) : redondance partielle

`Badge` (CL §16) liste 6 variantes dont les 4 statuts RDV (pending, confirmed, done, cancelled).
`StatusBadge` (CL §31) réimplémente les mêmes 4 statuts en PHP avec une fonction helper.

**Proposition :** Conserver `Badge` pour la définition CSS et `StatusBadge` pour le helper PHP. Mais dans CL §31, ajouter une référence explicite à CL §16 et préciser que StatusBadge est le wrapper PHP de Badge, pas un composant indépendant.

---

## PARTIE 4 — COHÉRENCE DES NOMMAGES

### NOM-01 — Nommage mixte anglais/français dans la CL

La CL nomme ses composants en anglais (`HeroPremium`, `ServiceCard`, `ReviewCard`) mais les descriptions et rôles sont en français. C'est cohérent en soi.
Cependant, certains composants ont des noms hybrides ou ambigus :
- `FloatingSection` vs `FloatingCard` : le mot "Floating" est utilisé pour deux composants de nature très différente (conteneur glassmorphisme vs carte positionnée en overlay).
- `FloatingButton` (CL §11) = bulle IA fixée en bas à droite — ce n'est pas vraiment "floating" au sens CSS du terme, c'est un bouton `position:fixed`.

**Recommandation :** Renommer :
- `FloatingSection` → `GlassSection` (reflète mieux le glassmorphisme)
- `FloatingButton` → `AIBubble` ou `FixedActionButton` (reflète `position:fixed`)

---

### NOM-02 — `GalleryCard` : classe CSS `luxury-profile-card` non conforme au nom

**CL §7** nomme le composant `GalleryCard` mais sa classe CSS est `.luxury-profile-card`.
Le Design System ne définit nulle part `.luxury-profile-card` — ce n'est pas un token officiel.

**Correction :** Renommer la classe CSS en `.gallery-card` dans DS §13 (à ajouter) et dans CL §7. Mettre à jour `profil_public.php` et `gestion_catalogue.php` lors de la migration.

---

### NOM-03 — `HeroCompact` non documenté comme section propre dans la CL

`HeroCompact` est mentionné dans CL §1 comme variante mais n'a pas de section dédiée. Le composant existe (`filter/annuaire_coiffeurs.php`) et a ses propres règles (min 35vh, background flou, formulaire en bas).

**Correction :** Ajouter une section `CL §1b — Hero Compact` ou enrichir CL §1 avec une sous-section détaillée pour cette variante.

---

### NOM-04 — `TableDark` référencé dans MP mais absent de la CL

**MP Phase 4** liste `TableDark` comme composant à utiliser pour `agenda_coiffeurs.php`.
**DS §16** définit `.table-dark-cct` avec son style complet.
**CL** n'a pas de composant `TableDark`.

**Correction :** Ajouter `CL §36 — Table` (ou `DataTable`) qui réfère à DS §16 et liste ses usages.

---

## PARTIE 5 — ANIMATIONS : CENTRALISATION

### ANIM-01 — Animations définies dans les pages, pas dans le DS [RÉSOLU PAR LE DS]

L'AR §5 identifie 3 variantes de `fadeIn`/`fadeSlideIn` éparpillées dans les pages.
Le DS §8 les unifie correctement avec `@keyframes fadeSlideIn` et `.page-transition`.

**Statut :** Le DS résout correctement le problème. La correction s'appliquera lors de la migration.
**Point de vigilance :** DS §8 ne précise pas que les animations ne doivent être définies que dans `css/components.css` — il faut l'écrire explicitement.

**Ajout requis dans DS §8 :** *"Toutes les animations sont exclusivement définies dans `css/components.css`. Aucune animation `@keyframes` ne doit être définie dans une page PHP individuelle."*

---

### ANIM-02 — Animation du carousel hero non documentée dans DS §8

Les pages utilisent un carousel d'images avec `transition: opacity 1.5s ease` (dashboard) et `transition: opacity 1s ease-in-out` (home).
Deux durées différentes (1s et 1.5s) pour le même composant.
DS §8 ne documente pas l'animation carousel/slider.

**Correction :** Ajouter dans DS §8 :
```css
/* Carousel / Slider d'images */
.hero-slide { opacity: 0; transition: opacity 1.2s ease; }
.hero-slide.active { opacity: 1; }
```
Et unifier à `1.2s` comme compromis entre les deux valeurs existantes.

---

### ANIM-03 — Animation hover des cartes : `transition-spring` non utilisée

**DS §2** définit `--transition-spring: all 0.3s cubic-bezier(0.25,0.8,0.25,1)`.
**DS §13 (Cartes coiffeur)** spécifie `transition: transform 0.3s cubic-bezier(0.25,0.8,0.25,1), border-color 0.3s` — valeur codée en dur au lieu de `var(--transition-spring)`.

**Correction :** DS §13 doit utiliser `var(--transition-spring)` pour la cohérence.

---

## PARTIE 6 — COUVERTURE DU PLAN DE MIGRATION

### COV-01 — Pages oubliées dans le MP

| Page | Présente dans MP | Action requise |
|------|-----------------|----------------|
| `deconnexion.php` | ❌ | Ajouter en Phase 1 ou Phase 5 |
| `coiffeurs/sauvegarder_agenda.php` | ❌ | Page de traitement — ajouter en Phase 4 (note: page backend, impact visuel minimal) |
| `envoyer_notification_whatsapp.php` | ❌ | Page backend — confirmer si elle a une interface |
| `public/index.php` | ❌ | Point d'entrée public — à vérifier |
| `app.php` | ❌ | Routeur principal — pas de vue, mais à mentionner comme hors-scope |
| `routes/web.php` | ❌ | Hors-scope UI — à mentionner explicitement |

---

### COV-02 — Phase 3 : `profil_public.php` classée en phase client mais appartient au dossier `coiffeurs/`

**MP Phase 3** liste `coiffeurs/profil_public.php` comme page client.
C'est logiquement correct (le client consulte ce profil) mais le fichier est dans `/coiffeurs/`.
Cette ambiguïté peut créer de la confusion lors de l'exécution.

**Correction :** Ajouter une note explicative dans MP Phase 3 : *"profil_public.php est physiquement dans /coiffeurs/ mais fonctionnellement une page client (vue en lecture seule)."*

---

### COV-03 — Phase 0 : `views/components/bottom_nav_client.php` à créer mais non documenté dans la CL

**MP Phase 0.3** liste `bottom_nav_client.php` à créer.
La CL §20 (BottomNavigation) documente les items coiffeur mais les items client sont marqués "à implémenter" sans détail.

**Correction :** CL §20 doit lister les 4 items client : Accueil (`bi-house`), Rechercher (`bi-search`), Mes RDV (`bi-calendar3`), Profil (`bi-person-circle`). Les liens correspondants doivent être précisés.

---

### COV-04 — Phase 0.4 : validation de `home.php` non précisée

**MP Phase 0.4** dit "valider `views/home.php` à 100%". Mais aucun critère de validation n'est défini (checklist, règles, responsable).

**Correction :** Ajouter dans MP Phase 0.4 une micro-checklist de validation :
- [ ] Toutes les variables CSS du DS §2 sont présentes
- [ ] Police Playfair + Inter chargées via Google Fonts
- [ ] Bottom nav présente sur mobile
- [ ] Animation `fadeSlideIn` utilisée (pas `fadeIn`)
- [ ] Aucun style inline
- [ ] `.btn-gold` utilisé (pas `.btn-gold-cct` ni `.btn-outline-warning`)

---

---

## PARTIE 7 — OPTIMISATIONS PROPOSÉES

### OPT-01 — Créer `css/animations.css` séparé de `css/components.css`

Le fichier `components.css` prévu dans MP Phase 0.2 est amené à devenir très volumineux (boutons, cartes, badges, messages, tables, empty states, skeletons, avatars, etc.).
Il est préférable d'isoler les animations dans un fichier dédié.

**Proposition :**
```
css/
  variables.css    ← tokens DS §2
  animations.css   ← @keyframes DS §8
  components.css   ← classes utilitaires
  style.css        ← hero home uniquement (existant)
```

---

### OPT-02 — Documenter l'ordre de chargement CSS obligatoire

Aucun document ne précise l'ordre d'import des fichiers CSS. Les variables doivent impérativement être chargées avant les composants.

**Ordre canonique à documenter dans DS §2 :**
```html
<link rel="stylesheet" href="/coiffons/css/variables.css">
<link rel="stylesheet" href="/coiffons/css/animations.css">
<link rel="stylesheet" href="/coiffons/css/components.css">
<!-- Puis Bootstrap, puis styles spécifiques à la page si nécessaire -->
```

---

### OPT-03 — Ajouter des z-index tokens dans DS §2

Le projet utilise des z-index variés sans centralisation :
- Sidebar : `z-index: 3000`
- Bottom nav : `z-index: 200`
- Modal : `z-index: 500`
- AI bubble : `z-index: 9998`
- Chat window : `z-index: 9999`
- Navbar : `z-index: 100` (dashboard) / `z-index: 200` (header_coiffeur)

Cinq valeurs différentes sans ordre documenté. Un conflit de z-index est inévitable à l'échelle.

**Variables à ajouter dans DS §2 :**
```css
--z-navbar:    100;
--z-bottomnav: 200;
--z-modal:     500;
--z-sidebar:   3000;
--z-ai:        9998;
--z-ai-window: 9999;
```

---

### OPT-04 — Définir la stratégie de padding-bottom pour la bottom nav

Toutes les pages avec bottom nav fixe doivent avoir un `padding-bottom: 56px` sur leur contenu pour éviter que le contenu soit masqué.
Ni le DS ni la CL ne documentent cette règle.

**Règle à ajouter dans DS §10 (Bottom Navigation) :**
*"Toute page qui inclut la bottom navigation doit appliquer `padding-bottom: calc(var(--bottomnav-height) + 1rem)` à son wrapper de contenu principal."*

---

### OPT-05 — Préciser la stratégie mobile-first vs desktop-first

Le DS §9 liste les breakpoints mais ne précise pas si l'approche est mobile-first (min-width) ou desktop-first (max-width).
L'AR montre des usages mixtes dans le code existant.

**Recommandation à documenter dans DS §9 :**
*"Le projet adopte une approche desktop-first (max-width) pour la migration des pages existantes, pour minimiser les changements dans le code legacy. Les nouveaux composants créés en Phase 0 utilisent une approche mobile-first (min-width)."*

---

### OPT-06 — Ajouter les états `focus-visible` dans DS §11

**DS §11** documente les états `:focus` des champs mais pas `:focus-visible` (accessibilité clavier).
Pour la conformité WCAG 2.1 AA, les éléments interactifs doivent avoir un indicateur de focus visible.

**Ajout requis dans DS §11 :**
```css
.form-field:focus-visible {
  outline: 2px solid var(--gold);
  outline-offset: 2px;
}
.btn-gold:focus-visible, .btn-outline-gold:focus-visible {
  outline: 2px solid var(--gold);
  outline-offset: 2px;
}
```

---

### OPT-07 — Documenter la stratégie de fallback Google Fonts

**AR §9** signale que Google Fonts n'est pas chargé sur toutes les pages. Le DS mentionne les polices mais ne documente pas la stratégie de fallback si les polices ne chargent pas (réseau lent, hors-ligne, RGPD).

**Recommandation dans DS §3 :**
```css
--font-display: 'Playfair Display', Georgia, 'Times New Roman', serif;
--font-body:    'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
```
Les fallbacks sont déjà dans les variables mais non documentés comme intentionnels.

---

## PARTIE 8 — VÉRIFICATIONS CROISÉES RÉUSSIES

Les points suivants ont été vérifiés et sont **conformes** entre les 4 documents.

| # | Vérification | Résultat |
|---|-------------|---------|
| ✅ | Palette de couleurs cohérente DS ↔ CL | Conforme |
| ✅ | Bootstrap Icons utilisé partout (pas de FontAwesome) | Conforme |
| ✅ | Bootstrap 5.3.3 comme seul framework grille | Conforme |
| ✅ | Logique PHP hors des composants | Conforme |
| ✅ | Ordre de migration logique (fondations → layouts → pages) | Conforme |
| ✅ | Glassmorphisme niveau 1 cohérent entre DS §7 et CL §3 | Conforme |
| ✅ | `.msg-success`/`.msg-danger` identiques dans DS §11 et CL §23 | Conforme |
| ✅ | `fadeSlideIn` = animation canonique dans DS §8 et CL §5 | Conforme |
| ✅ | Grille cartes `col-12 col-sm-6 col-lg-4` dans DS §9 et CL §5 | Conforme |
| ✅ | `--gold: #D4AF37` valeur cohérente dans tous les documents | Conforme |
| ✅ | Scrollbar custom `4px gold` dans DS §15 et CL §18 | Conforme |
| ✅ | AR identifie des doublons que MP Phase 6 planifie de supprimer | Conforme |
| ✅ | CL §32 (IAAssistant) conforme à DS §24 règle 3 (toute page mobile = bottom nav) | Conforme |
| ✅ | Toutes les polices autorisées (Playfair + Inter) respectées dans la CL | Conforme |
| ✅ | Aucun composant CL n'introduit de nouvelle bibliothèque d'icônes | Conforme |

---

## PARTIE 9 — TABLEAU DE SYNTHÈSE

| ID | Type | Sévérité | Document(s) | Statut recommandé |
|----|------|----------|-------------|-------------------|
| INC-01 | Incohérence | HAUTE | DS §10, CL §23 | Corriger avant validation |
| INC-02 | Incohérence | CRITIQUE | DS §12, CL §23 | Corriger avant validation |
| INC-03 | Incohérence | MOYENNE | DS §7, CL §10 | Corriger avant validation |
| INC-04 | Incohérence | HAUTE | DS §7/§13, CL §3/§9/§23 | Décision + correction requise |
| INC-05 | Incohérence | BASSE | DS §21/§24 | Corriger (note explicative) |
| INC-06 | Incohérence | BASSE | DS §14 | Corriger |
| INC-07 | Incohérence | BASSE | DS §8, CL §32 | Corriger |
| INC-08 | Incohérence | BASSE | CL §28, DS §2 | Corriger |
| INC-09 | Incohérence | BASSE | CL §27, DS §12 | Corriger |
| INC-10 | Incohérence | MOYENNE | CL §8, DS §1 | Corriger (tokeniser `#ffc107`) |
| INC-11 | Incohérence | BASSE | CL §9, DS §1 | Corriger |
| MISS-01 | Manquant | HAUTE | CL | Ajouter `FormField` (CL §33) |
| MISS-02 | Manquant | HAUTE | CL | Ajouter `AuthCard` (CL §34) |
| MISS-03 | Manquant | MOYENNE | CL | Ajouter `SlotsContainer` (CL §35) |
| MISS-04 | Manquant | MOYENNE | CL, MP Phase 4 | Documenter hero dashboard coiffeur |
| MISS-05 | Manquant | BASSE | DS §24 | Ajouter règle `Divider` |
| MISS-06 | Manquant | BASSE | MP | Ajouter `deconnexion.php` |
| FUSE-01 | Fusion | BASSE | CL §1/§2 | Restructurer sous `Hero` variants |
| FUSE-02 | Fusion | MOYENNE | CL §21/§23 | Fusionner en `NavBar` variants |
| FUSE-03 | Fusion | BASSE | CL §4/§28 | Documenter dépendance Avatar |
| FUSE-04 | Fusion | BASSE | CL §29, DS §11 | Unifier tokens couleur |
| FUSE-05 | Fusion | BASSE | CL §16/§31 | Clarifier CL §31 = wrapper PHP de CL §16 |
| NOM-01 | Nommage | BASSE | CL §11 | Renommer FloatingButton → AIBubble |
| NOM-02 | Nommage | HAUTE | CL §7, DS | Renommer `.luxury-profile-card` → `.gallery-card` |
| NOM-03 | Nommage | BASSE | CL §1 | Ajouter section HeroCompact |
| NOM-04 | Nommage | MOYENNE | CL, MP | Ajouter `Table` dans CL |
| ANIM-01 | Animation | HAUTE | DS §8 | Ajouter règle d'exclusivité `components.css` |
| ANIM-02 | Animation | MOYENNE | DS §8 | Ajouter animation carousel unifiée |
| ANIM-03 | Animation | BASSE | DS §13 | Utiliser `var(--transition-spring)` |
| COV-01 | Couverture | MOYENNE | MP | Ajouter pages manquantes |
| COV-02 | Couverture | BASSE | MP Phase 3 | Note sur `profil_public.php` |
| COV-03 | Couverture | MOYENNE | CL §20, MP | Compléter items bottom nav client |
| COV-04 | Couverture | BASSE | MP Phase 0.4 | Ajouter checklist validation |
| OPT-01 | Optimisation | — | MP Phase 0 | Créer `css/animations.css` |
| OPT-02 | Optimisation | — | DS §2 | Documenter ordre de chargement CSS |
| OPT-03 | Optimisation | — | DS §2 | Ajouter tokens z-index |
| OPT-04 | Optimisation | — | DS §10 | Règle padding-bottom bottom nav |
| OPT-05 | Optimisation | — | DS §9 | Préciser stratégie mobile-first |
| OPT-06 | Optimisation | — | DS §11 | Ajouter états `focus-visible` |
| OPT-07 | Optimisation | — | DS §3 | Documenter fallbacks polices |

---

---

## CONCLUSION — Le système est-il prêt à devenir la référence officielle ?

### Verdict global : **NON — Pas encore. Mais très proche.**

Le Design System et la Component Library représentent un travail solide et cohérent dans l'ensemble. La palette, le glassmorphisme, la typographie, les animations et la philosophie dark/gold sont correctement articulés. Le plan de migration est logique et priorisé de manière optimale. L'audit est précis et actionnable.

Cependant, **4 points bloquants** empêchent la validation officielle aujourd'hui :

---

**BLOQUANT 1 — INC-02 (`.btn-gold` vs `.btn-gold-cct`)**
Le DS et la CL se contredisent sur le nom de la classe du bouton primaire. C'est le composant le plus utilisé du projet. Cette contradiction doit être résolue avant toute migration sinon on reproduira exactement le doublon que l'audit veut éliminer.

**BLOQUANT 2 — INC-04 (`.glass` vs `.glass-cct`)**
La même ambiguïté existe pour le composant le plus utilisé dans les pages coiffeur. Une décision architecturale (alias ou remplacement) doit être prise et documentée.

**BLOQUANT 3 — MISS-01 (FormField absent de la CL)**
Les formulaires sont présents dans presque toutes les pages du projet. L'absence d'un composant `FormField` dans la CL signifie que lors de la migration, chaque développeur recréera son propre style de champ. C'est le vecteur de prolifération des `.fc-dark` que l'audit voulait éliminer.

**BLOQUANT 4 — ANIM-01 (Règle d'exclusivité des animations non écrite)**
Sans une règle explicite interdisant la définition d'animations dans les pages individuelles, la migration ne résoudra pas le problème des 3 variantes de `fadeIn` identifié dans l'audit. La règle doit être écrite, pas juste implicite.

---

### Ce qui est prêt et peut être utilisé immédiatement

- La palette de couleurs et les variables DS §1-§2 → **prêts à l'emploi**
- La typographie DS §3 → **prête à l'emploi**
- Le glassmorphisme DS §7 → **prêt** (après résolution INC-03 et INC-04)
- Les animations DS §8 → **prêtes** (après ajout règle ANIM-01)
- Les composants CL §8 (AvailabilityCard), CL §26 (Timeline), CL §28 (ProfileSummary), CL §30 (ActionBar) → **prêts à l'emploi**
- Le plan de migration MP → **structurellement prêt** (après ajouts COV-01, COV-03)
- L'audit AR → **complet et exact**, aucune correction requise

---

### Plan d'action avant validation

Les corrections requises sont légères et peuvent être réalisées en **une demi-journée** :

1. Résoudre INC-02 : supprimer `.btn-gold-cct` de la CL §23
2. Résoudre INC-04 : décider `.glass-cct` = alias de `.glass` et le documenter dans DS §7
3. Résoudre INC-03 : ajouter `.glass-lg` dans DS §7
4. Résoudre ANIM-01 : ajouter la règle d'exclusivité dans DS §8
5. Ajouter MISS-01 : créer la section `FormField` dans la CL
6. Résoudre INC-01 : aligner les hauteurs navbar à 64px ou créer `--navbar-height-coiffeur`
7. Ajouter OPT-03 : z-index tokens dans DS §2
8. Résoudre NOM-02 : renommer `.luxury-profile-card` en `.gallery-card`

Une fois ces 8 points adressés, le système sera **prêt à devenir la référence officielle** et pourra être utilisé comme contrat de développement pour toute la Phase 1 de migration.

---

*Revue réalisée le Juillet 2026 — Version 1.0*
*Prochaine revue recommandée : après validation et avant le début de la Phase 2 (pages d'authentification)*
