<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "Mi Perfil";
?>

<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>PROYECTA -
        <?php echo $page_title; ?>
    </title>
    <?php include '../../includes/head.php'; ?>
</head>

<body
    class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white font-display h-screen overflow-hidden">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1">
        <?php include '../../includes/header.php'; ?>

        <div class="p-6 max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">Mi Perfil</h1>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Columna Izquierda: Tarjeta de perfil -->
                <div class="md:col-span-1">
                    <div
                        class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center text-center">
                        <div class="relative group cursor-pointer mb-4"
                            onclick="document.getElementById('avatar-input').click()">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?php echo $user['avatar_url']; ?>"
                                    class="size-32 rounded-full object-cover border-4 border-gray-100 dark:border-gray-700">
                            <?php else: ?>
                                <div
                                    class="size-32 rounded-full bg-primary flex items-center justify-center text-white text-4xl font-bold border-4 border-gray-100 dark:border-gray-700">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div
                                class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-white text-3xl">camera_alt</span>
                            </div>
                            <input type="file" id="avatar-input" class="hidden" accept="image/*"
                                onchange="uploadAvatar(this)">
                        </div>

                        <h2 class="text-xl font-bold">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </h2>
                        <p class="text-gray-500 mb-2">
                            <?php echo htmlspecialchars($user['job_title'] ?? 'Miembro del equipo'); ?>
                        </p>
                        <span
                            class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded-full text-xs font-medium">
                            <?php echo $user['role']; ?>
                        </span>
                    </div>
                </div>

                <!-- Columna Derecha: Formularios -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Información Básica -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold text-lg mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
                            Información Personal</h3>

                        <form id="update-info-form" class="space-y-4">
                            <input type="hidden" name="action" value="update_info">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Nombre Completo</label>
                                    <input type="text" name="full_name"
                                        value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Email</label>
                                    <input type="email" name="email"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Cargo / Puesto</label>
                                <input type="text" name="job_title"
                                    value="<?php echo htmlspecialchars($user['job_title'] ?? ''); ?>"
                                    placeholder="Ej. Desarrollador Frontend"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Biografía</label>
                                <textarea name="bio" rows="3" placeholder="Cuéntanos un poco sobre ti..."
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit"
                                    class="px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Seguridad -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold text-lg mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">Seguridad
                        </h3>

                        <form id="change-password-form" class="space-y-4">
                            <input type="hidden" name="action" value="change_password">

                            <div>
                                <label class="block text-sm font-medium mb-1">Contraseña Actual</label>
                                <input type="password" name="current_password" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Nueva Contraseña</label>
                                    <input type="password" name="new_password" required minlength="6"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Confirmar Contraseña</label>
                                    <input type="password" name="confirm_password" required minlength="6"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                    Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Actualizar información
        document.getElementById('update-info-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../../api/users/update_profile.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
        });

        // Cambiar contraseña
        document.getElementById('change-password-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            // Validar contraseñas
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                showNotification('Las contraseñas no coinciden', 'error');
                return;
            }

            fetch('../../api/users/update_profile.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        this.reset();
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
        });

        // Subir Avatar
        function uploadAvatar(input) {
            if (!input.files || !input.files[0]) return;

            const formData = new FormData();
            formData.append('avatar', input.files[0]);

            fetch('../../api/users/update_profile.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        location.reload();
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
        }
    </script>
</body>

</html>