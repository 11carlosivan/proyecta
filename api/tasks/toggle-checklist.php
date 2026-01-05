<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? null;
$is_completed = $data['is_completed'] ?? false;

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit();
}

try {
    $query = "UPDATE task_checklist SET is_completed = :is_completed WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':is_completed', $is_completed, PDO::PARAM_BOOL);
    $stmt->bindParam(':id', $item_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>