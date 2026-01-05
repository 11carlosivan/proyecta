<?php
require_once __DIR__ . '/config/database.php';

echo "Creando tablas para funcionalidades avanzadas de tareas...\n\n";

$database = new Database();
$db = $database->getConnection();

$sql = file_get_contents(__DIR__ . '/database/migrations/create_task_features.sql');

try {
    // Dividir por punto y coma para ejecutar cada CREATE TABLE por separado
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
            // Extraer nombre de tabla del statement
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?\s/i', $statement, $matches)) {
                echo "✅ Tabla '{$matches[1]}' creada\n";
            }
        }
    }

    echo "\n✅ Todas las tablas se crearon exitosamente!\n";
    echo "\nNuevas funcionalidades disponibles:\n";
    echo "  - Comentarios en tareas\n";
    echo "  - Subtareas/Checklist\n";
    echo "  - Registro de tiempo\n";
    echo "  - Observadores (Watchers)\n";
    echo "  - Relaciones entre tareas\n";
    echo "  - Historial de cambios\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>