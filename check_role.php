<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Role Type: " . $row['Type'] . "\n";
?>