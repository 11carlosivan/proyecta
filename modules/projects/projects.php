<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/activity_helper.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$page_title = "Proyectos";

// Crear nuevo proyecto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $color = $_POST['color'] ?? '#137fec';
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    
    $query = "INSERT INTO projects (name, description, color, start_date, end_date, owner_id) 
              VALUES (:name, :description, :color, :start_date, :end_date, :owner_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':color', $color);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':owner_id', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $project_id = $db->lastInsertId();
        
        // Agregar creador como miembro admin
        $query = "INSERT INTO project_members (project_id, user_id, role) VALUES (:project_id, :user_id, 'admin')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        // Registrar actividad de creación del proyecto
        logProjectActivity(
            $db,
            $project_id,
            $_SESSION['user_id'],
            'created',
            'project',
            $project_id,
            "creó el proyecto",
            [
                'project_name' => $name,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]
        );
        
        // Registrar que se agregó como miembro admin
        logProjectActivity(
            $db,
            $project_id,
            $_SESSION['user_id'],
            'member_added',
            'member',
            $_SESSION['user_id'],
            "se unió al proyecto como administrador"
        );
        
        // Crear notificación
        createNotification($db, $_SESSION['user_id'], 
            "Proyecto creado", 
            "Has creado el proyecto: $name",
            'project'
        );
        
        header('Location: project.php?id=' . $project_id);
        exit();
    }
}

// Obtener proyectos del usuario
$query = "SELECT p.*, 
         (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as task_count,
         (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'done') as completed_tasks
         FROM projects p 
         WHERE p.owner_id = :user_id OR p.id IN (SELECT project_id FROM project_members WHERE user_id = :user_id2)
         ORDER BY p.created_at DESC";
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
    <?php include '../../includes/head.php'; ?>
</head>
<body class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white font-display h-screen overflow-hidden">
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="ml-0 md:ml-64 flex-1">
        <?php include '../../includes/header.php'; ?>
        <?php 
        $header_title = "Proyectos";
        $header_description = "Gestiona todos tus proyectos en un solo lugar";
        include '../../includes/page-header.php'; 
        ?>
        
        <div class="p-6">
            <!-- Botón para crear proyecto -->
            <div class="mb-6">
                <button onclick="openModal('create-project-modal')" 
                        class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-blue-600 text-white rounded-lg font-medium">
                    <span class="material-symbols-outlined">add</span>
                    Nuevo Proyecto
                </button>
            </div>
            
            <!-- Grid de proyectos -->
            <?php if (empty($projects)): ?>
            <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-4">folder_off</span>
                <h3 class="text-lg font-semibold mb-2">No hay proyectos</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Crea tu primer proyecto para comenzar</p>
                <button onclick="openModal('create-project-modal')" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                    <span class="material-symbols-outlined">add</span>
                    Crear Proyecto
                </button>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($projects as $project): 
                    $progress = $project['task_count'] > 0 ? ($project['completed_tasks'] / $project['task_count']) * 100 : 0;
                    $days_remaining = daysRemaining($project['end_date']);
                ?>
                <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
                    <!-- Cabecera con color -->
                    <div class="h-2" style="background-color: <?php echo $project['color']; ?>"></div>
                    
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($project['name']); ?></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2"><?php echo htmlspecialchars($project['description']); ?></p>
                            </div>
                            <div class="dropdown relative">
                                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <span class="material-symbols-outlined">more_vert</span>
                                </button>
                                <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-10">
                                    <a href="project.php?id=<?php echo $project['id']; ?>" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Ver detalles</a>
                                    <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Editar</a>
                                    <a href="#" onclick="confirmDeleteProject(<?php echo $project['id']; ?>)" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-red-500">Eliminar</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500 dark:text-gray-400">Progreso</span>
                                <span class="font-medium"><?php echo round($progress); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full" style="background-color: <?php echo $project['color']; ?>; width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Meta información -->
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                    <span class="material-symbols-outlined text-sm">assignment</span>
                                    <?php echo $project['task_count']; ?>
                                </span>
                                <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                    <span class="material-symbols-outlined text-sm">group</span>
                                    <?php 
                                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM project_members WHERE project_id = :project_id");
                                    $stmt->bindParam(':project_id', $project['id']);
                                    $stmt->execute();
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($project['end_date']): ?>
                            <div class="<?php echo $days_remaining < 0 ? 'text-red-500' : ($days_remaining < 7 ? 'text-orange-500' : 'text-gray-500'); ?>">
                                <?php 
                                if ($days_remaining < 0) {
                                    echo 'Vencido';
                                } elseif ($days_remaining == 0) {
                                    echo 'Hoy';
                                } else {
                                    echo $days_remaining . ' días';
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Pie de tarjeta -->
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs px-2 py-1 rounded-full <?php echo getStatusColor($project['status']); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                            <a href="project.php?id=<?php echo $project['id']; ?>" class="text-primary hover:underline text-sm font-medium">
                                Ver proyecto →
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Modal para crear proyecto -->
    <div id="create-project-modal" class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold">Nuevo Proyecto</h3>
                    <button onclick="closeModal('create-project-modal')" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Nombre del Proyecto *</label>
                            <input type="text" name="name" required 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Descripción</label>
                            <textarea name="description" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Fecha de inicio</label>
                                <input type="date" name="start_date" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Fecha límite</label>
                                <input type="date" name="end_date" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Color del proyecto</label>
                            <div class="flex gap-2">
                                <?php 
                                $colors = ['#137fec', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
                                foreach ($colors as $color): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="color" value="<?php echo $color; ?>" 
                                           class="hidden" <?php echo $color == '#137fec' ? 'checked' : ''; ?>>
                                    <div class="size-8 rounded-lg border-2 border-transparent hover:border-gray-300" 
                                         style="background-color: <?php echo $color; ?>"></div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeModal('create-project-modal')" 
                                class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancelar
                        </button>
                        <button type="submit" name="create_project" 
                                class="px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                            Crear Proyecto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
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
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
    
    // Confirmar eliminación de proyecto
    function confirmDeleteProject(projectId) {
        if (confirm('¿Estás seguro de eliminar este proyecto? Esta acción no se puede deshacer.')) {
            window.location.href = 'project-delete.php?id=' + projectId;
        }
    }
    
    // Dropdown toggle
    document.querySelectorAll('.dropdown button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            menu.classList.toggle('hidden');
        });
    });
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    });
    </script>
</body>
</html>