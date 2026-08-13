# OTP Centralisé — Spécification Complète

## 1. Vue d'ensemble

Un système OTP (One-Time Password) centralisé, maison, sécurisé et réutilisable pour le projet **Coiffe Chez Toi**.

### Objectifs principaux
- Fournir une unique source de vérité pour tous les codes OTP
- Respecter les standards de sécurité (6 chiffres, 5 min, hash, rate limiting)
- Être extensible pour futures fonctionnalités (Domizi, etc.)
- Ne pas dupliquer le code OTP dans chaque fonctionnalité

### Usages initiaux
- Vérification après inscription
- Réinitialisation du mot de passe
- Changement d'adresse email
- Suppression de compte (avec confirmation)
- Actions sensibles (Domizi)

---

## 2. Architecture & Conception

### 2.1 Service OTP Central

Emplacement: `src/Services/OtpService.php`

Responsabilités:
- Générer des codes OTP sécurisés (6 chiffres)
- Hasher et stocker les codes
- Vérifier les codes contre les hashes
- Gérer l'expiration (5 minutes)
- Implémenter le rate limiting
- Invalider les codes après usage
- Supporter plusieurs purposes

### 2.2 Base de données

Table `otp_codes`:
```
id (INT PRIMARY KEY AUTO_INCREMENT)
user_id (INT UNSIGNED, FK users.id)
purpose (VARCHAR 50) — registration, password_reset, email_change, account_deletion, domizi_action
code_hash (VARCHAR 255) — password_hash() du code
expires_at (DATETIME) — created_at + 5 minutes
attempts (TINYINT, DEFAULT 0) — compte des tentatives incorrectes
used_at (DATETIME NULL) — NULL si non utilisé, sinon date d'utilisation
created_at (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
```

Indexes:
- PRIMARY: id
- UNIQUE: (user_id, purpose) — un OTP actif par user + purpose
- INDEX: user_id
- INDEX: expires_at (pour nettoyage des anciens OTP)

### 2.3 Service d'email

Emplacement: `src/Services/EmailService.php`

Utiliser **PHPMailer** pour:
- Robustesse et gestion d'erreurs
- Support SMTP/sendmail/mail()
- Meilleure délivrabilité
- Logging sécurisé

Configuration `.env`:
```
MAIL_DRIVER=mail          # ou smtp
MAIL_FROM_ADDRESS=noreply@coiffeschez-toi.local
MAIL_FROM_NAME=Coiffe Chez Toi
SMTP_HOST=localhost       # si mail_driver=smtp
SMTP_PORT=25
SMTP_ENCRYPTION=none
```

### 2.4 Composant UI

Chemin: `views/components/otp_verification.php`

Respecte Design System v2.0:
- 6 champs de saisie (1 chiffre par champ)
- Auto-focus entre champs
- Backspace pour revenir
- Collage d'un code complet (123456)
- Compte à rebours (5:00 → 0:00)
- Bouton "Renvoyer le code" (avec cooldown)
- État "chargement"
- Affichage d'erreurs claires
- Responsive mobile/desktop

---

## 3. Sécurité

### 3.1 Stockage des codes
- ✅ JAMAIS stocker en clair
- ✅ password_hash(code, PASSWORD_DEFAULT)
- ✅ Vérifier avec password_verify()

### 3.2 Génération
- ✅ `random_int(100000, 999999)` (PHP natif, sécurisé)
- ✅ Côté serveur uniquement
- ✅ Jamais en URL
- ✅ Jamais en session en clair

### 3.3 Transmission
- ✅ Envoi par email sécurisé (PHPMailer)
- ✅ HTTPS pour les formulaires
- ✅ CSRF token pour les soumissions
- ✅ Validation serveur obligatoire

### 3.4 Protection brute-force
- ✅ Max 5 tentatives par OTP
- ✅ Après 5 tentatives: OTP invalidé
- ✅ Message générique: "Code invalide ou expiré"

### 3.5 Rate limiting
- ✅ 1 nouvel OTP maximum toutes les 60 secondes
- ✅ Max 3 demandes en 1 heure par user
- ✅ Protection IP (optionnel pour phase 1)

---

## 4. Flux utilisateur

### 4.1 Inscription avec OTP
```
1. Formulaire inscription
2. Validation serveur
3. Compte créé (statut inactif)
4. OTP généré (purpose: registration)
5. Email envoyé
6. Interface OTP affichée
7. User entre le code
8. Vérification serveur
9. Compte activé (email_verifie = 1)
10. Redirection dashboard
```

### 4.2 Mot de passe oublié
```
1. Formulaire "Mot de passe oublié"
2. User entre email
3. OTP généré (purpose: password_reset)
4. Email envoyé
5. Interface OTP
6. Après vérification: formulaire nouveau mot de passe
7. MDP réinitialisé
```

### 4.3 Changement d'email
```
1. Profil utilisateur
2. Demande changement email
3. OTP généré (purpose: email_change)
4. Email envoyé à NOUVEL email
5. Interface OTP
6. Après vérification: email changé
```

### 4.4 Suppression de compte
```
1. Paramètres → "Supprimer mon compte"
2. Confirmation demandée
3. OTP généré (purpose: account_deletion)
4. Email envoyé
5. Interface OTP
6. Après vérification: confirmation finale
7. Compte supprimé
8. Enregistrement dans suppressions_comptes
```

---

## 5. Validation des exigences

### Exigence 3: Table OTP
- ✅ Créer `otp_codes` avec les colonnes requises
- ✅ Indexes sur user_id, expires_at
- ✅ Constraint FK sur users.id

### Exigence 4: Génération
- ✅ random_int(100000, 999999)
- ✅ password_hash() pour stockage
- ✅ Jamais en clair

### Exigence 5: Expiration
- ✅ 5 minutes
- ✅ OTP invalide après expiration
- ✅ Réutilisation impossible

### Exigence 6: Purpose
- ✅ Champ `purpose` dans table
- ✅ Énumération: registration, password_reset, email_change, account_deletion, domizi_action
- ✅ OTP password_reset ≠ OTP account_deletion

### Exigence 7: Brute-force
- ✅ Max 5 tentatives
- ✅ Message générique

### Exigence 8: Rate limiting
- ✅ 1 OTP/60s
- ✅ Max 3 demandes/heure
- ✅ Vérification IP optionnelle

### Exigence 9: Email
- ✅ Code envoyé à l'adresse email du compte
- ✅ PHPMailer (service maison)
- ✅ Ne pas logger le code en production

### Exigence 10: Composant UI
- ✅ Respecter Design System v2.0
- ✅ 6 champs numériques
- ✅ Gestion complète du clavier
- ✅ Compte à rebours
- ✅ Responsive

### Exigence 11-14: Flux par fonctionnalité
- ✅ Inscription avec OTP
- ✅ Password reset avec OTP
- ✅ Email change avec OTP
- ✅ Account deletion avec OTP

### Exigence 15: Domizi
- ✅ Purpose générique pour futures actions Domizi
- ✅ Extensible

### Exigence 16: Service central
- ✅ Classe OtpService
- ✅ Méthodes: generate(), send(), verify(), invalidate(), canRequest()
- ✅ Logique centralisée

### Exigence 17: Sécurité PHP
- ✅ PDO préparées
- ✅ Validation serveur
- ✅ CSRF protection
- ✅ Session control
- ✅ Permissions check

### Exigence 18-20: Session, compatibilité
- ✅ Pas de confiance aux données du navigateur
- ✅ Pas de régression
- ✅ Architecture existante respectée

---

## 6. Tests à effectuer

### 6.1 Fonctionnalité OTP
- [ ] OTP valide → succès
- [ ] Code incorrect → refus
- [ ] OTP expiré → refus
- [ ] OTP réutilisé → refus
- [ ] Mauvais purpose → refus
- [ ] 5 tentatives → OTP invalide
- [ ] Spam demandes → rate limiting

### 6.2 Intégration
- [ ] Inscription + OTP
- [ ] Password reset + OTP
- [ ] Email change + OTP
- [ ] Account deletion + OTP

### 6.3 UX/UI
- [ ] Composant sur desktop
- [ ] Composant sur mobile
- [ ] Auto-focus champs
- [ ] Backspace fonctionne
- [ ] Collage code fonctionne
- [ ] Compte à rebours affiche correctement
- [ ] Bouton renvoyer accessible

### 6.4 Email
- [ ] Email reçu
- [ ] Code lisible dans email
- [ ] Lien de renvoie accessible
- [ ] Pas de log du code en prod

---

## 7. Livrables attendus

1. **Table SQL**: Migration pour créer `otp_codes`
2. **Service OTP**: `src/Services/OtpService.php`
3. **Service Email**: `src/Services/EmailService.php`
4. **Composant UI**: `views/components/otp_verification.php`
5. **Fichiers modifiés**:
   - `access/inscription.php` (intégration OTP)
   - `access/connexion.php` ou nouveau fichier `access/password_reset.php`
   - `profil.php` (intégration OTP pour suppression)
   - `modifier_profil.php` (changement email)
6. **Configuration**: `.env.example` avec paramètres mail
7. **Documentation**: Cette spec + guide d'usage

---

## 8. Dépendances

- PHP 7.4+ (password_hash, random_int)
- PHPMailer (composer require phpmailer/phpmailer)
- PDO MySQL
- Bootstrap Icons (déjà présent)
- Design System v2.0 (déjà présent)

---

## 9. Points de configuration

- Adresse email FROM (config)
- Durée OTP: 5 minutes (configurable)
- Max tentatives: 5 (configurable)
- Rate limit: 60s entre OTP (configurable)
- Max demandes/heure: 3 (configurable)
- Template email (versionné)

---

## 10. Prochaines étapes

Phase 1 (cette implémentation):
- ✅ Table OTP
- ✅ OtpService
- ✅ EmailService (PHPMailer)
- ✅ Composant UI
- ✅ Intégration inscription
- ✅ Tests de base

Phase 2 (futur):
- Password reset complet
- Email change
- Account deletion
- Domizi actions
- SMS/WhatsApp (optionnel)
