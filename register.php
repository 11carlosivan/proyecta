<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Todos los campos son requeridos';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si el email ya existe
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = 'El email ya está registrado';
        } else {
            // Crear usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $username = strtolower(str_replace(' ', '.', $full_name));
            
            $query = "INSERT INTO users (username, email, password, full_name, role) 
                     VALUES (:username, :email, :password, :full_name, 'viewer')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            
            if ($stmt->execute()) {
                $success = '¡Registro exitoso! Puedes iniciar sesión ahora.';
                header('refresh:3;url=index.php');
            } else {
                $error = 'Error al registrar el usuario';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>PROYECTA - Registro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet"/>
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="flex min-h-screen bg-background-light dark:bg-background-dark font-display">
    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-[480px]">
            <!-- Logo y Título -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 mb-4">
                    <div class="size-12 bg-primary rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-2xl">rocket_launch</span>
                    </div>
                    <h1 class="text-3xl font-black text-[#111418] dark:text-white">PROYECTA</h1>
                </div>
                <p class="text-[#637588] dark:text-[#9dabb9]">Crea tu cuenta para comenzar</p>
            </div>
            
            <!-- Card de Registro -->
            <div class="bg-white dark:bg-[#1c2127] rounded-xl border border-[#e5e7eb] dark:border-[#283039] shadow-lg p-6">
                <h2 class="text-xl font-bold text-[#111418] dark:text-white mb-6">Crear Cuenta</h2>
                
                <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-700 dark:text-red-300">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-700 dark:text-green-300">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-[#111418] dark:text-white mb-2">Nombre Completo</label>
                        <input type="text" name="full_name" required 
                               class="w-full px-3 py-2 border border-[#d1d5db] dark:border-[#3b4754] rounded-lg bg-[#f9fafb] dark:bg-[#111418] text-[#111418] dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Juan Pérez" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[#111418] dark:text-white mb-2">Correo electrónico</label>
                        <input type="email" name="email" required 
                               class="w-full px-3 py-2 border border-[#d1d5db] dark:border-[#3b4754] rounded-lg bg-[#f9fafb] dark:bg-[#111418] text-[#111418] dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="nombre@empresa.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[#111418] dark:text-white mb-2">Contraseña</label>
                        <input type="password" name="password" required 
                               class="w-full px-3 py-2 border border-[#d1d5db] dark:border-[#3b4754] rounded-lg bg-[#f9fafb] dark:bg-[#111418] text-[#111418] dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Mínimo 8 caracteres">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-[#111418] dark:text-white mb-2">Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" required 
                               class="w-full px-3 py-2 border border-[#d1d5db] dark:border-[#3b4754] rounded-lg bg-[#f9fafb] dark:bg-[#111418] text-[#111418] dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Repite tu contraseña">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="terms" required class="rounded border-gray-300 text-primary focus:ring-primary">
                        <label for="terms" class="ml-2 text-sm text-[#637588] dark:text-[#9dabb9]">
                            Acepto los <a href="#" class="text-primary hover:underline">Términos y Condiciones</a>
                        </label>
                    </div>
                    
                    <button type="submit" 
                            class="w-full py-2.5 px-4 bg-primary hover:bg-blue-600 text-white font-bold rounded-lg transition-colors">
                        Crear Cuenta
                    </button>
                </form>
                
                <div class="mt-6 pt-6 border-t border-[#e5e7eb] dark:border-[#3b4754]">
                    <p class="text-center text-[#637588] dark:text-[#9dabb9] text-sm">
                        ¿Ya tienes una cuenta? 
                        <a href="index.php" class="text-primary font-bold hover:underline">Inicia Sesión</a>
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-6 text-center">
                <p class="text-xs text-[#637588] dark:text-[#9dabb9]">
                    © 2024 PROYECTA. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>