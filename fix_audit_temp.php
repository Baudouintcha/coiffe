<?php
$content = file_get_contents('c:/xampp/htdocs/coiffons/audit_sql_columns.php');
$content = str_replace(
    "require_once 'src/Core/Database.php';",
    "require_once __DIR__ . '/vendor/autoload.php';" . PHP_EOL . "use App\\Core\\Database;",
    $content
);
$content = str_replace(
    "$db = new Database();" . PHP_EOL . "    $pdo = $db->getConnection();",
    "$pdo = Database::getInstance();",
    $content
);
file_put_contents('c:/xampp/htdocs/coiffons/audit_sql_columns.php', $content);
echo 'Fixed!';
