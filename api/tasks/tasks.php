<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$page_title = "Mis Tareas";

// Filtros
$status = $_GET['status'] ?? 'all';
$priority = $_GET['priority'] ?? 'all';
$project = $_GET['project'] ?? 'all';
$search = $_GET['search'] ?? '';

// Construir consulta
$where = "WHERE assignee_id = :user_id";
$params = [':user_id' => $_SESSION['user_id']];

if ($status !== 'all') {
    $where .= " AND status = :status";
    $params[':status'] = $status;
}

if ($priority !== 'all') {
    $where .= " AND priority = :priority";
    $params[':priority'] = $priority;
}

if ($project !== 'all' && $project !== '') {
    $where .= " AND project_id = :project_id";
    $params[':project_id'] = $project;
}

if (!empty($search)) {
    $where .= " AND (title LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Obtener tareas
$query = "SELECT t.*, p.name as project_name, p.color as project_color 
          FROM tasks t 
          LEFT JOIN projects p ON t.project_id = p.id 
          $where 
          ORDER BY 
            CASE priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END,
            due_date ASC,
            created_at DESC";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener proyectos para filtro
$query = "SELECT p.* FROM projects p 
          WHERE p.owner_id = :user_id OR p.id IN (SELECT project_id FROM project_members WHERE user_id = :user_id2)";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':user_id2', $_SESSION['user_id']);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>PROYECTA - <?php echo $page_title; ?></title>
    <?php include '../../includes/header.php'; ?>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="ml-0 md:ml-64 flex-1">
        <?php 
        $header_title = "Mis Tareas";
        $header_description = "Gestiona todas tus tareas asignadas";
        include '../../includes/page-header.php'; 
        ?>
        
        <!-- Filtros -->
        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <!-- Buscador -->
                <div class="flex-1">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                               placeholder="Buscar tareas...">
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <!-- Filtro por estado -->
                    <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos los estados</option>
                        <option value="todo" <?php echo $status === 'todo' ? 'selected' : ''; ?>>Por Hacer</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>En Progreso</option>
                        <option value="review" <?php echo $status === 'review' ? 'selected' : ''; ?>>En Revisión</option>
                        <option value="done" <?php echo $status === 'done' ? 'selected' : ''; ?>>Completado</option>
                    </select>
                    
                    <!-- Filtro por prioridad -->
                    <select name="priority" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        <option value="all" <?php echo $priority === 'all' ? 'selected' : ''; ?>>Todas las prioridades</option>
                        <option value="critical" <?php echo $priority === 'critical' ? 'selected' : ''; ?>>Crítica</option>
                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>Alta</option>
                        <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Media</option>
                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Baja</option>
                    </select>
                    
                    <!-- Filtro por proyecto -->
                    <select name="project" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        <option value="all" <?php echo $project === 'all' ? 'selected' : ''; ?>>Todos los proyectos</option>
                        <?php foreach ($projects as $proj): ?>
                        <option value="<?php echo $proj['id']; ?>" <?php echo $project == $proj['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($proj['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                        Filtrar
                    </button>
                    
                    <?php if ($status !== 'all' || $priority !== 'all' || $project !== 'all' || !empty($search)): ?>
                    <a href="tasks.php" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        Limpiar
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Lista de tareas -->
        <div class="p-6">
            <?php if (empty($tasks)): ?>
            <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-4">task_alt</span>
                <h3 class="text-lg font-semibold mb-2">No hay tareas</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">No tienes tareas asignadas con los filtros actuales</p>
                <a href="tasks.php" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                    <span class="material-symbols-outlined">refresh</span>
                    Mostrar todas las tareas
                </a>
            </div>
            <?php else: ?>
            <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarea</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Proyecto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                <th class="px6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prioridad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha Límite</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($tasks as $task): 
                                $days_remaining = daysRemaining($task['due_date']);
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4">
                                    <div>
                                        <a href="task.php?id=<?php echo $task['id']; ?>" class="font-medium text-gray-900 dark:text-white hover:text-primary">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </a>
                                        <?php if ($task['description']): ?>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                            <?php echo truncateText(htmlspecialchars($task['description']), 100); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <?php if ($task['project_name']): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="size-2 rounded-full" style="background-color: <?php echo $task['project_color']; ?>"></span>
                                        <span><?php echo htmlspecialchars($task['project_name']); ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-gray-400 dark:text-gray-500">Sin proyecto</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo getStatusColor($task['status']); ?>">
                                        <?php 
                                        $status_names = [
                                            'todo' => 'Por Hacer',
                                            'in_progress' => 'En Progreso',
                                            'review' => 'En Revisión',
                                            'done' => 'Completado'
                                        ];
                                        echo $status_names[$task['status']];
                                        ?>
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm <?php echo $task['priority'] === 'high' || $task['priority'] === 'critical' ? 'text-red-500' : 'text-gray-400'; ?>">
                                            <?php echo getPriorityIcon($task['priority']); ?>
                                        </span>
                                        <span class="text-sm"><?php echo ucfirst($task['priority']); ?></span>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <?php if ($task['due_date']): ?>
                                    <div class="<?php echo $days_remaining < 0 ? 'text-red-500' : ($days_remaining < 3 ? 'text-orange-500' : 'text-gray-500'); ?>">
                                        <?php echo formatDate($task['due_date']); ?>
                                        <?php if ($days_remaining < 0 && $task['status'] !== 'done'): ?>
                                        <div class="text-xs">Vencido hace <?php echo abs($days_remaining); ?> días</div>
                                        <?php elseif ($days_remaining >= 0 && $task['status'] !== 'done'): ?>
                                        <div class="text-xs"><?php echo $days_remaining; ?> días restantes</div>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-gray-400 dark:text-gray-500">Sin fecha</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="task.php?id=<?php echo $task['id']; ?>" 
                                           class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-gray-400 hover:text-gray-600"
                                           title="Ver detalles">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                        </a>
                                        <a href="task-edit.php?id=<?php echo $task['id']; ?>" 
                                           class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-gray-400 hover:text-gray-600"
                                           title="Editar">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </a>
                                        <?php if ($task['status'] !== 'done'): ?>
                                        <button onclick="markAsDone(<?php echo $task['id']; ?>)" 
                                                class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-gray-400 hover:text-emerald-600"
                                                title="Marcar como completada">
                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if (count($tasks) > 10): ?>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Mostrando <span class="font-medium">1-10</span> de <span class="font-medium"><?php echo count($tasks); ?></span> tareas
                    </div>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50" disabled>
                            Anterior
                        </button>
                        <button class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                            Siguiente
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
    function markAsDone(taskId) {
        if (confirm('¿Marcar esta tarea como completada?')) {
            fetch('../../api/tasks/update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    task_id: taskId,
                    status: 'done'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Tarea completada', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error al completar tarea', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
            });
        }
    }
    </script>
</body>
</html>