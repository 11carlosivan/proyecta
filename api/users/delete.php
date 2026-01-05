<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Solo administradores pueden eliminar usuarios
requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->user_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado']);
        exit;
    }

    // Evitar auto-eliminación
    if ($data->user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el usuario tiene tareas asignadas
    $query = "SELECT COUNT(*) as tasks FROM tasks WHERE assignee_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['tasks'] > 0) {
        echo json_encode(['success' => false, 'message' => 'No se puede eliminar el usuario porque tiene tareas asignadas. Reasígnalas primero.']);
        exit;
    }

    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
    }
}
?>