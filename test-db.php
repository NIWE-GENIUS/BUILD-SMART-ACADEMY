<?php
// test-db.php
// Test database connection

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>Testing Database Connection</h1>";

try {
    $db = Database::getConnection();
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";
    
    // Test query
    $stmt = $db->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll();
    
    echo "<p>Found " . count($tables) . " tables in database.</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . implode('', $table) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>