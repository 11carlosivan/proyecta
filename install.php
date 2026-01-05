<?php
// Habilitar todos los errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PROYECTA - Instalación</h1>";

// Verificar si PHP está funcionando
echo "<p>PHP version: " . phpversion() . "</p>";

// Verificar extensiones necesarias
echo "<p>PDO MySQL disponible: " . (extension_loaded('pdo_mysql') ? 'SÍ' : 'NO') . "</p>";
echo "<p>MySQLi disponible: " . (extension_loaded('mysqli') ? 'SÍ' : 'NO') . "</p>";

// Verificar permisos de escritura
echo "<p>Permisos de escritura en config/: " . (is_writable('config/') ? 'SÍ' : 'NO') . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Procesando instalación...</h3>";
    
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'proyecta_db';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    try {
        // Intentar conexión
        $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p style='color: green;'>✓ Conexión exitosa al servidor MySQL</p>";
        
        // Crear base de datos
        $conn->exec("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p style='color: green;'>✓ Base de datos creada o ya existente</p>";
        
        $conn->exec("USE $db_name");
        
        // Tabla de usuarios
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            role ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
            avatar_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Tabla 'users' creada</p>";
        
        // Crear archivo de configuración
        $config_content = "<?php
class Database {
    private \$host = \"$db_host\";
    private \$db_name = \"$db_name\";
    private \$username = \"$db_user\";
    private \$password = \"$db_pass\";
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name,
                \$this->username, 
                \$this->password
            );
            \$this->conn->exec(\"set names utf8\");
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException \$exception) {
            echo \"Error de conexión: \" . \$exception->getMessage();
        }
        return \$this->conn;
    }
}
?>";
        
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        if (file_put_contents('config/database.php', $config_content)) {
            echo "<p style='color: green;'>✓ Archivo config/database.php creado</p>";
            
            // Insertar usuario admin
            $admin_email = $_POST['admin_email'] ?? 'admin@proyecta.com';
            $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES ('admin', ?, ?, 'Administrador', 'admin')");
            $stmt->execute([$admin_email, $admin_password]);
            
            echo "<p style='color: green;'>✓ Usuario administrador creado</p>";
            echo "<p><strong>¡Instalación completada!</strong></p>";
            echo "<p>Usuario: $admin_email</p>";
            echo "<p><a href='index.php'>Ir al login</a></p>";
            
            // Renombrar este archivo para evitar re-instalación
            rename(__FILE__, 'install_backup.php');
            
        } else {
            echo "<p style='color: red;'>✗ Error al crear archivo de configuración</p>";
        }
        
    } catch(PDOException $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instalación PROYECTA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #137fec; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0c6bc5; }
        .info { background: #f0f7ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Instalación de PROYECTA</h1>
        
        <div class="info">
            <p><strong>Configuración por defecto para XAMPP:</strong></p>
            <ul>
                <li>Host: localhost</li>
                <li>Usuario: root</li>
                <li>Contraseña: (vacía por defecto en XAMPP)</li>
            </ul>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Host de MySQL:</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label>Nombre de la base de datos:</label>
                <input type="text" name="db_name" value="proyecta_db" required>
            </div>
            
            <div class="form-group">
                <label>Usuario MySQL:</label>
                <input type="text" name="db_user" value="root" required>
            </div>
            
            <div class="form-group">
                <label>Contraseña MySQL:</label>
                <input type="password" name="db_pass" value="">
            </div>
            
            <h3>Cuenta Administrador</h3>
            
            <div class="form-group">
                <label>Email del administrador:</label>
                <input type="email" name="admin_email" value="admin@proyecta.com" required>
            </div>
            
            <div class="form-group">
                <label>Contraseña del administrador:</label>
                <input type="password" name="admin_password" required>
            </div>
            
            <button type="submit">Instalar PROYECTA</button>
        </form>
    </div>
</body>
</html>