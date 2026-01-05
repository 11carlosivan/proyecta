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
$task_id = $data['task_id'] ?? null;
$title = $data['title'] ?? '';

if (!$task_id || empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit();
}

try {
    // Obtener la posición máxima actual
    $query = "SELECT COALESCE(MAX(position), 0) + 1 as next_position FROM task_checklist WHERE task_id = :task_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->execute();
    $position = $stmt->fetch(PDO::FETCH_ASSOC)['next_position'];

    $query = "INSERT INTO task_checklist (task_id, title, position) VALUES (:task_id, :title, :position)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':position', $position);

    if ($stmt->execute()) {
        $item_id = $db->lastInsertId();
        echo json_encode(['success' => true, 'item_id' => $item_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear item']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>