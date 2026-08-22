# 🚀 DÉPLOIEMENT SUR INFINITYFREE — GUIDE COMPLET

**Plateforme** : InfinityFree (Hosting gratuit)  
**Langage** : PHP 8.x  
**Base de données** : MySQL  
**Date** : Août 2026  

---

## 📋 PRÉREQUIS

- ✅ Un compte email (Gmail, Yahoo, Outlook, etc.)
- ✅ Un navigateur web
- ✅ Accès FTP (FileZilla ou similaire)
- ✅ Votre projet Domizi complet
- ⏱️ Temps estimé : 20-30 minutes

---

## ÉTAPE 1 : CRÉER UN COMPTE INFINITYFREE

### 1.1 Accès au site

1. Aller sur **https://www.infinityfree.net/**
2. Cliquer sur **"Sign Up"** (en haut à droite)
3. Remplir le formulaire :
   - Email : votre adresse email
   - Mot de passe : création sécurisée
   - Répéter mot de passe

### 1.2 Vérification email

1. InfinityFree vous envoie un email de confirmation
2. Cliquer sur le lien de confirmation dans l'email
3. Votre compte est activé ✅

---

## ÉTAPE 2 : CRÉER UN ESPACE D'HÉBERGEMENT

### 2.1 Accéder au tableau de bord

1. Connexion avec vos identifiants
2. Vous verrez le dashboard InfinityFree
3. Chercher **"Create Account"** ou **"New Account"**

### 2.2 Configuration du compte

**Remplir les informations** :

```
Account name (nom de domaine gratuit) :
  → Exemple : coiffetoi.infinityfreeapp.com
  → Format : [votre_choix].infinityfreeapp.com

Label (description) :
  → "Coiffe Chez Toi - Plateforme Services"

Password (accès FTP/cPanel) :
  → Créer un mot de passe robuste
```

### 2.3 Confirmation

Cliquer **"Create Account"** → Attendre quelques secondes → ✅ Compte créé

**À retenir** :
- Domaine : `coiffetoi.infinityfreeapp.com` (ou votre choix)
- Utilisateur FTP : `[votre_choix]@coiffetoi.infinityfreeapp.com`
- Mot de passe FTP : celui que vous avez défini

---

## ÉTAPE 3 : ACCÉDER À CPANEL

### 3.1 Ouvrir cPanel

1. Dans le dashboard InfinityFree, cliquer sur votre compte
2. Cliquer sur **"cPanel"** (accès au panneau de contrôle)
3. Vous êtes maintenant dans le cPanel

### 3.2 Parcourir les outils

Vous verrez plusieurs sections :
- **Files** (fichiers/FTP)
- **Databases** (MySQL)
- **Email** (boîtes email)
- **Domains** (domaines)

---

## ÉTAPE 4 : CRÉER LA BASE DE DONNÉES MYSQL

### 4.1 Créer la base

1. Dans cPanel, chercher **"MySQL Databases"** ou **"Databases"**
2. Cliquer sur **"MySQL Databases"**
3. Remplir :
   ```
   New Database Name : domizi
   ```
4. Cliquer **"Create Database"** → ✅ Base créée

**À retenir** :
```
Nom de base : domizi
Serveur : localhost (ou db.infinityfreeapp.com)
```

### 4.2 Créer un utilisateur MySQL

1. Dans la même page, chercher **"MySQL Users"**
2. Remplir :
   ```
   Username : admin_coiffons
   Password : (créer un mot de passe robuste)
   ```
3. Cliquer **"Create User"** → ✅ Utilisateur créé

**À retenir** :
```
User : admin_coiffons
Pass : [votre_mot_de_passe]
```

### 4.3 Ajouter les privilèges

1. Chercher **"Add User to Database"**
2. Sélectionner :
   - User : `admin_coiffons`
   - Database : `domizi`
3. Cliquer **"Add"**
4. Sélectionner **"All Privileges"** → **"Make Changes"** → ✅

---

## ÉTAPE 5 : IMPORTER LA BASE DE DONNÉES

### 5.1 Exporter la BD locale (XAMPP)

**Sur votre ordinateur** :

1. Ouvrir **phpMyAdmin** (http://localhost/phpmyadmin)
2. Sélectionner base : `domizi`
3. Cliquer onglet **"Export"**
4. Format : **"SQL"**
5. Cliquer **"Go"** → Fichier `domizi.sql` téléchargé ✅

### 5.2 Importer dans InfinityFree

1. Dans cPanel InfinityFree, chercher **"phpMyAdmin"**
2. Cliquer pour ouvrir phpMyAdmin
3. Login :
   ```
   User : admin_coiffons
   Pass : [votre_mot_de_passe_MySQL]
   ```
4. Sélectionner base `domizi` (déjà créée)
5. Cliquer onglet **"Import"**
6. **"Choose File"** → Sélectionner `domizi.sql`
7. Cliquer **"Go"** → ✅ Tables importées

**À ce stade** : La base de données complète est en ligne !

---

## ÉTAPE 6 : UPLOADER LES FICHIERS PHP

### 6.1 Accéder au FTP

**Option 1 : Via cPanel (File Manager)**

1. Dans cPanel, cliquer **"File Manager"**
2. Double-clic sur dossier **"public_html"**
3. C'est ici qu'on upload les fichiers

**Option 2 : Via FTP Client (FileZilla - Recommandé)**

Télécharger FileZilla : https://filezilla-project.org/

Configuration :
```
Host : ftp.coiffetoi.infinityfreeapp.com
User : [votre_choix]@coiffetoi.infinityfreeapp.com
Pass : [votre_mot_de_passe_FTP]
Port : 21
```

Cliquer **"Quickconnect"** → ✅ Connecté

### 6.2 Uploader les fichiers

**Structure à uploader** :

```
public_html/
├── coiffons/                    ← Dossier principal
│   ├── index.php
│   ├── app.php
│   ├── security/
│   │   ├── config.php           ← À MODIFIER
│   │   ├── PaymentService.php
│   │   └── ...
│   ├── src/
│   ├── views/
│   ├── css/
│   ├── js/
│   ├── first/
│   │   ├── admin_dashboard.php
│   │   └── admin_actions.php
│   ├── access/
│   └── ...
```

**Via File Manager (cPanel)** :
1. Upload ZIP de `coiffons/` → Décompresser
2. Ou upload dossier par dossier

**Via FileZilla** :
1. Glisser-déposer le dossier `coiffons/` vers `public_html/`
2. Attendre la synchronisation ✅

---

## ÉTAPE 7 : MODIFIER LE FICHIER config.php

### 7.1 Fichier à modifier

**Chemin** : `public_html/coiffons/security/config.php`

### 7.2 Modification des paramètres

**Avant (XAMPP local)** :
```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'domizi';
```

**Après (InfinityFree)** :
```php
$db_host = 'localhost'; // OU db.infinityfreeapp.com
$db_user = 'admin_coiffons';      // ← Votre user MySQL
$db_pass = '[votre_mot_de_passe]'; // ← Votre pass MySQL
$db_name = 'domizi';               // ← Nom base (resté pareil)
```

### 7.3 Appliquer la modification

**Via cPanel File Manager** :
1. Cliquer droit sur `config.php` → **"Edit"**
2. Modifier les lignes ci-dessus
3. Cliquer **"Save"** → ✅

**Via FileZilla** :
1. Télécharger `config.php`
2. Modifier dans un éditeur (Notepad++, VS Code)
3. Réuploader en écrasant

---

## ÉTAPE 8 : CONFIGURER L'UPLOAD DE FICHIERS

### 8.1 Créer les dossiers

**Via File Manager** :

1. Naviguer à `public_html/coiffons/`
2. Créer les dossiers :
   ```
   access/uploads/diplomes/
   access/uploads/profil/
   images/
   imgid/
   uploads/
   ```

3. **Permissions** (Important !) :
   - Cliquer droit sur chaque dossier
   - **"Change Permissions"**
   - Entrer **755** (ou **777**)
   - **"Change Permissions"** → ✅

### 8.2 Créer fichier .htaccess (optionnel)

Si vous voulez les URLs sans `.php` :

Créer fichier `public_html/coiffons/.htaccess` :
```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^\.]+)$ $1.php [NC,L]
</IfModule>
```

---

## ÉTAPE 9 : TESTER L'ACCÈS

### 9.1 Vérifier l'installation

Ouvrir navigateur → **https://coiffetoi.infinityfreeapp.com/coiffons/**

**Vous devriez voir** :
- Page d'accueil Domizi
- Interface responsive
- Connexion active

### 9.2 Tester la connexion

**Identifiants** :
```
Email    : admin@domizi.local
Password : Admin2026!
```

Si ça marche → ✅ **SUCCÈS !**

Si erreur de base de données :
- Vérifier `config.php`
- Vérifier credentials MySQL
- Vérifier import de base

---

## ÉTAPE 10 : PROBLÈMES COURANTS & SOLUTIONS

### ❌ Erreur "Unable to connect to database"

**Cause** : Identifiants MySQL incorrects

**Solution** :
1. Vérifier dans cPanel → MySQL Users
2. Copier exactement : user + pass
3. Mettre à jour `config.php`
4. Télécharger à nouveau

### ❌ Les images ne s'affichent pas

**Cause** : Chemins incorrects

**Solution** :
1. Modifier `config.php` ou fichiers PHP
2. Remplacer chemins locaux par chemins web :
   ```php
   // Avant
   '/uploads/profil/...'
   
   // Après
   'https://coiffetoi.infinityfreeapp.com/coiffons/uploads/profil/...'
   ```

### ❌ Upload de fichiers ne fonctionne pas

**Cause** : Permissions insuffisantes

**Solution** :
1. Cliquer droit sur dossier `uploads/`
2. Change Permissions → **777**
3. Réessayer

### ❌ "Access forbidden" (Error 403)

**Cause** : Permissions incorrectes ou fichier index manquant

**Solution** :
1. Vérifier fichier `index.php` existe
2. Vérifier permissions : **755** ou **644**
3. Créer `index.php` s'il manque

### ❌ Lenteurs / Timeout

**Cause** : InfinityFree a des limitations (hosting gratuit)

**Solution** :
- Passer à un hosting payant (Hostinger, O2switch, etc.)
- Ou optimiser : images compressées, cache PHP

---

## 🎯 RÉSUMÉ DU PROCESSUS

```
1. Créer compte InfinityFree          (5 min)
2. Créer compte d'hébergement         (2 min)
3. Créer base MySQL + user            (3 min)
4. Importer BD locale                 (2 min)
5. Uploader fichiers PHP              (5 min)
6. Modifier config.php                (2 min)
7. Configurer permissions/uploads     (3 min)
8. Tester l'accès                     (1 min)
───────────────────────────────────────────
Total : 20-25 minutes ✅
```

---

## 📌 INFORMATIONS À GARDER

Sauvegardez dans un fichier sécurisé :

```
INFINITYFREE DEPLOYMENT

Domaine public : coiffetoi.infinityfreeapp.com
Dossier racine : public_html/coiffons/

FTP Credentials :
  Host : ftp.coiffetoi.infinityfreeapp.com
  User : [votre_choix]@coiffetoi.infinityfreeapp.com
  Pass : [votre_mot_de_passe_FTP]
  Port : 21

MySQL Credentials :
  Host : localhost (ou db.infinityfreeapp.com)
  User : admin_coiffons
  Pass : [votre_mot_de_passe_MySQL]
  DB   : domizi

Admin Login :
  Email    : admin@domizi.local
  Password : Admin2026!
```

---

## 🚀 APRÈS LE DÉPLOIEMENT

### Configuration supplémentaire

1. **Emails** : Configurer SMTP pour notifications
2. **SSL** : Activer certificat HTTPS (gratuit)
3. **Backups** : Télécharger backups réguliers
4. **Monitoring** : Surveiller espace disque / bande passante

### Limitations InfinityFree

```
✅ Espace disque : 5 GB
✅ Bande passante : Illimitée
✅ Bases de données : 1
✅ Comptes FTP : 1
✅ Emails : 1

❌ Pas d'accès root
❌ Pas d'installation personnalisée
❌ Pub InfinityFree (gratuit)
❌ Lenteurs possibles (serveur shared)
```

---

## 💡 ALTERNATIVES PAYANTES (Recommandé pour production)

Si vous voulez meilleures performances :

| Hosting | Prix/mois | CPU | RAM | Support |
|---------|-----------|-----|-----|---------|
| Hostinger | €3-8 | Bon | 1GB+ | ✅ 24/7 |
| O2switch | €5-10 | Très bon | 2GB+ | ✅ Français |
| Bluehost | $3-7 | Moyen | 512MB | ✅ US |
| SiteGround | $3-7 | Excellent | 1GB+ | ✅ Premium |

---

## ✅ CHECKLIST FINAL

Avant de passer en production :

- [ ] Compte InfinityFree créé
- [ ] Hébergement activé
- [ ] Base de données MySQL importée
- [ ] Fichiers PHP uploadés
- [ ] config.php modifié (credentials MySQL)
- [ ] Dossiers uploads créés avec permissions 755/777
- [ ] Test login admin : ✅ Fonctionne
- [ ] Test création RDV : ✅ Fonctionne
- [ ] Test paiement KkiaPay : ✅ (ou test offline)
- [ ] URL publique accessible : ✅

**VOUS ÊTES EN PRODUCTION ! 🎉**

---

## 📞 SUPPORT

Si vous avez des problèmes :

1. **Erreurs PHP** → Vérifier logs cPanel
2. **BD inaccessible** → Vérifier config.php
3. **Uploads** → Vérifier permissions
4. **Lenteurs** → Contact support InfinityFree

**Documentation InfinityFree** : https://docs.infinityfree.net/

**Forums d'entraide** : https://forum.infinityfree.net/
