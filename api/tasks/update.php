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

// Get POST data
$task_id = $_POST['task_id'] ?? null;
$title = sanitize($_POST['title'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$status = $_POST['status'] ?? null;
$priority = $_POST['priority'] ?? null;
$due_date = $_POST['due_date'] ?? null;
$estimated_hours = $_POST['estimated_hours'] ?? null;
$tags = isset($_POST['tags']) ? json_decode($_POST['tags'], true) : [];

if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'ID de tarea requerido']);
    exit();
}

// CHECK LOCK STATUS
$stmt = $db->prepare("SELECT status FROM tasks WHERE id = :id");
$stmt->bindParam(':id', $task_id);
$stmt->execute();
$current_task = $stmt->fetch(PDO::FETCH_ASSOC);

if ($current_task && $current_task['status'] === 'done' && $status === 'done' && $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'La tarea está completada y no se puede editar.']);
    exit();
}

// Prepare update query
$query = "UPDATE tasks SET 
          title = :title, 
          description = :description, 
          status = :status, 
          priority = :priority, 
          due_date = :due_date,
          estimated_hours = :estimated_hours,
          updated_at = NOW()
          WHERE id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':priority', $priority);
$stmt->bindParam(':due_date', $due_date);
$stmt->bindParam(':estimated_hours', $estimated_hours);
$stmt->bindParam(':id', $task_id);

try {
    if ($stmt->execute()) {
        // Handle Tags
        // First delete existing tags
        $delete_tags = "DELETE FROM task_tags WHERE task_id = :task_id";
        $del_stmt = $db->prepare($delete_tags);
        $del_stmt->bindParam(':task_id', $task_id);
        $del_stmt->execute();

        // Insert new tags
        if (!empty($tags)) {
            $insert_tag = "INSERT INTO task_tags (task_id, tag_id) VALUES (:task_id, :tag_id)";
            $ins_stmt = $db->prepare($insert_tag);

            foreach ($tags as $tag_id) {
                $ins_stmt->bindParam(':task_id', $task_id);
                $ins_stmt->bindParam(':tag_id', $tag_id);
                $ins_stmt->execute();
            }
        }

        // Registrar actividad de actualización
        $query = "SELECT project_id FROM tasks WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $task_id);
        $stmt->execute();
        $task_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task_data && $task_data['project_id']) {
            $changes = [];
            if ($current_task['status'] !== $status) {
                $changes[] = 'estado';
            }

            $description = "actualizó la tarea \"$title\"";
            if (!empty($changes)) {
                $description .= " (" . implode(', ', $changes) . ")";
            }

            logProjectActivity(
                $db,
                $task_data['project_id'],
                $_SESSION['user_id'],
                'updated',
                'task',
                $task_id,
                $description,
                [
                    'task_title' => $title,
                    'priority' => $priority
                ]
            );
        }

        echo json_encode(['success' => true, 'message' => 'Tarea actualizada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar tarea']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>