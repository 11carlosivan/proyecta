<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/activity_helper.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    header('Location: tasks.php');
    exit;
}

// Obtener detalles de la tarea
$query = "SELECT t.*, p.name as project_name, p.color as project_color,
          u.full_name as assignee_name, u.avatar_url as assignee_avatar,
          r.full_name as reporter_name
          FROM tasks t 
          LEFT JOIN projects p ON t.project_id = p.id
          LEFT JOIN users u ON t.assignee_id = u.id
          LEFT JOIN users r ON t.reporter_id = r.id
          WHERE t.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $task_id);
$stmt->execute();
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header('Location: tasks.php');
    exit;
}

// Obtener comentarios
$query = "SELECT c.*, u.full_name, u.avatar_url 
          FROM task_comments c 
          JOIN users u ON c.user_id = u.id 
          WHERE c.task_id = :task_id 
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener checklist
$query = "SELECT * FROM task_checklist WHERE task_id = :task_id ORDER BY position ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->execute();
$checklist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de cambios
$query = "SELECT h.*, u.full_name 
          FROM task_history h 
          JOIN users u ON h.user_id = u.id 
          WHERE h.task_id = :task_id 
          ORDER BY h.created_at DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->execute();
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener registro de tiempo
$query = "SELECT tl.*, u.full_name 
          FROM task_time_logs tl 
          JOIN users u ON tl.user_id = u.id 
          WHERE tl.task_id = :task_id 
          ORDER BY tl.logged_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->execute();
$time_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular tiempo total registrado
$total_time = array_sum(array_column($time_logs, 'hours'));

// Obtener etiquetas disponibles
$query = "SELECT * FROM tags ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener etiquetas de la tarea
$query = "SELECT tag_id FROM task_tags WHERE task_id = :task_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->execute();
$task_tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener adjuntos
$query = "SELECT * FROM task_attachments WHERE task_id = :task_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->execute();
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuarios para asignación
$query = "SELECT id, full_name, avatar_url FROM users WHERE id IN (
          SELECT user_id FROM project_members WHERE project_id = :project_id
          ) ORDER BY full_name";
$stmt = $db->prepare($query);
$stmt->bindParam(':project_id', $task['project_id']);
$stmt->execute();
$team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Tarea #" . $task['id'] . " - " . $task['title'];
$is_locked = $task['status'] === 'done' && $_SESSION['user_role'] !== 'admin';

// Calcular progreso del checklist
$checklist_total = count($checklist);
$checklist_completed = count(array_filter($checklist, fn($item) => $item['is_completed']));
$checklist_progress = $checklist_total > 0 ? ($checklist_completed / $checklist_total) * 100 : 0;
?>

<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>PROYECTA - <?php echo htmlspecialchars($page_title); ?></title>
    <?php include '../../includes/head.php'; ?>
    <style>
        .tab-content {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .comment-item:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .dark .comment-item:hover {
            background-color: rgba(255,255,255,0.02);
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white font-display h-screen overflow-hidden">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 h-screen overflow-y-auto">
        <?php include '../../includes/header.php'; ?>

        <div class="p-6 max-w-7xl mx-auto">
            <!-- Header de la tarea -->
            <div class="mb-6">
                <div class="flex items-center gap-3 text-sm text-gray-500 mb-3">
                    <a href="tasks.php" class="hover:text-primary flex items-center gap-1">
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        Mis Tareas
                    </a>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($task['project_name']); ?></span>
                    <span>/</span>
                    <span class="text-gray-400">#<?php echo $task['id']; ?></span>
                </div>

                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($task['title']); ?></h1>
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="px-3 py-1 rounded-lg text-sm font-medium <?php echo getStatusColor($task['status']); ?>">
                                <?php 
                                $status_labels = ['todo' => 'Por Hacer', 'in_progress' => 'En Progreso', 'review' => 'En Revisión', 'done' => 'Completado'];
                                echo $status_labels[$task['status']];
                                ?>
                            </span>
                            <span class="px-3 py-1 rounded-lg text-sm font-medium <?php echo getPriorityColor($task['priority']); ?>">
                                Prioridad: <?php echo ucfirst($task['priority']); ?>
                            </span>
                            <?php if ($task['project_name']): ?>
                                <span class="px-3 py-1 rounded-lg text-sm" style="background-color: <?php echo $task['project_color']; ?>20; color: <?php echo $task['project_color']; ?>">
                                    <?php echo htmlspecialchars($task['project_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="../kanban/kanban.php?project_id=<?php echo $task['project_id']; ?>" 
                           class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">view_kanban</span>
                            Ver Tablero
                        </a>
                    </div>
                </div>

                <?php if ($is_locked): ?>
                    <div class="mt-4 flex items-center gap-2 text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 px-4 py-3 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <span class="material-symbols-outlined">lock</span>
                        <span class="text-sm font-medium">Esta tarea está bloqueada porque está completada. Cambia el estado para poder editarla.</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Principal (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Pestañas -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="flex gap-1 px-4" id="task-tabs">
                                <button class="tab-btn active px-4 py-3 border-b-2 border-primary text-primary font-medium" data-tab="details">
                                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">description</span>
                                    Detalles
                                </button>
                                <button class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium" data-tab="comments">
                                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">comment</span>
                                    Comentarios
                                    <span class="ml-1 px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded-full text-xs"><?php echo count($comments); ?></span>
                                </button>
                                <button class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium" data-tab="checklist">
                                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">checklist</span>
                                    Checklist
                                    <span class="ml-1 px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded-full text-xs"><?php echo $checklist_completed; ?>/<?php echo $checklist_total; ?></span>
                                </button>
                                <button class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium" data-tab="history">
                                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">history</span>
                                    Historial
                                </button>
                                <button class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium" data-tab="time">
                                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">schedule</span>
                                    Tiempo
                                </button>
                            </nav>
                        </div>

                        <div class="p-6">
                            <!-- Tab: Detalles -->
                            <div id="tab-details" class="tab-content">
                                <form id="edit-task-form" class="space-y-4">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">

                                    <div>
                                        <label class="block text-sm font-medium mb-2">Título</label>
                                        <input type="text" name="title" value="<?php echo htmlspecialchars($task['title']); ?>"
                                            required <?php echo $is_locked ? 'disabled' : ''; ?>
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 disabled:opacity-60 disabled:cursor-not-allowed">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-2">Descripción</label>
                                        <textarea name="description" rows="6" <?php echo $is_locked ? 'disabled' : ''; ?>
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 disabled:opacity-60 disabled:cursor-not-allowed"><?php echo htmlspecialchars($task['description']); ?></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium mb-2">Horas Estimadas</label>
                                            <input type="number" name="estimated_hours" step="0.5" <?php echo $is_locked ? 'disabled' : ''; ?>
                                                value="<?php echo $task['estimated_hours']; ?>"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 disabled:opacity-60 disabled:cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-2">Fecha Límite</label>
                                            <input type="date" name="due_date" value="<?php echo $task['due_date']; ?>" <?php echo $is_locked ? 'disabled' : ''; ?>
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 disabled:opacity-60 disabled:cursor-not-allowed">
                                        </div>
                                    </div>

                                    <!-- Archivos Adjuntos -->
                                    <div>
                                        <label class="block text-sm font-medium mb-2">Archivos Adjuntos</label>
                                        <div class="grid grid-cols-3 gap-3 mb-3">
                                            <?php foreach ($attachments as $file): ?>
                                                <a href="<?php echo $file['file_path']; ?>" target="_blank"
                                                    class="group relative block aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 hover:border-primary">
                                                    <?php if (strpos($file['file_type'], 'image') !== false): ?>
                                                        <img src="<?php echo $file['file_path']; ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                                            <span class="material-symbols-outlined text-4xl">description</span>
                                                            <span class="text-xs px-2 text-center truncate w-full mt-1">
                                                                <?php echo $file['file_name']; ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if (!$is_locked): ?>
                                            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                <input type="file" id="file-upload" class="hidden" onchange="uploadFile(this)">
                                                <label for="file-upload" class="cursor-pointer block">
                                                    <span class="material-symbols-outlined text-3xl text-gray-400">cloud_upload</span>
                                                    <p class="text-sm text-gray-500 mt-2">Haz clic para subir un archivo</p>
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex justify-end pt-4">
                                        <?php if (!$is_locked): ?>
                                            <button type="submit"
                                                class="px-6 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg font-medium">
                                                Guardar Cambios
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>

                            <!-- Tab: Comentarios -->
                            <div id="tab-comments" class="tab-content hidden">
                                <div class="space-y-4">
                                    <!-- Formulario nuevo comentario -->
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                        <textarea id="new-comment" rows="3" placeholder="Escribe un comentario..."
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 mb-2"></textarea>
                                        <div class="flex justify-end">
                                            <button onclick="addComment()" 
                                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                                                Comentar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Lista de comentarios -->
                                    <div id="comments-list" class="space-y-3">
                                        <?php if (empty($comments)): ?>
                                            <p class="text-center text-gray-500 py-8">No hay comentarios aún</p>
                                        <?php else: ?>
                                            <?php foreach ($comments as $comment): ?>
                                                <div class="comment-item flex gap-3 p-3 rounded-lg transition-colors">
                                                    <div class="size-10 rounded-full bg-primary flex items-center justify-center text-white font-medium overflow-hidden flex-shrink-0">
                                                        <?php if ($comment['avatar_url']): ?>
                                                            <img src="<?php echo htmlspecialchars($comment['avatar_url']); ?>" alt="Avatar" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <?php echo strtoupper(substr($comment['full_name'], 0, 1)); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <span class="font-medium"><?php echo htmlspecialchars($comment['full_name']); ?></span>
                                                            <span class="text-xs text-gray-500"><?php echo getActivityTime($comment['created_at']); ?></span>
                                                        </div>
                                                        <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Checklist -->
                            <div id="tab-checklist" class="tab-content hidden">
                                <div class="space-y-4">
                                    <!-- Progreso -->
                                    <?php if ($checklist_total > 0): ?>
                                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-sm font-medium">Progreso</span>
                                                <span class="text-sm font-bold text-primary"><?php echo round($checklist_progress); ?>%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div class="bg-primary h-2 rounded-full transition-all" style="width: <?php echo $checklist_progress; ?>%"></div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-2"><?php echo $checklist_completed; ?> de <?php echo $checklist_total; ?> completadas</p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Formulario nuevo item -->
                                    <?php if (!$is_locked): ?>
                                        <div class="flex gap-2">
                                            <input type="text" id="new-checklist-item" placeholder="Agregar nueva subtarea..."
                                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                                                onkeypress="if(event.key==='Enter') addChecklistItem()">
                                            <button onclick="addChecklistItem()" 
                                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                                                <span class="material-symbols-outlined">add</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Lista de checklist -->
                                    <div id="checklist-items" class="space-y-2">
                                        <?php if (empty($checklist)): ?>
                                            <p class="text-center text-gray-500 py-8">No hay items en el checklist</p>
                                        <?php else: ?>
                                            <?php foreach ($checklist as $item): ?>
                                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                                    <input type="checkbox" 
                                                        <?php echo $item['is_completed'] ? 'checked' : ''; ?>
                                                        onchange="toggleChecklistItem(<?php echo $item['id']; ?>, this.checked)"
                                                        class="size-5 rounded border-gray-300 text-primary focus:ring-primary">
                                                    <span class="flex-1 <?php echo $item['is_completed'] ? 'line-through text-gray-400' : ''; ?>">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Historial -->
                            <div id="tab-history" class="tab-content hidden">
                                <div class="space-y-3">
                                    <?php if (empty($history)): ?>
                                        <p class="text-center text-gray-500 py-8">No hay historial de cambios</p>
                                    <?php else: ?>
                                        <?php foreach ($history as $change): ?>
                                            <div class="flex gap-3 p-3 border-l-2 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 rounded transition-colors">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="font-medium text-sm"><?php echo htmlspecialchars($change['full_name']); ?></span>
                                                        <span class="text-xs text-gray-500"><?php echo getActivityTime($change['created_at']); ?></span>
                                                    </div>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                                        Cambió <span class="font-medium"><?php echo htmlspecialchars($change['field_name']); ?></span>
                                                        <?php if ($change['old_value']): ?>
                                                            de "<span class="text-red-500"><?php echo htmlspecialchars($change['old_value']); ?></span>"
                                                        <?php endif; ?>
                                                        a "<span class="text-green-500"><?php echo htmlspecialchars($change['new_value']); ?></span>"
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tab: Tiempo -->
                            <div id="tab-time" class="tab-content hidden">
                                <div class="space-y-4">
                                    <!-- Resumen de tiempo -->
                                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Tiempo Total Registrado</p>
                                                <p class="text-3xl font-bold text-primary"><?php echo number_format($total_time, 1); ?>h</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Estimado</p>
                                                <p class="text-2xl font-bold"><?php echo $task['estimated_hours'] ?? '0'; ?>h</p>
                                            </div>
                                        </div>
                                        <?php if ($task['estimated_hours']): ?>
                                            <div class="mt-3">
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    <?php $time_progress = min(($total_time / $task['estimated_hours']) * 100, 100); ?>
                                                    <div class="<?php echo $time_progress > 100 ? 'bg-red-500' : 'bg-primary'; ?> h-2 rounded-full transition-all" 
                                                         style="width: <?php echo $time_progress; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Formulario registrar tiempo -->
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                        <h4 class="font-medium mb-3">Registrar Tiempo</h4>
                                        <div class="grid grid-cols-2 gap-3">
                                            <input type="number" id="log-hours" step="0.5" min="0.5" placeholder="Horas"
                                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                            <input type="date" id="log-date" value="<?php echo date('Y-m-d'); ?>"
                                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                        </div>
                                        <textarea id="log-description" rows="2" placeholder="Descripción (opcional)" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 mt-3"></textarea>
                                        <button onclick="logTime()" 
                                            class="w-full mt-3 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                                            Registrar Tiempo
                                        </button>
                                    </div>

                                    <!-- Lista de registros -->
                                    <div class="space-y-2">
                                        <h4 class="font-medium">Registros de Tiempo</h4>
                                        <?php if (empty($time_logs)): ?>
                                            <p class="text-center text-gray-500 py-8">No hay tiempo registrado</p>
                                        <?php else: ?>
                                            <?php foreach ($time_logs as $log): ?>
                                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-medium"><?php echo htmlspecialchars($log['full_name']); ?></span>
                                                            <span class="text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($log['logged_at'])); ?></span>
                                                        </div>
                                                        <?php if ($log['description']): ?>
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($log['description']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="text-lg font-bold text-primary"><?php echo number_format($log['hours'], 1); ?>h</span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Lateral (1/3) -->
                <div class="space-y-6">
                    <!-- Estado y Prioridad -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Estado</label>
                            <select form="edit-task-form" name="status"
                                class="w-full px-3 py-2 border-2 border-blue-500 rounded-lg bg-white dark:bg-gray-700 font-medium">
                                <option value="todo" <?php echo $task['status'] == 'todo' ? 'selected' : ''; ?>>Por Hacer</option>
                                <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>En Progreso</option>
                                <option value="review" <?php echo $task['status'] == 'review' ? 'selected' : ''; ?>>En Revisión</option>
                                <option value="done" <?php echo $task['status'] == 'done' ? 'selected' : ''; ?>>Completado</option>
                            </select>
                            <?php if ($is_locked): ?>
                                <p class="text-xs text-blue-500 mt-1">* Cambia el estado para desbloquear la tarea</p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Prioridad</label>
                            <select form="edit-task-form" name="priority" <?php echo $is_locked ? 'disabled' : ''; ?>
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 disabled:opacity-60">
                                <option value="low" <?php echo $task['priority'] == 'low' ? 'selected' : ''; ?>>Baja</option>
                                <option value="medium" <?php echo $task['priority'] == 'medium' ? 'selected' : ''; ?>>Media</option>
                                <option value="high" <?php echo $task['priority'] == 'high' ? 'selected' : ''; ?>>Alta</option>
                                <option value="critical" <?php echo $task['priority'] == 'critical' ? 'selected' : ''; ?>>Crítica</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Asignado a</label>
                            <select form="edit-task-form" name="assignee_id" <?php echo $is_locked ? 'disabled' : ''; ?>
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 disabled:opacity-60">
                                <?php foreach ($team_members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>" <?php echo $task['assignee_id'] == $member['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Etiquetas -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold">Etiquetas</h3>
                            <button onclick="document.getElementById('new-tag-input').classList.toggle('hidden')"
                                class="text-primary hover:underline text-sm">
                                + Nueva
                            </button>
                        </div>

                        <!-- Formulario nueva etiqueta -->
                        <div id="new-tag-input" class="hidden mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <input type="text" id="tag-name" placeholder="Nombre..."
                                class="w-full mb-2 px-2 py-1 text-sm rounded border">
                            <div class="flex gap-2 mb-2">
                                <div class="size-6 rounded-full bg-red-500 cursor-pointer border-2 border-transparent hover:border-white"
                                    onclick="selectColor('bg-red-500')"></div>
                                <div class="size-6 rounded-full bg-blue-500 cursor-pointer border-2 border-transparent hover:border-white"
                                    onclick="selectColor('bg-blue-500')"></div>
                                <div class="size-6 rounded-full bg-green-500 cursor-pointer border-2 border-transparent hover:border-white"
                                    onclick="selectColor('bg-green-500')"></div>
                                <div class="size-6 rounded-full bg-purple-500 cursor-pointer border-2 border-transparent hover:border-white"
                                    onclick="selectColor('bg-purple-500')"></div>
                                <input type="hidden" id="tag-color" value="bg-blue-500">
                            </div>
                            <button onclick="createTag()"
                                class="w-full bg-primary text-white text-xs py-1 rounded">Crear</button>
                        </div>

                        <div id="tags-container" class="space-y-2">
                            <?php foreach ($all_tags as $tag): ?>
                                <label
                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded transition-colors">
                                    <input type="checkbox" form="edit-task-form" name="tags[]"
                                        value="<?php echo $tag['id']; ?>"
                                        class="rounded text-primary focus:ring-primary bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                                        <?php echo in_array($tag['id'], $task_tags) ? 'checked' : ''; ?>>
                                    <span class="px-2 py-0.5 rounded text-xs text-white <?php echo $tag['color']; ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold mb-3">Información</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Creado por</span>
                                <span class="font-medium"><?php echo htmlspecialchars($task['reporter_name']); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Creado</span>
                                <span class="font-medium"><?php echo formatDate($task['created_at']); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Actualizado</span>
                                <span class="font-medium"><?php echo formatDate($task['updated_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
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

        // Guardar cambios
        document.getElementById('edit-task-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const tags = Array.from(document.querySelectorAll('input[name="tags[]"]:checked')).map(el => el.value);
            formData.set('tags', JSON.stringify(tags));

            fetch('/PROYECTA/api/tasks/update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Tarea guardada correctamente', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        });

        // Agregar comentario
        function addComment() {
            const comment = document.getElementById('new-comment').value.trim();
            if (!comment) return;

            fetch('/PROYECTA/api/tasks/add-comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    task_id: <?php echo $task_id; ?>,
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Comentario agregado', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        }

        // Agregar item al checklist
        function addChecklistItem() {
            const title = document.getElementById('new-checklist-item').value.trim();
            if (!title) return;

            fetch('/PROYECTA/api/tasks/add-checklist-item.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    task_id: <?php echo $task_id; ?>,
                    title: title
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Item agregado', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        }

        // Toggle checklist item
        function toggleChecklistItem(itemId, isCompleted) {
            fetch('/PROYECTA/api/tasks/toggle-checklist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    item_id: itemId,
                    is_completed: isCompleted
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Checklist actualizado', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        }

        // Registrar tiempo
        function logTime() {
            const hours = document.getElementById('log-hours').value;
            const date = document.getElementById('log-date').value;
            const description = document.getElementById('log-description').value;

            if (!hours || !date) {
                showNotification('Por favor completa los campos requeridos', 'error');
                return;
            }

            fetch('/PROYECTA/api/tasks/log-time.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    task_id: <?php echo $task_id; ?>,
                    hours: hours,
                    logged_at: date,
                    description: description
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Tiempo registrado', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        }

        // Subir archivo
        function uploadFile(input) {
            if (!input.files || !input.files[0]) return;

            const formData = new FormData();
            formData.append('file', input.files[0]);
            formData.append('task_id', <?php echo $task['id']; ?>);

            showNotification('Subiendo archivo...', 'info');

            fetch('/PROYECTA/api/tasks/upload-attachment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Archivo subido', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        }

        // Crear etiqueta
        let selectedColor = 'bg-blue-500';
        function selectColor(color) {
            selectedColor = color;
            document.getElementById('tag-color').value = color;
        }

        function createTag() {
            const name = document.getElementById('tag-name').value;
            const color = document.getElementById('tag-color').value;

            if (!name) return;

            const formData = new FormData();
            formData.append('name', name);
            formData.append('color', color);

            fetch('/PROYECTA/api/tags/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            });
        }
    </script>
</body>

</html>