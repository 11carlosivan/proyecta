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
$hours = $data['hours'] ?? null;
$logged_at = $data['logged_at'] ?? date('Y-m-d');
$description = $data['description'] ?? '';

if (!$task_id || !$hours) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit();
}

try {
    $query = "INSERT INTO task_time_logs (task_id, user_id, hours, logged_at, description) 
              VALUES (:task_id, :user_id, :hours, :logged_at, :description)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':hours', $hours);
    $stmt->bindParam(':logged_at', $logged_at);
    $stmt->bindParam(':description', $description);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar tiempo']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>