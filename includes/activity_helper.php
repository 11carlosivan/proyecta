<?php
/**
 * Registra una actividad en el proyecto
 * 
 * @param PDO $db Conexión a la base de datos
 * @param int $project_id ID del proyecto
 * @param int $user_id ID del usuario que realiza la acción
 * @param string $action_type Tipo de acción (created, updated, deleted, etc.)
 * @param string $entity_type Tipo de entidad (task, project, member, etc.)
 * @param int|null $entity_id ID de la entidad afectada
 * @param string $description Descripción legible de la actividad
 * @param array|null $metadata Datos adicionales en formato array
 * @return bool
 */
function logProjectActivity($db, $project_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata = null)
{
    try {
        $query = "INSERT INTO project_activity 
                  (project_id, user_id, action_type, entity_type, entity_id, description, metadata) 
                  VALUES (:project_id, :user_id, :action_type, :entity_type, :entity_id, :description, :metadata)";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action_type', $action_type);
        $stmt->bindParam(':entity_type', $entity_type);
        $stmt->bindParam(':entity_id', $entity_id);
        $stmt->bindParam(':description', $description);

        $metadata_json = $metadata ? json_encode($metadata) : null;
        $stmt->bindParam(':metadata', $metadata_json);

        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging project activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el icono y color para un tipo de actividad
 * 
 * @param string $action_type
 * @return array ['icon' => string, 'color' => string]
 */
function getActivityIcon($action_type)
{
    $icons = [
        'created' => ['icon' => 'add_circle', 'color' => 'text-green-500 bg-green-500/10'],
        'updated' => ['icon' => 'edit', 'color' => 'text-blue-500 bg-blue-500/10'],
        'deleted' => ['icon' => 'delete', 'color' => 'text-red-500 bg-red-500/10'],
        'status_changed' => ['icon' => 'swap_horiz', 'color' => 'text-purple-500 bg-purple-500/10'],
        'assigned' => ['icon' => 'person_add', 'color' => 'text-orange-500 bg-orange-500/10'],
        'commented' => ['icon' => 'comment', 'color' => 'text-blue-500 bg-blue-500/10'],
        'priority_changed' => ['icon' => 'flag', 'color' => 'text-yellow-500 bg-yellow-500/10'],
        'member_added' => ['icon' => 'group_add', 'color' => 'text-green-500 bg-green-500/10'],
        'member_removed' => ['icon' => 'group_remove', 'color' => 'text-red-500 bg-red-500/10'],
        'completed' => ['icon' => 'check_circle', 'color' => 'text-green-500 bg-green-500/10'],
    ];

    return $icons[$action_type] ?? ['icon' => 'info', 'color' => 'text-gray-500 bg-gray-500/10'];
}

/**
 * Formatea el tiempo transcurrido desde una actividad
 * 
 * @param string $datetime
 * @return string
 */
function getActivityTime($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Hace un momento';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "Hace $mins " . ($mins == 1 ? 'minuto' : 'minutos');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Hace $hours " . ($hours == 1 ? 'hora' : 'horas');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Hace $days " . ($days == 1 ? 'día' : 'días');
    } else {
        return date('d/m/Y H:i', $timestamp);
    }
}
?>