<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Proyecta</title>
    <link rel="stylesheet" href="/proyecta/public/assets/style.css">
</head>

<body class="auth-body">
    <div class="auth-container">
        <h1>Proyecta</h1>
        <h2>Crear Cuenta</h2>
        <?php if (isset($error)): ?>
            <p class="error">
                <?php echo $error; ?>
            </p>
        <?php endif; ?>

        <form action="/proyecta/public/register" method="POST">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Rol Inicial</label>
                <select name="role">
                    <option value="Developer">Developer</option>
                    <option value="Project Manager">Project Manager</option>
                    <option value="Tester">Tester</option>
                    <option value="Client">Client</option>
                </select>
                <small>Nota: Para Admin, debe configurarse en DB.</small>
            </div>
            <button type="submit" class="btn-primary">Registrarse</button>
        </form>
        <p>¿Ya tienes cuenta? <a href="/proyecta/public/login">Inicia Sesión</a></p>
    </div>
</body>

</html>