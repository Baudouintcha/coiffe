<?php
require_once 'src/Core/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "=== VÉRIFICATION DES TABLES NÉCESSAIRES ===\n\n";
    
    // Check table otp_codes
    try {
        $result = $pdo->query("SHOW CREATE TABLE otp_codes");
        $exists = $result->fetch();
        echo "[✓] Table otp_codes: EXISTE\n";
    } catch (Exception $e) {
        echo "[✗] Table otp_codes: N'EXISTE PAS\n";
    }
    
    // Check table suppressions_comptes
    try {
        $result = $pdo->query("SHOW CREATE TABLE suppressions_comptes");
        $exists = $result->fetch();
        echo "[✓] Table suppressions_comptes: EXISTE\n";
    } catch (Exception $e) {
        echo "[✗] Table suppressions_comptes: N'EXISTE PAS\n";
    }
    
    // Check table email_actions
    try {
        $result = $pdo->query("SHOW CREATE TABLE email_actions");
        $exists = $result->fetch();
        echo "[✓] Table email_actions: EXISTE\n";
    } catch (Exception $e) {
        echo "[✗] Table email_actions: N'EXISTE PAS\n";
    }
    
    // Check users table columns
    echo "\n=== VÉRIFICATION COLONNES users TABLE ===\n";
    try {
        $result = $pdo->query("SHOW COLUMNS FROM users");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $required = ['email_verifie', 'email_verified_at', 'otp_verified_at'];
        foreach ($required as $col) {
            if (in_array($col, $columns)) {
                echo "[✓] Colonne $col: EXISTE\n";
            } else {
                echo "[✗] Colonne $col: N'EXISTE PAS\n";
            }
        }
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Erreur de connexion: " . $e->getMessage();
}
?>
