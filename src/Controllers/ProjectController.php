<?php
require_once __DIR__ . '/../../config/database.php';

class ProjectController
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
            $name = $_POST['name'];
            $org_id = $_POST['org_id'];
            $description = $_POST['description'];
            $created_by = $_SESSION['user_id'];

            $query = "INSERT INTO projects (name, organization_id, description, created_by) VALUES (:name, :org_id, :desc, :creator)";
            $stmt = $this->conn->prepare($query);

            if (
                $stmt->execute([
                    ':name' => $name,
                    ':org_id' => $org_id,
                    ':desc' => $description,
                    ':creator' => $created_by
                ])
            ) {
                $project_id = $this->conn->lastInsertId();

                // Create Default Board
                $qBoard = "INSERT INTO boards (project_id, name) VALUES (?, 'Tablero Principal')";
                $sBoard = $this->conn->prepare($qBoard);
                $sBoard->execute([$project_id]);
                $board_id = $this->conn->lastInsertId();

                // Create Default Columns
                $columns = ['Backlog', 'To Do', 'In Progress', 'Done'];
                $qCol = "INSERT INTO columns (board_id, name, position) VALUES (?, ?, ?)";
                $sCol = $this->conn->prepare($qCol);

                foreach ($columns as $index => $colName) {
                    // Start position at 1 or 0
                    $sCol->execute([$board_id, $colName, $index]);
                }

                // Add Creator as Project Manager
                $qMember = "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'Project Manager')";
                $sMember = $this->conn->prepare($qMember);
                $sMember->execute([$project_id, $created_by]);
            }

            header("Location: /proyecta/public/dashboard");
            exit;
        }
    }

    public function getAllByOrg($orgId)
    {
        $query = "SELECT * FROM projects WHERE organization_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
