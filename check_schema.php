<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "--- Users Table ---\n";
$stmt = $db->query("DESCRIBE users");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n--- Tasks Table ---\n";
$stmt = $db->query("DESCRIBE tasks");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>