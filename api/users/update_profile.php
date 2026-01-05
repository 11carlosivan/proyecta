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
$user_id = $_SESSION['user_id'];

// 1. Update basic info
if (isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $job_title = sanitize($_POST['job_title']);
    $bio = sanitize($_POST['bio']);

    if (empty($full_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Nombre y email son obligatorios']);
        exit();
    }

    try {
        $query = "UPDATE users SET full_name = :full_name, email = :email, job_title = :job_title, bio = :bio WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':job_title', $job_title);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':id', $user_id);

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name; // Update session
            $_SESSION['user_email'] = $email;
            echo json_encode(['success' => true, 'message' => 'Perfil actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    }
}

// 2. Change password
else if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas nuevas no coinciden']);
        exit();
    }

    // Verify current password
    $query = "SELECT password_hash FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($current_password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
        exit();
    }

    // Update password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $query = "UPDATE users SET password_hash = :hash WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':hash', $new_hash);
    $stmt->bindParam(':id', $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar contraseña']);
    }
}

// 3. Upload avatar
else if (isset($_FILES['avatar'])) {
    $upload_dir = '../../public/uploads/avatars/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['avatar'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($file_ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Formato no permitido']);
        exit();
    }

    $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
    $target_path = $upload_dir . $new_name;
    $web_path = '/PROYECTA/public/uploads/avatars/' . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $query = "UPDATE users SET avatar_url = :url WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':url', $web_path);
        $stmt->bindParam(':id', $user_id);

        if ($stmt->execute()) {
            $_SESSION['avatar_url'] = $web_path; // Sync session
            echo json_encode(['success' => true, 'message' => 'Avatar actualizado', 'avatar_url' => $web_path]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar en BD']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al subir archivo']);
    }
}
?>