# 🔐 IDENTIFIANTS ADMINISTRATEUR — Domizi v1.0

**⚠️ CONFIDENTIEL — À CONSERVER DANS UN ENDROIT SÛR**

---

## 🔑 COMPTES ADMINISTRATEUR

### 1. Compte Admin Principal

```
┌─────────────────────────────────────────────┐
│     ADMINISTRATEUR PRINCIPAL — Domizi      │
├─────────────────────────────────────────────┤
│ Email              : admin@domizi.local     │
│ Mot de passe       : Admin@2026!            │
│ Rôle               : admin                  │
│ Accès              : Tous les droits        │
└─────────────────────────────────────────────┘
```

**URL de connexion** :
```
http://localhost/coiffons/connexion
```

**Fonctionnalités** :
- Gestion complète de la plateforme
- Gestion des utilisateurs (clients, coiffeurs)
- Gestion des paiements et transactions
- Modération et suspensions
- Statistiques et rapports
- Configurations système

### 2. Compte Support

```
┌─────────────────────────────────────────────┐
│        COMPTE SUPPORT — Domizi             │
├─────────────────────────────────────────────┤
│ Email              : support@domizi.local   │
│ Mot de passe       : Support@2026!          │
│ Rôle               : admin                  │
│ Accès              : Support client         │
└─────────────────────────────────────────────┘
```

**Fonctionnalités** :
- Gestion des tickets de support
- Aide aux utilisateurs
- Modération des avis
- Résolution de litiges

### 3. Compte Test

```
┌─────────────────────────────────────────────┐
│         COMPTE TEST — Domizi               │
├─────────────────────────────────────────────┤
│ Email              : test@domizi.local      │
│ Mot de passe       : Test@2026!             │
│ Rôle               : admin                  │
│ Accès              : Tous les droits        │
└─────────────────────────────────────────────┘
```

**Utilisation** :
- Tests des fonctionnalités
- Développement et débogage
- Validation avant production

---

## 🗄️ BASE DE DONNÉES

### Configuration MySQL

```
Host        : localhost
Port        : 3306
User        : root
Password    : (vide)
Database    : domizi
Charset     : utf8mb4
```

### PhpMyAdmin

```
URL         : http://localhost/phpmyadmin/
User        : root
Password    : (vide)
```

---

## 🌐 ENVIRONNEMENT

### Configuration .env

```
DB_HOST=localhost
DB_NAME=domizi
DB_USER=root
DB_PASS=
APP_ENV=development
APP_DEBUG=true
```

---

## 📋 AUTRES COMPTES CRÉÉS

### Comptes de test utilisateurs

| Type | Email | Mot de passe | Rôle |
|------|-------|------------|------|
| Admin | admin@domizi.local | Admin@2026! | admin |
| Support | support@domizi.local | Support@2026! | admin |
| Test | test@domizi.local | Test@2026! | admin |

---

## 🔒 SÉCURITÉ & BONNES PRATIQUES

### ⚠️ IMPORTANT

1. **En développement local uniquement**
   - Ces identifiants sont pour l'environnement local (localhost)
   - Ne jamais utiliser en production
   - Ne jamais les committer dans Git

2. **Changer les mots de passe en production**
   ```bash
   # Avant de mettre en ligne, générer de nouveaux identifiants
   ```

3. **Logs et surveillance**
   - Activés : APP_DEBUG=true (développement)
   - À désactiver : APP_DEBUG=false (production)

4. **Emails de verification**
   - Vérification email : email_verifie = 1 (présélectionné)
   - Vérification téléphone : telephone_verifie = 0

5. **Solde et abonnements**
   - Solde initial : 0.00
   - Abonnement : Non actif (abonnement_status = 0)

---

## 🚀 PREMIER ACCÈS

### Étape 1 : Démarrer XAMPP

```
C:\xampp\xampp-control.exe
→ Cliquer "Start" Apache
→ Cliquer "Start" MySQL
```

### Étape 2 : Accéder au projet

```
http://localhost/coiffons/
```

### Étape 3 : Se connecter

```
URL      : http://localhost/coiffons/connexion
Email    : admin@domizi.local
Password : Admin@2026!
```

### Étape 4 : Tableau de bord admin

Après connexion, vous accédez au tableau de bord administrateur.

---

## 🔄 RÉCUPÉRATION DE MOT DE PASSE

### Si vous avez oublié le mot de passe

1. Aller à : `http://localhost/coiffons/password_reset`
2. Saisir votre email : `admin@domizi.local`
3. Vérifier votre email pour le lien
4. Cliquer sur le lien et créer un nouveau mot de passe

### Alternative : Réinitialiser via PHP

```php
<?php
$pdo = new PDO('mysql:host=localhost;dbname=domizi', 'root', '');
$new_password = password_hash('NewPassword@2026!', PASSWORD_BCRYPT);
$pdo->query("UPDATE users SET password='$new_password' WHERE email='admin@domizi.local'");
?>
```

---

## 📊 VÉRIFICATION DES COMPTES

### Voir les comptes admin

```sql
SELECT id, email, role, statut, created_at 
FROM users 
WHERE role = 'admin';
```

### Voir tous les utilisateurs

```sql
SELECT id, email, role, statut, created_at 
FROM users 
LIMIT 10;
```

### Vérifier le statut de connexion

```sql
SELECT * 
FROM users 
WHERE email = 'admin@domizi.local'\G
```

---

## 🛠️ SCRIPTS UTILES

### Générer de nouveaux identifiants

```bash
# Via navigateur
http://localhost/coiffons/generate_admin_credentials.php

# Via ligne de commande
C:\xampp\php\php.exe generate_admin_credentials.php
```

### Réinitialiser la base de données

```bash
# Supprimer et recréer
http://localhost/coiffons/install_db.php
```

---

## 📞 SUPPORT

### Diagnostiquer les problèmes

```
http://localhost/coiffons/diagnostic_project.php
```

### Voir les logs

```
Logs Apache    : C:\xampp\apache\logs\error.log
Logs PHP       : C:\xampp\php\logs\php_error.log
Logs MySQL     : C:\xampp\mysql\data\*.err
```

---

## 📝 JOURNAL DES MODIFICATIONS

### Créé
- 📅 Août 2026
- 👤 Admin Domizi
- 📝 Création initiale des comptes de test

### Dernière modification
- 📅 Août 2026
- 🔄 Vérification et validation des accès

---

## 🎯 CHECKLIST DE SÉCURITÉ

- [ ] Vérifier que les mots de passe sont forts
- [ ] Ne pas committer ce fichier dans Git
- [ ] Changer les mots de passe avant production
- [ ] Activer SSL/HTTPS en production
- [ ] Activer la 2FA (Two-Factor Authentication)
- [ ] Mettre en place des logs d'accès
- [ ] Faire des sauvegardes régulières
- [ ] Mettre à jour PHP et les dépendances

---

**⚠️ CONFIDENTIEL**

Ce fichier contient des identifiants sensibles. À conserver dans un endroit sûr et ne pas partager.

Créé : Août 2026  
Statut : ✅ En vigueur
