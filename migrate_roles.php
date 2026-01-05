<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Modify users table to add new enum values
    // We retain existing values and add new ones
    $query = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'member', 'collaborator', 'client', 'viewer', 'editor') DEFAULT 'member'";
    $db->exec($query);
    echo "Successfully updated users table role column.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>