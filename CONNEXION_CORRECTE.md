# ✅ IDENTIFIANTS DE CONNEXION — VÉRIFIÉS & TESTÉS

## 🔐 COMPTES ADMINISTRATEUR VALIDÉS

**TOUS LES MOTS DE PASSE SONT VÉRIFIÉS** — password_verify() = ✅ TRUE

---

## 📋 IDENTIFIANTS À UTILISER

### 1. COMPTE ADMIN PRINCIPAL

```
Email    : admin@domizi.local
Password : Admin2026!
```

**Type** : Administrateur complet  
**Droits** : Tous les droits système  
**Statut** : Actif ✅

### 2. COMPTE SUPPORT

```
Email    : support@domizi.local
Password : Support2026!
```

**Type** : Admin Support  
**Droits** : Gestion support client  
**Statut** : Actif ✅

### 3. COMPTE TEST

```
Email    : test@domizi.local
Password : Test2026!
```

**Type** : Admin Test  
**Droits** : Tous les droits (pour tests)  
**Statut** : Actif ✅

---

## 🌐 URL DE CONNEXION

```
http://localhost/coiffons/access/connexion.php
```

ou

```
http://localhost/coiffons/connexion
```

---

## ✅ VÉRIFICATION DES MOTS DE PASSE

Tous les mots de passe ont été vérifiés avec `password_verify()` :

```
admin@domizi.local .......... ✅ CORRECT
support@domizi.local ........ ✅ CORRECT  
test@domizi.local ........... ✅ CORRECT
```

Hash bcrypt (coût 12) appliqué avec succès.

---

## 🚀 PROCÉDURE DE CONNEXION

### 1. Démarrer XAMPP
```
C:\xampp\xampp-control.exe
→ Cliquer "Start" Apache
→ Cliquer "Start" MySQL
```

### 2. Attendre que les services soient verts
```
Apache : GREEN (Running)
MySQL  : GREEN (Running)
```

### 3. Ouvrir le navigateur
```
http://localhost/coiffons/access/connexion.php
```

### 4. Saisir les identifiants
```
Email    : admin@domizi.local
Password : Admin2026!
```

### 5. Cliquer "Connexion"
```
→ Redirection automatique vers tableau de bord admin
→ URL : http://localhost/coiffons/first/admin_dashboard.php
```

---

## 🔍 DIAGNOSTIC EN CAS DE PROBLÈME

### Si "Identifiants incorrects" s'affiche

**Étape 1** : Vérifier l'orthographe
```
Email    : admin@domizi.local (attention aux points, pas d'espace)
Password : Admin2026! (majuscule A, minuscules, chiffres, point d'exclamation)
```

**Étape 2** : Vérifier que XAMPP est bien démarré
```
Apache doit être GREEN (pas rouge)
MySQL doit être GREEN (pas rouge)
```

**Étape 3** : Exécuter le diagnostic
```
http://localhost/coiffons/diagnostic_project.php
```

**Étape 4** : Réinitialiser les mots de passe
```
http://localhost/coiffons/reset_admin_passwords.php
```

**Étape 5** : Vérifier la base de données
```
URL : http://localhost/phpmyadmin/
Sélectionner base "domizi"
Table "users"
Vérifier que admin@domizi.local existe
```

---

## 📊 STRUCTURE DE LA TABLE USERS

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,      -- Bcrypt hash
  role ENUM('client','prestataire','admin') DEFAULT 'client',
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  statut ENUM('actif','inactif','banni') DEFAULT 'actif',
  sexe ENUM('homme','femme') DEFAULT 'homme',
  telephone VARCHAR(25),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  -- ... (autres colonnes)
);
```

---

## 🔒 SÉCURITÉ DES MOTS DE PASSE

### Hash Bcrypt
- **Algorithme** : PASSWORD_BCRYPT
- **Coût** : 12 (fort)
- **Format** : $2y$12$...
- **Vérification** : password_verify()

### Exemple de hash
```
admin@domizi.local
$2y$12$bEOvRXuMIfJmRb5fWh.xDecc0hpPzKwud1xMNBKdKC/tD/Ko0oucq
```

### Vérification
```php
password_verify('Admin2026!', $hash_bcrypt) // Returns TRUE
```

---

## 📝 FICHIERS ASSOCIÉS

### Scripts
- `generate_admin_credentials.php` → Générer nouveaux comptes
- `reset_admin_passwords.php` → Réinitialiser les mots de passe

### Documentation
- `ADMIN_CREDENTIALS.md` → Documentation complète
- `CONNEXION_CORRECTE.md` → Ce fichier (vérifications)

### Pages de connexion
- `access/connexion.php` → Formulaire de connexion
- `access/deconnexion.php` → Déconnexion

---

## 🧪 TEST DE CONNEXION

### Test avec curl
```bash
curl -X POST http://localhost/coiffons/access/connexion.php \
  -d "email=admin@domizi.local&password=Admin2026!&connexion=1"
```

### Test avec PHP
```php
$pdo = new PDO('mysql:host=localhost;dbname=domizi', 'root', '');
$stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
$stmt->execute(['admin@domizi.local']);
$user = $stmt->fetch();
$verify = password_verify('Admin2026!', $user['password']);
echo $verify ? "✅ CORRECT" : "❌ INCORRECT";
```

---

## ✅ CHECKLIST DE VÉRIFICATION

- [x] XAMPP Apache actif
- [x] XAMPP MySQL actif
- [x] Base de données `domizi` créée
- [x] Table `users` créée
- [x] Comptes admin créés
- [x] Mots de passe hashés en bcrypt
- [x] password_verify() = TRUE pour tous
- [x] Statut des comptes = 'actif'
- [x] Rôle des comptes = 'admin'
- [x] URL de connexion accessible
- [x] Redirection vers tableau de bord admin OK

---

## 🎉 RÉSUMÉ

**Vous pouvez maintenant vous connecter avec :**

```
Email    : admin@domizi.local
Password : Admin2026!
URL      : http://localhost/coiffons/access/connexion.php
```

**Tous les identifiants ont été vérifiés et testés avec succès! ✅**

---

**Créé** : Août 2026  
**Vérifié** : ✅ OUI  
**Status** : 🟢 PRÊT À L'EMPLOI  
