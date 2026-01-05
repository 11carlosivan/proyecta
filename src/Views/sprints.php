<?php
// Retrieve Sprints
$projectId = $_GET['project_id'] ?? 0;
// Note: Logic normally in Controller, putting here for quick scaffolding or assuming Controller passed $sprints
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sprints - Proyecta</title>
    <link rel="stylesheet" href="/proyecta/public/assets/style.css">
</head>

<body>
    <nav class="navbar">
        <div class="logo">Proyecta | Sprints</div>
        <div>
            <a href="/proyecta/public/board?project_id=<?php echo $projectId; ?>" class="btn-secondary">Volver al
                Tablero</a>
        </div>
    </nav>
    <div class="content">
        <h1>Gestión de Sprints</h1>

        <div class="dashboard-grid">
            <section class="card">
                <h2>Sprints Activos & Futuros</h2>
                <ul>
                    <?php if (empty($sprints)): ?>
                        <li>No hay sprints creados.</li>
                    <?php else: ?>
                        <?php foreach ($sprints as $sprint): ?>
                            <li>
                                <strong>
                                    <?php echo htmlspecialchars($sprint['name']); ?>
                                </strong>
                                <span class="badge">
                                    <?php echo $sprint['status']; ?>
                                </span>
                                <small>
                                    <?php echo $sprint['start_date']; ?> -
                                    <?php echo $sprint['end_date']; ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </section>

            <section class="card">
                <h2>Crear Nuevo Sprint</h2>
                <form action="/proyecta/public/create-sprint" method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                    <div class="form-group">
                        <label>Nombre del Sprint</label>
                        <input type="text" name="name" placeholder="Sprint 1" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Inicio</label>
                        <input type="date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Fin</label>
                        <input type="date" name="end_date" required>
                    </div>
                    <button type="submit" class="btn-primary">Crear Sprint</button>
                </form>
            </section>
        </div>
    </div>
</body>

</html>