<?php
session_start();

require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/OrganizationController.php';
require_once __DIR__ . '/../src/Controllers/ProjectController.php';

// Simple Router
$request = $_SERVER['REQUEST_URI'];
$basePath = '/proyecta/public';
$path = str_replace($basePath, '', $request);
$path = strtok($path, '?');

// Routes
switch ($path) {
    case '/':
    case '/login':
        $controller = new AuthController();
        $controller->login();
        require __DIR__ . '/../src/Views/login.php';
        break;

    case '/register':
        $controller = new AuthController();
        $controller->register();
        require __DIR__ . '/../src/Views/register.php';
        break;

    case '/logout':
        $controller = new AuthController();
        $controller->logout();
        break;

    case '/dashboard':
        if (!isset($_SESSION['user_id'])) {
            header("Location: /proyecta/public/login");
            exit;
        }

        // Fetch data for dashboard
        $orgController = new OrganizationController();
        $orgs = $orgController->getAllByUser($_SESSION['user_id']);

        require __DIR__ . '/../src/Views/dashboard.php';
        break;

    case '/create-org':
        $controller = new OrganizationController();
        $controller->create();
        break;

    case '/create-project':
        $controller = new ProjectController();
        $controller->create();
        break;

    case '/board':
        require_once __DIR__ . '/../src/Controllers/BoardController.php';
        $controller = new BoardController();
        $projectId = $_GET['project_id'] ?? 0;
        $controller->view($projectId);
        break;

    case '/sprints':
        require_once __DIR__ . '/../src/Controllers/SprintController.php';
        $controller = new SprintController();
        $projectId = $_GET['project_id'] ?? 0;
        $controller->view($projectId);
        break;

    case '/reports':
        require_once __DIR__ . '/../src/Controllers/ReportController.php';
        $controller = new ReportController();
        $projectId = $_GET['project_id'] ?? 0;
        $controller->view($projectId);
        break;

    case '/create-sprint':
        require_once __DIR__ . '/../src/Controllers/SprintController.php';
        $controller = new SprintController();
        $controller->create();
        break;

    case '/create-task':
        require_once __DIR__ . '/../src/Controllers/TaskController.php';
        $controller = new TaskController();
        $controller->create();
        break;

    case '/move-task':
        require_once __DIR__ . '/../src/Controllers/TaskController.php';
        $controller = new TaskController();
        $controller->move();
        break;

    default:
        http_response_code(404);

        echo "404 Not Found";
        break;
}
