# OTP Système — Quick Start Guide

## ⚡ 5 Minutes pour Démarrer

### Étape 1: Setup Base de Données
```bash
php security/setup_otp.php
```
✅ Crée la table `otp_codes` et ajoute les colonnes manquantes

### Étape 2: Configuration Email (optionnel)
Copier les paramètres dans `.env`:
```bash
cp .env.otp .env  # Ou copier manuellement les paramètres
```

### Étape 3: Test du Système
```bash
php security/test_otp.php
```
✅ Vérifier tous les tests = PASS

### Étape 4: Intégration dans vos Pages

#### Exemple 1: Inscription avec OTP
```php
<?php
// Après création du compte avec succès

require_once 'src/Services/OtpService.php';
require_once 'src/Services/EmailService.php';

$otp_service = new OtpService($pdo);
$email_service = new EmailService();

// Générer l'OTP
$otp_result = $otp_service->generate($user_id, 'registration');

if ($otp_result['success']) {
    // Envoyer par email
    $email_result = $email_service->sendOtpCode(
        $user_email,
        $otp_result['code'],
        5,  // validité en minutes
        'registration'
    );
    
    if ($email_result['success']) {
        // Afficher le composant de vérification
        $otp_purpose = 'registration';
        $user_email = $otp_result['user_email']; // Masqué
        $csrf_token = $_SESSION['_csrf_token'];
        include 'views/components/otp_verification.php';
    }
}

// Traiter la vérification
if ($_POST['action'] === 'verify_otp') {
    include 'access/verify_otp.php';
    
    if ($otp_verification['success']) {
        // OTP vérifié ! Rediriger
        $_SESSION['email_verified'] = true;
        header("Location: /coiffons/index.php?page=dashboard");
        exit();
    } else {
        $error_message = $otp_verification['message'];
        // Réafficher le composant OTP avec erreur
    }
}
?>
```

#### Exemple 2: Password Reset
```php
<?php
// Après saisie de l'email et vérification qu'il existe

$otp_service = new OtpService($pdo);
$email_service = new EmailService();

$otp_result = $otp_service->generate($user_id, 'password_reset');

if ($otp_result['success']) {
    $email_service->sendOtpCode(
        $user_email,
        $otp_result['code'],
        5,
        'password_reset'
    );
    
    $otp_purpose = 'password_reset';
    include 'views/components/otp_verification.php';
}

// Après vérification
if ($_POST['action'] === 'verify_otp') {
    include 'access/verify_otp.php';
    
    if ($otp_verification['success']) {
        // Marquer comme vérifiée en session
        $_SESSION['password_reset_verified'] = true;
        $_SESSION['password_reset_user_id'] = $user_id;
        
        // Afficher le formulaire nouveau MDP
        include 'views/password_reset_form.php';
    }
}

// Réinitialiser le MDP (avec vérification)
if ($_POST['action'] === 'reset_password' && $_SESSION['password_reset_verified']) {
    $new_password = $_POST['password'];
    // ... validation ...
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
        ->execute([password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['password_reset_user_id']]);
    
    // Nettoyer
    unset($_SESSION['password_reset_verified']);
    
    // Succès
    header("Location: /coiffons/index.php?page=connexion");
}
?>
```

#### Exemple 3: Suppression de Compte
```php
<?php
// Dans profil.php, avant suppression

if ($_POST['action'] === 'request_account_deletion') {
    $otp_service = new OtpService($pdo);
    $email_service = new EmailService();
    
    $otp_result = $otp_service->generate($user_id, 'account_deletion');
    
    if ($otp_result['success']) {
        $email_service->sendOtpCode(
            $user_email,
            $otp_result['code'],
            5,
            'account_deletion'
        );
        
        $_SESSION['deletion_pending'] = true;
        
        $otp_purpose = 'account_deletion';
        include 'views/components/otp_verification.php';
    }
}

// Après vérification OTP
if ($_POST['action'] === 'verify_otp' && $_SESSION['deletion_pending']) {
    include 'access/verify_otp.php';
    
    if ($otp_verification['success']) {
        // Confirmation finale
        echo "<p>Êtes-vous sûr ? Cette action est définitive.</p>";
        echo "<form method='POST'>";
        echo "  <input type='hidden' name='action' value='confirm_deletion'>";
        echo "  <button type='submit'>OUI, supprimer mon compte</button>";
        echo "</form>";
    }
}

// Suppression finale
if ($_POST['action'] === 'confirm_deletion' && $_SESSION['deletion_pending']) {
    $pdo->prepare("DELETE FROM users WHERE id = ?") execute([$user_id]);
    
    // Log pour traçabilité
    $pdo->prepare(
        "INSERT INTO suppressions_comptes (id_user_supprime, nom, prenom, email, role) 
         VALUES (?, ?, ?, ?, ?)"
    )->execute([$user_id, $user['nom'], $user['prenom'], $user['email'], $user['role']]);
    
    session_destroy();
    header("Location: /coiffons/index.php");
}
?>
```

---

## 🔑 Concepts Clés

### OTP Service
```php
// Générer un code
$result = $otp_service->generate($user_id, $purpose);
// $result = ['success' => true, 'code' => '123456', 'expires_in' => 300, 'user_email' => 'u***@gmail.com']

// Vérifier un code
$result = $otp_service->verify($user_id, $purpose, $code);
// $result = ['success' => true, 'message' => 'Code vérifié avec succès.']

// Vérifier si peut demander
$result = $otp_service->canRequest($user_id, $purpose);
// $result = ['allowed' => false, 'message' => 'Veuillez attendre 45 secondes...']

// Invalider
$result = $otp_service->invalidate($user_id, $purpose);
```

### Email Service
```php
// Envoyer OTP
$result = $email_service->sendOtpCode(
    'user@example.com',
    '123456',
    5,              // minutes de validité
    'registration'  // purpose
);

// Envoyer notification
$result = $email_service->sendNotification(
    'user@example.com',
    'Sujet',
    'Message HTML',
    'Texte bouton',
    'https://lien.com'
);
```

### Purpose Types
```
registration        — Vérification après inscription
password_reset      — Réinitialisation mot de passe
email_change        — Changement adresse email
account_deletion    — Suppression compte
domizi_action       — Actions sensibles Domizi
```

---

## 📋 Checklist d'Implémentation

Pour intégrer dans chaque flux:

- [ ] Importer les services
- [ ] Générer l'OTP avec `$otp_service->generate()`
- [ ] Envoyer par email avec `$email_service->sendOtpCode()`
- [ ] Afficher le composant `otp_verification.php`
- [ ] Traiter la vérification avec `verify_otp.php`
- [ ] Vérifier `$otp_verification['success']`
- [ ] Exécuter l'action sensible seulement après vérification
- [ ] Nettoyer la session (unset des flags)
- [ ] Rediriger l'utilisateur

---

## ✅ Points de Vérification

Avant de déployer:

- [ ] `php security/test_otp.php` = 7/7 tests PASS
- [ ] Table `otp_codes` existe
- [ ] PHPMailer installé (`composer require phpmailer/phpmailer`)
- [ ] `.env` configuré (au minimum `MAIL_FROM_ADDRESS`)
- [ ] Colonnes `email_verified_at`, `otp_verified_at` existent dans `users`
- [ ] Composant `otp_verification.php` inclus
- [ ] CSRF tokens validés
- [ ] Pas de confiance aux données du navigateur

---

## 🐛 Dépannage Rapide

| Problème | Solution |
|----------|----------|
| "Table otp_codes n'existe pas" | `php security/setup_otp.php` |
| "Email non reçu" | Vérifier `.env` + `php security/test_otp.php` |
| "OTP bloqué immédiatement" | Rate limit activé — attendre 60s |
| "Code ne se vérifie pas" | Vérifier le code entré (6 chiffres) |
| "Composant UI ne s'affiche pas" | Vérifier inclusion + design system CSS chargé |

---

## 🎯 Résumé

**3 fichiers clés à utiliser:**

1. **OtpService** — Générer, vérifier, rate limit
2. **EmailService** — Envoyer codes par email
3. **otp_verification.php** — Composant UI à inclure

**Pattern d'intégration:**
```
Demande utilisateur
    ↓
Générer OTP + Envoyer email
    ↓
Afficher composant
    ↓
User entre code
    ↓
Vérifier (include verify_otp.php)
    ↓
Si succès: Exécuter action sensible
    ↓
Rediriger
```

---

**✅ Prêt pour intégration !**
