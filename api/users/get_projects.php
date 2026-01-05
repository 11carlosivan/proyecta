<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get all projects
$query = "SELECT id, name FROM projects ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assigned projects for this user
$query = "SELECT project_id FROM project_members WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$assigned_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success' => true,
    'projects' => $all_projects,
    'assigned_ids' => $assigned_ids
]);
?>