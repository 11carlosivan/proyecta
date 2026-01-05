<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$page_title = "Equipo";

// Obtener todos los usuarios
$query = "SELECT * FROM users ORDER BY full_name";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>PROYECTA - <?php echo $page_title; ?></title>
    <?php include '../../includes/head.php'; ?>
</head>

<body
    class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white font-display h-screen overflow-hidden">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 flex flex-col h-screen">
        <?php include '../../includes/header.php'; ?>

        <div
            class="p-6 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Equipo</h1>
                <p class="text-gray-500 dark:text-gray-400">Gestiona los miembros de tu organización</p>
            </div>

            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'member'): ?>
                <button onclick="openModal('create-user-modal')"
                    class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg font-medium transition-colors">
                    <span class="material-symbols-outlined">person_add</span>
                    Añadir Miembro
                </button>
            <?php endif; ?>
        </div>

        <div class="p-6 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($users as $user): ?>
                    <div
                        class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center text-center hover:shadow-lg transition-shadow">
                        <!-- Avatar -->
                        <div class="mb-4 relative">
                            <?php echo getUserAvatar($user, 24); ?>
                            <span
                                class="absolute bottom-0 right-0 size-4 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"
                                title="Activo"></span>
                        </div>

                        <!-- Info -->
                        <h3 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                        <p class="text-primary text-sm font-medium mb-1">
                            <?php echo htmlspecialchars($user['job_title'] ?? 'Miembro del equipo'); ?>
                        </p>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>

                        <!-- Role Badge -->
                        <div class="mb-6">
                            <?php
                            $roleColors = [
                                'admin' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                                'member' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                'viewer' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                            ];
                            $roleColor = $roleColors[$user['role']] ?? $roleColors['viewer'];
                            $roleLabel = [
                                'admin' => 'Administrador',
                                'member' => 'Miembro',
                                'viewer' => 'Observador'
                            ][$user['role']] ?? ucfirst($user['role']);
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $roleColor; ?>">
                                <?php echo $roleLabel; ?>
                            </span>
                        </div>

                        <!-- Stats (Placeholder) -->
                        <div class="w-full grid grid-cols-2 gap-4 border-t border-gray-200 dark:border-gray-700 pt-4 mb-4">
                            <div>
                                <?php
                                $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assignee_id = :id AND status != 'done'");
                                $stmt->bindParam(':id', $user['id']);
                                $stmt->execute();
                                $pending_tasks = $stmt->fetchColumn();
                                ?>
                                <span class="block text-lg font-bold"><?php echo $pending_tasks; ?></span>
                                <span class="text-xs text-gray-500">Pendientes</span>
                            </div>
                            <div>
                                <?php
                                $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assignee_id = :id AND status = 'done'");
                                $stmt->bindParam(':id', $user['id']);
                                $stmt->execute();
                                $completed_tasks = $stmt->fetchColumn();
                                ?>
                                <span class="block text-lg font-bold text-green-500"><?php echo $completed_tasks; ?></span>
                                <span class="text-xs text-gray-500">Completadas</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'member'): ?>
                            <div class="w-full flex gap-2">
                                <button
                                    onclick="openManageProjectsModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')"
                                    class="flex-1 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-lg transition-colors">
                                    Proyectos
                                </button>
                                <button
                                    class="flex-1 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                    Editar
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>)"
                                        class="flex-1 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition-colors">
                                        Eliminar
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Modal Crear Usuario -->
    <div id="create-user-modal"
        class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold">Añadir Nuevo Miembro</h3>
                <button onclick="closeModal('create-user-modal')" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form id="create-user-form" action="../../api/users/create.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Nombre Completo *</label>
                    <input type="text" name="full_name" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Correo Electrónico *</label>
                    <input type="email" name="email" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Cargo / Puesto</label>
                    <input type="text" name="job_title" placeholder="Ej. Desarrollador Frontend"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Contraseña Inicial *</label>
                    <input type="password" name="password" required minlength="6"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Rol</label>
                    <select name="role"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        <option value="member">Miembro</option>
                        <option value="admin">Administrador</option>
                        <option value="collaborator">Colaborador</option>
                        <option value="client">Cliente</option>
                        <option value="viewer">Observador</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal('create-user-modal')"
                        class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                        Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Gestionar Proyectos -->
    <div id="manage-projects-modal"
        class="modal hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-card-dark rounded-xl shadow-xl max-w-md w-full p-6 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Asignar Proyectos</h3>
                <button onclick="closeModal('manage-projects-modal')" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <p id="manage-projects-user-name" class="text-sm text-gray-500 mb-4 font-medium"></p>

            <div class="flex-1 overflow-y-auto min-h-[150px] border border-gray-200 dark:border-gray-700 rounded-lg p-2 space-y-2"
                id="projects-list-container">
                <div class="flex items-center justify-center h-full">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeModal('manage-projects-modal')"
                    class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    Cancelar
                </button>
                <button type="button" onclick="saveProjectAssignments()"
                    class="px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Modal logic
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // Manage Projects Logic
        let currentUserId = null;

        function openManageProjectsModal(userId, userName) {
            currentUserId = userId;
            document.getElementById('manage-projects-user-name').textContent = 'Gestionando acceso para: ' + userName;
            openModal('manage-projects-modal');
            loadUserProjects(userId);
        }

        function loadUserProjects(userId) {
            const container = document.getElementById('projects-list-container');
            container.innerHTML = '<div class="flex items-center justify-center h-full"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>'; // Loading

            fetch('../../api/users/get_projects.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderProjectsList(data.projects, data.assigned_ids);
                    } else {
                        container.innerHTML = '<p class="text-red-500 text-center p-4">Error al cargar proyectos</p>';
                    }
                })
                .catch(err => {
                    container.innerHTML = '<p class="text-red-500 text-center p-4">Error de conexión</p>';
                });
        }

        function renderProjectsList(allProjects, assignedIds) {
            const container = document.getElementById('projects-list-container');
            container.innerHTML = '';

            if (allProjects.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center p-4">No hay proyectos disponibles</p>';
                return;
            }

            allProjects.forEach(project => {
                const isAssigned = assignedIds.includes(project.id);
                const item = document.createElement('label');
                item.className = 'flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg cursor-pointer transition-colors';
                item.innerHTML = `
                    <input type="checkbox" name="project_assignment[]" value="${project.id}" ${isAssigned ? 'checked' : ''}
                           class="w-4 h-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex-1">${project.name}</span>
                `;
                container.appendChild(item);
            });
        }

        function saveProjectAssignments() {
            if (!currentUserId) return;

            const checkboxes = document.querySelectorAll('input[name="project_assignment[]"]:checked');
            const projectIds = Array.from(checkboxes).map(cb => cb.value);
            const button = document.querySelector('button[onclick="saveProjectAssignments()"]');

            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Guardando...';

            fetch('../../api/users/update_projects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: currentUserId,
                    project_ids: projectIds
                })
            })
                .then(response => response.json())
                .then(data => {
                    button.disabled = false;
                    button.textContent = originalText;

                    if (data.success) {
                        showNotification('Asignaciones actualizadas', 'success');
                        closeModal('manage-projects-modal');
                        // Optional: reload to update counts if necessary, but not strictly needed for this view
                        // location.reload();
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(err => {
                    button.disabled = false;
                    button.textContent = originalText;
                    showNotification('Error de conexión', 'error');
                });
        }

        // Form submission
        document.getElementById('create-user-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Usuario creado correctamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(err => showNotification('Error de conexión', 'error'));
        });

        // Delete user
        function deleteUser(userId) {
            if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) return;

            fetch('../../api/users/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Usuario eliminado', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message || 'Error al eliminar', 'error');
                    }
                });
        }
    </script>
</body>

</html>