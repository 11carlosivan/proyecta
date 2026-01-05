<?php
/**
 * Funciones auxiliares para PROYECTA
 */

require_once __DIR__ . '/time_helper.php';

/**
 * Sanitizar entrada de datos
 */
function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Generar slug para URLs
 */
function generateSlug($string)
{
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Formatear fecha para mostrar
 */
function formatDate($date, $format = 'd/m/Y')
{
    if (empty($date))
        return '';
    return date($format, strtotime($date));
}

/**
 * Calcular días restantes
 */
function daysRemaining($end_date)
{
    if (empty($end_date))
        return null;

    $now = time();
    $end = strtotime($end_date);
    $diff = $end - $now;

    return floor($diff / (60 * 60 * 24));
}

/**
 * Obtener color según estado
 */
function getStatusColor($status)
{
    $colors = [
        'active' => 'bg-green-500/10 text-green-500',
        'completed' => 'bg-blue-500/10 text-blue-500',
        'paused' => 'bg-yellow-500/10 text-yellow-500',
        'archived' => 'bg-gray-500/10 text-gray-500',
        'todo' => 'bg-slate-500/10 text-slate-500',
        'in_progress' => 'bg-orange-500/10 text-orange-500',
        'review' => 'bg-purple-500/10 text-purple-500',
        'done' => 'bg-emerald-500/10 text-emerald-500',
    ];

    return $colors[$status] ?? 'bg-gray-500/10 text-gray-500';
}

/**
 * Obtener icono según prioridad
 */
function getPriorityIcon($priority)
{
    $icons = [
        'low' => 'arrow_downward',
        'medium' => 'remove',
        'high' => 'arrow_upward',
        'critical' => 'priority_high'
    ];

    return $icons[$priority] ?? 'remove';
}

/**
 * Obtener color según prioridad
 */
function getPriorityColor($priority)
{
    $colors = [
        'low' => 'bg-blue-500/10 text-blue-500',
        'medium' => 'bg-yellow-500/10 text-yellow-500',
        'high' => 'bg-orange-500/10 text-orange-500',
        'critical' => 'bg-red-500/10 text-red-500'
    ];

    return $colors[$priority] ?? 'bg-gray-500/10 text-gray-500';
}

/**
 * Validar email
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generar código aleatorio
 */
function generateRandomCode($length = 8)
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Formatear tamaño de archivo
 */
function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Crear notificación
 */
function createNotification($db, $user_id, $title, $message, $type = 'info', $link = null)
{
    $query = "INSERT INTO notifications (user_id, title, message, type, link, created_at) 
              VALUES (:user_id, :title, :message, :type, :link, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':link', $link);

    return $stmt->execute();
}

/**
 * Verificar permisos de usuario
 */
function hasPermission($user_role, $required_role)
{
    $hierarchy = [
        'admin' => 3,
        'editor' => 2,
        'viewer' => 1
    ];

    $user_level = $hierarchy[$user_role] ?? 0;
    $required_level = $hierarchy[$required_role] ?? 0;

    return $user_level >= $required_level;
}

/**
 * Obtener avatar del usuario
 */
function getUserAvatar($user, $size = 40)
{
    if (!empty($user['avatar_url'])) {
        return '<img src="' . htmlspecialchars($user['avatar_url']) . '" class="rounded-full" width="' . $size . '" height="' . $size . '" alt="' . htmlspecialchars($user['full_name']) . '">';
    } else {
        $initial = strtoupper(substr($user['full_name'], 0, 1));
        return '<div class="rounded-full bg-primary flex items-center justify-center text-white font-medium" style="width: ' . $size . 'px; height: ' . $size . 'px;">' . $initial . '</div>';
    }
}

/**
 * Encriptar datos sensibles
 */
function encryptData($data, $key)
{
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Desencriptar datos
 */
function decryptData($data, $key)
{
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
}

/**
 * Validar URL
 */
function isValidUrl($url)
{
    return filter_var($url, FILTER_VALIDATE_URL);
}

/**
 * Limitar texto largo
 */
function truncateText($text, $length = 100)
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Calcular porcentaje
 */
function calculatePercentage($part, $total)
{
    if ($total == 0)
        return 0;
    return round(($part / $total) * 100);
}

/**
 * Obtener estadísticas del proyecto
 */
function getProjectStats($db, $project_id)
{
    $stats = [
        'total_tasks' => 0,
        'completed_tasks' => 0,
        'in_progress_tasks' => 0,
        'todo_tasks' => 0,
        'overdue_tasks' => 0
    ];

    // Total tasks
    $query = "SELECT COUNT(*) as count FROM tasks WHERE project_id = :project_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $stats['total_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Completed tasks
    $query = "SELECT COUNT(*) as count FROM tasks WHERE project_id = :project_id AND status = 'done'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $stats['completed_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // In progress tasks
    $query = "SELECT COUNT(*) as count FROM tasks WHERE project_id = :project_id AND status = 'in_progress'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $stats['in_progress_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Todo tasks
    $query = "SELECT COUNT(*) as count FROM tasks WHERE project_id = :project_id AND status = 'todo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $stats['todo_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Overdue tasks
    $query = "SELECT COUNT(*) as count FROM tasks WHERE project_id = :project_id AND due_date < CURDATE() AND status != 'done'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $stats['overdue_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    return $stats;
}
?>