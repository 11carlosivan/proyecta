<?php
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    return;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside
    class="hidden md:flex flex-col w-64 h-full bg-white dark:bg-[#111418] border-r border-[#e5e7eb] dark:border-[#283039] fixed left-0 top-0">
    <div class="p-6">
        <div class="flex items-center gap-3 mb-8">
            <div class="size-10 bg-primary rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-white">rocket_launch</span>
            </div>
            <div class="flex flex-col">
                <h1 class="text-lg font-bold text-[#111418] dark:text-white">PROYECTA</h1>
                <p class="text-xs text-[#9dabb9]">Gestión Ágil</p>
            </div>
        </div>

        <nav class="flex flex-col gap-1">
            <?php if ($_SESSION['user_role'] === 'client'): ?>
                <!-- Client View -->
                <a href="/PROYECTA/modules/kanban/kanban.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $current_page == 'kanban.php' ? 'bg-primary/10 text-primary' : 'text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white'; ?> transition-colors">
                    <span class="material-symbols-outlined">view_kanban</span>
                    <span class="text-sm font-medium">Tablero Kanban</span>
                </a>
            <?php else: ?>
                <!-- Standard View -->
                <a href="/PROYECTA/dashboard.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $current_page == 'dashboard.php' ? 'bg-primary/10 text-primary' : 'text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white'; ?> transition-colors">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>

                <a href="/PROYECTA/modules/projects/projects.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $current_page == 'projects.php' ? 'bg-primary/10 text-primary' : 'text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white'; ?> transition-colors">
                    <span class="material-symbols-outlined">folder</span>
                    <span class="text-sm font-medium">Proyectos</span>
                </a>

                <a href="/PROYECTA/modules/kanban/kanban.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $current_page == 'kanban.php' ? 'bg-primary/10 text-primary' : 'text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white'; ?> transition-colors">
                    <span class="material-symbols-outlined">view_kanban</span>
                    <span class="text-sm font-medium">Tablero Kanban</span>
                </a>

                <a href="/PROYECTA/modules/tasks/tasks.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $current_page == 'tasks.php' ? 'bg-primary/10 text-primary' : 'text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white'; ?> transition-colors">
                    <span class="material-symbols-outlined">checklist</span>
                    <span class="text-sm font-medium">Mis Tareas</span>
                    <?php
                    // Only show count if not already fetched or if needed (optimization: move DB logic out if possible, but kept here for now)
                    if (!isset($db)) {
                        require_once dirname(__DIR__) . '/config/database.php';
                        $database = new Database();
                        $db = $database->getConnection();
                    }
                    $user_id = $_SESSION['user_id'];
                    $query = "SELECT COUNT(*) as count FROM tasks WHERE assignee_id = :user_id AND status IN ('todo', 'in_progress')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $task_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    if ($task_count > 0): ?>
                        <span
                            class="ml-auto bg-primary text-white text-xs px-2 py-0.5 rounded-full"><?php echo $task_count; ?></span>
                    <?php endif; ?>
                </a>

                <a href="/PROYECTA/modules/team/team.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $current_page == 'team.php' ? 'bg-primary/10 text-primary' : 'text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white'; ?> transition-colors">
                    <span class="material-symbols-outlined">group</span>
                    <span class="text-sm font-medium">Equipo</span>
                </a>

                <a href="/PROYECTA/modules/reports/reports.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $current_page == 'reports.php' ? 'bg-primary/10 text-primary' : 'text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white'; ?> transition-colors">
                    <span class="material-symbols-outlined">bar_chart</span>
                    <span class="text-sm font-medium">Reportes</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="mt-auto p-6 border-t border-[#e5e7eb] dark:border-[#283039]">
        <?php if ($_SESSION['user_role'] !== 'client'): ?>
            <a href="/PROYECTA/modules/settings/settings.php"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo $current_page == 'settings.php' ? 'bg-primary/10 text-primary' : 'text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white'; ?> transition-colors mb-2">
                <span class="material-symbols-outlined">settings</span>
                <span class="text-sm font-medium">Configuración</span>
            </a>
        <?php endif; ?>

        <a href="/PROYECTA/modules/users/profile.php"
            class="flex items-center gap-3 px-3 py-3 mt-2 rounded-lg bg-gray-100 dark:bg-[#283039] hover:bg-gray-200 dark:hover:bg-[#343e49] transition-colors">
            <div
                class="size-8 rounded-full bg-primary flex items-center justify-center text-white font-medium overflow-hidden">
                <?php if (isset($_SESSION['avatar_url']) && $_SESSION['avatar_url']): ?>
                    <img src="<?php echo $_SESSION['avatar_url']; ?>" alt="Avatar" class="w-full h-full object-cover">
                <?php else: ?>
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="flex flex-col overflow-hidden">
                <p class="text-sm font-medium text-[#111418] dark:text-white truncate">
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Usuario'); ?>
                </p>
                <p class="text-xs text-[#9dabb9] truncate">
                    <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Rol'); ?></p>
            </div>
        </a>

        <a href="/PROYECTA/logout.php"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[#9dabb9] hover:bg-gray-100 dark:hover:bg-[#283039] hover:text-[#111418] dark:hover:text-white transition-colors mt-2">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-sm font-medium">Cerrar Sesión</span>
        </a>
    </div>
</aside>