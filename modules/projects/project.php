<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/activity_helper.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$project_id = $_GET['id'] ?? null;

if (!$project_id) {
    header('Location: projects.php');
    exit;
}

// Obtener detalles del proyecto
$query = "SELECT p.*, u.full_name as owner_name 
          FROM projects p 
          LEFT JOIN users u ON p.owner_id = u.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $project_id);
$stmt->execute();
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Estadísticas del proyecto
$stats_query = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo_tasks,
    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_tasks
FROM tasks WHERE project_id = :project_id";
$stmt = $db->prepare($stats_query);
$stmt->bindParam(':project_id', $project_id);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calcular progreso
$progress = $stats['total_tasks'] > 0 ? ($stats['completed_tasks'] / $stats['total_tasks']) * 100 : 0;

// Obtener miembros del equipo
$members_query = "SELECT u.id, u.full_name, u.avatar_url, u.email, pm.role
                  FROM project_members pm
                  JOIN users u ON pm.user_id = u.id
                  WHERE pm.project_id = :project_id
                  ORDER BY pm.role DESC, u.full_name ASC";
$stmt = $db->prepare($members_query);
$stmt->bindParam(':project_id', $project_id);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tareas recientes
$tasks_query = "SELECT t.*, u.full_name as assignee_name, u.avatar_url as assignee_avatar
                FROM tasks t
                LEFT JOIN users u ON t.assignee_id = u.id
                WHERE t.project_id = :project_id
                ORDER BY t.updated_at DESC
                LIMIT 10";
$stmt = $db->prepare($tasks_query);
$stmt->bindParam(':project_id', $project_id);
$stmt->execute();
$recent_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades recientes
$activity_query = "SELECT pa.*, u.full_name as user_name, u.avatar_url as user_avatar
                   FROM project_activity pa
                   JOIN users u ON pa.user_id = u.id
                   WHERE pa.project_id = :project_id
                   ORDER BY pa.created_at DESC
                   LIMIT 20";
$stmt = $db->prepare($activity_query);
$stmt->bindParam(':project_id', $project_id);
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular días restantes
$days_left = null;
if ($project['end_date']) {
    $days_left = floor((strtotime($project['end_date']) - time()) / (60 * 60 * 24));
}

$page_title = $project['name'];
?>

<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>PROYECTA - <?php echo htmlspecialchars($page_title); ?></title>
    <?php include '../../includes/head.php'; ?>
</head>

<body
    class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white font-display h-screen overflow-hidden">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 h-screen overflow-y-auto">
        <?php include '../../includes/header.php'; ?>

        <div class="p-6">
            <!-- Header del Proyecto -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="size-16 rounded-xl flex items-center justify-center text-white text-2xl font-bold"
                            style="background-color: <?php echo $project['color']; ?>">
                            <?php echo strtoupper(substr($project['name'], 0, 2)); ?>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold mb-1">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </h1>
                            <p class="text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($project['description']); ?>
                            </p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-sm text-gray-500">
                                    <span class="material-symbols-outlined text-[16px] align-middle">person</span>
                                    <?php echo htmlspecialchars($project['owner_name']); ?>
                                </span>
                                <span class="text-sm text-gray-500">
                                    <span class="material-symbols-outlined text-[16px] align-middle">group</span>
                                    <?php echo count($members); ?> miembros
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <span
                            class="px-4 py-2 rounded-lg text-sm font-medium <?php echo getStatusColor($project['status']); ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                        <a href="project-edit.php?id=<?php echo $project_id; ?>"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                            Editar
                        </a>
                        <a href="../kanban/kanban.php?project_id=<?php echo $project_id; ?>"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">view_kanban</span>
                            Kanban
                        </a>
                    </div>
                </div>

                <!-- Barra de Progreso -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Progreso General</span>
                        <span class="text-sm font-bold text-primary"><?php echo round($progress); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="bg-gradient-to-r from-blue-500 to-primary h-3 rounded-full transition-all duration-500"
                            style="width: <?php echo $progress; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Tareas</p>
                            <p class="text-2xl font-bold mt-1"><?php echo $stats['total_tasks']; ?></p>
                        </div>
                        <div class="size-12 rounded-lg bg-blue-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-blue-500 text-[28px]">assignment</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Completadas</p>
                            <p class="text-2xl font-bold mt-1 text-green-500"><?php echo $stats['completed_tasks']; ?>
                            </p>
                        </div>
                        <div class="size-12 rounded-lg bg-green-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-green-500 text-[28px]">check_circle</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">En Progreso</p>
                            <p class="text-2xl font-bold mt-1 text-orange-500">
                                <?php echo $stats['in_progress_tasks']; ?>
                            </p>
                        </div>
                        <div class="size-12 rounded-lg bg-orange-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-orange-500 text-[28px]">pending</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pendientes</p>
                            <p class="text-2xl font-bold mt-1 text-gray-500"><?php echo $stats['todo_tasks']; ?></p>
                        </div>
                        <div class="size-12 rounded-lg bg-gray-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-gray-500 text-[28px]">schedule</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?php echo $days_left !== null ? ($days_left >= 0 ? 'Días Restantes' : 'Días Vencidos') : 'Sin Fecha'; ?>
                            </p>
                            <p
                                class="text-2xl font-bold mt-1 <?php echo $days_left !== null && $days_left < 7 ? 'text-red-500' : 'text-primary'; ?>">
                                <?php echo $days_left !== null ? abs($days_left) : '-'; ?>
                            </p>
                        </div>
                        <div
                            class="size-12 rounded-lg <?php echo $days_left !== null && $days_left < 7 ? 'bg-red-500/10' : 'bg-primary/10'; ?> flex items-center justify-center">
                            <span
                                class="material-symbols-outlined <?php echo $days_left !== null && $days_left < 7 ? 'text-red-500' : 'text-primary'; ?> text-[28px]">event</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestañas -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex gap-4 px-6" id="project-tabs">
                        <button class="tab-btn active py-4 px-2 border-b-2 border-primary text-primary font-medium"
                            data-tab="overview">
                            <span class="material-symbols-outlined text-[20px] align-middle mr-1">dashboard</span>
                            Resumen
                        </button>
                        <button
                            class="tab-btn py-4 px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium"
                            data-tab="tasks">
                            <span class="material-symbols-outlined text-[20px] align-middle mr-1">checklist</span>
                            Tareas
                        </button>
                        <button
                            class="tab-btn py-4 px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium"
                            data-tab="team">
                            <span class="material-symbols-outlined text-[20px] align-middle mr-1">group</span>
                            Equipo
                        </button>
                        <button
                            class="tab-btn py-4 px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium"
                            data-tab="timeline">
                            <span class="material-symbols-outlined text-[20px] align-middle mr-1">timeline</span>
                            Cronología
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Tab: Resumen -->
                    <div id="tab-overview" class="tab-content">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Información del Proyecto -->
                            <div>
                                <h3 class="text-lg font-bold mb-4">Información del Proyecto</h3>
                                <div class="space-y-3">
                                    <div
                                        class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                        <span class="text-gray-500">Fecha de Inicio</span>
                                        <span
                                            class="font-medium"><?php echo formatDate($project['start_date']); ?></span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                        <span class="text-gray-500">Fecha Límite</span>
                                        <span class="font-medium"><?php echo formatDate($project['end_date']); ?></span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                        <span class="text-gray-500">Propietario</span>
                                        <span
                                            class="font-medium"><?php echo htmlspecialchars($project['owner_name']); ?></span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                        <span class="text-gray-500">Estado</span>
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-medium <?php echo getStatusColor($project['status']); ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between py-2">
                                        <span class="text-gray-500">Creado</span>
                                        <span
                                            class="font-medium"><?php echo formatDate($project['created_at']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de Progreso -->
                            <div>
                                <h3 class="text-lg font-bold mb-4">Distribución de Tareas</h3>
                                <div class="flex items-center justify-center h-64">
                                    <div class="relative size-48">
                                        <svg class="transform -rotate-90" viewBox="0 0 100 100">
                                            <!-- Completadas -->
                                            <circle cx="50" cy="50" r="40" fill="none" stroke="#10b981"
                                                stroke-width="12"
                                                stroke-dasharray="<?php echo ($stats['total_tasks'] > 0 ? ($stats['completed_tasks'] / $stats['total_tasks']) * 251.2 : 0); ?> 251.2" />
                                            <!-- En Progreso -->
                                            <circle cx="50" cy="50" r="40" fill="none" stroke="#f59e0b"
                                                stroke-width="12"
                                                stroke-dasharray="<?php echo ($stats['total_tasks'] > 0 ? ($stats['in_progress_tasks'] / $stats['total_tasks']) * 251.2 : 0); ?> 251.2"
                                                stroke-dashoffset="<?php echo -($stats['total_tasks'] > 0 ? ($stats['completed_tasks'] / $stats['total_tasks']) * 251.2 : 0); ?>" />
                                            <!-- Pendientes -->
                                            <circle cx="50" cy="50" r="40" fill="none" stroke="#6b7280"
                                                stroke-width="12"
                                                stroke-dasharray="<?php echo ($stats['total_tasks'] > 0 ? ($stats['todo_tasks'] / $stats['total_tasks']) * 251.2 : 0); ?> 251.2"
                                                stroke-dashoffset="<?php echo -($stats['total_tasks'] > 0 ? (($stats['completed_tasks'] + $stats['in_progress_tasks']) / $stats['total_tasks']) * 251.2 : 0); ?>" />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="text-center">
                                                <p class="text-3xl font-bold"><?php echo round($progress); ?>%</p>
                                                <p class="text-xs text-gray-500">Completado</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-center gap-6 mt-4">
                                    <div class="flex items-center gap-2">
                                        <div class="size-3 rounded-full bg-green-500"></div>
                                        <span class="text-sm">Completadas
                                            (<?php echo $stats['completed_tasks']; ?>)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="size-3 rounded-full bg-orange-500"></div>
                                        <span class="text-sm">En Progreso
                                            (<?php echo $stats['in_progress_tasks']; ?>)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="size-3 rounded-full bg-gray-500"></div>
                                        <span class="text-sm">Pendientes (<?php echo $stats['todo_tasks']; ?>)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Tareas -->
                    <div id="tab-tasks" class="tab-content hidden">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Tareas Recientes</h3>
                            <a href="../tasks/tasks.php?project_id=<?php echo $project_id; ?>"
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">add</span>
                                Nueva Tarea
                            </a>
                        </div>
                        <div class="space-y-2">
                            <?php if (empty($recent_tasks)): ?>
                                <p class="text-center text-gray-500 py-8">No hay tareas en este proyecto</p>
                            <?php else: ?>
                                <?php foreach ($recent_tasks as $task): ?>
                                    <div
                                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <div class="flex-1">
                                            <h4 class="font-medium mb-1"><?php echo htmlspecialchars($task['title']); ?></h4>
                                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                                <span
                                                    class="px-2 py-1 rounded text-xs font-medium <?php echo getStatusColor($task['status']); ?>">
                                                    <?php echo ucfirst($task['status']); ?>
                                                </span>
                                                <span
                                                    class="px-2 py-1 rounded text-xs font-medium <?php echo getPriorityColor($task['priority']); ?>">
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                                <?php if ($task['assignee_name']): ?>
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-[16px]">person</span>
                                                        <?php echo htmlspecialchars($task['assignee_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <a href="../tasks/task-edit.php?id=<?php echo $task['id']; ?>"
                                            class="px-3 py-2 bg-white dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tab: Equipo -->
                    <div id="tab-team" class="tab-content hidden">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Miembros del Equipo</h3>
                            <button
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">person_add</span>
                                Agregar Miembro
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php if (empty($members)): ?>
                                <p class="col-span-full text-center text-gray-500 py-8">No hay miembros en este proyecto</p>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                    <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div
                                            class="size-12 rounded-full bg-primary flex items-center justify-center text-white font-medium overflow-hidden">
                                            <?php if ($member['avatar_url']): ?>
                                                <img src="<?php echo htmlspecialchars($member['avatar_url']); ?>" alt="Avatar"
                                                    class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-medium"><?php echo htmlspecialchars($member['full_name']); ?></h4>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($member['email']); ?>
                                            </p>
                                            <span class="text-xs px-2 py-1 rounded bg-blue-500/10 text-blue-500 font-medium">
                                                <?php echo ucfirst($member['role']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tab: Cronología -->
                    <div id="tab-timeline" class="tab-content hidden">
                        <h3 class="text-lg font-bold mb-4">Actividad Reciente</h3>
                        <?php if (empty($activities)): ?>
                            <div class="text-center py-12">
                                <span class="material-symbols-outlined text-6xl text-gray-300 dark:text-gray-600">timeline</span>
                                <p class="text-gray-500 mt-4">No hay actividad registrada en este proyecto</p>
                                <p class="text-sm text-gray-400 mt-2">Las actividades aparecerán aquí cuando se realicen cambios</p>
                            </div>
                        <?php else: ?>
                            <div class="relative">
                                <!-- Línea vertical del timeline -->
                                <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                                
                                <div class="space-y-6">
                                    <?php foreach ($activities as $activity): ?>
                                        <?php $iconData = getActivityIcon($activity['action_type']); ?>
                                        <div class="relative flex gap-4">
                                            <!-- Icono de la actividad -->
                                            <div class="relative z-10 flex-shrink-0">
                                                <div class="size-12 rounded-full <?php echo $iconData['color']; ?> flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-[20px]"><?php echo $iconData['icon']; ?></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Contenido de la actividad -->
                                            <div class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div class="flex items-center gap-2">
                                                        <!-- Avatar del usuario -->
                                                        <div class="size-6 rounded-full bg-primary flex items-center justify-center text-white text-xs font-medium overflow-hidden">
                                                            <?php if ($activity['user_avatar']): ?>
                                                                <img src="<?php echo htmlspecialchars($activity['user_avatar']); ?>" alt="Avatar" class="w-full h-full object-cover">
                                                            <?php else: ?>
                                                                <?php echo strtoupper(substr($activity['user_name'], 0, 1)); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="font-medium text-sm"><?php echo htmlspecialchars($activity['user_name']); ?></span>
                                                    </div>
                                                    <span class="text-xs text-gray-500"><?php echo getActivityTime($activity['created_at']); ?></span>
                                                </div>
                                                
                                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                                    <?php echo htmlspecialchars($activity['description']); ?>
                                                </p>
                                                
                                                <?php if ($activity['metadata']): ?>
                                                    <?php $metadata = json_decode($activity['metadata'], true); ?>
                                                    <?php if (!empty($metadata)): ?>
                                                        <div class="mt-2 text-xs text-gray-500 space-y-1">
                                                            <?php foreach ($metadata as $key => $value): ?>
                                                                <div class="flex gap-2">
                                                                    <span class="font-medium"><?php echo htmlspecialchars($key); ?>:</span>
                                                                    <span><?php echo htmlspecialchars($value); ?></span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const tabName = this.dataset.tab;

                // Remove active class from all buttons
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active', 'border-primary', 'text-primary');
                    b.classList.add('border-transparent', 'text-gray-500');
                });

                // Add active class to clicked button
                this.classList.add('active', 'border-primary', 'text-primary');
                this.classList.remove('border-transparent', 'text-gray-500');

                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Show selected tab content
                document.getElementById('tab-' + tabName).classList.remove('hidden');
            });
        });
    </script>
</body>

</html>