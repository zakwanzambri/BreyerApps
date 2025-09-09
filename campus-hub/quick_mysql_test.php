<?php
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    echo "MySQL: Connected\n";
    
    $pdo->exec("USE campus_hub_db");
    echo "Database: campus_hub_db accessible\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "Users table: $count users found\n";
    
} catch(Exception $e) {
    echo "MySQL: Failed - " . $e->getMessage() . "\n";
}
?>
