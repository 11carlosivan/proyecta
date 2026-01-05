<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$page_title = "Tablero Kanban";

// Obtener proyecto si se especifica
$project_id = $_GET['project_id'] ?? null;
$project = null;

if ($project_id) {
    $query = "SELECT * FROM projects WHERE id = :project_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener tareas con etiquetas
$where_conds = [];
$params = [];

if ($project_id) {
    $where_conds[] = "t.project_id = :project_id";
    $params[':project_id'] = $project_id;
}

// RESTRICTION FOR CLIENTS: Only see tasks from projects where they are members
if ($_SESSION['user_role'] === 'client') {
    $where_conds[] = "t.project_id IN (SELECT project_id FROM project_members WHERE user_id = :client_id)";
    $params[':client_id'] = $_SESSION['user_id'];
}

$where_clause = !empty($where_conds) ? "WHERE " . implode(" AND ", $where_conds) : "";

$query = "SELECT t.*, 
         u.full_name as assignee_name,
         u.avatar_url as assignee_avatar,
         p.name as project_name,
         p.color as project_color,
         GROUP_CONCAT(tags.name ORDER BY tags.name SEPARATOR '|||') as tag_names,
         GROUP_CONCAT(tags.color ORDER BY tags.name SEPARATOR '|||') as tag_colors
         FROM tasks t
         LEFT JOIN users u ON t.assignee_id = u.id
         LEFT JOIN projects p ON t.project_id = p.id
         LEFT JOIN task_tags tt ON t.id = tt.task_id
         LEFT JOIN tags ON tt.tag_id = tags.id
         $where_clause
         GROUP BY t.id
         ORDER BY t.priority DESC, t.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar tareas por estado
$tasks_by_status = [
    'todo' => [],
    'in_progress' => [],
    'review' => [],
    'done' => []
];

foreach ($tasks as $task) {
    $tasks_by_status[$task['status']][] = $task;
}

// Columnas del Kanban
$columns = [
    'todo' => [
        'title' => 'Por Hacer',
        'color' => 'bg-slate-500',
        'icon' => 'radio_button_unchecked'
    ],
    'in_progress' => [
        'title' => 'En Progreso',
        'color' => 'bg-orange-500',
        'icon' => 'timelapse'
    ],
    'review' => [
        'title' => 'En Revisión',
        'color' => 'bg-purple-500',
        'icon' => 'visibility'
    ],
    'done' => [
        'title' => 'Completado',
        'color' => 'bg-emerald-500',
        'icon' => 'check_circle'
    ]
];
?>

<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>PROYECTA - <?php echo $page_title; ?></title>
    <?php include '../../includes/head.php'; ?>

    <style>
        .kanban-column {
            min-height: 500px;
            background: linear-gradient(to bottom, var(--surface), var(--surface) 50px, transparent);
        }

        .task-card {
            transition: all 0.2s ease;
            cursor: move;
        }

        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .task-card.dragging {
            opacity: 0.5;
            transform: rotate(3deg);
        }

        .column-drop-zone {
            border: 2px dashed var(--primary);
            background-color: var(--primary-light);
        }
    </style>
</head>

<body
    class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white font-display h-screen overflow-hidden">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 flex flex-col h-screen">
        <?php include '../../includes/header.php'; ?>
        <?php
        $header_title = $project ? "Tablero - " . htmlspecialchars($project['name']) : "Tablero Kanban";
        $header_description = $project ? htmlspecialchars($project['description']) : "Organiza tus tareas visualmente";
        include '../../includes/page-header.php';
        ?>

        <!-- Filtros y acciones -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <!-- Selector de proyecto -->
                    <div class="relative">
                        <select id="project-filter"
                            class="pl-10 pr-8 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 appearance-none">
                            <option value="">Todos los proyectos</option>
                            <?php
                            $query = "SELECT * FROM projects ORDER BY name";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $all_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($all_projects as $proj):
                                ?>
                                <option value="<?php echo $proj['id']; ?>" <?php echo $project_id == $proj['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proj['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">folder</span>
                        <span
                            class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-gray-400">expand_more</span>
                    </div>

                    <!-- Botón nueva tarea -->
                    <button onclick="openModal('create-task-modal')"
                        class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                        <span class="material-symbols-outlined">add</span>
                        Nueva Tarea
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Vista -->
                    <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <span class="material-symbols-outlined">grid_view</span>
                    </button>
                    <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <span class="material-symbols-outlined">view_list</span>
                    </button>

                    <!-- Exportar -->
                    <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <span class="material-symbols-outlined">download</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tablero Kanban -->
        <div class="flex-1 overflow-x-auto p-4">
            <div class="kanban-board flex gap-4 min-w-max">
                <?php foreach ($columns as $status => $column): ?>
                    <div
                        class="kanban-column w-80 flex-shrink-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <!-- Cabecera de columna -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="size-3 rounded-full <?php echo $column['color']; ?>"></span>
                                    <h3 class="font-semibold"><?php echo $column['title']; ?></h3>
                                    <span
                                        class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold px-2 py-0.5 rounded-full">
                                        <?php echo count($tasks_by_status[$status]); ?>
                                    </span>
                                </div>
                                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <span class="material-symbols-outlined">more_horiz</span>
                                </button>
                            </div>
                        </div>

                        <!-- Contenido de columna -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-3 kanban-drop-zone"
                            data-status="<?php echo $status; ?>">
                            <?php foreach ($tasks_by_status[$status] as $task): ?>
                                <div class="task-card bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm"
                                    data-task-id="<?php echo $task['id']; ?>" draggable="true">
                                    <!-- Etiquetas -->
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                class="text-xs px-2 py-1 rounded-full <?php echo getPriorityColor($task['priority']); ?>">
                                                <?php echo ucfirst($task['priority']); ?>
                                            </span>

                                            <?php
                                            if ($task['tag_names']):
                                                $names = explode('|||', $task['tag_names']);
                                                $colors = explode('|||', $task['tag_colors']);
                                                foreach ($names as $index => $name):
                                                    $color = $colors[$index] ?? 'bg-gray-500';
                                                    ?>
                                                    <span class="text-xs px-2 py-1 rounded-full text-white <?php echo $color; ?>">
                                                        <?php echo htmlspecialchars($name); ?>
                                                    </span>
                                                    <?php
                                                endforeach;
                                            endif;
                                            ?>
                                        </div>

                                        <?php if ($task['project_color']): ?>
                                            <span class="size-3 rounded-full flex-shrink-0"
                                                style="background-color: <?php echo $task['project_color']; ?>"></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Título -->
                                    <h4 class="font-medium text-sm mb-3 line-clamp-2">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </h4>

                                    <!-- Meta información -->
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center gap-3">
                                            <?php if ($task['assignee_name']): ?>
                                                <div class="flex items-center gap-1">
                                                    <?php echo getUserAvatar(['full_name' => $task['assignee_name'], 'avatar_url' => $task['assignee_avatar']], 20); ?>
                                                    <span><?php echo htmlspecialchars($task['assignee_name']); ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($task['due_date']): ?>
                                                <div class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                                                    <span><?php echo formatDate($task['due_date'], 'd/m'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Acciones -->
                                        <div class="flex items-center gap-1">
                                            <button onclick="editTask(<?php echo $task['id']; ?>)"
                                                class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-gray-500 hover:text-blue-500">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                            </button>
                                            <button onclick="deleteTask(<?php echo $task['id']; ?>)"
                                                class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-gray-500 hover:text-red-500">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Progreso -->
                                    <?php if ($task['estimated_hours'] && $task['actual_hours']):
                                        $progress = ($task['actual_hours'] / $task['estimated_hours']) * 100;
                                        ?>
                                        <div class="mt-3">
                                            <div class="flex justify-between text-xs mb-1">
                                                <span>Progreso</span>
                                                <span><?php echo round($progress); ?>%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full bg-primary"
                                                    style="width: <?php echo min($progress, 100); ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <!-- Espacio para nuevas tareas -->
                            <button onclick="openModal('create-task-modal')"
                                class="add-task-btn w-full p-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 transition-colors flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">add</span>
                                <span>Añadir tarea</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Modal para crear tarea -->
    <div id="create-task-modal"
        class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold">Nueva Tarea</h3>
                    <button onclick="closeModal('create-task-modal')" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="create-task-form" method="POST" action="../../api/tasks/create.php">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Título *</label>
                                <input type="text" name="title" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Descripción</label>
                                <textarea name="description" rows="5"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Proyecto</label>
                                <select name="project_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                    <option value="">Sin proyecto</option>
                                    <?php foreach ($all_projects as $proj): ?>
                                        <option value="<?php echo $proj['id']; ?>" <?php echo $project_id == $proj['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($proj['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Asignar a</label>
                                <select name="assignee_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                    <option value="">Sin asignar</option>
                                    <?php
                                    // Obtener todos los usuarios
                                    $query = "SELECT * FROM users ORDER BY full_name";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($users as $user):
                                        ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $_SESSION['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Estado</label>
                                    <select name="status"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                        <option value="todo">Por Hacer</option>
                                        <option value="in_progress">En Progreso</option>
                                        <option value="review">En Revisión</option>
                                        <option value="done">Completado</option>
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
                                    <label class="block text-sm font-medium mb-2">Fecha límite</label>
                                    <input type="date" name="due_date"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Horas estimadas</label>
                                    <input type="number" name="estimated_hours" step="0.5" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Etiquetas</label>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded text-xs">
                                        <span>Backend</span>
                                        <button type="button" class="hover:text-blue-800">
                                            <span class="material-symbols-outlined text-sm">close</span>
                                        </button>
                                    </span>
                                    <button type="button"
                                        class="px-2 py-1 border border-dashed border-gray-300 dark:border-gray-600 rounded text-xs text-gray-400 hover:text-gray-600">
                                        <span class="material-symbols-outlined text-sm">add</span>
                                        Añadir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeModal('create-task-modal')"
                            class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                            Crear Tarea
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Filtro de proyecto
        document.getElementById('project-filter').addEventListener('change', function () {
            const projectId = this.value;
            if (projectId) {
                window.location.href = 'kanban.php?project_id=' + projectId;
            } else {
                window.location.href = 'kanban.php';
            }
        });

        // Drag & Drop para Kanban
        let draggedTask = null;

        document.querySelectorAll('.task-card').forEach(task => {
            task.addEventListener('dragstart', function (e) {
                draggedTask = this;
                this.classList.add('dragging');
                e.dataTransfer.setData('text/plain', this.dataset.taskId);
            });

            task.addEventListener('dragend', function () {
                this.classList.remove('dragging');
                draggedTask = null;
            });
        });

        document.querySelectorAll('.kanban-drop-zone').forEach(zone => {
            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('column-drop-zone');
            });

            zone.addEventListener('dragleave', function () {
                this.classList.remove('column-drop-zone');
            });

            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('column-drop-zone');

                if (draggedTask) {
                    const taskId = draggedTask.dataset.taskId;
                    const newStatus = this.dataset.status;

                    // Mover visualmente la tarjeta
                    const addButton = this.querySelector('.add-task-btn');
                    if (addButton) {
                        this.insertBefore(draggedTask, addButton);
                    } else {
                        this.appendChild(draggedTask);
                    }

                    // Actualizar en servidor
                    updateTaskStatus(taskId, newStatus);
                }
            });
        });

        function updateTaskStatus(taskId, status) {
            fetch('../../api/tasks/update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    task_id: taskId,
                    status: status
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Tarea actualizada', 'success');
                    } else {
                        showNotification('Error al actualizar tarea', 'error');
                        // Revertir visualmente si hay error
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error de conexión', 'error');
                });
        }

        // Formulario de tarea
        document.getElementById('create-task-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Tarea creada', 'success');
                        closeModal('create-task-modal');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error de conexión', 'error');
                });
        });

        // Funciones auxiliares
        function editTask(taskId) {
            window.location.href = '../tasks/task-edit.php?id=' + taskId;
        }

        function deleteTask(taskId) {
            if (confirm('¿Estás seguro de eliminar esta tarea?')) {
                fetch('../../api/tasks/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ task_id: taskId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Tarea eliminada', 'success');
                            location.reload();
                        } else {
                            showNotification('Error al eliminar tarea', 'error');
                        }
                    });
            }
        }

        // Funciones para modales
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });
    </script>
</body>

</html>