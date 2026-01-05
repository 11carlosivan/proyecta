<?php
require_once __DIR__ . '/../../config/database.php';

class BoardController
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function view($projectId)
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /proyecta/public/login");
            exit;
        }

        // Fetch Project Info
        $stmt = $this->conn->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            echo "Project not found";
            return;
        }

        // Fetch Board (assuming 1 board for now)
        $stmt = $this->conn->prepare("SELECT * FROM boards WHERE project_id = ? LIMIT 1");
        $stmt->execute([$projectId]);
        $board = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch Columns
        $stmt = $this->conn->prepare("SELECT * FROM columns WHERE board_id = ? ORDER BY position ASC");
        $stmt->execute([$board['id']]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Tasks
        $tasksByColumn = [];
        foreach ($columns as $col) {
            $stmt = $this->conn->prepare("
                SELECT t.*, u.name as assignee_name 
                FROM tasks t 
                LEFT JOIN users u ON t.assignee_id = u.id 
                WHERE t.column_id = ? 
                ORDER BY t.position ASC
            ");
            $stmt->execute([$col['id']]);
            $tasksByColumn[$col['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        require __DIR__ . '/../Views/board.php';
    }
}
