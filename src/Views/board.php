<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - Tablero</title>
    <link rel="stylesheet" href="/proyecta/public/assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Proyecta | <?php echo htmlspecialchars($project['name']); ?></div>
        <div>
            <a href="/proyecta/public/sprints?project_id=<?php echo $project['id']; ?>" class="btn-secondary">Sprints</a>
            <a href="/proyecta/public/reports?project_id=<?php echo $project['id']; ?>" class="btn-secondary">Reportes</a>
            <a href="/proyecta/public/dashboard" class="btn-secondary">Volver</a>
        </div>
    </nav>
    <div class="board-container">
        <?php foreach($columns as $column): ?>
            <div class="kanban-column" data-column-id="<?php echo $column['id']; ?>">
                <div class="column-header">
                    <h3><?php echo htmlspecialchars($column['name']); ?></h3>
                    <span class="count"><?php echo count($tasksByColumn[$column['id']] ?? []); ?></span>
                </div>
                <div class="column-body">
                    <?php if(isset($tasksByColumn[$column['id']])): ?>
                        <?php foreach($tasksByColumn[$column['id']] as $task): ?>
                            <div class="task-card" draggable="true" data-task-id="<?php echo $task['id']; ?>">
                                <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                <div class="task-meta">
                                    <span class="type <?php echo strtolower($task['type']); ?>"><?php echo $task['type']; ?></span>
                                    <span class="assignee"><?php echo $task['assignee_name'] ? substr($task['assignee_name'], 0, 1) : '?'; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="add-task-form">
                    <form action="/proyecta/public/create-task" method="POST">
                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                        <input type="hidden" name="column_id" value="<?php echo $column['id']; ?>">
                        <input type="text" name="title" placeholder="+ Nueva Tarea" required>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script src="/proyecta/public/assets/kanban.js"></script>
</body>
</html>