<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas del usuario
$user_id = $_SESSION['user_id'];

// Total de proyectos
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if ($is_admin) {
    $query = "SELECT COUNT(*) as total FROM projects";
    $stmt = $db->prepare($query);
} else {
    $query = "SELECT COUNT(*) as total FROM projects WHERE owner_id = :user_id OR id IN (SELECT project_id FROM project_members WHERE user_id = :user_id2)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':user_id2', $user_id);
}
$stmt->execute();
$project_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tareas asignadas
$query = "SELECT COUNT(*) as total FROM tasks WHERE assignee_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$task_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tareas pendientes
$query = "SELECT COUNT(*) as total FROM tasks WHERE assignee_id = :user_id AND status IN ('todo', 'in_progress')";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>PROYECTA - Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet" />

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                        "card-dark": "#1c2127"
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>

<body class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white font-display h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="ml-0 md:ml-64 flex-1 flex flex-col h-full">
        <!-- Top Bar -->
        <!-- DEBUG INFO - MOVED UP -->


        <?php include 'includes/header.php'; ?>



        <!-- Stats Cards -->
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-card-dark rounded-xl border border-[#e5e7eb] dark:border-[#283039] p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">folder</span>
                    <span class="text-green-500 text-sm font-bold bg-green-500/10 px-2 py-1 rounded">+12%</span>
                </div>
                <h3 class="text-2xl font-bold"><?php echo $project_count; ?></h3>
                <p class="text-[#9dabb9]">Proyectos Activos</p>
            </div>

            <div class="bg-white dark:bg-card-dark rounded-xl border border-[#e5e7eb] dark:border-[#283039] p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="material-symbols-outlined text-3xl text-orange-500">assignment</span>
                    <span class="text-green-500 text-sm font-bold bg-green-500/10 px-2 py-1 rounded">+5%</span>
                </div>
                <h3 class="text-2xl font-bold"><?php echo $task_count; ?></h3>
                <p class="text-[#9dabb9]">Tareas Asignadas</p>
            </div>

            <div class="bg-white dark:bg-card-dark rounded-xl border border-[#e5e7eb] dark:border-[#283039] p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="material-symbols-outlined text-3xl text-red-500">pending</span>
                    <span
                        class="text-red-500 text-sm font-bold bg-red-500/10 px-2 py-1 rounded"><?php echo $pending_count; ?>
                        pendientes</span>
                </div>
                <h3 class="text-2xl font-bold"><?php echo $pending_count; ?></h3>
                <p class="text-[#9dabb9]">Tareas Pendientes</p>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="px-6">
            <div class="bg-white dark:bg-card-dark rounded-xl border border-[#e5e7eb] dark:border-[#283039] p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold">Proyectos Recientes</h2>
                    <a href="projects.php" class="text-primary text-sm font-medium hover:underline">Ver todos</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#e5e7eb] dark:border-[#283039]">
                                <th class="text-left py-3 text-[#9dabb9] font-medium">Proyecto</th>
                                <th class="text-left py-3 text-[#9dabb9] font-medium">Estado</th>
                                <th class="text-left py-3 text-[#9dabb9] font-medium">Progreso</th>
                                <th class="text-left py-3 text-[#9dabb9] font-medium">Fecha Límite</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($is_admin) {
                                $query = "SELECT p.*, 
                                         (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as total_tasks,
                                         (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'done') as completed_tasks
                                         FROM projects p 
                                         ORDER BY p.created_at DESC LIMIT 5";
                                $stmt = $db->prepare($query);
                            } else {
                                $query = "SELECT p.*, 
                                         (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as total_tasks,
                                         (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'done') as completed_tasks
                                         FROM projects p 
                                         WHERE p.owner_id = :user_id OR p.id IN (SELECT project_id FROM project_members WHERE user_id = :user_id2)
                                         ORDER BY p.created_at DESC LIMIT 5";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':user_id', $user_id);
                                $stmt->bindParam(':user_id2', $user_id);
                            }
                            $stmt->execute();

                            while ($project = $stmt->fetch(PDO::FETCH_ASSOC)):
                                $progress = $project['total_tasks'] > 0 ? ($project['completed_tasks'] / $project['total_tasks']) * 100 : 0;
                                ?>
                                <tr
                                    class="border-b border-[#e5e7eb] dark:border-[#283039] hover:bg-gray-50 dark:hover:bg-[#283039]">
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="size-8 rounded-lg"
                                                style="background-color: <?php echo $project['color']; ?>"></div>
                                            <div>
                                                <p class="font-medium"><?php echo htmlspecialchars($project['name']); ?></p>
                                                <p class="text-sm text-[#9dabb9]"><?php echo $project['total_tasks']; ?>
                                                    tareas</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium 
                                          <?php echo $project['status'] == 'active' ? 'bg-green-500/10 text-green-500' :
                                              ($project['status'] == 'paused' ? 'bg-yellow-500/10 text-yellow-500' :
                                                  'bg-gray-500/10 text-gray-500'); ?>">
                                            <?php echo $project['status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        <div class="w-32">
                                            <div class="w-full bg-gray-200 dark:bg-[#283039] rounded-full h-2">
                                                <div class="bg-primary h-2 rounded-full"
                                                    style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                            <p class="text-xs text-[#9dabb9] mt-1"><?php echo round($progress); ?>%
                                                completado</p>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <?php if ($project['end_date']): ?>
                                            <p class="font-medium"><?php echo date('d/m/Y', strtotime($project['end_date'])); ?>
                                            </p>
                                            <?php
                                            $days_left = floor((strtotime($project['end_date']) - time()) / (60 * 60 * 24));
                                            if ($days_left < 0) {
                                                echo '<p class="text-xs text-red-500">Vencido hace ' . abs($days_left) . ' días</p>';
                                            } elseif ($days_left < 7) {
                                                echo '<p class="text-xs text-orange-500">' . $days_left . ' días restantes</p>';
                                            } else {
                                                echo '<p class="text-xs text-[#9dabb9]">' . $days_left . ' días restantes</p>';
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Toggle dark mode
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        }

        // Check saved preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>

</html>