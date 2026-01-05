<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$task_id = $_POST['task_id'] ?? null;

if (!$task_id || empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit();
}

$upload_dir = '../../public/uploads/tasks/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file = $_FILES['file'];
$file_name = basename($file['name']);
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$unique_name = uniqid() . '.' . $file_ext;
$target_path = $upload_dir . $unique_name;
$web_path = '/PROYECTA/public/uploads/tasks/' . $unique_name;

// Validate file type
$allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
if (!in_array($file_ext, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
    exit();
}

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $database = new Database();
    $db = $database->getConnection();

    $query = "INSERT INTO task_attachments (task_id, uploader_id, file_name, file_path, file_type, file_size) 
              VALUES (:task_id, :uploader_id, :file_name, :file_path, :file_type, :file_size)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':uploader_id', $_SESSION['user_id']);
    $stmt->bindParam(':file_name', $file_name);
    $stmt->bindParam(':file_path', $web_path);
    $stmt->bindParam(':file_type', $file['type']);
    $stmt->bindParam(':file_size', $file['size']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Archivo subido',
            'attachment' => [
                'id' => $db->lastInsertId(),
                'file_name' => $file_name,
                'file_path' => $web_path
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar en base de datos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error al subir archivo']);
}
?>