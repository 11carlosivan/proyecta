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

$database = new Database();
$db = $database->getConnection();

$name = sanitize($_POST['name'] ?? '');
$color = sanitize($_POST['color'] ?? 'bg-gray-500');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Nombre requerido']);
    exit();
}

try {
    $query = "INSERT INTO tags (name, color) VALUES (:name, :color)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':color', $color);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'tag' => [
                'id' => $db->lastInsertId(),
                'name' => $name,
                'color' => $color
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear etiqueta']);
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'La etiqueta ya existe']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    }
}
?>