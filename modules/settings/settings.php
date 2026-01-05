<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$page_title = "Configuración";
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

    <main class="ml-0 md:ml-64 flex-1">
        <?php include '../../includes/header.php'; ?>

        <div class="p-6 max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">Configuración</h1>

            <!-- Preferencias de Interfaz -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined">palette</span>
                    Apariencia
                </h3>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">Modo Oscuro</p>
                        <p class="text-sm text-gray-500">Alternar entre tema claro y oscuro</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="settings-theme-toggle" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary">
                        </div>
                    </label>
                </div>
            </div>

            <!-- Notificaciones -->
            <div class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined">notifications</span>
                    Notificaciones
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">Asignación de tareas</p>
                            <p class="text-sm text-gray-500">Recibir notificación cuando me asignen una tarea</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary">
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">E-mails diarios</p>
                            <p class="text-sm text-gray-500">Resumen diario de actividad por correo</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary">
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Sincronizar toggle con estado actual
        const themeToggle = document.getElementById('settings-theme-toggle');

        if (document.documentElement.classList.contains('dark')) {
            themeToggle.checked = true;
        }

        themeToggle.addEventListener('change', function () {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('darkMode', isDark);

            // Actualizar el icono del header si existe
            // (El evento de storage puede encargarse de sincronizar pestañas, pero aquí es directo)
        });
    </script>
</body>

</html>