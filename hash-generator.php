<?php
// hash-generator.php
// Generate correct password hash for Irut@200206

$password = 'Irut@200206';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br>";
echo "<hr>";
echo "Copy this hash into your SQL: <br>";
echo "<textarea rows='3' style='width:100%; font-family: monospace;'>" . $hash . "</textarea>";
?>