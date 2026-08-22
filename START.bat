@echo off
REM START.bat — Démarrage rapide de Domizi avec XAMPP
REM À lancer APRÈS avoir démarré Apache et MySQL dans XAMPP Control Panel

cls
echo ════════════════════════════════════════════════════════════════
echo     DOMIZI — Plateforme de services à domicile
echo ════════════════════════════════════════════════════════════════
echo.
echo Vérification du statut XAMPP...
echo.

REM Vérifier si les fichiers critiques existent
if not exist "C:\xampp\apache\bin\httpd.exe" (
    echo ❌ XAMPP Apache non trouvé
    echo    Installer XAMPP depuis https://www.apachefriends.org/
    pause
    exit /b 1
)

if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo ❌ XAMPP MySQL non trouvé
    echo    Installer XAMPP depuis https://www.apachefriends.org/
    pause
    exit /b 1
)

echo ✓ XAMPP détecté
echo.

REM Vérifier la base de données
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT VERSION();" >nul 2>&1
if errorlevel 1 (
    echo ⚠️  MySQL n'est pas accessible
    echo    Assurez-vous que MySQL est démarré dans XAMPP Control Panel
    echo.
    echo    Démarrer Apache et MySQL :
    echo    1. Ouvrir : C:\xampp\xampp-control.exe
    echo    2. Cliquer "Start" pour Apache
    echo    3. Cliquer "Start" pour MySQL
    echo.
    pause
    exit /b 1
)

echo ✓ MySQL est accessible
echo.

REM Créer la base et les tables si nécessaire
echo Installation de la base de données...
C:\xampp\php\php.exe "C:\xampp\htdocs\coiffons\install_db.php" >nul 2>&1

echo.
echo ════════════════════════════════════════════════════════════════
echo ✅ DÉMARRAGE RÉUSSI
echo ════════════════════════════════════════════════════════════════
echo.
echo 🌐 Accès au projet :
echo    http://localhost/coiffons/
echo.
echo 📊 Diagnostic :
echo    http://localhost/coiffons/diagnostic_project.php
echo.
echo 🗄️  PhpMyAdmin :
echo    http://localhost/phpmyadmin/
echo.
echo ⚠️  IMPORTANT :
echo    - Apache doit être en cours d'exécution
echo    - MySQL doit être en cours d'exécution
echo    - Si ce n'est pas le cas, démarrer XAMPP Control Panel
echo.

REM Ouvrir le navigateur
echo Ouverture de http://localhost/coiffons/ dans le navigateur...
echo.
timeout /t 2 /nobreak

REM Lancer le navigateur (Edge > Chrome > Firefox > IE)
if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" "http://localhost/coiffons/"
) else if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" "http://localhost/coiffons/"
) else if exist "C:\Program Files\Mozilla Firefox\firefox.exe" (
    start "" "C:\Program Files\Mozilla Firefox\firefox.exe" "http://localhost/coiffons/"
) else (
    start "" "http://localhost/coiffons/"
)

echo.
echo Si le navigateur ne s'ouvre pas, aller manuellement à :
echo http://localhost/coiffons/
echo.
pause
