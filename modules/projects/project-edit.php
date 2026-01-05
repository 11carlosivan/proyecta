<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$project_id = $_GET['id'] ?? null;

if (!$project_id) {
    header('Location: projects.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $query = "UPDATE projects SET 
              name = :name, 
              description = :description, 
              status = :status, 
              start_date = :start_date, 
              end_date = :end_date 
              WHERE id = :id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':id', $project_id);

    if ($stmt->execute()) {
        header("Location: project.php?id=$project_id");
        exit;
    }
}

// Obtener datos del proyecto
$query = "SELECT * FROM projects WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $project_id);
$stmt->execute();
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header('Location: projects.php');
    exit;
}

$page_title = "Editar Proyecto";
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

        <div class="p-6">
            <div
                class="max-w-2xl mx-auto bg-white dark:bg-card-dark rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h1 class="text-2xl font-bold mb-6">Editar Proyecto</h1>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nombre del Proyecto</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>"
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Descripción</label>
                        <textarea name="description" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Estado</label>
                            <select name="status"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                <option value="active" <?php echo $project['status'] == 'active' ? 'selected' : ''; ?>>
                                    Activo</option>
                                <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completado</option>
                                <option value="on_hold" <?php echo $project['status'] == 'on_hold' ? 'selected' : ''; ?>>
                                    En Pausa</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Fechas</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" name="start_date" value="<?php echo $project['start_date']; ?>"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                <input type="date" name="end_date" value="<?php echo $project['end_date']; ?>"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <a href="projects.php"
                            class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancelar
                        </a>
                        <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>

</html>