<?php
require_once __DIR__ . '/../../config/database.php';

class TaskController
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'];
            $column_id = $_POST['column_id'];
            $project_id = $_POST['project_id'];

            $stmt = $this->conn->prepare("INSERT INTO tasks (project_id, column_id, title, reporter_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$project_id, $column_id, $title, $_SESSION['user_id']]);

            header("Location: /proyecta/public/board?project_id=" . $project_id);
            exit;
        }
    }

    public function move()
    {
        // API Endpoint for Drag & Drop
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['task_id']) && isset($data['column_id'])) {
            $stmt = $this->conn->prepare("UPDATE tasks SET column_id = ? WHERE id = ?");
            $success = $stmt->execute([$data['column_id'], $data['task_id']]);

            echo json_encode(['success' => $success]);
        }
    }
}
