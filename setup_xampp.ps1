################################################################################
# setup_xampp.ps1 — Automation du setup Domizi sur XAMPP
# Usage : 
#   PowerShell.exe -ExecutionPolicy Bypass -File setup_xampp.ps1
#
# À exécuter EN TANT QU'ADMINISTRATEUR
################################################################################

param(
    [switch]$SkipMySQL = $false,
    [switch]$SkipApache = $false
)

Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║       SETUP DOMIZI — Automatisation XAMPP                     ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# ─────────────────────────────────────────────────────────────────────────────
# 1. VÉRIFICATIONS PRÉALABLES
# ─────────────────────────────────────────────────────────────────────────────

Write-Host "1️⃣  Vérifications préalables..." -ForegroundColor Yellow

$xampp_path = "C:\xampp"
$project_path = "$xampp_path\htdocs\coiffons"

if (!(Test-Path $xampp_path)) {
    Write-Host "❌ XAMPP non trouvé à : $xampp_path" -ForegroundColor Red
    exit 1
}
Write-Host "✓ XAMPP trouvé" -ForegroundColor Green

if (!(Test-Path $project_path)) {
    Write-Host "❌ Projet non trouvé à : $project_path" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Projet Domizi trouvé" -ForegroundColor Green

# ─────────────────────────────────────────────────────────────────────────────
# 2. APACHE — Configuration mod_rewrite
# ─────────────────────────────────────────────────────────────────────────────

if (!$SkipApache) {
    Write-Host ""
    Write-Host "2️⃣  Configuration Apache..." -ForegroundColor Yellow
    
    $httpd_conf = "$xampp_path\apache\conf\httpd.conf"
    
    if (Test-Path $httpd_conf) {
        $content = Get-Content $httpd_conf -Raw
        
        # Vérifier et activer mod_rewrite
        if ($content -match "#LoadModule rewrite_module modules/mod_rewrite.so") {
            Write-Host "  - Activation de mod_rewrite..." -ForegroundColor Gray
            $content = $content -replace "#LoadModule rewrite_module modules/mod_rewrite.so", "LoadModule rewrite_module modules/mod_rewrite.so"
            Set-Content $httpd_conf -Value $content -Encoding UTF8
            Write-Host "    ✓ mod_rewrite activé" -ForegroundColor Green
        } else {
            Write-Host "    ✓ mod_rewrite déjà actif" -ForegroundColor Green
        }
        
        # Vérifier AllowOverride
        if ($content -match 'AllowOverride All') {
            Write-Host "    ✓ AllowOverride All configuré" -ForegroundColor Green
        } else {
            Write-Host "    ⚠️  AllowOverride peut nécessiter configuration manuelle" -ForegroundColor Yellow
        }
    } else {
        Write-Host "  ❌ httpd.conf non trouvé" -ForegroundColor Red
    }
}

# ─────────────────────────────────────────────────────────────────────────────
# 3. MYSQL — Vérification et création de la base
# ─────────────────────────────────────────────────────────────────────────────

if (!$SkipMySQL) {
    Write-Host ""
    Write-Host "3️⃣  Configuration MySQL..." -ForegroundColor Yellow
    
    $mysql_bin = "$xampp_path\mysql\bin\mysql.exe"
    
    if (!(Test-Path $mysql_bin)) {
        Write-Host "  ❌ MySQL non trouvé" -ForegroundColor Red
    } else {
        Write-Host "  - Vérification de la base domizi..." -ForegroundColor Gray
        
        # Vérifier si la base existe
        $check_cmd = & $mysql_bin -u root -e "SHOW DATABASES;" 2>&1
        
        if ($check_cmd -match "domizi") {
            Write-Host "    ✓ Base domizi existe" -ForegroundColor Green
            Write-Host "    ✓ Vous pouvez installer les tables via :" -ForegroundColor Cyan
            Write-Host "       http://localhost/coiffons/install_db.php" -ForegroundColor Cyan
        } else {
            Write-Host "    - Création de la base domizi..." -ForegroundColor Gray
            & $mysql_bin -u root -e "CREATE DATABASE IF NOT EXISTS domizi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" 2>&1
            Write-Host "    ✓ Base domizi créée" -ForegroundColor Green
            Write-Host "    - Installation des tables..." -ForegroundColor Gray
            Write-Host "      (À faire via : http://localhost/coiffons/install_db.php)" -ForegroundColor Gray
        }
    }
}

# ─────────────────────────────────────────────────────────────────────────────
# 4. PHP — Vérification des extensions
# ─────────────────────────────────────────────────────────────────────────────

Write-Host ""
Write-Host "4️⃣  Vérification PHP..." -ForegroundColor Yellow

$php_bin = "$xampp_path\php\php.exe"

if (Test-Path $php_bin) {
    $php_version = & $php_bin -v | Select-Object -First 1
    Write-Host "  ✓ $php_version" -ForegroundColor Green
    
    # Vérifier les extensions
    $extensions_needed = @('pdo', 'pdo_mysql', 'mbstring', 'curl', 'json', 'gd')
    
    foreach ($ext in $extensions_needed) {
        $ext_check = & $php_bin -m | Select-String $ext
        if ($ext_check) {
            Write-Host "    ✓ Extension $ext" -ForegroundColor Green
        } else {
            Write-Host "    ❌ Extension $ext manquante" -ForegroundColor Red
        }
    }
} else {
    Write-Host "  ❌ PHP non trouvé" -ForegroundColor Red
}

# ─────────────────────────────────────────────────────────────────────────────
# 5. PERMISSIONS
# ─────────────────────────────────────────────────────────────────────────────

Write-Host ""
Write-Host "5️⃣  Vérification des permissions..." -ForegroundColor Yellow

$dirs_check = @(
    "$project_path\uploads",
    "$project_path\access\uploads"
)

foreach ($dir in $dirs_check) {
    if (Test-Path $dir) {
        # Tenter d'écrire un fichier test
        $test_file = "$dir\test_write.tmp"
        try {
            "test" | Out-File -FilePath $test_file -Encoding UTF8 -ErrorAction Stop
            Remove-Item $test_file -Force -ErrorAction SilentlyContinue
            Write-Host "  ✓ $dir accessible en écriture" -ForegroundColor Green
        } catch {
            Write-Host "  ❌ $dir NOT accessible en écriture" -ForegroundColor Red
        }
    } else {
        Write-Host "  ⚠️  Dossier n'existe pas : $dir" -ForegroundColor Yellow
    }
}

# ─────────────────────────────────────────────────────────────────────────────
# 6. RÉSUMÉ
# ─────────────────────────────────────────────────────────────────────────────

Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "✅ SETUP TERMIÉ" -ForegroundColor Green
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan

Write-Host ""
Write-Host "📋 ÉTAPES SUIVANTES :" -ForegroundColor Yellow
Write-Host ""
Write-Host "  1️⃣  Ouvrir XAMPP Control Panel" -ForegroundColor Cyan
Write-Host "      C:\xampp\xampp-control.exe" -ForegroundColor Gray
Write-Host ""
Write-Host "  2️⃣  Démarrer Apache et MySQL" -ForegroundColor Cyan
Write-Host ""
Write-Host "  3️⃣  Créer les tables de la base de données" -ForegroundColor Cyan
Write-Host "      http://localhost/coiffons/install_db.php" -ForegroundColor Gray
Write-Host ""
Write-Host "  4️⃣  Accéder au projet" -ForegroundColor Cyan
Write-Host "      http://localhost/coiffons/" -ForegroundColor Gray
Write-Host ""
Write-Host "  5️⃣  Tester le diagnostic" -ForegroundColor Cyan
Write-Host "      http://localhost/coiffons/diagnostic_project.php" -ForegroundColor Gray
Write-Host ""

Write-Host "📖 Documentation complète :" -ForegroundColor Yellow
Write-Host "   $project_path\SETUP_XAMPP.md" -ForegroundColor Gray
Write-Host ""

Write-Host "✨ Domizi est prêt! 🚀" -ForegroundColor Green
