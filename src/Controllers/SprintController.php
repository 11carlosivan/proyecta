<?php
require_once __DIR__ . '/../../config/database.php';

class SprintController
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

        $stmt = $this->conn->prepare("SELECT * FROM sprints WHERE project_id = ? ORDER BY start_date DESC");
        $stmt->execute([$projectId]);
        $sprints = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../Views/sprints.php';
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $project_id = $_POST['project_id'];
            $start = $_POST['start_date'];
            $end = $_POST['end_date'];

            $stmt = $this->conn->prepare("INSERT INTO sprints (project_id, name, start_date, end_date, status) VALUES (?, ?, ?, ?, 'Planning')");
            $stmt->execute([$project_id, $name, $start, $end]);

            header("Location: /proyecta/public/sprints?project_id=" . $project_id);
            exit;
        }
    }
}
