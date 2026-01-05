<?php
if (!isset($_SESSION['user_id'])) {
    return;
}

// Include time helper for notifications
if (file_exists(__DIR__ . '/time_helper.php')) {
    require_once __DIR__ . '/time_helper.php';
}
?>
<header
    class="h-16 border-b border-[#e5e7eb] dark:border-[#283039] bg-white dark:bg-[#111418] px-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <!-- Botón para mobile -->
        <button id="mobile-menu-btn" class="md:hidden text-[#111418] dark:text-white">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <!-- Breadcrumbs -->
        <div class="hidden md:flex items-center gap-2 text-sm">
            <a href="dashboard.php" class="text-[#9dabb9] hover:text-primary transition-colors">Dashboard</a>
            <?php if (isset($page_title)): ?>
                <span class="text-[#9dabb9]">/</span>
                <span class="text-[#111418] dark:text-white font-medium"><?php echo $page_title; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <!-- Buscador -->
        <div class="relative hidden md:block">
            <span
                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#9dabb9]">search</span>
            <input type="text"
                class="pl-10 pr-4 py-2 rounded-lg bg-gray-100 dark:bg-[#283039] border border-transparent focus:border-primary focus:ring-1 focus:ring-primary w-64 text-sm"
                placeholder="Buscar proyectos, tareas...">
        </div>

        <!-- Notificaciones -->
        <div id="notif-container" class="relative group">
            <button id="notification-btn"
                class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-[#283039] text-[#111418] dark:text-white relative">
                <span class="material-symbols-outlined">notifications</span>
                <?php
                $notification_count = 0;
                try {
                    // Ensure DB connection is available safely
                    if (!isset($db)) {
                        $db_path = dirname(__DIR__) . '/config/database.php';
                        if (file_exists($db_path) && !class_exists('Database')) {
                            require_once $db_path;
                        }
                        if (class_exists('Database')) {
                            $database = new Database();
                            $db = $database->getConnection();
                        }
                    }

                    if (isset($db)) {
                        $user_id = $_SESSION['user_id'];
                        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    }
                } catch (Exception $e) {
                    error_log("Header notification error: " . $e->getMessage());
                }
                ?>
                <span id="notif-badge"
                    class="absolute -top-1 -right-1 size-2 bg-red-500 rounded-full <?php echo $notification_count > 0 ? '' : 'hidden'; ?>"></span>
            </button>

            <!-- Dropdown Notificaciones -->
            <div id="notif-dropdown"
                class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-[#1c2127] border border-[#e5e7eb] dark:border-[#283039] rounded-lg shadow-lg hidden z-50 overflow-hidden">
                <div class="p-3 border-b border-[#e5e7eb] dark:border-[#283039]">
                    <h3 class="font-bold text-sm">Notificaciones</h3>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <?php
                    $notifications = [];
                    if (isset($db)) {
                        try {
                            $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':user_id', $user_id);
                            $stmt->execute();
                            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            // Silent fail
                        }
                    }

                    if (empty($notifications)): ?>
                        <div class="p-4 text-center text-sm text-gray-500">
                            No tienes notificaciones
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <a href="<?php echo $notif['link'] ? $notif['link'] : '#'; ?>"
                                onclick="markAsRead(<?php echo $notif['id']; ?>)"
                                class="block p-3 hover:bg-gray-50 dark:hover:bg-[#283039] border-b border-[#e5e7eb] dark:border-[#283039] last:border-0 <?php echo $notif['is_read'] ? 'opacity-60' : ''; ?>">
                                <div class="flex gap-3">
                                    <div class="mt-1">
                                        <?php if ($notif['type'] == 'task'): ?>
                                            <span class="material-symbols-outlined text-blue-500 text-sm">assignment</span>
                                        <?php else: ?>
                                            <span class="material-symbols-outlined text-gray-500 text-sm">notifications</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-[#111418] dark:text-white line-clamp-1">
                                            <?php echo htmlspecialchars($notif['message']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php
                                            if (function_exists('time_elapsed_string')) {
                                                echo time_elapsed_string($notif['created_at']);
                                            } else {
                                                echo date('d/m/Y H:i', strtotime($notif['created_at']));
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <?php if (!$notif['is_read']): ?>
                                        <div class="ml-auto mt-2">
                                            <span class="size-2 bg-blue-500 rounded-full block"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            function markAsRead(id) {
                fetch('/PROYECTA/api/notifications/mark_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
            }
        </script>

        <!-- Modo oscuro/claro -->
        <button id="theme-toggle"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-[#283039] text-[#111418] dark:text-white">
            <span class="material-symbols-outlined dark:hidden">dark_mode</span>
            <span class="material-symbols-outlined hidden dark:inline">light_mode</span>
        </button>

        <!-- Perfil -->
        <div id="profile-container" class="relative group">
            <button class="flex items-center gap-3">
                <?php
                try {
                    $avatar = $_SESSION['avatar_url'] ?? '';
                    $name = $_SESSION['full_name'] ?? '';
                    if (empty($name))
                        $name = 'Usuario';
                    $initial = substr($name, 0, 1);
                    ?>
                    <div
                        class="size-8 rounded-full bg-primary flex items-center justify-center text-white font-medium overflow-hidden">
                        <?php if (!empty($avatar)): ?>
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="w-full h-full object-cover"
                                onerror="this.style.display='none'; this.parentElement.innerHTML='<?php echo strtoupper($initial); ?>';">
                        <?php else: ?>
                            <?php echo strtoupper($initial); ?>
                        <?php endif; ?>
                    </div>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($name); ?></span>
                    <span class="material-symbols-outlined text-[#9dabb9]">expand_more</span>
                    <?php
                } catch (Exception $e) {
                    echo '<div class="size-8 rounded-full bg-primary flex items-center justify-center text-white font-medium">U</div>';
                    echo '<span class="text-sm font-medium">Usuario</span>';
                    echo '<span class="material-symbols-outlined text-[#9dabb9]">expand_more</span>';
                    error_log("Header profile error: " . $e->getMessage());
                }
                ?>
            </button>

            <!-- Dropdown de perfil -->
            <div id="profile-dropdown"
                class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-[#1c2127] border border-[#e5e7eb] dark:border-[#283039] rounded-lg shadow-lg hidden z-50">
                <a href="/PROYECTA/modules/users/profile.php"
                    class="flex items-center gap-2 px-4 py-3 hover:bg-gray-100 dark:hover:bg-[#283039]">
                    <span class="material-symbols-outlined text-[20px]">person</span>
                    <span>Mi Perfil</span>
                </a>
                <a href="/PROYECTA/modules/settings/settings.php"
                    class="flex items-center gap-2 px-4 py-3 hover:bg-gray-100 dark:hover:bg-[#283039]">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                    <span>Configuración</span>
                </a>
                <div class="border-t border-[#e5e7eb] dark:border-[#283039]"></div>
                <a href="logout.php"
                    class="flex items-center gap-2 px-4 py-3 hover:bg-gray-100 dark:hover:bg-[#283039] text-red-500">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>
</header>

<script>
    // Toggle mobile menu
    document.getElementById('mobile-menu-btn')?.addEventListener('click', function () {
        document.querySelector('aside').classList.toggle('hidden');
    });

    // Toggle theme
    document.getElementById('theme-toggle')?.addEventListener('click', function () {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
    });

    // Check saved theme
    if (localStorage.getItem('darkMode') === 'true') {
        document.documentElement.classList.add('dark');
    }

    // Dropdown functionality - Click to open/close
    document.addEventListener('DOMContentLoaded', function () {
        // Notifications dropdown
        const notifBtn = document.getElementById('notification-btn');
        const notifDropdown = document.getElementById('notif-dropdown');

        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                notifDropdown.classList.toggle('hidden');
                // Close profile dropdown if open
                const profileDropdown = document.getElementById('profile-dropdown');
                if (profileDropdown) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }

        // Profile dropdown
        const profileContainer = document.getElementById('profile-container');
        const profileBtn = profileContainer?.querySelector('button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
                // Close notifications dropdown if open
                if (notifDropdown) {
                    notifDropdown.classList.add('hidden');
                }
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            if (notifDropdown && !notifBtn?.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }
            if (profileDropdown && !profileBtn?.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.add('hidden');
            }
        });
    });
</script>