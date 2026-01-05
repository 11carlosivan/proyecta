<?php
/**
 * INSTALADOR DE PROYECTA
 * Este archivo se ejecuta una sola vez para configurar la base de datos
 * Después de la instalación, este archivo se auto-deshabilitará
 */

session_start();

// Verificar si ya está instalado
if (file_exists(__DIR__ . '/config/database.php') && !isset($_GET['force'])) {
    die('
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PROYECTA - Ya Instalado</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md">
            <div class="text-center">
                <div class="text-green-500 text-6xl mb-4">✓</div>
                <h1 class="text-2xl font-bold mb-4">PROYECTA ya está instalado</h1>
                <p class="text-gray-600 mb-6">La aplicación ya ha sido configurada correctamente.</p>
                <a href="index.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 inline-block">
                    Ir a la Aplicación
                </a>
                <p class="text-sm text-gray-500 mt-4">
                    Si necesitas reinstalar, agrega <code>?force=1</code> a la URL
                </p>
            </div>
        </div>
    </body>
    </html>
    ');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// PASO 2: Procesar configuración de BD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';

    if (empty($db_name) || empty($db_user)) {
        $error = 'Por favor completa todos los campos requeridos';
    } else {
        try {
            // Intentar conectar
            $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Crear base de datos si no existe
            $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->exec("USE `$db_name`");

            // Guardar configuración en sesión
            $_SESSION['install'] = [
                'db_host' => $db_host,
                'db_name' => $db_name,
                'db_user' => $db_user,
                'db_pass' => $db_pass,
                'conn' => $conn
            ];

            header('Location: install.php?step=3');
            exit;

        } catch (PDOException $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    }
}

// PASO 3: Crear tablas
if ($step == 3 && isset($_SESSION['install'])) {
    $config = $_SESSION['install'];
    $conn = $config['conn'];

    try {
        // SQL para crear todas las tablas
        $sql = "
        -- Tabla de usuarios
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            user_role ENUM('admin', 'member', 'client') DEFAULT 'member',
            avatar_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (user_role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de proyectos
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            color VARCHAR(7) DEFAULT '#137fec',
            status ENUM('active', 'on_hold', 'completed', 'cancelled') DEFAULT 'active',
            start_date DATE,
            end_date DATE,
            owner_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status (status),
            INDEX idx_owner (owner_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de miembros de proyecto
        CREATE TABLE IF NOT EXISTS project_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('admin', 'member', 'viewer') DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_member (project_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de tareas
        CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            project_id INT NOT NULL,
            assignee_id INT,
            reporter_id INT NOT NULL,
            status ENUM('todo', 'in_progress', 'review', 'done') DEFAULT 'todo',
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            due_date DATE,
            estimated_hours DECIMAL(5,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status (status),
            INDEX idx_priority (priority),
            INDEX idx_assignee (assignee_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de etiquetas
        CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            color VARCHAR(50) DEFAULT 'bg-blue-500',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de etiquetas de tareas
        CREATE TABLE IF NOT EXISTS task_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            tag_id INT NOT NULL,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
            UNIQUE KEY unique_task_tag (task_id, tag_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de adjuntos
        CREATE TABLE IF NOT EXISTS task_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(100),
            file_size INT,
            uploaded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de notificaciones
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'info',
            link VARCHAR(500),
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_read (user_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de actividades del proyecto
        CREATE TABLE IF NOT EXISTS project_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NULL,
            description TEXT NOT NULL,
            metadata JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_project_created (project_id, created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de comentarios
        CREATE TABLE IF NOT EXISTS task_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_task_created (task_id, created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de checklist
        CREATE TABLE IF NOT EXISTS task_checklist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            is_completed BOOLEAN DEFAULT FALSE,
            position INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            INDEX idx_task_position (task_id, position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de registro de tiempo
        CREATE TABLE IF NOT EXISTS task_time_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            hours DECIMAL(5,2) NOT NULL,
            description TEXT,
            logged_at DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_task_date (task_id, logged_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de observadores
        CREATE TABLE IF NOT EXISTS task_watchers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_watcher (task_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de relaciones entre tareas
        CREATE TABLE IF NOT EXISTS task_relations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            related_task_id INT NOT NULL,
            relation_type ENUM('blocks', 'blocked_by', 'relates_to', 'duplicates') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (related_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            UNIQUE KEY unique_relation (task_id, related_task_id, relation_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- Tabla de historial
        CREATE TABLE IF NOT EXISTS task_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            field_name VARCHAR(50) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_task_created (task_id, created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        // Ejecutar cada statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $conn->exec($statement);
            }
        }

        header('Location: install.php?step=4');
        exit;

    } catch (PDOException $e) {
        $error = 'Error al crear tablas: ' . $e->getMessage();
    }
}

// PASO 4: Crear usuario administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 4) {
    $config = $_SESSION['install'];
    $conn = $config['conn'];

    $admin_name = $_POST['admin_name'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';

    if (empty($admin_name) || empty($admin_email) || empty($admin_password)) {
        $error = 'Por favor completa todos los campos';
    } elseif ($admin_password !== $admin_password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($admin_password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        try {
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $username = strtolower(str_replace(' ', '', $admin_name));

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, user_role) VALUES (?, ?, ?, ?, 'admin')");
            $stmt->execute([$username, $admin_email, $hashed_password, $admin_name]);

            header('Location: install.php?step=5');
            exit;

        } catch (PDOException $e) {
            $error = 'Error al crear usuario: ' . $e->getMessage();
        }
    }
}

// PASO 5: Finalizar instalación
if ($step == 5 && isset($_SESSION['install'])) {
    $config = $_SESSION['install'];

    // Crear archivo de configuración
    $config_content = "<?php
/**
 * Configuración de Base de Datos - PROYECTA
 * Generado automáticamente por el instalador
 */

class Database {
    private \$host = \"{$config['db_host']}\";
    private \$db_name = \"{$config['db_name']}\";
    private \$username = \"{$config['db_user']}\";
    private \$password = \"{$config['db_pass']}\";
    private \$conn;

    public function getConnection() {
        \$this->conn = null;

        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=utf8mb4\",
                \$this->username,
                \$this->password
            );
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException \$exception) {
            echo \"Error de conexión: \" . \$exception->getMessage();
        }

        return \$this->conn;
    }
}
?>";

    if (!is_dir(__DIR__ . '/config')) {
        mkdir(__DIR__ . '/config', 0755, true);
    }

    file_put_contents(__DIR__ . '/config/database.php', $config_content);

    // Crear directorios necesarios
    $dirs = ['uploads', 'uploads/avatars', 'uploads/attachments', 'uploads/temp'];
    foreach ($dirs as $dir) {
        if (!is_dir(__DIR__ . '/' . $dir)) {
            mkdir(__DIR__ . '/' . $dir, 0755, true);
        }
    }

    // Limpiar sesión
    unset($_SESSION['install']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador PROYECTA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">PROYECTA</h1>
            <p class="text-gray-600">Instalador del Sistema de Gestión de Proyectos</p>
        </div>

        <!-- Progress Bar -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-2">
                <?php
                $steps_info = [
                    1 => 'Bienvenida',
                    2 => 'Base de Datos',
                    3 => 'Crear Tablas',
                    4 => 'Usuario Admin',
                    5 => 'Finalizar'
                ];
                foreach ($steps_info as $num => $label):
                    $is_active = $num == $step;
                    $is_completed = $num < $step;
                    ?>
                    <div class="flex-1 text-center">
                        <div class="relative">
                            <div
                                class="size-10 mx-auto rounded-full flex items-center justify-center font-bold
                                <?php echo $is_completed ? 'bg-green-500 text-white' : ($is_active ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500'); ?>">
                                <?php echo $is_completed ? '✓' : $num; ?>
                            </div>
                            <p
                                class="text-xs mt-2 <?php echo $is_active ? 'text-blue-600 font-semibold' : 'text-gray-500'; ?>">
                                <?php echo $label; ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($num < 5): ?>
                        <div class="flex-1 h-1 mx-2 <?php echo $is_completed ? 'bg-green-500' : 'bg-gray-200'; ?>"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-red-500 mr-2">error</span>
                        <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- PASO 1: Bienvenida -->
                <div class="text-center">
                    <span class="material-symbols-outlined text-6xl text-blue-500 mb-4">rocket_launch</span>
                    <h2 class="text-2xl font-bold mb-4">¡Bienvenido a PROYECTA!</h2>
                    <p class="text-gray-600 mb-6">Este asistente te guiará en la configuración inicial del sistema.</p>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-left">
                        <h3 class="font-semibold text-blue-900 mb-2">Requisitos del Sistema:</h3>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>✓ PHP 8.0 o superior</li>
                            <li>✓ MySQL 8.0 o MariaDB 10.5+</li>
                            <li>✓ Extensión PDO de PHP</li>
                            <li>✓ Permisos de escritura en el directorio</li>
                        </ul>
                    </div>

                    <a href="?step=2"
                        class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 inline-block font-medium">
                        Comenzar Instalación
                    </a>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- PASO 2: Configuración de BD -->
                <h2 class="text-2xl font-bold mb-6">Configuración de Base de Datos</h2>
                <form method="POST" action="?step=2" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Host de Base de Datos</label>
                        <input type="text" name="db_host" value="localhost" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Generalmente es "localhost"</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Nombre de la Base de Datos *</label>
                        <input type="text" name="db_name" placeholder="proyecta_db" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Se creará si no existe</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Usuario de Base de Datos *</label>
                        <input type="text" name="db_user" placeholder="root" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Contraseña de Base de Datos</label>
                        <input type="password" name="db_pass" placeholder="Dejar en blanco si no tiene contraseña"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="flex justify-between pt-4">
                        <a href="?step=1" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Atrás
                        </a>
                        <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                            Continuar
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <!-- PASO 3: Creando tablas -->
                <div class="text-center">
                    <div
                        class="animate-spin size-16 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-4">
                    </div>
                    <h2 class="text-2xl font-bold mb-4">Creando Tablas...</h2>
                    <p class="text-gray-600">Por favor espera mientras se configuran las tablas de la base de datos.</p>
                </div>
                <script>
                    // Auto-continuar
                    setTimeout(() => window.location.href = '?step=4', 1000);
                </script>

            <?php elseif ($step == 4): ?>
                <!-- PASO 4: Usuario Administrador -->
                <h2 class="text-2xl font-bold mb-6">Crear Usuario Administrador</h2>
                <form method="POST" action="?step=4" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Nombre Completo *</label>
                        <input type="text" name="admin_name" placeholder="Juan Pérez" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Email *</label>
                        <input type="email" name="admin_email" placeholder="admin@proyecta.com" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Contraseña *</label>
                        <input type="password" name="admin_password" placeholder="Mínimo 6 caracteres" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Confirmar Contraseña *</label>
                        <input type="password" name="admin_password_confirm" placeholder="Repite la contraseña" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                            Crear Administrador
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 5): ?>
                <!-- PASO 5: Completado -->
                <div class="text-center">
                    <div class="text-green-500 text-6xl mb-4">✓</div>
                    <h2 class="text-2xl font-bold mb-4">¡Instalación Completada!</h2>
                    <p class="text-gray-600 mb-6">PROYECTA se ha instalado correctamente y está listo para usar.</p>

                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-left">
                        <h3 class="font-semibold text-green-900 mb-2">Próximos Pasos:</h3>
                        <ol class="text-sm text-green-800 space-y-2 list-decimal list-inside">
                            <li>Por seguridad, elimina o renombra el archivo <code
                                    class="bg-green-100 px-1 rounded">install.php</code></li>
                            <li>Inicia sesión con las credenciales de administrador que creaste</li>
                            <li>Configura tu primer proyecto</li>
                            <li>Invita a tu equipo</li>
                        </ol>
                    </div>

                    <a href="login.php"
                        class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 inline-block font-medium">
                        Ir al Login
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-6 text-sm text-gray-500">
            <p>PROYECTA v1.0.0 - Sistema de Gestión de Proyectos Ágil</p>
        </div>
    </div>
</body>

</html>