# 📚 DICTIONNAIRE FONCTIONNEL — COIFFE CHEZ TOI
## Résumé Complet de Chaque Fichier & Ses Fonctionnalités

**Document Technique | Référence Développeur | Août 2026**

---

## 🔍 COMMENT UTILISER CE DOCUMENT

Ce document est un **index complet** de tous les fichiers du projet avec:
- **Chemin fichier** — où il est localisé
- **Type** — PHP/HTML/CSS/JS/SQL
- **Responsabilité** — ce qu'il fait
- **Fonctionnalités** — liste détaillée des actions
- **Données** — tables utilisées, inputs/outputs
- **Flux** — comment il s'intègre au reste
- **Sécurité** — protections implémentées

---

## 🌍 PORTAIL DOMIZI (Multi-Métiers)

### `app.php`
**Type**: PHP + HTML  
**Localisation**: Racine du projet  
**Responsabilité**: Point d'entrée portail Domizi — permet choisir pays/métier

**Fonctionnalités:**
- ✅ Dropdown pays (Bénin sélectionné par défaut)
- ✅ Grid 4 domaines (Beauté, Maison, Professionnel, Santé)
- ✅ Clic domaine → affiche métiers
- ✅ Clic métier (Coiffure) → redirect `index.php` CCT

**Données Utilisées:**
- Session: `$_SESSION['pays_id']`, `$_SESSION['domaine_id']`
- Pas de BDD (données statiques)

**Sécurité:**
- ✅ Pas de données sensibles
- ✅ Validations GET simples

**Flux:**
```
Visiteur accède app.php 
→ Choisit "Beauté" 
→ Choisit "Coiffure" 
→ Redirect /coiffons/index.php
```

---

## 🎯 ROUTEUR PRINCIPAL (CCT)

### `index.php`
**Type**: PHP Router  
**Localisation**: `/coiffons/index.php`  
**Responsabilité**: Routeur principal branche Coiffe Chez Toi

**Fonctionnalités:**
- ✅ Dispatch routes via paramètre `?page=`
- ✅ Redirection login si non authentifié
- ✅ Chargement pages selon rôle (client/coiffeur/admin)
- ✅ Inclusion headers/footers
- ✅ Gestion erreur 404

**Routes Principales:**
```php
?page=home          → views/home.php (accueil)
?page=login         → access/connexion.php
?page=signup        → access/inscription.php
?page=dashboard     → views/client/dashboard.php (client)
?page=dashboard_coiffeur → views/coiffeur/dashboard.php
?page=admin         → first/admin_dashboard.php
?page=annuaire      → filter/annuaire_coiffeurs.php
```

**Sécurité:**
- ✅ CSRF token généré session
- ✅ Vérification rôle utilisateur
- ✅ Protection path traversal (`basename($page)`)

---

## 🔐 AUTHENTIFICATION & ACCÈS

### `access/connexion.php`
**Type**: PHP + HTML + Form  
**Responsabilité**: Page login clients/coiffeurs

**Fonctionnalités:**
- ✅ Formulaire email + password
- ✅ Validation serveur (email valide, password non vide)
- ✅ Vérif base de données (email existe, password correct)
- ✅ Brute-force protection (5 tentatives / 15 min)
- ✅ Vérif compte banni (`statut = 'banni'`)
- ✅ Gestion session (hydratation `$_SESSION` complet)
- ✅ Redirect post-login selon rôle (client/coiffeur/admin)

**Données Utilisées:**
- **Table**: `users` (SELECT email, password_hash, role, statut)
- **Output Session**: `id_user`, `email`, `nom`, `prenom`, `photo_profil`, `role`, `id_ville`

**Erreurs Gérés:**
- ❌ Email non trouvé → "Email ou mot de passe incorrect"
- ❌ Password incorrect → idem (sécurité: pas de leak info)
- ❌ Compte banni → "Compte suspendu, contactez support"
- ❌ Tentatives > 5 → "Compte temporairement bloqué (15 min)"

**Sécurité:**
- ✅ Password hashing `password_verify()`
- ✅ CSRF token validé POST
- ✅ Brute-force counter en session
- ✅ Prepared statements (injection SQL)
- ✅ Pas de données sensibles en logs

---

### `access/inscription.php`
**Type**: PHP + HTML + Form + AJAX  
**Responsabilité**: Inscription clients/coiffeurs

**Fonctionnalités:**
- ✅ Rôle pré-défini via GET `?role=client|coiffeur`
- ✅ Formulaire multi-étapes (refonte moderne)
- ✅ Validation client-side (regex email, password force)
- ✅ Validation serveur strict
- ✅ Compression photo côté client (Canvas JS)
- ✅ Upload photo profil + diplôme (coiffeur)
- ✅ Dropdown ville/quartier AJAX
- ✅ Protection CSRF
- ✅ Hash password (`password_hash()`)
- ✅ Création compte + session + redirect

**Étapes Formulaire:**
1. **Identité**: Email, password, nom, prenom
2. **Photo**: Upload photo profil
3. **Localisation**: Ville (select) + quartier (AJAX)
4. **Métier** (coiffeur uniquement): Upload diplôme
5. **Confirmation**: Résumé + bouton confirmer

**Données Utilisées:**
- **Input**: Formulaire POST (`name`, `prenom`, `email`, `password`)
- **Table INSERT**: `users` (email, password_hash, role, photo_profil, id_ville, id_quartier)
- **Output Session**: Hydratation session automatique après insertion

**Validation:**
- ✅ Email: format valide + non existant BD
- ✅ Password: min 8 chars, 1 majuscule, 1 chiffre
- ✅ Photo: MIME valide (jpeg, png), max 2MB
- ✅ Diplôme (coiffeur): même validations photo
- ✅ CSRF token: généré + validé

**Sécurité:**
- ✅ `htmlspecialchars()` tous inputs
- ✅ MIME check réel (magic bytes)
- ✅ Prepared statements
- ✅ Password hash `password_hash($pwd, PASSWORD_DEFAULT)`
- ✅ Aucun admin en formulaire public

---

### `access/password_reset.php`
**Type**: PHP + HTML + Form  
**Responsabilité**: Récupération mot de passe via OTP

**Fonctionnalités:**
- ✅ **Étape 1**: Saisie email → génération OTP
- ✅ **Étape 2**: Vérification OTP → formulaire nouveau MDP
- ✅ **Étape 3**: Mise à jour password → confirmation

**Flux:**
```
Email → Générer OTP (5 min validité)
→ Envoyer email OTP
→ User entre OTP (6 chiffres)
→ Vérif OTP valide + pas expiré
→ Form nouveau password
→ Hash + UPDATE users
→ Redirect login success
```

**Sécurité:**
- ✅ OTP 6 chiffres aléatoire
- ✅ Hash password reset (pas en clair)
- ✅ Expiration 5 minutes stricte
- ✅ Rate limit OTP (1/min, 3/heure)
- ✅ Invalidation OTP après usage

---

### `access/verify_otp.php`
**Type**: PHP AJAX Handler  
**Responsabilité**: Backend vérification OTP

**Fonctionnalités:**
- ✅ Validation OTP POST
- ✅ Vérif code valide (password_verify hash)
- ✅ Vérif pas expiré (NOW() < expires_at)
- ✅ Vérif tentatives < 5
- ✅ Incrémentation tentatives
- ✅ Invalidation après 5 tentatives
- ✅ Retour JSON (success/error)

**Sécurité:**
- ✅ OTP jamais en clair
- ✅ Brute-force protection (max 5)
- ✅ Rate limiting

---

### `access/get_quartiers.php`
**Type**: PHP AJAX Handler  
**Responsabilité**: Charger quartiers via AJAX

**Fonctionnalités:**
- ✅ Input: `id_ville` (GET)
- ✅ Query: `SELECT * FROM quartiers WHERE id_ville = ?`
- ✅ Output: JSON array quartiers
- ✅ Utilisé inscription + modifier_profil

---

## 👥 PAGES CLIENT

### `views/client/dashboard.php`
**Type**: PHP + HTML + CSS  
**Responsabilité**: Homepage client — point focal

**Fonctionnalités:**
- ✅ Hero section avec carousel slider
- ✅ Barre recherche coiffeurs (hero)
- ✅ Filters: ville, quartier, note min
- ✅ Grid coiffeurs (cartes)
- ✅ Pagination (20 par page)
- ✅ Section promo "Pourquoi Coiffe Chez Toi"
- ✅ Chat IA overlay (bottom-right)
- ✅ Footer premium

**Données Affichées:**
- Liste coiffeurs approuvés + note moyenne
- Paramètres SESSION client
- Matching géographique (ville/quartier session)

**Sécurité:**
- ✅ Auth check: `if ($_SESSION['role'] !== 'client')`
- ✅ Pagination limit offset

---

### `filter/annuaire_coiffeurs.php`
**Type**: PHP + HTML + Search  
**Responsabilité**: Listing & filtrage coiffeurs

**Fonctionnalités:**
- ✅ Listing tous coiffeurs approuvés (`is_approved = 1`)
- ✅ Recherche par nom/prénom (`?q=`)
- ✅ Filtrage ville (select)
- ✅ Filtrage quartier (multi-select)
- ✅ Filtrage note minimum (slider)
- ✅ Affichage note moyenne + nb avis
- ✅ Clic coiffeur → profil public

**SQL Queries:**
```sql
SELECT u.*, AVG(c.note) as note_moy, COUNT(c.id) as nb_avis
FROM users u
LEFT JOIN commentaires c ON c.id_coiffeur = u.id
WHERE u.is_approved = 1 
  AND (u.nom LIKE ? OR u.prenom LIKE ?)
  AND u.id_ville = ?
  AND u.id_quartier IN (...)
  AND note_moy >= ?
GROUP BY u.id
```

---

### `coiffeurs/profil_public.php`
**Type**: PHP + HTML + View  
**Responsabilité**: Profil public coiffeur consultable

**Fonctionnalités:**
- ✅ Affichage photo profil
- ✅ Bio professionnel
- ✅ Note moyenne + nombre avis
- ✅ Horaires disponibles (table)
- ✅ Services catalogue (list)
- ✅ 5 derniers avis publics (masqué prénom)
- ✅ Btn "Réserver RDV"
- ✅ Contact WhatsApp/Appel

**Sécurité:**
- ✅ ID coiffeur validé (intval)
- ✅ Vérif `is_approved = 1`
- ✅ Prénom masqué dans avis (K***)

---

### `client/creer_rendezvous.php`
**Type**: PHP + HTML + Form  
**Responsabilité**: Step 1 — Sélection date/heure/service

**Fonctionnalités:**
- ✅ Sélection prestation (dropdown services coiffeur)
- ✅ Sélection date (calendrier, min aujourd'hui)
- ✅ Sélection heure (time picker, horaires dispo)
- ✅ Options supplémentaires (checkboxes)
- ✅ Calcul prix total dynamique
- ✅ Preview heure fin calculée
- ✅ Btn "Continuer" → GET params → `reserver.php`

**GET Params Passés:**
```
?presta_id=123&coiffeur_id=45&date_rdv=2026-08-15&heure_debut=14:30
```

---

### `client/reserver.php`
**Type**: PHP + HTML + Form  
**Responsabilité**: Step 2 — Confirmation & paiement

**Fonctionnalités:**
- ✅ Récapitulatif complet RDV
- ✅ Affichage coiffeur (photo + note)
- ✅ Affichage service (nom + prix + durée)
- ✅ Affichage date/heure
- ✅ Affichage options + surcoût
- ✅ **Total à geler** bien visible
- ✅ Vérif solde portefeuille suffisant
- ✅ Btn "Confirmer Réservation"
- ✅ POST → INSERT rendez_vous + notif coiffeur

**Logique Paiement:**
```
1. Vérif solde >= prix_total
2. INSERT INTO rendez_vous (statut='en_attente')
3. UPDATE users SET solde = solde - prix_total (gel)
4. INSERT INTO notifications (coiffeur)
5. Redirect confirmation screen
```

**Sécurité:**
- ✅ CSRF token validé
- ✅ Solde réel consulté BDD (pas session)
- ✅ Prepared statements

---

### `client/mes_rendezvous.php`
**Type**: PHP + HTML  
**Responsabilité**: Historique RDV client

**Fonctionnalités:**
- ✅ Listing tous RDV client (paginated 20/page)
- ✅ Filtrage par statut (en_attente, accepté, refusé, terminé, archivé)
- ✅ Affichage coiffeur (photo + nom)
- ✅ Affichage date/heure
- ✅ Affichage service + prix
- ✅ Statut avec couleur badge
- ✅ Actions rapides:
  - Btn "Appeler" (tel: link)
  - Btn "GPS" (Google Maps)
  - Btn "Annuler" (si en_attente)
- ✅ Btn "Laisser Avis" (si terminé)

**Données Affichées:**
```sql
SELECT rv.*, 
  u.nom, u.prenom, u.photo_profil,
  p.nom_style, p.prix
FROM rendez_vous rv
JOIN users u ON u.id = rv.coiffeur_id
JOIN prestations p ON p.id_prestation = rv.coiffure_id
WHERE rv.client_id = ?
ORDER BY rv.date_rdv DESC
```

---

### `client/laisser_avis.php`
**Type**: PHP + HTML + Form  
**Responsabilité**: Notation post-RDV

**Fonctionnalités:**
- ✅ Validation RDV appartient client
- ✅ Validation RDV terminé
- ✅ Validation pas avis duplo
- ✅ Notation 1-5 étoiles (UI clickable)
- ✅ Commentaire texte (max 500 chars)
- ✅ Plainte optionnelle (si note <= 2)
- ✅ Btn "Soumettre Avis"

**Flux:**
```
GET ?id_rdv=123&id_coiffeur=45
→ Vérif sécurité (client owns RDV, RDV terminé)
→ Formulaire notation
→ POST → traitement_avis.php
```

---

### `traitement_avis.php`
**Type**: PHP Handler  
**Responsabilité**: Backend traitement avis

**Fonctionnalités:**
- ✅ Validation `id_rdv` client
- ✅ Vérif RDV terminé
- ✅ Anti-doublon (CHECK pas avis existe)
- ✅ INSERT INTO commentaires (note, texte, date, type='public')
- ✅ **Notification coiffeur**: "Nouvel avis 5⭐"
- ✅ **Alerte admin** (si note <= 2): notification plainte
- ✅ Solde dégelé → versé coiffeur
- ✅ Flash message success
- ✅ Redirect mes_rendezvous.php

**Sécurité:**
- ✅ Vérif ownership (client_id = SESSION)
- ✅ Prepared statements
- ✅ Anti-doublon check SQL

---

### `client/notifications.php`
**Type**: PHP + HTML  
**Responsabilité**: Centre notifications

**Fonctionnalités:**
- ✅ Listing notifications (LIMIT 50, newest first)
- ✅ Marquage "lire" via AJAX
- ✅ Btn "Marquer tout comme lu"
- ✅ Suppression notification (soft delete)
- ✅ Couleur badge par type (info/success/warning/error)
- ✅ Empty state "Aucune notification"

---

### `client/portefeuille.php`
**Type**: PHP + HTML  
**Responsabilité**: Gestion solde client

**Fonctionnalités:**
- ✅ Affichage solde disponible (grand, doré)
- ✅ Affichage solde gelé (RDV en attente)
- ✅ Historique transactions (20 derniers)
- ✅ Chaque transaction: date, montant, type (débit/crédit), statut
- ✅ Btn "Recharger Solde" (redirect Kkiapay)

---

### `client/traiter_recharge.php`
**Type**: PHP Handler  
**Responsabilité**: Simulation recharge portefeuille

**Fonctionnalités:**
- ✅ Saisie montant (500 - 500K FCFA)
- ✅ Validation montant (entier, range)
- ✅ Simulation recharge (modo test)
- ✅ UPDATE users SET solde = solde + montant
- ✅ INSERT transactions_portefeuille
- ✅ Notification success
- ✅ (Future: vrai webhook Kkiapay)

---

## 💼 PAGES COIFFEUR

### `views/coiffeur/dashboard.php`
**Type**: PHP + HTML + CSS  
**Responsabilité**: Homepage coiffeur — dashboard analytics

**Fonctionnalités:**
- ✅ **Stats du jour**: RDV prévus, revenus du jour, demandes en attente
- ✅ **Bannières alertes**:
  - 🔴 Abonnement expirant
  - 🟡 Diplôme en attente approbation
  - 🟠 Problème paiement
- ✅ **Prochain RDV**: Carte détail (client, adresse, heure)
- ✅ **Planning du jour**: Chronologie RDV + actions
- ✅ **Demandes en attente**: Grid demandes + btnAccepter/Refuser
- ✅ **Complétude profil**: Barre progression % (photo, bio, agenda, services, zones)
- ✅ **Portefeuille**: Solde + dépôt récent
- ✅ **Quick actions**: Liens catalog, agenda, zones, avis

**Données Affichées:**
```sql
-- Stats du jour
SELECT COUNT(*) as nb_rdv_jour,
       SUM(prix) as revenus_jour
FROM rendez_vous 
WHERE coiffeur_id = ?
  AND DATE(date_rdv) = CURDATE()
  AND statut IN ('accepté', 'en_cours', 'terminé')

-- Demandes en attente
SELECT * FROM rendez_vous
WHERE coiffeur_id = ? AND statut = 'en_attente'
ORDER BY date_rdv ASC
```

**Sécurité:**
- ✅ Auth check role='coiffeur'
- ✅ Vérif owner coiffeur_id = SESSION id_user

---

### `coiffeurs/profil_coiffeurs.php`
**Type**: PHP + HTML + Form  
**Responsabilité**: Gestion profil coiffeur

**Fonctionnalités:**
- ✅ Upload photo profil
- ✅ Upload diplôme
- ✅ Bio professional (textarea, max 500 chars)
- ✅ Contact WhatsApp/Email
- ✅ Localisation (ville + quartier)
- ✅ Form save → UPDATE users

**Validation:**
- ✅ MIME photo valide
- ✅ Diplôme accepté admin? (flag)
- ✅ Bio non vide

---

### `coiffeurs/gestion_catalogue.php`
**Type**: PHP + HTML + CRUD  
**Responsabilité**: Gestion services/prestations

**Fonctionnalités:**
- ✅ **Listing**: Tous services coiffeur (edit/delete inline)
- ✅ **Créer service**: Formulaire
  - Nom style (Tresse, Box, Gnarls...)
  - Description (1000 chars max)
  - Prix (FCFA)
  - Durée (minutes)
  - Options JSON (checkbox prix surcoût)
  - Photo style
- ✅ **Éditer service**: Modification nom/prix/durée/options
- ✅ **Supprimer service**: Soft delete (ou hard)
- ✅ **Upload photo**: Stocké dossier `/coiffeurs/uploads/prestations/`

**Options JSON Format:**
```json
[
  {"nom": "Lissage", "prix": 500},
  {"nom": "Volume", "prix": 300}
]
```

---

### `coiffeurs/agenda_coiffeurs.php`
**Type**: PHP + HTML + Form  
**Responsabilité**: Gestion horaires hebdomadaires

**Fonctionnalités:**
- ✅ Tableau 7 jours (Lun-Dim)
- ✅ Checkbox "Actif" par jour
- ✅ Time pickers heure début/fin (si actif)
- ✅ Save → INSERT/UPDATE disponibilites
- ✅ Validation: fin > début
- ✅ Feedback success

**Données:**
```sql
CREATE TABLE disponibilites (
  id INT,
  coiffeur_id INT,
  jour VARCHAR(20), -- lundi, mardi...
  heure_debut TIME,
  heure_fin TIME,
  actif BOOLEAN
)
```

---

### `coiffeurs/valider_rendezvous.php`
**Type**: PHP Handler AJAX  
**Responsabilité**: Accepter/refuser demandes RDV

**Fonctionnalités:**
- ✅ Listing demandes en attente
- ✅ Détails: client (photo, nom), date, heure, service, prix
- ✅ Btn "Accepter RDV"
  - UPDATE rendez_vous SET statut = 'accepté'
  - Notification client: "RDV accepté!"
  - Montant gelé confirme
- ✅ Btn "Refuser RDV"
  - UPDATE rendez_vous SET statut = 'refusé'
  - Notification client: "RDV refusé"
  - Solde client dégelé (remboursé)

---

### `coiffeurs/mes_avis.php`
**Type**: PHP + HTML  
**Responsabilité**: Consultation ses avis

**Fonctionnalités:**
- ✅ **Score global**: Note moyenne affichée (ex: 4.7/5)
- ✅ **Distribution notes**: Barres colorées (5⭐, 4⭐, 3⭐, 2⭐, 1⭐)
- ✅ **Listing avis**: 20 derniers avis
  - Chaque avis: prénom masqué (K***), note ⭐, commentaire, date relative
  - Empty state si aucun avis

---

### `coiffeurs/mes_zones.php`
**Type**: PHP + HTML + Form  
**Responsabilité**: Gestion zones couverture

**Fonctionnalités:**
- ✅ Sélection ville (Cotonou, Parakou...)
- ✅ Multi-select quartiers (Haut, Jéricho, Gobron...)
- ✅ Rayon déplacement (km, optionnel)
- ✅ Save → UPDATE zones_prestataire

---

### `coiffeurs/portefeuille.php`
**Type**: PHP + HTML  
**Responsabilité**: Visualiser solde coiffeur

**Fonctionnalités:**
- ✅ Solde disponible (grand, doré)
- ✅ Solde gelé (optionnel, RDV en attente)
- ✅ Historique transactions (20 derniers)
- ✅ (Future: Btn "Retrait" vers compte bancaire)

---

## 🛡️ SÉCURITÉ & SERVICES

### `security/config.php`
**Type**: PHP Config  
**Responsabilité**: Centralisation config + BDD

**Contenu:**
- ✅ Connexion PDO (créé Singleton)
- ✅ Constantes config (SITE_NAME, ADMIN_EMAIL...)
- ✅ Chargement .env

---

### `security/csrf.php`
**Type**: PHP Helper  
**Responsabilité**: Génération & validation CSRF tokens

**Fonctionnalités:**
```php
function generate_csrf_token() {
  $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
  return $_SESSION['_csrf_token'];
}

function validate_csrf_token($token) {
  return hash_equals($_SESSION['_csrf_token'] ?? '', $token);
}
```

---

### `security/notifications.php`
**Type**: PHP Helper  
**Responsabilité**: Centralisation notifications

**Fonctionnalités:**
```php
function notifier_coiffeur($id_coiffeur, $msg, $type='info') {
  // INSERT INTO notifications
}

function notifier_client($id_client, $msg, $type='info') {
  // INSERT INTO notifications
}

function notifier_admins($msg, $type='warning') {
  // INSERT INTO notifications (tous admins)
}
```

---

### `security/cron_notifications.php`
**Type**: PHP Cron Task  
**Responsabilité**: Notifications automatiques

**Tâches:**
- ✅ RDV demain: Rappel client 24h avant
- ✅ Abonnement expirant: Alerte coiffeur 7j avant
- ✅ RDV du jour: Rappel client 2h avant
- ✅ Diplômes en attente: Alerte admin

---

### `security/PaymentService.php`
**Type**: PHP Service  
**Responsabilité**: Abstraction paiements Kkiapay

**Méthodes:**
```php
class PaymentService {
  public function recharger($amount, $phone) { }
  public function geler($amount, $id_client) { }
  public function liberer($id_client) { }
  public function activerAbonnement($id_coiffeur, $plan) { }
}
```

---

## 🎨 COMPOSANTS RÉUTILISABLES

### `views/components/navbar_client.php`
**Contenu:**
- ✅ Logo
- ✅ Barre recherche (optionnel)
- ✅ Icône notifications (avec badge count)
- ✅ Menu utilisateur (dropdown)
- ✅ Btn logout

---

### `views/components/otp_verification.php`
**Contenu:**
- ✅ 6 champs input (1 digit each)
- ✅ Auto-focus navigation
- ✅ Paste detection
- ✅ Countdown timer
- ✅ Btn "Renvoyer Code"

---

### `views/components/toast.php`
**Contenu:**
- ✅ Message toast (success/error/warning/info)
- ✅ Auto-dismiss après 5s
- ✅ Close button

---

### `views/components/empty_state.php`
**Contenu:**
- ✅ Icon + message "Aucune donnée"
- ✅ CTA button (optionnel)

---

## 📊 CORE INFRASTRUCTURE

### `src/Core/Database.php`
**Type**: PHP Class  
**Responsabilité**: Singleton PDO

**Méthodes:**
```php
class Database {
  public static function getInstance() { }
  public function prepare($sql) { }
  public function execute($params) { }
}
```

---

### `src/Core/Router.php`
**Type**: PHP Router  
**Responsabilité**: Dispatch routes

---

### `src/Core/Lang.php`
**Type**: PHP Multilingual  
**Responsabilité**: Gestion FR/EN/ES/AR

**Données:**
```php
$LANG = [
  'fr' => ['login' => 'Connexion', ...],
  'en' => ['login' => 'Login', ...],
  ...
]
```

---

## 👨‍💼 ADMINISTRATION

### `first/admin_dashboard.php`
**Type**: PHP + HTML  
**Responsabilité**: Dashboard admin

**Fonctionnalités:**
- ✅ KPIs globaux (users, RDV/jour, revenue)
- ✅ Graphiques (optionnel)
- ✅ Quick actions (validation, modération)

---

### `first/admin_actions.php`
**Type**: PHP Handler  
**Responsabilité**: Actions admin

**Actions:**
- ✅ Approuver diplôme coiffeur
- ✅ Refuser diplôme coiffeur
- ✅ Modérer avis abusif
- ✅ Bannir compte user

---

## 📧 AUTRES

### `modifier_profil.php`
**Responsabilité**: Édition profil general (tous rôles)

### `profil.php`
**Responsabilité**: Paramètres utilisateur

### `deconnexion.php`
**Responsabilité**: Logout (destroy session)

### `coiffeurs/sauvegarder_agenda.php`
**Responsabilité**: Backend save horaires AJAX

---

## 📐 DATABASE SCHEMA

### Tables Principales

```sql
users:
  id, email, password_hash, role, nom, prenom,
  photo_profil, bio, telephone, id_ville, id_quartier,
  email_verifie, is_approved, statut, solde,
  created_at, updated_at

rendez_vous:
  id, client_id, coiffeur_id, coiffure_id,
  date_rdv, heure_debut, heure_fin,
  statut_rdv, created_at, updated_at

prestations:
  id_prestation, coiffeur_id, nom_style,
  description, prix, duree, image

commentaires (avis):
  id, id_client, id_coiffeur, id_rdv,
  note, texte, type (public/privé), created_at

notifications:
  id, id_user, message, type, statut_lecture,
  date_notification

disponibilites:
  id, coiffeur_id, jour, heure_debut, heure_fin, actif

otp_codes:
  id, user_id, code_hash, expires_at, attempts, used_at

suppressions_comptes (audit):
  id, nom_utilisateur, email_utilisateur,
  role_utilisateur, raison, created_at
```

---

## 🔒 SÉCURITÉ GLOBALE

| Aspect | Implémentation |
|--------|-----------------|
| **SQL Injection** | Prepared statements tous queries |
| **CSRF** | Tokens session validés POST |
| **Brute Force** | Counters session + timeouts |
| **Password** | `password_hash()` + `password_verify()` |
| **XSS** | `htmlspecialchars()` tous outputs |
| **Session** | Sécurisée, timeout 30 min |
| **OTP** | 6 digits, 5 min expiration, hash stocké |
| **Audit** | Suppressions loggées `suppressions_comptes` |

---

**Document Final | Août 2026**
