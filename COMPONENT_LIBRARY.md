# COMPONENT LIBRARY — Coiffe Chez Toi
> Référence officielle de tous les composants réutilisables du projet.
> Version : 2.0 — Juillet 2026 | Statut : **VALIDÉ — Référence officielle**

---

## CONVENTIONS

- Chaque composant est un bloc PHP/HTML autonome
- Les composants partagés sont placés dans `views/components/`
- Les variables CSS de `DESIGN_SYSTEM.md` s'appliquent à tous les composants
- Les composants utilisent Bootstrap 5.3.3 pour la grille uniquement
- Toute logique PHP (données dynamiques) reste dans la page appelante
- **Règle d'or :** avant tout nouveau composant, vérifier si un équivalent existe déjà (voir DS §25)

---

## 1. HERO PREMIUM

**Nom :** `HeroPremium`  
**Rôle :** Bannière plein écran pour la page d'accueil (visiteurs non connectés)  
**Utilisé dans :** `views/home.php`

### Variantes
- `HeroPremium` — plein écran avec slider d'images
- `HeroCapsule` — hero "capsule flottante" pour dashboard client
- `HeroCompact` — hero 35vh avec filtre flou (annuaire)

### Anatomie
```
[Slider images en fond]
[Overlay rgba(0,0,0,0.50)]
[Contenu centré]
  └─ [Titre H1 Playfair uppercase]
  └─ [Sous-titre Inter light]
  └─ [Bloc de sélection de rôle]
       └─ [BTN "Je suis Client"]
       └─ [BTN "Je suis Coiffeur"]
  └─ [Lien connexion]
```

### Responsive
- Desktop : plein écran, contenu centré
- Mobile : même layout, taille du titre réduite via `clamp()`

### Comportement
- Changement de slide automatique toutes les 4s (JS)
- Transition : `opacity 1s ease-in-out`

### Animations
- Entrée contenu : `fadeSlideIn` au chargement

---

## 2. HERO CAPSULE (Dashboard Client)

**Nom :** `HeroCapsule`  
**Rôle :** Section hero de la page d'accueil client connecté  
**Utilisé dans :** `views/client/dashboard.php`

### Anatomie
```
[Wrapper hero-wrapper: padding top navbar]
  └─ [Capsule .hero: border-radius 52px, max-width 1400px]
       └─ [Carousel images confiné]
       └─ [Overlay gradient bas-vers-haut]
       └─ [Profile glass card — top right]
            └─ [Avatar 44px]
            └─ [Salutation + nom]
            └─ [Stat RDV]
       └─ [Contenu centré bas]
            └─ [Titre H1]
            └─ [Sous-titre]
            └─ [SearchBar capsule]
```

### Responsive
- Desktop : `height: 420px`, `border-radius: 52px`
- MD (768px) : profile card masquée, `height: 340px`, `border-radius: 28px`
- SM (480px) : `height: 300px`, `border-radius: 20px`, search bar en colonne

### Comportement
- Carousel automatique 4s
- Search bar : redirige vers `filter/annuaire_coiffeurs.php?q=`

---

## 3. FLOATING SECTION

**Nom :** `FloatingSection`  
**Rôle :** Conteneur glassmorphisme pour regrouper du contenu dans une section  
**Utilisé dans :** Annuaire (glass-card-annuaire), Agenda, pages coiffeur

### Anatomie
```
[.glass-cct]
  └─ [Header section : titre + sous-titre + action secondaire]
  └─ [Contenu variable]
  └─ [Footer section : optionnel]
```

### Styles
```css
.glass-cct {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 16px;
}
.glass-cct:hover { border-color: rgba(212,175,55,0.25); }
```

### Variantes
- `.glass-cct` — standard
- `.glass-cct` avec `border-color: rgba(212,175,55,0.25)` — highlighted
- `.glass-cct.danger` — avec `border-color: rgba(220,53,69,0.3)`

---

## 4. FLOATING CARD (Profile Glass Card)

**Nom :** `FloatingCard`  
**Rôle :** Carte glassmorphisme flottante superposée sur un fond image  
**Utilisé dans :** `HeroCapsule` (profil client), `profil_public.php`

### Anatomie
```
[.profile-glass-card: position absolute, top-right]
  └─ [Avatar 44px avec bordure gold]
  └─ [Info block]
       └─ [Greeting "Bonjour 👋"]
       └─ [Nom utilisateur]
       └─ [Stat secondaire]
```

### Style
```css
.profile-glass-card {
  background: rgba(255,255,255,0.08);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.14);
  border-radius: 16px;
  padding: 14px 18px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.40);
  min-width: 220px;
}
```

### Responsive
- Masquée sur `< 768px`

---

## 5. SERVICE CARD (Carte Coiffeur / Prestation)

**Nom :** `ServiceCard`  
**Rôle :** Afficher un coiffeur ou une prestation dans une grille  
**Utilisé dans :** `dashboard.php`, `annuaire_coiffeurs.php`, `profil_public.php`

### Anatomie
```
[.coiffeur-card: lien cliquable]
  └─ [.card-cover: 180px]
       └─ [Image ou placeholder]
       └─ [.verified-badge: position absolute top-right]
       └─ [.card-avatar-wrap: position absolute bottom -20px]
  └─ [.card-body]
       └─ [Nom]
       └─ [Rating + localisation]
       └─ [Tags de style]
       └─ [Footer: prix + bouton CTA]
```

### Variantes
- Avec photo de cover et avatar
- Sans photo (placeholder icône)
- Avec badge "Certifié" (vert) ou sans

### Responsive
- `col-12 col-sm-6 col-lg-4`
- Hover levitation : `translateY(-6px)` + border gold

### Animations
- Hover : `transition: transform 0.3s cubic-bezier(0.25,0.8,0.25,1), border-color 0.3s`
- Apparition : `fadeSlideIn` au chargement de la grille

---

## 6. REVIEW CARD (Carte d'avis)

**Nom :** `ReviewCard`  
**Rôle :** Afficher un avis client sur un coiffeur  
**Utilisé dans :** `profil_public.php` (à venir), `client/laisser_avis.php`

### Anatomie
```
[.review-card: glass-cct]
  └─ [Header]
       └─ [Avatar client 36px]
       └─ [Nom client]
       └─ [Date]
  └─ [Stars rating (★★★★☆)]
  └─ [Texte de l'avis]
```

### Style des étoiles
```css
.stars { color: var(--gold); font-size: 0.9rem; letter-spacing: 2px; }
.stars-empty { color: rgba(212,175,55,0.30); }
```

---

## 7. GALLERY CARD (Carte Galerie Prestation)

**Nom :** `GalleryCard`  
**Classe CSS officielle :** `.gallery-card`  
**Classe dépréciée :** ~~`.luxury-profile-card`~~ — **interdite, ne plus utiliser**  
**Rôle :** Afficher une photo de prestation avec son nom et son prix  
**Utilisé dans :** `profil_public.php`, `gestion_catalogue.php`

### Anatomie
```
[.gallery-card]
  └─ [.gallery-card-img: 240px]
       └─ [img ou placeholder .gallery-card-img-placeholder]
  └─ [.card-body centré]
       └─ [Nom du style — font-weight:700]
       └─ [Prix en FCFA — couleur var(--gold)]
       └─ [CTA: "CHOISIR CE STYLE" — .btn-outline-gold]
```

### Variantes
- **View** (`profil_public.php`) : lecture seule, CTA vers `reserver.php`
- **Edit** (`gestion_catalogue.php`) : actions Modifier/Supprimer

### Style
```css
/* Voir DS §13 — .gallery-card */
```

### Responsive
- `col-sm-6 col-md-4`
- Hover : `translateY(-4px)` + border gold

---

## 8. AVAILABILITY CARD (Sélecteur de jour)

**Nom :** `AvailabilityCard`  
**Rôle :** Afficher un jour de disponibilité et permettre la sélection d'un créneau  
**Utilisé dans :** `profil_public.php`

### Anatomie
```
[.day-selector-card]
  └─ [Abréviation du jour (3 lettres)]
  └─ [Numéro du jour]
  └─ [Mois abrégé]
```

### États
| État | Style |
|------|-------|
| Disponible | `border-color: rgba(25,135,84,0.30)` |
| Indisponible | `border-color: rgba(220,53,69,0.40)`, `opacity: 0.50`, `cursor: not-allowed` |
| Sélectionné | `background: var(--select-active)`, `color: #000`, `transform: scale(1.05)` |

### Comportement
- Clic sur un jour disponible → affiche les créneaux horaires
- Clic sur un jour indisponible → alert + masquage des créneaux
- Créneaux occupés : `btn-secondary opacity-25 disabled`
- Créneaux libres : lien vers `reserver.php` avec params date+heure

---

## 9. STATISTIC CARD

**Nom :** `StatisticCard`  
**Rôle :** Afficher un KPI (gains, commissions, solde) dans un bloc compact  
**Utilisé dans :** `portefeuille.php`, dashboards

### Anatomie
```
[.glass-cct.p-3.text-center]
  └─ [Icône bootstrap centrée (1.3rem)]
  └─ [Label uppercase 0.65rem muted]
  └─ [Valeur 1rem bold + unité 0.65rem]
```

### Variantes
- Gold (default) : icône `var(--gold)`
- Danger : icône `var(--danger-icon)` = `#FF6B6B`
- Success : icône `var(--success-text)` = `#6EE7B7`

---

## 10. SEARCH CAPSULE

**Nom :** `SearchCapsule`  
**Rôle :** Barre de recherche glassmorphisme encapsulée dans le hero  
**Utilisé dans :** `HeroCapsule` (dashboard), `annuaire_coiffeurs.php`

### Anatomie
```
[.search-bar: border-radius 50px, glass niveau 3]
  └─ [Icône search gauche]
  └─ [Input texte libre]
  └─ [Bouton .search-btn gold pill]
```

### Responsive
- Desktop : flex horizontal avec bouton à droite
- SM (480px) : flex-direction column, bouton plein largeur, `border-radius: 16px`

---

## 11. AI BUBBLE (Bulle IA)

**Nom :** `AIBubble`  
**Nom déprécié :** ~~`FloatingButton`~~ — remplacé par `AIBubble` pour éviter toute confusion avec un bouton d'action overlay  
**Rôle :** Bouton d'accès rapide à l'assistant IA, toujours visible  
**Utilisé dans :** `layout/footer.php` (toutes les pages client)

### Anatomie
```
[#ai-bubble: position fixed bottom-right]
  └─ [Avatar emoji contextuel (🤖 / 👨‍💼 / 👩‍💼)]
```

### Style
```css
#ai-bubble {
  position: fixed; bottom: 30px; right: 30px;
  width: 75px; height: 75px; border-radius: 50%;
  background: #1a1a1a;
  border: 2px solid var(--gold);
  box-shadow: 0 0 20px rgba(212,175,55,0.60);
}
#ai-bubble:hover { transform: scale(1.1) rotate(5deg); box-shadow: 0 0 30px rgba(212,175,55,0.80); }
```

### Responsive
- Mobile (< 576px) : `60×60px`, `bottom:20px; right:20px`

---

## 12. PRIMARY BUTTON

**Nom :** `PrimaryButton`  
**Rôle :** Action principale sur toute la plateforme  
**Utilisé dans :** Partout

```html
<button class="btn-gold [btn-sm|btn-lg]">Libellé</button>
<!-- ou -->
<a href="..." class="btn-gold [btn-sm|btn-lg]">Libellé</a>
```

Tailles : `btn-sm` (6×14px), default (10×22px), `btn-lg` (12×30px)

---

## 13. SECONDARY BUTTON

**Nom :** `SecondaryButton`  
**Rôle :** Action secondaire, confirmation légère  
```html
<button class="btn-outline-gold">Libellé</button>
```

---

## 14. GHOST BUTTON

**Nom :** `GhostButton`  
**Rôle :** Action tertiaire, navigation, retour  
```html
<button class="btn-ghost">← Retour</button>
```

---

## 15. DANGER BUTTON

**Nom :** `DangerButton`  
**Rôle :** Suppression, annulation, actions destructrices  
```html
<button class="btn-danger-cct">Supprimer</button>
```
Toujours accompagné d'une confirmation (modal ou `confirm()`)

---

## 16. BADGE

**Nom :** `Badge`  
**Rôle :** Étiquettes de statut, catégories, tags  
**Utilisé dans :** Partout

### Variantes
| Classe | Couleur | Usage |
|--------|---------|-------|
| `.badge-verified` | Vert | Coiffeur certifié |
| `.badge-gold` | Gold | Catégorie, style |
| `.badge-pending` | Amber | RDV en attente |
| `.badge-confirmed` | Bleu | RDV confirmé |
| `.badge-done` | Vert | RDV terminé |
| `.badge-cancelled` | Gris | RDV annulé |

---

## 17. POPUP / DROPDOWN NOTIFICATIONS

**Nom :** `NotificationDropdown`  
**Rôle :** Afficher les alertes et notifications de l'utilisateur  
**Utilisé dans :** `layout/header.php` (cloche), `layout/header_coiffeur.php`

### Anatomie
```
[.dropdown-menu: 280px, background #0c0c0c, glass]
  └─ [Header: "Alertes (N)" + lien "Tout lire"]
  └─ [Liste notifications]
       └─ [Item: fond contextuel + texte + date]
  └─ [État vide: "Rien à signaler"]
```

### Types de notifications
- `danger` : `background: #1a0505; border: 1px solid #4a1414; color: #ff6b6b bold`
- `info` : `background: #0b131f; color: text-info`
- `standard` : fond transparent

### Comportement
- Point rouge animé (pulse) si notifications non lues
- "Tout lire" : reset via `?action_notif=marquer_lu`

---

## 18. MODAL

**Nom :** `Modal`  
**Rôle :** Fenêtre superposée pour confirmation, historique, formulaire secondaire  
**Utilisé dans :** `mes_rendezvous.php`, `portefeuille.php`, `gestion_catalogue.php`

### Variantes
- Modal Bootstrap 5 restyled dark
- Modal custom vanilla (portefeuille historique)

### Règles
- Toujours `backdrop-filter: blur(8px)` sur l'overlay
- Fond de la box : `#111`, bordure `rgba(212,175,55,0.20)`
- `border-radius: 20px`
- Header avec titre gold et `&times;` de fermeture
- Fermeture : clic overlay + bouton close

### Scrollbar dans les modales
```css
.modal-box::-webkit-scrollbar { width: 4px; }
.modal-box::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 10px; }
```

---

## 19. TOAST

**Nom :** `Toast`  
**Rôle :** Message flash temporaire (succès, erreur, avertissement)  
**Utilisé dans :** Toutes les pages avec actions POST

### Règles
- `position: fixed; bottom: 80px; right: 20px` (au-dessus de la bottom nav)
- Disparaît après 4 secondes
- Utilise les classes `.msg-success`, `.msg-danger`, `.msg-warning`

---

## 20. BOTTOM NAVIGATION

**Nom :** `BottomNavigation`  
**Rôle :** Navigation principale sur mobile  
**Utilisé dans :** `layout/header_coiffeur.php` (coiffeur), `views/components/bottom_nav_client.php` (client)

### Anatomie
```
[.cbn-items: flex space-around]
  └─ [.cbn-item: icône + label 0.58rem]
       States: active (gold), inactive (rgba 30%), hover (gold)
```

### Items coiffeur
- Dashboard (`bi-grid-1x2`), Catalogue (`bi-scissors`), Agenda (`bi-calendar3`), RDV (`bi-check2-circle`), Quitter (`bi-box-arrow-right`)

### Items client
- Accueil (`bi-house`), Rechercher (`bi-search`), Mes RDV (`bi-calendar3`), Profil (`bi-person-circle`)
- Liens : `/coiffons/index.php`, `/coiffons/filter/annuaire_coiffeurs.php`, `/coiffons/client/mes_rendezvous.php`, `/coiffons/profil.php`

### Responsive
- `display: none` sur `>= 768px`
- `display: block` sur `< 768px`
- `z-index: var(--z-bottomnav)` = 200

---

## 21. TOP NAVIGATION (Navbar Client)

**Nom :** `TopNavigation`  
**Rôle :** Barre de navigation principale pour les pages client  
**Utilisé dans :** `views/client/dashboard.php`, à extraire en composant partagé

### Anatomie
```
[.cct-nav: fixed top, height 64px, blur glass]
  └─ [Brand "Coiffe Chez Toi" gold Playfair]
  └─ [Actions droite]
       └─ [Icône search]
       └─ [Icône RDV]
       └─ [Avatar / placeholder initial]
```

### Note
Actuellement dupliqué dans `dashboard.php` et `header.php`. Doit être extrait en `views/components/navbar_client.php`.

---

## 22. FOOTER

**Nom :** `Footer`  
**Rôle :** Pied de page avec infos, liens et bulle IA  
**Utilisé dans :** `layout/footer.php`

### Anatomie
```
[footer.cct-footer]
  └─ [Col Brand + description]
  └─ [Col Navigation]
  └─ [Col Légal]
  └─ [Col Contact + social]
  └─ [HR + copyright]
[#ai-bubble: FloatingButton IA]
[#chat-window: Fenêtre de chat IA]
```

### Note
La bulle IA et la fenêtre de chat sont liées au footer mais doivent être extraits en composant `IAAssistant`.

---

## 23. HEADER (Coiffeur)

**Nom :** `HeaderCoiffeur`  
**Rôle :** Topbar sticky pour toutes les pages de l'espace coiffeur  
**Fichier :** `layout/header_coiffeur.php`

### Inclut
- Variables CSS globales `:root`
- Polices Google (Playfair Display + Inter)
- Bootstrap 5.3.3 + Bootstrap Icons
- Topbar avec brand, notifications, avatar
- Bottom nav mobile
- Classes utilitaires : `.glass` (alias `.glass-cct`), `.fc-dark`, `.btn-gold`, `.msg-success`, `.msg-danger`

> **Note :** `.btn-gold-cct` n'est plus dans la liste des classes incluses. La classe canonique est `.btn-gold` (voir DS §12).

---

## 24. PAGINATION

**Nom :** `Pagination`  
**Rôle :** Navigation entre les pages de résultats  
**Utilisé dans :** Annuaire (à venir), historique transactions

```css
.pagination-cct .page-item .page-link {
  background: var(--dark-2);
  border: 1px solid rgba(255,255,255,0.08);
  color: rgba(255,255,255,0.60);
  border-radius: var(--radius-sm);
}
.pagination-cct .page-item.active .page-link {
  background: var(--gold);
  color: #000;
  border-color: var(--gold);
}
.pagination-cct .page-item .page-link:hover {
  background: var(--gold-dim);
  color: var(--gold);
}
```

---

## 25. AVATAR

**Nom :** `Avatar`  
**Rôle :** Afficher la photo de profil ou un placeholder initiale  
**Utilisé dans :** Navbar, cartes, profils

### Variantes
| Taille | Dimensions | Context |
|--------|-----------|---------|
| XS | 32–36px | Navbar icons |
| SM | 44px | Cartes coiffeur, profile card hero |
| MD | 64px | En-têtes profil |
| LG | 80–100px | Page profil public, annuaire |

### Règle
Si la photo de profil n'existe pas → afficher le placeholder initial :
```html
<div class="avatar-placeholder">[INITIALE]</div>
```
```css
.avatar-placeholder { background:var(--gold-dim); border:2px solid var(--gold); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--gold); }
```

---

## 26. TIMELINE (Historique transactions)

**Nom :** `Timeline`  
**Rôle :** Afficher les transactions / événements dans l'ordre chronologique  
**Utilisé dans :** `portefeuille.php`

### Anatomie
```
[.timeline-item: flex align-center gap-12px]
  └─ [Icône cercle 32px: vert (gain) ou rouge (débit)]
  └─ [Info block: motif + date]
  └─ [Montant coloré: +vert ou -rouge]
```

---

## 27. APPOINTMENT CARD (Carte RDV)

**Nom :** `AppointmentCard`  
**Rôle :** Afficher un rendez-vous client dans la liste  
**Utilisé dans :** `mes_rendezvous.php`

### Variantes
- Desktop : tableau `.table-dark-cct` avec colonne statut + bouton gérer
- Mobile : carte individuelle `.glass-cct` avec badge statut + `.btn-outline-gold`

### États du statut
- `en_attente` → `.badge-pending`
- `confirme`/`accepte` → `.badge-confirmed`
- `termine` → `.badge-done`
- `annule` → `.badge-cancelled`

> **Note :** La variante mobile utilise `.glass-cct` (alias `.glass`), plus `.border-warning` Bootstrap qui n'est pas dans la palette. Lors de la migration, remplacer `border border-warning` par `border: 1px solid rgba(212,175,55,0.40)`.

### Comportement
- Bouton "Gérer" → ouvre Modal de détails
- Modal contient les règles d'annulation + compte-à-rebours en heures

---

## 28. PROFILE SUMMARY

**Nom :** `ProfileSummary`  
**Rôle :** Bloc résumé du profil d'un coiffeur (nom, ville, note, bio)  
**Utilisé dans :** `profil_public.php`

### Anatomie
```
[Section centré: background var(--glass-bg-subtle) = rgba(255,255,255,0.02)]
  └─ [Avatar gold circle 80px — composant Avatar LG]
  └─ [Nom uppercase H2 Playfair couleur var(--gold)]
  └─ [Ville + quartier avec icône bi-geo-alt-fill]
  └─ [Badge note/5 — .badge-gold ou .badge-muted si aucun avis]
  └─ [Bio courte max 600px — font-size 0.9rem]
```

> **Variable ajoutée :** `--glass-bg-subtle: rgba(255,255,255,0.02)` — à ajouter dans `css/variables.css`. Valeur distincte de `--glass-bg` pour les sections de fond très léger.

---

## 29. INFORMATION BLOCK (Bloc abonnement)

**Nom :** `InformationBlock`  
**Rôle :** Bloc d'information ou d'alerte contextuelle avec icône + texte + action  
**Utilisé dans :** `portefeuille.php` (abonnement actif/expiré)

### Variantes
- **Success** : fond vert, icône check, texte et dates
- **Danger** : fond rouge, icône triangle exclamation, CTA de réactivation

---

## 30. ACTION BAR

**Nom :** `ActionBar`  
**Rôle :** Barre d'actions horizontale (titre page + lien retour)  
**Utilisé dans :** Toutes les pages coiffeur

### Anatomie
```
[div.flex justify-between align-center mb-4]
  └─ [Bloc titre: H2 gold Playfair + sous-titre muted]
  └─ [Lien retour: "← Dashboard" muted]
```

### Code type
```php
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--gold);">
            <i class="bi bi-[icone] me-2"></i>[Titre Page]
        </h2>
        <p style="color:rgba(255,255,255,0.35);font-size:0.78rem;margin:0;">[Sous-titre]</p>
    </div>
    <a href="/coiffons/index.php?page=dashboard_coiffeur" style="color:rgba(255,255,255,0.35);text-decoration:none;font-size:0.8rem;display:flex;align-items:center;gap:5px;">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>
```

---

## 31. STATUS BADGE

**Nom :** `StatusBadge`  
**Rôle :** Wrapper PHP qui produit le HTML d'un Badge de statut  
**Relation :** `StatusBadge` est le helper PHP de `Badge` (CL §16). Il n'est pas un composant indépendant — il utilise les mêmes classes CSS définies dans DS §14.  
**Utilisé dans :** Tableaux, cartes, profils

```php
/* PHP helper — views/components/status_badge.php */
function render_status_badge(string $status): string {
    return match($status) {
        'en_attente'        => '<span class="badge-pending badge">En attente</span>',
        'confirme','accepte'=> '<span class="badge-confirmed badge">Confirmé</span>',
        'termine'           => '<span class="badge-done badge">Terminé</span>',
        'annule'            => '<span class="badge-cancelled badge">Annulé</span>',
        default             => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>',
    };
}
```

---

## 32. IA ASSISTANT (Bulle + Fenêtre Chat)

**Nom :** `IAAssistant`  
**Rôle :** Interface de chat avec l'assistant IA  
**Utilisé dans :** `layout/footer.php`

### Anatomie
```
[#ai-bubble: FloatingButton]
[#chat-window: position fixed bottom-right]
  └─ [Header: avatar + nom + statut en ligne + close]
  └─ [#chat-content: 350px scroll]
       └─ [Messages utilisateur: bulle gold droite]
       └─ [Messages IA: bulle dark gauche avec border warning]
       └─ [Typing indicator: blink animation]
  └─ [Footer input]
       └─ [Input texte + bouton mic]
       └─ [Label photo + bouton Envoyer gold]
```

### Comportement
- Mic : reconnaissance vocale `webkitSpeechRecognition` (fr-FR)
- Réponse IA : lecture vocale `SpeechSynthesis`
- Photo : Canvas compression base64 → envoi à `ia_controlleur.php`
- Fetch POST vers `/coiffons/ia_controlleur.php`

---

## 33. FORM FIELD

**Nom :** `FormField`  
**Classe CSS officielle :** `.fc-dark`  
**Rôle :** Champ de formulaire dark glassmorphisme — composant de base de tous les formulaires du projet  
**Utilisé dans :** `access/connexion.php`, `access/inscription.php`, `coiffeurs/gestion_catalogue.php`, `coiffeurs/agenda_coiffeurs.php`, `coiffeurs/mes_zones.php`, `client/reserver.php`, `client/creer_rendezvous.php`

### Variantes
| Variante | Tag HTML | Usage |
|---------|---------|-------|
| Text / Email / Tel | `<input type="text|email|tel">` | Saisie libre |
| Password | `<input type="password">` avec toggle | Mot de passe |
| Select | `<select>` | Listes déroulantes |
| Textarea | `<textarea>` | Descriptions, bio |
| Time | `<input type="time">` | Créneaux horaires |
| File | `<input type="file">` | Upload photos |
| Input Group | Icône + champ + toggle | Connexion, recherche |

### Style CSS
```css
.fc-dark {
  background: rgba(255,255,255,0.06) !important;
  border: 1px solid rgba(255,255,255,0.12) !important;
  color: #fff !important;
  border-radius: var(--radius-sm) !important;
  padding: 11px 14px !important;
  backdrop-filter: blur(4px);
  transition: var(--transition-fast);
}
.fc-dark:focus {
  border-color: var(--gold) !important;
  background: rgba(255,255,255,0.10) !important;
  box-shadow: 0 0 0 3px rgba(212,175,55,0.12) !important;
  color: #fff !important;
}
.fc-dark::placeholder { color: rgba(255,255,255,0.25) !important; }
.fc-dark option       { background: var(--dark-2); color: #fff; }
```

### Labels (obligatoires au-dessus de chaque champ)
```html
<label class="form-label-cct">Libellé du champ</label>
```
```css
.form-label-cct {
  display: block;
  color: rgba(255,255,255,0.65);
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  margin-bottom: 5px;
}
```

### Input Group (icône + champ + toggle)
```html
<div class="input-group-cct">
  <span class="ig-prefix"><i class="bi bi-[icone]"></i></span>
  <input type="text" class="fc-dark" placeholder="...">
  <button type="button" class="ig-suffix"><i class="bi bi-eye"></i></button>
</div>
```
```css
.input-group-cct       { display:flex; align-items:stretch; }
.ig-prefix, .ig-suffix { background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); color:rgba(255,255,255,0.50); display:flex; align-items:center; padding:0 12px; }
.ig-prefix             { border-right:none; border-radius:var(--radius-sm) 0 0 var(--radius-sm); }
.ig-suffix             { border-left:none;  border-radius:0 var(--radius-sm) var(--radius-sm) 0; cursor:pointer; }
.ig-suffix:hover       { background:rgba(212,175,55,0.15); color:var(--gold); }
.input-group-cct .fc-dark { border-radius:0 !important; border-left:none !important; border-right:none !important; }
```

### Responsive
- Tous les champs : `width: 100%`
- Sur mobile, les grilles de champs en ligne (`row g-3`) passent en colonne simple

---

## 34. AUTH CARD

**Nom :** `AuthCard`  
**Classe CSS officielle :** `.auth-card`  
**Rôle :** Conteneur glassmorphisme pour les pages d'authentification (connexion, inscription)  
**Utilisé dans :** `access/connexion.php`, `access/inscription.php`

### Variantes
| Variante | max-width | Page |
|---------|----------|------|
| Connexion | `440px` | `connexion.php` |
| Inscription | `620px` | `inscription.php` |

### Anatomie
```
[.auth-bg: fond flou fixe — image hero]
[.auth-overlay: rgba(0,0,0,0.35) fixe]
[.auth-wrapper: flex centré min-height 100vh]
  └─ [.auth-card: glass niveau 2]
       └─ [Titre H2 Playfair couleur var(--gold)]
       └─ [Sous-titre muted]
       └─ [Message flash .msg-danger / .msg-success]
       └─ [Formulaire avec .fc-dark fields]
       └─ [.btn-gold pleine largeur]
       └─ [Lien secondaire (inscription/connexion)]
```

### Style CSS
```css
/* Fond flou fixe — utilise la première image du slider */
.auth-bg {
  position: fixed; inset: 0; z-index: 0;
  background-size: cover; background-position: center;
  filter: blur(20px) brightness(0.5);
  transform: scale(1.1);
}
.auth-overlay { position:fixed; inset:0; z-index:1; background:rgba(0,0,0,0.35); }
.auth-wrapper {
  position:relative; z-index:10;
  min-height:100vh; display:flex; align-items:center; justify-content:center;
  padding:2rem 1rem; overflow-y:auto;
}
.auth-card {
  background: rgba(255,255,255,0.08);
  backdrop-filter: blur(28px);
  -webkit-backdrop-filter: blur(28px);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: var(--radius-xl);
  box-shadow: 0 10px 50px rgba(0,0,0,0.50);
  padding: 2.5rem 2rem;
  width: 100%;
  color: #fff;
}
.auth-card--sm { max-width: 440px; } /* connexion */
.auth-card--lg { max-width: 620px; } /* inscription */
```

### Responsive
- Padding réduit à `1.5rem 1.25rem` sur mobile
- Champs en colonne unique sur `< 480px`

---

## 35. SLOTS CONTAINER

**Nom :** `SlotsContainer`  
**Classe CSS officielle :** `.slots-container`  
**Rôle :** Grille de créneaux horaires générée dynamiquement après sélection d'un jour disponible  
**Utilisé dans :** `coiffeurs/profil_public.php`

### Anatomie
```
[#zone-slots-horaires.slots-container: d-none → visible au clic sur AvailabilityCard]
  └─ [Titre "Heures disponibles..." — small gold uppercase]
  └─ [#slots-container: flex wrap gap-2]
       └─ [Créneau libre — .btn-outline-gold.btn-sm]
       └─ [Créneau occupé — .btn-ghost.btn-sm disabled opacity-25]
```

### Style CSS
```css
.slots-container {
  background: var(--dark-2);
  border: 1px dashed rgba(212,175,55,0.30);
  border-radius: var(--radius-md);
  padding: 1.5rem;
  text-align: center;
}
.slots-container-title {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--gold);
  margin-bottom: 1rem;
}
```

### Comportement JS
- Généré dynamiquement par `selectionnerJour(element)` 
- Paramètres lus depuis les `data-start` et `data-end` de l'`AvailabilityCard` cliquée
- Créneaux par heure entière entre `data-start` et `data-end`
- Créneau libre → `href` vers `reserver.php?coiffeur_id=&presta_id=&date_rdv=&heure_debut=`
- Créneau occupé → désactivé (vérification via `slotsOccupes` object PHP injecté)

### Responsive
- `flex-wrap: wrap; justify-content: center; gap: 8px`
- Boutons : `padding: 6px 14px; font-size: 0.82rem`

---

## 36. DATA TABLE

**Nom :** `DataTable`  
**Classe CSS officielle :** `.table-dark-cct`  
**Rôle :** Tableau de données dark theme (planning, historique, liste RDV desktop)  
**Utilisé dans :** `coiffeurs/agenda_coiffeurs.php`, `client/mes_rendezvous.php`

### Style CSS
```css
/* Voir DS §16 — .table-dark-cct */
```

### Anatomie
```
[.table-responsive] (wrapper mobile)
  └─ [table.table-dark-cct]
       └─ [thead tr]
            └─ [th — uppercase 0.75rem muted]
       └─ [tbody tr — hover background léger]
            └─ [td — 0.85rem #fff]
```

### Responsive
- Wrapper `.table-responsive` obligatoire
- Sur mobile (`< 768px`) : masquer le tableau, afficher la variante carte de l'`AppointmentCard`

---

## 37. HERO COMPACT

**Nom :** `HeroCompact`  
**Rôle :** Section hero compacte (35vh) avec fond flou et formulaire de recherche/filtres  
**Utilisé dans :** `filter/annuaire_coiffeurs.php`

### Anatomie
```
[.annuaire-hero: min-height 35vh, position relative]
  └─ [::before pseudo-element: image fond, blur(4px) brightness(0.18)]
  └─ [.container]
       └─ [.glass-md (HeroCard): border-radius 20px, glass niveau 2]
            └─ [Titre H1 Playfair clamp(1.4rem, 3vw, 2rem)]
            └─ [Sous-titre muted 0.82rem]
            └─ [Formulaire de filtres — grille Bootstrap]
                 └─ [.fc-dark select × 3]
                 └─ [.fc-dark input number]
                 └─ [.btn-gold submit]
```

### Responsive
- Sur mobile : formulaire en colonne, padding réduit
- Fond `filter:blur(4px) brightness(0.18)` conservé à toutes tailles

---

## 38. INFORMATION BLOCK (étendu)

**Nom :** `InformationBlock`  
**Rôle :** Bloc d'alerte/information contextuelle large avec icône + texte + action optionnelle  
**Relation :** Variante large des messages `.msg-*` (DS §11) — partage les mêmes tokens de couleur  
**Utilisé dans :** `coiffeurs/portefeuille.php` (abonnement actif/expiré)

### Variantes
| Variante | Classe | Fond | Icône |
|---------|--------|------|-------|
| Danger | `.info-block--danger` | `rgba(220,53,69,0.12)` | `bi-exclamation-triangle-fill`, couleur `var(--danger-icon)` |
| Success | `.info-block--success` | `rgba(25,135,84,0.08)` | `bi-check-circle-fill`, couleur `var(--success-text)` |

### Style CSS
```css
.info-block {
  border-radius: 14px;
  padding: 20px 24px;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 14px;
}
.info-block--danger  { background:rgba(220,53,69,0.12); border:1px solid var(--danger-border); }
.info-block--success { background:rgba(25,135,84,0.08);  border:1px solid var(--success-border); }
.info-block-icon     { font-size:1.4rem; flex-shrink:0; margin-top:2px; }
.info-block-title    { font-weight:700; margin-bottom:4px; }
.info-block-text     { color:rgba(255,255,255,0.50); font-size:0.80rem; margin-bottom:12px; }
```
