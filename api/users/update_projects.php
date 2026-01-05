<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/activity_helper.php';

// Only admins and members can assign projects
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'member') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $user_id = $data['user_id'] ?? null;
    $project_ids = $data['project_ids'] ?? []; // Array of project IDs

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        // Obtener proyectos actuales del usuario para detectar cambios
        $query = "SELECT project_id FROM project_members WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $current_projects = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Obtener nombre del usuario para los logs
        $query = "SELECT full_name FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_name = $user_data['full_name'] ?? 'Usuario';

        // 1. Remove all existing assignments
        $query = "DELETE FROM project_members WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        // Registrar remociones
        $removed_projects = array_diff($current_projects, $project_ids);
        foreach ($removed_projects as $removed_pid) {
            logProjectActivity(
                $db,
                $removed_pid,
                $_SESSION['user_id'],
                'member_removed',
                'member',
                $user_id,
                "removió a $user_name del proyecto"
            );
        }

        // 2. Add new assignments
        if (!empty($project_ids)) {
            $query = "INSERT INTO project_members (project_id, user_id, role, joined_at) VALUES (:project_id, :user_id, 'member', NOW())";
            $stmt = $db->prepare($query);

            foreach ($project_ids as $pid) {
                $stmt->bindParam(':project_id', $pid);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();

                // Registrar solo si es un proyecto nuevo (no estaba antes)
                if (!in_array($pid, $current_projects)) {
                    logProjectActivity(
                        $db,
                        $pid,
                        $_SESSION['user_id'],
                        'member_added',
                        'member',
                        $user_id,
                        "agregó a $user_name al proyecto"
                    );
                }
            }
        }

        $db->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>