# 🚀 SETUP COMPLET — Domizi sur XAMPP

## 📋 Prérequis

- XAMPP 8.2+ (PHP 8.2, MySQL, Apache)
- Windows 10+
- Admin privileges (pour les services Windows)

---

## ✅ ÉTAPE 1 : Vérification de XAMPP

### 1.1 Démarrer XAMPP Control Panel
```
C:\xampp\xampp-control.exe
```

### 1.2 Démarrer les services
- **Apache** → Cliquer "Start" (doit être vert)
- **MySQL** → Cliquer "Start" (doit être vert)

### 1.3 Tester l'accès
Ouvrir le navigateur et aller à :
```
http://localhost/dashboard/
```

Vous devez voir le dashboard XAMPP (bonne nouvelle = XAMPP fonctionne).

---

## 📂 ÉTAPE 2 : Vérifier l'emplacement du projet

Le projet doit être à :
```
C:\xampp\htdocs\coiffons\
```

### Vérification rapide via PowerShell
```powershell
cd C:\xampp\htdocs\coiffons
Get-ChildItem | Select-Object Name
```

Vous devez voir : `app.php`, `index.php`, `composer.json`, `src/`, `views/`, etc.

---

## 🗄️ ÉTAPE 3 : Configuration MySQL

### 3.1 Accéder à PhpMyAdmin
```
http://localhost/phpmyadmin/
```

### 3.2 Créer la base de données domizi (si elle n'existe pas)
1. Cliquer "Nouveau" dans le panneau gauche
2. Nom de base : `domizi`
3. Collation : `utf8mb4_general_ci`
4. Cliquer "Créer"

### 3.3 Restaurer les tables
1. Cliquer sur la base `domizi`
2. Aller à l'onglet "Importer"
3. Choisir le fichier : `security/domizi_v2.sql`
4. Cliquer "Exécuter"

**OU via PHP** :
```
http://localhost/coiffons/install_db.php
```

---

## 🔧 ÉTAPE 4 : Configuration PHP

### 4.1 Vérifier php.ini (C:\xampp\php\php.ini)

Les extensions suivantes doivent être actives :
```ini
extension=pdo
extension=pdo_mysql
extension=mbstring
extension=curl
extension=json
extension=gd
```

Rechercher ces lignes et enlever le `;` s'il y en a un (décommentage).

### 4.2 Redémarrer Apache après modification
```
XAMPP Control Panel → Apache → Stop → Start
```

---

## 🌐 ÉTAPE 5 : Configuration Apache

### 5.1 Vérifier mod_rewrite (pour les URL propres)

Fichier : `C:\xampp\apache\conf\httpd.conf`

Chercher la ligne :
```apache
#LoadModule rewrite_module modules/mod_rewrite.so
```

Enlever le `#` pour l'activer :
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

### 5.2 Vérifier AllowOverride

Chercher :
```apache
<Directory "C:/xampp/htdocs">
```

S'assurer que la ligne suivante existe :
```apache
AllowOverride All
```

### 5.3 Redémarrer Apache
```
XAMPP Control Panel → Apache → Stop → Start
```

---

## 🚀 ÉTAPE 6 : Accéder au projet

### URL de base
```
http://localhost/coiffons/
```

### Différentes pages
- **Accueil** → `http://localhost/coiffons/`
- **Connexion** → `http://localhost/coiffons/index.php?page=login`
- **Inscription** → `http://localhost/coiffons/index.php?page=register`
- **Annuaire** → `http://localhost/coiffons/filter/annuaire_coiffeurs.php`

---

## 📊 ÉTAPE 7 : Diagnostic

Exécuter le script de diagnostic :
```
http://localhost/coiffons/diagnostic_project.php
```

Vous devez voir :
- ✓ Tous les fichiers critiques
- ✓ Base de données connectée
- ✓ 27 tables créées
- ✓ PHP 8.2+ avec toutes les extensions
- ✓ Permissions correctes

---

## 🧪 ÉTAPE 8 : Tester les fonctionnalités

### Test 1 : Agent IA
1. Aller à `http://localhost/coiffons/`
2. Regarder le coin inférieur droit
3. Cliquer sur l'orbe lumineux ✨
4. La fenêtre de chat doit s'ouvrir
5. Tester un message

### Test 2 : Base de données
1. Aller à `http://localhost/phpmyadmin/`
2. Sélectionner `domizi`
3. Vérifier les tables (27 au total)

### Test 3 : Sécurité
```
http://localhost/coiffons/diagnostic_project.php
```

---

## ⚙️ ÉTAPE 9 : Configuration optionnelle

### Port Apache personnalisé (si 80 est occupé)
Fichier : `C:\xampp\apache\conf\httpd.conf`

Ligne :
```apache
Listen 80
```

Changer en (ex: 8080) :
```apache
Listen 8080
```

Puis accéder via : `http://localhost:8080/coiffons/`

### Port MySQL personnalisé (si 3306 est occupé)
Fichier : `C:\xampp\mysql\bin\my.ini`

Ligne :
```ini
port=3306
```

Changer en (ex: 3307) :
```ini
port=3307
```

Puis dans `.env` :
```env
DB_HOST=localhost:3307
```

---

## 🆘 Dépannage

### Erreur "Cannot GET /coiffons/"
→ Vérifier que Apache est actif et que mod_rewrite est actif

### Erreur "Connection refused" MySQL
→ Vérifier que MySQL est actif dans XAMPP Control Panel

### Erreur "File not found" sur public/index.php
→ Vérifier que le dossier `public/` existe et contient `index.php`

### 404 sur les ressources (CSS, JS)
→ Vérifier que `.htaccess` permet les fichiers réels (`!-f` et `!-d`)

### PHP errors
→ Vérifier `C:\xampp\apache\logs\error.log` pour plus de détails

---

## 📝 Notes importantes

1. **XAMPP doit toujours être lancé** pour que le site fonctionne
2. **La base domizi doit exister** avec les 27 tables
3. **Les fichiers dans `uploads/` doivent être accessibles en écriture**
4. **PHP 8.2+ est obligatoire** (PDO type hints)
5. **Composer dependencies** sont déjà en place dans `vendor/`

---

## ✅ Checklist finale

- [ ] XAMPP est installé (8.2+)
- [ ] Apache est actif
- [ ] MySQL est actif
- [ ] Dossier `C:\xampp\htdocs\coiffons\` existe
- [ ] Base de données `domizi` créée
- [ ] 27 tables présentes
- [ ] `.htaccess` est en place
- [ ] `mod_rewrite` est actif
- [ ] `AllowOverride All` est configuré
- [ ] Extensions PHP (pdo, pdo_mysql, mbstring, curl) sont actives
- [ ] `http://localhost/coiffons/` est accessible
- [ ] Diagnostic affiche tous les ✓

---

**Vous êtes prêt! 🚀**

Pour toute question, consulter le diagnostic : `http://localhost/coiffons/diagnostic_project.php`
