<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$page_title = "Mis Tareas";

// Obtener filtros
$filter_status = $_GET['status'] ?? 'all';
$filter_priority = $_GET['priority'] ?? 'all';
$filter_project = $_GET['project'] ?? 'all';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'due_date';
$view = $_GET['view'] ?? 'list'; // list, cards, calendar

// Construir query base
$query = "SELECT t.*, 
          p.name as project_name,
          p.color as project_color,
          u.full_name as reporter_name,
          (SELECT COUNT(*) FROM task_checklist WHERE task_id = t.id) as checklist_total,
          (SELECT COUNT(*) FROM task_checklist WHERE task_id = t.id AND is_completed = 1) as checklist_completed,
          (SELECT COUNT(*) FROM task_comments WHERE task_id = t.id) as comments_count
          FROM tasks t
          LEFT JOIN projects p ON t.project_id = p.id
          LEFT JOIN users u ON t.reporter_id = u.id
          WHERE t.assignee_id = :user_id";

// Aplicar filtros
$params = [':user_id' => $_SESSION['user_id']];

if ($filter_status !== 'all') {
    $query .= " AND t.status = :status";
    $params[':status'] = $filter_status;
}

if ($filter_priority !== 'all') {
    $query .= " AND t.priority = :priority";
    $params[':priority'] = $filter_priority;
}

if ($filter_project !== 'all') {
    $query .= " AND t.project_id = :project_id";
    $params[':project_id'] = $filter_project;
}

if (!empty($search)) {
    $query .= " AND (t.title LIKE :search OR t.description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Ordenamiento
$order_by = match ($sort) {
    'due_date' => 'ORDER BY COALESCE(t.due_date, "9999-12-31") ASC',
    'priority' => 'ORDER BY FIELD(t.priority, "critical", "high", "medium", "low")',
    'created' => 'ORDER BY t.created_at DESC',
    'updated' => 'ORDER BY t.updated_at DESC',
    default => 'ORDER BY COALESCE(t.due_date, "9999-12-31") ASC'
};

$query .= " " . $order_by;

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats = [
    'todo' => 0,
    'in_progress' => 0,
    'review' => 0,
    'done' => 0,
    'overdue' => 0
];

foreach ($tasks as $task) {
    if (isset($stats[$task['status']])) {
        $stats[$task['status']]++;
    }
    if ($task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] != 'done') {
        $stats['overdue']++;
    }
}

// Obtener proyectos para filtro
$stmt = $db->prepare("SELECT DISTINCT p.id, p.name FROM projects p 
                      INNER JOIN tasks t ON p.id = t.project_id 
                      WHERE t.assignee_id = :user_id 
                      ORDER BY p.name");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>PROYECTA - <?php echo $page_title; ?></title>
    <?php include '../../includes/head.php'; ?>
    <style>
        .task-card {
            transition: all 0.2s ease;
        }

        .task-card:hover {
            transform: translateY(-2px);
        }

        .filter-badge {
            transition: all 0.2s ease;
        }

        .filter-badge.active {
            background: #137fec;
            color: white;
        }
    </style>
</head>

<body
    class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white font-display h-screen overflow-hidden">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 h-screen overflow-y-auto">
        <?php include '../../includes/header.php'; ?>

        <div class="p-6">
            <!-- Header con estadísticas -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold mb-1">Mis Tareas</h1>
                        <p class="text-gray-500">Gestiona todas tus tareas asignadas</p>
                    </div>
                    <button onclick="openQuickCreateModal()"
                        class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-blue-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all">
                        <span class="material-symbols-outlined">add</span>
                        Nueva Tarea
                    </button>
                </div>

                <!-- Estadísticas -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div
                        class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-gray-500">Por Hacer</span>
                                <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                                    <?php echo $stats['todo']; ?>
                                </p>
                            </div>
                            <span class="material-symbols-outlined text-3xl text-gray-400">schedule</span>
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-xl border-2 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-blue-600 dark:text-blue-400">En Progreso</span>
                                <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                                    <?php echo $stats['in_progress']; ?>
                                </p>
                            </div>
                            <span class="material-symbols-outlined text-3xl text-blue-500">pending</span>
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-4 rounded-xl border border-purple-200 dark:border-purple-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-purple-600 dark:text-purple-400">En Revisión</span>
                                <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                                    <?php echo $stats['review']; ?>
                                </p>
                            </div>
                            <span class="material-symbols-outlined text-3xl text-purple-500">rate_review</span>
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-4 rounded-xl border border-green-200 dark:border-green-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-green-600 dark:text-green-400">Completadas</span>
                                <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                                    <?php echo $stats['done']; ?>
                                </p>
                            </div>
                            <span class="material-symbols-outlined text-3xl text-green-500">check_circle</span>
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-4 rounded-xl border border-red-200 dark:border-red-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-red-600 dark:text-red-400">Vencidas</span>
                                <p class="text-2xl font-bold text-red-700 dark:text-red-300">
                                    <?php echo $stats['overdue']; ?>
                                </p>
                            </div>
                            <span class="material-symbols-outlined text-3xl text-red-500">error</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra de filtros y búsqueda -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Búsqueda -->
                    <div class="flex-1">
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                            <input type="text" id="search-input" value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Buscar tareas..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="flex gap-2 flex-wrap">
                        <select id="filter-status"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Todos los
                                estados</option>
                            <option value="todo" <?php echo $filter_status === 'todo' ? 'selected' : ''; ?>>Por Hacer
                            </option>
                            <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>
                                En Progreso</option>
                            <option value="review" <?php echo $filter_status === 'review' ? 'selected' : ''; ?>>En
                                Revisión</option>
                            <option value="done" <?php echo $filter_status === 'done' ? 'selected' : ''; ?>>Completado
                            </option>
                        </select>

                        <select id="filter-priority"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            <option value="all" <?php echo $filter_priority === 'all' ? 'selected' : ''; ?>>Todas las
                                prioridades</option>
                            <option value="critical" <?php echo $filter_priority === 'critical' ? 'selected' : ''; ?>>
                                Crítica</option>
                            <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>Alta
                            </option>
                            <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Media
                            </option>
                            <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Baja</option>
                        </select>

                        <select id="filter-project"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            <option value="all" <?php echo $filter_project === 'all' ? 'selected' : ''; ?>>Todos los
                                proyectos</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?php echo $proj['id']; ?>" <?php echo $filter_project == $proj['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proj['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="sort-by"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            <option value="due_date" <?php echo $sort === 'due_date' ? 'selected' : ''; ?>>Fecha límite
                            </option>
                            <option value="priority" <?php echo $sort === 'priority' ? 'selected' : ''; ?>>Prioridad
                            </option>
                            <option value="created" <?php echo $sort === 'created' ? 'selected' : ''; ?>>Más recientes
                            </option>
                            <option value="updated" <?php echo $sort === 'updated' ? 'selected' : ''; ?>>Actualizadas
                            </option>
                        </select>
                    </div>

                    <!-- Cambiar vista -->
                    <div class="flex gap-1 border border-gray-300 dark:border-gray-600 rounded-lg p-1">
                        <button onclick="changeView('list')"
                            class="p-2 rounded <?php echo $view === 'list' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                            <span class="material-symbols-outlined text-[20px]">view_list</span>
                        </button>
                        <button onclick="changeView('cards')"
                            class="p-2 rounded <?php echo $view === 'cards' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                            <span class="material-symbols-outlined text-[20px]">grid_view</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista/Grid de Tareas -->
            <?php if (empty($tasks)): ?>
                <div
                    class="text-center py-16 bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700">
                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">task_alt</span>
                    <h3 class="text-xl font-semibold mb-2">No hay tareas</h3>
                    <p class="text-gray-500 mb-4">No se encontraron tareas con los filtros seleccionados</p>
                    <button onclick="openQuickCreateModal()"
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                        Crear Nueva Tarea
                    </button>
                </div>
            <?php else: ?>
                <div id="tasks-container"
                    class="<?php echo $view === 'cards' ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4' : 'space-y-3'; ?>">
                    <?php foreach ($tasks as $task):
                        $is_overdue = $task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] != 'done';
                        $checklist_progress = $task['checklist_total'] > 0 ? ($task['checklist_completed'] / $task['checklist_total']) * 100 : 0;
                        ?>
                        <?php if ($view === 'list'): ?>
                            <!-- Vista de Lista -->
                            <div
                                class="task-card group bg-white dark:bg-card-dark p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
                                <div class="flex items-start gap-4">
                                    <!-- Checkbox de completado rápido -->
                                    <button onclick="quickComplete(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')"
                                        class="mt-1 size-5 rounded border-2 flex items-center justify-center transition-all
                                                   <?php echo $task['status'] === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-primary'; ?>">
                                        <?php if ($task['status'] === 'done'): ?>
                                            <span class="material-symbols-outlined text-white text-[16px]">check</span>
                                        <?php endif; ?>
                                    </button>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-4 mb-2">
                                            <div class="flex-1">
                                                <a href="task-edit.php?id=<?php echo $task['id']; ?>"
                                                    class="font-semibold text-lg hover:text-primary transition-colors <?php echo $task['status'] === 'done' ? 'line-through text-gray-400' : ''; ?>">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </a>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-xs text-gray-500">#<?php echo $task['id']; ?></span>
                                                    <?php if ($task['project_name']): ?>
                                                        <span class="text-xs px-2 py-0.5 rounded"
                                                            style="background-color: <?php echo $task['project_color']; ?>20; color: <?php echo $task['project_color']; ?>">
                                                            <?php echo htmlspecialchars($task['project_name']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <!-- Prioridad -->
                                                <?php if ($task['priority'] === 'critical' || $task['priority'] === 'high'): ?>
                                                    <span
                                                        class="px-2 py-1 rounded text-xs font-medium <?php echo getPriorityColor($task['priority']); ?>">
                                                        <?php echo ucfirst($task['priority']); ?>
                                                    </span>
                                                <?php endif; ?>

                                                <!-- Estado -->
                                                <select onchange="quickStatusChange(<?php echo $task['id']; ?>, this.value)"
                                                    class="px-3 py-1 rounded-lg text-sm font-medium border-0 cursor-pointer <?php echo getStatusColor($task['status']); ?>">
                                                    <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>
                                                        Por Hacer</option>
                                                    <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>En Progreso</option>
                                                    <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>En Revisión</option>
                                                    <option value="done" <?php echo $task['status'] === 'done' ? 'selected' : ''; ?>>
                                                        Completado</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <?php if ($task['due_date']): ?>
                                                <span
                                                    class="flex items-center gap-1 <?php echo $is_overdue ? 'text-red-500 font-medium' : ''; ?>">
                                                    <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                                                    <?php echo formatDate($task['due_date']); ?>
                                                    <?php if ($is_overdue): ?>
                                                        <span class="text-xs">(Vencida)</span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($task['checklist_total'] > 0): ?>
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[16px]">checklist</span>
                                                    <?php echo $task['checklist_completed']; ?>/<?php echo $task['checklist_total']; ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($task['comments_count'] > 0): ?>
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[16px]">comment</span>
                                                    <?php echo $task['comments_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($task['checklist_total'] > 0): ?>
                                            <div class="mt-2">
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                                    <div class="bg-primary h-1.5 rounded-full transition-all"
                                                        style="width: <?php echo $checklist_progress; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Vista de Tarjetas -->
                            <div
                                class="task-card bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-xl transition-all">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <button
                                            onclick="quickComplete(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')"
                                            class="size-5 rounded border-2 flex items-center justify-center transition-all
                                                       <?php echo $task['status'] === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-primary'; ?>">
                                            <?php if ($task['status'] === 'done'): ?>
                                                <span class="material-symbols-outlined text-white text-[16px]">check</span>
                                            <?php endif; ?>
                                        </button>
                                        <span class="text-xs text-gray-500">#<?php echo $task['id']; ?></span>
                                    </div>
                                    <?php if ($task['priority'] === 'critical' || $task['priority'] === 'high'): ?>
                                        <span
                                            class="px-2 py-1 rounded text-xs font-medium <?php echo getPriorityColor($task['priority']); ?>">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <a href="task-edit.php?id=<?php echo $task['id']; ?>" class="block mb-3">
                                    <h3
                                        class="font-semibold text-lg mb-1 hover:text-primary transition-colors <?php echo $task['status'] === 'done' ? 'line-through text-gray-400' : ''; ?>">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </h3>
                                    <?php if ($task['description']): ?>
                                        <p class="text-sm text-gray-500 line-clamp-2">
                                            <?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?>
                                        </p>
                                    <?php endif; ?>
                                </a>

                                <?php if ($task['project_name']): ?>
                                    <div class="mb-3">
                                        <span class="text-xs px-2 py-1 rounded"
                                            style="background-color: <?php echo $task['project_color']; ?>20; color: <?php echo $task['project_color']; ?>">
                                            <?php echo htmlspecialchars($task['project_name']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($task['checklist_total'] > 0): ?>
                                    <div class="mb-3">
                                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                            <span>Progreso</span>
                                            <span><?php echo round($checklist_progress); ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="bg-primary h-1.5 rounded-full transition-all"
                                                style="width: <?php echo $checklist_progress; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3 text-xs text-gray-500">
                                        <?php if ($task['due_date']): ?>
                                            <span class="flex items-center gap-1 <?php echo $is_overdue ? 'text-red-500' : ''; ?>">
                                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                                <?php echo date('d/m', strtotime($task['due_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($task['comments_count'] > 0): ?>
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">comment</span>
                                                <?php echo $task['comments_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <select onchange="quickStatusChange(<?php echo $task['id']; ?>, this.value)"
                                        class="px-2 py-1 rounded text-xs font-medium border-0 cursor-pointer <?php echo getStatusColor($task['status']); ?>">
                                        <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>Por Hacer
                                        </option>
                                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>En Progreso</option>
                                        <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>En
                                            Revisión</option>
                                        <option value="done" <?php echo $task['status'] === 'done' ? 'selected' : ''; ?>>Completado
                                        </option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de Creación Rápida -->
    <div id="quick-create-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold">Crear Nueva Tarea</h3>
                    <button onclick="closeQuickCreateModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <form id="quick-create-form" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Título *</label>
                    <input type="text" name="title" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                        placeholder="Ej: Implementar nueva funcionalidad">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Descripción</label>
                    <textarea name="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                        placeholder="Describe los detalles de la tarea..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Proyecto</label>
                        <select name="project_id" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            <option value="">Seleccionar proyecto</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?php echo $proj['id']; ?>"><?php echo htmlspecialchars($proj['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Prioridad</label>
                        <select name="priority"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            <option value="low">Baja</option>
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                            <option value="critical">Crítica</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Estado</label>
                        <select name="status"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            <option value="todo" selected>Por Hacer</option>
                            <option value="in_progress">En Progreso</option>
                            <option value="review">En Revisión</option>
                            <option value="done">Completado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Fecha límite</label>
                        <input type="date" name="due_date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeQuickCreateModal()"
                        class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-6 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg font-medium">
                        Crear Tarea
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Filtros en tiempo real
        let searchTimeout;
        document.getElementById('search-input').addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => applyFilters(), 500);
        });

        document.getElementById('filter-status').addEventListener('change', applyFilters);
        document.getElementById('filter-priority').addEventListener('change', applyFilters);
        document.getElementById('filter-project').addEventListener('change', applyFilters);
        document.getElementById('sort-by').addEventListener('change', applyFilters);

        function applyFilters() {
            const params = new URLSearchParams();
            const search = document.getElementById('search-input').value;
            const status = document.getElementById('filter-status').value;
            const priority = document.getElementById('filter-priority').value;
            const project = document.getElementById('filter-project').value;
            const sort = document.getElementById('sort-by').value;
            const view = '<?php echo $view; ?>';

            if (search) params.set('search', search);
            if (status !== 'all') params.set('status', status);
            if (priority !== 'all') params.set('priority', priority);
            if (project !== 'all') params.set('project', project);
            if (sort) params.set('sort', sort);
            if (view) params.set('view', view);

            window.location.href = '?' + params.toString();
        }

        function changeView(view) {
            const params = new URLSearchParams(window.location.search);
            params.set('view', view);
            window.location.href = '?' + params.toString();
        }

        // Cambio rápido de estado
        function quickStatusChange(taskId, newStatus) {
            fetch('/PROYECTA/api/tasks/update-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: taskId, status: newStatus })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Estado actualizado', 'success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                });
        }

        // Completar rápido
        function quickComplete(taskId, currentStatus) {
            const newStatus = currentStatus === 'done' ? 'todo' : 'done';
            quickStatusChange(taskId, newStatus);
        }

        // Modal de creación rápida
        function openQuickCreateModal() {
            document.getElementById('quick-create-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeQuickCreateModal() {
            document.getElementById('quick-create-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Enviar formulario de creación rápida
        document.getElementById('quick-create-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('/PROYECTA/api/tasks/create.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Tarea creada exitosamente', 'success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                });
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('quick-create-modal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeQuickCreateModal();
            }
        });
    </script>
</body>

</html>