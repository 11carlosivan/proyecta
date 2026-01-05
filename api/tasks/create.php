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

// Obtener datos del formulario
$title = sanitize($_POST['title'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$project_id = $_POST['project_id'] ?? null;
$assignee_id = $_POST['assignee_id'] ?: $_SESSION['user_id']; // Default to creator if empty
$status = $_POST['status'] ?? 'todo';
$priority = $_POST['priority'] ?? 'medium';
$due_date = $_POST['due_date'] ?? null;
$estimated_hours = $_POST['estimated_hours'] ?? null;

// Validar datos
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'El título es requerido']);
    exit();
}

// Insertar tarea
$query = "INSERT INTO tasks (title, description, project_id, assignee_id, reporter_id, status, priority, due_date, estimated_hours) 
          VALUES (:title, :description, :project_id, :assignee_id, :reporter_id, :status, :priority, :due_date, :estimated_hours)";
$stmt = $db->prepare($query);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':project_id', $project_id);
$stmt->bindParam(':assignee_id', $assignee_id);
$stmt->bindParam(':reporter_id', $_SESSION['user_id']);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':priority', $priority);
$stmt->bindParam(':due_date', $due_date);
$stmt->bindParam(':estimated_hours', $estimated_hours);

try {
    if ($stmt->execute()) {
        $task_id = $db->lastInsertId();

        // Crear notificación si hay asignado
        if ($assignee_id) {
            $query = "SELECT full_name FROM users WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $reporter = $stmt->fetch(PDO::FETCH_ASSOC);

            createNotification(
                $db,
                $assignee_id,
                "Nueva tarea asignada",
                $reporter['full_name'] . " te ha asignado la tarea: " . $title,
                'task',
                '/PROYECTA/modules/tasks/task-edit.php?id=' . $task_id
            );
        }

        // Registrar actividad en el proyecto
        if ($project_id) {
            $assignee_name = '';
            if ($assignee_id) {
                $query = "SELECT full_name FROM users WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $assignee_id);
                $stmt->execute();
                $assignee_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $assignee_name = $assignee_data['full_name'] ?? '';
            }

            $description = "creó la tarea \"$title\"";
            if ($assignee_name && $assignee_id != $_SESSION['user_id']) {
                $description .= " y la asignó a $assignee_name";
            }

            logProjectActivity(
                $db,
                $project_id,
                $_SESSION['user_id'],
                'created',
                'task',
                $task_id,
                $description,
                [
                    'task_title' => $title,
                    'priority' => $priority,
                    'status' => $status
                ]
            );
        }

        echo json_encode([
            'success' => true,
            'task_id' => $task_id,
            'message' => 'Tarea creada exitosamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear tarea']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>