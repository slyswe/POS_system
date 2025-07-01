<?php
namespace App\Controllers;

use App\Models\UserModel;

ob_start(); // Buffer output to prevent header issues

class AuthController
{
    private $model;

    public function __construct()
    {
        session_start();
        require_once BASE_PATH . 'app/models/UserModel.php';
        $this->model = new UserModel();
    }

    public function showLoginForm()
    {
        $error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
        unset($_SESSION['error']);
        if (!defined('IN_CONTROLLER')) {
            define('IN_CONTROLLER', true);
        }
        include BASE_PATH . 'app/views/auth/login.php';
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            if (!$email || !$password) {
                $_SESSION['error'] = 'Email and password are required';
                header('Location: /pos/public/login');
                exit;
            }

            $user = $this->model->getUserByEmail($email);

            if (!$user) {
                error_log("Login failed: No user found for email: $email");
                $_SESSION['error'] = 'Invalid email or password';
                header('Location: /pos/public/login');
                exit;
            }

            if ($user['status'] !== 'active') {
                error_log("Login failed: Inactive user for email: $email");
                $_SESSION['error'] = 'Account is inactive';
                header('Location: /pos/public/login');
                exit;
            }

            if (!password_verify($password, $user['password_hash'])) {
                error_log("Login failed: Invalid password for email: $email");
                $_SESSION['error'] = 'Invalid email or password';
                header('Location: /pos/public/login');
                exit;
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            $this->model->updateLastLogin($user['id']);

           if ($user['role'] === 'admin') {
            header('Location: /pos/public/dashboard');
           } elseif ($user['role'] === 'cashier') {
            header('Location: /pos/public/sales/pos');
           } elseif ($user['role'] === 'inventory_clerk') {
            header('Location: /pos/public/products/inventory_clerk_dashboard');
           } else {
            $_SESSION['error'] = 'Invalid user role';
            header('Location: /pos/public/login');
           }
         exit;
           }
        $this->showLoginForm();
    }

    public function showRegisterForm()
    {
        if (!defined('IN_CONTROLLER')) {
            define('IN_CONTROLLER', true);
        }
        include BASE_PATH . 'app/views/auth/register.php';
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING),
                'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                'password' => $_POST['password'] ?? '',
                'address' => filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING),
                'phone' => filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING),
                'status' => filter_var($_POST['status'] ?? 'active', FILTER_SANITIZE_STRING),
                'role' => filter_var($_POST['role'] ?? 'Cashier', FILTER_SANITIZE_STRING)
            ];

            if (!$data['name'] || !$data['email'] || !$data['password']) {
                $_SESSION['error'] = 'Name, email, and password are required';
                header('Location: /pos/public/register');
                exit;
            }

            if ($this->model->emailExists($data['email'])) {
                $_SESSION['error'] = 'Email already registered';
                header('Location: /pos/public/register');
                exit;
            }

            if ($this->model->createUser($data)) {
                $_SESSION['success'] = 'Registration successful. Please login.';
                header('Location: /pos/public/login');
                exit;
            } else {
                $_SESSION['error'] = 'Registration failed';
                header('Location: /pos/public/register');
                exit;
            }
        }
        $this->showRegisterForm();
    }

    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();
        header('Location: /pos/public/login');
        exit;
    }
}