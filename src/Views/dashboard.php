<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Proyecta</title>
    <link rel="stylesheet" href="/proyecta/public/assets/style.css">
</head>

<body>
    <nav class="navbar">
        <div class="logo">Proyecta</div>
        <div class="user-menu">
            <span>Hola,
                <?php echo $_SESSION['user_name']; ?> (
                <?php echo $_SESSION['user_role']; ?>)
            </span>
            <a href="/proyecta/public/logout" class="btn-logout">Salir</a>
        </div>
    </nav>
    <div class="main-container">
        <aside class="sidebar">
            <ul>
                <li><a href="/proyecta/public/dashboard">Inicio</a></li>
                <li><a href="/proyecta/public/projects">Proyectos</a></li>
                <li><a href="/proyecta/public/teams">Equipos</a></li>
            </ul>
        </aside>
        <main class="content">
            <h1>Bienvenido al Panel de Control</h1>
            
            <div class="dashboard-grid">
                <!-- Organizations Section -->
                <section class="card">
                    <h2>Mis Organizaciones</h2>
                    <ul>
                        <?php if(empty($orgs)): ?>
                            <li>No perteneces a ninguna organización aún.</li>
                        <?php else: ?>
                            <?php foreach($orgs as $org): ?>
                                <li><strong><?php echo htmlspecialchars($org['name']); ?></strong> (<?php echo $org['user_role']; ?>)</li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    
                    <h3>Crear Nueva Organización</h3>
                    <form action="/proyecta/public/create-org" method="POST">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Nombre de la Organización" required>
                        </div>
                        <button type="submit" class="btn-primary">Crear</button>
                    </form>
                </section>
                
                <!-- Projects Section -->
                <section class="card">
                    <h2>Crear Proyecto</h2>
                    <form action="/proyecta/public/create-project" method="POST">
                        <div class="form-group">
                            <label>Organización</label>
                            <select name="org_id" required>
                                <?php foreach($orgs as $org): ?>
                                    <option value="<?php echo $org['id']; ?>"><?php echo htmlspecialchars($org['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nombre del Proyecto</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description"></textarea>
                        </div>
                        <button type="submit" class="btn-primary">Crear Proyecto</button>
                    </form>
                </section>
            </div>
        </main>

    </div>
</body>

</html>