<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Proyecta</title>
    <link rel="stylesheet" href="/proyecta/public/assets/style.css">
</head>

<body class="auth-body">
    <div class="auth-container">
        <h1>Proyecta</h1>
        <h2>Iniciar Sesión</h2>
        <?php if (isset($error)): ?>
            <p class="error">
                <?php echo $error; ?>
            </p>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <p class="success">Registro exitoso. Por favor inicia sesión.</p>
        <?php endif; ?>

        <form action="/proyecta/public/login" method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Entrar</button>
        </form>
        <p>¿No tienes cuenta? <a href="/proyecta/public/register">Regístrate</a></p>
    </div>
</body>

</html>