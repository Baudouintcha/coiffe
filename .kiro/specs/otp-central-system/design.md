# OTP Centralisé — Conception Intégration Phase 2

## Statut: ✅ IMPLÉMENTATION COMPLÈTE

Toutes les tâches d'intégration Phase 2 sont complètes et prêtes pour la production.

## Overview

Ce document de conception spécifie l'architecture technique et les détails d'implémentation du système OTP centralisé en Phase 2. Le système intègre l'infrastructure OTP Phase 1 dans quatre flux utilisateur clés: inscription, réinitialisation de mot de passe, changement d'email et suppression de compte. Tous les flux utilisent une vérification OTP sécurisée et limitée en débit avec gestion d'erreurs complète et UX conviviale.

## Architecture

Le système OTP utilise une architecture centralisée basée sur les services:

- **OtpService**: Logique OTP centrale (générer, vérifier, limiter le débit, invalider)
- **EmailService**: Livraison d'email via PHPMailer (SMTP/sendmail/mail)
- **Composant Vérification OTP**: Composant UI réutilisable (6 chiffres, auto-focus, compte à rebours)
- **Base de Données**: Table `otp_codes` centralisée avec contrainte `unique(user_id, purpose)`
- **Gestion Session**: État stocké en session, codes jamais en texte clair
- **Sécurité**: Hashage des codes, application des limites brute-force, limitation de débit, protection CSRF

## Components and Interfaces

### API OtpService
```php
class OtpService {
    public function generate(int $user_id, string $purpose, int $validity = 5): array
    public function verify(int $user_id, string $purpose, string $code): array
    public function canRequest(int $user_id, string $purpose): array
    public function invalidate(int $user_id, string $purpose): bool
    public function cleanupExpiredOtps(): int
    public function getActiveOtpInfo(int $user_id, string $purpose): ?array
}
```

### API EmailService
```php
class EmailService {
    public function sendOtpCode(string $to_email, string $otp_code, int $validity, string $purpose): array
    public function sendNotification(string $to_email, string $subject, string $message, string $action_text, string $action_url): array
    public function testConnection(): array
}
```

### Composant Vérification OTP
- **Emplacement**: `views/components/otp_verification.php`
- **Entrées**: purpose, email (masqué), durée compte à rebours, affichage erreur
- **Sorties**: Soumission de formulaire avec code 6 chiffres
- **Fonctionnalités**: Auto-focus, navigation backspace, détection collage, compte à rebours, bouton renvoi

## Data Models

### Table otp_codes
```sql
CREATE TABLE otp_codes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL (FK users.id),
    purpose VARCHAR(50) NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED DEFAULT 0,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, purpose),
    KEY (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Ajouts à la Table users
- `email_verifie` INT DEFAULT 0
- `email_verified_at` DATETIME NULL
- `otp_verified_at` DATETIME NULL

### Table suppressions_comptes (Audit)
```sql
CREATE TABLE suppressions_comptes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_utilisateur VARCHAR(255),
    email_utilisateur VARCHAR(255),
    role_utilisateur VARCHAR(50),
    raison TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Correctness Properties

### Property 1: Unicité du Code OTP
**Validates: Requirements 2.2, 3**
- ∀ user_id ∈ users, ∀ purpose ∈ {registration, password_reset, email_change, account_deletion, domizi_action}
- Au maximum UN OTP actif par paire (user_id, purpose) à tout moment
- Appliqué par contrainte UNIQUE en base de données

### Property 2: Expiration du Code
**Validates: Requirements 5**
- ∀ otp ∈ otp_codes, si NOW() > otp.expires_at alors otp.used_at = NULL
- Alors la vérification DOIT échouer avec "Code invalide ou expiré"
- Expiration = création + 5 minutes (non-négociable)

### Property 3: Protection Brute Force
**Validates: Requirements 3.4, 7**
- ∀ otp ∈ otp_codes, si otp.attempts ≥ 5 alors otp.used_at ≠ NULL
- L'OTP doit être invalidé après 5 tentatives échouées
- Aucune tentative de vérification supplémentaire autorisée pour cet OTP

### Property 4: Limitation de Débit
**Validates: Requirements 3.5, 8**
- ∀ user_id, purpose: canRequest(user_id, purpose) retourne TRUE
- Seulement si: (NOW() - last_created_at) ≥ 60 secondes ET count(past_hour) < 3
- Doit bloquer les requêtes qui violent cette contrainte

### Property 5: Vérification Email (Inscription)
**Validates: Requirements 4.1, 11**
- ∀ user ∈ users, si email_verifie = 1 alors ∃ otp avec purpose='registration' et used_at ≠ NULL
- Le compte ne peut être activé qu'après vérification OTP réussie
- La vérification email est prérequis pour l'activation du compte

### Property 6: Piste Audit Suppression de Compte
**Validates: Requirements 4.4, 14**
- ∀ deletion: (user supprimé de users) ⟹ (entrée créée dans suppressions_comptes)
- L'enregistrement audit DOIT contenir: nom, email, rôle, raison, horodatage
- L'enregistrement audit ne DOIT PAS être modifiable ou supprimable

### Property 7: Sécurité Hash de Code
**Validates: Requirements 3.1, 4**
- ∀ otp ∈ otp_codes, code_hash = password_hash(code, PASSWORD_DEFAULT)
- Le code ne DOIT JAMAIS être stocké ou enregistré en texte clair
- La vérification DOIT utiliser password_verify(input_code, code_hash)

### Property 8: Protection CSRF
**Validates: Requirements 3.3, 17**
- ∀ requête POST vers {verify_otp, request_email_change, ...}
- La requête DOIT inclure un _csrf_token valide
- Le token DOIT correspondre à $_SESSION['_csrf_token']
- La requête DOIT être rejetée si le token est invalide ou manquant

## Error Handling

**Erreurs du Flux Inscription**
| Erreur | Message | Récupération |
|-------|---------|----------|
| Format email invalide | "Adresse email invalide" | L'utilisateur rentre à nouveau l'email |
| Email déjà existant | "Cet email est déjà utilisé" | L'utilisateur choisit un autre email |
| Génération OTP échoue | "Erreur lors de la génération du code" | Compte annulé, réessai plus tard |
| Envoi email échoue | "Erreur lors de l'envoi du code: [reason]" | Compte annulé, réessai setup |
| OTP invalide | "Code invalide ou expiré" | L'utilisateur réessai ou demande renvoi |
| OTP expiré (5+ min) | "Code invalide ou expiré" | L'utilisateur clique "Renvoyer le code" |
| Brute force (5 tentatives) | "Code invalide ou expiré" + option renvoi | L'utilisateur demande nouvel OTP |
| Limite de débit | "Veuillez attendre XX secondes" | L'utilisateur attend le délai |

**Erreurs Réinitialisation de Mot de Passe**
| Erreur | Message | Récupération |
|-------|---------|----------|
| Email non trouvé | "Aucun compte trouvé avec cet email" | L'utilisateur essai différent email |
| Mot de passe trop court | "Le mot de passe doit faire au moins 8 caractères" | L'utilisateur entre mot de passe plus long |
| Mots de passe ne correspondent pas | "Les mots de passe ne correspondent pas" | L'utilisateur rentre à nouveau |
| Session expirée | "Session invalide. Veuillez recommencer" | L'utilisateur recommence le processus |

**Erreurs Changement d'Email**
| Erreur | Message | Récupération |
|-------|---------|----------|
| Format email invalide | "Adresse email invalide" | L'utilisateur corrige le format |
| Email déjà utilisé | "Cet email est déjà utilisé par un autre compte" | L'utilisateur choisit différent email |
| Identique à actuel | "Le nouvel email doit être différent" | L'utilisateur entre nouvel email |
| OTP invalide | "Code invalide ou expiré" | L'utilisateur réessai ou renvoie |

**Erreurs Suppression de Compte**
| Erreur | Message | Récupération |
|-------|---------|----------|
| OTP invalide | "Code invalide ou expiré" | L'utilisateur réessai ou renvoie |
| Session perdue | "Session invalide" | L'utilisateur clique "Annuler" et recommence |

**Toutes les erreurs maintiennent la sécurité:**
- ✅ Pas de fuite d'information sur l'existence de l'utilisateur
- ✅ Messages génériques où applicable
- ✅ Pas de données sensibles dans les messages d'erreur
- ✅ Les erreurs limite de débit incluent le temps d'attente

## Testing Strategy

### Tests Fonctionnels
- ✅ Génération OTP avec format 6 chiffres correct
- ✅ Hashage de code et vérification
- ✅ Expiration après 5 minutes
- ✅ Blocage brute-force à 5 tentatives
- ✅ Limitation de débit entre requêtes (60s, 3/heure)
- ✅ Isolation basée sur purpose (registration ≠ password_reset)

### Tests d'Intégration
- ✅ Inscription: signup → OTP → vérifier → email_verifie=1 → dashboard
- ✅ Réinitialisation mot de passe: email → OTP → formulaire mot de passe → mise à jour → connexion
- ✅ Changement email: formulaire → OTP → vérifier → email mis à jour → message
- ✅ Suppression compte: avertissement → OTP → confirmation → supprimé → journal audit

### Tests UI/UX
- ✅ Responsivité mobile (6 champs d'entrée visibles)
- ✅ Navigation clavier et auto-focus
- ✅ Détection collage et auto-remplissage
- ✅ Précision compte à rebours
- ✅ Fonctionnalité bouton renvoi

### Tests de Sécurité
- ✅ Code non enregistré en production
- ✅ Session ne contient pas le code en clair
- ✅ Tokens CSRF validés
- ✅ Piste audit pour suppressions dans suppressions_comptes
- ✅ Masquage email en UI

### Guide de Test Complet
- Voir `PHASE_2_TESTING_GUIDE.md` pour 14 scénarios de test détaillés
- Inclut les requêtes SQL de vérification base de données
- Cas limites et gestion d'erreurs couverts

## Points d'Intégration

### 2.1 Flux Inscription (access/inscription.php) ✅ IMPLÉMENTÉ

**Flux implémenté:**
1. ✅ L'utilisateur soumet le formulaire d'inscription
2. ✅ La validation réussit
3. ✅ Compte créé avec `email_verifie = 0` (inactif)
4. ✅ OTP généré (purpose: 'registration')
5. ✅ Code OTP envoyé via EmailService
6. ✅ Composant vérification OTP affichage
7. ✅ L'utilisateur entre code 6 chiffres dans les champs auto-focus
8. ✅ Vérifier code via OtpService::verify()
9. ✅ Bouton renvoi: Limité en débit (60s), régénère OTP
10. ✅ Token CSRF validé sur tous les formulaires

### 2.2 Flux Réinitialisation Mot de Passe (access/password_reset.php — NOUVEAU) ✅ IMPLÉMENTÉ

**Flux progressif 3-étapes implémenté:**
1. ✅ Étape 1: Entrée Email - L'utilisateur entre email, OTP généré et envoyé
2. ✅ Étape 2: Vérification OTP - L'utilisateur entre code 6 chiffres
3. ✅ Étape 3: Nouveau Mot de Passe - L'utilisateur entre nouveau mot de passe, BD mise à jour

### 2.3 Flux Changement d'Email (modifier_profil.php) ✅ IMPLÉMENTÉ

**Flux 2-étapes implémenté:**
1. ✅ Étape 1: Demande Email - L'utilisateur entre nouvel email, OTP envoyé à nouvel email
2. ✅ Étape 2: Vérification OTP - L'utilisateur entre code, email mis à jour

### 2.4 Flux Suppression de Compte (profil.php) ✅ IMPLÉMENTÉ

**Suppression protégée OTP 3-étapes implémentée:**
1. ✅ Étape 1: Demande OTP - L'utilisateur confirme demande suppression
2. ✅ Étape 2: Vérification OTP - L'utilisateur entre code reçu par email
3. ✅ Étape 3: Confirmation Finale - L'utilisateur confirme suppression permanente, audit enregistré

## Implémentation de Sécurité ✅ COMPLÈTE

✅ **Hashage de Code** - password_hash/verify, jamais texte clair
✅ **Protection Brute Force** - Max 5 tentatives par OTP
✅ **Limitation de Débit** - 1 OTP/60s, max 3/heure par utilisateur
✅ **Expiration** - Validité 5 minutes
✅ **Protection CSRF** - Validation token sur tous formulaires
✅ **Gestion Session** - Pas de codes en session
✅ **Sécurité Email** - Codes envoyés par email uniquement, masqués en UI
✅ **Piste Audit** - Suppressions enregistrées dans suppressions_comptes
✅ **Validation Entrée** - Serveur uniquement

## Modifications de Fichiers ✅ COMPLÈTES

| Fichier | Statut | Modifications |
|------|--------|---------|
| `access/inscription.php` | ✅ Modifié | Génération OTP, vérification, renvoi |
| `access/password_reset.php` | ✅ NOUVEAU | Réinitialisation mot de passe 3-étapes avec OTP |
| `access/connexion.php` | ✅ Modifié | Lien réinitialisation mot de passe, message succès |
| `modifier_profil.php` | ✅ Modifié | Changement email avec OTP |
| `profil.php` | ✅ Modifié | Flux OTP suppression compte |

## Composants Réutilisables (Phase 1) ✅ VÉRIFIÉS

- ✅ `views/components/otp_verification.php` — 6 chiffres, auto-focus, compte à rebours, renvoi
- ✅ `src/Services/OtpService.php` — Logique OTP centrale
- ✅ `src/Services/EmailService.php` — Livraison email PHPMailer
- ✅ `access/verify_otp.php` — Gestionnaire vérification avec validation CSRF

## Checklist Déploiement ✅ PRÊT

- ✅ Migration base de données créée
- ✅ PHPMailer dans composer.json (^6.8)
- ✅ Tous les services Phase 1 vérifiés
- ✅ Tous les fichiers Phase 2 créés/modifiés
- ✅ Configuration email (.env.example)
- ✅ Gestion d'erreurs complète
- ✅ Sécurité pleinement implémentée
- ✅ Guide de test fourni
- ✅ Documentation d'intégration complète
