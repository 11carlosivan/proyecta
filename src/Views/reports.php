<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes -
        <?php echo htmlspecialchars($project['name']); ?>
    </title>
    <link rel="stylesheet" href="/proyecta/public/assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <nav class="navbar">
        <div class="logo">Proyecta | Reportes</div>
        <div>
            <a href="/proyecta/public/board?project_id=<?php echo $projectId; ?>" class="btn-secondary">Volver al
                Tablero</a>
        </div>
    </nav>
    <div class="content">
        <h1>Métricas del Proyecto</h1>

        <div class="dashboard-grid">
            <section class="card">
                <h2>Tareas por Estado</h2>
                <canvas id="statusChart"></canvas>
            </section>

            <section class="card">
                <h2>Carga de Trabajo (Tareas por Usuario)</h2>
                <canvas id="workloadChart"></canvas>
            </section>
        </div>
    </div>

    <script>
        // Data from PHP
        const statusData = <?php echo json_encode($tasksByStatus); ?>;
        const workloadData = <?php echo json_encode($tasksByUser); ?>;

        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.status),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: ['#dbeafe', '#fee2e2', '#d1d5db', '#10b981']
                }]
            }
        });

        const ctxWorkload = document.getElementById('workloadChart').getContext('2d');
        new Chart(ctxWorkload, {
            type: 'bar',
            data: {
                labels: workloadData.map(d => d.name),
                datasets: [{
                    label: 'Tareas Asignadas',
                    data: workloadData.map(d => d.count),
                    backgroundColor: '#2563eb'
                }]
            }
        });
    </script>
</body>

</html>