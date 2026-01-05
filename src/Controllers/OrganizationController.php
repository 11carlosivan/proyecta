<?php
require_once __DIR__ . '/../../config/database.php';

class OrganizationController
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
            $name = $_POST['name'] ?? '';
            $owner_id = $_SESSION['user_id'];

            if (empty($name))
                return;

            $query = "INSERT INTO organizations (name, owner_id) VALUES (:name, :owner_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':owner_id', $owner_id);

            if ($stmt->execute()) {
                // Also add as member
                $org_id = $this->conn->lastInsertId();
                $q2 = "INSERT INTO organization_members (organization_id, user_id, role) VALUES (?, ?, 'Owner')";
                $s2 = $this->conn->prepare($q2);
                $s2->execute([$org_id, $owner_id]);

                header("Location: /proyecta/public/dashboard");
                exit;
            }
        }
    }

    public function getAllByUser($userId)
    {
        $query = "SELECT o.*, om.role as user_role 
                  FROM organizations o 
                  JOIN organization_members om ON o.id = om.organization_id 
                  WHERE om.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
