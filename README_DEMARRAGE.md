# 🚀 Démarrage Domizi — Guide Rapide

## ⚡ Démarrage en 5 minutes

### 1️⃣ Vérifier XAMPP

```
C:\xampp\xampp-control.exe
```

Démarrer :
- **Apache** → Click "Start" (vert)
- **MySQL** → Click "Start" (vert)

### 2️⃣ Installer les tables

Ouvrir dans le navigateur :
```
http://localhost/coiffons/install_db.php
```

Vous verrez `✅ Installation complétée! (27 tables créées)`

### 3️⃣ Accéder au projet

```
http://localhost/coiffons/
```

### 4️⃣ Tester l'IA

- Cliquer sur l'orbe ✨ (coin inférieur droit)
- Écrire une question
- L'IA répond!

---

## 📂 Structure du projet

```
C:\xampp\htdocs\coiffons\
├── app.php                    # Application principale
├── index.php                  # Point d'entrée (URL propres)
├── composer.json              # Dépendances PHP
├── .env                       # Config MySQL
├── .htaccess                  # Apache rewrite
│
├── src/                       # Code métier
│   ├── Core/                  # Database, Router, Session
│   ├── Services/              # OTP, Email
│   └── Controllers/           # Contrôleurs
│
├── security/                  # Sécurité des transactions
│   ├── PaymentService.php
│   ├── AuditService.php
│   ├── RateLimitService.php
│   ├── IdentityVerificationService.php
│   ├── IdempotencyService.php
│   ├── ValidationService.php
│   └── SecurePaymentOrchestrator.php
│
├── views/                     # Templates PHP
│   ├── components/ia_assistant.php  # Agent IA
│   ├── client/
│   ├── coiffeur/
│   └── ...
│
├── css/                       # Feuilles de style
├── js/                        # JavaScript
├── uploads/                   # Fichiers uploadés
│
├── diagnostic_project.php     # ✅ Vérifier tout
├── install_db.php             # 📊 Installer la base
├── START.bat                  # 🚀 Démarrage rapide
├── setup_xampp.ps1            # ⚙️ Configuration auto
└── SETUP_XAMPP.md             # 📖 Doc complète
```

---

## 🧪 Tests

### Test 1 : Diagnostic complet

```
http://localhost/coiffons/diagnostic_project.php
```

Vous devez voir tout en ✓ (vert)

### Test 2 : Agent IA

```
http://localhost/coiffons/
```

1. Regarder l'orbe ✨ (bottom-right)
2. Cliquer dessus
3. Écrire : "Bonjour"
4. L'IA répond

### Test 3 : Base de données

```
http://localhost/phpmyadmin/
```

1. Connexion : user=`root`, password=(vide)
2. Sélectionner `domizi`
3. Vérifier 27 tables

---

## 🆘 Dépannage

### "Cannot GET /coiffons/"
- Vérifier que Apache est actif (XAMPP Control Panel)
- Vérifier que `.htaccess` existe
- Relancer Apache

### "Connection refused" (MySQL)
- Vérifier que MySQL est actif
- Relancer MySQL

### L'orbe IA n'apparaît pas
- Aller à : `http://localhost/coiffons/diagnostic_project.php`
- Vérifier que tout est ✓
- Recharger la page (F5)

### Erreur de fichier manquant
- Vérifier que le projet est dans : `C:\xampp\htdocs\coiffons\`
- Vérifier que `install_db.php` existe
- Relancer Apache

### "SQLSTATE[HY000]"
- MySQL n'est pas disponible
- Démarrer MySQL dans XAMPP Control Panel
- Vérifier que la base `domizi` existe

---

## 📝 Configuration

### Fichier .env (MySQL)
```
DB_HOST=localhost
DB_NAME=domizi
DB_USER=root
DB_PASS=
```

### Gemini API (IA)
Clé API actuellement en place : `AIzaSyAHxqVv-NSDE4JYVjl_6ztYtCSVqFTtaPA`

Pour changer la clé, éditer : `ia_controlleur.php` ligne ~180

---

## 🎯 Fonctionnalités testées

- ✅ Connexion MySQL
- ✅ 27 tables créées
- ✅ PHP 8.2+ avec extensions
- ✅ Apache mod_rewrite
- ✅ Agent IA moderne
- ✅ États visuels (Inactif, Écoute, Réflexion, Action, Réponse)
- ✅ Reconnaissance vocale
- ✅ Synthèse vocale
- ✅ Upload photo
- ✅ Permissions d'upload
- ✅ Services de sécurité (Audit, RateLimit, Identity, Idempotency)

---

## 📞 Support

### Vérification rapide
```
http://localhost/coiffons/diagnostic_project.php
```

### Logs Apache
```
C:\xampp\apache\logs\error.log
```

### Logs PHP
```
C:\xampp\php\logs\php_error.log
```

---

## 🎉 Prêt!

Le projet est maintenant entièrement restauré et fonctionnel.

```
http://localhost/coiffons/
```

Bienvenue sur **Domizi** ! 🚀
