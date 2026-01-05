<?php
session_start();

function isLoggedIn()
{
    // Self-heal session for avatar if missing
    if (isset($_SESSION['user_id']) && !isset($_SESSION['avatar_url'])) {
        $ds = DIRECTORY_SEPARATOR;
        // Adjust path based on where this file is included from (usually relative to includes/)
        // But safer to use __DIR__
        $path = __DIR__ . $ds . '..' . $ds . 'config' . $ds . 'database.php';

        if (file_exists($path)) {
            require_once $path;
            // Avoid re-declaring class if already declared
            if (class_exists('Database')) {
                $database = new Database();
                $db_conn = $database->getConnection();
                $query = "SELECT avatar_url, full_name FROM users WHERE id = :id";
                $stmt = $db_conn->prepare($query);
                $stmt->bindParam(':id', $_SESSION['user_id']);
                $stmt->execute();
                if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['avatar_url'] = $user['avatar_url'];
                    $_SESSION['full_name'] = $user['full_name'];
                }
            }
        }
    }
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /proyecta/index.php');
        exit();
    }
}

function requireRole($requiredRole)
{
    requireLogin();
    if ($_SESSION['user_role'] !== $requiredRole && $_SESSION['user_role'] !== 'admin') {
        header('Location: /proyecta/dashboard.php');
        exit();
    }
}

function loginUser($user)
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['avatar_url'] = $user['avatar_url'] ?? null;
}

function logoutUser()
{
    session_destroy();
    header('Location: /proyecta/index.php');
    exit();
}
?>