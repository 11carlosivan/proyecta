<?php
// Este archivo se incluye dentro del <main> para mostrar el encabezado de la página
?>
<header class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2"><?php echo $header_title ?? 'PROYECTA'; ?></h1>
            <?php if (isset($header_description)): ?>
            <p class="text-gray-500 dark:text-gray-400"><?php echo $header_description; ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (isset($header_actions)): ?>
        <div class="flex items-center gap-3">
            <?php echo $header_actions; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($header_tabs)): ?>
    <div class="mt-4">
        <nav class="flex space-x-4" aria-label="Tabs">
            <?php echo $header_tabs; ?>
        </nav>
    </div>
    <?php endif; ?>
</header>