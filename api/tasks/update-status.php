<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/activity_helper.php';

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
$status = $data['status'] ?? null;

if (!$task_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit();
}

// Validar estado
$allowed_statuses = ['todo', 'in_progress', 'review', 'done'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit();
}

try {
    $query = "UPDATE tasks SET status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $task_id);

    if ($stmt->execute()) {
        // Obtener información de la tarea para el log
        $query = "SELECT t.title, t.project_id, t.status FROM tasks t WHERE t.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $task_id);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task && $task['project_id']) {
            $status_labels = [
                'todo' => 'Pendiente',
                'in_progress' => 'En Progreso',
                'review' => 'En Revisión',
                'done' => 'Completado'
            ];

            $action_type = ($status === 'done') ? 'completed' : 'status_changed';
            $description = "cambió el estado de \"" . $task['title'] . "\" a " . $status_labels[$status];

            logProjectActivity(
                $db,
                $task['project_id'],
                $_SESSION['user_id'],
                $action_type,
                'task',
                $task_id,
                $description,
                [
                    'task_title' => $task['title'],
                    'new_status' => $status_labels[$status]
                ]
            );
        }

        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>