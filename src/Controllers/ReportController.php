<?php
require_once __DIR__ . '/../../config/database.php';

class ReportController
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

        // Project Info
        $stmt = $this->conn->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        // Metrics: Tasks by Status (Column)
        // Join with columns to get status names
        $qStatus = "SELECT c.name as status, COUNT(t.id) as count 
                    FROM tasks t 
                    JOIN columns c ON t.column_id = c.id 
                    WHERE t.project_id = ? 
                    GROUP BY c.name";
        $stmt = $this->conn->prepare($qStatus);
        $stmt->execute([$projectId]);
        $tasksByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Metrics: Workload
        $qWorkload = "SELECT u.name, COUNT(t.id) as count 
                      FROM tasks t 
                      JOIN users u ON t.assignee_id = u.id 
                      WHERE t.project_id = ? 
                      GROUP BY u.name";
        $stmt = $this->conn->prepare($qWorkload);
        $stmt->execute([$projectId]);
        $tasksByUser = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../Views/reports.php';
    }
}
