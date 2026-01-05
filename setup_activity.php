<?php
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Crear la tabla project_activity
$sql = "CREATE TABLE IF NOT EXISTS project_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    description TEXT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_created (project_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->exec($sql);
    echo "✅ Tabla 'project_activity' creada exitosamente<br>";

    // Insertar algunos datos de ejemplo
    $user_id = $_SESSION['user_id'] ?? 3; // Tu ID de usuario

    // Obtener un proyecto de ejemplo
    $stmt = $db->query("SELECT id FROM projects LIMIT 1");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($project) {
        $project_id = $project['id'];

        // Insertar actividades de ejemplo
        $activities = [
            [
                'action' => 'created',
                'entity' => 'project',
                'desc' => 'creó el proyecto'
            ],
            [
                'action' => 'member_added',
                'entity' => 'member',
                'desc' => 'agregó un nuevo miembro al equipo'
            ],
            [
                'action' => 'created',
                'entity' => 'task',
                'desc' => 'creó una nueva tarea'
            ],
            [
                'action' => 'status_changed',
                'entity' => 'task',
                'desc' => 'cambió el estado de una tarea a "En Progreso"'
            ],
            [
                'action' => 'completed',
                'entity' => 'task',
                'desc' => 'completó una tarea'
            ]
        ];

        $insert_sql = "INSERT INTO project_activity (project_id, user_id, action_type, entity_type, entity_id, description) 
                       VALUES (:project_id, :user_id, :action_type, :entity_type, :entity_id, :description)";
        $stmt = $db->prepare($insert_sql);

        foreach ($activities as $activity) {
            $stmt->execute([
                ':project_id' => $project_id,
                ':user_id' => $user_id,
                ':action_type' => $activity['action'],
                ':entity_type' => $activity['entity'],
                ':entity_id' => null,
                ':description' => $activity['desc']
            ]);
        }

        echo "✅ Datos de ejemplo insertados exitosamente<br>";
        echo "<br><a href='../projects/project.php?id=$project_id' class='text-blue-500 underline'>Ver proyecto con cronología</a>";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>