<?php
require_once __DIR__ . '/../Models/User.php';

class AuthController
{

    public function login()
    {
        // Handle login POST request
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $user = new User();
            $user->email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($user->emailExists() && password_verify($password, $user->password)) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_role'] = $user->role;

                header("Location: /proyecta/public/dashboard");
                exit;
            } else {
                return "Invalid email or password.";
            }
        }
    }

    public function register()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $user = new User();
            $user->name = $_POST['name'] ?? '';
            $user->email = $_POST['email'] ?? '';
            $user->password = $_POST['password'] ?? '';
            $user->role = $_POST['role'] ?? 'Developer';

            if ($user->register()) {
                header("Location: /proyecta/public/login?registered=true");
                exit;
            } else {
                return "Registration failed. Email might be token.";
            }
        }
    }

    public function logout()
    {
        session_destroy();
        header("Location: /proyecta/public/login");
        exit;
    }
}
