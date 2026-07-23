# COMPONENT LIBRARY — Coiffe Chez Toi
> Référence officielle de tous les composants réutilisables du projet.
> Version : 1.0 — Juillet 2026

---

## CONVENTIONS

- Chaque composant est un bloc PHP/HTML autonome
- Les composants partagés sont placés dans `views/components/`
- Les variables CSS du DESIGN_SYSTEM.md s'appliquent à tous les composants
- Les composants utilisent Bootstrap 5.3.3 pour la grille uniquement
- Toute logique PHP (données dynamiques) reste dans la page appelante

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
**Rôle :** Afficher une photo de prestation avec son nom et son prix  
**Utilisé dans :** `profil_public.php`, `gestion_catalogue.php`

### Anatomie
```
[.luxury-profile-card]
  └─ [.luxury-img-container: 240px]
       └─ [img ou placeholder]
  └─ [.card-body centré]
       └─ [Nom du style]
       └─ [Prix en FCFA gold]
       └─ [CTA: "CHOISIR CE STYLE"]
```

### Variantes
- Avec image réelle
- Avec placeholder dégradé + icône étoile

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
| Sélectionné | `background: #ffc107`, `color: #000`, `transform: scale(1.05)` |

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
- Danger : icône `#ff6b6b` (commissions/charges)
- Success : icône `var(--success-text)`

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

## 11. FLOATING BUTTON (Bulle IA)

**Nom :** `FloatingButton`  
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
**Utilisé dans :** `layout/header_coiffeur.php` (coiffeur), à standardiser pour client

### Anatomie
```
[.cbn-items: flex space-around]
  └─ [.cbn-item: icône + label 0.58rem]
       States: active (gold), inactive (rgba 30%), hover (gold)
```

### Items coiffeur
- Dashboard, Catalogue, Agenda, RDV, Quitter

### Items client (à implémenter)
- Accueil, Rechercher, Mes RDV, Profil

### Responsive
- `display: none` sur `>= 768px`
- `display: block` sur `< 768px`
- Z-index : `200` (au-dessus du contenu, sous les modales)

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
- Classes utilitaires : `.glass-cct`, `.fc-dark`, `.btn-gold-cct`, `.msg-success`, `.msg-danger`

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
- Desktop : tableau dark avec colonne statut + bouton gérer
- Mobile : carte individuelle `.card.bg-dark.border.border-warning`

### États du statut
- `en_attente` → `.badge-pending`
- `confirme`/`accepte` → `.badge-confirmed`
- `termine` → `.badge-done`
- `annule` → `.badge-cancelled`

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
[Section centré: background rgba(255,255,255,0.02)]
  └─ [Avatar gold circle 80px]
  └─ [Nom uppercase H1 text-warning]
  └─ [Ville + quartier avec icône géo]
  └─ [Badge note/5]
  └─ [Bio courte max 600px]
```

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
**Rôle :** Afficher le statut d'un élément (RDV, abonnement, coiffeur)  
**Utilisé dans :** Tableaux, cartes, profils

```php
/* PHP helper à créer dans views/components/status_badge.php */
function render_status_badge(string $status): string {
    return match($status) {
        'en_attente' => '<span class="badge-pending badge px-3 py-2">En attente</span>',
        'confirme','accepte' => '<span class="badge-confirmed badge px-3 py-2">Confirmé</span>',
        'termine'  => '<span class="badge-done badge px-3 py-2">Terminé</span>',
        'annule'   => '<span class="badge-cancelled badge px-3 py-2">Annulé</span>',
        default    => '<span class="badge bg-secondary px-3 py-2">' . htmlspecialchars($status) . '</span>',
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
