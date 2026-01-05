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
$comment = $data['comment'] ?? '';

if (!$task_id || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit();
}

try {
    $query = "INSERT INTO task_comments (task_id, user_id, comment) VALUES (:task_id, :user_id, :comment)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':comment', $comment);

    if ($stmt->execute()) {
        $comment_id = $db->lastInsertId();

        // Obtener el comentario con datos del usuario
        $query = "SELECT c.*, u.full_name, u.avatar_url 
                  FROM task_comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $comment_id);
        $stmt->execute();
        $comment_data = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'comment' => $comment_data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear comentario']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>