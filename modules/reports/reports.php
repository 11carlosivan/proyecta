<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$page_title = "Reportes";
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
        <?php
        $header_title = "Reportes";
        $header_description = "Visualiza el estado de los proyectos";
        include '../../includes/page-header.php';
        ?>

        <div class="p-6">
            <div
                class="bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-4">bar_chart</span>
                <h3 class="text-lg font-semibold mb-2">En Construcción</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Este módulo estará disponible pronto.</p>
            </div>
        </div>
    </main>
</body>

</html>